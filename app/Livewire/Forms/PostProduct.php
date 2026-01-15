<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\Image;
use App\Models\Product;
use App\Models\PriceList;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;

class PostProduct extends Form
{
    //product properties

    //#[Validate('required', message: 'Ingresa el nombre')]
    //#[Validate('max:60', message: 'El nombre debe tener maximo 60 caracteres')]
    //#[Validate('unique:products,name', message: 'El nombre ya existe',  onUpdate: false)]
    //#[Validate('unique:productos,name,' . $this->product_id, message: 'El título debe ser único')]
    public $name, $sku, $description, $type = 'physical', $status = 'available', $cost = 0, $price = 0, $manage_stock = 1, $stock_qty = 0, $low_stock = 0, $category_id = 0, $supplier_id = 0, $product_id = 0, $gallery;
    public $max_stock = 0, $brand, $presentation, $is_pre_assembled = false, $additional_cost = 0, $stock_details = [];

    //properties priceList
    public $value;
    public $values = [];

    //properties suppliers
    public $supplier_cost;
    public $temp_supplier_id;
    public $product_suppliers = [];
    public $product_components = [];



    //reglas de validacion
    public function rules()
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:60',
                Rule::unique('products', 'name')->ignore($this->product_id, 'id')
            ],
            'sku' => [
                'nullable',
                'max:25',
                Rule::unique('products', 'sku')->ignore($this->product_id, 'id'),
            ],
            'description' => [
                'nullable',
                'max:500'
            ],
            'type' => [
                'required',
                'in:service,physical'
            ],
            'status' => [
                'required',
                'in:available,out_of_stock'
            ],
            'cost' => "required",
            'price' => "required",
            'manage_stock' => "nullable",
            'stock_qty' => "required",
            'low_stock' => "required",
            'category_id' => [
                "required",
                Rule::notIn([0])
            ],
            'supplier_id' => [
                "required",
                Rule::notIn([0])
            ],
            'product_suppliers' => 'nullable|array',
            'product_components' => 'nullable|array',
            'is_pre_assembled' => 'nullable|boolean',
            'additional_cost' => 'nullable|numeric|min:0',
        ];
        return $rules;
    }


    public function messages()
    {
        return [
            'name.required' => 'Ingresa el nombre',
            'name.string' => 'El nombre debe ser una cadena de texto',
            'name.unique' => 'El nombre ya existe',
            'name.min' => 'El nombre deber tener al menos 3 caracteres',
            'name.max' => 'El nombre deber tener máximo 60 caracteres',
            'sku.max' => 'El sku debe tener máximo 25 caracteres',
            'description.max' => 'La descripción debe tener máximo 500 caracteres',
            'type.required' => 'Elige el tipo de producto',
            'type.in' => 'Elige el tipo de producto',
            'status.required' => 'Elige el estatus',
            'status.in' => 'Elige un tipo de estatus',
            'stock_qty.required' => 'Ingresa el stock inicial',
            'low_stock.required' => 'Ingresa el stock mínimo',
            'category_id.required' => 'Elige la categoría',
            'category_id.not_in' => 'Elige una categoría',
            'supplier_id.required' => 'Elige el proveedor',
            'supplier_id.not_in' => 'Elige un proveedor',
        ];
    }

    function store()
    {


        $this->validate();

        // Validate Component Stock for Pre-assembled
        if ($this->is_pre_assembled && $this->stock_qty > 0 && !empty($this->product_components)) {
            foreach ($this->product_components as $component) {
                $childProduct = Product::find($component['child_product_id']);
                $requiredQty = $component['quantity'] * $this->stock_qty;
                
                // Check Global Stock
                if ($childProduct->stock_qty < $requiredQty) {
                    $this->addError('stock_qty', "Stock insuficiente de componente: {$childProduct->name}. Requerido: {$requiredQty}, Disponible: {$childProduct->stock_qty}");
                    return;
                }
            }
        }

        $product =  Product::create([
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'cost' => $this->cost,
            'price' => $this->price,
            'manage_stock' => $this->manage_stock ? $this->manage_stock : 1,
            'stock_qty' => $this->stock_qty,
            'low_stock' => $this->low_stock,
            'max_stock' => $this->max_stock,
            'brand' => $this->brand,
            'presentation' => $this->presentation,
            'supplier_id' => $this->supplier_id,
            'category_id' => $this->category_id,
            'is_pre_assembled' => $this->is_pre_assembled ? 1 : 0,
            'additional_cost' => $this->additional_cost
        ]);



        //
        if (!empty($this->gallery)) {

            // guardar imagenes nuevas
            foreach ($this->gallery as $photo) {
                $fileName = uniqid() . '_.' . $photo->extension();
                $photo->storeAs('public/products', $fileName);

                // creamos relacion
                $img = Image::create([
                    'model_id' => $this->product_id,
                    'model_type' => 'App\Models\Product',
                    'file' => $fileName
                ]);

                // guardar relacion
                $product->images()->save($img);
            }
        }

        //lista de precios
        if (session()->has('values')) {
            $data = array_map(function ($value) use ($product) {
                return [
                    'product_id' => $product->id, 
                    'price' => $value['price']
                ];
            }, $this->values);
            PriceList::insert($data);
        }

        // Save Suppliers
        if (!empty($this->product_suppliers)) {
            foreach ($this->product_suppliers as $supplier) {
                \App\Models\ProductSupplier::create([
                    'product_id' => $product->id,
                    'supplier_id' => $supplier['supplier_id'],
                    'cost' => $supplier['cost']
                ]);
            }
        }

        // Save Components
        if (!empty($this->product_components)) {
            $product->components()->attach(
                collect($this->product_components)->mapWithKeys(function ($item) {
                    return [$item['child_product_id'] => ['quantity' => $item['quantity']]];
                })->toArray()
            );
        }

        // Sync Stock to Default Warehouse
        if ($this->manage_stock == 1) {
            $config = \App\Models\Configuration::first();
            $defaultWarehouseId = $config->default_warehouse_id ?? \App\Models\Warehouse::first()->id;
            
            if ($defaultWarehouseId) {
                \App\Models\ProductWarehouse::updateOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $defaultWarehouseId],
                    ['stock_qty' => $this->stock_qty]
                );

                // Deduct Components Stock if Pre-assembled
                if ($this->is_pre_assembled && $this->stock_qty > 0 && !empty($this->product_components)) {
                    foreach ($this->product_components as $component) {
                        $childProduct = Product::find($component['child_product_id']);
                        $qtyToDeduct = $component['quantity'] * $this->stock_qty;
                        
                        // Deduct Global
                        $childProduct->decrement('stock_qty', $qtyToDeduct);
                        
                        // Deduct Warehouse
                        $childPw = \App\Models\ProductWarehouse::where('product_id', $childProduct->id)
                            ->where('warehouse_id', $defaultWarehouseId)
                            ->first();
                        
                        if ($childPw) {
                            $childPw->decrement('stock_qty', $qtyToDeduct);
                        }
                    }
                }
            }
        }



        return $product;
    }


    function update()
    {
        $this->validate();
        
        $product =  Product::find($this->product_id);
        $oldStock = $product->stock_qty; // Capture old stock

        // Validate Component Stock for Pre-assembled (Only if increasing stock)
        if ($this->is_pre_assembled && $this->stock_qty > $oldStock && !empty($this->product_components)) {
            $diff = $this->stock_qty - $oldStock;
            foreach ($this->product_components as $component) {
                $childProduct = Product::find($component['child_product_id']);
                $requiredQty = $component['quantity'] * $diff;
                
                // Check Global Stock
                if ($childProduct->stock_qty < $requiredQty) {
                    $this->addError('stock_qty', "Stock insuficiente de componente: {$childProduct->name}. Requerido para aumento: {$requiredQty}, Disponible: {$childProduct->stock_qty}");
                    return;
                }
            }
        }

        $product->update([
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'cost' => $this->cost,
            'price' => $this->price,
            'manage_stock' => $this->manage_stock,
            'stock_qty' => $this->stock_qty,
            'low_stock' => $this->low_stock,
            'max_stock' => $this->max_stock,
            'brand' => $this->brand,
            'presentation' => $this->presentation,
            'supplier_id' => $this->supplier_id,
            'category_id' => $this->category_id,
            'is_pre_assembled' => $this->is_pre_assembled ? 1 : 0,
            'additional_cost' => $this->additional_cost
        ]);


        if (!empty($this->gallery)) {
            // eliminar imagenes del disco
            if ($this->product_id > 0) {
                $product->images()->each(function ($img) {
                    unlink('storage/products/' . $img->file);
                });

                // eliminar las relaciones
                $product->images()->delete();
            }

            // guardar imagenes nuevas
            foreach ($this->gallery as $photo) {
                $fileName = uniqid() . '_.' . $photo->extension();
                $photo->storeAs('public/products', $fileName);

                // creamos relacion
                $img = Image::create([
                    'model_id' => $this->product_id,
                    'model_type' => 'App\Models\Product',
                    'file' => $fileName
                ]);

                // guardar relacion
                $product->images()->save($img);
            }
        }

        //lista de precios
        if (session()->has('values')) {
            PriceList::where('product_id', $this->product_id)->delete();
            $data = array_map(function ($value) {
                return [
                    'product_id' => $this->product_id, 
                    'price' => $value['price']
                ];
            }, $this->values);
            PriceList::insert($data);
        }

        // Update Suppliers
        \App\Models\ProductSupplier::where('product_id', $this->product_id)->delete();
        if (!empty($this->product_suppliers)) {
            foreach ($this->product_suppliers as $supplier) {
                \App\Models\ProductSupplier::create([
                    'product_id' => $this->product_id,
                    'supplier_id' => $supplier['supplier_id'],
                    'cost' => $supplier['cost']
                ]);
            }
        }

        // Update Components
        $product->components()->detach();
        if (!empty($this->product_components)) {
            $product->components()->attach(
                collect($this->product_components)->mapWithKeys(function ($item) {
                    return [$item['child_product_id'] => ['quantity' => $item['quantity']]];
                })->toArray()
            );
        }

        // Sync Stock to Default Warehouse
        if ($this->manage_stock == 1) {
            $config = \App\Models\Configuration::first();
            $defaultWarehouseId = $config->default_warehouse_id ?? \App\Models\Warehouse::first()->id;
            
            if ($defaultWarehouseId) {
                \App\Models\ProductWarehouse::updateOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $defaultWarehouseId],
                    ['stock_qty' => $this->stock_qty]
                );

                // Adjust Components Stock if Pre-assembled
                if ($this->is_pre_assembled && !empty($this->product_components)) {
                    $diff = $this->stock_qty - $oldStock;
                    
                    if ($diff != 0) {
                        foreach ($this->product_components as $component) {
                            $childProduct = Product::find($component['child_product_id']);
                            $qtyToAdjust = $component['quantity'] * abs($diff);
                            
                            if ($diff > 0) { // Increased Stock -> Deduct Components
                                $childProduct->decrement('stock_qty', $qtyToAdjust);
                                $childPw = \App\Models\ProductWarehouse::where('product_id', $childProduct->id)->where('warehouse_id', $defaultWarehouseId)->first();
                                if ($childPw) $childPw->decrement('stock_qty', $qtyToAdjust);
                            } else { // Decreased Stock -> Return Components
                                $childProduct->increment('stock_qty', $qtyToAdjust);
                                $childPw = \App\Models\ProductWarehouse::where('product_id', $childProduct->id)->where('warehouse_id', $defaultWarehouseId)->first();
                                if ($childPw) {
                                    $childPw->increment('stock_qty', $qtyToAdjust);
                                } else {
                                    \App\Models\ProductWarehouse::create([
                                        'product_id' => $childProduct->id, 
                                        'warehouse_id' => $defaultWarehouseId, 
                                        'stock_qty' => $qtyToAdjust
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }



    }

    function cancel()
    {
        session(['values' => []]);
        $this->values = session('values', []);
        $this->reset();
    }
}
