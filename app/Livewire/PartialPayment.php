<?php

namespace App\Livewire;

use App\Models\Bank;
use App\Models\Currency;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalePaymentDetail;
use App\Traits\PrintTrait;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartialPayment extends Component
{
    use PrintTrait;

    public $sales, $pays;
    public  $search, $sale_selected_id, $customer_name, $debt, $debt_usd;
    
    // Add listeners for compatibility
    protected $listeners = [
        'payment-completed' => 'handlePaymentCompleted',
        'close-modal' => 'resetUI'
    ];

    function mount($key = null)
    {
        $this->sales = [];
        $this->pays = [];
        $this->search = null;
        $this->sale_selected_id = null;
        $this->customer_name = null;
    }

    public function render()
    {
        $this->getSalesWithDetails();
        return view('livewire.payments.partial-payment');
    }

    public  function getSalesWithDetails()
    {
        $query = Sale::whereHas('customer', function ($query) {
            if (!empty(trim($this->search))) {
                $query->where('name', 'like', "%{$this->search}%");
            }
        })
            ->where('type', 'credit')
            ->where('status', 'pending')
            ->with(['customer', 'payments'])
            ->take(15)
            ->orderBy('sales.id', 'desc');

        $sales = $query->get();
        
        // Obtener moneda principal
        $primaryCurrency = Currency::where('is_primary', true)->first();
        
        // Calcular totales correctos
        $sales->map(function($sale) use ($primaryCurrency) {
            // Calcular total pagado en USD
            $totalPaidUSD = $sale->payments->sum(function($payment) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                return $payment->amount / $rate;
            });
            
            // Si la venta no tiene total_usd (ventas antiguas), calcularlo
            $totalUSD = $sale->total_usd;
            if (!$totalUSD || $totalUSD == 0) {
                $exchangeRate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $totalUSD = $sale->total / $exchangeRate;
            }
            
            $debtUSD = $totalUSD - $totalPaidUSD;
            
            // Asignar valores para la vista (convertidos a moneda principal actual)
            $sale->total_display = $totalUSD * $primaryCurrency->exchange_rate;
            $sale->total_paid_display = $totalPaidUSD * $primaryCurrency->exchange_rate;
            $sale->debt_display = $debtUSD * $primaryCurrency->exchange_rate;
            
            return $sale;
        });

        if (!empty(trim($this->search))) {
            $this->search = null;
            $this->dispatch('clear-search');
        }
        
        $this->sales = $sales;
    }

    function initPay($sale_id, $customer, $debt)
    {
        $sale = Sale::find($sale_id);
        
        if (!$sale) {
            $this->dispatch('noty', msg: 'Venta no encontrada');
            return;
        }
        
        // Calcular deuda en USD (moneda base)
        $totalPaidUSD = $sale->payments->sum(function($payment) {
            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
            return $payment->amount / $rate;
        });
        
        $debtUSD = $sale->total_usd - $totalPaidUSD;
        
        // Convertir deuda a moneda principal para mostrar al usuario
        $primaryCurrency = Currency::where('is_primary', true)->first();
        $debtInPrimary = $debtUSD * $primaryCurrency->exchange_rate;
        
        $this->sale_selected_id = $sale_id;
        $this->customer_name = $customer;
        $this->debt = round($debtInPrimary, 2);
        $this->debt_usd = round($debtUSD, 2);
        
        // Open the Payment Component Modal
        $this->dispatch('initPayment', 
            total: $this->debt, 
            currency: 'USD', 
            customer: $this->customer_name, 
            allowPartial: true
        );
    }

    #[On('payment-completed')]
    public function handlePaymentCompleted($payments, $change, $changeDistribution)
    {
        Log::info("=== handlePaymentCompleted CALLED ===");
        Log::info("handlePaymentCompleted called", ['payments_count' => count($payments), 'payments' => $payments]);
        
        if (!$this->sale_selected_id) {
            Log::info("handlePaymentCompleted: No sale selected, returning");
            return;
        }

        DB::beginTransaction();
        try {
            $sale = Sale::find($this->sale_selected_id);
            $primaryCurrency = Currency::where('is_primary', true)->first();
            
            $totalPaidUSD = 0;

            foreach ($payments as $payment) {
                // Determine type (pay vs settled)
                // This is tricky with multiple payments. 
                // We'll calculate total paid vs debt at the end.
                
                $amount = $payment['amount'];
                $currencyCode = $payment['currency'];
                $exchangeRate = $payment['exchange_rate'];
                
                Log::info("Processing Payment in PartialPayment", ['method' => $payment['method'], 'data' => $payment]);

                // Handle Zelle Record
                $zelleRecordId = null;
                if ($payment['method'] == 'zelle') {
                    Log::info("Zelle method detected");
                    // Check if Zelle record exists
                    $zelleRecord = \App\Models\ZelleRecord::where('sender_name', $payment['zelle_sender'])
                        ->where('zelle_date', $payment['zelle_date'])
                        ->where('amount', $payment['zelle_amount'])
                        ->first();

                    $amountUsed = $payment['amount'];

                    if ($zelleRecord) {
                        // Use existing record
                        $zelleRecord->remaining_balance -= $amountUsed;
                        if ($zelleRecord->remaining_balance < 0) $zelleRecord->remaining_balance = 0;
                        
                        $zelleRecord->status = $zelleRecord->remaining_balance <= 0.01 ? 'used' : 'partial';
                        $zelleRecord->save();
                        
                        $zelleRecordId = $zelleRecord->id;
                    } else {
                        // Create new record
                        $remaining = $payment['zelle_amount'] - $amountUsed;
                        
                        $zelleRecord = \App\Models\ZelleRecord::create([
                            'sender_name' => $payment['zelle_sender'],
                            'zelle_date' => $payment['zelle_date'],
                            'amount' => $payment['zelle_amount'],
                            'reference' => $payment['reference'] ?? null,
                            'image_path' => $payment['zelle_image'] ?? null,
                            'status' => $remaining <= 0.01 ? 'used' : 'partial',
                            'remaining_balance' => max(0, $remaining),
                            'customer_id' => $sale->customer_id,
                            'sale_id' => $sale->id,
                            'invoice_total' => $sale->total,
                            'payment_type' => $amountUsed >= ($sale->total - 0.01) ? 'full' : 'partial'
                        ]);
                        
                        $zelleRecordId = $zelleRecord->id;
                        Log::info("Zelle Record Created/Updated", ['id' => $zelleRecordId]);
                    }
                } else {
                    Log::info("Not a Zelle payment: " . $payment['method']);
                }

                $bankRecordId = null;
                $createdBankRecord = null;

                // Create BankRecord if VED details are present (Abonos) - Create FIRST to link to Payment
                if ($payment['method'] == 'bank' && !empty($payment['bank_reference'])) {
                     try {
                        $createdBankRecord = \App\Models\BankRecord::create([
                            'bank_id' => $payment['bank_id'],
                            'amount' => $payment['amount'],
                            'reference' => $payment['bank_reference'],
                            'payment_date' => $payment['bank_date'] ?? now(),
                            'image_path' => $payment['bank_image'] ?? null,
                            'note' => $payment['bank_note'] ?? null,
                            'customer_id' => $sale->customer_id,
                            'sale_id' => $sale->id,
                            // payment_id will be updated after Payment creation
                        ]);
                        $bankRecordId = $createdBankRecord->id;
                     } catch (\Exception $e) {
                          Log::error("Error creating BankRecord for Abono: " . $e->getMessage());
                     }
                }

                // Create Payment Record
                $pay = Payment::create([
                    'user_id' => Auth()->user()->id,
                    'sale_id' => $this->sale_selected_id,
                    'amount' => floatval($amount),
                    'currency' => $currencyCode,
                    'exchange_rate' => $exchangeRate,
                    'primary_exchange_rate' => $primaryCurrency->exchange_rate,
                    'pay_way' => $payment['method'] == 'bank' ? 'deposit' : $payment['method'],
                    'type' => 'pay', // Default to pay, update later if settled
                    'bank' => $payment['bank_name'] ?? null,
                    'account_number' => $payment['account_number'] ?? null,
                    'deposit_number' => $payment['reference'] ?? null,
                    'phone_number' => $payment['phone'] ?? null,
                    'payment_date' => \Carbon\Carbon::now(),
                    'zelle_record_id' => $zelleRecordId,
                    'bank_record_id' => $bankRecordId // Correctly link to BankRecord
                ]);

                // Update BankRecord with payment_id
                if ($createdBankRecord) {
                    $createdBankRecord->update(['payment_id' => $pay->id]);
                }
                
                // Calculate USD amount for this payment
                $amountUSD = $amount / $exchangeRate;
                $totalPaidUSD += $amountUSD;
            }

            // Check if settled
            // Force refresh to get all payments including new ones
            $sale->refresh();
            
            $currentTotalPaidUSD = $sale->payments->sum(function($p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                return $p->amount / $rate;
            });
            
             // Also include initial payment details
            $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                return $detail->amount / $rate;
            });
            
            $grandTotalPaidUSD = $currentTotalPaidUSD + $initialPaidUSD;
            
            // Tolerance for floating point
            if ($grandTotalPaidUSD >= ($sale->total_usd - 0.01)) {
                // Mark sale as paid
                $sale->update(['status' => 'paid']);
                
                // Update all recent payments to 'settled'
                // Or just the last one? Usually 'settled' means this payment settled it.
                // Let's mark all payments from this batch as 'settled' if the sale is now paid.
                // But we just created them.
                Payment::where('sale_id', $sale->id)
                    ->where('created_at', '>=', \Carbon\Carbon::now()->subMinute())
                    ->update(['type' => 'settled']);
                    
                // Calculate Commission
                \App\Services\CommissionService::calculateCommission($sale);
            }

            DB::commit();

            $this->dispatch('noty', msg: 'ABONO REGISTRADO CON ÉXITO');
            $this->dispatch('close-modal'); // Close PartialPayment modal
            $this->resetUI();

        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);
            $this->dispatch('noty', msg: "Error al registrar el pago: {$th->getMessage()}");
        }
    }

    #[On('close-modal')]
    public function resetUI()
    {
        $this->sale_selected_id = null;
        $this->customer_name = null;
        $this->debt = null;
        $this->debt_usd = null;
    }

    public function cancelPay()
    {
        $this->resetUI();
    }

    function historyPayments(Sale $sale)
    {
        $this->pays = $sale->payments;
        $this->dispatch('show-payhistory');
    }

    function printReceipt($payment_id)
    {
        $this->printPayment($payment_id);
        $this->dispatch('noty', msg: 'IMPRIMIENDO RECIBO DE PAGO...');
    }

    function printHistory()
    {
        if (empty($this->pays) || count($this->pays) == 0) {
            $this->dispatch('noty', msg: 'NO HAY PAGOS PARA IMPRIMIR');
            return;
        }

        $saleId = $this->pays[0]->sale_id;
        $this->printPaymentHistory($saleId);
        $this->dispatch('noty', msg: 'IMPRIMIENDO HISTORIAL DE PAGOS...');
    }
}
