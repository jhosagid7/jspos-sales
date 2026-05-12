<?php

namespace App\Livewire;

use Livewire\Component;

class ProductionReport extends Component
{
    use \Livewire\WithPagination;

    public $dateFrom, $dateTo;
    private $pagination = 10;

    public function mount()
    {
        $this->dateFrom = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = \Carbon\Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $query = \App\Models\ProductionLog::with(['shift', 'user', 'materials.product', 'outputs.product.productionTarget'])
            ->orderBy('id', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $data = $query->paginate($this->pagination);

        // Calculate Summaries for the selected period
        $summaryQuery = \App\Models\ProductionLog::with(['materials', 'outputs'])
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo);

        $totalGood = 0;
        $totalDamaged = 0;
        $totalMaterials = 0;

        foreach ($summaryQuery->get() as $log) {
            $totalGood += $log->outputs->whereIn('quality', ['1st', '2nd'])->sum('quantity');
            $totalDamaged += $log->outputs->where('quality', 'damaged')->sum('quantity');
            $totalMaterials += $log->materials->sum('quantity');
        }

        $yield = $totalMaterials > 0 ? ($totalGood / $totalMaterials) * 100 : 0;

        return view('livewire.production-report', [
            'data' => $data,
            'stats' => [
                'totalGood' => $totalGood,
                'totalDamaged' => $totalDamaged,
                'totalMaterials' => $totalMaterials,
                'yield' => $yield
            ]
        ])->extends('layouts.theme.app')->section('content');
    }
}
