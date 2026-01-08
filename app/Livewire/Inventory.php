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
        //$action  1=agregar,  2=restar,  3=ajustar
        //dd($product, $qty, $action);
        if (intval($action) < 1 && intval($action) > 3) {
            $this->dispatch('noty', msg: 'LA ACCIÓN A REALIZAR ES INVÁLIDA!');
            return;
        }
        if (!is_numeric($qty) || intval($qty) < 1) {
            $this->dispatch('noty', msg: 'LA CANTIDAD ES INCORRECTA!');
            return;
        }

        try {


            $feedback = null;
            if ($action == 1) {
                $product->decrement('stock_qty', $qty);
                $feedback = "SE RESTÓ AL STOCK $qty UNIDADES";
            } else if ($action == 2) {
                $product->increment('stock_qty', $qty);
                $feedback = "SE AGREGARON $qty UNIDADES AL STOCK";
            } else if ($action == 3) {
                $product->update(['stock_qty' => $qty]);
                $feedback = "SE AJUSTÓ EL STOCK A $qty UNIDADES ";
            }
            $this->dispatch('noty', msg: $feedback);
            $this->dispatch('clear-input', id: $product->id);
            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar ajustar el stock \n {$th->getMessage()} ");
        }
    }
}
