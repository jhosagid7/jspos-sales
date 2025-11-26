<?php

namespace App\Livewire;

use Carbon\Carbon;

use App\Models\Bank;
use App\Models\Sale;
use App\Models\Order;
use App\Models\Product;
use Livewire\Component;
use App\Models\Currency;
use App\Models\Customer;
use App\Traits\JsonTrait;
use App\Traits\SaleTrait;
use App\Traits\UtilTrait;
use App\Models\SaleDetail;
use App\Models\SaleChangeDetail;
use App\Models\SalePaymentDetail;
use App\Traits\PrintTrait;
use App\Models\OrderDetail;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Models\Configuration;
use App\Traits\PdfInvoiceTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\PdfOrderInvoiceTrait;
use App\Services\ConfigurationService;
use App\Services\CashRegisterService; // Importar servicio
use Illuminate\Support\Facades\Auth; // Importar Auth

class Sales extends Component
{
    use UtilTrait;
    use PrintTrait;
    use PdfInvoiceTrait;
    use PdfOrderInvoiceTrait;
    use JsonTrait;
    use WithPagination;
    use SaleTrait;

    public Collection $cart;
    public $taxCart = 0, $itemsCart, $subtotalCart = 0, $totalCart = 0, $ivaCart = 0;

    public $config, $customer, $iva = 0;
    //register customer
    public $cname, $caddress, $ccity, $cemail, $cphone, $ctaxpayerId, $ctype = 'Consumidor Final';

    //pay properties
    public $banks, $cashAmount, $nequiAmount, $phoneNumber, $acountNumber, $depositNumber, $bank, $payType = 1, $payTypeName = 'PAGO EN EFECTIVO';

    public $search3, $products = [], $selectedIndex = -1;

    public $order_selected_id, $customer_name, $amount;
    public $order_id, $ordersObt, $order_note, $details = [];
    public $pagination = 5, $status;
    public $confirmation_code = null;

    public $search = '';

    public $payments = []; // Lista de pagos realizados
    public $paymentAmount; // Monto del pago actual
    public $paymentCurrency = 'COP'; // Moneda del pago actual
    public $remainingAmount = 0; // Monto restante por pagar
    public $change = 0; // Cambio en diferentes monedas

    public $totalInPrimaryCurrency = 0; // Suma total en la moneda principal
    public $nequiPhoneNumber;
    public $pagoMovilBank, $pagoMovilPhoneNumber, $pagoMovilReference, $pagoMovilAmount;

    // /**
    //  * @var Collection
    //  */
    // public $currencies = []; // Declarar explícitamente como una colección

    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\Currency>
     */
    public $currencies;

    // Propiedades para distribución de vueltos en múltiples monedas
    public $changeDistribution = []; // Array de vueltos por moneda seleccionados manualmente
    public $selectedChangeCurrency; // Moneda seleccionada para dar vuelto
    public $selectedChangeAmount; // Monto a dar en esa moneda
    public $totalCartAtPayment; // Total del carrito en el momento del pago (para evitar que cambie durante re-renders)



    public function addCashPayment()
    {
        $currency = collect($this->currencies)->firstWhere('code', $this->paymentCurrency);

        if (!$currency) {
            $this->dispatch('noty', msg: 'La moneda seleccionada no está configurada.');
            return;
        }

        $amountInPrimaryCurrency = $this->paymentAmount / $currency->exchange_rate;

        $this->payments[] = [
            'method' => 'cash',
            'amount' => $this->paymentAmount,
            'currency' => $this->paymentCurrency,
            'symbol' => $currency->symbol,
            'exchange_rate' => $currency->exchange_rate,
            'amount_in_primary_currency' => $amountInPrimaryCurrency,
            'details' => null,
        ];
        Log::info('Pagos actuales:', $this->payments); // Depuración
        $this->calculateRemainingAndChange();
        session(['payments' => $this->payments]);
        
        // Guardar el monto restante y el cambio en la sesión
        session(['remainingAmount' => $this->remainingAmount]);
        session(['change' => $this->change]);
    }

    public function addNequiPayment()
    {
        $this->payments[] = [
            'method' => 'nequi',
            'amount' => $this->nequiAmount,
            'currency' => 'COP',
            'symbol' => '$',
            'exchange_rate' => null,
            'amount_in_primary_currency' => null,
            'details' => "Teléfono: {$this->nequiPhoneNumber}",
        ];

        $this->calculateRemainingAndChange();
        session(['payments' => $this->payments]);
        
        // Guardar el monto restante y el cambio en la sesión
        session(['remainingAmount' => $this->remainingAmount]);
        session(['change' => $this->change]);
        
        $this->dispatch('close-modalPay', ['element' => 'modalNequiPartial']);
    }

    public function addPagoMovilPayment()
    {
        $this->payments[] = [
            'method' => 'pago_movil',
            'amount' => $this->pagoMovilAmount,
            'currency' => 'VES',
            'symbol' => 'Bs.',
            'exchange_rate' => null,
            'amount_in_primary_currency' => null,
            'details' => "Banco: {$this->pagoMovilBank}, Teléfono: {$this->pagoMovilPhoneNumber}, Ref: {$this->pagoMovilReference}",
        ];

        $this->calculateRemainingAndChange();
        session(['payments' => $this->payments]);
        
        // Guardar el monto restante y el cambio en la sesión
        session(['remainingAmount' => $this->remainingAmount]);
        session(['change' => $this->change]);
        
        $this->dispatch('close-modal', 'modalPagoMovil');
    }


