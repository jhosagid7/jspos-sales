<?php

namespace App\Livewire\Common;

use App\Models\Bank;
use App\Models\Currency;
use App\Models\ZelleRecord;
use App\Livewire\PartialPayment;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;

class PaymentComponent extends Component
{
    use WithFileUploads;

    // Input properties from parent
    public $totalToPay = 0;
    public $currencyCode = 'COP'; // Currency of the debt
    public $customerName = '';
    
    // Internal properties
    public $payments = [];
    public $paymentMethod = 'cash'; // cash, bank, zelle
    public $banks = [];
    public $currencies = [];
    
    // Form inputs
    public $amount;
    public $paymentCurrency;
    public $bankId;
    public $accountNumber;
    public $depositNumber;
    public $phoneNumber; // For Nequi
    
    // Zelle Inputs
    public $zelleSender;
    public $zelleDate;
    public $zelleAmount;
    public $zelleReference;
    public $zelleImage;
    
    // Zelle Validation Status
    public $zelleStatusMessage = '';
    public $zelleStatusType = ''; // 'info', 'warning', 'danger', 'success'
    public $zelleRemainingBalance = null;
    
    // Totals
    public $totalPaid = 0;
    public $remaining = 0;
    public $change = 0;
    
    // Change distribution
    public $changeDistribution = [];
    public $selectedChangeCurrency;
    public $selectedChangeAmount;
    public $allowPartialPayment = false;
    public $isZelleSelected = false;
    public $isVedBankSelected = false;
    
    // VED Bank Details
    public $bankReference;
    public $bankDate;
    public $bankNote;
    public $bankGlobalAmount; // Total Deposit Amount for Remaining Balance Logic

    // Bank Validation Status
    public $bankStatusMessage = '';
    public $bankStatusType = '';
    public $bankRemainingBalance = null;

    public $bankImage;

    // Credit Adjustment (Discount/Surcharge)
    public $adjustment = null;
    public $applyAdjustment = true; // Default to true
    public $allowDiscounts = false; // Controlled by PartialPayment

    // USD Payment Discount
    public $usdPaymentDiscountPercent = 0; 
    public $fixedUsdDiscountAmount = 0; // The fixed discount amount passed from PartialPayment
    
    public $usdAdjustment = null; // ['amount' => x, 'percentage' => y]
    public $applyUsdDiscount = false;
    
    // Permission Flags
    public $canUpload = false;
    public $canPay = false;

    // Custom Rate & History Logic (New)
    public $customExchangeRate;
    public $paymentDate; // Universal payment date field (mandatory for VED/Cash)

    // Manual Credit Note
    public $manualCreditAmount;
    public $manualCreditReason;


    protected $listeners = ['initPayment'];

    public function mount()
    {
        $this->banks = Bank::orderBy('sort')->get();
        $this->currencies = Currency::orderBy('is_primary', 'desc')->get();
        
        // Default payment currency to primary
        $primary = $this->currencies->firstWhere('is_primary', 1);
        $this->paymentCurrency = $primary ? $primary->code : 'COP';
        
        $this->resetPaymentForm();
    }

    public function initPayment($total, $currency = 'COP', $customer = '', $allowPartial = false, $adjustment = null, $allowDiscounts = false, $usdDiscountPercent = 0, $fixedUsdDiscountAmount = 0, $canUpload = false, $canPay = false)
    {
        Log::info('PaymentComponent::initPayment Received', [
            'total' => $total,
            'allowDiscounts' => $allowDiscounts,
            'percent' => $usdDiscountPercent,
            'fixedAmount' => $fixedUsdDiscountAmount,
            'canUpload' => $canUpload,
            'canPay' => $canPay
        ]);

        $this->totalToPay = floatval($total);
        $this->currencyCode = $currency;
        $this->paymentCurrency = $currency; // Set dropdown to match invoice currency
        $this->customerName = $customer;
        $this->allowPartialPayment = $allowPartial;
        $this->adjustment = $adjustment;
        $this->allowDiscounts = $allowDiscounts;
        $this->usdPaymentDiscountPercent = $usdDiscountPercent;
        $this->fixedUsdDiscountAmount = $fixedUsdDiscountAmount;
        
        $this->canUpload = $canUpload;
        $this->canPay = $canPay;
        
        // Default: If eligible for USD Discount, show it initially.
        if ($this->allowDiscounts && $this->fixedUsdDiscountAmount > 0) {
            $this->applyUsdDiscount = true;
            $this->applyAdjustment = false;
        } elseif ($this->adjustment) {
            $this->applyAdjustment = true;
            $this->applyUsdDiscount = false;
        }

        $this->payments = [];
        $this->changeDistribution = [];
        $this->calculateTotals();
        $this->resetPaymentForm();
        
        $this->dispatch('show-payment-modal');
    }

