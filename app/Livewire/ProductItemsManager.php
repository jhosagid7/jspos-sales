<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Warehouse;

class ProductItemsManager extends Component
{
    public $product_id;
    public $items = [];
    public $warehouses = [];
    
    // Form properties
    public $weight;
    public $color;
    public $warehouse_id;
    public $batch;

    public function mount($productId)
    {
        $this->product_id = $productId;
        $this->warehouses = Warehouse::all();
        // Default to first warehouse
        if($this->warehouses->count() > 0) {
            $this->warehouse_id = $this->warehouses->first()->id;
        }
        $this->loadItems();
    }

    public function loadItems()
    {
        $this->items = ProductItem::where('product_id', $this->product_id)
            ->where('status', 'available')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function saveItem()
    {
        $this->validate([
            'weight' => 'required|numeric|min:0.01',
            'warehouse_id' => 'required|exists:warehouses,id',
            'color' => 'nullable|string|max:50',
            'batch' => 'nullable|string|max:50'
        ]);

        ProductItem::create([
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'quantity' => $this->weight,
            'original_quantity' => $this->weight,
            'status' => 'available',
            'color' => $this->color,
            'batch' => $this->batch
        ]);

        // Update Master Stock
        $this->updateMasterStock();

        $this->reset(['weight', 'color', 'batch']);
        $this->loadItems();
        $this->dispatch('noty', msg: 'Item creado correctamente');
    }

    public function deleteItem($itemId)
    {
        $item = ProductItem::find($itemId);
        if ($item) {
            $item->delete(); // Or set status to 'deleted' logic if soft delete needed currently hard delete or 'discharged'
            // For now, hard delete is simple for "Management" if mistakes made. 
            // If it was sold, it wouldn't be 'available' status anyway, so loaded list is safe.
            
            $this->updateMasterStock();
            $this->loadItems();
            $this->dispatch('noty', msg: 'Item eliminado correctamente');
        }
    }

    public function updateMasterStock()
    {
        $product = Product::find($this->product_id);
        if ($product) {
            $totalStock = ProductItem::where('product_id', $this->product_id)
                ->where('status', 'available')
                ->sum('quantity');
            
            $product->stock_qty = $totalStock;
            $product->save();
             
             // Also update warehouse stock pivot if needed
             // This logic might need to be more granular per warehouse
             foreach($this->warehouses as $wh) {
                 $whStock = ProductItem::where('product_id', $this->product_id)
                    ->where('warehouse_id', $wh->id)
                    ->where('status', 'available')
                    ->sum('quantity');
                 
                 $product->warehouses()->syncWithoutDetaching([
                     $wh->id => ['stock_qty' => $whStock]
                 ]);
             }
        }
    }

    public function render()
    {
        return view('livewire.product-items-manager');
    }
}
