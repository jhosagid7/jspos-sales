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

    public $categories = [];
    public $tags = [];
    public $selectedCategory = null;
    public $selectedTag = null;

    public $productionId;
    public $isEdit = false;

    public function mount($production = null)
    {
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
                    $this->cart[$detail->product_id] = [
                        'id' => $detail->product_id,
                        'name' => $detail->product->name,
                        'sku' => $detail->product->sku,
                        'quantity' => floatval($detail->quantity),
                        'weight' => floatval($detail->weight),
                        'material_type' => $detail->material_type
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

    public function addToCart($productId)
    {
        $product = \App\Models\Product::find($productId);
        if (!$product) return;

        if (isset($this->cart[$productId])) {
            $this->dispatch('noty', msg: 'El producto ya está en la lista', type: 'warning');
        } else {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => 1,
                'weight' => 0,
                'material_type' => ''
            ];
        }
        
        $this->search = '';
        $this->searchResults = [];
    }

    public function updateRow($productId, $field, $value)
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId][$field] = $value;
        }
    }

    public function removeFromCart($productId)
    {
        unset($this->cart[$productId]);
    }

    public function save()
    {
        $this->validate([
            'production_date' => 'required|date',
            'cart' => 'required|array|min:1',
            'cart.*.quantity' => 'required|numeric|min:0.01',
            'cart.*.weight' => 'required|numeric|min:0',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            if ($this->isEdit) {
                $production = \App\Models\Production::find($this->productionId);
                $production->update([
                    'production_date' => $this->production_date,
                    'note' => $this->note,
                ]);
                
                // Delete old details
                $production->details()->delete();
                
            } else {
                $production = \App\Models\Production::create([
                    'user_id' => auth()->id(),
                    'production_date' => $this->production_date,
                    'note' => $this->note,
                    'status' => 'pending'
                ]);
            }

            foreach ($this->cart as $item) {
                \App\Models\ProductionDetail::create([
                    'production_id' => $production->id,
                    'product_id' => $item['id'],
                    'material_type' => $item['material_type'],
                    'quantity' => $item['quantity'],
                    'weight' => $item['weight']
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            
            $this->reset(['cart', 'note', 'search']);
            $msg = $this->isEdit ? 'Producción actualizada correctamente' : 'Producción registrada correctamente';
            $this->dispatch('noty', msg: $msg);
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
