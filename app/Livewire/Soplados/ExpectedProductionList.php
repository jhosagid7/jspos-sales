<?php

namespace App\Livewire\Soplados;

use Livewire\Component;
use App\Models\Product;
use App\Models\SopladosProductionTarget;
use Livewire\WithPagination;

class ExpectedProductionList extends Component
{
    use WithPagination;

    public $product_id, $min_target = 0, $max_target = 0;
    public $search = '';
    public $search_product = '', $product_results = [];
    public $selected_id;
    private $pagination = 10;

    protected $rules = [
        'product_id' => 'required|exists:products,id|unique:soplados_production_targets,product_id',
        'min_target' => 'required|integer|min:0',
        'max_target' => 'required|integer|min:0|gte:min_target',
    ];

    public function getRules()
    {
        $rules = $this->rules;
        if ($this->selected_id) {
            $rules['product_id'] = 'required|exists:products,id|unique:soplados_production_targets,product_id,' . $this->selected_id;
        }
        return $rules;
    }

    public function updatedSearchProduct()
    {
        if (strlen($this->search_product) > 2) {
            $this->product_results = Product::whereHas('tags', function($q) {
                    $q->where('name', 'soplados');
                })
                ->where('is_raw_material', false)
                ->where('name', 'like', '%' . $this->search_product . '%')
                ->take(10)
                ->get();
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

    public function render()
    {
        $targets = SopladosProductionTarget::with('product')
            ->whereHas('product', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($this->pagination);

        return view('livewire.soplados.expected-production-list', [
            'targets' => $targets,
        ])->extends('layouts.theme.app')->section('content');
    }

    public function edit($id)
    {
        $target = SopladosProductionTarget::with('product')->findOrFail($id);
        $this->selected_id = $target->id;
        $this->product_id = $target->product_id;
        $this->search_product = $target->product->name;
        $this->min_target = $target->min_target;
        $this->max_target = $target->max_target;
    }

    public function cancelEdit()
    {
        $this->reset(['selected_id', 'product_id', 'search_product', 'product_results', 'min_target', 'max_target']);
    }

    public function store()
    {
        $this->validate($this->getRules());

        if ($this->selected_id) {
            $target = SopladosProductionTarget::findOrFail($this->selected_id);
            $target->update([
                'product_id' => $this->product_id,
                'min_target' => $this->min_target,
                'max_target' => $this->max_target
            ]);
            $msg = 'Meta de producción actualizada correctamente';
        } else {
            SopladosProductionTarget::create([
                'product_id' => $this->product_id,
                'min_target' => $this->min_target,
                'max_target' => $this->max_target
            ]);
            $msg = 'Meta de producción creada correctamente';
        }

        $this->cancelEdit();
        $this->dispatch('msg', $msg);
    }

    public function delete($id)
    {
        SopladosProductionTarget::find($id)->delete();
        $this->dispatch('msg', 'Meta de producción eliminada');
    }
}
