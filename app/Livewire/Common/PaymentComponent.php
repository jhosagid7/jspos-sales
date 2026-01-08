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

    public function initPayment($total, $currency = 'COP', $customer = '', $allowPartial = false)
    {
        $this->totalToPay = floatval($total);
        $this->currencyCode = $currency;
        $this->customerName = $customer;
        $this->allowPartialPayment = $allowPartial;
        
        $this->payments = [];
        $this->changeDistribution = [];
        $this->calculateTotals();
        $this->resetPaymentForm();
        
        $this->dispatch('show-payment-modal');
    }

    public function resetPaymentForm()
    {
        $this->amount = null;
        $this->bankId = '';
        $this->accountNumber = '';
        $this->depositNumber = '';
        $this->phoneNumber = '';
        $this->isZelleSelected = false;
        
        // Reset Zelle
        $this->zelleSender = '';
        $this->zelleDate = date('Y-m-d');
        $this->zelleAmount = null;
        $this->zelleReference = '';
        $this->zelleImage = null;
        
        // Keep paymentCurrency and paymentMethod as is for better UX
    }

    public function updatedPaymentMethod()
    {
        $this->resetPaymentForm();
    }

    public function updatedBankId($value)
    {
        $this->isZelleSelected = false;
        if($value) {
            $bank = $this->banks->find($value);
            if($bank && stripos($bank->name, 'zelle') !== false) {
                $this->isZelleSelected = true;
            }
        }
    }

    public function updatedZelleSender() { $this->checkZelleStatus(); }
    public function updatedZelleDate() { $this->checkZelleStatus(); }
    public function updatedZelleAmount() { $this->checkZelleStatus(); }

    public function checkZelleStatus()
    {
        $this->zelleStatusMessage = '';
        $this->zelleStatusType = '';
        $this->zelleRemainingBalance = null;

        Log::info("Checking Zelle Status: Sender={$this->zelleSender}, Date={$this->zelleDate}, Amount={$this->zelleAmount}");

        if (!empty($this->zelleSender) && !empty($this->zelleDate) && !empty($this->zelleAmount)) {
            $dbMessage = '';
            $dbType = '';
            $localUsed = 0;
            $baseBalance = (float)$this->zelleAmount; // Default to full amount if new

            // 1. Check Database
            $record = ZelleRecord::where('sender_name', 'like', trim($this->zelleSender))
                ->where('zelle_date', $this->zelleDate)
                ->where('amount', $this->zelleAmount)
                ->first();

            if ($record) {
                Log::info("Zelle Record Found: ID={$record->id}, Balance={$record->remaining_balance}");
                $baseBalance = (float)$record->remaining_balance;
                
                if ($baseBalance <= 0) {
                    $dbMessage = "Este Zelle ya fue utilizado completamente en BD.";
                    $dbType = 'danger';
                } else {
                    $dbMessage = "Registrado en BD.";
                    $dbType = 'info';
                }
            } else {
                Log::info("No Zelle Record Found for: " . trim($this->zelleSender));
                $dbMessage = "Nuevo (No en BD).";
                $dbType = 'success';
            }

            // 2. Check Local Payments (In Memory)
            foreach ($this->payments as $payment) {
                if (isset($payment['zelle_sender']) && 
                    strcasecmp($payment['zelle_sender'], $this->zelleSender) === 0 &&
                    $payment['zelle_date'] == $this->zelleDate &&
                    $payment['zelle_amount'] == $this->zelleAmount) {
                    
                    $localUsed += $payment['amount']; // Sum amount used in this session
                }
            }

            // 3. Calculate Actual Remaining
            $this->zelleRemainingBalance = $baseBalance - $localUsed;

            if ($this->zelleRemainingBalance < 0) {
                 $this->zelleStatusMessage = "Error: El uso local excede el monto del Zelle.";
                 $this->zelleStatusType = 'danger';
            } elseif ($localUsed > 0) {
                 $this->zelleStatusMessage = "Zelle reutilizado (Lista actual). Usado: $" . number_format($localUsed, 2) . ". Restante: $" . number_format($this->zelleRemainingBalance, 2) . ". ($dbMessage)";
                 $this->zelleStatusType = $this->zelleRemainingBalance > 0 ? 'warning' : 'danger';
            } else {
                 $this->zelleStatusMessage = $dbMessage . " Disponible: $" . number_format($this->zelleRemainingBalance, 2);
                 $this->zelleStatusType = $dbType;
            }
        }
    }

    public function addPayment()
    {
        $this->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($this->paymentMethod == 'bank') {
            if ($this->isZelleSelected) {
                $this->validate([
                    'zelleSender' => 'required',
                    'zelleDate' => 'required|date',
                    'zelleAmount' => 'required|numeric|min:0.01',
                ]);
                
                // Check for duplicate Zelle
                $this->checkZelleStatus();
                
                // Block only if danger (Overused or Invalid)
                if ($this->zelleStatusType === 'danger') {
                     $this->dispatch('noty', msg: $this->zelleStatusMessage);
                     return;
                }
                
                // Validate Amount vs Remaining Balance
                // Note: zelleRemainingBalance now accounts for local usage
                if ($this->zelleRemainingBalance !== null && $this->amount > $this->zelleRemainingBalance) {
                    $this->dispatch('noty', msg: "El monto a usar ($" . number_format($this->amount, 2) . ") excede el saldo restante del Zelle ($" . number_format($this->zelleRemainingBalance, 2) . ")");
                    return;
                }
            } else {
                $this->validate([
                    'bankId' => 'required',
                    'accountNumber' => 'required',
                    'depositNumber' => 'required',
                ]);
            }
        }

        // Determine currency and exchange rate
        $currencyCode = $this->paymentCurrency;
        $bankName = null;

        if ($this->paymentMethod == 'bank') {
            $bank = $this->banks->find($this->bankId);
            $currencyCode = $bank ? $bank->currency_code : 'COP';
            $bankName = $bank ? $bank->name : '';
            
            if ($this->isZelleSelected) {
                $currencyCode = 'USD'; // Zelle is always USD
            }
        }

        $currency = $this->currencies->firstWhere('code', $currencyCode);
        $exchangeRate = $currency ? $currency->exchange_rate : 1;
        $symbol = $currency ? $currency->symbol : '$';

        // Calculate amount in primary currency
        $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
        
        $amountInPrimary = 0;
        if ($currency && $currency->is_primary) {
            $amountInPrimary = $this->amount;
        } else {
            // Convert to USD (Base)
            $amountInUSD = $this->amount / ($exchangeRate ?: 1);
            // Convert to Primary
            $amountInPrimary = $amountInUSD * $primaryCurrency->exchange_rate;
        }

        // Handle Zelle Image Upload
        $imagePath = null;
        if ($this->zelleImage) {
            $imagePath = $this->zelleImage->store('zelle_receipts', 'public');
        }

        $this->payments[] = [
            'method' => $this->isZelleSelected ? 'zelle' : $this->paymentMethod,
            'amount' => $this->amount,
            'currency' => $currencyCode,
            'symbol' => $symbol,
            'exchange_rate' => $exchangeRate,
            'amount_in_primary' => $amountInPrimary,
            'bank_name' => $bankName,
            'account_number' => $this->accountNumber,
            'reference' => $this->isZelleSelected ? $this->zelleReference : $this->depositNumber,
            'phone' => $this->phoneNumber,
            // Zelle specific
            'zelle_sender' => $this->zelleSender,
            'zelle_date' => $this->zelleDate,
            'zelle_amount' => $this->zelleAmount,
            'zelle_image' => $imagePath,
            'zelle_file_url' => $imagePath ? asset('storage/' . $imagePath) : null
        ];

        $this->calculateTotals();
        $this->resetPaymentForm();
    }

    public function removePayment($index)
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->totalPaid = array_sum(array_column($this->payments, 'amount_in_primary'));
        
        $this->remaining = max(0, $this->totalToPay - $this->totalPaid);
        $this->change = max(0, $this->totalPaid - $this->totalToPay);
    }

    public function addChangeDistribution()
    {
        if (!$this->selectedChangeAmount || !$this->selectedChangeCurrency) return;

        $currency = $this->currencies->firstWhere('code', $this->selectedChangeCurrency);
        $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);

        // Calculate amount in primary
        $amountInPrimary = 0;
        if ($currency->is_primary) {
            $amountInPrimary = $this->selectedChangeAmount;
        } else {
            $amountInUSD = $this->selectedChangeAmount / $currency->exchange_rate;
            $amountInPrimary = $amountInUSD * $primaryCurrency->exchange_rate;
        }

        $this->changeDistribution[] = [
            'currency' => $this->selectedChangeCurrency,
            'amount' => $this->selectedChangeAmount,
            'symbol' => $currency->symbol,
            'amount_in_primary' => $amountInPrimary
        ];

        $this->selectedChangeAmount = null;
    }

    public function removeChangeDistribution($index)
    {
        unset($this->changeDistribution[$index]);
        $this->changeDistribution = array_values($this->changeDistribution);
    }

    public function submit()
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

        Log::info("PaymentComponent: About to dispatch payment-completed", [
            'payments_count' => count($this->payments),
            'has_zelle' => collect($this->payments)->contains('method', 'zelle'),
            'payments_data' => $this->payments
        ]);

        // Use browser event for reliable cross-component communication
        $this->dispatch('payment-completed', 
            payments: $this->payments, 
            change: $this->change, 
            changeDistribution: $this->changeDistribution
        );
        
        Log::info("PaymentComponent: payment-completed event dispatched");
        
        $this->dispatch('close-payment-modal');
    }

    public function render()
    {
        return view('livewire.common.payment-component');
    }
}
