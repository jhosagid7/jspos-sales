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

    //properties priceList
    public $value;
    public $values = [];

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

        $product =  Product::create([
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'cost' => $this->cost,
            'price' => $this->price,
            'manage_stock' => $this->manage_stock ? $this->manage_stock : 1,
            'stock_qty' => $this->stock_qty,
            'low_stock' => $this->low_stock,
            'supplier_id' => $this->supplier_id,
            'category_id' => $this->category_id
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

            // Prepara los datos para la inserción
            $data = array_map(function ($value) use ($product) {
                return ['product_id' => $product->id, 'price' => $value['price']];
            }, $this->values);

            // Inserta los datos en la tabla
            PriceList::insert($data);
        }

        $this->reset();

        // $this->resetExcept(['product']);

        // $this->product = new Product();
        // $this->product->type = 'service';
        // $this->product->status = 'available';
        // $this->product->manage_stock = 1;

        // $this->product->supplier_id = $this->suppliers->first()->id ?? null;

        //

    }


    function update()
    {
        $this->validate();

        $product =  Product::find($this->product_id);

        $product->update([
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'cost' => $this->cost,
            'price' => $this->price,
            'manage_stock' => $this->manage_stock,
            'stock_qty' => $this->stock_qty,
            'low_stock' => $this->low_stock,
            'supplier_id' => $this->supplier_id,
            'category_id' => $this->category_id
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
            // delete prices
            PriceList::where('product_id', $this->product_id)->delete();

            // Prepara los datos para la inserción
            $data = array_map(function ($value) {
                return ['product_id' => $this->product_id, 'price' => $value['price']];
            }, $this->values);

            // Inserta los datos en la tabla
            PriceList::insert($data);
        }

        $this->reset();
    }

    function cancel()
    {
        session(['values' => []]);
        $this->values = session('values', []);
        $this->reset();
    }
}