    public function toggleAdjustment()
    {
        if ($this->applyAdjustment) {
            $this->applyUsdDiscount = false;
        }
        $this->calculateTotals(); 
    }

    public function toggleUsdDiscount()
    {
        if ($this->applyUsdDiscount) {
            $this->applyAdjustment = false;
        }
        $this->calculateTotals();
    }
    
    public function resetPaymentForm()
    {
        $this->amount = null;
        $this->bankId = '';
        $this->accountNumber = '';
        $this->depositNumber = '';
        $this->phoneNumber = '';
        $this->isZelleSelected = false;
        $this->isVedBankSelected = false;
        
        // Reset Zelle
        $this->zelleSender = '';
        $this->zelleDate = date('Y-m-d');
        $this->zelleAmount = null;
        $this->zelleReference = '';
        $this->zelleImage = null;
        
        // Reset VED Bank
        $this->bankReference = '';
        $this->bankDate = date('Y-m-d');
        $this->bankNote = '';
        $this->bankImage = null;
        
        // Reset VED Bank
        $this->bankReference = '';
        $this->bankDate = date('Y-m-d');
        $this->bankNote = '';
        $this->bankImage = null;
        
        $this->customExchangeRate = null;
        $this->paymentDate = date('Y-m-d');
        
        $this->manualCreditAmount = null;
        $this->manualCreditReason = null;
        
        // Keep paymentCurrency and paymentMethod as is for better UX
    }

    public function updatedPaymentMethod()
    {
        $this->resetPaymentForm();
    }

    public function updatedZelleSender() { $this->checkZelleStatus(); }
    public function updatedZelleDate() { $this->checkZelleStatus(); }
    public function updatedZelleAmount() { $this->checkZelleStatus(); }
    
    public function updatedApplyUsdDiscount() 
    { 
        $this->calculateTotals(); 
    }



    // REACTIVE RATE LOOKUP
    public function updatedPaymentDate()
    {
        $this->lookupHistoricalRate();
    }
    
    // Also trigger lookup if bank/method changes, as currency might change to VED
    public function updatedPaymentCurrency() { $this->lookupHistoricalRate(); }

    public function lookupHistoricalRate() 
    {
        // Identify if current context is VED
        $isVED = false;
        
        // 1. Check Explicit Payment Currency (Cash)
        if ($this->paymentMethod == 'cash' && in_array($this->paymentCurrency, ['VED', 'VES'])) {
            $isVED = true;
        }
        
        // 2. Check Bank Currency
        if ($this->paymentMethod == 'bank' && $this->bankId) {
             $bank = $this->banks->find($this->bankId);
             if ($bank && in_array($bank->currency_code, ['VED', 'VES'])) {
                 $isVED = true;
             }
        }
        
        if ($isVED) {
             $dateToSearch = $this->paymentDate ?: ($this->bankDate ?: date('Y-m-d'));
             
             // If date is empty, default to today
             if(empty($dateToSearch)) $dateToSearch = date('Y-m-d');
             
             // Look up history: Last rate recorded <= end of that day
             $history = \App\Models\ExchangeRateHistory::where('rate_type', 'BCV') // Assume BCV for now as standard official
                 ->where('created_at', '<=', \Carbon\Carbon::parse($dateToSearch)->endOfDay())
                 ->orderBy('created_at', 'desc')
                 ->first();
                 
             if ($history) {
                 $this->customExchangeRate = $history->rate;
                 // Optional: Flash message? No, too noisy.
             } else {
                 // Fallback to current config rate if no history found?
                 // Or keep empty? 
                 // Requirement: "PRECARGADA LA TASA BCV QUE CORRESPONDA... SI EL HISTORIAL TIENE REGISTRADO"
                 // If not found in history for that date, maybe load current Config BCV as fallback?
                 // Or fallback to current rate.
                 $config = \App\Models\Configuration::first();
                 if ($config && $config->bcv_rate) {
                      $this->customExchangeRate = $config->bcv_rate;
                 }
             }
        } else {
             $this->customExchangeRate = null; // Reset if not VED
        }
    }
    
