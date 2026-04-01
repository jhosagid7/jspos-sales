<?php

namespace App\Livewire;

use App\Models\Bank;
use App\Models\Currency;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalePaymentDetail;
use App\Traits\PrintTrait;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;
use App\Services\CreditConfigService;
use Carbon\Carbon;

use App\Traits\CollectionSheetTrait;

class PartialPayment extends Component
{
    use WithPagination, CollectionSheetTrait;

    protected $paginationTheme = 'bootstrap';

    public $pays;
    public $search, $sale_selected_id, $customer_name, $debt, $debt_usd;
    public $history_sale_id;
    public $editingPaymentId, $editPaymentRef, $editPaymentAmount, $editPaymentDate, $editPaymentRate, $editPaymentComment;
    public $editApplyEarlyDiscount = false, $editApplyUsdDiscount = false;
    public $editEarlyDiscountAmount = 0, $editUsdDiscountAmount = 0;
    public $editEarlyDiscountPercent = 0, $editUsdDiscountPercent = 0;
    public $editEarlyDiscountReason = '', $editUsdDiscountReason = '';
    public $editSaleTotal = 0, $editSalePaid = 0, $editSaleDebt = 0;
    
    // Add listeners for compatibility
    protected $listeners = [
        'payment-completed' => 'handlePaymentCompleted',
        'close-modal' => 'resetUI'
    ];

    public function mount($key = null)
    {
        $this->pays = [];
        $this->search = null;
        $this->sale_selected_id = null;
        $this->customer_name = null;
        
        $this->editingPaymentId = null;
        $this->editPaymentRef = '';
        $this->editPaymentAmount = 0;
        $this->editPaymentDate = date('Y-m-d');
        $this->editPaymentRate = 1;
        $this->editPaymentComment = '';
    }

    public function render()
    {
        $sales = $this->getSalesWithDetails();
        return view('livewire.payments.partial-payment', [
            'sales' => $sales
        ]);
    }

