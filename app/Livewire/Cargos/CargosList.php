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

    // Action properties
    public $action_type = ''; // 'reject' or 'delete'
    public $reason = '';
    
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
        if (!auth()->user()->can('adjustments.approve_cargo')) {
            $this->dispatch('noty', msg: 'No tienes permisos para aprobar cargos.', type: 'error');
            return;
        }

        $cargo = Cargo::find($id);
        if ($id && $cargo->status == 'pending') {
            try {
                \Illuminate\Support\Facades\DB::beginTransaction();

                foreach ($cargo->details as $item) {
                    $product = $item->product;
                    
                    // Create Product Items if variable
                    if ($item->items_json) {
                        $items = json_decode($item->items_json, true);
                        foreach ($items as $bobina) {
                            \App\Models\ProductItem::create([
                                'product_id' => $item->product_id,
                                'warehouse_id' => $cargo->warehouse_id,
                                'quantity' => $bobina['weight'],
                                'original_quantity' => $bobina['weight'],
                                'color' => $bobina['color'] ?? null,
                                'batch' => $bobina['batch'] ?? null,
                                'status' => 'available'
                            ]);
                        }
                    }

                    // Update Stock
                    // Check if pivot exists
                    $pivot = $product->warehouses()->where('warehouse_id', $cargo->warehouse_id)->first();
                    
                    if ($pivot) {
                        $newQty = $pivot->pivot->stock_qty + $item->quantity;
                        $product->warehouses()->updateExistingPivot($cargo->warehouse_id, ['stock_qty' => $newQty]);
                    } else {
                        $product->warehouses()->attach($cargo->warehouse_id, ['stock_qty' => $item->quantity]);
                    }
                    
                    // Update global stock
                    $product->stock_qty += $item->quantity;
                    $product->save();
                }

                $cargo->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approval_date' => now()
                ]);

                \Illuminate\Support\Facades\DB::commit();
                $this->dispatch('noty', msg: 'Cargo aprobado y stock actualizado.');
                
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                $this->dispatch('noty', msg: 'Error al aprobar: ' . $e->getMessage(), type: 'error');
            }
        }
    }

    public function openActionModal($id, $type)
    {
        $this->cargo_id = $id;
        $this->action_type = $type;
        $this->reason = '';
        $this->dispatch('show-action-modal');
    }

    public function processAction()
    {
        $this->validate([
            'reason' => 'required|min:5|max:255'
        ]);

        $cargo = Cargo::find($this->cargo_id);
        
        if ($this->action_type === 'reject') {
            if (!auth()->user()->can('adjustments.reject_cargo')) {
                $this->dispatch('noty', msg: 'No tienes permisos para rechazar.', type: 'error');
                return;
            }
            $cargo->update([
                'status' => 'rejected',
                'rejection_reason' => $this->reason,
                'rejected_by' => auth()->id(),
                'rejection_date' => now()
            ]);
            $this->dispatch('noty', msg: 'Cargo rechazado.');
        } else {
            if (!auth()->user()->can('adjustments.delete_cargo')) {
                $this->dispatch('noty', msg: 'No tienes permisos para eliminar.', type: 'error');
                return;
            }
            $cargo->update([
                'status' => 'voided',
                'deletion_reason' => $this->reason,
                'deleted_by' => auth()->id(),
                'deletion_date' => now()
            ]);
            $this->dispatch('noty', msg: 'Cargo eliminado.');
        }

        $this->dispatch('hide-action-modal');
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