    public function checkZelleStatus()
    {
        if ($this->zelleSender && $this->zelleDate && $this->zelleAmount) {
            
            // 1. Check for Session Duplicates (Already in list)
            $isDuplicateInSession = collect($this->payments)->contains(function ($payment) {
                return $payment['method'] === 'zelle' &&
                       $payment['zelle_sender'] === $this->zelleSender &&
                       $payment['zelle_date'] === $this->zelleDate &&
                       floatval($payment['zelle_amount']) === floatval($this->zelleAmount);
            });

            if ($isDuplicateInSession) {
                $this->zelleStatusMessage = "Este Zelle ya está en la lista de pagos. Si desea cambiar el monto, elimine el anterior y agréguelo nuevamente.";
                $this->zelleStatusType = 'warning'; // Orange: Session Duplicate
                $this->zelleRemainingBalance = null;
                return;
            }

            // 2. Check Database
            $zelleRecord = ZelleRecord::where('sender_name', $this->zelleSender)
                ->where('zelle_date', $this->zelleDate)
                ->where('amount', $this->zelleAmount)
                ->first();

            if ($zelleRecord) {
                if ($zelleRecord->remaining_balance <= 0.01) {
                    $this->zelleStatusMessage = "Este Zelle ya fue utilizado completamente.";
                    $this->zelleStatusType = 'danger'; // Red: DB Exhausted
                    $this->zelleRemainingBalance = 0;
                } else {
                    $this->zelleStatusMessage = "Zelle encontrado. Saldo restante: $" . number_format($zelleRecord->remaining_balance, 2);
                    $this->zelleStatusType = 'success'; // Green: Available Balance
                    $this->zelleRemainingBalance = $zelleRecord->remaining_balance;
                }
            } else {
                $this->zelleStatusMessage = "Nuevo Zelle (No registrado en BD).";
                $this->zelleStatusType = 'success'; // Green: New
                $this->zelleRemainingBalance = $this->zelleAmount;
            }
        } else {
            $this->zelleStatusMessage = '';
            $this->zelleStatusType = '';
            $this->zelleRemainingBalance = null;
        }
    }

    public function updatedBankId($value) 
    { 
        $this->isZelleSelected = false;
        $this->isVedBankSelected = false;
        
        if($value) {
            $bank = $this->banks->find($value);
            if ($bank) {
                if (stripos($bank->name, 'zelle') !== false) {
                    $this->isZelleSelected = true;
                }
                if ($bank->currency_code === 'VED' || $bank->currency_code === 'VES') {
                    $this->isVedBankSelected = true;
                }
            }
        }
        $this->checkBankStatus(); 
        $this->lookupHistoricalRate(); 
    }
    public function updatedBankReference() { $this->checkBankStatus(); }
    public function updatedBankGlobalAmount() { $this->checkBankStatus(); }

    public function checkBankStatus()
    {
        $ref = $this->isVedBankSelected ? $this->bankReference : $this->depositNumber;
        $amount = $this->bankGlobalAmount;
        $bankId = $this->bankId;

        if ($bankId && $ref && $amount) {
            
            // Check Database for BankRecord with SAME Total Amount
            $bankRecord = \App\Models\BankRecord::where('bank_id', $bankId)
                ->where('reference', $ref)
                ->where('amount', $amount)
                ->first();

            // Bypass check: If the reference is exactly the user's taxpayer_id (Cédula), treat it as a new deposit immediately.
            $bypassedRef = auth()->user() && auth()->user()->taxpayer_id && trim($ref) === trim(auth()->user()->taxpayer_id);

            if ($bankRecord && !$bypassedRef) {
                if ($bankRecord->remaining_balance <= 0.01) {
                    $this->bankStatusMessage = "Este depósito ya fue utilizado completamente.";
                    $this->bankStatusType = 'danger';
                    $this->bankRemainingBalance = 0;
                } else {
                    $this->bankStatusMessage = "Depósito encontrado. Saldo restante: $" . number_format($bankRecord->remaining_balance, 2);
                    $this->bankStatusType = 'success';
                    $this->bankRemainingBalance = $bankRecord->remaining_balance;
                }
            } else {
                $msg = $bypassedRef ? "Referencia comodín (Cédula) aceptada." : "Nuevo Depósito (Se creará registro).";
                $this->bankStatusMessage = $msg;
                $this->bankStatusType = 'success'; // Green: New or Bypassed
                $this->bankRemainingBalance = $amount;
            }
        } else {
            $this->bankStatusMessage = '';
            $this->bankStatusType = '';
            $this->bankRemainingBalance = null;
        }
    }

