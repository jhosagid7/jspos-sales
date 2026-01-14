<?php

namespace App\Livewire\Cargos;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Cargo;

class CargosList extends Component
{
    use \Livewire\WithPagination;

    public $search = '';
    public $dateFrom;
    public $dateTo;
    public $warehouse_id;
    
    // Detail properties
    public $cargo_id;
    public $cargoObt;
    public $details = [];
    
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function getCargoDetail($id)
    {
        $this->cargo_id = $id;
        $this->cargoObt = Cargo::find($id);
        $this->details = $this->cargoObt->details;
        $this->dispatch('show-detail');
    }

    public function approve($id)
    {
        if (!auth()->user()->can('aprobar_cargos')) {
            $this->dispatch('msg-error', 'No tienes permisos para aprobar cargos.');
            return;
        }

        $cargo = Cargo::find($id);
        if ($cargo->status == 'pending') {
            $cargo->update(['status' => 'approved']);
            $this->dispatch('msg-ok', 'Cargo aprobado correctamente.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Layout('layouts.theme.app')]
    public function render()
    {
        $cargos = \App\Models\Cargo::with(['warehouse', 'user'])
            ->when($this->search, function ($q) {
                $q->where('motive', 'like', '%' . $this->search . '%')
                  ->orWhere('authorized_by', 'like', '%' . $this->search . '%')
                  ->orWhere('id', 'like', '%' . $this->search . '%');
            })
            ->when($this->warehouse_id, function ($q) {
                $q->where('warehouse_id', $this->warehouse_id);
            })
            ->when($this->dateFrom && $this->dateTo, function ($q) {
                $q->whereBetween('date', [$this->dateFrom . ' 00:00:00', $this->dateTo . ' 23:59:59']);
            })
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('livewire.cargos.cargos-list', [
            'cargos' => $cargos,
            'warehouses' => \App\Models\Warehouse::where('is_active', 1)->get()
        ]);
    }
}
