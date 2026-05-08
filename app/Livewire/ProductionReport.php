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
        $query = \App\Models\ProductionLog::with(['shift', 'user', 'materials.product', 'outputs.product'])
            ->orderBy('id', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $data = $query->paginate($this->pagination);

        return view('livewire.production-report', [
            'data' => $data
        ])->extends('layouts.theme.app')->section('content');
    }
}
