<?php

namespace App\Livewire;

use Livewire\Component;


use App\Models\Product;
use Livewire\WithPagination;

class LabelGenerator extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedProducts = []; // [id => ['name' => '...', 'qty' => 1]]

    public function render()
    {
        $products = [];
        if (strlen($this->search) > 0) {
            $products = Product::where('name', 'like', '%' . $this->search . '%')
                ->orWhere('sku', 'like', '%' . $this->search . '%')
                ->orWhereHas('category', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('tags', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })
                ->take(10)
                ->get();
        }

        return view('livewire.label-generator', [
            'products' => $products
        ])->extends('layouts.theme.app')
          ->section('content');
    }

    public function addProduct($productId)
    {
        $product = Product::find($productId);
        if ($product && !isset($this->selectedProducts[$productId])) {
            $this->selectedProducts[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->sku, // Map sku to barcode key for compatibility with view
                'qty' => 1
            ];
        }
    }

    public function removeProduct($productId)
    {
        unset($this->selectedProducts[$productId]);
    }

    public function updateQuantity($productId, $qty)
    {
        if (isset($this->selectedProducts[$productId])) {
            $this->selectedProducts[$productId]['qty'] = max(1, intval($qty));
        }
    }

    public function generatePdf()
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('msg-error', 'Debe seleccionar al menos un producto');
            return;
        }
        
        // Logic to redirect to PDF generation route with selected data
        // We can pass IDs and quantities via query string or session
        // Session is cleaner for larger datasets
        session(['label_products' => $this->selectedProducts]);
        
        $this->dispatch('open-new-tab', route('labels.pdf'));
    }
}
