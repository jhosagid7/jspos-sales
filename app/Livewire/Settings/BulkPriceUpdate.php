<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class BulkPriceUpdate extends Component
{
    public $categories = [];
    public $suppliers = [];
    
    public $selected_category = '';
    public $selected_supplier = '';
    
    public $target_price = 'price'; // 'cost' or 'price'
    
    public $percentage = 0;
    
    public $affected_count = 0;
    public $confirming = false;

    public function mount()
    {
        $this->categories = Category::orderBy('name')->get();
        // Solo cargar proveedores si el modulo esta habilitado
        if (config('tenant.modules.module_purchases', true)) {
            $this->suppliers = Supplier::orderBy('name')->get();
        }
    }

    public function render()
    {
        // Calcular cuentos productos se van a afectar en vivo
        $query = Product::where('status', '!=', 'deleted');
        
        if (!empty($this->selected_category)) {
            $query->where('category_id', $this->selected_category);
        }
        
        if (!empty($this->selected_supplier)) {
            $query->where('supplier_id', $this->selected_supplier);
        }

        $this->affected_count = $query->count();

        return view('livewire.settings.bulk-price-update');
    }

    public function previewUpdate()
    {
        $this->validate([
            'percentage' => 'required|numeric|not_in:0',
            'target_price' => 'required|in:cost,price',
        ]);

        if (empty($this->selected_category) && empty($this->selected_supplier)) {
            $this->dispatch('noty', msg: 'Debes seleccionar al menos una Categoría o Proveedor para aplicar el cambio.', type: 'error');
            return;
        }

        if ($this->affected_count == 0) {
            $this->dispatch('noty', msg: 'No hay productos bajo estos filtros.', type: 'error');
            return;
        }

        $this->confirming = true;
    }

    public function applyUpdate()
    {
        if (!$this->confirming) return;

        DB::beginTransaction();

        try {
            $query = Product::where('status', '!=', 'deleted');
            
            if (!empty($this->selected_category)) {
                $query->where('category_id', $this->selected_category);
            }
            
            if (!empty($this->selected_supplier)) {
                $query->where('supplier_id', $this->selected_supplier);
            }

            $products = $query->get();
            $factor = 1 + ($this->percentage / 100);

            foreach ($products as $product) {
                if ($this->target_price == 'cost') {
                    $newCost = $product->cost * $factor;
                    $product->cost = $newCost;
                } else {
                    $newPrice = $product->price * $factor;
                    $product->price = $newPrice;
                }
                $product->save();
            }

            DB::commit();

            $this->dispatch('noty', msg: "¡Se actualizaron {$this->affected_count} productos existosamente!");
            
            // Reestablecer formulario
            $this->reset(['percentage', 'confirming', 'selected_category', 'selected_supplier']);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al actualizar precios: " . $e->getMessage(), type: 'error');
            $this->confirming = false;
        }
    }
    
    public function cancelUpdate()
    {
        $this->confirming = false;
    }
}
