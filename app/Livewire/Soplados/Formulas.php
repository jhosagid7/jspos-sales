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
    public $selected_id;
    private $pagination = 10;

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

        $this->reset(['ingredient_id', 'quantity']);
        $this->dispatch('msg', 'Receta guardada correctamente');
    }

    public function delete($id)
    {
        ProductionFormula::find($id)->delete();
        $this->dispatch('msg', 'Ingrediente eliminado de la receta');
    }
}
