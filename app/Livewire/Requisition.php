<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Supplier;

class Requisition extends Component
{
    use WithPagination;

    public $supplier_id;
    public $selected = [];
    public $pageTitle, $componentName;
    public $showAll = false;
    public $search = ''; // New search property
    private $pagination = 10;

    public function mount()
    {
        $this->pageTitle = 'Requisición';
        $this->componentName = 'Compras Sugeridas';
        $this->supplier_id = '';
        $this->selected = [];
        $this->showAll = false;
        $this->search = '';
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // ... createOrder method remains unchanged ...

    public function render()
    {
        $products = Product::query()
            ->when(!$this->showAll, function($q) {
                $q->whereColumn('stock_qty', '<', 'max_stock');
            })
            ->when($this->supplier_id, function($q) {
                $q->whereHas('productSuppliers', function($q2) {
                    $q2->where('supplier_id', $this->supplier_id);
                });
            })
            ->when(strlen($this->search) > 0, function($q) {
                $searchTerms = explode(' ', $this->search);
                $q->where(function($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query->where('name', 'like', '%' . $term . '%');
                    }
                })->orWhere('sku', 'like', '%' . $this->search . '%');
            })
            ->with(['productSuppliers.supplier'])
            ->paginate($this->pagination);

        $suppliers = Supplier::orderBy('name', 'asc')->get();

        return view('livewire.requisition', [
            'data' => $products,
            'suppliers' => $suppliers
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }
}
