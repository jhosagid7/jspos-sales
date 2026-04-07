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
    public $selectedIndex = -1;
    public $primaryVat = 0;
    public $canApproveDescargo = false;
    public $canCreateDescargo = false;
    
    public $warehouses = [];
    public $cart = [];
    public $descargo_id = null;

    public function mount($descargo = null)
    {
        $this->date = now()->format('Y-m-d\TH:i');

        // Cache Permissions
        $user = auth()->user();
        $this->canApproveDescargo = $user->can('adjustments.approve_descargo');
        $this->canCreateDescargo = $user->can('adjustments.create_descargo');

        // Cache Configuration
        $config = \App\Services\ConfigurationService::getConfig();
        $this->primaryVat = $config?->vat ?? 0;

        $this->warehouses = \App\Models\Warehouse::where('is_active', 1)->get();
        // Default warehouse if exists
        $this->warehouse_id = $this->warehouses->first()->id ?? null;

        // If editing an existing descargo
        if ($descargo) {
            $descargoObj = \App\Models\Descargo::with('details.product')->find($descargo);
            if ($descargoObj && $descargoObj->status == 'pending') {
                $this->descargo_id = $descargoObj->id;
                $this->warehouse_id = $descargoObj->warehouse_id;
                $this->motive = $descargoObj->motive;
                $this->authorized_by = $descargoObj->authorized_by;
                $this->comments = $descargoObj->comments;
                $this->date = $descargoObj->date->format('Y-m-d\TH:i');

                // Pre-fill cart
                foreach ($descargoObj->details as $item) {
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

        // Check for cloning via query parameter
        if (request()->has('clone_id')) {
            $this->loadFromDescargo(request('clone_id'));
        }
    }

    public function loadFromDescargo($id)
    {
        $descargo = \App\Models\Descargo::with('details.product')->find($id);

        if ($descargo) {
            $this->cart = [];
            $this->motive = $descargo->motive;
            $this->authorized_by = $descargo->authorized_by;
            $this->comments = "Clonado desde Descargo #{$descargo->id}. " . $descargo->comments;
            $this->warehouse_id = $descargo->warehouse_id;

            // Important: We do NOT set $this->descargo_id because this is CLONING (new record)
            $this->descargo_id = null;

            foreach ($descargo->details as $detail) {
                if ($detail->product) {
                    $this->cart[$detail->product_id] = [
                        'id' => $detail->product_id,
                        'name' => $detail->product->name,
                        'sku' => $detail->product->sku,
                        'cost' => $detail->cost,
                        'quantity' => $detail->quantity,
                        'is_variable' => (bool)$detail->product->is_variable_quantity,
                        'items' => $detail->items_json ? json_decode($detail->items_json, true) : []
                    ];
                }
            }
            $this->dispatch('noty', msg: "Descargo #{$id} cargado (Modo Clonación)");
        } else {
            $this->dispatch('noty', msg: "No se encontró el descargo #{$id}", type: 'error');
        }
    }

    public function processCloningCode($code)
    {
        $code = strtoupper(trim($code));
        
        // Flexible Regex: DESCARGO | SALIDA
        if (preg_match('/^(DESCARGO|SALIDA)[^0-9]*([0-9]+)$/i', $code, $matches)) {
            $id = $matches[2];
            $this->loadFromDescargo($id);
            $this->search = '';
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

        // Validate items
        foreach ($this->cart as $item) {
            $product = \App\Models\Product::find($item['id']);
            if ($product && !$product->allow_decimal) {
                if (floor($item['quantity']) != $item['quantity']) {
                    $this->addError("cart", "El producto {$product->name} no permite decimales.");
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

            if ($this->descargo_id) {
                // UPDATE MODE
                $descargo = \App\Models\Descargo::find($this->descargo_id);
                if (!$descargo || $descargo->status !== 'pending') {
                    throw new \Exception("No se puede editar un descargo que ya no es pendiente o no existe.");
                }

                $descargo->update([
                    'warehouse_id' => $this->warehouse_id,
                    'authorized_by' => $this->authorized_by,
                    'motive' => $this->motive,
                    'date' => $this->date,
                    'comments' => $this->comments,
                ]);

                // Clear old details
                $descargo->details()->delete();
                $msg = 'Descargo actualizado correctamente.';
            } else {
                // CREATE MODE
                $descargo = \App\Models\Descargo::create([
                    'warehouse_id' => $this->warehouse_id,
                    'user_id' => auth()->id(),
                    'authorized_by' => $this->authorized_by,
                    'motive' => $this->motive,
                    'date' => $this->date,
                    'comments' => $this->comments,
                    'status' => 'pending',
                ]);
                $msg = 'Descargo registrado. Pendiente de aprobación.';
            }

            foreach ($this->cart as $item) {
                $detail = \App\Models\DescargoDetail::create([
                    'descargo_id' => $descargo->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'cost' => $item['cost']
                ]);

                if ($item['is_variable'] && !empty($item['items'])) {
                    $detail->update(['items_json' => json_encode($item['items'])]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            
            // Dispatch Events and Notifications (Only for new records if preferred, or both)
            if (!$this->descargo_id) {
                event(new \App\Events\DescargoCreated($descargo));
                $approvers = \App\Models\User::permission('adjustments.approve_descargo')->get();
                \Illuminate\Support\Facades\Notification::send($approvers, new \App\Notifications\DescargoCreatedNotification($descargo));
            }

            if ($this->descargo_id) {
                return redirect()->route('descargos')->with('success', $msg);
            }

            $this->reset(['cart', 'motive', 'authorized_by', 'comments', 'search', 'descargo_id']);
            $this->dispatch('noty', msg: $msg);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->dispatch('noty', msg: 'Error al procesar descargo: ' . $e->getMessage(), type: 'error');
        }
    }

    #[Layout('layouts.theme.app')]
    public function render()
    {
        return view('livewire.descargos.create-descargo');
    }
}