    public function addPayment()
    {
        if ($this->paymentMethod == 'bank' && !in_array('module_advanced_payments', config('tenant.modules', []))) {
            $this->dispatch('noty', msg: 'ACCESO DENEGADO: Módulo de pagos avanzados no activo.');
            return;
        }

        $this->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);
        
        // Validate Cash VED Date
        if ($this->paymentMethod == 'cash' && in_array($this->paymentCurrency, ['VED', 'VES'])) {
             $this->validate([
                 'paymentDate' => 'required|date'
             ]);
        }


        if ($this->paymentMethod == 'bank') {
            if ($this->isZelleSelected) {
                 $this->validate([
                    'zelleSender' => 'required',
                    'zelleDate' => 'required|date',
                    'zelleAmount' => 'required|numeric|min:0.01',
                    'zelleImage' => 'required|image|max:2048', 
                ]);
                 $this->checkZelleStatus();
                 if ($this->zelleStatusType === 'danger' || $this->zelleStatusType === 'warning') {
                     $this->dispatch('noty', msg: $this->zelleStatusMessage);
                     return;
                }
                if ($this->zelleRemainingBalance !== null && $this->amount > $this->zelleRemainingBalance) {
                    $this->dispatch('noty', msg: "El monto a usar ($" . number_format($this->amount, 2) . ") excede el saldo restante del Zelle ($" . number_format($this->zelleRemainingBalance, 2) . ")");
                    return;
                }
            } elseif ($this->isVedBankSelected) {
                $this->validate([
                    'bankId' => 'required',
                    'bankReference' => 'required',
                    'bankDate' => 'required|date',
                    'bankGlobalAmount' => 'required|numeric|min:0.01', // Total Deposit
                    'amount' => 'required|numeric|min:0.01', // Amount to Use
                    'bankImage' => 'required|image|max:2048', 
                ]);
                
                $this->checkBankStatus();
                
                 if ($this->bankStatusType === 'danger') {
                     $this->dispatch('noty', msg: $this->bankStatusMessage);
                     return;
                }
                if ($this->bankRemainingBalance !== null && $this->amount > $this->bankRemainingBalance) {
                    $this->dispatch('noty', msg: "El monto a usar ($" . number_format($this->amount, 2) . ") excede el saldo restante ($" . number_format($this->bankRemainingBalance, 2) . ")");
                    return;
                }

                 $duplicateInSession = collect($this->payments)->contains(function ($payment) {
                    return $payment['method'] === 'bank' && ($payment['bank_reference'] ?? '') === $this->bankReference;
                 });
                 // Bypass session duplicate check if it's their Cedula
                 $bypassedRef = auth()->user() && auth()->user()->taxpayer_id && trim($this->bankReference) === trim(auth()->user()->taxpayer_id);
                 
                 if ($duplicateInSession && !$bypassedRef) { $this->dispatch('noty', msg: 'Esta referencia ya está agregada en esta lista.'); return; }
            } else {
                 $this->validate(['bankId' => 'required', 'accountNumber' => 'required', 'depositNumber' => 'required']);
            }
        }

        // Determine currency and exchange rate
        $currencyCode = $this->paymentCurrency;
        $bankName = null;

        if ($this->paymentMethod == 'bank') {
            $bank = $this->banks->find($this->bankId);
            $currencyCode = $bank ? $bank->currency_code : 'COP';
            $bankName = $bank ? $bank->name : '';
            if ($this->isZelleSelected) $currencyCode = 'USD';
        }

        $currency = $this->currencies->firstWhere('code', $currencyCode);
        $exchangeRate = $currency ? $currency->exchange_rate : 1;
        $symbol = $currency ? $currency->symbol : '$';

        $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
        
