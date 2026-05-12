<?php

namespace App\Livewire\Soplados;

use Livewire\Component;
use App\Models\Product;
use App\Models\ProductionFormula;
use Livewire\WithPagination;

class Formulas extends Component
{
    use WithPagination;

    public $product_id, $ingredient_id, $quantity = 1;
    public $search = '';
    public $search_product = '', $product_results = [];
    public $search_ingredient = '', $ingredient_results = [];
    public $selected_id;
    private $pagination = 10;

    public function updatedSearchProduct()
    {
        if (strlen($this->search_product) > 2) {
            $this->product_results = Product::search($this->search_product)->take(10)->get();
        } else {
            $this->product_results = [];
        }
    }

    public function selectProduct($id, $name)
    {
        $this->product_id = $id;
        $this->search_product = $name;
        $this->product_results = [];
    }

    public function updatedSearchIngredient()
    {
        if (strlen($this->search_ingredient) > 2) {
            $this->ingredient_results = Product::search($this->search_ingredient)->take(10)->get();
        } else {
            $this->ingredient_results = [];
        }
    }

    public function selectIngredient($id, $name)
    {
        $this->ingredient_id = $id;
        $this->search_ingredient = $name;
        $this->ingredient_results = [];
    }

    protected $rules = [
        'product_id' => 'required|exists:products,id',
        'ingredient_id' => 'required|exists:products,id|different:product_id',
        'quantity' => 'required|numeric|min:0.0001',
    ];

    public function render()
    {
        $formulas = ProductionFormula::with(['product', 'ingredient'])
            ->whereHas('product', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->orWhereHas('ingredient', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('product_id')
            ->paginate($this->pagination);

        $products = Product::orderBy('name')->get();

        return view('livewire.soplados.formulas', [
            'formulas' => $formulas,
            'products' => $products
        ])->extends('layouts.theme.app')->section('content');
    }

    public function store()
    {
        $this->validate();

        ProductionFormula::updateOrCreate(
            ['product_id' => $this->product_id, 'ingredient_id' => $this->ingredient_id],
            ['quantity' => $this->quantity]
        );

        // Auto-tag the finished product so it appears in the Soplados App
        $product = Product::find($this->product_id);
        if ($product) {
            $tag = \App\Models\Tag::firstOrCreate(['name' => 'soplados']);
            if (!$product->tags()->where('tags.id', $tag->id)->exists()) {
                $product->tags()->attach($tag->id);
            }
        }

        $this->reset(['ingredient_id', 'quantity', 'search_ingredient', 'ingredient_results']);
        $this->dispatch('msg', 'Receta guardada correctamente y producto etiquetado como Soplados');
    }

    public function delete($id)
    {
        ProductionFormula::find($id)->delete();
        $this->dispatch('msg', 'Ingrediente eliminado de la receta');
    }
}
