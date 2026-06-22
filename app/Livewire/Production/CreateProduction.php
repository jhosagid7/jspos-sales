<?php

namespace App\Livewire\Production;

use Livewire\Component;

class CreateProduction extends Component
{

    public $production_date;
    public $note;
    
    public $search;
    public $searchResults = [];
    public $cart = [];
    public $selectedIndex = -1;

    public $warehouse_id;
    public $warehouses = [];

    public $categories = [];
    public $tags = [];
    public $selectedCategory = null;
    public $selectedTag = null;

    public $productionId;
    public $isEdit = false;

    public function mount($production = null)
    {
        $this->warehouses = \App\Models\Warehouse::where('is_active', 1)->get();
        // Default warehouse
        $this->warehouse_id = $this->warehouses->first()->id ?? null;

        $this->categories = \App\Models\Category::orderBy('name')->get();
        $this->tags = \App\Models\Tag::orderBy('name')->get();

        if ($production) {
            $productionModel = \App\Models\Production::find($production);
            if ($productionModel && $productionModel->status == 'pending') {
                $this->productionId = $productionModel->id;
                $this->production_date = $productionModel->production_date->format('Y-m-d');
                $this->note = $productionModel->note;
                $this->isEdit = true;

                foreach ($productionModel->details as $detail) {
                    $this->cart['detail_' . $detail->id] = [
                        'id' => $detail->product_id,
                        'name' => $detail->product->name,
                        'sku' => $detail->product->sku,
                        'quantity' => floatval($detail->quantity),
                        'weight' => floatval($detail->weight),
                        'material_type' => $detail->material_type,
                        'warehouse_id' => $detail->warehouse_id,
                        'is_variable' => (bool)$detail->product->is_variable_quantity,
                        'items' => $detail->metadata ?? [],
                        'production_date' => $detail->production_date ? $detail->production_date->format('Y-m-d') : $productionModel->production_date->format('Y-m-d'),
                        'operator_name' => $detail->operator_name ?? '',
                        'cost' => floatval($detail->product->cost ?? 0)
                    ];
                }
            } else {
                return redirect()->route('production.index');
            }
        } else {
            $this->production_date = now()->format('Y-m-d');
        }
    }

    public function updatedSearch()
    {
        $search = trim($this->search);
        $this->selectedIndex = -1;
        
        if (strlen($search) > 0) {
            $query = \App\Models\Product::query();
            
            // Filter by Category
            if ($this->selectedCategory) {
                $query->where('category_id', $this->selectedCategory);
            }

            // Filter by Tag
            if ($this->selectedTag) {
                $query->whereHas('tags', function($q) {
                    $q->where('tags.id', $this->selectedTag);
                });
            }

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

            // Auto-add if exact SKU match (Barcode Scanning)
            if ($this->searchResults->count() == 1 && $this->searchResults->first()->sku == $search) {
                $this->addToCart($this->searchResults->first()->id);
                $this->search = '';
                $this->searchResults = [];
            }

        } else {
            $this->searchResults = [];
        }
    }

    public function updatedSelectedCategory() { $this->updatedSearch(); }
    public function updatedSelectedTag() { $this->updatedSearch(); }

    public function selectProduct($index)
    {
        if ($index >= 0 && isset($this->searchResults[$index])) {
            $this->addToCart($this->searchResults[$index]->id);
        } elseif (count($this->searchResults) > 0) {
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



    public function updateRow($cartKey, $field, $value)
    {
        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey][$field] = $value;
        }
    }

    // Variable Item Inputs
    public $vw_weight, $vw_color, $vw_batch, $current_cart_key;

    public function addToCart($productId)
    {
        $product = \App\Models\Product::find($productId);
        if (!$product) return;

        // Find if there is an existing cart item for this product
        $existingKey = null;
        foreach ($this->cart as $key => $item) {
            if ($item['id'] == $productId) {
                $existingKey = $key;
                break;
            }
        }

        if ($existingKey !== null) {
            if (!$product->is_variable_quantity) {
                 $this->cart[$existingKey]['quantity']++;
            }
        } else {
            $newKey = 'new_' . uniqid();
            $this->cart[$newKey] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $product->is_variable_quantity ? 0 : 1,
                'weight' => 0,
                'production_date' => $this->production_date ?? now()->format('Y-m-d'),
                'operator_name' => '',
                'material_type' => 'PT', // "Producto Terminado" is too long (limit 10)
                'is_variable' => (bool)$product->is_variable_quantity,
                'items' => [], // To store bobinas
                'cost' => floatval($product->cost ?? 0)
            ];
        }
        
