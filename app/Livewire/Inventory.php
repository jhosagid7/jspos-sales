<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;

class Inventory extends Component
{
    //public $tcosto = 0, $tventa = 0;

    public function render()
    {
        return view('livewire.inventories.inventory', [
            'info' => $this->getInventory()
        ]);
    }

    function getInventory()
    {
        $data = Product::orderBy('name')->get();

        $tcosto = $data->sum(function ($product) {
            return $product->stock_qty * $product->cost;
        });

        $tventa = $data->sum(function ($product) {
            return $product->stock_qty * $product->price;
        });

        $profit = $tventa - $tcosto;
        $margin = $tventa > 0 ? ($profit / $tventa) * 100 : 0;

        session(['map' => "TOTAL COSTO $" . number_format($tcosto, 2), 'child' => "TOTAL VENTA $" . number_format($tventa, 2), 'rest' => ' GANANCIA: $' . number_format($profit, 2) . " / MARGEN: " . number_format($margin, 2) . "%", 'pos' => '']);

        return $data;
    }

    function Ajustar(Product $product, $qty, $action = 1)
    {
        //$action  1=restar,  2=agregar,  3=ajustar
        if (intval($action) < 1 || intval($action) > 3) {
            $this->dispatch('noty', msg: 'LA ACCIÓN A REALIZAR ES INVÁLIDA!');
            return;
        }
        if (!is_numeric($qty) || intval($qty) < 0) {
            $this->dispatch('noty', msg: 'LA CANTIDAD ES INCORRECTA!');
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $config = \App\Models\Configuration::first();
            $warehouseId = auth()->user()->warehouse_id ?? $config->default_warehouse_id ?? \App\Models\Warehouse::first()->id ?? 1;

            $pw = \App\Models\ProductWarehouse::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouseId],
                ['stock_qty' => 0]
            );

            $feedback = null;
            if ($action == 1) {
                $product->decrement('stock_qty', $qty);
                $pw->decrement('stock_qty', $qty);
                $feedback = "SE RESTÓ AL STOCK $qty UNIDADES";
            } else if ($action == 2) {
                $product->increment('stock_qty', $qty);
                $pw->increment('stock_qty', $qty);
                $feedback = "SE AGREGARON $qty UNIDADES AL STOCK";
            } else if ($action == 3) {
                $pw->stock_qty = $qty;
                $pw->save();

                $product->stock_qty = \App\Models\ProductWarehouse::where('product_id', $product->id)->sum('stock_qty');
                $product->save();
                $feedback = "SE AJUSTÓ EL STOCK A $qty UNIDADES ";
            }

            \Illuminate\Support\Facades\DB::commit();

            $this->dispatch('noty', msg: $feedback);
            $this->dispatch('clear-input', id: $product->id);
        } catch (\Exception $th) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->dispatch('noty', msg: "Error al intentar ajustar el stock \n {$th->getMessage()} ");
        }
    }
}
