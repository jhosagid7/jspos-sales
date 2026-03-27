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

    public $cargo_id = null;

    public function mount($cargo = null)
    {
        $this->date = now()->format('Y-m-d\TH:i');
        $this->warehouses = \App\Models\Warehouse::where('is_active', 1)->get();
        // Default warehouse if exists
        $this->warehouse_id = $this->warehouses->first()->id ?? null;

        if ($cargo) {
            $cargoObj = \App\Models\Cargo::with('details.product')->find($cargo);
            if ($cargoObj && $cargoObj->status == 'pending') {
                $this->cargo_id = $cargoObj->id;
                $this->warehouse_id = $cargoObj->warehouse_id;
                $this->motive = $cargoObj->motive;
                $this->authorized_by = $cargoObj->authorized_by;
                $this->comments = $cargoObj->comments;
                $this->date = $cargoObj->date->format('Y-m-d\TH:i');

                // Pre-fill cart
                foreach ($cargoObj->details as $item) {
                    if ($item->product) {
                        $this->cart[$item->product_id] = [
                            'id' => $item->product_id,
                            'name' => $item->product->name,
                            'sku' => $item->product->sku,
                            'cost' => $item->cost,
                            'quantity' => $item->quantity,
                            'is_variable' => (bool)$item->product->is_variable_quantity,
                            'items' => $item->items_json ? json_decode($item->items_json, true) : []
                        ];
                    }
                }
            }
        }
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

    public function updateQuantity($productId, $cant)
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity'] = $cant;
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

        // Validate Variable Items and Decimals
        foreach ($this->cart as $item) {
            $product = \App\Models\Product::find($item['id']);
            
            if ($product && !$product->allow_decimal) {
                if (floor($item['quantity']) != $item['quantity']) {
                    $this->addError("cart.{$item['id']}.quantity", "El producto {$product->name} no permite decimales.");
                    $this->dispatch('noty', msg: "El producto {$product->name} no permite cantidades decimales.", type: 'error');
                    return;
                }
            }

            if ($item['is_variable'] && empty($item['items'])) {
                $this->addError("cart", "El producto {$item['name']} requiere items.");
                $this->dispatch('noty', msg: "Faltan items en producto {$item['name']}", type: 'error');
                return;
            }
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            if ($this->cargo_id) {
                // Update existing cargo
                $cargo = \App\Models\Cargo::find($this->cargo_id);
                $cargo->update([
                    'warehouse_id' => $this->warehouse_id,
                    'user_id' => auth()->id(),
                    'authorized_by' => $this->authorized_by,
                    'motive' => $this->motive,
                    'date' => $this->date,
                    'comments' => $this->comments,
                ]);

                // Limpiar detalles anteriores
                \App\Models\CargoDetail::where('cargo_id', $cargo->id)->delete();
                $isUpdate = true;
            } else {
                // Create new cargo
                $cargo = \App\Models\Cargo::create([
                    'warehouse_id' => $this->warehouse_id,
                    'user_id' => auth()->id(),
                    'authorized_by' => $this->authorized_by,
                    'motive' => $this->motive,
                    'date' => $this->date,
                    'comments' => $this->comments,
                    'status' => 'pending', 
                ]);
                $isUpdate = false;
            }

            foreach ($this->cart as $item) {
                $detail = \App\Models\CargoDetail::create([
                    'cargo_id' => $cargo->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'cost' => $item['cost'] 
                ]);

                if ($item['is_variable'] && !empty($item['items'])) {
                    $detail->update(['items_json' => json_encode($item['items'])]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            
            if (!$isUpdate) {
                // Dispatch Event only on creation
                event(new \App\Events\CargoCreated($cargo));

                // Send Email Notifications
                $approvers = \App\Models\User::permission('adjustments.approve_cargo')->get();
                \Illuminate\Support\Facades\Notification::send($approvers, new \App\Notifications\CargoCreatedNotification($cargo));
                $this->dispatch('noty', msg: 'Cargo registrado correctamente. Pendiente de aprobación.');
                $this->reset(['cart', 'motive', 'authorized_by', 'comments', 'search']);
            } else {
                $this->dispatch('noty', msg: 'Cargo actualizado correctamente.');
                return redirect()->route('cargos');
            }
            
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
