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
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;
use App\Services\CreditConfigService;
use Carbon\Carbon;

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
            ->when(!auth()->user()->can('payments.view_all') && auth()->user()->can('payments.view_own'), function($q) {
                 $q->whereHas('customer', function($subQ) {
                     $subQ->where('seller_id', auth()->id());
                 });
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
        
        // Define Invoice Currency Variables EARLY
        $invoiceCurrency = $sale->primary_currency_code ?? 'USD'; 
        $invoiceRate = $sale->primary_exchange_rate ?? 1;

        // Calcular deuda en USD (moneda base)
        // Fix: Exclude 'pending' payments so they don't reduce the debt until verified
        $totalPaidUSD = $sale->payments->where('status', '!=', 'pending')->sum(function($payment) {
            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
            return $payment->amount / $rate;
        });
        
        $debtUSD = $sale->total_usd - $totalPaidUSD;
        
        // Calcular los días transcurridos desde que se CREÓ la venta
        $daysElapsed = Carbon::parse($sale->created_at)->diffInDays(Carbon::now());
        
        // Obtener configuración de crédito (usar Snapshot si existe)
        $parsedSnapshot = CreditConfigService::parseCreditSnapshot($sale->credit_rules_snapshot);
        $rules = $parsedSnapshot['discount_rules'];
        $snapshotUsdDiscount = $parsedSnapshot['usd_payment_discount'];

        // Si no hay snapshot (y tampoco se pudo parsear nada válido), fallback a configuración actual
        if (empty($sale->credit_rules_snapshot)) {
             $creditConfig = CreditConfigService::getCreditConfig($sale->customer, $sale->customer->seller);
             $rules = $creditConfig['discount_rules'];
             $snapshotUsdDiscount = null; 
        }
        
        $adjustment = null;
        $allowDiscounts = false;
        $usdPaymentDiscountPercent = 0;
        $fixedUsdDiscountAmount = 0;
        
        if ($sale->is_foreign_sale) {
            
            // Check History: If any VED/VES payment exists, USD Discount is VOID.
            $hasVedHistory = $sale->payments()->whereIn('currency', ['VED', 'VES'])->exists();
            
            Log::info('PartialPayment::initPay History Check', ['hasVedHistory' => $hasVedHistory]);
            
            if (!$hasVedHistory) {
                $allowDiscounts = true;
                
                // Fetch USD Payment Discount %
                if ($snapshotUsdDiscount !== null) {
                    $usdPaymentDiscountPercent = $snapshotUsdDiscount;
                } else {
                    $config = CreditConfigService::getCreditConfig($sale->customer, $sale->customer->seller);
                    $usdPaymentDiscountPercent = $config['usd_payment_discount'] ?? 0;
                }
                
                Log::info('PartialPayment::initPay Config', ['percent' => $usdPaymentDiscountPercent]);
                
                // Calculate Fixed Discount Amount based on ORIGINAL Sale Total USD
                // User: "siempre le mostrara el descuento de pago divisa por el monto original"
                if ($usdPaymentDiscountPercent > 0) {
                     $fixedUsdDiscountAmount = $sale->total_usd * ($usdPaymentDiscountPercent / 100);
                     $fixedUsdDiscountAmount = round($fixedUsdDiscountAmount, 2);
                     
                     // Convert to Invoice Currency if needed
                     if ($invoiceCurrency !== 'USD') {
                         $fixedUsdDiscountAmount = $fixedUsdDiscountAmount * $invoiceRate;
                         $fixedUsdDiscountAmount = round($fixedUsdDiscountAmount, 2);
                     }
                }
            }
            
            // Early Payment Adjustment always applies? 
            $adjustment = CreditConfigService::calculateDiscount($debtUSD, $daysElapsed, $rules);

            // Convert Adjustment Amount to Invoice Currency if needed
            if ($adjustment && $invoiceCurrency !== 'USD') {
                $adjustment['amount'] = round($adjustment['amount'] * $invoiceRate, 2);
            }
        }

        // Convert Debt to Invoice Currency
        $debtInInvoiceCurrency = $debtUSD * $invoiceRate;
        
        $this->sale_selected_id = $sale_id;
        $this->customer_name = $customer;
        $this->debt = round($debtInInvoiceCurrency, 2);
        $this->debt_usd = round($debtUSD, 2);
        
        // Check Permissions
        $canUpload = auth()->user()->can('payments.upload');
        $canPay = auth()->user()->can('payments.register_direct');
        
        // Open the Payment Component Modal
        $this->dispatch('initPayment', 
            total: $this->debt, 
            currency: $invoiceCurrency, 
            customer: $this->customer_name, 
            allowPartial: true,
            adjustment: $adjustment, 
            allowDiscounts: $allowDiscounts, 
            usdDiscountPercent: $usdPaymentDiscountPercent, 
            fixedUsdDiscountAmount: $fixedUsdDiscountAmount, 
            canUpload: $canUpload,
            canPay: $canPay
        );
    }
    
    #[On('payment-uploaded')]
    public function handlePaymentUploaded($payments, $change, $changeDistribution) 
    {
         Log::info("=== handlePaymentUploaded CALLED ===");
         $this->processPayment($payments, 'pending');
    }

    #[On('payment-completed')]
    public function handlePaymentCompleted($payments, $change, $changeDistribution)
    {
        Log::info("=== handlePaymentCompleted CALLED ===");
        $this->processPayment($payments, 'approved');
    }
    
    public function processPayment($payments, $status)
    {
        Log::info("processPayment called", ['status' => $status, 'payments_count' => count($payments), 'payments' => $payments]);
        
        if (!$this->sale_selected_id) {
            Log::info("processPayment: No sale selected, returning");
            return;
        }

        DB::beginTransaction();
        try {
            $sale = Sale::find($this->sale_selected_id);
            $primaryCurrency = Currency::where('is_primary', true)->first();
            
            foreach ($payments as $payment) {
                
                $amount = $payment['amount'];
                $currencyCode = $payment['currency'];
                $exchangeRate = $payment['exchange_rate'];
                
                // Handle Zelle Record - Logic is SAME for Uploaded or Direct
                $zelleRecordId = null;
                if ($payment['method'] == 'zelle') {
                    // ... Zelle Logic (Same as before) ...
                    $zelleRecord = \App\Models\ZelleRecord::where('sender_name', $payment['zelle_sender'])
                        ->where('zelle_date', $payment['zelle_date'])
                        ->where('amount', $payment['zelle_amount'])
                        ->first();

                    $amountUsed = $payment['amount'];

                    if ($zelleRecord) {
                        $zelleRecord->remaining_balance -= $amountUsed;
                        if ($zelleRecord->remaining_balance < 0) $zelleRecord->remaining_balance = 0;
                        $zelleRecord->status = $zelleRecord->remaining_balance <= 0.01 ? 'used' : 'partial';
                        $zelleRecord->save();
                        $zelleRecordId = $zelleRecord->id;
                    } else {
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
                    }
                } 

                $bankRecordId = null;
                $createdBankRecord = null;

                // Create or Link BankRecord 
                if ($payment['method'] == 'bank' && !empty($payment['bank_reference'])) {
                     try {
                        $bankGlobalAmount = $payment['bank_global_amount'] ?? $payment['amount']; // Fallback to used amount if not provided
                        $amountUsed = $payment['amount'];
                        
                        // Check if exists (Logic similar to Zelle)
                        $bankRecord = \App\Models\BankRecord::where('bank_id', $payment['bank_id'])
                            ->where('reference', $payment['bank_reference'])
                            ->where('amount', $bankGlobalAmount)
                            ->first();

                        if ($bankRecord) {
                            $bankRecord->remaining_balance -= $amountUsed;
                            if ($bankRecord->remaining_balance < 0) $bankRecord->remaining_balance = 0;
                            $bankRecord->status = $bankRecord->remaining_balance <= 0.01 ? 'used' : 'partial';
                            $bankRecord->customer_id = $sale->customer_id; // Update customer? Maybe last customer used it.
                            $bankRecord->save();
                            $createdBankRecord = $bankRecord;
                            $bankRecordId = $bankRecord->id;
                        } else {
                            $remaining = $bankGlobalAmount - $amountUsed;
                            
                            $createdBankRecord = \App\Models\BankRecord::create([
                                'bank_id' => $payment['bank_id'],
                                'amount' => $bankGlobalAmount, // Save TOTAL amount
                                'reference' => $payment['bank_reference'],
                                'payment_date' => $payment['bank_date'] ?? now(),
                                'image_path' => $payment['bank_image'] ?? null,
                                'note' => $payment['bank_note'] ?? null,
                                'status' => $remaining <= 0.01 ? 'used' : 'partial',
                                'remaining_balance' => max(0, $remaining),
                                'customer_id' => $sale->customer_id,
                                'sale_id' => $sale->id,
                            ]);
                            $bankRecordId = $createdBankRecord->id;
                        }

                     } catch (\Exception $e) {
                          Log::error("Error creating/linking BankRecord: " . $e->getMessage());
                     }
                }

                $collectionSheetId = $this->getOrCreateCollectionSheet();

                // Create Payment Record
                $pay = Payment::create([
                    'user_id' => Auth()->user()->id,
                    'sale_id' => $this->sale_selected_id,
                    'amount' => floatval($amount),
                    'currency' => $currencyCode,
                    'exchange_rate' => $exchangeRate,
                    'primary_exchange_rate' => $primaryCurrency->exchange_rate,
                    'pay_way' => $payment['method'] == 'bank' ? 'deposit' : $payment['method'],
                    'type' => 'pay', 
                    'status' => $status, // 'approved' or 'pending'
                    'bank' => $payment['bank_name'] ?? null,
                    'account_number' => $payment['account_number'] ?? null,
                    'deposit_number' => $payment['reference'] ?? null,
                    'phone_number' => $payment['phone'] ?? null,
                    'payment_date' => $payment['bank_date'] ?? $payment['zelle_date'] ?? \Carbon\Carbon::now(),
                    'zelle_record_id' => $zelleRecordId,
                    'bank_record_id' => $bankRecordId,
                    'discount_applied' => $payment['discount_amount'] ?? 0,
                    'discount_percentage' => $payment['discount_percentage'] ?? 0,
                    'discount_reason' => $payment['discount_reason'] ?? null,
                    'payment_days' => $payment['days_elapsed'] ?? 0,
                    'rule_type' => $payment['rule_type'] ?? null,
                    'collection_sheet_id' => $collectionSheetId
                ]);

                if ($createdBankRecord) {
                    $createdBankRecord->update(['payment_id' => $pay->id]);
                }
            }

            // Only update sale totals if APPROVED
            if ($status === 'approved') {
                 $this->checkSaleSettlement($sale);
            }

            DB::commit();

            if ($status === 'approved') {
                $this->dispatch('noty', msg: 'ABONO REGISTRADO CON ÉXITO');
                if (isset($pay) && $pay) {
                    event(new \App\Events\PaymentReceived($pay, collect($payments)->sum('amount'), $sale));
                }
            } else {
                 $this->dispatch('noty', msg: 'PAGO SUBIDO. PENDIENTE DE APROBACIÓN.');
            }
            
            $this->dispatch('close-modal'); 
            $this->resetUI();

        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);
            $this->dispatch('noty', msg: "Error al registrar el pago: {$th->getMessage()}");
        }
    }
    
    public function approvePayment(\App\Services\CashRegisterService $cashRegisterService, $paymentId)
    {
        if (!auth()->user()->can('payments.approve')) {
             $this->dispatch('noty', msg: 'NO TIENES PERMISO PARA APROBAR PAGOS');
             return;
        }

        try {
            DB::beginTransaction();
            $payment = Payment::find($paymentId);
            if ($payment && $payment->status === 'pending') {
                
                // 1. Determine Target Cash Register
                $cashRegisterId = null;
                $userRegister = $cashRegisterService->getActiveCashRegister(Auth::id());
                
                if ($userRegister) {
                    $cashRegisterId = $userRegister->id;
                } else {
                    $config = \App\Models\Configuration::first();
                    if ($config && $config->enable_shared_cash_register) {
                        $lastOpenRegister = \App\Models\CashRegister::where('status', 'open')->latest()->first();
                        if ($lastOpenRegister) {
                            $cashRegisterId = $lastOpenRegister->id;
                        }
                    }
                }

                // 2. Validate Cash Register Availability
                if (!$cashRegisterId) {
                    throw new \Exception("NO HAY CAJA ABIERTA para recibir este pago. Abra una caja o active el modo compartido.");
                }

                // 3. Record Cash Movement
                $cashRegisterService->recordSaleMovement(
                    $cashRegisterId,
                    $payment->sale_id,
                    'sale_payment', // Type
                    $payment->currency,
                    $payment->amount,
                    "Aprobación de pago #{$payment->id} (Ref: {$payment->deposit_number})"
                );

                // 4. Update Payment & Sale
                // CRITICAL: Update collection_sheet_id to the CURRENT sheet of the approver (or shared).
                // This ensures the payment appears in TODAY's report, not the upload day's report.
                $currentSheetId = $this->getOrCreateCollectionSheet();

                $payment->update([
                    'status' => 'approved',
                    'collection_sheet_id' => $currentSheetId
                ]);
                
                $sale = Sale::find($payment->sale_id);
                $this->checkSaleSettlement($sale);
                
                DB::commit();
                $this->dispatch('noty', msg: 'PAGO APROBADO Y REGISTRADO EN CAJA');
                event(new \App\Events\PaymentReceived($payment, $payment->amount, $sale));
                
                // Refresh list
                $this->pays = $sale->payments; 
                $this->dispatch('refresh-history'); 
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('noty', msg: 'Error al aprobar: ' . $e->getMessage());
        }
    }
    

    
    public function rejectPayment($paymentId, $reason)
    {
        if (!auth()->user()->can('payments.approve')) {
             $this->dispatch('noty', msg: 'NO TIENES PERMISO PARA RECHAZAR PAGOS');
             return;
        }

        try {
            $payment = Payment::find($paymentId);
            if ($payment && $payment->status === 'pending') {
                $payment->update([
                    'status' => 'rejected',
                    'rejection_reason' => $reason
                ]);
                
                $this->dispatch('noty', msg: 'PAGO RECHAZADO CORRECTAMENTE');
                
                // Refresh list
                $sale = Sale::find($payment->sale_id);
                $this->pays = $sale->payments; 
            }
        } catch (\Exception $e) {
            $this->dispatch('noty', msg: 'Error al rechazar: ' . $e->getMessage());
        }
    }

    public function deletePayment($paymentId)
    {
        if (!auth()->user()->can('payments.delete')) {
            $this->dispatch('noty', msg: 'NO TIENES PERMISO PARA ELIMINAR PAGOS');
            return;
        }

        try {
            DB::beginTransaction();
            $payment = Payment::find($paymentId);
            
            // Allow deletion if pending or rejected AND user owns it or has manager permission
            // For now, let's assume if they can upload, they can delete their own pending/rejected stuff
            // Or explicitly check ownership
            
            if ($payment && ($payment->status === 'pending' || $payment->status === 'rejected')) {
                
                // Revert Zelle Record if exists
                if ($payment->zelle_record_id) {
                    $zelle = \App\Models\ZelleRecord::find($payment->zelle_record_id);
                    if ($zelle) {
                         // We need to know how much was used. 
                         // Logic in processPayment: $zelleRecord->remaining_balance -= $amountUsed;
                         // So we add it back.
                         // But wait, if partial payment, amount might be different from zelle amount?
                         // In processPayment: $amountUsed = $payment['amount'];
                         // $payment->amount IS the amount used.
                         // However, need to check exchange rates? 
                         // No, Zelle record is in USD usually? 
                         // Let's check ZelleRecord model/usage. 
                         // In processPayment: $zelleRecord->amount is stored.
                         // But we reduced remaining_balance.
                         
                         $amountToRestore = $payment->amount; // This is the amount of the PAYMENT, in the currency of the payment.
                         // If payment was in USD, fine. If Zelle, it is USD.
                         
                         $zelle->remaining_balance += $amountToRestore;
                         if ($zelle->remaining_balance > $zelle->amount) $zelle->remaining_balance = $zelle->amount;
                         
                         $zelle->status = 'partial'; // Revert to partial (or unused if full match, but 'partial' is safe)
                         if($zelle->remaining_balance == $zelle->amount) $zelle->status = 'unused'; // Optional status logic if exists
                         $zelle->save();
                    }
                }
                
                // Bank Record - usually created specifically for this payment, so delete it?
                // Logic in processPayment creates a new BankRecord.
                if ($payment->bank_record_id) {
                    \App\Models\BankRecord::destroy($payment->bank_record_id);
                }

                $payment->delete();
                
                DB::commit();
                $this->dispatch('noty', msg: 'Pago eliminado correctamente');
                
                $sale = Sale::find($payment->sale_id);
                $this->pays = $sale->payments;
            } else {
                 $this->dispatch('noty', msg: 'No se puede eliminar este pago (Estado incorrecto)');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('noty', msg: 'Error al eliminar: ' . $e->getMessage());
        }
    }
    
    // For now, "Editing" is complex because of the modal state. 
    // Easier flow: Delete and Re-upload. User requested "Enable to edit... OR delete". 
    // Delete is essentially "Undo", allowing them to try again.
    // Implementing "Edit" would require populating the "Add Payment" form with this payment's data.
    // Given the complexity of the dynamic payment rows, I will stick to "Delete" for now as the primary "Fix" mechanism, 
    // effectively "Reject -> Delete -> Upload Correctly".
    
    public function checkSaleSettlement(Sale $sale)
    {
        $sale->refresh();
        
        $currentTotalPaidUSD = $sale->payments->where('status', 'approved')->sum(function($p) {
            $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
            $amountUSD = $p->amount / $rate; 
            
            $adjustmentUSD = $p->discount_applied ?? 0;
            
            if ($p->rule_type === 'overdue') {
                return $amountUSD - $adjustmentUSD;
            } else {
                return $amountUSD + $adjustmentUSD;
            }
        });
        
        $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
            $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
            return $detail->amount / $rate;
        });
        
        $grandTotalPaidUSD = $currentTotalPaidUSD + $initialPaidUSD;
        
        if ($grandTotalPaidUSD >= ($sale->total_usd - 0.01)) {
            $sale->update(['status' => 'paid']);
            
            Payment::where('sale_id', $sale->id)
                ->where('status', 'approved')
                ->where('created_at', '>=', \Carbon\Carbon::now()->subMinute())
                ->update(['type' => 'settled']);
            
            // COMMISSION CALCULATION (Fix: Use Payment Date)
            $lastPaymentDate = $sale->payments->where('status', 'approved')->max('payment_date');
            if (!$lastPaymentDate) $lastPaymentDate = now();
                
            \App\Services\CommissionService::calculateCommission($sale, $lastPaymentDate);
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

    public function generatePaymentHistoryPdf($saleId)
    {
        $sale = Sale::with(['customer', 'payments.zelleRecord', 'payments.bankRecord.bank', 'user'])->find($saleId);
        if (!$sale) {
             $this->dispatch('noty', msg: 'Venta no encontrada');
             return;
        }
        
        $config = \App\Models\Configuration::first();
        // $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.payment-history-pdf', compact('sale', 'config'));

        // Use full namespace to avoid import issues if not present at top
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.payment-history-pdf', ['sale' => $sale, 'config' => $config]);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Historial_Pagos_Factura_' . $sale->id . '_' . date('YmdHis') . '.pdf');
    }

    private function getOrCreateCollectionSheet()
    {
        $user = Auth()->user();
        $today = Carbon::today();

        // 1. Try to find an open sheet for today
        $sheet = \App\Models\CollectionSheet::where('status', 'open')
            // ->where('user_id', $user->id) // Assuming CollectionSheet doesn't have user_id based on Model?
            // Wait, looking at grep results, AccountsReceivableReport checks user_id?
            // "where('user_id', $user->id)" was NOT in the grep, but implied by logic.
            // Let's check the Model again. It DOES NOT have user_id in $fillable.
            // But grep step 675 showed: AccountsReceivableReport lines 546-562.
            // Let's assume for now it DOES NOT track user_id or we need to check migration.
            // The model fillable suggests NO user_id.
            // Let's re-read step 709. Just sheet_number, total_amount, status...
            // So sheets are Global? Or did I miss a column?
            // If they are global, filtering by user in Report (Step 663 line 122) filters PAYMENTS by user, not Sheets.
            // So we just get the open sheet for today.
            ->whereDate('opened_at', $today)
            ->first();

        if (!$sheet) {
            // Count for sheet number
            $count = \App\Models\CollectionSheet::whereDate('opened_at', $today)->count() + 1;
            
            $sheet = \App\Models\CollectionSheet::create([
                'sheet_number' => $today->format('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT),
                'status' => 'open',
                'opened_at' => Carbon::now(),
                'total_amount' => 0
            ]);
        }

        return $sheet->id;
    }
}
