<?php

namespace App\Livewire\Descargos;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Descargo;

class DescargosList extends Component
{
    use \Livewire\WithPagination;

    public $search = '';
    public $dateFrom;
    public $dateTo;
    public $warehouse_id;
    
    // Detail properties
    public $descargo_id;
    public $descargoObt;
    public $details = [];
    
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function getDescargoDetail($id)
    {
        $this->descargo_id = $id;
        $this->descargoObt = Descargo::find($id);
        $this->details = $this->descargoObt->details;
        $this->dispatch('show-detail');
    }

    public function approve($id)
    {
        if (!auth()->user()->can('aprobar_descargos')) {
            $this->dispatch('msg-error', 'No tienes permisos para aprobar descargos.');
            return;
        }

        $descargo = Descargo::find($id);
        if ($descargo->status == 'pending') {
            $descargo->update(['status' => 'approved']);
            $this->dispatch('msg-ok', 'Descargo aprobado correctamente.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Layout('layouts.theme.app')]
    public function render()
    {
        $descargos = \App\Models\Descargo::with(['warehouse', 'user'])
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

        return view('livewire.descargos.descargos-list', [
            'descargos' => $descargos,
            'warehouses' => \App\Models\Warehouse::where('is_active', 1)->get()
        ]);
    }
}
