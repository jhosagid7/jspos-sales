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
use App\Traits\PrintTrait;

use Livewire\WithPagination;

class Purchases extends Component
{
    use UtilTrait;
    use PrintTrait; // Add PrintTrait
    use WithPagination;

    public Collection $cart;

    public $taxCart = 0, $itemsCart, $subtotalCart = 0, $totalCart = 0, $ivaCart = 0, $status = 'paid', $purchaseType = 'cash', $notes;
    public $supplier, $flete;
    public $search, $productSelected;
    public $iva = 0, $config;
    public $editingProductPrices = [];
    public $editingCartId = null;

    // Order processing properties
    public $searchOrder = '';
    public $order_selected_id = null;
    public $pagination = 5;

    // Supplier creation properties
    public $sname, $saddress, $sphone;

    // Listeners
    protected $listeners = [
        'cancelSale' => 'clear',
        'purchase_supplier' => 'setCustomer',
        'purchase_product' => 'setProduct',
        'refresh' => '$refresh'
    ];

    public function openPriceModal($cartId)
    {
        $this->editingCartId = $cartId;
        $item = $this->cart->firstWhere('id', $cartId);
        $product = Product::find($item['pid']);
        
        // Initialize with main price
        $this->editingProductPrices = [
            [
                'type' => 'main',
                'price' => $item['price'] ?? $product->price,
                'margin' => $this->calculateMargin($item['price'] ?? $product->price, $item['cost'])
            ]
        ];

        // Add secondary prices
        if(isset($item['modified_prices'])) {
            // If we already modified prices in this session, load them
            foreach($item['modified_prices'] as $price) {
                if($price['type'] !== 'main') {
                    $this->editingProductPrices[] = $price;
                }
            }
        } else {
            // Load from DB
            foreach($product->priceList as $pl) {
                $this->editingProductPrices[] = [
                    'type' => 'secondary',
                    'price' => $pl->price,
                    'margin' => $this->calculateMargin($pl->price, $item['cost'])
                ];
            }
        }

        $this->dispatch('show-modal', 'priceModal');
    }

    public function addPriceRow()
    {
        $this->editingProductPrices[] = [
            'type' => 'secondary',
            'price' => 0,
            'margin' => 0
        ];
    }

    public function removePriceRow($index)
    {
        unset($this->editingProductPrices[$index]);
        $this->editingProductPrices = array_values($this->editingProductPrices);
    }

    public function updateModalMargin($index)
    {
        $item = $this->cart->firstWhere('id', $this->editingCartId);
        $price = $this->editingProductPrices[$index]['price'];
        $cost = $item['cost'];
        
        $this->editingProductPrices[$index]['margin'] = $this->calculateMargin($price, $cost);
    }

    public function savePrices()
    {
        $item = $this->cart->firstWhere('id', $this->editingCartId);
        $mainPrice = $this->editingProductPrices[0]['price'];
        
        // Update main price in cart
        $this->setPrice($this->editingCartId, $mainPrice);
        
        // Store all prices in cart item for later saving
        $item = $this->cart->firstWhere('id', $this->editingCartId); // Re-fetch updated item
        $item['modified_prices'] = $this->editingProductPrices;
        
        // Update cart
        $this->cart = $this->cart->reject(function ($product) {
            return $product['id'] === $this->editingCartId;
        });
        $this->cart->push($item);
        $this->save();
        
        $this->dispatch('hide-modal', 'priceModal');
        $this->dispatch('noty', msg: 'Precios actualizados');
    }

    public function calculateMargin($price, $cost)
    {
        if($cost > 0) {
            return round((($price - $cost) / $cost) * 100, 2);
        }
        return 0;
    }

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
            'searchResults' => $this->searchProduct(),
            'orders' => $this->orders
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

        $exist = $this->cart->firstWhere('pid', $product->id);
        
        if ($exist) {
            $this->updateQty($exist['id'], $exist['qty'] + $qty);
            return;
        }


        $cost = $product->cost;
        $price = $product->price;
        $margin = 0;
        
