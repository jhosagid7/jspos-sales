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

    // Action properties
    public $action_type = ''; // 'reject' or 'delete'
    public $reason = '';
    
    public function getDescargoDetail($id)
    {
        $this->descargo_id = $id;
        $this->descargoObt = Descargo::find($id);
        $this->details = $this->descargoObt->details;
        $this->dispatch('show-detail');
    }

    public function approve($id)
    {
        if (!auth()->user()->can('adjustments.approve_descargo')) {
            $this->dispatch('noty', msg: 'No tienes permisos para aprobar descargos.', type: 'error');
            return;
        }

        $descargo = Descargo::find($id);
        if ($id && $descargo->status == 'pending') {
            try {
                \Illuminate\Support\Facades\DB::beginTransaction();

                foreach ($descargo->details as $item) {
                    $product = $item->product;
                    
                    // Handle variable product items cleanup
                    if ($item->items_json) {
                        $items = json_decode($item->items_json, true);
                        foreach ($items as $bobina) {
                            // Find and delete matching items (FIFO approach or matching weight/color)
                            $foundItem = \App\Models\ProductItem::where('product_id', $item->product_id)
                                ->where('warehouse_id', $descargo->warehouse_id)
                                ->where('quantity', $bobina['weight'])
                                ->where('status', 'available')
                                ->when($bobina['color'], function($q) use ($bobina) { $q->where('color', $bobina['color']); })
                                ->first();

                            if ($foundItem) {
                                $foundItem->delete();
                            } else {
                                // If not exact match found, just log it or pick the first available of same weight?
                                // For now, we take the first available to ensure stock remains correct
                                $anyItem = \App\Models\ProductItem::where('product_id', $item->product_id)
                                    ->where('warehouse_id', $descargo->warehouse_id)
                                    ->where('status', 'available')
                                    ->orderBy('created_at', 'asc')
                                    ->first();
                                if ($anyItem) {
                                    $anyItem->delete();
                                }
                            }
                        }
                    }

                    // Update Stock (REDUCE)
                    $pivot = $product->warehouses()->where('warehouse_id', $descargo->warehouse_id)->first();
                    
                    if ($pivot) {
                        $newQty = $pivot->pivot->stock_qty - $item->quantity;
                        $product->warehouses()->updateExistingPivot($descargo->warehouse_id, ['stock_qty' => $newQty]);
                    } else {
                        $product->warehouses()->attach($descargo->warehouse_id, ['stock_qty' => -$item->quantity]);
                    }
                    
                    // Update global stock
                    $product->stock_qty -= $item->quantity;
                    $product->save();
                }

                $descargo->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approval_date' => now()
                ]);

                \Illuminate\Support\Facades\DB::commit();
                $this->dispatch('noty', msg: 'Descargo aprobado y stock reducido.');
                
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                $this->dispatch('noty', msg: 'Error al aprobar: ' . $e->getMessage(), type: 'error');
            }
        }
    }

    public function openActionModal($id, $type)
    {
        $this->descargo_id = $id;
        $this->action_type = $type;
        $this->reason = '';
        $this->dispatch('show-action-modal');
    }

    public function processAction()
    {
        $this->validate(['reason' => 'required|min:5|max:255']);

        $descargo = Descargo::find($this->descargo_id);
        
        if ($this->action_type === 'reject') {
            if (!auth()->user()->can('adjustments.reject_descargo')) {
                $this->dispatch('noty', msg: 'Sin permisos para rechazar.', type: 'error');
                return;
            }
            $descargo->update([
                'status' => 'rejected',
                'rejection_reason' => $this->reason,
                'rejected_by' => auth()->id(),
                'rejection_date' => now()
            ]);
            $this->dispatch('noty', msg: 'Descargo rechazado.');
        } else {
            if (!auth()->user()->can('adjustments.delete_descargo')) {
                $this->dispatch('noty', msg: 'Sin permisos para eliminar.', type: 'error');
                return;
            }

            try {
                \Illuminate\Support\Facades\DB::beginTransaction();

                // If descargo was approved, we MUST reverse the stock impacts (INCREASE)
                if ($descargo->status == 'approved') {
                    foreach ($descargo->details as $item) {
                        $product = $item->product;

                        // Recreate ProductItems that were deleted
                        if ($item->items_json) {
                            $items = json_decode($item->items_json, true);
                            foreach ($items as $bobina) {
                                \App\Models\ProductItem::create([
                                    'product_id' => $item->product_id,
                                    'warehouse_id' => $descargo->warehouse_id,
                                    'quantity' => $bobina['weight'],
                                    'original_quantity' => $bobina['weight'],
                                    'color' => $bobina['color'] ?? null,
                                    'batch' => $bobina['batch'] ?? null,
                                    'status' => 'available'
                                ]);
                            }
                        }

                        // Reverse Stock (INCREASE)
                        $pivot = $product->warehouses()->where('warehouse_id', $descargo->warehouse_id)->first();
                        
                        if ($pivot) {
                            $newQty = $pivot->pivot->stock_qty + $item->quantity;
                            $product->warehouses()->updateExistingPivot($descargo->warehouse_id, ['stock_qty' => $newQty]);
                        } else {
                            $product->warehouses()->attach($descargo->warehouse_id, ['stock_qty' => $item->quantity]);
                        }
                        
                        // Update global stock
                        $product->stock_qty += $item->quantity;
                        $product->save();
                    }
                }

                $descargo->update([
                    'status' => 'voided',
                    'deletion_reason' => $this->reason,
                    'deleted_by' => auth()->id(),
                    'deletion_date' => now()
                ]);

                \Illuminate\Support\Facades\DB::commit();
                $this->dispatch('noty', msg: 'Descargo eliminado y stock restaurado.');

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                $this->dispatch('noty', msg: 'Error al eliminar: ' . $e->getMessage(), type: 'error');
            }
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
