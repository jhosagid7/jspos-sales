<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Transfers extends Component
{
    use WithPagination;

    public $from_warehouse_id, $to_warehouse_id, $note, $status = 'pending';
    public $search, $product_search, $selected_id, $pageTitle, $componentName;
    public $cart = [];
    public $is_creating = false;
    private $pagination = 5;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Traspasos';
        $this->from_warehouse_id = '';
        $this->to_warehouse_id = '';
    }

    public function render()
    {
        if (strlen($this->search) > 0)
            $data = Transfer::where('note', 'like', '%' . $this->search . '%')
                ->paginate($this->pagination);
        else
            $data = Transfer::orderBy('id', 'desc')->paginate($this->pagination);

        $warehouses = Warehouse::where('is_active', true)->get();
        
        // Search products for autocomplete/selection
        $products = [];
        if(strlen($this->product_search) > 0) {
            $products = Product::where('name', 'like', '%' . $this->product_search . '%')
                        ->orWhere('sku', 'like', '%' . $this->product_search . '%')
                        ->take(5)->get();
        }

        return view('livewire.transfers', [
            'data' => $data,
            'warehouses' => $warehouses,
            'products_search_result' => $products
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function addToCart($productId)
    {
        $product = Product::find($productId);
        if(!$product) return;

        // Check if already in cart
        foreach($this->cart as $key => $item) {
            if($item['product_id'] == $productId) {
                $this->cart[$key]['qty']++;
                return;
            }
        }

        $this->cart[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'qty' => 1
        ];
        
        $this->product_search = '';
    }

    public function updateQty($index, $qty)
    {
        if($qty <= 0) {
            $this->removeFromCart($index);
            return;
        }
        $this->cart[$index]['qty'] = $qty;
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function saveTransfer()
    {
        $rules = [
            'from_warehouse_id' => 'required|different:to_warehouse_id',
            'to_warehouse_id' => 'required',
            'cart' => 'required|array|min:1'
        ];

        $messages = [
            'from_warehouse_id.required' => 'Seleccione origen',
            'from_warehouse_id.different' => 'Origen y destino deben ser diferentes',
            'to_warehouse_id.required' => 'Seleccione destino',
            'cart.required' => 'Agregue productos al traspaso',
            'cart.min' => 'Agregue al menos un producto'
        ];

        $this->validate($rules, $messages);

        DB::beginTransaction();
        try {
            $transfer = Transfer::create([
                'from_warehouse_id' => $this->from_warehouse_id,
                'to_warehouse_id' => $this->to_warehouse_id,
                'user_id' => Auth::user()->id,
                'status' => 'pending',
                'note' => $this->note
            ]);

            foreach($this->cart as $item) {
                TransferDetail::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['qty']
                ]);
                
                // We DO NOT deduct stock here anymore. Stock is deducted upon Dispatch.
            }

            DB::commit();
            $this->resetUI();
            $this->dispatch('transfer-added', 'Traspaso Registrado (Pendiente)');

        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function dispatchTransferFromWeb($transferId)
    {
        $transfer = Transfer::find($transferId);
        if (!$transfer || $transfer->status !== 'pending') return;

        DB::beginTransaction();
        try {
            $transfer->update([
                'status' => 'dispatched',
                'dispatched_by_id' => Auth::user()->id
            ]);

            $details = TransferDetail::where('transfer_id', $transfer->id)->get();
            foreach ($details as $detail) {
                $this->updateStock($transfer->from_warehouse_id, $detail->product_id, -$detail->quantity);
            }

            DB::commit();
            $this->dispatch('msg', 'Traspaso Despachado (Stock descontado del origen)');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', 'Error al despachar: ' . $e->getMessage());
        }
    }

    public function updateStock($warehouseId, $productId, $qty)
    {
        $pw = \App\Models\ProductWarehouse::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['stock_qty' => 0]
        );

        $pw->stock_qty += $qty;
        $pw->save();

        $config = \App\Models\Configuration::first();
        $defaultWarehouseId = $config->default_warehouse_id ?? \App\Models\Warehouse::first()->id ?? 1;

        if ($warehouseId == $defaultWarehouseId) {
            $product = \App\Models\Product::find($productId);
            if ($product) {
                $product->stock_qty += $qty;
                $product->save();
            }
        }
    }

    // Modal properties for receiving
    public $receiving_transfer_id;
    public $receiving_details = [];
    public $rejection_reason = '';

    public function openReceiveModal($transferId)
    {
        $transfer = Transfer::with('details.product')->find($transferId);
        if (!$transfer || $transfer->status !== 'dispatched') return;

        $this->receiving_transfer_id = $transfer->id;
        $this->rejection_reason = '';
        $this->receiving_details = [];

        foreach ($transfer->details as $detail) {
            $this->receiving_details[$detail->id] = [
                'product_name' => $detail->product->name,
                'requested' => $detail->quantity,
                'received' => $detail->quantity // Default to fully received
            ];
        }

        $this->dispatch('show-receive-modal');
    }

    public function finalizeTransfer()
    {
        $transfer = Transfer::find($this->receiving_transfer_id);
        if (!$transfer || $transfer->status !== 'dispatched') return;

        DB::beginTransaction();
        try {
            $hasRejections = false;

            foreach ($this->receiving_details as $detailId => $data) {
                $detail = TransferDetail::find($detailId);
                $received = floatval($data['received']);
                
                if ($received > $detail->quantity) {
                    $received = $detail->quantity;
                }
                
                $rejected = $detail->quantity - $received;
                
                if ($rejected > 0) {
                    $hasRejections = true;
                    // We DO NOT return stock here. The Soplados App operator must receive the return in the App.
                }

                $detail->update([
                    'received_quantity' => $received,
                    'rejected_quantity' => $rejected
                ]);

                // Add received stock to destination warehouse
                if ($received > 0) {
                    $this->updateStock($transfer->to_warehouse_id, $detail->product_id, $received);
                }
            }

            $transfer->update([
                'status' => $hasRejections ? 'completed_partial' : 'completed',
                'received_by_id' => Auth::user()->id,
                'rejection_reason' => $this->rejection_reason
            ]);

            DB::commit();
            $this->dispatch('hide-receive-modal');
            $this->dispatch('msg', 'Traspaso procesado. Los rechazos pendientes aparecerán en la App del operador.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    public function approveTransfer($id)
    {
        $transfer = Transfer::with('details')->find($id);
        if (!$transfer || $transfer->status !== 'dispatched') return;

        DB::beginTransaction();
        try {
            foreach ($transfer->details as $detail) {
                // Fully received
                $detail->update([
                    'received_quantity' => $detail->quantity,
                    'rejected_quantity' => 0
                ]);
                // Add received stock to destination warehouse
                $this->updateStock($transfer->to_warehouse_id, $detail->product_id, $detail->quantity);
            }

            $transfer->update([
                'status' => 'completed',
                'received_by_id' => Auth::user()->id,
                'rejection_reason' => null
            ]);

            DB::commit();
            $this->dispatch('msg', 'Traspaso aprobado y mercancía ingresada al destino.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', 'Error al aprobar: ' . $e->getMessage());
        }
    }

    public function rejectTransfer($id)
    {
        $transfer = Transfer::with('details')->find($id);
        if (!$transfer || $transfer->status !== 'dispatched') return;

        DB::beginTransaction();
        try {
            foreach ($transfer->details as $detail) {
                // Fully rejected
                $detail->update([
                    'received_quantity' => 0,
                    'rejected_quantity' => $detail->quantity
                ]);
                // We DO NOT return stock here. The operator must confirm the return in the app.
            }

            $transfer->update([
                'status' => 'rejected',
                'received_by_id' => Auth::user()->id,
                'rejection_reason' => 'Rechazado completamente'
            ]);

            DB::commit();
            $this->dispatch('msg', 'Traspaso rechazado. La devolución aparecerá en la App para el operador.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', 'Error al rechazar: ' . $e->getMessage());
        }
    }

    public function deleteTransfer($id)
    {
        $transfer = Transfer::find($id);
        if (!$transfer || $transfer->status !== 'pending') {
            $this->dispatch('error', 'Solo se pueden eliminar traspasos en estado pendiente.');
            return;
        }

        try {
            $transfer->delete();
            $this->dispatch('msg', 'Traspaso eliminado correctamente.');
        } catch (\Exception $e) {
            $this->dispatch('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $this->resetUI();
        $this->is_creating = true;
    }

    public function cancel()
    {
        $this->resetUI();
        $this->is_creating = false;
    }

    public function resetUI()
    {
        $this->from_warehouse_id = '';
        $this->to_warehouse_id = '';
        $this->note = '';
        $this->cart = [];
        $this->search = '';
        $this->product_search = '';
        $this->selected_id = 0;
        $this->is_creating = false;
        $this->receiving_transfer_id = null;
        $this->receiving_details = [];
        $this->rejection_reason = '';
    }
}