    public function getSalesWithDetails()
    {
        $sales = Sale::where(function ($query) {
            if (!empty(trim($this->search))) {
                $searchValue = trim($this->search);
                
                // Search by Customer Name
                $query->whereHas('customer', function ($subQuery) use ($searchValue) {
                    $subQuery->where('name', 'like', "%{$searchValue}%")
                        ->orWhereHas('seller', function ($sellerQuery) use ($searchValue) {
                            $sellerQuery->where('name', 'like', "%{$searchValue}%");
                        });
                });

                // Check if search resembles an Invoice ID
                $saleId = 0;
                if (is_numeric($searchValue)) {
                    $saleId = (int)$searchValue;
                } elseif (preg_match('/^[Ff]0*([1-9][0-9]*)$/', $searchValue, $matches)) {
                    $saleId = (int)$matches[1];
                }

                // Append OR condition for exact matching Sale ID
                if ($saleId > 0) {
                    $query->orWhere('id', $saleId);
                }
            }
        })
            ->when(!auth()->user()->can('payments.view_all') && auth()->user()->can('payments.view_own'), function($q) {
                 $q->whereHas('customer', function($subQ) {
                     $subQ->where('seller_id', auth()->id());
                 });
            })
            ->where('type', 'credit')
            ->where('status', 'pending')
            ->with(['customer.seller', 'payments', 'returns'])
            ->orderBy('sales.id', 'desc')
            ->paginate(5);

        // Obtener moneda principal
        $primaryCurrency = Currency::where('is_primary', true)->first();
        
        // Calcular totales correctos
        $sales->getCollection()->transform(function($sale) use ($primaryCurrency) {
            // Calcular total pagado en USD
            $totalPaidUSD = $sale->payments->whereNotIn('status', ['pending', 'rejected'])->sum(function($p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                $amountUSD = $p->amount / $rate; 
                
                $discountVal = $p->discount_applied ?? 0;
                if ($p->rule_type === 'overdue') {
                    return $amountUSD - $discountVal;
                } else {
                    return $amountUSD + $discountVal;
                }
            });
            
            // Pagos iniciales (si los hay)
            $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                return $detail->amount / $rate;
            });
            
            // Si la venta no tiene total_usd (ventas antiguas), calcularlo
            $totalUSD = $sale->total_usd;
            if (!$totalUSD || $totalUSD == 0) {
                $exchangeRate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $totalUSD = $sale->total / $exchangeRate;
            }
            
            // Notas de Crédito (devoluciones y ajustes manuales)
            $totalReturnsOrig = $sale->returns->where('refund_method', 'debt_reduction')->sum('total_returned');
            $exchangeRateReturns = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
            $totalReturnsUSD = $totalReturnsOrig / $exchangeRateReturns;
            
            // Cálculo del saldo real
            $debtUSD = max(0, $totalUSD - ($totalPaidUSD + $initialPaidUSD + $totalReturnsUSD));
            
            // Asignar valores para la vista (convertidos a moneda principal actual)
            $sale->total_display = $totalUSD * $primaryCurrency->exchange_rate;
            $sale->total_paid_display = ($totalPaidUSD + $initialPaidUSD) * $primaryCurrency->exchange_rate;
            $sale->total_returns_display = $totalReturnsUSD * $primaryCurrency->exchange_rate;
            $sale->debt_display = $debtUSD * $primaryCurrency->exchange_rate;
            
            return $sale;
        });

        return $sales;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function initPay($sale_id, $customer, $debt)
    {
        $sale = Sale::with('customer')->find($sale_id);
        
        if (!$sale) {
            $this->dispatch('noty', msg: 'Venta no encontrada');
            return;
        }
        
        $invoiceCurrency = $sale->primary_currency_code ?? 'USD'; 
        $invoiceRate = $sale->primary_exchange_rate ?? 1;

        $totalPaidUSD = $sale->payments->whereNotIn('status', ['pending', 'rejected'])->sum(function($payment) {
            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
            return $payment->amount / $rate;
        });
        
        $totalReturnsOrig = $sale->returns->where('refund_method', 'debt_reduction')->sum('total_returned');
        $exchangeRateReturns = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
        $totalReturnsUSD = $totalReturnsOrig / $exchangeRateReturns;
        
        $netTotalUSD = max(0, $sale->total_usd - $totalReturnsUSD);
        $debtUSD = max(0, $netTotalUSD - $totalPaidUSD);
        
        $daysElapsed = Carbon::parse($sale->created_at)->diffInDays(Carbon::now());
        
        $parsedSnapshot = CreditConfigService::parseCreditSnapshot($sale->credit_rules_snapshot);
        $rules = $parsedSnapshot['discount_rules'];
        $snapshotUsdDiscount = $parsedSnapshot['usd_payment_discount'];

        if (empty($sale->credit_rules_snapshot)) {
            $creditConfig = CreditConfigService::getCreditConfig($sale->customer, $sale->customer->seller);
            $rules = $creditConfig['discount_rules'];
            $snapshotUsdDiscount = null; 
        }
        
        $earlyDiscountAdjustment = null;
        $allowDiscounts = false;
        $usdPaymentDiscountPercent = 0;
        $fixedUsdDiscountAmount = 0;
        
        $hasVedHistory = $sale->payments()->whereIn('currency', ['VED', 'VES'])->exists();
        $customerUsdDiscount = $sale->customer->usd_payment_discount ?? 0;
        
        if ($sale->is_foreign_sale || $snapshotUsdDiscount > 0 || $customerUsdDiscount > 0) {
            if (!$hasVedHistory || auth()->user()->can('payments.force_discounts')) {
                $allowDiscounts = true;
                $usdPaymentDiscountPercent = $snapshotUsdDiscount ?? $customerUsdDiscount;
                
                if ($usdPaymentDiscountPercent > 0) {
                    $fixedUsdDiscountAmount = round($netTotalUSD * ($usdPaymentDiscountPercent / 100) * $invoiceRate, 2);
                }
            }
        }
        
        $earlyDiscountAdjustment = CreditConfigService::calculateDiscount($debtUSD, $daysElapsed, $rules);
        
        if (!$earlyDiscountAdjustment && auth()->user()->can('payments.force_discounts')) {
            $baseRule = $rules->where('rule_type', 'early_payment')->first();
            if ($baseRule) {
                $earlyDiscountAdjustment = [
                    'amount' => round($debtUSD * ($baseRule->discount_percentage / 100), 2),
                    'percentage' => $baseRule->discount_percentage,
                    'reason' => 'Forzado: ' . ($baseRule->description ?? 'Pronto Pago Base'),
                    'days' => $daysElapsed,
                    'rule_type' => 'early_payment',
                    'tag' => $baseRule->tag ?? null
                ];
            }
        }

        if ($earlyDiscountAdjustment && $invoiceCurrency !== 'USD') {
            $earlyDiscountAdjustment['amount'] = round($earlyDiscountAdjustment['amount'] * $invoiceRate, 2);
        }

        $debtInInvoiceCurrency = $debtUSD * $invoiceRate;
        
        $this->sale_selected_id = $sale_id;
        $this->customer_name = $customer;
        $this->debt = round($debtInInvoiceCurrency, 2);
        $this->debt_usd = round($debtUSD, 2);
        
        $canUpload = auth()->user()->can('payments.upload');
        $canPay = auth()->user()->can('payments.register_direct');
        
        $this->dispatch('initPayment', 
            $this->debt, 
            $invoiceCurrency, 
            $this->customer_name, 
            true,
            $earlyDiscountAdjustment, 
            $allowDiscounts, 
            $usdPaymentDiscountPercent, 
            $fixedUsdDiscountAmount, 
            $canUpload,
            $canPay,
            $sale->customer->id ?? $sale->customer_id,
            $sale->customer->wallet_balance ?? 0
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

                if ($payment['method'] == 'credit_note') {
                    $collectionSheetId = $this->getOrCreateCollectionSheet();

                    \App\Models\SaleReturn::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $sale->customer_id,
                        'user_id' => auth()->id(),
                        'return_number' => 'NC-' . strtoupper(\Illuminate\Support\Str::random(6)),
                        'total_returned' => $payment['amount_in_invoice_currency'] ?? $amount,
                        'reason' => $payment['note'] ?? 'Nota de Crédito Manual',
                        'return_type' => 'manual',
                        'refund_method' => 'debt_reduction',
                        'collection_sheet_id' => $this->getOrCreateCollectionSheet(),
                        'status' => 'approved'
                    ]);
                    continue;
                }
                
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

                if ($payment['method'] === 'wallet') {
                    if ($status === 'approved') {
                        $customer = \App\Models\Customer::find($sale->customer_id);
                        if ($customer) {
                            $customer->wallet_balance -= floatval($payment['amount_in_primary']);
                            $customer->save();
                            Log::info("Wallet balance deducted for customer #{$customer->id} (Direct Payment)");
                        }
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
                    'payment_date' => $payment['payment_date'] ?? $payment['bank_date'] ?? $payment['zelle_date'] ?? \Carbon\Carbon::now(),
                    'zelle_record_id' => $zelleRecordId,
                    'bank_record_id' => $bankRecordId,
                    'discount_applied' => $payment['discount_amount'] ?? 0,
                    'discount_percentage' => $payment['discount_percentage'] ?? 0,
                    'discount_reason' => $payment['discount_reason'] ?? null,
                    'discount_tag' => $payment['discount_tag'] ?? null,
                    'payment_days' => $payment['days_elapsed'] ?? 0,
                    'rule_type' => $payment['rule_type'] ?? null,
                    'collection_sheet_id' => $collectionSheetId
                ]);

                if ($createdBankRecord) {
                    $createdBankRecord->update(['payment_id' => $pay->id]);
                }
                // Standardized Rule: Only increment collection sheet total if APPROVED
                if ($status === 'approved') {
                    $amountUSD = $amount / ($exchangeRate > 0 ? $exchangeRate : 1);
                    $sheet = \App\Models\CollectionSheet::find($collectionSheetId);
                    if ($sheet) {
                        $sheet->increment('total_amount', $amountUSD);
                    }
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

                // 4. Handle Collection Sheet Transition
                $oldSheetId = $payment->collection_sheet_id;
                $currentSheetId = $this->getOrCreateCollectionSheet();
                $currentSheet = \App\Models\CollectionSheet::find($currentSheetId);

                // If moving from another sheet, decrement old one (safety)
                if ($oldSheetId && $oldSheetId != $currentSheetId) {
                    $oldSheet = \App\Models\CollectionSheet::find($oldSheetId);
                    if ($oldSheet && $payment->status === 'approved') { // Safety check
                         $amountUSD = $payment->amount / ($payment->exchange_rate > 0 ? $payment->exchange_rate : 1);
                         $oldSheet->decrement('total_amount', $amountUSD);
                    }
                }

                $payment->update([
                    'status' => 'approved',
                    'collection_sheet_id' => $currentSheetId
                ]);

                // Increment TODAY'S sheet total
                $amountUSD = $payment->amount / ($payment->exchange_rate > 0 ? $payment->exchange_rate : 1);
                $currentSheet->increment('total_amount', $amountUSD);

                // Deduct from wallet if applicable
                if ($payment->pay_way === 'wallet') {
                    $customer = \App\Models\Customer::find($payment->sale->customer_id);
                    if ($customer) {
                        // For Payments table, we need to convert to primary currency
                        $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                        $primaryRate = $payment->primary_exchange_rate > 0 ? $payment->primary_exchange_rate : 1;
                        $amountInUSD = $payment->amount / $rate;
                        $amountInPrimary = $amountInUSD * $primaryRate;

                        $customer->wallet_balance -= floatval($amountInPrimary);
                        $customer->save();
                        Log::info("Wallet balance deducted for customer #{$customer->id} (Payment Approved)");
                    }
                }
                
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
                $zelleRecordId = $payment->zelle_record_id;
                if ($zelleRecordId) {
                    $zelle = \App\Models\ZelleRecord::find($zelleRecordId);
                    if ($zelle) {
                         $amountToRestore = $payment->amount; 
                         $zelle->remaining_balance += $amountToRestore;
                         if ($zelle->remaining_balance > $zelle->amount) $zelle->remaining_balance = $zelle->amount;
                         
                         $zelle->status = ($zelle->remaining_balance >= ($zelle->amount - 0.01)) ? 'unused' : 'partial';
                         $zelle->save();
                    }
                }
                
                // Bank Record - Restore balance
                $bankRecordId = $payment->bank_record_id;
                if ($bankRecordId) {
                    $bankRec = \App\Models\BankRecord::find($bankRecordId);
                    if ($bankRec) {
                        $bankRec->remaining_balance += $payment->amount; // Restaurar saldo
                        if ($bankRec->remaining_balance > $bankRec->amount) $bankRec->remaining_balance = $bankRec->amount;
                        $bankRec->status = ($bankRec->remaining_balance >= ($bankRec->amount - 0.01)) ? 'unused' : 'partial';
                        $bankRec->save();
                    }
                }
                
                // Revert Collection Sheet total if it was APPROVED
                if ($payment->collection_sheet_id && $payment->status === 'approved') {
                    $sheet = \App\Models\CollectionSheet::find($payment->collection_sheet_id);
                    if ($sheet) {
                        $amountUSD = $payment->amount / ($payment->exchange_rate > 0 ? $payment->exchange_rate : 1);
                        $sheet->decrement('total_amount', $amountUSD);
                    }
                }

                $payment->delete(); // Delete payment first
                
                // Final cleanup: Delete records if they have NO more payments
                if ($zelleRecordId) {
                    if (!\App\Models\Payment::where('zelle_record_id', $zelleRecordId)->exists()) {
                        \App\Models\ZelleRecord::destroy($zelleRecordId);
                    }
                }

                if ($bankRecordId) {
                    if (!\App\Models\Payment::where('bank_record_id', $bankRecordId)->exists()) {
                        \App\Models\BankRecord::destroy($bankRecordId);
                    }
                }
                
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
        $sale->checkSettlement();
    }

    #[On('close-modal')]
    public function resetUI()
    {
        $this->sale_selected_id = null;
        $this->customer_name = null;
        $this->debt = null;
        $this->debt_usd = null;
    }

    public function resetCreditSnapshot($saleId)
    {
        if (!auth()->user()->can('sales.reset_credit_snapshot')) {
            $this->dispatch('noty', msg: 'NO TIENES PERMISO PARA ESTA ACCIÓN');
            return;
        }

        $sale = Sale::find($saleId);
        if ($sale) {
            $creditConfig = \App\Services\CreditConfigService::getCreditConfig($sale->customer, $sale->customer->seller);
            
            $snapshotToSave = [
                'discount_rules' => $creditConfig['discount_rules']->toArray(),
                'usd_payment_discount' => $creditConfig['usd_payment_discount'],
                'usd_payment_discount_tag' => $creditConfig['usd_payment_discount_tag']
            ];

            $sale->update(['credit_rules_snapshot' => $snapshotToSave]);
            $this->dispatch('noty', msg: 'REGLAS DE CRÉDITO ACTUALIZADAS Y FIJADAS A LA CONFIGURACIÓN DEL CLIENTE (INMUTABLE)');
            // Also refresh the history view
            $this->historyPayments($sale);
        }
    }

    public function editPayment($paymentId)
    {
        if (!auth()->user()->can('payments.approve')) {
             $this->dispatch('noty', msg: 'NO TIENES PERMISO PARA EDITAR PAGOS');
             return;
        }
        
        $payment = Payment::find($paymentId);
        if ($payment && $payment->status === 'pending') {
            $this->editingPaymentId = $payment->id;
            $this->editPaymentAmount = number_format($payment->amount, 2, '.', '');
            $this->editPaymentRate = number_format($payment->exchange_rate, 4, '.', '');
            // Strip trailing zeros after the decimal point to show neat numbers (e.g. 420.0000 -> 420)
            if (strpos($this->editPaymentRate, '.') !== false) {
                $this->editPaymentRate = rtrim(rtrim($this->editPaymentRate, '0'), '.');
            }
            $this->editPaymentComment = $payment->modification_comment ?? '';
            $this->editPaymentRef = $payment->deposit_number ?? $payment->reference_number ?? ($payment->zelleRecord->reference ?? $payment->bankRecord->reference ?? '');
            $this->editPaymentDate = $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : date('Y-m-d');
            
            // Re-calculate potential discounts 
            $this->editApplyEarlyDiscount = false;
            $this->editApplyUsdDiscount = false;
            $this->editEarlyDiscountAmount = 0;
            $this->editUsdDiscountAmount = 0;
            $this->editEarlyDiscountPercent = 0;
            $this->editUsdDiscountPercent = 0;
            $this->editEarlyDiscountReason = '';
            $this->editUsdDiscountReason = '';
            $this->editSaleTotal = 0;
            $this->editSalePaid = 0;
            $this->editSaleDebt = 0;

            $sale = Sale::find($payment->sale_id);
            if ($sale) {
                // Calculate debt overview
                $this->editSaleTotal = $sale->total_usd > 0 ? $sale->total_usd : ($sale->total / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1));
                
                $totalPaidUSD = $sale->payments->whereNotIn('status', ['pending', 'rejected'])->sum(function($p) {
                    $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                    return ($p->amount / $rate) + ($p->discount_applied ?? 0);
                });
                $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                    $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                    return $detail->amount / $rate;
                });
                $totalReturnsOrig = $sale->returns->where('refund_method', 'debt_reduction')->sum('total_returned');
                $totalReturnsUSD = $totalReturnsOrig / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1);
                
                $this->editSalePaid = $totalPaidUSD + $initialPaidUSD + $totalReturnsUSD;
                $this->editSaleDebt = max(0, $this->editSaleTotal - $this->editSalePaid);

                // Parse snapshot
                $parsedSnapshot = \App\Services\CreditConfigService::parseCreditSnapshot($sale->credit_rules_snapshot);
                $rules = $parsedSnapshot['discount_rules'];
                $snapshotUsdDiscount = $parsedSnapshot['usd_payment_discount'];

                if (empty($sale->credit_rules_snapshot)) {
                    $creditConfig = \App\Services\CreditConfigService::getCreditConfig($sale->customer, $sale->customer->seller);
                    $rules = $creditConfig['discount_rules'];
                    $snapshotUsdDiscount = null;
                }

                $daysElapsed = \Carbon\Carbon::parse($sale->created_at)->diffInDays(\Carbon\Carbon::parse($payment->payment_date ?? $payment->created_at));
                $adjustment = \App\Services\CreditConfigService::calculateDiscount($sale->total_usd, $daysElapsed, $rules);

                if ($adjustment) {
                    $this->editEarlyDiscountAmount = $adjustment['amount'];
                    $this->editEarlyDiscountPercent = $adjustment['percentage'];
                    $this->editEarlyDiscountReason = $adjustment['reason'];
                } elseif (auth()->user()->can('payments.force_discounts')) {
                    $baseRule = $rules->where('rule_type', 'early_payment')->first();
                    if ($baseRule) {
                        $this->editEarlyDiscountPercent = $baseRule->discount_percentage;
                        $this->editEarlyDiscountAmount = round($sale->total_usd * ($baseRule->discount_percentage / 100), 2);
                        $this->editEarlyDiscountReason = 'Forzado: ' . ($baseRule->description ?? 'Pronto Pago Base');
                    }
                }

                $usdPaymentDiscountPercent = 0;
                $isUsdForced = false;
                
                if ($sale->is_foreign_sale) {
                    // Check Ved History like in initPay
                    $hasVedHistory = $sale->payments()->whereIn('currency', ['VED', 'VES'])->exists();
                    
                    if (!$hasVedHistory || auth()->user()->can('payments.force_discounts')) {
                        if ($snapshotUsdDiscount !== null) {
                            $usdPaymentDiscountPercent = $snapshotUsdDiscount;
                        } else {
                            $config = \App\Services\CreditConfigService::getCreditConfig($sale->customer, $sale->customer->seller);
                            $usdPaymentDiscountPercent = $config['usd_payment_discount'] ?? 0;
                        }
                        
                        if ($hasVedHistory) {
                            $isUsdForced = true;
                        }
                    }

                    if ($usdPaymentDiscountPercent > 0) {
                        $this->editUsdDiscountAmount = round($sale->total_usd * ($usdPaymentDiscountPercent / 100), 2);
                        $this->editUsdDiscountPercent = $usdPaymentDiscountPercent;
                        $this->editUsdDiscountReason = 'Descuento Pago Divisa' . ($isUsdForced ? ' (Forzado)' : '');
                    }
                }

                // Check BD status
                $ruleType = $payment->rule_type ?? '';
                if (in_array($ruleType, ['early_payment', 'combined'])) {
                    $this->editApplyEarlyDiscount = true;
                }
                if (in_array($ruleType, ['usd_payment', 'combined'])) {
                    $this->editApplyUsdDiscount = true;
                }
                if (empty($ruleType) && $payment->discount_applied > 0) {
                    $this->editApplyEarlyDiscount = true; // Fallback
                }
            }
            
            $this->dispatch('show-edit-payment-modal');
        }
    }

    public function updatePayment()
    {
        if (!auth()->user()->can('payments.approve')) {
             $this->dispatch('noty', msg: 'NO TIENES PERMISO PARA EDITAR PAGOS');
             return;
        }
        
        $this->validate([
            'editPaymentAmount' => 'required|numeric|min:0.01',
            'editPaymentRate' => 'required|numeric|min:0.01',
            'editPaymentRef' => 'required|string',
            'editPaymentDate' => 'required|date',
            'editPaymentComment' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            $payment = Payment::find($this->editingPaymentId);
            
            if ($payment && $payment->status === 'pending') {
                $payment->amount = $this->editPaymentAmount;
                $payment->exchange_rate = $this->editPaymentRate;
                $payment->deposit_number = $this->editPaymentRef;
                $payment->payment_date = $this->editPaymentDate;
                $payment->modification_comment = $this->editPaymentComment;
                
                // Recalculate and apply discount fields
                $totalDiscount = 0;
                $totalPercent = 0;
                $reason = '';
                $ruleType = '';
                
                if ($this->editApplyEarlyDiscount) {
                     $totalDiscount += $this->editEarlyDiscountAmount;
                     $totalPercent += $this->editEarlyDiscountPercent;
                     $reason = $this->editEarlyDiscountReason;
                     $ruleType = 'early_payment';
                }
                
                if ($this->editApplyUsdDiscount) {
                     $totalDiscount += $this->editUsdDiscountAmount;
                     $totalPercent += $this->editUsdDiscountPercent;
                     if ($reason) $reason .= ' + ';
                     $reason .= $this->editUsdDiscountReason;
                     $ruleType = $ruleType ? 'combined' : 'usd_payment';
                }

                $payment->discount_applied = $totalDiscount;
                $payment->discount_percentage = $totalPercent;
                $payment->discount_reason = $reason;
                $payment->rule_type = $ruleType;
                
                $payment->save();

                // Update underlying BankRecord or ZelleRecord
                if ($payment->bank_record_id) {
                    $bankRecord = \App\Models\BankRecord::find($payment->bank_record_id);
                    if ($bankRecord) {
                        $bankRecord->amount = $this->editPaymentAmount;
                        $bankRecord->reference = $this->editPaymentRef;
                        $bankRecord->payment_date = $this->editPaymentDate;
                        $bankRecord->save();
                    }
                } elseif ($payment->zelle_record_id) {
                    $zelleRecord = \App\Models\ZelleRecord::find($payment->zelle_record_id);
                    if ($zelleRecord) {
                        $zelleRecord->amount = $this->editPaymentAmount;
                        $zelleRecord->reference = $this->editPaymentRef;
                        $zelleRecord->zelle_date = $this->editPaymentDate;
                        $zelleRecord->save();
                    }
                }

                DB::commit();
                $this->dispatch('noty', msg: 'Pago pendiente actualizado exitosamente');
                $this->dispatch('hide-edit-payment-modal');
                
                // Refresh list
                $sale = Sale::find($payment->sale_id);
                $this->pays = $sale->payments; 
            } else {
                $this->dispatch('noty', msg: 'No se puede editar este pago (debe estar pendiente)');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('noty', msg: 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function cancelPay()
    {
        $this->resetUI();
    }

    public function historyPayments(Sale $sale)
    {
        $this->history_sale_id = $sale->id;
        $this->pays = $sale->payments;
        $this->dispatch('show-payhistory');
    }

    public function printReceipt($payment_id)
    {
        $this->printPayment($payment_id);
        $this->dispatch('noty', msg: 'IMPRIMIENDO RECIBO DE PAGO...');
    }

    public function printHistory()
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

}
