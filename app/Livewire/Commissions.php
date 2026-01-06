<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Commissions extends Component
{
    use WithPagination;

    public $dateFrom, $dateTo, $seller_id, $status_filter, $batch_filter;
    public $pagination = 10;

    public $selectedSaleId;
    public $paymentMethod = 'Cash'; // Cash, Bank
    public $referenceNumber;
    public $selectedBankId;
    public $selectedCurrencyCode;
    public $selectedCurrencySymbol;
    public $paymentRate = 1;
    public $paymentAmount = 0;
    public $paymentNotes;
    
    public $banks = [];
    public $currencies = [];

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->status_filter = 'all';
        $this->seller_id = 0;
        
        $this->banks = \App\Models\Bank::orderBy('name')->get();
        $this->currencies = \App\Models\Currency::orderBy('name')->get();
        
        $primary = \App\Models\Currency::where('is_primary', true)->first();
        
        if ($primary) {
            $this->selectedCurrencyCode = $primary->code;
            $this->selectedCurrencySymbol = $primary->symbol;
        } else {
            $this->selectedCurrencyCode = 'USD';
            $this->selectedCurrencySymbol = '$';
        }
    }

    public function render()
    {
        $user = Auth::user();
        $canManage = $user->can('gestionar_comisiones');
        
        $query = Sale::query()
            ->with(['customer', 'user', 'payments'])
            ->where('is_foreign_sale', true)
            ->where(function($q) {
                $q->where('final_commission_amount', '>', 0)
                  ->orWhere('commission_status', 'pending_calculation')
                  ->orWhereNull('final_commission_amount');
            });

        // Filter by Date Range
        $query->whereBetween('created_at', [$this->dateFrom . ' 00:00:00', $this->dateTo . ' 23:59:59']);

        // Filter by Seller
        if ($canManage) {
            if ($this->seller_id != 0) {
                $query->whereHas('customer', function($q) {
                    $q->where('seller_id', $this->seller_id);
                });
            }
        } else {
            $query->whereHas('customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
            });
        }

        // Filter by Status
        if ($this->status_filter !== 'all') {
            if ($this->status_filter === 'pending') {
                $query->where('commission_status', '!=', 'paid');
            } else {
                $query->where('commission_status', $this->status_filter);
            }
        }

        // Filter by Batch
        if (!empty($this->batch_filter)) {
            $query->where('batch_name', 'like', "%{$this->batch_filter}%");
        }

        $commissions = $query->orderBy('created_at', 'desc')->paginate($this->pagination);
        
        $sellers = User::role('Vendedor')->get();

        return view('livewire.commissions.component', [
            'commissions' => $commissions,
            'sellers' => $sellers,
            'canManage' => $canManage
        ]);
    }

    public function initPayment($saleId)
    {
        if (!Auth::user()->can('gestionar_comisiones')) {
            $this->dispatch('noty', msg: 'NO TIENES PERMISOS PARA ESTA ACCIÓN');
            return;
        }
        
        $this->selectedSaleId = $saleId;
        $this->paymentMethod = 'Cash';
        $this->referenceNumber = '';
        $this->selectedBankId = '';
        $this->paymentNotes = '';
        
        // Reset to primary currency
        $primary = \App\Models\Currency::where('is_primary', true)->first();
        $this->selectedCurrencyCode = $primary ? $primary->code : 'USD';
        
        $this->calculatePaymentValues();

        $this->dispatch('show-modal', msg: 'Abriendo modal de pago');
    }

    public function calculatePaymentValues()
    {
        $sale = Sale::find($this->selectedSaleId);
        $baseAmount = $sale ? $sale->final_commission_amount : 0;
        
        $currency = \App\Models\Currency::where('code', $this->selectedCurrencyCode)->first();
        
        if ($currency) {
            $this->paymentRate = $currency->exchange_rate;
            $this->selectedCurrencySymbol = $currency->symbol;
            $this->paymentAmount = round($baseAmount * $this->paymentRate, 2);
        } else {
            $this->paymentRate = 1;
            $this->selectedCurrencySymbol = '$';
            $this->paymentAmount = $baseAmount;
        }
    }

    public function updatedSelectedBankId($value)
    {
        if($value) {
            $bank = \App\Models\Bank::find($value);
            if($bank) {
                $this->selectedCurrencyCode = $bank->currency_code;
                $this->calculatePaymentValues();
            }
        }
    }

    public function updatedSelectedCurrencyCode($value)
    {
        $this->calculatePaymentValues();
    }

    public function updatedPaymentMethod($value)
    {
        if($value == 'Cash') {
            $primaryCurrency = \App\Models\Currency::where('is_primary', true)->first();
            $this->selectedCurrencyCode = $primaryCurrency->code;
            $this->selectedBankId = null;
            $this->referenceNumber = null;
            
            $this->calculatePaymentValues();
        }
    }

    public function updatedPaymentRate($value)
    {
        $sale = Sale::find($this->selectedSaleId);
        $baseAmount = $sale ? $sale->final_commission_amount : 0;
        
        if(is_numeric($value) && $value > 0) {
            $this->paymentAmount = round($baseAmount * $value, 2);
        }
    }

    public function savePayment()
    {
        if (!Auth::user()->can('gestionar_comisiones')) {
            return;
        }

        $this->validate([
            'paymentMethod' => 'required',
            'referenceNumber' => $this->paymentMethod == 'Bank' ? 'required' : 'nullable',
            'selectedBankId' => $this->paymentMethod == 'Bank' ? 'required' : 'nullable',
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentRate' => 'required|numeric|min:0.0001',
        ]);

        try {
            $sale = Sale::findOrFail($this->selectedSaleId);
            
            if ($sale->commission_status === 'paid') {
                $this->dispatch('noty', msg: 'ESTA COMISIÓN YA FUE PAGADA');
                return;
            }

            $bankName = null;
            if ($this->paymentMethod == 'Bank') {
                $bank = \App\Models\Bank::find($this->selectedBankId);
                $bankName = $bank ? $bank->name : null;
            }

            $sale->update([
                'commission_status' => 'paid',
                'commission_paid_at' => Carbon::now(),
                'commission_payment_method' => $this->paymentMethod,
                'commission_payment_reference' => $this->referenceNumber,
                'commission_payment_bank_name' => $bankName,
                'commission_payment_currency' => $this->selectedCurrencyCode,
                'commission_payment_rate' => $this->paymentRate,
                'commission_payment_amount' => $this->paymentAmount,
                'commission_payment_notes' => $this->paymentNotes
            ]);

            $this->dispatch('hide-modal', msg: 'COMISIÓN PAGADA EXITOSAMENTE');
            $this->dispatch('noty', msg: 'COMISIÓN PAGADA EXITOSAMENTE');
        } catch (\Exception $e) {
            $this->dispatch('noty', msg: 'ERROR AL PAGAR COMISIÓN: ' . $e->getMessage());
        }
    }

    public function recalculate($saleId)
    {
        if (!Auth::user()->can('gestionar_comisiones')) {
            $this->dispatch('noty', msg: 'NO TIENES PERMISOS PARA ESTA ACCIÓN');
            return;
        }
        
        try {
            $sale = Sale::findOrFail($saleId);
            $result = \App\Services\CommissionService::calculateCommission($sale);
            $this->dispatch('noty', msg: $result);
        } catch (\Exception $e) {
            $this->dispatch('noty', msg: 'ERROR: ' . $e->getMessage());
        }
    }

    public $selected_commissions = [];

    public function generatePdf()
    {
        $user = Auth::user();
        $canManage = $user->can('gestionar_comisiones');

        $query = Sale::query()
            ->with(['customer', 'user', 'payments'])
            ->where('is_foreign_sale', true)
            ->where(function($q) {
                $q->where('final_commission_amount', '>', 0)
                  ->orWhere('commission_status', 'pending_calculation')
                  ->orWhereNull('final_commission_amount');
            });

        // If specific records are selected, filter by them
        if (!empty($this->selected_commissions)) {
            $query->whereIn('id', $this->selected_commissions);
        } else {
            // Otherwise apply standard filters
            $query->whereBetween('created_at', [$this->dateFrom . ' 00:00:00', $this->dateTo . ' 23:59:59']);

            if ($canManage) {
                if ($this->seller_id != 0) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                }
            } else {
                $query->whereHas('customer', function($q) use ($user) {
                    $q->where('seller_id', $user->id);
                });
            }

            if ($this->status_filter !== 'all') {
                if ($this->status_filter === 'pending') {
                    $query->where('commission_status', '!=', 'paid');
                } else {
                    $query->where('commission_status', $this->status_filter);
                }
            }

            // Filter by Batch
            if (!empty($this->batch_filter)) {
                $query->where('batch_name', 'like', "%{$this->batch_filter}%");
            }
        }

        $commissions = $query->orderBy('created_at', 'desc')->get();
        
        $sellerName = 'Todos';
        if ($canManage) {
            if ($this->seller_id != 0) {
                $seller = User::find($this->seller_id);
                $sellerName = $seller ? $seller->name : 'Todos';
            }
        } else {
            $sellerName = $user->name;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('livewire.commissions.report', [
            'commissions' => $commissions,
            'user' => $user,
            'sellerName' => $sellerName,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'reporte_comisiones.pdf');
    }
}
