<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;

class AuditReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $dateFrom, $dateTo;
    public $userId;
    public $searchTerm;

    public function mount()
    {
        $this->dateFrom = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = \Carbon\Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $query = Activity::with(['causer'])
            ->whereIn('subject_type', ['App\Models\Product', 'App\Models\ProductWarehouse'])
            ->orderBy('created_at', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        if ($this->userId) {
            $query->where('causer_id', $this->userId);
        }
        
        if ($this->searchTerm) {
             // Since subject is polymorphic, searching by product name can be tricky directly in SQL without joins
             // We'll search by description or event.
             $query->where(function($q) {
                  $q->where('description', 'like', "%{$this->searchTerm}%")
                    ->orWhere('event', 'like', "%{$this->searchTerm}%");
             });
        }

        $logs = $query->paginate(20);

        // Enhance logs with subject details
        $logs->getCollection()->transform(function ($log) {
            $subjectName = 'Desconocido';
            $warehouseName = '';
            
            if ($log->subject_type === 'App\Models\Product' && $log->subject) {
                $subjectName = $log->subject->name;
            } elseif ($log->subject_type === 'App\Models\ProductWarehouse' && $log->subject) {
                $product = Product::find($log->subject->product_id);
                $warehouse = Warehouse::find($log->subject->warehouse_id);
                if ($product) $subjectName = $product->name;
                if ($warehouse) $warehouseName = ' (' . $warehouse->name . ')';
            }
            
            $log->custom_subject_name = $subjectName . $warehouseName;
            return $log;
        });

        $users = User::orderBy('name')->get();

        return view('livewire.reports.audit-report', [
            'logs' => $logs,
            'users' => $users
        ])->extends('layouts.theme.app')
          ->section('content');
    }
}
