<?php

namespace App\Livewire\Soplados;

use Livewire\Component;
use App\Models\SopladosInventory;
use Livewire\WithPagination;
use Carbon\Carbon;

class SopladosInventoriesList extends Component
{
    use WithPagination;

    public $dateFrom, $dateTo;
    public $status = 'all';
    public $search = '';
    public $selectedInventory;
    public $showModal = false;
    
    private $pagination = 15;

    protected $queryString = [
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'status' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function viewDetails($id)
    {
        $this->selectedInventory = SopladosInventory::with(['details.product', 'supervisor', 'operator', 'warehouse', 'shift'])->findOrFail($id);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedInventory = null;
    }

    public function render()
    {
        $query = SopladosInventory::with(['supervisor', 'operator', 'warehouse', 'shift'])
            ->orderBy('id', 'desc');

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('supervisor', function($sq) {
                    $sq->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('operator', function($oq) {
                    $oq->where('name', 'like', '%' . $this->search . '%');
                });
            });
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return view('livewire.soplados.soplados-inventories-list', [
            'inventories' => $query->paginate($this->pagination)
        ])->extends('layouts.theme.app')->section('content');
    }
}
