<?php

namespace App\Livewire;

use App\Models\Bank;
use App\Models\Currency;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalePaymentDetail;
use App\Traits\PrintTrait;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartialPayment extends Component
{
    use PrintTrait;

    public $sales, $pays;
    public  $search, $sale_selected_id, $customer_name, $debt, $debt_usd;
    
    // Listeners
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

    public function handlePaymentCompleted($payments, $change, $changeDistribution)
    {
        if (!$this->sale_selected_id) return;

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
                    'payment_date' => \Carbon\Carbon::now() // Or allow user to pick date in component?
                ]);
                
                // Calculate USD amount for this payment
                $amountUSD = $amount / $exchangeRate;
                $totalPaidUSD += $amountUSD;
            }

            // Check if settled
            // Re-calculate total debt in USD
            $previousPaidUSD = $sale->payments->sum(function($p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                return $p->amount / $rate;
            });
            
            $newTotalPaidUSD = $previousPaidUSD + $totalPaidUSD;
            
            // Tolerance for floating point
            if ($newTotalPaidUSD >= ($sale->total_usd - 0.01)) {
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