        $amountInPrimary = 0;
        if ($currency && $currency->is_primary) {
            $amountInPrimary = $this->amount;
        } else {
            // Apply Custom Rate if set
            $finalRate = $exchangeRate;
            if ($this->customExchangeRate > 0) {
                 // Logic check: Only apply if VED? 
                 // Yes, addPayment logic above already verified context.
                 // But wait, $exchangeRate var here is used for calculation.
                 // We need to update it BEFORE calculation.
                 if (($this->paymentMethod == 'cash' && in_array($currencyCode, ['VED', 'VES'])) ||
                    ($this->paymentMethod == 'bank' && $this->isVedBankSelected)) {
                        $finalRate = $this->customExchangeRate;
                 }
            }
            
            $amountInUSD = $this->amount / ($finalRate ?: 1);
            $amountInPrimary = $amountInUSD * $primaryCurrency->exchange_rate;
        }

        // Handle Images
        $imagePath = ($this->isZelleSelected && $this->zelleImage) ? $this->zelleImage->store('zelle_receipts', 'public') : null;
        $bankImagePath = ($this->paymentMethod == 'bank' && $this->isVedBankSelected && $this->bankImage) ? $this->bankImage->store('bank_receipts', 'public') : null;

        // Check Custom Rate Logic
        if (($this->paymentMethod == 'cash' && in_array($currencyCode, ['VED', 'VES'])) ||
            ($this->paymentMethod == 'bank' && $this->isVedBankSelected)) {
            
             // Override exchange rate if custom rate provided
             if ($this->customExchangeRate > 0) {
                 $exchangeRate = $this->customExchangeRate;
             }
        }

        $newPayment = [
            'method' => $this->isZelleSelected ? 'zelle' : $this->paymentMethod,
            'amount' => $this->amount, // Amount Used
            'currency' => $currencyCode,
            'symbol' => $symbol,
            'exchange_rate' => $exchangeRate,
            'amount_in_primary' => $amountInPrimary,
            'bank_id' => $this->bankId,
            'bank_name' => $bankName,
            'account_number' => $this->isVedBankSelected ? null : $this->accountNumber,
            'reference' => $this->isZelleSelected ? $this->zelleReference : ($this->isVedBankSelected ? $this->bankReference : $this->depositNumber),
            'phone' => $this->phoneNumber,
            'zelle_sender' => $this->zelleSender,
            'zelle_date' => $this->zelleDate,
            'zelle_amount' => $this->zelleAmount,
            'zelle_image' => $imagePath,
            'zelle_file_url' => $imagePath ? asset('storage/' . $imagePath) : null,
            'bank_reference' => $this->bankReference,
            'bank_date' => $this->bankDate,
            'bank_note' => $this->bankNote,
            'bank_global_amount' => $this->bankGlobalAmount, // NEW: Pass global amount
            'bank_image' => $bankImagePath,
            'bank_file_url' => $bankImagePath ? asset('storage/' . $bankImagePath) : null,
            // Add Payment Date for History/Cash
            'payment_date' => $this->paymentMethod == 'cash' ? ($this->paymentDate ?: now()) : ($this->bankDate ?: $this->zelleDate)
        ];

        
        $this->payments[] = $newPayment;

        // Auto-Enable Logic on Add Payment
        if (in_array($currencyCode, ['VED', 'VES'])) {
             $this->applyUsdDiscount = false;
             $this->applyAdjustment = true;
        }

