<?php

namespace App\Livewire\Cargos;

use Livewire\Component;
use Livewire\Attributes\Layout;

class CreateCargo extends Component
{
    use \Livewire\WithPagination;

    public $warehouse_id;
    public $motive;
    public $authorized_by;
    public $comments;
    public $date;
    
    public $search;
    public $searchResults = [];
    public $cart = [];
    public $selectedIndex = -1;
    
    public $warehouses = [];

    public function mount()
    {
        $this->date = now()->format('Y-m-d\TH:i');
        $this->warehouses = \App\Models\Warehouse::where('is_active', 1)->get();
        // Default warehouse if exists
        $this->warehouse_id = $this->warehouses->first()->id ?? null;
    }

    public function updatedSearch()
    {
        $search = trim($this->search);
        $this->selectedIndex = -1;
        
        if (strlen($search) > 1) {
            $query = \App\Models\Product::query();
            
            // Tokenize search terms for multi-word search (inverted words support)
            $tokens = explode(' ', $search);
            
            foreach ($tokens as $token) {
                if (!empty($token)) {
                    $query->where(function($q) use ($token) {
                        $q->where('name', 'like', "%{$token}%")
                          ->orWhere('sku', 'like', "%{$token}%")
                          ->orWhereHas('category', function ($subQuery) use ($token) {
                              $subQuery->where('name', 'like', "%{$token}%");
                          });
                    });
                }
            }
            
            $this->searchResults = $query->take(10)->get();
        } else {
            $this->searchResults = [];
        }
    }

    public function selectProduct($index)
    {
        if ($index >= 0 && isset($this->searchResults[$index])) {
            $this->addToCart($this->searchResults[$index]->id);
        } elseif (count($this->searchResults) > 0) {
            // If nothing selected but Enter pressed, add the first one
            $this->addToCart($this->searchResults[0]->id);
        }
    }

    public function keyDown($key)
    {
        if ($key === 'ArrowDown') {
            $this->selectedIndex = min(count($this->searchResults) - 1, $this->selectedIndex + 1);
        } elseif ($key === 'ArrowUp') {
            $this->selectedIndex = max(-1, $this->selectedIndex - 1);
        } elseif ($key === 'Enter') {
            $this->selectProduct($this->selectedIndex);
        }
    }

    // Variable Item Inputs
    public $vw_weight, $vw_color, $vw_batch, $current_product_id;

    public function addToCart($productId)
    {
        $product = \App\Models\Product::find($productId);
        if (!$product) return;

        if (isset($this->cart[$productId])) {
            if (!$product->is_variable_quantity) {
                 $this->cart[$productId]['quantity']++;
            }
        } else {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'cost' => $product->cost,
                'quantity' => $product->is_variable_quantity ? 0 : 1,
                'is_variable' => (bool)$product->is_variable_quantity,
                'items' => [] 
            ];
        }
        
        $this->search = '';
        $this->searchResults = [];
    }
    
    public function openVariableModal($productId)
    {
        $this->current_product_id = $productId;
        $this->vw_weight = '';
        $this->vw_color = '';
        $this->vw_batch = '';
        $this->dispatch('show-variable-modal');
    }

    public function addVariableItem()
    {
        $this->validate([
            'vw_weight' => 'required|numeric|min:0.01',
            'vw_color' => 'nullable|string|max:50',
            'vw_batch' => 'nullable|string|max:50'
        ]);

        if (isset($this->cart[$this->current_product_id])) {
            $this->cart[$this->current_product_id]['items'][] = [
                'weight' => $this->vw_weight,
                'color' => $this->vw_color,
                'batch' => $this->vw_batch
            ];
            
            // Recalculate total weight/quantity
            $totalWeight = collect($this->cart[$this->current_product_id]['items'])->sum('weight');
            $this->cart[$this->current_product_id]['quantity'] = $totalWeight;
        }

        $this->reset(['vw_weight', 'vw_color', 'vw_batch']);
        $this->dispatch('noty', msg: 'Item agregado a la lista');
        $this->dispatch('focus-weight');
    }

    public function removeVariableItem($productId, $index)
    {
        if (isset($this->cart[$productId]['items'][$index])) {
            unset($this->cart[$productId]['items'][$index]);
            $this->cart[$productId]['items'] = array_values($this->cart[$productId]['items']);
            
            $totalWeight = collect($this->cart[$productId]['items'])->sum('weight');
            $this->cart[$productId]['quantity'] = $totalWeight;
        }
    }


    public function save()
    {
        $this->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'motive' => 'required|string|max:255',
            'authorized_by' => 'required|string|max:255',
            'cart' => 'required|array|min:1'
        ]);

        // Validate Variable Items
        foreach ($this->cart as $item) {
            $product = \App\Models\Product::find($item['id']);
            
            // Validate Decimals
            if ($product && !$product->allow_decimal) {
                if (floor($item['quantity']) != $item['quantity']) {
                    $this->addError("cart.{$item['id']}.quantity", "El producto {$product->name} no permite decimales.");
                    $this->dispatch('noty', msg: "El producto {$product->name} no permite cantidades decimales.", type: 'error');
                    return;
                }
            }

            // Validate Missing Items
            if ($item['is_variable'] && empty($item['items'])) {
                $this->addError("cart", "El producto {$item['name']} requiere items.");
                $this->dispatch('noty', msg: "Faltan items en producto {$item['name']}", type: 'error');
                return;
            }
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $cargo = \App\Models\Cargo::create([
                'warehouse_id' => $this->warehouse_id,
                'user_id' => auth()->id(),
                'authorized_by' => $this->authorized_by,
                'motive' => $this->motive,
                'date' => $this->date,
                'comments' => $this->comments,
                'status' => 'pending', // Pending approval or specific status? Using 'pending' as default.
            ]);

            foreach ($this->cart as $item) {
                \App\Models\CargoDetail::create([
                    'cargo_id' => $cargo->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'cost' => $item['cost'] 
                ]);

                // Create Product Items if variable
                if ($item['is_variable'] && !empty($item['items'])) {
                    foreach ($item['items'] as $bobina) {
                        \App\Models\ProductItem::create([
                            'product_id' => $item['id'],
                            'warehouse_id' => $this->warehouse_id,
                            'quantity' => $bobina['weight'],
                            'original_quantity' => $bobina['weight'],
                            'color' => $bobina['color'] ?? null,
                            'batch' => $bobina['batch'] ?? null,
                            'status' => 'available'
                        ]);
                    }
                }

                // Update Stock
                $product = \App\Models\Product::find($item['id']);
                
                // Check if pivot exists
                $pivot = $product->warehouses()->where('warehouse_id', $this->warehouse_id)->first();
                
                if ($pivot) {
                    $newQty = $pivot->pivot->stock_qty + $item['quantity'];
                    $product->warehouses()->updateExistingPivot($this->warehouse_id, ['stock_qty' => $newQty]);
                } else {
                    $product->warehouses()->attach($this->warehouse_id, ['stock_qty' => $item['quantity']]);
                }
                
                // Update global stock
                $product->stock_qty += $item['quantity'];
                $product->save();
            }

            \Illuminate\Support\Facades\DB::commit();
            
            $this->reset(['cart', 'motive', 'authorized_by', 'comments', 'search']);
            $this->dispatch('noty', msg: 'Cargo registrado correctamente');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->dispatch('noty', msg: 'Error al registrar cargo: ' . $e->getMessage(), type: 'error');
        }
    }

    #[Layout('layouts.theme.app')]
    public function render()
    {
        return view('livewire.cargos.create-cargo');
    }
}
