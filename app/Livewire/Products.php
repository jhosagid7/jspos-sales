<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Livewire\Forms\PostProduct;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class Products extends Component
{
    use WithFileUploads;
    use WithPagination;

    // form validation
    public PostProduct $form;

    //operational properties
    public $search, $editing, $tab = 1, $categories, $suppliers, $btnCreateCategory = false, $btnCreateSupplier = false, $catalogueName, $pagination = 6;
    public $search_component = '', $component_search_results = [];




    public function mount()
    {

        $this->editing = false;

        session(['map' => 'Productos', 'child' => ' Componente ']);

        $this->categories = Category::orderBy('name')->get();

        $this->suppliers = Supplier::orderBy('name')->get();
    }


    public function render()
    {
        $this->form->values = session('values', []);

        return view('livewire.products.products', [
            'products' => $this->getProducts()
        ]);
    }


    //methods
    function getProducts()
    {
        //php artisan config:cache

        try {
            if (!empty($this->search)) {

                $this->resetPage();

                return Product::search(trim($this->search))->orderBy('id')->paginate($this->pagination);
            } else {
                return Product::with(['category', 'supplier', 'priceList'])->orderBy('id')->paginate($this->pagination);
            }
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al buscar el producto: {$th->getMessage()}");
        }
    }


    function addNew()
    {
        $this->form->cancel();
        $this->editing = true;
    }



    function Edit(Product $product)
    {
        $this->editing = true;
        $this->form->product_id = $product->id;
        $this->form->name = $product->name;
        $this->form->sku = $product->sku;
        $this->form->description = $product->description;
        $this->form->cost = $product->cost;
        $this->form->price = $product->price;
        $this->form->manage_stock = $product->manage_stock;
        $this->form->stock_qty = $product->stock_qty;
        $this->form->low_stock = $product->low_stock;
        $this->form->max_stock = $product->max_stock;
        $this->form->brand = $product->brand;
        $this->form->presentation = $product->presentation;
        $this->form->supplier_id = $product->supplier_id;
        $this->form->category_id = $product->category_id;
        $this->form->is_pre_assembled = (bool) $product->is_pre_assembled;
        $this->form->additional_cost = $product->additional_cost;
        $this->form->values = $product->priceList->toArray();

        // Load suppliers
        $this->form->product_suppliers = $product->productSuppliers->map(function($ps) {
            return [
                'supplier_id' => $ps->supplier_id,
                'name' => $ps->supplier->name,
                'cost' => $ps->cost
            ];
        })->toArray();
        
        // Load components
        $this->form->product_components = $product->components->map(function($child) {
            return [
                'child_product_id' => $child->id,
                'name' => $child->name,
                'quantity' => $child->pivot->quantity
            ];
        })->toArray();

        // Load Stock Details
        $warehouses = \App\Models\Warehouse::all();
        $this->form->stock_details = $warehouses->map(function($warehouse) use ($product) {
            $stock = $product->warehouses()->where('warehouse_id', $warehouse->id)->first()->pivot->stock_qty ?? 0;
            return [
                'warehouse_name' => $warehouse->name,
                'stock' => $stock
            ];
        })->toArray();


        $this->editing = true;

        session(['values' => $product->priceList->toArray()]);
        $this->dispatch('update-quill-content', content: $product->description);
    }


    function cancel()
    {
        $this->editing = false;
    }


    function modal($type)
    {
        if ($type == 'category') {
            $this->btnCreateSupplier = false;
            $this->btnCreateCategory = true;
        } else {
            $this->btnCreateSupplier = true;
            $this->btnCreateCategory = false;
        }
        $this->dispatch('modalCatalogue');
    }


    function createCatalogue()
    {
        if (empty($this->catalogueName)) {
            $this->dispatch('error', msg: 'Ingresa el nombre ' . $this->btnCreateSupplier ? ' del Proveedor' : ' de la Categoría');
            return;
        }

        //create supplier
        if ($this->btnCreateSupplier) {
            $sup = Supplier::create([
                'name' => $this->catalogueName
            ]);

            $this->suppliers = Supplier::orderBy('name')->get();
            $this->form->supplier_id =    $sup->id;
        }
        //create category
        else {
            $cat = Category::create([
                'name' => $this->catalogueName
            ]);
            $this->categories = Category::orderBy('name')->get();
            $this->form->category_id =  $cat->id; //$this->categories->last()->id;
        }
        $this->reset('catalogueName');
        $this->dispatch('close-modal');
        $this->dispatch('noty', msg: $this->btnCreateSupplier ? 'Proveedor registrado' : 'Categoría agregada');
    }



    public function storeTempPrice()
    {
        // validar que el valor sea un número positivo con un máximo de un decimal
        $validator = validator(
            ['price' => $this->form->value],
            ['price' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{2})?$/']]
        );

        if ($validator->fails()) {
            $this->form->value = '';
            $this->dispatch('noty', msg: '¡El valor debe ser un número positivo con un máximo de dos decimal!');
            return;
        }


        // validar que el valor no esté repetido
        if (!in_array($this->form->value, array_column($this->form->values, 'price'))) {
            $newId = Str::uuid()->toString();
            $this->form->values[] = [
                'id' => $newId, 
                'price' => $this->form->value
            ];
            $this->form->value = ''; // limpiar property después de agregar
            session(['values' => $this->form->values]); // Guardar los valores en sesión
            $this->dispatch('noty', msg: 'Precio agregado correctamente');
        } else {
            $this->dispatch('noty', msg: '¡El precio ya existe!');
        }
        // $this->tab = 4;
    }

    public function removeTempPrice($id)
    {
        $this->form->values = array_values(array_filter($this->form->values, function ($item) use ($id) {
            return $item['id'] !== $id;
        }));

        // actualizar los valores en sesión después de eliminar
        session(['values' => $this->form->values]);
        $this->dispatch('noty', msg: 'Precio eliminado correctamente');

        // $this->tab = 4;
    }

    public function addSupplier()
    {
        $this->validate([
            'form.temp_supplier_id' => 'required|not_in:0',
            'form.supplier_cost' => 'required|numeric|min:0'
        ]);

        // Check if supplier already exists in the list
        $exists = collect($this->form->product_suppliers)->contains('supplier_id', $this->form->temp_supplier_id);

        if ($exists) {
            $this->dispatch('noty', msg: 'El proveedor ya está agregado a la lista');
            return;
        }

        $supplier = Supplier::find($this->form->temp_supplier_id);

        $this->form->product_suppliers[] = [
            'supplier_id' => $this->form->temp_supplier_id,
            'name' => $supplier->name,
            'cost' => $this->form->supplier_cost
        ];

        $this->form->supplier_cost = '';
        $this->form->temp_supplier_id = 0; // Reset selection
        $this->dispatch('noty', msg: 'Proveedor agregado correctamente');
    }

    public function removeSupplier($index)
    {
        unset($this->form->product_suppliers[$index]);
        $this->form->product_suppliers = array_values($this->form->product_suppliers);
        $this->dispatch('noty', msg: 'Proveedor eliminado correctamente');
        $this->dispatch('noty', msg: 'Proveedor eliminado correctamente');
    }

    public function updatedSearchComponent()
    {
        if (strlen($this->search_component) > 2) {
            $this->component_search_results = Product::where('name', 'like', "%{$this->search_component}%")
                ->orWhere('sku', 'like', "%{$this->search_component}%")
                ->take(10)
                ->get();
        } else {
            $this->component_search_results = [];
        }
    }

    public function addComponent($productId, $name)
    {
        // Check if already exists
        $exists = collect($this->form->product_components)->contains('child_product_id', $productId);
        if ($exists) {
            $this->dispatch('noty', msg: 'El componente ya está en la lista');
            return;
        }

        // Prevent adding itself
        if ($this->form->product_id == $productId && $this->form->product_id != 0) {
            $this->dispatch('noty', msg: 'No puedes agregar el mismo producto como componente');
            return;
        }

        $this->form->product_components[] = [
            'child_product_id' => $productId,
            'name' => $name,
            'quantity' => 1
        ];

        $this->search_component = '';
        $this->component_search_results = [];
        $this->calculateCompositeCost();
        $this->dispatch('noty', msg: 'Componente agregado');
    }

    public function removeComponent($index)
    {
        unset($this->form->product_components[$index]);
        $this->form->product_components = array_values($this->form->product_components);
        $this->calculateCompositeCost();
        $this->dispatch('noty', msg: 'Componente eliminado');
    }

    public function updateComponentQty($index, $qty)
    {
        if ($qty > 0) {
            $this->form->product_components[$index]['quantity'] = $qty;
            $this->calculateCompositeCost();
        }
    }

    public function calculateCompositeCost()
    {
        $totalCost = 0;
        foreach ($this->form->product_components as $component) {
            $childProduct = Product::find($component['child_product_id']);
            if ($childProduct) {
                $totalCost += $childProduct->cost * $component['quantity'];
            }
        }
        
        $totalCost += floatval($this->form->additional_cost);
        $this->form->cost = round($totalCost, 2);
    }

    public function updatedFormAdditionalCost()
    {
        $this->calculateCompositeCost();
    }




    function Store()
    {
        try {
            $this->resetErrorBag();
            $product = $this->form->store();

            $this->dispatch('noty', msg: 'PRODUCTO CREADO');
            // Stay on form, switch to edit mode
            $this->Edit($product);

            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar crear el producto \n  {$th->getMessage()} ");
        }
    }

    function Update()
    {
        // dd($this->form);
        try {
            $this->resetErrorBag();

            $this->form->update();

            $this->dispatch('noty', msg: 'PRODUCTO ACTUALIZADO');

            // $this->editing = false;

            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar actualizar el producto \n  {$th->getMessage()} ");
        }
    }


    #[On('quilContent')]
    public function setDescription($content)
    {
        $this->form->description = $content;
    }


    #[On('Destroy')]
    public function Destroy($id)
    {
        try {
            $product = Product::find($id);

            if ($product) {

                // delete all images
                $product->images()->each(function ($img) {
                    unlink('storage/products/' . $img->file);
                });

                // eliminar las relaciones
                $product->images()->delete();


                // delete from db
                $product->delete();

                $this->resetPage();


                $this->dispatch('noty', msg: 'PRODUCTO ELIMINADO CORRECTAMENTE');
            }
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar eliminar el producto \n {$th->getMessage()}");
        }
    }
}
