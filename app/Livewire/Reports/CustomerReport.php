<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Customer;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerReport extends Component
{
    use WithPagination;

    public $sellers = [];
    public $selectedSellers = [];
    public $groupBy = 'none';
    public $showReport = false;
    public $showPdfModal = false;
    public $pdfUrl = '';
    public $showDeleted = false;

    public $columns = [
        'name' => true,
        'taxpayer_id' => false,
        'address' => true,
        'city' => true,
        'phone' => true,
        'seller' => true,
        'wallet_balance' => false,
        'zone' => false,
        'allow_credit' => false,
        'credit_limit' => false,
        'credit_days' => false,
        'notifications' => false,
        'status' => false,
    ];

    public function mount()
    {
        session(['pos' => 'Reporte de Clientes']);
        $this->sellers = User::sellers()->orderBy('name')->get();
    }

    public function searchData()
    {
        $this->showReport = true;
        $this->dispatch('noty', msg: 'REPORTE ACTUALIZADO');
    }

    public function openPdfPreview()
    {
        $params = [
            'selectedSellers' => implode(',', $this->selectedSellers),
            'groupBy' => $this->groupBy,
            'showDeleted' => $this->showDeleted ? 1 : 0,
            'columns' => json_encode($this->columns),
        ];

        $this->pdfUrl = route('reports.customers.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    public function getReport()
    {
        if (!$this->showReport) {
            return [];
        }

        $query = Customer::with('seller')
            ->when($this->showDeleted, function ($q) {
                $q->withTrashed();
            })
            ->when(!empty($this->selectedSellers), function ($q) {
                $q->whereIn('seller_id', $this->selectedSellers);
            })
            ->orderBy('name');

        $customers = $query->get();

        if ($this->groupBy === 'seller_id') {
            return $customers->groupBy(function ($customer) {
                return $customer->seller ? $customer->seller->name : 'Sin Vendedor';
            });
        }

        return $customers;
    }

    public function render()
    {
        $customers = $this->getReport();

        return view('livewire.reports.customer-report', [
            'customers' => $customers,
        ]);
    }
}
