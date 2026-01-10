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
                
                // Deduct from source warehouse (Pending transfer logic)
                // Or just reserve? For now, let's just record it. 
                // Actual stock movement might happen on "Completion".
                // But user wants to track stock.
                // Let's deduct from source immediately to prevent selling it?
                // Yes, usually "In Transit" means it left the source.
                
                $this->updateStock($this->from_warehouse_id, $item['product_id'], -$item['qty']);
            }

            DB::commit();
            $this->resetUI();
            $this->dispatch('transfer-added', 'Traspaso Registrado');

        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function updateStock($warehouseId, $productId, $qty)
    {
        // Find or create product_warehouse record
        $stock = DB::table('product_warehouse')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->first();

        if ($stock) {
            DB::table('product_warehouse')
                ->where('id', $stock->id)
                ->update(['stock_qty' => $stock->stock_qty + $qty]);
        } else {
            DB::table('product_warehouse')->insert([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'stock_qty' => $qty,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    public function finalizeTransfer($transferId)
    {
        $transfer = Transfer::find($transferId);
        if (!$transfer || $transfer->status == 'completed') return;

        DB::beginTransaction();
        try {
            $transfer->status = 'completed';
            $transfer->save();

            $details = TransferDetail::where('transfer_id', $transfer->id)->get();
            foreach ($details as $detail) {
                $this->updateStock($transfer->to_warehouse_id, $detail->product_id, $detail->quantity);
            }

            DB::commit();
            $this->dispatch('msg', 'Traspaso completado correctamente');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', 'Error al completar el traspaso: ' . $e->getMessage());
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
    }
}
