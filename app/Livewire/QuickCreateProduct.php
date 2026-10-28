<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\PriceGroup;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use App\Livewire\Forms\PostProduct;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class QuickCreateProduct extends Component
{
    use WithFileUploads;

    public PostProduct $form;

    public $tab = 1;
    public $categories = [];
    public $suppliers = [];
    public $priceGroups = [];

    // Auxiliary quick creation (category / supplier)
    public $btnCreateCategory = false;
    public $btnCreateSupplier = false;
    public $catalogueName = '';

    // Auxiliary price / tier / supplier inputs
    public $tempPrice = '';
    public $tempTierMinQty = '';
    public $tempTierPrice = '';
    public $tempSupplierId = 0;
    public $tempSupplierCost = '';

    public function mount()
    {
        $this->form->cancel();
        $this->loadCatalogues();
    }

    public function loadCatalogues()
    {
        $this->categories = Category::orderBy('name')->get();
        $this->suppliers = Supplier::orderBy('name')->get();
        $this->priceGroups = PriceGroup::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.quick-create-product');
    }

    public function setTab($tabNumber)
    {
        $this->tab = $tabNumber;
    }

    public function resetForm()
    {
        $this->form->cancel();
        $this->tab = 1;
        $this->reset(['tempPrice', 'tempTierMinQty', 'tempTierPrice', 'tempSupplierId', 'tempSupplierCost', 'catalogueName']);
        $this->resetErrorBag();
        $this->loadCatalogues();
    }

    public function store()
    {
        $this->authorize('products.create');

        try {
            $this->resetErrorBag();

            // Set default type if empty
            if (empty($this->form->type)) {
                $this->form->type = 'physical';
            }
            if (empty($this->form->status)) {
                $this->form->status = 'available';
            }

            $product = $this->form->store();

            if (!$product || !isset($product->id)) {
                return;
            }

            $this->dispatch('product-created', product: $product->id, name: $product->name);
            $this->dispatch('noty', msg: "PRODUCTO {$product->name} CREADO EXITOSAMENTE");
            $this->dispatch('close-modal-create-product');

            $this->resetForm();

        } catch (\Illuminate\Validation\ValidationException $e) {
            // If validation errors are on other tabs, navigate user to the relevant tab
            $errors = $e->validator->errors()->keys();
            if (in_array('name', $errors) || in_array('cost', $errors) || in_array('price', $errors) || in_array('sku', $errors) || in_array('type', $errors)) {
                $this->tab = 1;
            } elseif (in_array('category_id', $errors) || in_array('supplier_id', $errors)) {
                $this->tab = 3;
            } elseif (in_array('stock_qty', $errors) || in_array('low_stock', $errors)) {
                $this->tab = 5;
            }
            throw $e;
        } catch (\Throwable $th) {
            $this->dispatch('noty', msg: "Error al crear el producto: {$th->getMessage()}");
        }
    }

    // Secondary price list management
    public function storeTempPrice()
    {
        $cleanPrice = str_replace(',', '.', trim(strval($this->tempPrice)));

        $validator = validator(
            ['price' => $cleanPrice],
            ['price' => ['required', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,4})?$/']]
        );

        if ($validator->fails()) {
            $this->dispatch('noty', msg: 'El precio debe ser un número positivo con máximo 4 decimales');
            return;
        }

        $priceVal = (float)$cleanPrice;
        $existing = array_map(function ($item) {
            return (float)($item['price'] ?? 0);
        }, $this->form->values);

        if (!in_array($priceVal, $existing)) {
            $this->form->values[] = [
                'id' => Str::uuid()->toString(),
                'price' => $priceVal
            ];
            $this->tempPrice = '';
            $this->dispatch('noty', msg: 'Precio secundario agregado');
        } else {
            $this->dispatch('noty', msg: 'Ese precio ya existe en la lista');
        }
    }

    public function removeTempPrice($id)
    {
        $this->form->values = array_values(array_filter($this->form->values, function ($item) use ($id) {
            return ($item['id'] ?? null) !== $id;
        }));
        $this->dispatch('noty', msg: 'Precio secundario eliminado');
    }

    // Pricing tiers management (escala por volumen)
    public function addPricingTier()
    {
        $minQty = (float)$this->tempTierMinQty;
        $tierPrice = (float)$this->tempTierPrice;

        if ($minQty <= 0 || $tierPrice <= 0) {
            $this->dispatch('noty', msg: 'Ingrese cantidad mínima y precio válidos para la escala');
            return;
        }

        $tiers = $this->form->pricing_tiers ?? [];
        $tiers[] = [
            'min_qty' => $minQty,
            'price' => $tierPrice
        ];

        // Sort ascending by min_qty
        usort($tiers, fn($a, $b) => $a['min_qty'] <=> $b['min_qty']);
        $this->form->pricing_tiers = $tiers;

        $this->tempTierMinQty = '';
        $this->tempTierPrice = '';
        $this->dispatch('noty', msg: 'Escala por volumen agregada');
    }

    public function removePricingTier($index)
    {
        unset($this->form->pricing_tiers[$index]);
        $this->form->pricing_tiers = array_values($this->form->pricing_tiers);
        $this->dispatch('noty', msg: 'Escala eliminada');
    }

    // Additional suppliers management
    public function addSupplier()
    {
        if (empty($this->tempSupplierId) || $this->tempSupplierId == 0) {
            $this->dispatch('noty', msg: 'Seleccione un proveedor');
            return;
        }

        if (!is_numeric($this->tempSupplierCost) || $this->tempSupplierCost < 0) {
            $this->dispatch('noty', msg: 'Ingrese un costo válido');
            return;
        }

        $exists = collect($this->form->product_suppliers)->contains('supplier_id', $this->tempSupplierId);
        if ($exists) {
            $this->dispatch('noty', msg: 'Este proveedor ya está en la lista');
            return;
        }

        $supplier = Supplier::find($this->tempSupplierId);
        if ($supplier) {
            $this->form->product_suppliers[] = [
                'supplier_id' => $supplier->id,
                'name' => $supplier->name,
                'cost' => (float)$this->tempSupplierCost
            ];
            $this->tempSupplierId = 0;
            $this->tempSupplierCost = '';
            $this->dispatch('noty', msg: 'Proveedor adicional agregado');
        }
    }

    public function removeSupplier($index)
    {
        unset($this->form->product_suppliers[$index]);
        $this->form->product_suppliers = array_values($this->form->product_suppliers);
        $this->dispatch('noty', msg: 'Proveedor adicional removido');
    }

    // Quick Catalogue Modal (create category or supplier inline)
    public function openCatalogueModal($type)
    {
        if ($type === 'category') {
            $this->btnCreateCategory = true;
            $this->btnCreateSupplier = false;
        } else {
            $this->btnCreateCategory = false;
            $this->btnCreateSupplier = true;
        }
        $this->catalogueName = '';
        $this->dispatch('show-quick-catalogue-modal');
    }

    public function saveCatalogue()
    {
        $name = trim($this->catalogueName);
        if (empty($name)) {
            $this->dispatch('noty', msg: 'Ingrese el nombre');
            return;
        }

        if ($this->btnCreateCategory) {
            $cat = Category::firstOrCreate(['name' => $name]);
            $this->categories = Category::orderBy('name')->get();
            $this->form->category_id = $cat->id;
            $this->dispatch('noty', msg: "Categoría '{$cat->name}' creada");
        } else {
            $sup = Supplier::firstOrCreate(['name' => $name]);
            $this->suppliers = Supplier::orderBy('name')->get();
            $this->form->supplier_id = $sup->id;
            $this->dispatch('noty', msg: "Proveedor '{$sup->name}' creado");
        }

        $this->catalogueName = '';
        $this->dispatch('close-quick-catalogue-modal');
    }
}