    public function calculateRemainingAndChange()
    {
        $totalPaid = array_sum(array_column($this->payments, 'amount_in_primary_currency'));
        
        // Usar totalCartAtPayment si existe, sino usar totalCart actual
        $cartTotal = $this->totalCartAtPayment ?? $this->totalCart;

        $this->remainingAmount = max(0, $cartTotal - $totalPaid);
        $this->change = max(0, $totalPaid - $cartTotal);

        Log::info('Cálculo de montos:', [
            'totalCart' => $this->totalCart,
            'totalCartAtPayment' => $this->totalCartAtPayment,
            'cartTotal_used' => $cartTotal,
            'totalPaid' => $totalPaid,
            'remainingAmount' => $this->remainingAmount,
            'change' => $this->change,
        ]);
        $this->calculateTotalInPrimaryCurrency();
    }

    function setCustomPrice($uid, $price)
    {
        $price = trim(str_replace('$', '', $price));

        if (!is_numeric($price)) {
            $this->dispatch('noty', msg: 'EL VALOR DEL PRECIO ES INCORRECTO');
            return;
        }

        $mycart = $this->cart;

        $oldItem = $mycart->where('id', $uid)->first();

        $newItem = $oldItem;
        $newItem['sale_price'] = $price;

        $values = $this->Calculator($newItem['sale_price'], $newItem['qty']);

        $decimals = ConfigurationService::getDecimalPlaces();
        $newItem['tax'] = round($values['iva'], $decimals);
        $newItem['total'] = $this->formatAmount(round($values['total'], $decimals));

        //delete from cart
        $this->cart = $this->cart->reject(function ($product) use ($uid) {
            return $product['id'] === $uid || $product['pid'] === $uid;
        });

        $this->save();

        //add item to cart
        $this->cart->push(Arr::add(
            $newItem,
            null,
            null
        ));

        $this->save();
        $this->dispatch('refresh');
        $this->dispatch('noty', msg: 'PRECIO ACTUALIZADO');
    }

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

