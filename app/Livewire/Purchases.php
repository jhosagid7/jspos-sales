<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Product;
use Livewire\Component;
use App\Models\Purchase;
use App\Traits\UtilTrait;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use App\Models\Configuration;
use App\Models\PurchaseDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Purchases extends Component
{
    use UtilTrait;
    public Collection $cart;

    public $taxCart = 0, $itemsCart, $subtotalCart = 0, $totalCart = 0, $ivaCart = 0, $status = 'paid', $purchaseType = 'cash', $notes;
    public $supplier, $flete;
    public $search, $productSelected;
    public $iva = 0, $config;

    public function mount()
    {
        if (session()->has("purchase_cart")) {
            $this->cart = session("purchase_cart");
        } else {
            $this->cart = new Collection;
        }
        $this->config = Configuration::first();
        // dd($this->config);

        session(['map' => 'Compras', 'child' => ' Componente ', 'pos' => 'MÓDULO DE COMPRAS']);
    }


    public function render()
    {

        $this->config = Configuration::first();
        $this->flete =  session('flete', 0);

        $this->cart = $this->cart->sortBy('name');
        $this->taxCart = round($this->totalIVA(), 2);
        $this->itemsCart = $this->totalItems();
        $this->totalCart = round($this->totalCart() + floatval($this->flete), 2);

        if ($this->config->vat > 0) {
            $this->iva = $this->config->vat / 100;
            $this->subtotalCart = round($this->subtotalCart() / (1 + $this->iva));
            $this->ivaCart = round(($this->totalCart() / (1 + $this->iva)) * $this->iva);
        } else {
            $this->iva = $this->config->vat;
            $this->subtotalCart = round($this->subtotalCart());
            $this->ivaCart = round(0);
        }
        $this->supplier =  session('purchase_supplier', null);

        return view('livewire.purchases.purchases', [
            'searchResults' => $this->searchProduct()
        ]);
    }



    #[On('purchase_supplier')]
    function setCustomer($supplier)
    {
        session(['purchase_supplier' => $supplier]);
        $this->supplier = $supplier;
    }


    #[On('purchase_product')]
    function setProduct(Product $product)
    {

        $this->AddProduct($product);
    }



    //metodos flete
    function setFlete($costoFlete)
    {
        if (empty($costoFlete)) return;

        if (!is_numeric($costoFlete)) return;

        session(['flete' => $costoFlete]);

        $this->calcFlete();
    }

    function unsetFlete()
    {
        session(['flete' => 0]);
        $this->calcFlete();
    }

    //-------------------------------------------------------------------------//
    //                  metodos locales del carrito
    //-------------------------------------------------------------------------//
    /* puedes colocar toda la lógica siguiente en un trait
    o en un helper para hacerlo reutilizable en cualquier parte del proyecto */
    function AddProduct($product, $qty = 1)
    {

        // if ($this->inCart($product->id)) {
        //     $this->updateQty(null, $qty, $product->id);
        //     return;
        // }


        $cost = 0;
        $total = 0;

        if ($this->config->vat > 0) {
            //iva venezuela 16%
            $iva = ($this->config->vat / 100);

            // precio unitario sin iva
            $precioUnitarioSinIva =  $cost / (1 + $iva);
            // subtotal neto
            $subtotalNeto =   $precioUnitarioSinIva * $this->formatAmount($qty);
            //monto iva
            $montoIva = $subtotalNeto  * $iva;
            //total con iva
            $totalConIva =  $subtotalNeto + $montoIva;

            $tax = $montoIva;
            $total = $totalConIva;
        } else {
            // precio unitario sin iva
            $precioUnitarioSinIva =  $cost;
            // subtotal neto
            $subtotalNeto =   $precioUnitarioSinIva * $this->formatAmount($qty);
            //monto iva
            $montoIva = 0;
            //total con iva
            $totalConIva =  $subtotalNeto + $montoIva;

            $tax = $montoIva;
            $total = $totalConIva;
        }

        $uid = uniqid() . $product->id;
        // dd('addProduct');

        $coll = collect(
            [
                'id' => $uid,
                'pid' => $product->id,
                'name' => $product->name,
                'cost' => $cost,
                'qty' => floatval($qty),
                'total' => $total,
                'tax' => $tax,
                'flete' => array('flete_producto' =>  0, 'total_flete' => 0, 'valor_flete' => 0, 'nuevo_total' => 0),


            ]
        );

        $itemCart = Arr::add($coll, null, null);
        $this->cart->push($itemCart);
        $this->save();
        $this->dispatch('refresh');
        $this->dispatch('noty', msg: ' AGREGADO AL CARRITO');
        $this->dispatch('focus-cost', element: $product->id, uid: $uid);
    }



    public function setCost($uid, $cost)
    {
        //dd($uid, $cant);
        if (!is_numeric($cost)) {
            $this->dispatch('noty', msg: 'EL VALOR DEL COSTO ES INCORRECTO');
            return;
        }

        $mycart = $this->cart;


        $oldItem = $mycart->where('id', $uid)->first();
        //dd($oldItem);
        $newItem = $oldItem;
        $newItem['cost'] = $cost;

        $newItem['total'] = round($newItem['qty'] * $cost, 2);

        //$newItem['flete'] = $this->getItemFlete($newItem['total'], $newItem['qty'], $cost);

        //delete from cart
        $this->cart = $this->cart->reject(function ($product) use ($uid) {
            return $product['id'] === $uid;
        });

        $this->save();

        //add item to cart
        $this->cart->push(Arr::add(
            $newItem,
            null,
            null
        ));

        $this->save();
        $this->dispatch('noty', msg: 'PRECIO ACTUALIZADO');
        $this->dispatch('focus-search');
    }

    public function updateQty($uid, $cant = 1)
    {
        //dd($uid, $cant);
        if (!is_numeric($cant)) {
            $this->dispatch('noty', msg: 'EL VALOR DE LA CANTIDAD ES INCORRECTO');
            $this->dispatch('reverse', id: $uid);
            return;
        }

        $mycart = $this->cart;

        $oldItem = $mycart->where('id', $uid)->first();

        $newItem = $oldItem;

        $newItem['qty'] = $cant;

        $newItem['total'] = round($newItem['qty'] * $newItem['cost'], 2);

        //delete from cart
        $this->cart = $this->cart->reject(function ($product) use ($uid) {
            return $product['id'] === $uid;
        });

        $this->save();

        //add item to cart
        $this->cart->push(Arr::add(
            $newItem,
            null,
            null
        ));

        $this->save();
        $this->dispatch('focus-search');
        $this->dispatch('noty', msg: 'CANTIDAD ACTUALIZADA');
    }


    public function IncDec($uid, $action = 1)
    {
        $mycart = $this->cart;

        $oldItem = $mycart->where('id', $uid)->first();

        $newItem = $oldItem;

        $currentQty = $newItem['qty'];
        $newQty = ($action == 1 ? $currentQty + 1 : $currentQty - 1);

        if (floatval($newQty) > 0) {

            $newItem['qty'] = $newQty;

            $newItem['total'] = round($newItem['qty'] * $newItem['cost'], 2);

            //delete from cart
            $this->cart = $this->cart->reject(function ($product) use ($uid) {
                return $product['id'] === $uid;
            });

            $this->save();

            //add item to cart
            $this->cart->push(Arr::add($newItem,  null,  null));
        } else {
            $this->cart = $this->cart->reject(function ($product) use ($uid) {
                return $product['id'] === $uid;
            });
        }

        $this->save();
        $this->dispatch('focus-search');
        $this->dispatch('noty', msg: 'CANTIDAD ACTUALIZADA');
    }


    public function removeItem($uid)
    {
        $this->cart = $this->cart->reject(function ($product) use ($uid) {
            return $product['id'] === $uid;
        });

        $this->save();
        $this->dispatch('refresh');
        $this->dispatch('noty', msg: 'PRODUCTO ELIMINADO');
    }

    //totales cart
    public function totalIVA()
    {
        $iva = $this->cart->sum(function ($product) {
            return $product['tax'] ?? 0;
        });
        return $iva;
    }



    public function totalCart()
    {
        $amount = $this->cart->sum(function ($product) {
            return $product['total'];
        });
        return $amount;
    }

    public function subtotalCart()
    {
        $subt = $this->cart->sum(function ($product) {
            return $product['qty'] * $product['cost'];
        });
        return $subt;
    }

    public function totalItems()
    {
        return   $this->cart->count();
    }

    public function save()
    {
        session()->put("purchase_cart", $this->cart);
        session()->save();
    }

    public function clear()
    {
        $this->cart = new Collection;
        $this->save();
        $this->dispatch('focus-search');
    }

    // calculo flete individual
    function getItemFlete($totalItem = 0, $qtyItem = 0, $costItem = 0)
    {
        try {
            $totalCart =  $this->totalCart;
            $costoFlete = session('flete', 0);

            if ($costoFlete == 0 || $totalCart <= 0) {
                return  array('flete_producto' =>  0, 'total_flete' => 0, 'valor_flete' => 0, 'nuevo_total' => 0);
            }


            //total flete producto
            $tfp = round(floatval(($costoFlete * $totalItem) / $totalCart), 2);
            //flete por producto
            $fxp = round(floatval($tfp /  $qtyItem), 2);
            //valor + flete
            $vf =  round(($costItem + $fxp), 2);
            //total con flete
            $tcf = round(floatval($totalItem + $tfp), 2);


            //
            return array('flete_producto' =>  $fxp, 'total_flete' => $tfp, 'valor_flete' => $vf, 'nuevo_total' => $tcf);
        } catch (\Throwable) {
            return array('flete_producto' => 0, 'total_flete' => 0, 'valor_flete' => 0, 'nuevo_total' => 0);
        }
    }

    // calculo general flete
    function calcFlete()
    {
        //agrega el try /catch necesario
        $totalCart =  $this->totalCart;
        $costoFlete = session('flete', 0);
        // if ($costoFlete == 0) return;

        $cart = $this->cart;
        foreach ($cart as  $item) {
            //total flete producto
            $tfp = round(floatval(($costoFlete * $item['total']) / $totalCart), 2);
            //flete por producto
            $fxp = round(floatval($tfp /  $item['qty']), 2);
            //valor + flete
            $vf =  round(($item['cost'] + $fxp), 2);
            //total con flete
            $tcf = round(floatval($item['total'] + $tfp), 2);

            //remove product
            $this->cart = $this->cart->reject(function ($product) use ($item) {
                return $product['id'] === $item['id'];
            });
            $this->save();

            //add product
            $arrayFlete = array('flete_producto' =>  $fxp, 'total_flete' => $tfp, 'valor_flete' => $vf, 'nuevo_total' => $tcf);
            $item['flete'] = $arrayFlete;
            $this->cart->push(Arr::add($item,  null,  null));
            $this->save();
        }
    }

    public function searchProduct()
    {
        if (!empty($this->search)) {
            return Product::where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")
                ->orderBy('name')
                ->take(1)->get();
        } else {
            return [];
        }
    }



    function initPayment($type)
    {
        $this->purchaseType = $type;

        if ($type == 1) $this->purchaseType = 'cash';
        if ($type == 2) $this->purchaseType = 'credit';


        $this->dispatch('initPay', payType: $type);
    }



    // store purchase
    function Store()
    {
        if (floatval($this->totalCart) <= 0) {
            $this->dispatch('noty', msg: 'AGREGA PRODUCTOS AL CARRITO');
            return;
        }
        if ($this->supplier == null) {
            $this->dispatch('noty', msg: 'SELECCIONA EL PROVEEDOR');
            return;
        }

        $this->status = $this->purchaseType == 'credit' ? 'pending' : 'paid';

        DB::beginTransaction();
        try {

            $purchase = Purchase::create([
                'total' => $this->totalCart(),
                'flete' => $this->flete,
                'items' => $this->itemsCart,
                'discount' => 0,
                'status' => $this->status,
                'type' => $this->purchaseType,
                'supplier_id' => $this->supplier['id'],
                'user_id' =>  Auth()->user()->id,
                'notes' => $this->notes,
            ]);


            $cart = session("purchase_cart");

            // insert sale detail
            $details = $cart->map(function ($item) use ($purchase) {
                return [
                    'product_id' => $item['pid'],
                    'purchase_id' => $purchase->id,
                    'quantity' => $item['qty'],
                    'cost' => $item['cost'] ?? 0,
                    'flete_total' => $item['flete']['total_flete'],
                    'flete_product' => $item['flete']['flete_producto'],
                    'created_at' => Carbon::now()
                ];
            })->toArray();

            PurchaseDetail::insert($details);


            //actualizar nuevo precio de venta
            foreach ($cart as  $item) {

                $myProduct = Product::find($item['pid']);

                //stock / cost / before purchase
                $currentStock = $myProduct->stock_qty;
                $currentCostPrice = $myProduct->cost;

                //quantity / cost / purchase
                $purchaseStock = $item['qty'];
                $purchaseCost = $item['cost'];

                //get new sale price using formula
                /*
                para obtener el nuevo precio de venta, se utiliza el método: "fijación de precios basado en costos y margen de ganancia"
                puedes modificarlo o agregar tus propios métodos considerando otros datos como: gastos de traslado, producción, almacenamiento,
                y cualquier variable que implique un costo de adquisición de tus productos
                 */
                $newSalePrice = $this->getPrecioVenta($currentCostPrice,    $currentStock, $purchaseCost, $purchaseStock, 30);

                if (!isset($newSalePrice['error'])) {
                    $myProduct->cost = $item['cost'];
                    $myProduct->price = $newSalePrice['price'];
                    $myProduct->save();
                }
            }

            //update stocks
            foreach ($cart as  $item) {
                Product::find($item['pid'])->increment('stock_qty', $item['qty']);
            }

            DB::commit();

            $this->dispatch('reset-tom');
            $this->dispatch('noty', msg: 'COMPRA REGISTRADA EXITOSAMENTE');
            $this->dispatch('close-modal');
            $this->reset();
            $this->clear();
            session()->forget('purchase_supplier');
            session()->forget('flete');


            //
        } catch (\Exception $th) {
            DB::rollBack();

            $this->dispatch('noty', msg: "Error al intentar guardar la compra \n {$th->getMessage()}");
        }
    }
}
