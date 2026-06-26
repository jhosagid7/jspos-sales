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
    public $showTrackingPdfModal = false;
    public $trackingPdfUrl = '';
    public $inactivityDays = 0;
    public $showRecoveryPdfModal = false;
    public $recoveryPdfUrl = '';
    public $selectedCustomerIds = [];
    public $selectAll = false;

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
        'last_purchase' => false,
        'total_purchased' => false,
        'risk_level' => false,
    ];

    public function mount()
    {
        session(['pos' => 'Reporte de Clientes']);
        $this->sellers = User::sellers()->orderBy('name')->get();
    }

    public function searchData()
    {
        $this->showReport = true;

        $customers = $this->getReport();
        if ($this->groupBy === 'seller_id') {
            $this->selectedCustomerIds = $customers->flatMap(function($group) {
                return $group->pluck('id');
            })->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedCustomerIds = $customers->pluck('id')->map(fn($id) => (string)$id)->toArray();
        }
        $this->selectAll = true;

        $this->dispatch('noty', msg: 'REPORTE ACTUALIZADO');
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $customers = $this->getReport();
            if ($this->groupBy === 'seller_id') {
                $this->selectedCustomerIds = $customers->flatMap(function($group) {
                    return $group->pluck('id');
                })->map(fn($id) => (string)$id)->toArray();
            } else {
                $this->selectedCustomerIds = $customers->pluck('id')->map(fn($id) => (string)$id)->toArray();
            }
        } else {
            $this->selectedCustomerIds = [];
        }
    }

    public function updatedSelectedCustomerIds($value)
    {
        $customers = $this->getReport();
        if ($this->groupBy === 'seller_id') {
            $totalCount = $customers->flatMap(function($group) {
                return $group->pluck('id');
            })->count();
        } else {
            $totalCount = $customers->count();
        }

        $this->selectAll = ($totalCount > 0 && count($this->selectedCustomerIds) === $totalCount);
    }

    public function openPdfPreview()
    {
        $params = [
            'selectedSellers' => implode(',', $this->selectedSellers),
            'groupBy' => $this->groupBy,
            'showDeleted' => $this->showDeleted ? 1 : 0,
            'columns' => json_encode($this->columns),
            'inactivityDays' => $this->inactivityDays,
        ];
        if (!empty($this->selectedCustomerIds)) {
            $params['selectedCustomers'] = implode(',', $this->selectedCustomerIds);
        }

        $this->pdfUrl = route('reports.customers.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    public function openTrackingPdfPreview()
    {
        $params = [
            'selectedSellers' => implode(',', $this->selectedSellers),
            'groupBy' => $this->groupBy,
            'showDeleted' => $this->showDeleted ? 1 : 0,
            'columns' => json_encode($this->columns),
            'inactivityDays' => $this->inactivityDays,
        ];
        if (!empty($this->selectedCustomerIds)) {
            $params['selectedCustomers'] = implode(',', $this->selectedCustomerIds);
        }

        $this->trackingPdfUrl = route('reports.customers.tracking.pdf', $params);
        $this->showTrackingPdfModal = true;
    }

    public function closeTrackingPdfPreview()
    {
        $this->showTrackingPdfModal = false;
        $this->trackingPdfUrl = '';
    }

    public function openRecoveryPdfPreview()
    {
        $params = [
            'selectedSellers' => implode(',', $this->selectedSellers),
            'groupBy' => $this->groupBy,
            'showDeleted' => $this->showDeleted ? 1 : 0,
            'columns' => json_encode($this->columns),
            'inactivityDays' => $this->inactivityDays,
        ];
        if (!empty($this->selectedCustomerIds)) {
            $params['selectedCustomers'] = implode(',', $this->selectedCustomerIds);
        }

        $this->recoveryPdfUrl = route('reports.customers.recovery.pdf', $params);
        $this->showRecoveryPdfModal = true;
    }

    public function closeRecoveryPdfPreview()
    {
        $this->showRecoveryPdfModal = false;
        $this->recoveryPdfUrl = '';
    }

    public function getReport()
    {
        if (!$this->showReport) {
            return [];
        }

        $query = Customer::with('seller')
            ->select('customers.*')
            ->selectSub(function ($q) {
                $q->selectRaw('max(created_at)')
                    ->from('sales')
                    ->whereColumn('sales.customer_id', 'customers.id')
                    ->where('sales.status', '<>', 'returned')
                    ->whereNull('sales.deletion_approved_at');
            }, 'last_purchase_at')
            ->selectSub(function ($q) {
                $q->selectRaw('coalesce(sum(total_usd), 0)')
                    ->from('sales')
                    ->whereColumn('sales.customer_id', 'customers.id')
                    ->where('sales.status', '<>', 'returned')
                    ->whereNull('sales.deletion_approved_at');
            }, 'total_purchased_usd')
            ->when($this->showDeleted, function ($q) {
                $q->withTrashed();
            })
            ->when(!empty($this->selectedSellers), function ($q) {
                $q->whereIn('seller_id', $this->selectedSellers);
            })
            ->when($this->inactivityDays > 0, function ($q) {
                $threshold = Carbon::now()->subDays($this->inactivityDays)->toDateTimeString();
                $q->where(function ($sub) use ($threshold) {
                    $sub->whereRaw('(select max(created_at) from sales where sales.customer_id = customers.id and sales.status <> "returned" and sales.deletion_approved_at is null) < ?', [$threshold])
                        ->orWhereRaw('not exists (select 1 from sales where sales.customer_id = customers.id and sales.status <> "returned" and sales.deletion_approved_at is null)');
                });
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
