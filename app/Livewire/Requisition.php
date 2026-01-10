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

    public function createOrder()
    {
        if (empty($this->selected)) {
            $this->dispatch('noty', msg: 'Seleccione al menos un producto');
            return;
        }

        $products = Product::whereIn('id', $this->selected)->get();
        $itemsToCreate = [];
        $productsWithoutSupplier = [];

        // Determine the supplier for the entire order
        // If a specific supplier is selected in the dropdown, use it.
        // Otherwise, set it to NULL (user will select it later in Purchases).
        $orderSupplierId = $this->supplier_id ? $this->supplier_id : null;

        foreach ($products as $product) {
            $cost = 0;

            if ($orderSupplierId) {
                // Scenario 1: Specific supplier selected
                // Try to find existing cost for this supplier
                $ps = $product->productSuppliers()->where('supplier_id', $orderSupplierId)->first();
                $cost = $ps ? $ps->cost : $product->cost; // Fallback to product cost
            } else {
                // Scenario 2: No specific supplier (Mixed/All)
                // We use the product's current cost or cheapest supplier cost as a reference
                $supplierInfo = $product->getCheapestSupplier();
                $cost = $supplierInfo ? $supplierInfo->cost : $product->cost;
            }

            $deficit = $product->max_stock - $product->stock_qty;
            
            // If deficit is <= 0 (stock is full), we default to 1 unit as requested
            if ($deficit <= 0) {
                $deficit = 1;
            }

            $itemsToCreate[] = [
                'product_id' => $product->id,
                'qty' => $deficit,
                'cost' => $cost
            ];
        }
        
        // Create the SINGLE order
        if (!empty($itemsToCreate)) {
            \Illuminate\Support\Facades\DB::beginTransaction();
            try {
                $total = 0;
                foreach ($itemsToCreate as $item) {
                    $total += $item['qty'] * $item['cost'];
                }

                $purchase = \App\Models\Purchase::create([
                    'supplier_id' => $orderSupplierId, // Can be null now
                    'status' => 'pending',
                    'type' => 'credit',
                    'user_id' => auth()->id(),
                    'total' => $total,
                    'items' => count($itemsToCreate),
                    'notes' => 'Generado desde Requisición'
                ]);

                foreach ($itemsToCreate as $item) {
                    \App\Models\PurchaseDetail::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['qty'],
                        'cost' => $item['cost'],
                        'flete_total' => 0,
                        'flete_product' => 0,
                        'created_at' => now()
                    ]);
                }

                \Illuminate\Support\Facades\DB::commit();
                $this->selected = [];
                
                $msg = 'Orden generada exitosamente.';
                $this->dispatch('msg', $msg);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollback();
                $this->dispatch('error', 'Error al generar orden: ' . $e->getMessage());
            }
        } else {
             $this->dispatch('noty', msg: 'No se pudieron procesar los productos seleccionados.');
        }
    }

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
