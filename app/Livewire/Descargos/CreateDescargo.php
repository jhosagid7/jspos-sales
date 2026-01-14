<?php

namespace App\Livewire\Descargos;

use Livewire\Component;
use Livewire\Attributes\Layout;

class CreateDescargo extends Component
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

    public function addToCart($productId)
    {
        $product = \App\Models\Product::find($productId);
        if (!$product) return;

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        } else {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'cost' => $product->cost, // Add cost
                'quantity' => 1
            ];
        }
        
        $this->search = '';
        $this->searchResults = [];
    }

    public function updateQuantity($productId, $qty)
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity'] = $qty;
        }
    }

    public function removeFromCart($productId)
    {
        unset($this->cart[$productId]);
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

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $descargo = \App\Models\Descargo::create([
                'warehouse_id' => $this->warehouse_id,
                'user_id' => auth()->id(),
                'authorized_by' => $this->authorized_by,
                'motive' => $this->motive,
                'date' => $this->date,
                'comments' => $this->comments,
                'status' => 'pending',
            ]);

            foreach ($this->cart as $item) {
                \App\Models\DescargoDetail::create([
                    'descargo_id' => $descargo->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'cost' => $item['cost'] // Save cost
                ]);

                // Update Stock (SUBTRACT)
                $product = \App\Models\Product::find($item['id']);
                
                // Check if pivot exists
                $pivot = $product->warehouses()->where('warehouse_id', $this->warehouse_id)->first();
                
                if ($pivot) {
                    $newQty = $pivot->pivot->stock_qty - $item['quantity'];
                    $product->warehouses()->updateExistingPivot($this->warehouse_id, ['stock_qty' => $newQty]);
                } else {
                    // If not in warehouse, create with negative stock? Or 0 - qty?
                    // Assuming we can have negative stock or it starts at 0.
                    $product->warehouses()->attach($this->warehouse_id, ['stock_qty' => -$item['quantity']]);
                }
                
                // Update global stock
                $product->stock_qty -= $item['quantity'];
                $product->save();
            }

            \Illuminate\Support\Facades\DB::commit();
            
            $this->reset(['cart', 'motive', 'authorized_by', 'comments', 'search']);
            $this->dispatch('noty', msg: 'Descargo registrado correctamente');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->dispatch('noty', msg: 'Error al registrar descargo: ' . $e->getMessage(), type: 'error');
        }
    }

    #[Layout('layouts.theme.app')]
    public function render()
    {
        return view('livewire.descargos.create-descargo');
    }
}