            if (is_object($this->products) && count($this->products) == 1 && $this->products->first() !== null && ($this->products->first()->sku == $this->search3)) {
                $this->AddProduct($this->products->first());
                $this->search3 = '';
                $this->products = null; // or $this->products = new \Illuminate\Support\Collection();
                $this->dispatch('refresh');
            }
        } else {
            $this->search3 = '';
            $this->dispatch('refresh');
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
            $decimals = ConfigurationService::getDecimalPlaces();

            if (round(floatval($this->cashAmount), $decimals) >= floatval($this->totalCart)) {
                $this->nequiAmount = null;
                $this->phoneNumber = null;
            }

            $this->change = round(floatval($this->cashAmount) + floatval($this->nequiAmount) - floatval($this->totalCart), $decimals);
        }
    }



    function updatedNequiAmount()
    {
        if (floatval($this->totalCart) > 0) {
            $decimals = ConfigurationService::getDecimalPlaces();
            $this->change = round(floatval($this->cashAmount) + floatval($this->nequiAmount) - floatval($this->totalCart), $decimals);
        }
    }

    function updatedPhoneNumber()
    {
        if (floatval($this->totalCart) > 0 && $this->phoneNumber != '') {
            $decimals = ConfigurationService::getDecimalPlaces();
            $this->change = round(floatval($this->cashAmount) + floatval($this->nequiAmount) - floatval($this->totalCart), $decimals);
        } else {
            $decimals = ConfigurationService::getDecimalPlaces();
            $this->change = round(floatval($this->cashAmount) - floatval($this->totalCart), $decimals);
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

        $this->amount = null;
        $this->search = null;
        $this->order_selected_id = null;
        $this->status = 'pending';
        $this->order_id = null;

        $this->loadCurrencies(); // Cargar monedas disponibles

        // Cargar los pagos desde la sesión si existen
        $this->payments = session()->has('payments') ? session('payments') : [];

        // Cargar changeDistribution desde la sesión si existe
        $this->changeDistribution = session()->has('changeDistribution') ? session('changeDistribution') : [];

        // Cargar el monto restante desde la sesión si existe
        $this->remainingAmount = session()->has('remainingAmount') ? session('remainingAmount') : $this->totalCart;

        // Cargar el cambio desde la sesión si existe
        $this->change = session()->has('change') ? session('change') : 0;

        $this->calculateTotalInPrimaryCurrency();





        // Establecer la moneda principal como la seleccionada por defecto
        $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
        $this->paymentCurrency = $primaryCurrency ? $primaryCurrency->code : null;
    }
    
    public function hydrate()
    {
        // Este método se ejecuta en cada request de Livewire (después de mount)
        // Restaurar el change desde la sesión si existe
        if (session()->has('change')) {
            $this->change = session('change');
            Log::info('HYDRATE - Change restaurado desde sesión:', ['change' => $this->change]);
            // Verificar inmediatamente que el valor se mantuvo
            Log::info('HYDRATE - Verificación inmediata:', ['change_verificado' => $this->change]);
        } else {
            Log::info('HYDRATE - No hay change en sesión');
        }
        
        // Restaurar changeDistribution desde la sesión si existe
        if (session()->has('changeDistribution')) {
            $this->changeDistribution = session('changeDistribution');
        }
        
        // Restaurar payments desde la sesión si existe
        if (session()->has('payments')) {
            $this->payments = session('payments');
        }
        
        // Restaurar remainingAmount desde la sesión si existe
        if (session()->has('remainingAmount')) {
            $this->remainingAmount = session('remainingAmount');
        }
        
        // Restaurar totalCartAtPayment desde la sesión si existe
        if (session()->has('totalCartAtPayment')) {
            $this->totalCartAtPayment = session('totalCartAtPayment');
        }
        
        Log::info('HYDRATE - Final:', [
            'change' => $this->change,
            'payments_count' => count($this->payments),
            'totalCartAtPayment' => $this->totalCartAtPayment
        ]);
    }

    public function render()
    {
        // FAILSAFE: Si change es 0 pero hay un valor en sesión, restaurarlo
        // Esto soluciona un problema del ciclo de vida de Livewire donde el valor se pierde entre hydrate() y render()
        if ($this->change == 0 && session()->has('change') && session('change') > 0) {
            $this->change = session('change');
            Log::info('RENDER - FAILSAFE activado, change restaurado:', ['change' => $this->change]);
        }
        
        Log::info('RENDER - Inicio:', [
            'change' => $this->change,
            'changeDistribution_count' => count($this->changeDistribution),
            'payments_count' => count($this->payments)
        ]);
        
        $decimals = ConfigurationService::getDecimalPlaces();

        $this->cart = $this->cart->sortByDesc('id');
        $this->taxCart = round($this->totalIVA(), $decimals);
        $this->itemsCart = $this->totalItems();
        $this->totalCart = round($this->totalCart(), $decimals);
        if ($this->config->vat > 0) {
            $this->iva = $this->config->vat / 100;
            $this->subtotalCart = round($this->subtotalCart() / (1 + $this->iva), $decimals);
            $this->ivaCart = round(($this->totalCart() / (1 + $this->iva)) * $this->iva, $decimals);
        } else {
            $this->iva = $this->config->vat;
            $this->subtotalCart = round($this->subtotalCart(), $decimals);
            $this->ivaCart = round(0, $decimals);
        }


        $this->customer = session('sale_customer', null);
        $orders = $this->getOrdersWithDetails();
        return view(
            'livewire.pos.sales',
            compact('orders')
        );
    }

    public function loadCurrencies()
    {
        $this->currencies = Currency::orderBy('is_primary', 'desc')->get();
        Log::info('Monedas cargadas:', $this->currencies->toArray()); // Depuración
        $primaryCurrency = $this->currencies->firstWhere('is_primary', true);
        $this->paymentCurrency = $primaryCurrency ? $primaryCurrency->code : null;
    }



    public function addPayment()
    {
        // Guardar el total del carrito en el primer pago
        if (empty($this->payments)) {
            $this->totalCartAtPayment = $this->totalCart;
            session(['totalCartAtPayment' => $this->totalCartAtPayment]);
        }
        
        // Buscar la moneda seleccionada en la lista de monedas disponibles
        $currency = collect($this->currencies)->firstWhere('code', $this->paymentCurrency);

        if (!$currency) {
            // Mostrar un mensaje si la moneda seleccionada no está configurada
            $this->dispatch('noty', msg: 'La moneda seleccionada no está configurada.');
            return;
        }

        // Verificar que la tasa de cambio sea válida
        if (!isset($currency->exchange_rate) || $currency->exchange_rate <= 0) {
            $this->dispatch('noty', msg: 'La tasa de cambio para la moneda seleccionada no es válida.');
            return;
        }

        // Calcular el monto en dólares (moneda base)
        $amountInUSD = $this->paymentAmount / $currency->exchange_rate;

        // Calcular el monto en la moneda principal
        $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
        $amountInPrimaryCurrency = $amountInUSD * $primaryCurrency->exchange_rate;

        // Verificar si ya existe un abono en la misma moneda
        $existingPaymentIndex = collect($this->payments)->search(function ($payment) {
            return $payment['currency'] === $this->paymentCurrency;
        });

        if ($existingPaymentIndex !== false) {
            // Si ya existe un abono en la misma moneda, sumar el monto
            $this->payments[$existingPaymentIndex]['amount'] += $this->paymentAmount;
            $this->payments[$existingPaymentIndex]['amount_in_primary_currency'] += $amountInPrimaryCurrency;
        } else {
            // Si no existe, agregar un nuevo registro
            $this->payments[] = [
                'method' => 'cash', // Tipo de pago
                'amount' => $this->paymentAmount, // Monto ingresado
                'currency' => $this->paymentCurrency, // Moneda seleccionada
                'symbol' => $currency->symbol, // Símbolo de la moneda
                'exchange_rate' => $currency->exchange_rate, // Tasa de cambio
                'amount_in_primary_currency' => $amountInPrimaryCurrency, // Monto convertido a la moneda principal
                'details' => null, // No hay detalles adicionales para efectivo
            ];
        }

        // Guardar los pagos en la sesión
        session(['payments' => $this->payments]);

        // Actualizar el monto restante y el cambio
        $this->calculateRemainingAndChange();

        // Guardar el monto restante y el cambio en la sesión
        session(['remainingAmount' => $this->remainingAmount]);
        session(['change' => $this->change]);

        // Registrar en los logs para depuración
        Log::info('Pagos realizados:', $this->payments);

        // Limpiar el monto ingresado
        $this->paymentAmount = null;
    }

    // public function removePayment($index)
    // {
    //     if (isset($this->payments[$index])) {
    //         // Eliminar el pago del array
    //         unset($this->payments[$index]);

    //         // Reindexar el array para evitar problemas con índices desordenados
    //         $this->payments = array_values($this->payments);

    //         // Actualizar la variable de sesión
    //         session(['payments' => $this->payments]);

    //         // Actualizar el monto restante y el cambio
    //         $this->calculateRemainingAndChange();

    //         // Guardar el monto restante en la sesión
    //         session(['remainingAmount' => $this->remainingAmount]);

    //         // Registrar en los logs para depuración
    //         Log::info('Pago eliminado. Pagos actuales:', $this->payments);
    //         Log::info('Monto restante actualizado:', ['remainingAmount' => $this->remainingAmount]);
    //     }
    // }

    public function removePayment($index)
    {
        if (isset($this->payments[$index])) {
            unset($this->payments[$index]);
            $this->payments = array_values($this->payments);

            session(['payments' => $this->payments]);
            $this->calculateRemainingAndChange();
            
            // Guardar el monto restante y el cambio en la sesión
            session(['remainingAmount' => $this->remainingAmount]);
            session(['change' => $this->change]);
        }
    }


    public function calculateTotalInPrimaryCurrency()
    {
        $this->totalInPrimaryCurrency = 0;

        foreach ($this->payments as $payment) {
            // Buscar la moneda del pago
            $currency = collect($this->currencies)->firstWhere('code', $payment['currency']);

            if ($currency && isset($payment['amount'])) {
                // Si el monto en la moneda principal ya está definido, úsalo
                if (isset($payment['amount_in_primary_currency']) && $payment['amount_in_primary_currency'] > 0) {
                    $this->totalInPrimaryCurrency += $payment['amount_in_primary_currency'];
                } else {
                    // Si no está definido, calcula la conversión
                    $this->totalInPrimaryCurrency += $payment['amount'] / $currency->exchange_rate;
                }
            }
        }
    }

    public function openNequiModal()
    {
        $this->dispatch('initPay', ['payType' => 4]); // Emite el evento para abrir el modal de Nequi
    }

    public function openNequiPartialModal()
    {
        $this->dispatch('initPay', ['payType' => 'nequi_partial']); // Emite el evento para abrir el modal de abonos parciales con Nequi
    }
    
    public function updatedPaymentAmount()
    {
        // Cuando cambia el monto del pago, NO recalcular el change
        // El change solo debe recalcularse cuando se agrega o elimina un pago
        // Esto evita que el change se resetee mientras el usuario escribe
    }
    
    public function updatedPaymentCurrency()
    {
        // Cuando cambia la moneda del pago, NO recalcular el change
        // El change solo debe recalcularse cuando se agrega o elimina un pago
    }
    
    public function updatedPayments()
    {
        $this->calculateTotalInPrimaryCurrency();
    }

    public function calculateChange()
    {
        $totalPaidInPrimaryCurrency = array_sum(array_column($this->payments, 'amount_in_primary_currency'));
        $changeInPrimaryCurrency = $totalPaidInPrimaryCurrency - $this->totalCart;

        $this->change = 0;

        foreach ($this->currencies as $currency) {
            $this->change[$currency->code] = $changeInPrimaryCurrency * $currency->exchange_rate;
        }
    }

    public function addChangeInCurrency(CashRegisterService $cashRegisterService)
    {
        if ($this->selectedChangeAmount > 0 && $this->selectedChangeCurrency) {
            $currency = collect($this->currencies)->firstWhere('code', $this->selectedChangeCurrency);
            $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);

            // Validar disponibilidad en caja
            $register = $cashRegisterService->getActiveCashRegister(Auth::id());
            $validation = $cashRegisterService->validateChangeAvailability(
                $register->id, 
                $this->selectedChangeCurrency, 
                $this->selectedChangeAmount
            );

            if (!$validation['valid']) {
                $this->dispatch('noty', msg: "Saldo insuficiente en {$currency->symbol}. Disponible: " . number_format($validation['current_balance'], 2));
                return;
            }

            // Calcular el monto equivalente en la moneda principal
            $amountInPrimaryCurrency = 0;
        }
        if (!$this->selectedChangeCurrency || !$this->selectedChangeAmount) {
            $this->dispatch('noty', msg: 'Selecciona una moneda y monto para el vuelto');
            return;
        }

        $currency = collect($this->currencies)->firstWhere('code', $this->selectedChangeCurrency);
        
        if (!$currency) {
            $this->dispatch('noty', msg: 'Moneda no válida');
            return;
        }

        // CORRECCIÓN: Convertir correctamente a moneda principal
        // Si la moneda seleccionada es la principal, el monto ya está en moneda principal
        $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
        
        if ($currency->is_primary) {
            // Si es la moneda principal, no hay conversión necesaria
            $amountInPrimaryCurrency = $this->selectedChangeAmount;
        } else {
            // Si es una moneda secundaria, convertir vía USD
            $amountInUSD = $this->selectedChangeAmount / $currency->exchange_rate;
            $amountInPrimaryCurrency = $amountInUSD * $primaryCurrency->exchange_rate;
        }
        
        // Calcular cuánto vuelto ya se ha asignado
        $totalAssignedChange = array_sum(array_column($this->changeDistribution, 'amount_in_primary_currency'));
        
        // Calcular el vuelto total disponible
        $totalPaidInPrimaryCurrency = array_sum(array_column($this->payments, 'amount_in_primary_currency'));
        $totalChangeAvailable = $totalPaidInPrimaryCurrency - $this->totalCart;
        
        // Validar que no se exceda el vuelto disponible
        if (($totalAssignedChange + $amountInPrimaryCurrency) > $totalChangeAvailable) {
            $this->dispatch('noty', msg: 'El vuelto asignado excede el vuelto disponible');
            return;
        }

        // Agregar el vuelto a la distribución
        $this->changeDistribution[] = [
            'currency' => $this->selectedChangeCurrency,
            'symbol' => $currency->symbol,
            'amount' => $this->selectedChangeAmount,
            'exchange_rate' => $currency->exchange_rate,
            'amount_in_primary_currency' => $amountInPrimaryCurrency,
        ];

        // Guardar en sesión para persistencia
        session(['changeDistribution' => $this->changeDistribution]);

        // Limpiar campos
        $this->selectedChangeCurrency = null;
        $this->selectedChangeAmount = null;
        
        $this->dispatch('noty', msg: 'Vuelto agregado correctamente');
    }

    public function removeChangeDistribution($index)
    {
        if (isset($this->changeDistribution[$index])) {
            unset($this->changeDistribution[$index]);
            $this->changeDistribution = array_values($this->changeDistribution); // Reindexar
            
            // Actualizar sesión
            session(['changeDistribution' => $this->changeDistribution]);
            
            $this->dispatch('noty', msg: 'Vuelto eliminado');
        }
    }

    public function getRemainingChangeToAssign()
    {
        $totalPaidInPrimaryCurrency = array_sum(array_column($this->payments, 'amount_in_primary_currency'));
        
        // Usar totalCartAtPayment si existe, sino usar totalCart actual
        $cartTotal = $this->totalCartAtPayment ?? $this->totalCart;
        
        $totalChangeAvailable = $totalPaidInPrimaryCurrency - $cartTotal;
        $totalAssignedChange = array_sum(array_column($this->changeDistribution, 'amount_in_primary_currency'));
        
        return max(0, $totalChangeAvailable - $totalAssignedChange);
    }

    public function loadOrderToCart($orderId)
    {
        //limpiamos el carrito

        $this->resetExcept('config', 'banks', 'bank');
        $this->clear();
        session()->forget('sale_customer');

        //Cargar el nombre del cliente
        $order = Order::find($orderId);
        // //cargar el objeto costumer
        $customer = Customer::find($order->customer_id);


        $this->setCustomer($customer);
        $this->order_id = $orderId;
        // session(['sale_customer' => $order->customer->name]);

        // Obtener los detalles de la orden
        $orderDetails = OrderDetail::where('order_id', $orderId)->get();

        foreach ($orderDetails as $detail) {
            // Obtener el producto correspondiente
            $product = Product::find($detail->product_id);

            // Llamar al método AddProduct con los detalles del producto y la cantidad
            $this->AddProduct($product, $detail->quantity);
        }

        $this->dispatch('close-process-order');
    }

    public function getOrdersWithDetails()
    {
        if (empty(trim($this->search))) {
            return Order::whereHas('customer')
                ->where('status', 'pending')
                ->orderBy('orders.id', 'desc')
                ->paginate($this->pagination);
        } else {
            $search = strtolower(trim($this->search));

            return Order::where(function ($query) use ($search) {
                // Búsqueda por el nombre del cliente
                $query->whereHas('customer', function ($subQuery) use ($search) {
                    $subQuery->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"]);
                });

                // Búsqueda por el ID de la orden
                $query->orWhere('id', 'LIKE', "%{$search}%");

                // Búsqueda por el total
                $query->orWhere('total', 'LIKE', "%{$search}%");

                // Búsqueda por el usuario (suponiendo que tienes una relación 'user' en Order)
                $query->orWhereHas('user', function ($subQuery) use ($search) {
                    $subQuery->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"]); // Cambia 'name' por el campo que corresponda en tu modelo User
                });
            })
                ->where('status', $this->status)
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);
        }
    }

    function getOrderDetail(Order $order)
    {
        $this->ordersObt = $order;
        $this->order_id = $order->id;
        $this->details = $order->details;
        // dd($this->details);
        $this->dispatch('show-detail');
    }

    function getOrderDetailNote(Order $order)
    {
        $this->ordersObt = $order;
        $this->order_id = $order->id;
        $this->details = $order->details;
        $this->order_note = $order->notes; // Populate the sale_note property
        $this->dispatch('show-detail-note');
    }

    public function saveOrderNote()
    {
        $this->validate([
            'order_note' => 'nullable|string',
        ]);

        $this->ordersObt->update([
            'notes' => $this->order_note,
        ]);

        $this->dispatch('noty', msg: 'Nota de la orden actualizada correctamente');
        $this->dispatch('close-detail-note'); // Close the modal
        return;
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

        // Obtener el número de decimales configurados
        $decimals = ConfigurationService::getDecimalPlaces();

        // Obtener el IVA desde la configuración
        $iva = ConfigurationService::getVat() / 100;

        // Determinamos el precio de venta (con IVA)
        if ($iva > 0) {
            // Precio unitario sin IVA
            $precioUnitarioSinIva =  $salePrice / (1 + $iva);
            // Subtotal neto
            $subtotalNeto =   $precioUnitarioSinIva * $this->formatAmount($qty);
            // Monto IVA
            $montoIva = $subtotalNeto  * $iva;
            // Total con IVA
            $totalConIva =  $subtotalNeto + $montoIva;

            $tax = round($montoIva, $decimals);
            $total = round($totalConIva, $decimals);
        } else {
            // Precio unitario sin IVA
            $precioUnitarioSinIva =  $salePrice;
            // Subtotal neto
            $subtotalNeto =   $precioUnitarioSinIva * $this->formatAmount($qty);
            // Monto IVA
            $montoIva = 0;
            // Total con IVA
            $totalConIva =  $subtotalNeto + $montoIva;

            $tax = round($montoIva, $decimals);
            $total = round($totalConIva, $decimals);
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
        // Obtener el número de decimales configurados
        $decimals = ConfigurationService::getDecimalPlaces();

        // Obtener el IVA desde la configuración
        $iva = ConfigurationService::getVat() / 100;

        // Determinamos el precio de venta (con IVA)
        $salePrice = $price;
        // Precio unitario sin IVA
        $precioUnitarioSinIva =  $salePrice / (1 + $iva);
        // Subtotal neto
        $subtotalNeto =   $precioUnitarioSinIva * round(floatval($qty), $decimals);
        // Monto IVA
        $montoIva = $subtotalNeto  * $iva;
        // Total con IVA
        $totalConIva =  $subtotalNeto + $montoIva;

        return [
            'sale_price' => round($salePrice, $decimals),
            'neto' => round($subtotalNeto, $decimals),
            'iva' => round($montoIva, $decimals),
            'total' => round($totalConIva, $decimals)
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
        $decimals = ConfigurationService::getDecimalPlaces();
        $newItem['tax'] = round($values['iva'], $decimals);
        $newItem['total'] = $this->formatAmount(round($values['total'], $decimals));

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

        // Limpiar TODAS las sesiones de pago (sin condiciones)
        session()->forget('payments');
        session()->forget('changeDistribution');
        session()->forget('remainingAmount');
        session()->forget('change');
        session()->forget('totalCartAtPayment');
        
        // Resetear propiedades
        $this->payments = [];
        $this->changeDistribution = [];
        $this->remainingAmount = 0;
        $this->change = 0;
        $this->totalCartAtPayment = null;

        $this->dispatch('noty', msg: 'Venta cancelada y datos limpiados.');
        
        $this->loadCurrencies();

        Log::info('Venta cancelada - Todas las sesiones limpiadas');
    }

    public function totalIVA()
    {
        $decimals = ConfigurationService::getDecimalPlaces();
        $iva = $this->cart->sum(function ($product) {
            return $product['tax'];
        });
        return round($iva, $decimals);
    }

    public function totalCart()
    {
        $decimals = ConfigurationService::getDecimalPlaces();
        $amount = $this->cart->sum(function ($product) {
            return $product['total'];
        });
        return round($amount, $decimals);
    }

    public function totalItems()
    {
        return   $this->cart->count();
    }

    public function subtotalCart()
    {
        $decimals = ConfigurationService::getDecimalPlaces();
        $subt = $this->cart->sum(function ($product) {
            return $product['qty'] * $product['sale_price'];
        });
        return round($subt, $decimals);
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
        // dd($customer);
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

    function Store(CashRegisterService $cashRegisterService)
    {
        // dd($this);

        // dd($this->totalInPrimaryCurrency);
        // dd(get_object_vars($this));
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
                $this->dispatch('noty', msg: 'EL MONTO PAGADO ES MENOR AL TOTAL DE LA VENTA');
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
                $notes = $this->banks->where(
                    'id',
                    $this->bank
                )->first()->name;
                $notes .= ",N.Cta: {$this->acountNumber}";
                $notes .= ",N.Deposito: {$this->depositNumber}";
            }
            if ($type == 4) {
                $notes = ",N.Teléfono: {$this->phoneNumber}";
            }

            // Calcular el total pagado y el cambio usando el nuevo sistema de pagos múltiples
            $totalPaidInPrimaryCurrency = $type == 1 ? round($this->totalInPrimaryCurrency, ConfigurationService::getDecimalPlaces()) : 0;
            $changeAmount = $type == 1 ? round($this->change, ConfigurationService::getDecimalPlaces()) : 0;

            if ($type > 1) $this->cashAmount = 0;

            if ($type == 1 && $this->nequiAmount > 1 && $this->phoneNumber > 0) {
                $notes = "EFECTIVO: {$this->cashAmount}";
                $notes .= ",N.Teléfono: {$this->phoneNumber}";
                $notes .= ",Valor Consignado: {$this->nequiAmount}";
                $type = 5;
            }

            $decimals = ConfigurationService::getDecimalPlaces();

            $sale = Sale::create([
                'total' => round($this->totalCart, $decimals),
                'discount' => 0,
                'items' => $this->itemsCart,
                'customer_id' => $this->customer['id'],
                'user_id' => Auth()->user()->id,
                'type' => $type == 1 ? 'cash' : ($type == 2 ? 'credit' : ($type == 3 ? 'deposit' : ($type == 4 ? 'nequi' : 'cash/nequi'))),
                'status' => ($type == 2 ?  'pending' : 'paid'),
                'cash' => $totalPaidInPrimaryCurrency,
                'change' => $changeAmount,
                'notes' => $notes
            ]);

            // get cart session
            $cart = collect(session("cart"));

            // insert sale detail
            $details = $cart->map(function ($item) use ($sale, $decimals) {
                return [
                    'product_id' => $item['pid'],
                    'sale_id' => $sale->id,
                    'quantity' => round($item['qty'], $decimals),
                    'regular_price' => round(
                        $item['price2'] ?? 0,
                        $decimals
                    ),
                    'sale_price' => round($item['sale_price'], $decimals),
                    'created_at' => Carbon::now(),
                    'discount' => 0
                ];
            })->toArray();

            SaleDetail::insert($details);

            //update stocks
            foreach ($cart as  $item) {
                Product::find($item['pid'])->decrement('stock_qty', $item['qty']);
            }

            // Guardar detalles de vueltos en múltiples monedas
            if (!empty($this->changeDistribution)) {
                foreach ($this->changeDistribution as $changeDetail) {
                    SaleChangeDetail::create([
                        'sale_id' => $sale->id,
                        'currency_code' => $changeDetail['currency'],
                        'amount' => $changeDetail['amount'],
                        'exchange_rate' => $changeDetail['exchange_rate'],
                        'amount_in_primary_currency' => $changeDetail['amount_in_primary_currency'],
                    ]);
                }
            }

            // Guardar detalles de pagos en múltiples monedas
        if ($type == 1) {
            if (!empty($this->payments)) {
                foreach ($this->payments as $payment) {
                    SalePaymentDetail::create([
                        'sale_id' => $sale->id,
                        'currency_code' => $payment['currency'],
                        'amount' => $payment['amount'],
                        'exchange_rate' => $payment['exchange_rate'] ?? 1,
                        'amount_in_primary_currency' => $payment['amount_in_primary_currency'],
                    ]);
                }
            } else {
                // Si no hay pagos detallados, es un pago simple en efectivo
                // Asumimos moneda principal si no se especifica otra (aunque debería venir del input si hubiera selector)
                // Para este caso simple, usamos la moneda principal configurada
                $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
                $currencyCode = $primaryCurrency ? $primaryCurrency->code : 'COP';
                
                SalePaymentDetail::create([
                    'sale_id' => $sale->id,
                    'currency_code' => $currencyCode,
                    'amount' => $this->cashAmount, // El monto ingresado en el input simple
                    'exchange_rate' => 1,
                    'amount_in_primary_currency' => $this->cashAmount,
                ]);
            }
        } elseif ($type == 3) { // Depósito Bancario
            $bank = $this->banks->where('id', $this->bank)->first();
            $currencyCode = $bank->currency_code ?? 'USD'; // Default to USD if not set
            // Buscar la tasa de cambio de la moneda del banco
            $currency = collect($this->currencies)->firstWhere('code', $currencyCode);
            $exchangeRate = $currency ? $currency->exchange_rate : 1;
            
            // Si la moneda del banco no es la principal, convertir el monto
            // Asumimos que totalCart está en moneda principal
            $amount = $this->totalCart * $exchangeRate;

            SalePaymentDetail::create([
                'sale_id' => $sale->id,
                'currency_code' => $currencyCode,
                'bank_name' => $bank ? $bank->name : null, // Guardar nombre del banco
                'amount' => $amount, // Monto en la moneda del banco
                'exchange_rate' => $exchangeRate,
                'amount_in_primary_currency' => $this->totalCart,
            ]);
        } elseif ($type == 4) { // Nequi
            // Nequi siempre es en Pesos (COP)
            $currencyCode = 'COP';
            $currency = collect($this->currencies)->firstWhere('code', $currencyCode);
            $exchangeRate = $currency ? $currency->exchange_rate : 1;
            
            $amount = $this->totalCart * $exchangeRate;

            SalePaymentDetail::create([
                'sale_id' => $sale->id,
                'currency_code' => $currencyCode,
                'amount' => $amount,
                'exchange_rate' => $exchangeRate,
                'amount_in_primary_currency' => $this->totalCart,
            ]);
        }          

            // Registrar movimientos de caja
            $register = $cashRegisterService->getActiveCashRegister(Auth::id());
            if ($register) {
                // Registrar pagos
                if ($type == 1) { // Solo si es pago en efectivo (o mixto con efectivo)
                    if (!empty($this->payments)) {
                        foreach ($this->payments as $payment) {
                            $cashRegisterService->recordSaleMovement(
                                $register->id,
                                $sale->id,
                                'sale_payment',
                                $payment['currency'],
                                $payment['amount'],
                                "Venta #{$sale->id}"
                            );
                        }
                    } else {
                        // Caso simple: pago efectivo sin desglose múltiple
                        $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
                        $currencyCode = $primaryCurrency ? $primaryCurrency->code : 'COP';
                        
                        $cashRegisterService->recordSaleMovement(
                            $register->id,
                            $sale->id,
                            'sale_payment',
                            $currencyCode,
                            $this->cashAmount,
                            "Venta #{$sale->id}"
                        );
                    }
                }

                // Registrar vueltos
                if ($this->change > 0) {
                    if (!empty($this->changeDistribution)) {
                        foreach ($this->changeDistribution as $changeItem) {
                            $cashRegisterService->recordSaleMovement(
                                $register->id,
                                $sale->id,
                                'sale_change',
                                $changeItem['currency'],
                                -$changeItem['amount'], // Negativo porque sale de caja
                                "Vuelto Venta #{$sale->id}"
                            );
                        }
                    } else {
                        // Si hay cambio pero no distribución, registrar en moneda principal (o la del pago)
                        // Por defecto moneda principal
                        $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
                        $currencyCode = $primaryCurrency ? $primaryCurrency->code : 'COP';
                        
                        $cashRegisterService->recordSaleMovement(
                            $register->id,
                            $sale->id,
                            'sale_change',
                            $currencyCode,
                            -$this->change,
                            "Vuelto Venta #{$sale->id}"
                        );
                    }
                }
            }

            DB::commit();

            // Limpiar la variable de sesión
            session()->forget('payments');

            $this->order_id ? $this->UpdateStatusOrder($this->order_id, 'processed') : '';

            $this->dispatch('noty', msg: 'VENTA REGISTRADA CON ÉXITO');
            $this->dispatch('close-modalPay', element: $type == 3 ? 'modalDeposit' : ($type == 4 ? 'modalNequi' : 'modalCash'));
            $this->resetExcept('config', 'banks', 'bank');
            $this->clear();
            session()->forget('sale_customer');
            
            // Limpiar sesiones de pagos y vueltos después de venta exitosa
            session()->forget('payments');
            session()->forget('changeDistribution');
            session()->forget('remainingAmount');
            session()->forget('change');
            session()->forget('totalCartAtPayment');

            // mike42
            $this->printSale($sale->id);

            // base64 / printerapp
            $b64 = $this->jsonData($sale->id);

            $this->dispatch(
                'print-json',
                data: $b64
            );
        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al intentar guardar la venta \n {$th->getMessage()}");
        }
    }

    #[On('storeOrder')]
    public function storeOrder()
    {
        DB::beginTransaction();
        try {
            Log::info('Antes de guardar la orden:', $this->currencies->toArray());
            //store sale
            if (floatval($this->totalCart) <= 0) {
                $this->dispatch('noty', msg: 'AGREGA PRODUCTOS AL CARRITO');
                return;
            }
            if ($this->customer == null) {
                $this->dispatch('noty', msg: 'SELECCIONA EL CLIENTE');
                return;
            }
            $notes = null;

            $decimals = ConfigurationService::getDecimalPlaces();

            if ($this->order_id) {
                // Actualiza la orden existente
                $order = Order::find($this->order_id);

                if ($order) {
                    $order->update([
                        'total' => round($this->totalCart, $decimals),
                        'discount' => 0,
                        'items' => $this->itemsCart,
                        'customer_id' => $this->customer['id'],
                        'user_id' => Auth()->user()->id,
                        'status' => 'pending'
                    ]);

                    // Actualiza los detalles de la orden
                    $cart = collect(session("cart"));
                    $details = $cart->map(function ($item) use ($order, $decimals) {
                        return [
                            'product_id' => $item['pid'],
                            'order_id' => $order->id,
                            'quantity' => round($item['qty'], $decimals),
                            'regular_price' => round($item['price1'] ?? 0, $decimals),
                            'sale_price' => round($item['sale_price'], $decimals),
                            'created_at' => Carbon::now(),
                            'discount' => 0
                        ];
                    })->toArray();

                    // Elimina los detalles existentes antes de insertar los nuevos
                    OrderDetail::where('order_id', $order->id)->delete();
                    OrderDetail::insert($details);
                }
            } else {
                // Crea una nueva orden
                $order = Order::create([
                    'total' => round($this->totalCart, $decimals),
                    'discount' => 0,
                    'items' => $this->itemsCart,
                    'customer_id' => $this->customer['id'],
                    'user_id' => Auth()->user()->id,
                    'status' => 'pending'
                ]);

                // Obtiene el carrito de la sesión
                $cart = collect(session("cart"));

                // Inserta los detalles de la venta
                $details = $cart->map(function ($item) use ($order, $decimals) {
                    return [
                        'product_id' => $item['pid'],
                        'order_id' => $order->id,
                        'quantity' => round($item['qty'], $decimals),
                        'regular_price' => round($item['price1'] ?? 0, $decimals),
                        'sale_price' => round($item['sale_price'], $decimals),
                        'created_at' => Carbon::now(),
                        'discount' => 0
                    ];
                })->toArray();

                OrderDetail::insert($details);
            }

            DB::commit();

            $this->dispatch('noty', msg: 'ORDEN GUARDADA CON ÉXITO');

            $this->resetExcept('config', 'banks', 'bank');
            $this->clear();
            session()->forget('sale_customer');
            $this->loadCurrencies();

            Log::info('Después de guardar la orden:', $this->currencies->toArray());
        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al intentar guardar la orden \n {$th->getMessage()}");
        }
    }

    function validateCash()
    {
        $decimals = ConfigurationService::getDecimalPlaces();
        $total = round(floatval($this->totalCart), $decimals);
        
        // Validar usando el nuevo sistema de pagos múltiples
        $totalPaid = round(floatval($this->totalInPrimaryCurrency), $decimals);
        
        if ($totalPaid < $total) {
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
    #[On('printLast')]
    function printLast()
    {
        $sale = Sale::latest()->first();
        if ($sale != null && $sale->count() > 0) {
            $this->printSale($sale->id);
        } else {
            $this->dispatch('noty', msg: 'NO HAY VENTAS REGISTRADAS');
        }
    }

    #[On('DestroyOrder')]
    public function DestroyOrder($orderId)
    {
        $orderId ? $this->UpdateStatusOrder($orderId, 'deleted') : '';
    }

    public function UpdateStatusOrder($orderId = null, $status)
    {
        try {
            DB::beginTransaction();

            $order = Order::findOrFail($orderId);
            if ($status == 'deleted') {

                $order->update([
                    'status' => $status,
                    'deleted_at' => Carbon::now(),
                ]);
                $msg = 'eliminada';
            }
            if ($status == 'processed') {

                $order->update([
                    'status' => $status,
                ]);
                $msg = 'procesada';
            }


            DB::commit();


            $this->dispatch('noty', msg: "Orden $msg correctamente");
        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al intentar $status la orden \n {$th->getMessage()}");
        }
    }
}
