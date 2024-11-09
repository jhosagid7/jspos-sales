<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Bank;
use App\Models\Sale;
use App\Models\Product;
use Livewire\Component;
use App\Models\Customer;
use App\Traits\JsonTrait;
use App\Traits\UtilTrait;
use App\Models\SaleDetail;
use App\Traits\PrintTrait;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use App\Models\Configuration;
use App\Traits\PdfInvoiceTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Sales extends Component
{
    use UtilTrait;
    use PrintTrait;
    use PdfInvoiceTrait;
    use JsonTrait;


    public Collection $cart;
    public $taxCart = 0, $itemsCart, $subtotalCart = 0, $totalCart = 0, $ivaCart = 0;

    public $config, $customer, $iva = 0;
    //register customer
    public $cname, $caddress, $ccity, $cemail, $cphone, $ctaxpayerId, $ctype = 'Consumidor Final';

    //pay properties
    public $banks, $cashAmount, $nequiAmount, $change, $phoneNumber, $acountNumber, $depositNumber, $bank, $payType = 1, $payTypeName = 'PAGO EN EFECTIVO';

    public $search3, $products = [], $selectedIndex = -1;

    function updatedSearch3()
    {
        if (Strlen($this->search3) > 1) {
            $this->products = Product::with('priceList')
                ->where('sku', 'like', "%{$this->search3}%")
                ->orWhere('name', 'like', "%{$this->search3}%")
                ->get();
            if (count($this->products) == 0) {
                $this->dispatch('noty', msg: 'NO EXISTE EL CÓDIGO ESCANEADO');
            }
        } else {
            $this->search3 = '';
            $this->products = [];
            $this->dispatch('noty', msg: 'NO EXISTE EL CÓDIGO ESCANEADO');
        }
    }

    public function selectProduct($index)
    {
        if (isset($this->products[$index])) {
            $this->AddProduct($this->products[$index]); // Llama a tu método para agregar el producto
            $this->search3 = ''; // Resetear el campo de búsqueda
            $this->products = []; // Limpiar la lista de productos
            $this->selectedIndex = -1; // Resetear el índice seleccionado
        }
    }

    public function keyDown($key)
    {
        if ($key === 'ArrowDown') {
            $this->selectedIndex = min(count($this->products) - 1, $this->selectedIndex + 1);
        } elseif ($key === 'ArrowUp') {
            $this->selectedIndex = max(-1, $this->selectedIndex - 1);
        } elseif ($key === 'Enter') {
            $this->selectProduct($this->selectedIndex);
        }
    }


    function updatedCashAmount()
    {
        if (floatval($this->totalCart) > 0) {

            if (round(floatval($this->cashAmount))  >= floatval($this->totalCart)) {
                $this->nequiAmount = null;
                $this->phoneNumber = null;
            }

            $this->change = (round(floatval($this->cashAmount) + floatval($this->nequiAmount)) - floatval($this->totalCart));
        }
    }
    function updatedNequiAmount()
    {
        if (floatval($this->totalCart) > 0) {
            $this->change = (round(floatval($this->cashAmount) + floatval($this->nequiAmount)) - floatval($this->totalCart));
        }
    }
    function updatedPhoneNumber()
    {
        if (floatval($this->totalCart) > 0 && $this->phoneNumber != '') {
            $this->change = (round(floatval($this->cashAmount) + floatval($this->nequiAmount)) - floatval($this->totalCart));
        } else {
            $this->change = round(floatval($this->cashAmount) - floatval($this->totalCart));
            $this->nequiAmount = 0;
        }
    }

    function clearCashAmount()
    {
        $this->nequiAmount = null;
        $this->phoneNumber = null;
        $this->change = 0;
    }

    public function mount()
    {
        if (session()->has("cart")) {
            $this->cart = collect(session("cart"));
        } else {
            $this->cart = new Collection;
        }

        session(['map' => 'Ventas', 'child' => ' Componente ', 'pos' => 'MÓDULO DE VENTAS']);

        $this->config = Configuration::first();

        $this->banks = Bank::orderBy('sort')->get();
        $this->bank = $this->banks[0]->id;
    }


    public function render()
    {
        $this->cart = $this->cart->sortByDesc('id');
        $this->taxCart = round($this->totalIVA());
        $this->itemsCart = $this->totalItems();
        $this->totalCart = round($this->totalCart());
        if ($this->config->vat > 0) {
            $this->iva = $this->config->vat / 100;
            $this->subtotalCart = round($this->subtotalCart() / (1 + $this->iva));
            $this->ivaCart = round(($this->totalCart() / (1 + $this->iva)) * $this->iva);
        } else {
            $this->iva = $this->config->vat;
            $this->subtotalCart = round($this->subtotalCart());
            $this->ivaCart = round(0);
        }

        $this->customer =  session('sale_customer', null);

        return view('livewire.pos.sales');
    }


    // cart methods
    function ScanningCode($barcode)
    {
        $product = Product::with('priceList')
            ->where('sku', "%{$barcode}%")
            ->orWhere('name', 'like', "%{$barcode}%")
            ->first();
        if ($product) {
            $this->AddProduct($product);
        } else {
            $this->dispatch('noty', msg: 'NO EXISTE EL CÓDIGO ESCANEADO');
        }
    }

    function AddProduct(Product $product, $qty = 1)
    {
        // Obtener la cantidad actual del producto en el carrito
        $currentQtyInCart = 0;
        if ($this->inCart($product->id)) {
            // Si el producto ya está en el carrito, obtener la cantidad actual
            $currentQtyInCart = $this->getQtyInCart($product->id);
        }

        // Calcular la cantidad total que se intentará agregar
        $totalQtyToAdd = $currentQtyInCart + $qty;

        // Mensaje de depuración
        \Log::info("Intentando agregar al carrito: {$product->name}, Cantidad solicitada: {$qty}, Stock disponible: {$product->stock_qty}, Cantidad en carrito: {$currentQtyInCart}");
        if ($product->manage_stock == 1) {
            // Verificar si la cantidad total a agregar es mayor que el stock disponible
            if ($totalQtyToAdd > $product->stock_qty) {
                $this->dispatch('noty', msg: 'No hay suficiente stock para el producto: ' . $product->name);
                return;
            }
        }
        if ($this->inCart($product->id)) {
            $this->updateQty(null, $totalQtyToAdd, $product->id);
            return;
        }
        if (count($product->priceList) > 0)
            $salePrice = ($product->priceList[0]['price']);
        else
            $salePrice =  $product->price;

        //determinamos el precio de venta(con iva)
        if ($this->config->vat > 0) {
            //iva venezuela 16%
            $iva = ($this->config->vat / 100);

            // precio unitario sin iva
            $precioUnitarioSinIva =  $salePrice / (1 + $iva);
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
            $precioUnitarioSinIva =  $salePrice;
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

        $coll = collect(
            [
                'id' => $uid,
                'pid' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price1' => $product->price,
                'price2' => $product->price2,
                'sale_price' => $salePrice,
                'pricelist' => $product->priceList,
                'qty' => $this->formatAmount($qty),
                'tax' => $tax,
                'total' => $total,
                'stock' => $product->stock_qty,
                'type' => $product->type,
                'image' => $product->photo,
                'platform_id' => $product->platform_id
            ]
        );

        $itemCart = Arr::add($coll, null, null);
        $this->cart->push($itemCart);
        $this->save();
        $this->dispatch('refresh');
        $this->search3 = '';
        $this->products = [];
        $this->dispatch('noty', msg: 'PRODUCTO AGREGADO AL CARRITO');
    }

    // Método para obtener la cantidad de un producto en el carrito
    function getQtyInCart($productId)
    {
        foreach ($this->cart as $item) {
            if ($item['pid'] == $productId) {
                return $item['qty'];
            }
        }
        return 0; // Si no se encuentra el producto, retornar 0
    }

    function Calculator($price, $qty)
    {
        //iva méxico 16%
        $iva = ($this->config->vat / 100); // 0.16;
        //determinamos el precio de venta(con iva)
        $salePrice = $price;
        // precio unitario sin iva
        $precioUnitarioSinIva =  $salePrice / (1 + $iva);
        // subtotal neto
        $subtotalNeto =   $precioUnitarioSinIva * intval($qty);
        //monto iva
        $montoIva = $subtotalNeto  * $iva;
        //total con iva
        $totalConIva =  $subtotalNeto + $montoIva;

        return [
            'sale_price' => $salePrice,
            'neto' => $subtotalNeto,
            'iva' => $montoIva,
            'total' => $totalConIva
        ];
    }

    public function removeItem($id)
    {
        $this->cart = $this->cart->reject(function ($product) use ($id) {
            return $product['pid'] === $id || $product['id'] === $id;
        });

        $this->save();
        $this->dispatch('refresh');
        $this->dispatch('noty', msg: 'PRODUCTO ELIMINADO');
    }

    public function updateQty($uid, $cant = 1, $product_id = null)
    {
        // Validar que la cantidad sea numérica y mayor que cero
        if (!is_numeric($cant) || $cant <= 0) {
            $this->dispatch('noty', msg: 'EL VALOR DE LA CANTIDAD ES INCORRECTO');
            return;
        }

        // Obtener el carrito actual
        $mycart = $this->cart;

        if ($product_id == null) {
            $oldItem = $mycart->firstWhere('id', $uid);
            $product_id = $oldItem['pid'];
        } else {
            $oldItem = $mycart->firstWhere('pid', $product_id);
        }

        $product = Product::find($product_id);

        // Verificar si la cantidad total a agregar es mayor que el stock disponible

        if ($product->manage_stock == 1) {
            $newQty = $cant; // solo se agrega la cantidad que se está agregando
            if ($product->stock_qty < $newQty) {
                \Log::info("Intentando agregar al carrito: {$product->name}, Cantidad solicitada: {$newQty}, Stock disponible: {$product->stock_qty}, Cantidad en carrito: {$oldItem['qty']}");
                $this->dispatch('noty', msg: 'No hay suficiente stock para el producto: ' . $product->name);
                return;
            }
        }

        // Crear un nuevo artículo con la cantidad actualizada
        $newItem = $oldItem;

        $newItem['qty'] = $this->formatAmount($cant);

        // Calcular valores
        $values = $this->Calculator($newItem['sale_price'], $newItem['qty']);
        $newItem['tax'] = $values['iva'];
        $newItem['total'] = $this->formatAmount($values['total']);

        // Actualizar el carrito
        $this->cart = $this->cart->reject(function ($product) use ($uid, $product_id) {
            return $product['id'] === $uid || $product['pid'] === $product_id;
        });

        // Agregar el nuevo artículo al carrito
        $this->cart->push($newItem);

        // Actualizar la sesión
        session(['cart' => $this->cart->toArray()]);

        // Emitir eventos
        $this->dispatch('refresh');
        $this->dispatch('noty', msg: 'CANTIDAD ACTUALIZADA');
    }

    public function clear()
    {
        $this->cart = new Collection;
        $this->save();
        $this->dispatch('refresh');
    }

    #[On('cancelSale')]
    function cancelSale()
    {
        $this->resetExcept('config', 'banks');
        $this->clear();
        session()->forget('sale_customer');
    }

    public function totalIVA()
    {
        $iva = $this->cart->sum(function ($product) {
            return $product['tax'];
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

    public function totalItems()
    {
        return   $this->cart->count();
    }


    public function subtotalCart()
    {
        $subt = $this->cart->sum(function ($product) {
            return $product['qty'] * $product['sale_price'];
        });
        return $subt;
    }

    public function save()
    {
        session()->put("cart", $this->cart);
        session()->save();
    }

    public function inCart($product_id)
    {
        $mycart = $this->cart;

        $cont = $mycart->where('pid', $product_id)->count();

        return  $cont > 0 ? true : false;
    }

    #[On('sale_customer')]
    function setCustomer($customer)
    {
        session(['sale_customer' => $customer]);
        $this->customer = $customer;
    }


    function initPayment($type)
    {
        $this->payType = $type;

        if ($type == 1) $this->payTypeName = 'PAGO EN EFECTIVO';
        if ($type == 2)   $this->payTypeName = 'PAGO A CRÉDITO';
        if ($type == 3) $this->payTypeName = 'PAGO CON BANCO';
        if ($type == 4) $this->payTypeName = 'PAGO CON NEQUI';

        $this->dispatch('initPay', payType: $type);
    }

    //save sale
    function Store()
    {
        $type = $this->payType;

        //type:  1 = efectivo, 2 = crédito, 3 = depósito
        if (floatval($this->totalCart) <= 0) {
            $this->dispatch('noty', msg: 'AGREGA PRODUCTOS AL CARRITO');
            return;
        }
        if ($this->customer == null) {
            $this->dispatch('noty', msg: 'SELECCIONA EL CLIENTE');
            return;
        }

        if ($type == 1) {

            if (!$this->validateCash()) {
                $this->dispatch('noty', msg: 'EL EFECTIVO ES MENOR AL TOTAL DE LA VENTA');
                return;
            }

            if ($this->nequiAmount > 1 && empty($this->cashAmount)) {
                $this->dispatch('noty', msg: 'DEBE INGRESAR UN MONTO EN EFECTIVO');
                return;
            }

            if ($this->nequiAmount > 1 && empty($this->phoneNumber) < 0) {
                $this->dispatch('noty', msg: 'INGRESA EL NÚMERO DE TELÉFONO');
                return;
            }
        }

        if ($type == 3) {
            if (empty($this->acountNumber)) {
                $this->dispatch('noty', msg: 'INGRESA EL NÚMERO DE CUENTA');
                return;
            }
            if (empty($this->depositNumber)) {
                $this->dispatch('noty', msg: 'INGRESA EL NÚMERO DE DEPÓSITO');
                return;
            }
        }
        if ($type == 4) {
            if (empty($this->phoneNumber)) {
                $this->dispatch('noty', msg: 'INGRESA EL NÚMERO DE TELÉFONO');
                return;
            }
        }

        DB::beginTransaction();
        try {
            //store sale
            $notes = null;

            if ($type == 3) {
                $notes = $this->banks->where('id', $this->bank)->first()->name;
                $notes .= ",N.Cta: {$this->acountNumber}";
                $notes .= ",N.Deposito: {$this->depositNumber}";
            }
            if ($type == 4) {
                $notes = ",N.Teléfono: {$this->phoneNumber}";
            }

            if ($type > 1) $this->cashAmount = 0;

            if ($type == 1 && $this->nequiAmount > 1 && $this->phoneNumber > 0) {
                $notes = "EFECTIVO: {$this->cashAmount}";
                $notes .= ",N.Teléfono: {$this->phoneNumber}";
                $notes .= ",Valor Consignado: {$this->nequiAmount}";
                $type = 5;
            }

            $sale = Sale::create([
                'total' => $this->totalCart,
                'discount' => 0,
                'items' => $this->itemsCart,
                'customer_id' => $this->customer['id'],
                'user_id' => Auth()->user()->id,
                'type' => $type == 1 ? 'cash' : ($type == 2 ? 'credit' : ($type == 3 ? 'deposit' : ($type == 4 ? 'nequi' : 'cash/nequi'))),
                'status' => ($type == 2 ?  'pending' : 'paid'),
                'cash' => $this->cashAmount,
                'change' => $type == 1 ? round((floatval($this->cashAmount) + floatval($this->nequiAmount)) - floatval($this->totalCart())) : 0,
                'notes' => $notes
            ]);

            // get cart session
            $cart = collect(session("cart"));

            // insert sale detail
            $details = $cart->map(function ($item) use ($sale) {
                return [
                    'product_id' => $item['pid'],
                    'sale_id' => $sale->id,
                    'quantity' => $item['qty'],
                    'regular_price' => $item['price2'] ?? 0,
                    'sale_price' => $item['sale_price'],
                    'created_at' => Carbon::now(),
                    'discount' => 0
                ];
            })->toArray();

            SaleDetail::insert($details);

            //update stocks
            foreach ($cart as  $item) {
                Product::find($item['pid'])->decrement('stock_qty', $item['qty']);
            }

            DB::commit();

            $this->dispatch('noty', msg: 'VENTA REGISTRADA CON ÉXITO');
            $this->dispatch('close-modalPay', element: $type == 3 ? 'modalDeposit' : ($type == 4 ? 'modalNequi' : 'modalCash'));
            $this->resetExcept('config', 'banks', 'bank');
            $this->clear();
            session()->forget('sale_customer');

            // mike42
            $this->printSale($sale->id);

            // base64 / printerapp
            $b64 = $this->jsonData($sale->id);

            $this->dispatch('print-json', data: $b64);

            // return redirect()->action(
            //     [Self::class, 'generateInvoice'],
            //     ['sale' => $sale]
            // );
            // return redirect()->name("pos.sales.generateInvoice");
            // return $this->generateInvoice($sale);

        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al intentar guardar la venta \n {$th->getMessage()}");
        }
    }

    function validateCash()
    {
        $total = floatval($this->totalCart);
        $cash = floatval($this->cashAmount);
        $nequi = floatval($this->nequiAmount);
        if ($cash + $nequi < $total) {
            return false;
        }

        return true;
    }


    function storeCustomer()
    {
        $this->resetValidation();
        if (!$this->validaProp($this->cname)) {

            $this->addError('cname', 'INGRESA EL NOMBRE');
            return;
        }
        if (!$this->validaProp($this->ctaxpayerId)) {
            $this->addError('ctaxpayerId', 'INGRESA EL CC/NIT');
            return;
        }
        if (!$this->validaProp($this->caddress)) {
            $this->addError('caddress', 'INGRESA LA DIRECCIÓN');
            return;
        }
        if (!$this->validaProp($this->ccity)) {
            $this->addError('ccity', 'INGRESA LA CIUDA');
            return;
        }

        $customer =  Customer::create([
            'name' => $this->cname,
            'address' => $this->caddress,
            'city' => $this->ccity,
            'email' => $this->cemail,
            'phone' => $this->cphone,
            'taxpayer_id' => $this->ctaxpayerId,
            'type' => $this->ctype
        ]);

        session(['sale_customer' => $customer->toArray()]);
        $this->customer = $customer->toArray();

        $this->reset('cname', 'cphone', 'ctaxpayerId', 'cemail', 'caddress', 'ccity', 'ctype');
        $this->dispatch('close-modal-customer-create');
    }

    function printLast()
    {
        $sale = Sale::latest()->first();
        if ($sale != null && $sale->count() > 0) {
            $this->printSale($sale->id);
        } else {
            $this->dispatch('noty', msg: 'NO HAY VENTAS REGISTRADAS');
        }
    }
}