        $this->calculateTotals();
        $this->resetPaymentForm();
    }

    public function addCreditNote()
    {
        if (!auth()->user()->can('payments.create_credit_note')) {
            $this->dispatch('noty', msg: 'No tienes permiso para crear notas de crédito manuales.');
            return;
        }

        $this->validate([
            'manualCreditAmount' => 'required|numeric|min:0.01',
            'manualCreditReason' => 'required|string|min:3'
        ]);

        $currency = $this->currencies->firstWhere('code', $this->paymentCurrency);
        $exchangeRate = $currency ? $currency->exchange_rate : 1;
        $symbol = $currency ? $currency->symbol : '$';

        $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
        $amountInUSD = $this->manualCreditAmount / ($exchangeRate ?: 1);
        
        if ($currency && $currency->is_primary) {
            $amountInPrimary = $this->manualCreditAmount;
        } else {
            $amountInPrimary = $amountInUSD * $primaryCurrency->exchange_rate;
        }

        // Amount in the currency of the debt (invoice)
        $debtCurrency = $this->currencies->firstWhere('code', $this->currencyCode);
        $debtRate = $debtCurrency ? $debtCurrency->exchange_rate : 1;
        $amountInInvoiceCurrency = $amountInUSD * $debtRate;

        $this->payments[] = [
            'method' => 'credit_note',
            'amount' => $this->manualCreditAmount,
            'amount_in_invoice_currency' => $amountInInvoiceCurrency,
            'currency' => $this->paymentCurrency,
            'symbol' => $symbol,
            'exchange_rate' => $exchangeRate,
            'amount_in_primary' => $amountInPrimary,
            'note' => $this->manualCreditReason,
            'payment_date' => now()
        ];

        $this->manualCreditAmount = null;
        $this->manualCreditReason = null;
        
        $this->calculateTotals();
    }

    public function removePayment($index)
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
        $this->calculateTotals();
    }
    
    public function addChangeDistribution()
    {
        if ($this->selectedChangeAmount > 0 && $this->selectedChangeCurrency) {
            $currency = $this->currencies->firstWhere('code', $this->selectedChangeCurrency);
            $symbol = $currency ? $currency->symbol : '$';
             $this->changeDistribution[] = [
                'currency' => $this->selectedChangeCurrency,
                'amount' => $this->selectedChangeAmount,
                'symbol' => $symbol
            ];
            $this->selectedChangeAmount = null;
            $this->selectedChangeCurrency = null;
        }
    }

    public function removeChangeDistribution($index)
    {
        unset($this->changeDistribution[$index]);
        $this->changeDistribution = array_values($this->changeDistribution);
    }

    public function calculateTotals()
    {
        $this->totalPaid = array_sum(array_column($this->payments, 'amount_in_primary'));
        
        // Analyze Payment Currencies
        $hasPayments = !empty($this->payments);
        $hasVed = false;
        $onlyDivisa = false;

        if ($hasPayments) {
            $hasVed = collect($this->payments)->contains(function ($p) {
                return in_array($p['currency'], ['VED', 'VES']);
            });
            
            // Divisa = USD, Zelle, COP
            $onlyDivisa = collect($this->payments)->every(function ($p) {
                return in_array($p['currency'], ['USD', 'COP']); // Zelle is method, but currency is USD
            });
        }

        // Auto-Switch Logic
        if ($hasPayments) {
            if ($onlyDivisa) {
                // Scenario: Pure Divisa -> Prefer USD Discount
                // Do NOT force applyUsdDiscount = true. Let user toggle it.
                // Just ensure mutual exclusivity if USD discount IS active.
                if ($this->applyUsdDiscount) {
                     $this->applyAdjustment = false; 
                } else {
                     $this->applyAdjustment = false;
                }
            } elseif ($hasVed) {
                // Scenario: VED Involved -> Strict Early Payment Only
                // FORCE USD Discount OFF
                $this->applyUsdDiscount = false;
                // Scenario: VED Involved -> Strict Early Payment Only
                // FORCE USD Discount OFF
                $this->applyUsdDiscount = false;
                // $this->applyAdjustment = true; // REMOVED: Allow user to toggle it off manually if they want.
            }
        } else {
            // No payments -> Respect user toggle, but ensure exclusivity
            if ($this->allowDiscounts && $this->fixedUsdDiscountAmount > 0) {
                 if ($this->applyUsdDiscount) {
                     $this->applyAdjustment = false;
                 }
                 // If applyAdjustment is true, handle exclusivity
                 if ($this->applyAdjustment) {
                     $this->applyUsdDiscount = false;
                 }
            }
        }

        // Calculate Target to Pay based on Discount Toggle
        $targetToPay = $this->totalToPay;
        
        // 1. Adjustment (Early Payment)
        // Only apply if enabled AND (Implicitly) not overridden by USD Discount?
        // Actually flags are mutually exclusive now by logic above.
        if ($this->adjustment && $this->applyAdjustment) {
            $targetToPay -= $this->adjustment['amount'];
        }
        
        $this->usdAdjustment = null;
        
        if ($this->allowDiscounts && $this->fixedUsdDiscountAmount > 0 && !$hasVed) {
             
             $this->usdAdjustment = [
                 'amount' => $this->fixedUsdDiscountAmount,
                 'percentage' => $this->usdPaymentDiscountPercent,
                 'reason' => 'Descuento Pago Divisa'
             ];
             
             // Check if paying enough to settle
             $effectiveTarget = $targetToPay - $this->fixedUsdDiscountAmount;
             $canApply = $this->totalPaid >= ($effectiveTarget - 0.01); 
             
             if ($this->applyUsdDiscount) {
                 if ($canApply) {
                     $targetToPay -= $this->fixedUsdDiscountAmount;
                 } else {
                     $targetToPay -= $this->fixedUsdDiscountAmount;
                 }
             }
        } else {
             // If NOT eligible (e.g. has VE payments), then we disable and nullify
             $this->applyUsdDiscount = false; 
             $this->usdAdjustment = null;
        }

        $this->remaining = max(0, $targetToPay - $this->totalPaid);
        $this->change = max(0, $this->totalPaid - $targetToPay);
    }
    
    // ... addChangeDistribution ...

    public function submit($action = 'pay')
    {
        if ($this->totalPaid <= 0) {
            $this->dispatch('noty', msg: 'Debe ingresar un monto mayor a cero');
            return;
        }

        // Only block if partial payments are NOT allowed and amount is not covered
        if (!$this->allowPartialPayment && $this->remaining > 0.01) { 
            $this->dispatch('noty', msg: 'El monto pagado no cubre el total');
            return;
        }

        // Enforce Full Payment for Discount
        // Check Early Payment Discount
        if ($this->adjustment && $this->applyAdjustment) {
             if ($this->remaining > 0.01) {
                 $this->dispatch('noty', msg: 'El descuento solo aplica si paga la totalidad de la deuda.');
                 return;
             }
        }
        
        // Check USD Discount
        if ($this->usdAdjustment && $this->applyUsdDiscount) {
             if ($this->remaining > 0.01) {
                 $this->dispatch('noty', msg: 'El descuento por divisa solo aplica si liquida la deuda totalmente.');
                 return;
             }
        }
        
        // Inject Discount into the LAST payment
        $lastIndex = count($this->payments) - 1;
        
        if ($lastIndex >= 0) {
             // ... logic same as before ...
             // 1. Early Payment
             
             if ($this->adjustment && $this->applyAdjustment) {
                 $this->payments[$lastIndex]['discount_amount'] = $this->adjustment['amount']; 
                 $this->payments[$lastIndex]['discount_percentage'] = $this->adjustment['percentage'];
                 $this->payments[$lastIndex]['discount_reason'] = $this->adjustment['reason'] ?? 'Pronto Pago';
                 $this->payments[$lastIndex]['days_elapsed'] = $this->adjustment['days'] ?? 0;
                 $this->payments[$lastIndex]['rule_type'] = $this->adjustment['rule_type'] ?? 'early_payment';
             }
             
             // 2. USD Discount
             if ($this->usdAdjustment && $this->applyUsdDiscount) {
                 // Check if existing
                 $existingAmount = $this->payments[$lastIndex]['discount_amount'] ?? 0;
                 $existingPercent = $this->payments[$lastIndex]['discount_percentage'] ?? 0;
                 $existingReason = $this->payments[$lastIndex]['discount_reason'] ?? '';
                 
                 $newAmount = $this->usdAdjustment['amount'];
                 
                 $this->payments[$lastIndex]['discount_amount'] = $existingAmount + $newAmount;
                 $this->payments[$lastIndex]['discount_percentage'] = $existingPercent + $this->usdAdjustment['percentage'];
                 
                 $reason = $this->usdAdjustment['reason'];
                 if ($existingReason) {
                     $reason = $existingReason . " + " . $reason;
                 }
                 $this->payments[$lastIndex]['discount_reason'] = $reason;
                 
                 if (isset($this->payments[$lastIndex]['rule_type'])) {
                     $this->payments[$lastIndex]['rule_type'] = 'combined'; 
                 } else {
                     $this->payments[$lastIndex]['rule_type'] = 'usd_payment';
                 }
             }
        }

        Log::info("PaymentComponent: About to dispatch payment event", [
            'action' => $action,
            'payments_count' => count($this->payments),
            'has_zelle' => collect($this->payments)->contains('method', 'zelle'),
            'payments_data' => $this->payments
        ]);

        if ($action === 'upload') {
            $this->dispatch('payment-uploaded', 
                payments: $this->payments, 
                change: $this->change, 
                changeDistribution: $this->changeDistribution
            );
        } else {
            $this->dispatch('payment-completed', 
                payments: $this->payments, 
                change: $this->change, 
                changeDistribution: $this->changeDistribution
            );
        }
        
        Log::info("PaymentComponent: Event dispatched for action: $action");
        
        $this->dispatch('close-payment-modal');
    }

    public function render()
    {
        return view('livewire.common.payment-component');
    }
}
