<?php

namespace App\Livewire\Consultation;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ZelleRecord;
use App\Models\Sale;
use Carbon\Carbon;

class ZelleConsultation extends Component
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
        
        // Check permission
        if (!auth()->user()->can('zelle_index')) {
            abort(403, 'No tienes permiso para ver este módulo.');
        }
    }

    public function viewDetails($id)
    {
        if (!auth()->user()->can('zelle_view_details')) {
            $this->dispatch('noty', msg: 'No tienes permisos para ver detalles.');
            return;
        }

        $this->selectedRecord = ZelleRecord::with([
            'payments.user', 
            'payments.sale.customer', 
            'payments.sale.user',
            'salePaymentDetails.sale.customer'
        ])->find($id);
        
        // If not directly linked via payments, try to find context
        // ZelleRecords usually link to payments or sales.
        
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
        if (!auth()->user()->can('zelle_print_pdf')) {
            $this->dispatch('noty', msg: 'No tienes permisos para imprimir.');
            return;
        }

        return redirect()->route('zelle.pdf', ['id' => $id]);
    }

    public function render()
    {
        $query = ZelleRecord::query()->with([
            'payments.sale.customer',
            'salePaymentDetails.sale.customer'
        ]);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference', 'like', '%' . $this->search . '%')
                  ->orWhere('sender_name', 'like', '%' . $this->search . '%');
                  // Add logic to search by customer name if possible, requiring join
            });
        }

        if ($this->dateFrom) {
            $query->whereDate('zelle_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('zelle_date', '<=', $this->dateTo);
        }
        
        if ($this->status) {
             $query->where('status', $this->status);
        }

        $records = $query->orderBy('zelle_date', 'desc')->paginate(10);

        return view('livewire.consultation.zelle-consultation', [
            'records' => $records
        ])->layout('layouts.theme.app');
    }
}
