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
    public $showAll = false; // New property
    private $pagination = 10;

    public function mount()
    {
        $this->pageTitle = 'Requisición';
        $this->componentName = 'Compras Sugeridas';
        $this->supplier_id = '';
        $this->selected = [];
        $this->showAll = false;
    }

    public function createOrder()
    {
        if (empty($this->selected)) {
            $this->dispatch('noty', msg: 'Seleccione al menos un producto');
            return;
        }

        $products = Product::whereIn('id', $this->selected)->get();
        $ordersBySupplier = [];
        $productsWithoutSupplier = [];

        foreach ($products as $product) {
            $supplierInfo = $product->getCheapestSupplier();
            
            if (!$supplierInfo) {
                $productsWithoutSupplier[] = $product->name;
                continue;
            }

            $supplierId = $supplierInfo->supplier_id;
            $deficit = $product->max_stock - $product->stock_qty;
            
            // If deficit is <= 0 (stock is full), we default to 1 unit as requested
            if ($deficit <= 0) {
                $deficit = 1;
            }

            $ordersBySupplier[$supplierId][] = [
                'product_id' => $product->id,
                'qty' => $deficit,
                'cost' => $supplierInfo->cost
            ];
        }
        
        // If there are valid orders, create them
        if (!empty($ordersBySupplier)) {
            \Illuminate\Support\Facades\DB::beginTransaction();
            try {
                foreach ($ordersBySupplier as $supplierId => $items) {
                    $total = 0;
                    foreach ($items as $item) {
                        $total += $item['qty'] * $item['cost'];
                    }

                    $purchase = \App\Models\Purchase::create([
                        'supplier_id' => $supplierId,
                        'status' => 'pending',
                        'type' => 'credit',
                        'user_id' => auth()->id(),
                        'total' => $total,
                        'items' => count($items),
                        'notes' => 'Generado desde Requisición'
                    ]);

                    foreach ($items as $item) {
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
                }

                \Illuminate\Support\Facades\DB::commit();
                $this->selected = [];
                
                $msg = 'Órdenes generadas exitosamente.';
                if (!empty($productsWithoutSupplier)) {
                    $msg .= ' Atención: Algunos productos no tienen proveedor asignado: ' . implode(', ', $productsWithoutSupplier);
                }
                
                $this->dispatch('msg', $msg);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollback();
                $this->dispatch('error', 'Error al generar órdenes: ' . $e->getMessage());
            }
        } else {
            // No valid orders were generated
            if (!empty($productsWithoutSupplier)) {
                 $this->dispatch('noty', msg: 'No se generaron órdenes. Productos sin proveedor: ' . implode(', ', $productsWithoutSupplier));
            } else {
                 $this->dispatch('noty', msg: 'No se generaron órdenes. Verifique stock.');
            }
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