        $this->search = '';
        $this->searchResults = [];
    }

    public function openVariableModal($cartKey)
    {
        $this->current_cart_key = $cartKey;
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

        if (isset($this->cart[$this->current_cart_key])) {
            $this->cart[$this->current_cart_key]['items'][] = [
                'weight' => $this->vw_weight,
                'color' => $this->vw_color,
                'batch' => $this->vw_batch
            ];
            
            // Recalculate total weight/quantity
            $totalWeight = collect($this->cart[$this->current_cart_key]['items'])->sum('weight');
            $this->cart[$this->current_cart_key]['quantity'] = $totalWeight;
            $this->cart[$this->current_cart_key]['weight'] = $totalWeight;
        }

        $this->reset(['vw_weight', 'vw_color', 'vw_batch']);
        $this->dispatch('noty', msg: 'Item agregado a la lista');
        // Keep modal open for faster entry? Or close? Let's keep input cleared and user can close manually or add more.
         $this->dispatch('focus-weight');
    }

    public function removeVariableItem($cartKey, $index)
    {
        if (isset($this->cart[$cartKey]['items'][$index])) {
            unset($this->cart[$cartKey]['items'][$index]);
            // Re-index array
            $this->cart[$cartKey]['items'] = array_values($this->cart[$cartKey]['items']);
            
            // Recalculate
            $totalWeight = collect($this->cart[$cartKey]['items'])->sum('weight');
            $this->cart[$cartKey]['quantity'] = $totalWeight;
            $this->cart[$cartKey]['weight'] = $totalWeight;
        }
    }

    public function removeFromCart($cartKey)
    {
        unset($this->cart[$cartKey]);
    }

    public function save()
    {
        $this->validate([
            'production_date' => 'required|date',
            'warehouse_id' => 'required|exists:warehouses,id',
            'cart' => 'required|array|min:1',
            'cart.*.quantity' => 'required|numeric|min:0.01',
            'cart.*.weight' => 'required|numeric|min:0',
            'cart.*.production_date' => 'required|date',
            'cart.*.operator_name' => 'required|string|max:255',
        ]);

        // Validate Variable Items
        foreach ($this->cart as $item) {
            if ($item['is_variable'] && empty($item['items'])) {
                $this->addError("cart", "El producto {$item['name']} requiere que agregues al menos un item/bobina.");
                $this->dispatch('noty', msg: "Faltan items en producto {$item['name']}", type: 'error');
                return;
            }
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Create/Update Production Record
            if ($this->isEdit) {
                // ... (Edit logic could be complex with stock reversal, for now simplified or assume new for this flow)
                // For safety in this task, let's focus on Creation or simple update
                 $production = \App\Models\Production::find($this->productionId);
                 $production->update([
                    'production_date' => $this->production_date,
                    'note' => $this->note,
                 ]);
                 $production->details()->delete();
            } else {
                $production = \App\Models\Production::create([
                    'user_id' => auth()->id(),
                    'production_date' => $this->production_date,
                    'note' => $this->note,
                    'status' => 'pending' // Correcting to Pending as requested
                ]);
            }

            foreach ($this->cart as $item) {
                // Production Detail with Metadata
                \App\Models\ProductionDetail::create([
                    'production_id' => $production->id,
                    'product_id' => $item['id'],
                    'production_date' => $item['production_date'],
                    'operator_name' => $item['operator_name'],
                    'warehouse_id' => $item['warehouse_id'] ?? $this->warehouse_id, // Per-item or global default
                    'material_type' => $item['material_type'],
                    'quantity' => $item['quantity'],
                    'weight' => $item['weight'],
                    'metadata' => ($item['is_variable'] && !empty($item['items'])) ? $item['items'] : null,
                    'cost' => $item['cost'] ?? 0
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            
            $this->reset(['cart', 'note', 'search']);
            $this->dispatch('noty', msg: 'Producción y Stock registrados correctamente');
            return redirect()->route('production.index');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->dispatch('noty', msg: 'Error al registrar: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.production.create-production');
    }
}
