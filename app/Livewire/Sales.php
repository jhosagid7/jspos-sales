<?php

namespace App\Livewire;

use Carbon\Carbon;

use App\Models\Bank;
use App\Models\Sale;
use App\Models\Order;
use App\Models\Product;
use App\Models\ZelleRecord;
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
use App\Helpers\CurrencyHelper; // Importar Helper

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
    public $sellerConfig = null; // Store active seller config
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
    
    // Propiedades para Modal Unificado
    public $selectedPaymentMethod = 'cash'; // 'cash', 'bank', 'nequi'
    
    // Datos Banco
    public $bankId;
    public $bankAccountNumber;
    public $bankDepositNumber;
    public $bankAmount;
    
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

    public $applyCommissions = false; // Toggle for applying commissions

    public function updatedApplyCommissions($value)
    {
        session(['applyCommissions' => $value]);
        $this->recalculateCartWithSellerConfig();
        $this->dispatch('noty', msg: $value ? 'Comisiones Activadas' : 'Comisiones Desactivadas');
    }



    public function addPayment()
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentCurrency' => 'required',
        ]);

        $currency = collect($this->currencies)->firstWhere('code', $this->paymentCurrency);

        if (!$currency) {
            $this->dispatch('noty', msg: 'La moneda seleccionada no está configurada.');
            return;
        }

        // Convertir a USD (base) y luego a moneda principal
        $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
        $amountInUSD = $this->paymentAmount / $currency->exchange_rate;
        $amountInPrimaryCurrency = $amountInUSD * $primaryCurrency->exchange_rate;

        $this->payments[] = [
            'method' => 'cash',
            'amount' => $this->paymentAmount,
            'currency' => $this->paymentCurrency,
            'symbol' => $currency->symbol,
            'exchange_rate' => $currency->exchange_rate,
            'amount_in_primary_currency' => $amountInPrimaryCurrency,
            'details' => null,
        ];
        
        $this->calculateRemainingAndChange();
        session(['payments' => $this->payments]);
        session(['remainingAmount' => $this->remainingAmount]);
        session(['change' => $this->change]);
        
        $this->reset(['paymentAmount']);
    }

    public function addBankPayment()
    {
        $this->validate([
            'bankId' => 'required',
            'bankAccountNumber' => 'required',
            'bankDepositNumber' => 'required',
            'bankAmount' => 'required|numeric|min:0.01',
        ]);

        $bank = Bank::find($this->bankId);
        if (!$bank) {
            $this->dispatch('noty', msg: 'Banco no encontrado.');
            return;
        }

        $currency = collect($this->currencies)->firstWhere('code', $bank->currency_code);

        if (!$currency) {
            $this->dispatch('noty', msg: 'La moneda del banco no está configurada.');
            return;
        }

        // Convertir a USD (base) y luego a moneda principal
        $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
        $amountInUSD = $this->bankAmount / $currency->exchange_rate;
        $amountInPrimaryCurrency = $amountInUSD * $primaryCurrency->exchange_rate;

        $this->payments[] = [
            'method' => 'bank',
            'bank_id' => $bank->id,
            'bank_name' => $bank->name,
            'account_number' => $this->bankAccountNumber,
            'deposit_number' => $this->bankDepositNumber,
            'amount' => $this->bankAmount,
            'currency' => $currency->code,
            'symbol' => $currency->symbol,
            'exchange_rate' => $currency->exchange_rate,
            'amount_in_primary_currency' => $amountInPrimaryCurrency,
            'details' => "Banco: {$bank->name}, Cta: {$this->bankAccountNumber}, Ref: {$this->bankDepositNumber}",
        ];

        $this->calculateRemainingAndChange();
        session(['payments' => $this->payments]);
        session(['remainingAmount' => $this->remainingAmount]);
        session(['change' => $this->change]);
        
        $this->reset(['bankId', 'bankAccountNumber', 'bankDepositNumber', 'bankAmount']);
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
        $search = trim($this->search3);
        
        if (strlen($search) > 1) {
            $query = Product::with('priceList');
            
            // Tokenize search terms for multi-word search
            $tokens = explode(' ', $search);
            
            foreach ($tokens as $token) {
                if (!empty($token)) {
                    $query->where(function($q) use ($token) {
                        $q->where('name', 'like', "%{$token}%")
                          ->orWhere('sku', 'like', "%{$token}%");
                    });
                }
            }
            
            // Limit results for performance
            $this->products = $query->take(20)->get();

            if ($this->products->count() == 0) {
                $this->dispatch('noty', msg: 'NO EXISTE EL CÓDIGO ESCANEADO PERO PREGUNTELE');
            }

            // Auto-add if exact SKU match and it's the only one (or prioritized)
            if ($this->products->count() == 1 && $this->products->first()->sku == $search) {
                $this->AddProduct($this->products->first());
                $this->search3 = '';
                $this->products = null;
                $this->dispatch('refresh');
            }
        } else {
            $this->search3 = '';
            $this->dispatch('refresh');
            $this->products = [];
            // Only notify if user explicitly cleared or typed 1 char, maybe too noisy? 
            // Keeping original behavior but checking if empty
            if(strlen($search) > 0) {
                 $this->dispatch('noty', msg: 'INGRESE MÁS CARACTERES');
            }
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
        if ($this->banks->isNotEmpty()) {
            $this->bank = $this->banks[0]->id;
        } else {
            $this->bank = null; 
        }

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

        $this->applyCommissions = session('applyCommissions', false);
    }
    
    public function hydrate()
    {
        // Recargar currencies en cada request (Livewire puede perder colecciones de Eloquent)
        $this->loadCurrencies();
        
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
        
        if (!$this->currencies || $this->currencies->isEmpty()) {
            $this->loadCurrencies();
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
        if ($this->config && $this->config->vat > 0) {
            $this->iva = $this->config->vat / 100;
            $this->subtotalCart = round($this->subtotalCart() / (1 + $this->iva), $decimals);
            $this->ivaCart = round(($this->totalCart() / (1 + $this->iva)) * $this->iva, $decimals);
        } else {
            $this->iva = $this->config ? $this->config->vat : 0;
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
        
        // Solo establecer la moneda principal si paymentCurrency aún no está definido
        if (empty($this->paymentCurrency)) {
            $primaryCurrency = $this->currencies->firstWhere('is_primary', true);
            $this->paymentCurrency = $primaryCurrency ? $primaryCurrency->code : null;
        }
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

        $this->resetExcept('config', 'banks', 'bank', 'currencies');
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

        // Recalcular precios con la configuración del vendedor (si aplica)
        $this->recalculateCartWithSellerConfig();

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

    // Obtener moneda principal y tasa
    $primaryCurrency = CurrencyHelper::getPrimaryCurrency();
    $exchangeRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;

// DEBUG: Log currency information
\Log::info("=== CURRENCY CONVERSION DEBUG ===");
\Log::info("Product: {$product->name}");
\Log::info("Product Price (USD): {$product->price}");
\Log::info("Primary Currency: " . ($primaryCurrency ? $primaryCurrency->code : 'NULL'));
\Log::info("Exchange Rate: {$exchangeRate}");

    // Convertir precio base a moneda principal
    $basePriceInPrimary = $product->price * $exchangeRate;

\Log::info("Base Price in Primary Currency: {$basePriceInPrimary}");
\Log::info("=================================");
    
    // Convertir lista de precios a moneda principal
    $priceListInPrimary = [];

    // Agregar precio principal a la lista
    $priceListInPrimary[] = [
        'id' => 'main',
        'price' => $basePriceInPrimary,
        'name' => 'Precio Principal'
    ];

    if (count($product->priceList) > 0) {
        foreach ($product->priceList as $p) {
            $priceListInPrimary[] = [
                'id' => $p['id'],
                'price' => $p['price'] * $exchangeRate, // Convertir a moneda principal
                'name' => $p['name'] ?? ''
            ];
        }
    }

    // SIEMPRE usar el precio base del producto como precio de venta predeterminado
    // La lista de precios solo se usa cuando el usuario selecciona manualmente un precio diferente
    
    // Aplicar markup si existe configuración de vendedor Y el toggle está activo
    if ($this->sellerConfig && $this->applyCommissions) {
        $comm = ($basePriceInPrimary * $this->sellerConfig->commission_percent) / 100;
        $freight = ($basePriceInPrimary * $this->sellerConfig->freight_percent) / 100;
        $diff = ($basePriceInPrimary * $this->sellerConfig->exchange_diff_percent) / 100;
        
        $salePrice = $basePriceInPrimary + $comm + $freight + $diff;
    } else {
        $salePrice = $basePriceInPrimary;
    }

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
            'price1' => $basePriceInPrimary, // Guardar en moneda principal
            'price2' => $product->price2 * $exchangeRate, // Guardar en moneda principal
            'sale_price' => $salePrice,
            'pricelist' => $priceListInPrimary, // Guardar lista convertida
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
        // Ensure customer is array
        if (is_object($customer)) {
            $customer = $customer->toArray();
        }

        // dd($customer);
        session(['sale_customer' => $customer]);
        $this->customer = $customer;

        // Check for Foreign Seller Config
        $this->sellerConfig = null;
        if(isset($customer['seller_id']) && $customer['seller_id']) {
            $seller = \App\Models\User::find($customer['seller_id']);
            if($seller) {
                $this->sellerConfig = $seller->latestSellerConfig;
            }
        }
        
        $this->recalculateCartWithSellerConfig();
    }

    public function recalculateCartWithSellerConfig()
    {
        $newCart = new Collection();
        $cart = $this->cart;

        // Get primary currency exchange rate
        $primaryCurrency = CurrencyHelper::getPrimaryCurrency();
        $exchangeRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;

        foreach ($cart as $item) {
            $product = Product::find($item['pid']);
            if(!$product) continue;

            // Base price in primary currency
            $basePrice = $product->price * $exchangeRate;
            
            // Apply logic if config exists AND toggle is active
            if ($this->sellerConfig && $this->applyCommissions) {
                $comm = ($basePrice * $this->sellerConfig->commission_percent) / 100;
                $freight = ($basePrice * $this->sellerConfig->freight_percent) / 100;
                $diff = ($basePrice * $this->sellerConfig->exchange_diff_percent) / 100;
                
                $finalPrice = $basePrice + $comm + $freight + $diff;
            } else {
                $finalPrice = $basePrice;
            }

            // Recalculate item totals
            $item['sale_price'] = $finalPrice;
            $values = $this->Calculator($item['sale_price'], $item['qty']);
            $decimals = ConfigurationService::getDecimalPlaces();
            $item['tax'] = round($values['iva'], $decimals);
            $item['total'] = $this->formatAmount(round($values['total'], $decimals));

            $newCart->push($item);
        }

        $this->cart = $newCart;
        $this->save(); // Save cart to session
    }

    function initPayment($type)
    {
        // Redirigir Banco (3) y Nequi (4) al modal unificado (1)
        if ($type == 3) {
            $this->selectedPaymentMethod = 'bank';
            $type = 1; 
        } else {
            $this->selectedPaymentMethod = 'cash';
        }

        $this->payType = $type;

        if ($type == 1) $this->payTypeName = 'PAGO / ABONOS';
        if ($type == 2) $this->payTypeName = 'PAGO A CRÉDITO';

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
            // Determinar tipo de venta y notas
            $saleType = 'cash';
            $status = 'paid';
            $notes = '';

            if ($type == 2) {
                $saleType = 'credit';
                $status = 'pending';
            } elseif (!empty($this->payments)) {
                 $methods = collect($this->payments)->pluck('method')->unique();
                if ($methods->count() > 1) {
                    $saleType = 'mixed';
                } elseif ($methods->isNotEmpty()) {
                    $saleType = $methods->first();
                }
                
                // Construir notas
                foreach ($this->payments as $payment) {
                    if ($payment['method'] == 'bank') {
                        $notes .= "Banco: {$payment['bank_name']}, Ref: {$payment['deposit_number']} | ";
                    }
                }
                $notes = rtrim($notes, " | ");
            } 
            // Fallback para lógica antigua si no hay payments y no es crédito
            elseif ($type == 3) {
            }

            // Calcular el total pagado y el cambio
            $totalPaidInPrimaryCurrency = ($type == 1 || !empty($this->payments)) ? round($this->totalInPrimaryCurrency, ConfigurationService::getDecimalPlaces()) : 0;
            $changeAmount = ($type == 1 || !empty($this->payments)) ? round($this->change, ConfigurationService::getDecimalPlaces()) : 0;

            if ($type > 1 && empty($this->payments)) $this->cashAmount = 0;

            $decimals = ConfigurationService::getDecimalPlaces();
            
            // Obtener moneda principal para snapshot
            $primaryCurrency = Currency::where('is_primary', true)->first();
            
            // Calcular total en USD (moneda base) para créditos
            $totalUSD = $this->totalCart / $primaryCurrency->exchange_rate;

            // Generate Invoice Number
            $config = Configuration::lockForUpdate()->first();
            $config->invoice_sequence += 1;
            $config->save();
            $invoiceNumber = 'F' . str_pad($config->invoice_sequence, 8, '0', STR_PAD_LEFT);

            // Get Order Number if exists
            $orderNumber = null;
            if ($this->order_id) {
                $order = Order::find($this->order_id);
                if ($order) {
                    $orderNumber = $order->order_number;
                }
            }
            
            // Determine Batch and Sequence
            $batchName = null;
            $batchSequence = null;

            if ($this->sellerConfig) {
                $batchName = $this->sellerConfig->current_batch;
                $lastSequence = Sale::where('user_id', Auth()->user()->id)
                    ->where('batch_name', $batchName)
                    ->max('batch_sequence');
                $batchSequence = $lastSequence ? $lastSequence + 1 : 1;
            }

            $sale = Sale::create([
                'seller_config_id' => $this->sellerConfig ? $this->sellerConfig->id : null,
                'applied_commission_percent' => $this->sellerConfig ? $this->sellerConfig->commission_percent : null,
                'applied_freight_percent' => $this->sellerConfig ? $this->sellerConfig->freight_percent : null,
                'applied_exchange_diff_percent' => $this->sellerConfig ? $this->sellerConfig->exchange_diff_percent : null,
                'is_foreign_sale' => $this->sellerConfig ? true : false,
                'total' => round($this->totalCart, $decimals),
                'total_usd' => round($totalUSD, $decimals),
                'discount' => 0,
                'items' => $this->itemsCart,
                'customer_id' => $this->customer['id'],
                'user_id' => Auth()->user()->id,
                'type' => $saleType,
                'status' => $status,
                'cash' => $totalPaidInPrimaryCurrency,
                'change' => $changeAmount,
                'notes' => $notes,
                'primary_currency_code' => $primaryCurrency->code,
                'primary_exchange_rate' => $primaryCurrency->exchange_rate,
                'invoice_number' => $invoiceNumber,
                'order_number' => $orderNumber,
                'batch_name' => $batchName,
                'batch_sequence' => $batchSequence
            ]);

            // get cart session
            $cart = collect(session("cart"));

            // insert sale detail
            $details = $cart->map(function ($item) use ($sale, $decimals, $primaryCurrency) {
                // El precio del producto está en USD (base)
                $product = Product::find($item['pid']);
                $priceUSD = $product ? $product->price : 0;
                
                return [
                    'product_id' => $item['pid'],
                    'sale_id' => $sale->id,
                    'quantity' => round($item['qty'], $decimals),
                    'regular_price' => round(
                        $item['price2'] ?? 0,
                        $decimals
                    ),
                    'sale_price' => round($item['sale_price'], $decimals),
                    'price_usd' => $priceUSD,
                    'exchange_rate' => $primaryCurrency->exchange_rate,
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
            if (!empty($this->payments)) {
                foreach ($this->payments as $payment) {
                    $zelleRecordId = null;

                    Log::info("Processing payment in Sales", ['method' => $payment['method'], 'has_zelle_sender' => isset($payment['zelle_sender'])]);

                    if ($payment['method'] == 'zelle') {
                        Log::info("Zelle payment detected in Sales", $payment);
                        // Check if Zelle record exists
                        $zelleRecord = ZelleRecord::where('sender_name', $payment['zelle_sender'])
                            ->where('zelle_date', $payment['zelle_date'])
                            ->where('amount', $payment['zelle_amount'])
                            ->first();

                        $amountUsed = $payment['amount']; // Amount applied to this sale

                        if ($zelleRecord) {
                            // Use existing record
                            $zelleRecord->remaining_balance -= $amountUsed;
                            if ($zelleRecord->remaining_balance < 0) $zelleRecord->remaining_balance = 0;
                            
                            $zelleRecord->status = $zelleRecord->remaining_balance <= 0.01 ? 'used' : 'partial';
                            $zelleRecord->save();
                            
                            $zelleRecordId = $zelleRecord->id;
                        } else {
                            // Create new record
                            $remaining = $payment['zelle_amount'] - $amountUsed;
                            
                                $zelleRecord = ZelleRecord::create([
                                'sender_name' => $payment['zelle_sender'],
                                'zelle_date' => $payment['zelle_date'],
                                'amount' => $payment['zelle_amount'],
                                'reference' => $payment['reference'] ?? null,
                                'image_path' => $payment['zelle_image'] ?? null,
                                'status' => $remaining <= 0.01 ? 'used' : 'partial',
                                'remaining_balance' => max(0, $remaining),
                                'customer_id' => $sale->customer_id,
                                'sale_id' => $sale->id,
                                'invoice_total' => $sale->total,
                                'payment_type' => $amountUsed >= ($sale->total - 0.01) ? 'full' : 'partial'
                            ]);
                            
                            $zelleRecordId = $zelleRecord->id;
                        }
                        
                        Log::info("Zelle Record processed", ['id' => $zelleRecordId, 'created_new' => !$zelleRecord->wasRecentlyCreated]);
                    } else {
                        Log::info("Not a Zelle payment", ['method' => $payment['method']]);
                    }

                    SalePaymentDetail::create([
                        'sale_id' => $sale->id,
                        'payment_method' => $payment['method'],
                        'currency_code' => $payment['currency'],
                        'amount' => $payment['amount'],
                        'exchange_rate' => $payment['exchange_rate'] ?? 1,
                        'amount_in_primary_currency' => $payment['amount_in_primary_currency'],
                        'bank_name' => $payment['bank_name'] ?? null,
                        'account_number' => $payment['account_number'] ?? null,
                        'reference_number' => $payment['reference'] ?? ($payment['deposit_number'] ?? null),
                        'phone_number' => $payment['phone_number'] ?? null,
                        'zelle_record_id' => $zelleRecordId
                    ]);
                }
            } elseif ($type == 1) {
                // Caso simple: pago efectivo sin desglose múltiple (legacy fallback)
                $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
                $currencyCode = $primaryCurrency ? $primaryCurrency->code : 'COP';
                
                SalePaymentDetail::create([
                    'sale_id' => $sale->id,
                    'payment_method' => 'cash',
                    'currency_code' => $currencyCode,
                    'amount' => $this->cashAmount,
                    'exchange_rate' => 1,
                    'amount_in_primary_currency' => $this->cashAmount,
                ]);
            } elseif ($type == 3) { // Depósito Bancario (Legacy)
                $bank = $this->banks->where('id', $this->bank)->first();
                $currencyCode = $bank->currency_code ?? 'USD';
                $currency = collect($this->currencies)->firstWhere('code', $currencyCode);
                $exchangeRate = $currency ? $currency->exchange_rate : 1;
                $amount = $this->totalCart * $exchangeRate;

                SalePaymentDetail::create([
                    'sale_id' => $sale->id,
                    'payment_method' => 'bank',
                    'currency_code' => $currencyCode,
                    'bank_name' => $bank ? $bank->name : null,
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

            // Calculate Commission if paid immediately (Cash/Instant)
            if ($sale->status == 'paid') {
                \App\Services\CommissionService::calculateCommission($sale);
            }

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
                // Generate Order Number
                $config = Configuration::lockForUpdate()->first();
                $config->order_sequence += 1;
                $config->save();
                $orderNumber = 'P' . str_pad($config->order_sequence, 8, '0', STR_PAD_LEFT);

                // Crea una nueva orden
                $order = Order::create([
                    'order_number' => $orderNumber,
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

            // Calculate Commission if paid immediately (Cash/Instant)
            // We need to reload the sale to get relations if needed, but for calculation we just need the model
            // However, the sale object created in storeOrder might not have the foreign flag set if it was set via sellerConfig relation
            // Let's ensure we pass the correct sale object
            if ($order->status == 'paid') {
                 // Convert Order to Sale model logic is handled elsewhere? 
                 // Wait, Sales.php creates ORDERS or SALES? 
                 // Looking at storeOrder, it creates Order or updates Order.
                 // But initPayment creates Sale. 
                 // Let's check initPayment instead.
            }

            $this->resetExcept('config', 'banks', 'bank', 'currencies');
            $this->clear();
            session()->forget('sale_customer');
            // Obtener el último registro insertado
            $order = Order::latest('id')->first();

            // return $order;
            $this->dispatch('noty', msg: 'ORDEN  imprimientod ' . $order->id . ' CON ÉXITO ');


            // mike42
            $this->printOrder($order->id);

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

    #[On('cancelSaleById')]
    public function cancelSaleById($saleId, CashRegisterService $cashRegisterService)
    {
        try {
            DB::beginTransaction();

            $sale = Sale::with(['details', 'paymentDetails', 'changeDetails'])->findOrFail($saleId);

            // Validar que la venta no esté ya cancelada
            if ($sale->status === 'cancelled') {
                $this->dispatch('noty', msg: 'Esta venta ya está cancelada');
                return;
            }

            // Validar que la venta no sea muy antigua (opcional, puedes ajustar o quitar esta validación)
            // if ($sale->created_at->diffInDays(now()) > 30) {
            //     $this->dispatch('noty', msg: 'No se pueden cancelar ventas con más de 30 días');
            //     return;
            // }

            // Actualizar estado de la venta
            $sale->update([
                'status' => 'cancelled'
            ]);

            // Restaurar stock de productos
            foreach ($sale->details as $detail) {
                $product = Product::find($detail->product_id);
                if ($product && $product->manage_stock == 1) {
                    $product->increment('stock_qty', $detail->quantity);
                }
            }

            // Revertir movimientos de caja si existe un registro activo
            $register = $cashRegisterService->getActiveCashRegister(Auth::id());
            if ($register) {
                // Revertir pagos (quitar dinero de caja)
                foreach ($sale->paymentDetails as $payment) {
                    $cashRegisterService->recordSaleMovement(
                        $register->id,
                        $sale->id,
                        'sale_cancellation',
                        $payment->currency_code,
                        -$payment->amount, // Negativo para restar
                        "Anulación Venta #{$sale->id}"
                    );
                }

                // Revertir vueltos (devolver dinero a caja)
                foreach ($sale->changeDetails as $change) {
                    $cashRegisterService->recordSaleMovement(
                        $register->id,
                        $sale->id,
                        'sale_cancellation',
                        $change->currency_code,
                        $change->amount, // Positivo para sumar
                        "Reversión Vuelto Venta #{$sale->id}"
                    );
                }
            }

            DB::commit();

            $this->dispatch('noty', msg: "Venta #{$sale->invoice_number} anulada correctamente");
        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al anular la venta: {$th->getMessage()}");
        }
    }

}