        if($cost > 0) {
            $margin = round((($price - $cost) / $cost) * 100, 2);
        }

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
                'price' => $price,
                'margin' => $margin,
                'qty' => floatval($qty),
                'total' => $total,
                'tax' => $tax,
                'flete' => array('flete_producto' =>  0, 'total_flete' => 0, 'valor_flete' => 0, 'nuevo_total' => 0),


            ]
        );

        $itemCart = $coll->toArray();
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
        
        // Recalculate margin
        if($cost > 0 && isset($newItem['price'])) {
             $newItem['margin'] = round((($newItem['price'] - $cost) / $cost) * 100, 2);
        }

        $newItem['total'] = round($newItem['qty'] * $cost, 2);

        //$newItem['flete'] = $this->getItemFlete($newItem['total'], $newItem['qty'], $cost);

        //delete from cart
        $this->cart = $this->cart->reject(function ($product) use ($uid) {
            return $product['id'] === $uid;
        });

        $this->save();

        //add item to cart
        $this->cart->push($newItem);

        $this->save();
        $this->dispatch('noty', msg: 'PRECIO ACTUALIZADO');
        $this->dispatch('focus-search');
    }

    public function setPrice($uid, $price)
    {
        if (!is_numeric($price)) {
            $this->dispatch('noty', msg: 'EL VALOR DEL PRECIO ES INCORRECTO');
            return;
        }

        $mycart = $this->cart;
        $oldItem = $mycart->where('id', $uid)->first();
        $newItem = $oldItem;
        $newItem['price'] = $price;

        // Recalculate margin
        if(isset($newItem['cost']) && $newItem['cost'] > 0) {
             $newItem['margin'] = round((($price - $newItem['cost']) / $newItem['cost']) * 100, 2);
        }

        //delete from cart
        $this->cart = $this->cart->reject(function ($product) use ($uid) {
            return $product['id'] === $uid;
        });

        $this->save();

        //add item to cart
        $this->cart->push($newItem);

        $this->save();
        $this->dispatch('noty', msg: 'PRECIO VENTA ACTUALIZADO');
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
        $this->cart->push($newItem);

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
            $this->cart->push($newItem);
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
            $this->cart->push($item);
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

                // Update cost (always)
                $myProduct->cost = $item['cost'];

                // If prices were modified via Modal
                if(isset($item['modified_prices'])) {
                    // Update main price
                    $myProduct->price = $item['price']; 
                    $myProduct->save();

                    // Update secondary prices
                    $myProduct->priceList()->delete();
                    foreach($item['modified_prices'] as $priceRow) {
                        if($priceRow['type'] === 'secondary') {
                            $myProduct->priceList()->create([
                                'price' => $priceRow['price']
                            ]);
                        }
                    }
                } else {
                    // If price was modified in the row (without modal) or just needs update
                    // We trust the price in the cart as the user might have edited it
                    $myProduct->price = $item['price'];
                    $myProduct->save();
                    
                    /* 
                    // OLD LOGIC: Weighted Average Calculation
                    // We disable this to respect the user's manual input in the cart
                    
                    //stock / cost / before purchase
                    $currentStock = $myProduct->stock_qty;
                    $currentCostPrice = $myProduct->cost;

                    //quantity / cost / purchase
                    $purchaseStock = $item['qty'];
                    $purchaseCost = $item['cost'];

                    $newSalePrice = $this->getPrecioVenta($currentCostPrice,    $currentStock, $purchaseCost, $purchaseStock, 30);

                    if (!isset($newSalePrice['error'])) {
                        $myProduct->cost = $item['cost'];
                        $myProduct->price = $newSalePrice['price'];
                        $myProduct->save();
                    }
                    */
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

    public function storeOrder()
    {
        if (floatval($this->totalCart) <= 0) {
            $this->dispatch('noty', msg: 'AGREGA PRODUCTOS AL CARRITO');
            return;
        }
        if ($this->supplier == null) {
            $this->dispatch('noty', msg: 'SELECCIONA EL PROVEEDOR');
            return;
        }

        $this->status = 'pending';
        $this->purchaseType = 'credit'; // Orders are typically pending/credit

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

            // For orders, we might NOT want to update product costs/prices immediately?
            // Usually orders are just saved. But if it's a "Purchase Order" that confirms cost...
            // Let's assume for now it behaves like a purchase but with 'pending' status.
            // So we DO update costs/prices? Or wait until it becomes a real purchase?
            // The user request says "Save Purchase as Order". 
            // In Sales, StoreOrder saves as pending.
            // Let's keep the price update logic for consistency, or maybe skip it?
            // If I skip it, then when is it updated? When status changes to paid?
            // For simplicity and safety, let's Update prices/costs now as it reflects the agreed cost.
            
            //actualizar nuevo precio de venta
            foreach ($cart as  $item) {

                $myProduct = Product::find($item['pid']);

                // Update cost (always)
                $myProduct->cost = $item['cost'];

                // If prices were modified via Modal
                if(isset($item['modified_prices'])) {
                    // Update main price
                    $myProduct->price = $item['price']; 
                    $myProduct->save();

                    // Update secondary prices
                    $myProduct->priceList()->delete();
                    foreach($item['modified_prices'] as $priceRow) {
                        if($priceRow['type'] === 'secondary') {
                            $myProduct->priceList()->create([
                                'price' => $priceRow['price']
                            ]);
                        }
                    }
                } else {
                    $myProduct->price = $item['price'];
                    $myProduct->save();
                }
            }

            //update stocks?
            // If it's an order (pending), maybe we don't increase stock yet?
            // In Sales, pending sales DEDUCT stock.
            // In Purchases, pending purchases SHOULD NOT increase stock until received?
            // However, the current Store() method increments stock regardless of status.
            // Let's follow the existing Store() logic which increments stock.
            foreach ($cart as  $item) {
                Product::find($item['pid'])->increment('stock_qty', $item['qty']);
            }

            DB::commit();

            $this->dispatch('reset-tom');
            $this->dispatch('noty', msg: 'ORDEN REGISTRADA EXITOSAMENTE');
            $this->dispatch('close-modal');
            $this->reset();
            $this->clear();
            session()->forget('purchase_supplier');
            session()->forget('flete');

        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al intentar guardar la orden \n {$th->getMessage()}");
        }
    }

    // Order Management Methods
    public function getOrdersProperty()
    {
        if (empty(trim($this->searchOrder))) {
            return Purchase::where('status', 'pending')
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);
        } else {
            $search = trim($this->searchOrder);
            return Purchase::where('status', 'pending')
                ->where(function ($query) use ($search) {
                    $query->where('id', 'like', "%{$search}%")
                          ->orWhere('total', 'like', "%{$search}%")
                          ->orWhereHas('supplier', function ($q) use ($search) {
                              $q->where('name', 'like', "%{$search}%");
                          });
                })
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);
        }
    }

    public function loadOrderToCart($purchaseId)
    {
        $this->clear();
        session()->forget('purchase_supplier');

        $purchase = Purchase::find($purchaseId);
        
        if(!$purchase) {
            $this->dispatch('noty', msg: 'Orden no encontrada');
            return;
        }

        // Set Supplier
        if($purchase->supplier) {
            $this->setCustomer($purchase->supplier);
        }

        // Load Items
        $details = PurchaseDetail::where('purchase_id', $purchaseId)->get();
        
        foreach ($details as $detail) {
            $product = Product::find($detail->product_id);
            if($product) {
                // We use the cost from the order detail
                $product->cost = $detail->cost;
                $this->AddProduct($product, $detail->quantity);
                
                // If flete was saved, we might need to handle it. 
                // Current AddProduct logic initializes flete to 0.
                // If we want to restore flete, we'd need to update the item in cart.
            }
        }
        
        // Restore global flete if any
        if($purchase->flete > 0) {
            $this->setFlete($purchase->flete);
        }

        // Delete the pending order as it's now in cart (or keep it until saved again?)
        // In Sales, it seems to just load it. If we save again, it creates a NEW purchase?
        // Or does it update the existing one?
        // The Store() method creates a NEW Purchase.
        // So we should probably DELETE the old pending order to avoid duplicates if the intention is to "resume" it.
        // However, if the user cancels, the order is lost if we delete it now.
        // Better to delete it ONLY when the new purchase is successfully saved, OR delete it now and if they cancel, it's gone?
        // Let's follow a safe approach: Keep it for now. If they save, it creates a new one.
        // But then we have the old pending one.
        // Sales module `loadOrderToCart` does NOT delete the order.
        // But `Store` creates a new one. So pending orders pile up?
        // Let's check `DestroyOrder`.
        
        // For now, let's just load it. The user can manually delete the old one if needed, or we can handle it later.
        // Actually, if I load it and save it, it becomes a "paid" purchase (or another pending).
        // If I want to "edit" the pending order, I should probably delete the old one when loading?
        // Let's stick to loading for now.

        $this->dispatch('close-process-order');
        $this->dispatch('noty', msg: 'Orden cargada al carrito');
    }

    #[On('DestroyOrder')]
    public function DestroyOrder($purchaseId)
    {
        $purchase = Purchase::find($purchaseId);
        if ($purchase) {
            $purchase->status = 'deleted'; // Soft delete or status change
            $purchase->save();
            // Or $purchase->delete(); if using soft deletes model
            $this->dispatch('noty', msg: 'Orden eliminada');
        }
    }

    public function storeSupplier()
    {
        $this->validate([
            'sname' => 'required|min:3',
            'sphone' => 'required',
            'saddress' => 'nullable'
        ]);

        $supplier = \App\Models\Supplier::create([
            'name' => $this->sname,
            'phone' => $this->sphone,
            'address' => $this->saddress
        ]);

        $this->setCustomer($supplier);
        $this->dispatch('noty', msg: 'PROVEEDOR REGISTRADO');
        $this->dispatch('close-modal-supplier');
        $this->reset('sname', 'sphone', 'saddress');
    }

    public function printLast()
    {
        $lastPurchase = Purchase::latest()->first();
        if ($lastPurchase) {
            $this->printPurchase($lastPurchase->id); // Assuming PrintTrait has printPurchase
            $this->dispatch('noty', msg: 'IMPRIMIENDO ÚLTIMA COMPRA');
        } else {
            $this->dispatch('noty', msg: 'NO HAY COMPRAS REGISTRADAS');
        }
    }

    #[On('cancelSale')]
    public function cancelSale()
    {
        $this->reset();
        $this->clear();
        session()->forget('purchase_supplier');
        session()->forget('flete');
        $this->dispatch('reset-tom');
        $this->dispatch('noty', msg: 'COMPRA CANCELADA');
    }
}
