<?php

namespace App\Livewire\Consultation;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UsdtRecord;
use Carbon\Carbon;

class UsdtConsultation extends Component
{
    use WithPagination;

    public $search = '';
    public $dateFrom;
    public $dateTo;
    public $status = '';
    
    public $selectedRecord = null;
    public $detailsModalOpen = false;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        
        // Check permission if system permissions exist, or check module_usdt
        if (auth()->check() && auth()->user()->can('usdt_index')) {
            // Permission checked
        }
    }

    public function viewDetails($id)
    {
        $this->selectedRecord = UsdtRecord::with([
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
        return redirect()->route('usdt.pdf', ['id' => $id]);
    }

    public function render()
    {
        $query = UsdtRecord::query()->with([
            'payments.sale.customer',
            'salePaymentDetails.sale.customer'
        ]);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference', 'like', '%' . $this->search . '%')
                  ->orWhere('sender_name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->dateFrom) {
            $query->whereDate('usdt_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('usdt_date', '<=', $this->dateTo);
        }
        
        if ($this->status) {
             $query->where('status', $this->status);
        }

        $records = $query->latest('id')->paginate(15);

        return view('livewire.consultation.usdt-consultation', [
            'records' => $records
        ])->extends('layouts.theme.app')->section('content');
    }
}
