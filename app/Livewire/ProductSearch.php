<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;

class ProductSearch extends Component
{
    public $search = '';
    public $products = [];
    public $selectedIndex = -1;

    public function updatedSearch()
    {
        $term = trim($this->search);

        if (strlen($term) > 0) {
            $this->products = Product::search($term)
                ->with(['category', 'productWarehouses'])
                ->take(30)
                ->get();
        } else {
            $this->products = [];
            $this->selectedIndex = -1;
        }
    }

    public function selectProduct($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            $this->dispatch('purchase_product', product: $product->id);
            $this->resetSearch();
        }
    }

    public function resetSearch()
    {
        $this->search = '';
        $this->products = [];
        $this->selectedIndex = -1;
        $this->dispatch('clear-search');
    }

    public function render()
    {
        return view('livewire.product-search');
    }
}
