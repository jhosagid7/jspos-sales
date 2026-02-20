<?php

namespace App\Livewire\Consultation;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BankRecord;
use App\Models\Bank;
use Carbon\Carbon;

class BankConsultation extends Component
{
    use WithPagination;

    public $search = '';
    public $dateFrom;
    public $dateTo;
    public $bank_id = '';
    public $status = '';
    
    public $selectedRecord = null;
    public $detailsModalOpen = false;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        
        if (!auth()->user()->can('bank_index')) {
            abort(403, 'No tienes permiso para ver este módulo.');
        }
    }

    public function viewDetails($id)
    {
        if (!auth()->user()->can('bank_view_details')) {
            $this->dispatch('noty', msg: 'No tienes permisos para ver detalles.');
            return;
        }

        $this->selectedRecord = BankRecord::with([
            'bank', 
            'payments.user', 
            'payments.sale.customer', 
            'payments.sale.user',
            'salePaymentDetails.sale.customer'
        ])->find($id);
        
        $this->detailsModalOpen = true;
        $this->dispatch('show-details-modal');
    }

    public function closeDetails()
    {
        $this->detailsModalOpen = false;
        $this->selectedRecord = null;
        $this->dispatch('hide-details-modal');
    }

    public function downloadPdf($id)
    {
        if (!auth()->user()->can('bank_print_pdf')) {
            $this->dispatch('noty', msg: 'No tienes permisos para imprimir.');
            return;
        }

        return redirect()->route('bank.pdf', ['id' => $id]);
    }

    public function render()
    {
        $query = BankRecord::query()->with([
            'bank',
            'payments.sale.customer',
            'salePaymentDetails.sale.customer'
        ]);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference', 'like', '%' . $this->search . '%')
                  ->orWhere('note', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->dateFrom) {
            $query->whereDate('payment_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('payment_date', '<=', $this->dateTo);
        }

        if ($this->bank_id) {
            $query->where('bank_id', $this->bank_id);
        }
        
        if ($this->status) {
             $query->where('status', $this->status);
        }

        $records = $query->orderBy('payment_date', 'desc')->paginate(10);
        $banks = Bank::orderBy('name')->get();

        return view('livewire.consultation.bank-consultation', [
            'records' => $records,
            'banks' => $banks
        ])->layout('layouts.theme.app');
    }
}
