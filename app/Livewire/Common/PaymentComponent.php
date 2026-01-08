<?php

namespace App\Livewire\Common;

use App\Models\Bank;
use App\Models\Currency;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class PaymentComponent extends Component
{
    // Input properties from parent
    public $totalToPay = 0;
    public $currencyCode = 'COP'; // Currency of the debt
    public $customerName = '';
    
    // Internal properties
    public $payments = [];
    public $paymentMethod = 'cash'; // cash, bank
    public $banks = [];
    public $currencies = [];
    
    // Form inputs
    public $amount;
    public $paymentCurrency;
    public $bankId;
    public $accountNumber;
    public $depositNumber;
    public $phoneNumber; // For Nequi
    
    // Totals
    public $totalPaid = 0;
    public $remaining = 0;
    public $change = 0;
    
    // Change distribution
    public $changeDistribution = [];
    public $selectedChangeCurrency;
    public $allowPartialPayment = false;

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
        // Keep paymentCurrency and paymentMethod as is for better UX
    }

    public function updatedPaymentMethod()
    {
        $this->resetPaymentForm();
    }

    public function addPayment()
    {
        $this->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($this->paymentMethod == 'bank') {
            $this->validate([
                'bankId' => 'required',
                'accountNumber' => 'required',
                'depositNumber' => 'required',
            ]);
        }

        // Determine currency and exchange rate
        $currencyCode = $this->paymentCurrency;
        $bankName = null;

        if ($this->paymentMethod == 'bank') {
            $bank = $this->banks->find($this->bankId);
            $currencyCode = $bank ? $bank->currency_code : 'COP';
            $bankName = $bank ? $bank->name : '';
        }

        $currency = $this->currencies->firstWhere('code', $currencyCode);
        $exchangeRate = $currency ? $currency->exchange_rate : 1;
        $symbol = $currency ? $currency->symbol : '$';

        // Calculate amount in primary currency
        $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
        
        $amountInPrimary = 0;
        if ($currency->is_primary) {
            $amountInPrimary = $this->amount;
        } else {
            // Convert to USD (Base)
            $amountInUSD = $this->amount / $exchangeRate;
            // Convert to Primary
            $amountInPrimary = $amountInUSD * $primaryCurrency->exchange_rate;
        }

        $this->payments[] = [
            'method' => $this->paymentMethod,
            'amount' => $this->amount,
            'currency' => $currencyCode,
            'symbol' => $symbol,
            'exchange_rate' => $exchangeRate,
            'amount_in_primary' => $amountInPrimary,
            'bank_name' => $bankName,
            'account_number' => $this->accountNumber,
            'reference' => $this->depositNumber,
            'phone' => $this->phoneNumber
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

        $this->dispatch('payment-completed', 
            payments: $this->payments, 
            change: $this->change, 
            changeDistribution: $this->changeDistribution
        );
        
        $this->dispatch('close-payment-modal');
    }

    public function render()
    {
        return view('livewire.common.payment-component');
    }
}
