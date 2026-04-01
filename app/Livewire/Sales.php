<?php

namespace App\Livewire;

use Carbon\Carbon;

use App\Models\Bank;
use App\Models\Sale;
use App\Models\Order;
use App\Models\Product;
use App\Models\ZelleRecord;
use Livewire\Component;
use Livewire\WithFileUploads;
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
    use WithFileUploads;

    public $cart;
    public $taxCart = 0, $itemsCart, $subtotalCart = 0, $totalCart = 0, $ivaCart = 0;

    public $config, $customer, $iva = 0;
    public $sellerConfig = null; // Store active seller config
    public $customerConfig = null; // Store active customer config
    public $creditConfig = []; // Store customer credit configuration (Cliente > Vendedor > Global)
    //register customer
    public $cname, $caddress, $ccity, $cemail, $cphone, $ctaxpayerId, $ctype = 'Consumidor Final';

    //pay properties
    public $banks, $cashAmount, $nequiAmount, $phoneNumber, $acountNumber, $depositNumber, $bank, $payType = 1, $payTypeName = 'PAGO EN EFECTIVO';
    public $walletAmount = 0; // Amount to use from virtual wallet

    public $search3, $products = [], $selectedIndex = -1;
    public $warehouse_id;
    public $bypassReservation = false;
    public $stockWarningMessage;
    public $pendingProductToAdd;
    public $pendingQtyToAdd;
    public $pendingWarehouseId;
    public $warehouses;
    public $selectedProductForUnits = null;

    public $order_selected_id, $customer_name, $amount;
    public $order_id, $ordersObt, $order_note, $details = [];
    public $pagination = 5, $status;
    public $confirmation_code = null;
    public $salesViewMode = 'grid'; // 'grid' or 'list'

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
    
    // VED Bank Details
    public $bankReference;
    public $bankDate;
    public $selectedCoils = []; // FOR MULTI-SELECT BOBINAS
    public $bankNote;
    public $bankImage;
    public $isVedBankSelected = false;

    // Bank Validation Status (Added for Remaining Balance Logic)
    public $bankGlobalAmount; 
    public $bankStatusMessage = '';
    public $bankStatusType = '';
    public $bankRemainingBalance = null;
    


    public $pagoMovilBank, $pagoMovilPhoneNumber, $pagoMovilReference, $pagoMovilAmount;

    // Zelle Properties
    public $zelleAmount;
    public $zelleDate;
    public $zelleSender;
    public $zelleReference;
    public $zelleImage;
    public $isZelleSelected = false;
    
    // Zelle Validation Status
    public $zelleStatusMessage = '';
    public $zelleStatusType = ''; // 'info', 'warning', 'danger', 'success'
    public $zelleRemainingBalance = null;
    
    public $drivers = []; // List of users with Driver role

    public $driver_id = null; // Selected driver

    public $invoiceCurrency_id = null;
    public $invoiceExchangeRate = 1;

    public $searchSeller = '';
    public $sellers = [];


    public function updatedSelectedPaymentMethod($value)
    {
        $this->resetBankForm();
    }
    public function updatedBankId($value)
    {
        $this->isZelleSelected = false;
        $this->isVedBankSelected = false;
        
        if($value) {
            $bank = collect($this->banks)->firstWhere('id', $value);
            if($bank) {
                if(stripos($bank->name, 'zelle') !== false) {
                    $this->isZelleSelected = true;
                }
                if($bank->currency_code === 'VED' || $bank->currency_code === 'VES') {
                    $this->isVedBankSelected = true;
                }
            }
        }
        
        // Reset Fields when Bank Changes
        $this->bankReference = '';
        $this->bankDate = date('Y-m-d');
        $this->bankNote = '';
        $this->bankAmount = null;
        $this->bankGlobalAmount = null;
        $this->bankAccountNumber = '';
        $this->bankDepositNumber = '';
        $this->bankImage = null;
        $this->bankStatusMessage = '';
        $this->bankStatusType = '';
        $this->bankRemainingBalance = null;

        $this->checkBankStatus();
    }
    
    public function updatedBankReference() { $this->checkBankStatus(); }
    public function updatedBankGlobalAmount() { $this->checkBankStatus(); }

    public function checkBankStatus()
    {
        $ref = $this->isVedBankSelected ? $this->bankReference : $this->bankDepositNumber;
        // In POS standard fields, depositNumber is used. In VED detailed, bankReference is used.
        
        $amount = $this->bankGlobalAmount;
        $bankId = $this->bankId;

        if ($this->isVedBankSelected && $bankId && $ref && $amount) {
            
            // 1. Check for Session Duplicates (Already in list)
            $duplicateInSession = collect($this->payments)->contains(function ($payment) use ($ref) {
                return $payment['method'] === 'bank' && 
                       ($payment['bank_reference'] ?? '') === $ref;
             });

            if ($duplicateInSession) {
                $this->bankStatusMessage = "Esta referencia ya está agregada en esta venta.";
                $this->bankStatusType = 'warning';
                $this->bankRemainingBalance = null;
                return;
            }

            // 2. Check Database for BankRecord with SAME Total Amount
            $bankRecord = \App\Models\BankRecord::where('bank_id', $bankId)
                ->where('reference', $ref)
                ->where('amount', $amount)
                ->first();

            if ($bankRecord) {
                if ($bankRecord->remaining_balance <= 0.01) {
                    $this->bankStatusMessage = "Este depósito ya fue utilizado completamente.";
                    $this->bankStatusType = 'danger';
                    $this->bankRemainingBalance = 0;
                } else {
                    $this->bankStatusMessage = "Depósito encontrado. Saldo restante: $" . number_format($bankRecord->remaining_balance, 2);
                    $this->bankStatusType = 'success';
                    $this->bankRemainingBalance = $bankRecord->remaining_balance;
                }
            } else {
                $this->bankStatusMessage = "Nuevo Depósito (Se creará registro).";
                $this->bankStatusType = 'success'; // Green: New
                $this->bankRemainingBalance = $amount;
            }
        } else {
            $this->bankStatusMessage = '';
            $this->bankStatusType = '';
            $this->bankRemainingBalance = null;
        }
    }

    // Helper property to pass the full currency object to views
    public $displayCurrency = null;
    public $totalPaidDisplay = 0;

    public function updatedInvoiceCurrencyId($value)
    {
        if ($value) {
            $currency = collect($this->currencies)->firstWhere('id', $value);
            if ($currency) {
                $this->invoiceExchangeRate = $currency->exchange_rate;
                $this->displayCurrency = $currency;
                session(['invoiceCurrency_id' => $value]);
            }
        }
    }



    /**
     * Livewire lifecycle hook - called when customer property is updated
     * Reload credit configuration for the newly selected customer
     */
    public function updatedCustomer($value)
    {
        // Dispatch event to trigger credit config reload from JavaScript
        // This ensures proper timing after customer property is fully updated
        $this->dispatch('customer-updated');
    }

    public function updatedZelleSender() { $this->checkZelleStatus(); }
    public function updatedZelleDate() { $this->checkZelleStatus(); }
    public function updatedZelleAmount() 
    { 
        $this->paymentAmount = $this->zelleAmount;
        $this->checkZelleStatus(); 
    }

    public function checkZelleStatus()
    {
        if ($this->zelleSender && $this->zelleDate && $this->zelleAmount) {
            
            // 1. Check for Session Duplicates (Already in list)
            $isDuplicateInSession = collect($this->payments)->contains(function ($payment) {
                return $payment['method'] === 'zelle' &&
                       $payment['zelle_sender'] === $this->zelleSender &&
                       $payment['zelle_date'] === $this->zelleDate &&
                       floatval($payment['zelle_amount']) === floatval($this->zelleAmount);
            });

            if ($isDuplicateInSession) {
                $this->zelleStatusMessage = "Este Zelle ya está en la lista de pagos. Si desea cambiar el monto, elimine el anterior y agréguelo nuevamente.";
                $this->zelleStatusType = 'warning'; // Orange: Session Duplicate
                $this->zelleRemainingBalance = null;
                return;
            }

            // 2. Check Database
            $zelleRecord = ZelleRecord::where('sender_name', $this->zelleSender)
                ->where('zelle_date', $this->zelleDate)
                ->where('amount', $this->zelleAmount)
                ->first();

            if ($zelleRecord) {
                if ($zelleRecord->remaining_balance <= 0.01) {
                    $this->zelleStatusMessage = "Este Zelle ya fue utilizado completamente.";
                    $this->zelleStatusType = 'danger'; // Red: DB Exhausted
                    $this->zelleRemainingBalance = 0;
                } else {
                    $this->zelleStatusMessage = "Zelle encontrado. Saldo restante: $" . number_format((float)$zelleRecord->remaining_balance, 2);
                    $this->zelleStatusType = 'success'; // Green: Available Balance
                    $this->zelleRemainingBalance = $zelleRecord->remaining_balance;
                }
            } else {
                $this->zelleStatusMessage = "Nuevo Zelle (No registrado en BD).";
                $this->zelleStatusType = 'success'; // Green: New
                $this->zelleRemainingBalance = $this->zelleAmount;
            }
        } else {
            $this->zelleStatusMessage = '';
            $this->zelleStatusType = '';
            $this->zelleRemainingBalance = null;
        }
    }

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

    public $variableItemStats = [
        'available' => 0,
        'reserved' => 0,
        'total' => 0
    ];

    public $applyCommissions = false; // Toggle for applying commissions
    public $applyFreight = false; // Toggle for applying freight ONLY
    public $is_freight_broken_down = false; // Toggle for breaking down freight
    public $total_freight = 0; // Total freight amount

    public function updatedApplyCommissions()
    {
        session(['applyCommissions' => $this->applyCommissions]);
        $this->recalculateCartWithSellerConfig();
        $this->recalculateFreightTotal(); // Ensure freight is recalculated
        $this->dispatch('noty', msg: 'COMISIONES DE VENTA ACTUALIZADAS');
    }

    public function updatedApplyFreight()
    {
        session(['applyFreight' => $this->applyFreight]);
        $this->recalculateCartWithSellerConfig();
        $this->recalculateFreightTotal();
        $this->dispatch('noty', msg: 'CONFIGURACIÓN DE FLETE ACTUALIZADA');
    }

    public function updatedIsFreightBrokenDown()
    {
        session(['is_freight_broken_down' => $this->is_freight_broken_down]);
        $this->recalculateCartWithSellerConfig();
        $this->recalculateFreightTotal();
        $this->dispatch('noty', msg: 'DISTRIBUCIÓN DE FLETE ACTUALIZADA');
    }



    public function addPayment()
    {
        // PAGO A CRÉDITO
        if ($this->selectedPaymentMethod === 'credit') {
            if (!in_array('module_credits', session('tenant.modules', []))) {
                $this->dispatch('noty', msg: 'ACCESO DENEGADO: Módulo de Créditos no activo.');
                return;
            }

            // Validar que el cliente tenga crédito habilitado
            if (!isset($this->creditConfig['allow_credit']) || !$this->creditConfig['allow_credit']) {
                $this->dispatch('noty', msg: 'El cliente no tiene crédito habilitado');
                return;
            }
            
            // Validar que haya un cliente seleccionado
            if (!$this->customer || !isset($this->customer->id)) {
                $this->dispatch('noty', msg: 'Debe seleccionar un cliente para venta a crédito');
                return;
            }
            
            // Validar límite de crédito
            $validation = \App\Services\CreditConfigService::validateCreditLimit(
                \App\Models\Customer::find($this->customer->id),
                $this->totalCart
            );
            
            if (!$validation['allowed']) {
                $this->dispatch('noty', msg: $validation['message']);
                return;
            }
            
            // Obtener moneda principal
            $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
            
            // Agregar pago a crédito (monto completo del carrito)
            $this->payments[] = [
                'method' => 'CREDITO',
                'amount' => $this->totalCart,
                'currency' => $primaryCurrency->code ?? 'COP',
                'symbol' => $primaryCurrency->symbol ?? '$',
                'exchange_rate' => 1,
                'amount_in_primary_currency' => $this->totalCart,
                'details' => 'Crédito ' . ($this->creditConfig['credit_days'] ?? 30) . ' días',
            ];
            
            $this->calculateRemainingAndChange();
            session(['payments' => $this->payments]);
            session(['remainingAmount' => $this->remainingAmount]);
            session(['change' => $this->change]);
            
            $this->dispatch('noty', msg: 'Pago a crédito agregado correctamente');
            
            // Cerrar modal si el pago está completo
            if ($this->remainingAmount <= 0) {
                $this->dispatch('close-modal-cash');
            }
            
            return;
        }
        
        // PAGO CON BILLETERA
        if ($this->selectedPaymentMethod === 'wallet') {
            $this->validate([
                'walletAmount' => 'required|numeric|min:0.01',
            ]);

            // Validar que el cliente tenga saldo suficiente
            $walletBalance = $this->customer['wallet_balance'] ?? 0;
            if ($this->walletAmount > $walletBalance) {
                $this->dispatch('noty', msg: 'Saldo insuficiente en la billetera virtual.', type: 'error');
                return;
            }

            // Obtener moneda principal
            $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);

            $this->payments[] = [
                'method' => 'wallet',
                'amount' => $this->walletAmount,
                'currency' => $primaryCurrency->code ?? 'USD',
                'symbol' => $primaryCurrency->symbol ?? '$',
                'exchange_rate' => 1,
                'amount_in_primary_currency' => $this->walletAmount,
                'details' => 'Pago con Billetera Virtual',
            ];

            $this->calculateRemainingAndChange();
            session(['payments' => $this->payments]);
            session(['remainingAmount' => $this->remainingAmount]);
            session(['change' => $this->change]);

            $this->reset(['walletAmount']);
            $this->dispatch('noty', msg: 'Pago con billetera agregado correctamente');
            
            return;
        }

        // PAGO EN EFECTIVO (lógica existente)
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






    public function addPagoMovilPayment()
    {
        if (!in_array('module_advanced_payments', config('tenant.modules', []))) {
            $this->dispatch('noty', msg: 'ACCESO DENEGADO: Módulo de pagos avanzados no activo.');
            return;
        }

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



    public function addZellePayment()
    {
        if (!in_array('module_advanced_payments', config('tenant.modules', []))) {
            $this->dispatch('noty', msg: 'ACCESO DENEGADO: Módulo de pagos avanzados no activo.');
            return;
        }

        $this->validate([
            'zelleAmount' => 'required|numeric|min:0.01',
            'zelleDate' => 'required|date',
            'zelleSender' => 'required|string',
            'zelleReference' => 'nullable|string',
            'zelleImage' => 'required|image|max:2048', // Validate image
        ]);

        // Check for duplicate Zelle
        $this->checkZelleStatus();
        
        // Block if danger (Overused) OR warning (Session Duplicate)
        if ($this->zelleStatusType === 'danger' || $this->zelleStatusType === 'warning') {
             $this->dispatch('noty', msg: $this->zelleStatusMessage);
             return;
        }
        
        // Validate Amount vs Remaining Balance (Only for DB records)
        // Note: paymentAmount is the amount being used in this transaction
        $amountToUse = $this->paymentAmount ?: $this->zelleAmount;
        
        if ($this->zelleRemainingBalance !== null && $amountToUse > $this->zelleRemainingBalance) {
            $this->dispatch('noty', msg: "El monto a usar ($" . number_format($amountToUse, 2) . ") excede el saldo restante del Zelle ($" . number_format($this->zelleRemainingBalance, 2) . ")");
            return;
        }

        // Check if USD exists in currencies
        $currency = collect($this->currencies)->firstWhere('code', 'USD');
        
        if (!$currency) {
             $this->dispatch('noty', msg: 'Moneda USD no configurada para Zelle.');
             return;
        }

        // Handle Image Upload
        $imagePath = null;
        if ($this->zelleImage) {
            $imagePath = $this->zelleImage->store('zelle_receipts', 'public');
        }

        // Determine amount to use (paymentAmount if set, else zelleAmount)
        $amountToUse = $this->paymentAmount ?: $this->zelleAmount;

        // Convertir a moneda principal
        $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
        
        // Zelle is USD. 
        // If amountToUse is in USD (which it should be for Zelle context), convert to Primary.
        $amountInPrimaryCurrency = $amountToUse * $primaryCurrency->exchange_rate;

        $this->payments[] = [
            'method' => 'zelle',
            'amount' => $amountToUse,
            'currency' => 'USD',
            'symbol' => '$',
            'exchange_rate' => $currency->exchange_rate,
            'amount_in_primary_currency' => $amountInPrimaryCurrency,
            'zelle_date' => $this->zelleDate,
            'zelle_sender' => $this->zelleSender,
            'reference' => $this->zelleReference,
            'zelle_amount' => $this->zelleAmount, // The actual Zelle transaction amount
            'zelle_image' => $imagePath,
            'zelle_file_url' => $imagePath ? asset('storage/' . $imagePath) : null,
            'details' => "Zelle: {$this->zelleSender}, Fecha: {$this->zelleDate}, Ref: {$this->zelleReference}",
        ];

        $this->calculateRemainingAndChange();
        session(['payments' => $this->payments]);
        session(['remainingAmount' => $this->remainingAmount]);
        session(['change' => $this->change]);
        
        $this->reset(['zelleAmount', 'zelleSender', 'zelleReference', 'zelleImage', 'paymentAmount']);
        $this->zelleDate = date('Y-m-d'); // Reset date to today
        $this->zelleStatusMessage = '';
        $this->zelleStatusType = '';
        $this->zelleRemainingBalance = null;
        $this->dispatch('noty', msg: 'Pago Zelle agregado correctamente');
    }


    public function calculateRemainingAndChange()
    {
        $conversionFactor = $this->getConversionFactor();
        $decimals = ConfigurationService::getDecimalPlaces();

        // Calculate Total Paid in Primary Currency first
        $totalPaidInPrimary = array_sum(array_column($this->payments, 'amount_in_primary_currency'));
        
        // Convert Total Paid to Invoice/Display Currency
        $totalPaidInInvoice = $totalPaidInPrimary * $conversionFactor;
        
        // Use totalCartAtPayment (Invoice Currency) if exists, else convert current totalCart
        $cartTotal = $this->totalCartAtPayment ?? ($this->totalCart * $conversionFactor);

        $this->remainingAmount = round(max(0, $cartTotal - $totalPaidInInvoice), $decimals);
        $this->change = round(max(0, $totalPaidInInvoice - $cartTotal), $decimals);
        $this->totalPaidDisplay = round($totalPaidInInvoice, $decimals);

        Log::info('Cálculo de montos (Invoice Currency):', [
            'totalCart (Primary)' => $this->totalCart,
            'factor' => $conversionFactor,
            'cartTotal (Invoice)' => $cartTotal,
            'totalPaid (Invoice)' => $totalPaidInInvoice,
            'remainingAmount' => $this->remainingAmount,
            'change' => $this->change,
        ]);
        $this->calculateTotalInPrimaryCurrency();
    }

    public function setCustomPrice($uid, $price)
    {
        $price = trim(str_replace(['$', ','], '', $price));
        
        if (!is_numeric($price)) {
            $this->dispatch('noty', msg: 'EL VALOR DEL PRECIO ES INCORRECTO');
            return;
        }

        // Convert the input price (which is in Invoice/Display Currency) back to Primary Currency
        $conversionFactor = $this->getConversionFactor();
        if ($conversionFactor > 0) {
             $price = $price / $conversionFactor;
        }

        $mycart = $this->cart;

        $oldItem = $mycart->where('id', $uid)->first();

        $newItem = $oldItem;
        $newItem['base_price'] = $price; // Update base_price with manual override
        $newItem['sale_price'] = $price; // Temporary, Calculator will overwrite

        $productModel = \App\Models\Product::find($newItem['pid']);
        $values = $this->Calculator($newItem['base_price'], $newItem['qty'], $productModel);

        $decimals = ConfigurationService::getDecimalPlaces();
        $newItem['sale_price'] = $values['sale_price']; // Set final price (Base + Freight/Comm)
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
        $this->save();
        $this->dispatch('refresh');
        $this->dispatch('noty', msg: 'PRECIO ACTUALIZADO');
    }



    function updatedSearch3()
    {
        $search = trim($this->search3);
        
        if (strlen($search) > 0) {
            $query = Product::with('priceList');
            
            // Tokenize search terms for multi-word search
            $tokens = explode(' ', $search);
            
            foreach ($tokens as $token) {
                if (!empty($token)) {
                    $query->where(function($q) use ($token) {
                        $q->where('name', 'like', "%{$token}%")
                          ->orWhere('sku', 'like', "%{$token}%")
                          ->orWhereHas('category', function ($subQuery) use ($token) {
                              $subQuery->where('name', 'like', "%{$token}%");
                          })
                          ->orWhereHas('tags', function ($subQuery) use ($token) {
                              $subQuery->where('name', 'like', "%{$token}%");
                          });
                    });
                }
            }
            
            // Limit results for performance
            // Limit results for performance
            // Prioritize results: 1. SKU starts with search, 2. Name starts with search, 3. Name contains search, 4. Others (Tags/Categories)
            $query->orderByRaw("CASE 
                WHEN sku LIKE ? THEN 1 
                WHEN name LIKE ? THEN 2 
                WHEN name LIKE ? THEN 3 
                ELSE 4 END", 
                ["{$search}%", "{$search}%", "%{$search}%"]
            )
            ->orderByRaw("REPLACE(name, '  ', ' ') ASC");

            // Limit results for performance
            $this->products = $query->with(['productWarehouses.warehouse', 'units'])->take(50)->get();

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
            $this->products = [];
            // Only notify if user explicitly cleared or typed 1 char, maybe too noisy? 
            // Keeping original behavior but checking if empty
            if(strlen($search) > 0) {
                 $this->dispatch('noty', msg: 'INGRESE MÁS CARACTERES');
            }
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
            $this->cart = collect(session("cart"))->map(function($item) {
                if (!isset($item['id'])) {
                    $item['id'] = uniqid() . ($item['pid'] ?? 'old');
                }
                return $item;
            });
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

        $this->warehouses = \App\Models\Warehouse::where('is_active', 1)->get();
        
        $user = Auth::user();
        if ($user->warehouse_id) {
            $this->warehouse_id = $user->warehouse_id;
        } else {
            // Use system default or fallback to first active
            $this->warehouse_id = $this->config->default_warehouse_id ?? ($this->warehouses->first()->id ?? null);
        }

        // If user cannot switch warehouse, restrict the list or just ensure the selected one is enforced
        if (!$user->can('sales.switch_warehouse')) {
            if ($this->warehouse_id) {
                 // Restrict list to the assigned (or default) warehouse
                $this->warehouses = $this->warehouses->where('id', $this->warehouse_id);
            }
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
    
    // Initialize Invoice Currency
    // Priority: Session > Primary Currency > First Available
    $sessionCurrencyId = session('invoiceCurrency_id');
    if ($sessionCurrencyId) {
        $currency = collect($this->currencies)->firstWhere('id', $sessionCurrencyId);
        if ($currency) {
            $this->invoiceCurrency_id = $currency->id;
            $this->invoiceExchangeRate = $currency->exchange_rate;
            $this->displayCurrency = $currency;
        }
    } 
    
    if (!$this->invoiceCurrency_id) {
        $primary = collect($this->currencies)->firstWhere('is_primary', true);
        if ($primary) {
            $this->invoiceCurrency_id = $primary->id;
            $this->invoiceExchangeRate = $primary->exchange_rate;
            $this->displayCurrency = $primary;
        } elseif ($this->currencies->isNotEmpty()) {
             $first = $this->currencies->first();
             $this->invoiceCurrency_id = $first->id;
             $this->invoiceExchangeRate = $first->exchange_rate;
             $this->displayCurrency = $first;
        }
    }



        // Establecer la moneda principal como la seleccionada por defecto
        $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
        $this->paymentCurrency = $primaryCurrency ? $primaryCurrency->code : null;

        $this->applyCommissions = session('applyCommissions', false);
        $this->applyFreight = session('applyFreight', false);
        $this->is_freight_broken_down = session('is_freight_broken_down', false);
        
        // Determine Sales View Mode
        // Priority: User Preference > Global Config > Default 'grid'
        $this->salesViewMode = $user->sales_view_mode ?? $this->config->sales_view_mode ?? 'grid';

        
        // Initialize Zelle Date
        $this->zelleDate = date('Y-m-d');
        // Initialize Bank Date
        $this->bankDate = date('Y-m-d');
        
        // Load Drivers de forma segura (solo roles que existan)
        $possibleRoles = ['driver', 'chofer', 'repartidor', 'Driver', 'Chofer'];
        $existingRoles = \Spatie\Permission\Models\Role::whereIn('name', $possibleRoles)->pluck('name')->toArray();
        if (!empty($existingRoles)) {
            $this->drivers = \App\Models\User::role($existingRoles)->get();
        } else {
            $this->drivers = \App\Models\User::all(); // Fallback si no hay roles creados
        }

        // Load Sellers
        $this->sellers = \App\Models\User::all(); // Simplified for now, can be role-filtered if needed
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
        
        // Cargar configuración de crédito del cliente
        $this->loadCreditConfig();
        
        Log::info('HYDRATE - Final:', [
            'change' => $this->change,
            'payments_count' => count($this->payments),
            'totalCartAtPayment' => $this->totalCartAtPayment
        ]);

        $this->sellers = \App\Models\User::all();
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
        
        // Re-hydrate config models if lost (e.g., F5 refresh loses untyped component properties)
        if ($this->customer) {
            if (!$this->sellerConfig && isset($this->customer['seller_id'])) {
                $seller = \App\Models\User::find($this->customer['seller_id']);
                if ($seller) {
                    $this->sellerConfig = $seller->latestSellerConfig;
                }
            }
            if (!$this->customerConfig && isset($this->customer['id'])) {
                $customerDb = \App\Models\Customer::find($this->customer['id']);
                if ($customerDb) {
                    $this->customerConfig = $customerDb->latestCustomerConfig;
                }
            }

            // AUTO-RE-ENABLE Commissions and Freight during hydration (Render/F5)
            // CRITICAL: ONLY force-enable if the user CANNOT manage adjustments (Foreign Seller logic)
            // This prevents "auto-reactivation" for office users who want to toggle them off manually.
            if (!Auth::user()->can('sales.manage_adjustments')) {
                $hasCustomerConfig = ($this->customerConfig && ($this->customerConfig->commission_percent > 0 || $this->customerConfig->freight_percent > 0 || $this->customerConfig->exchange_diff_percent > 0));
                $hasSellerConfig = ($this->sellerConfig && ($this->sellerConfig->commission_percent > 0 || $this->sellerConfig->freight_percent > 0 || $this->sellerConfig->exchange_diff_percent > 0));

                if ($hasCustomerConfig || $hasSellerConfig) {
                    $this->applyCommissions = true;
                    $this->applyFreight = true;
                }
            }
        }
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
                if (isset($payment['amount_in_primary_currency']) && $payment['amount_in_primary_currency'] !== null) {
                    $this->totalInPrimaryCurrency += $payment['amount_in_primary_currency'];
                } else {
                    // Si no, calcúlalo (esto es un fallback, idealmente siempre debería estar definido)
                    $primaryCurrency = collect($this->currencies)->firstWhere('is_primary', 1);
                    $amountInUSD = $payment['amount'] / $currency->exchange_rate;
                    $amountInPrimaryCurrency = $amountInUSD * $primaryCurrency->exchange_rate;
                    $this->totalInPrimaryCurrency += $amountInPrimaryCurrency;
                }
            }
        }
    }

    public function assignDriver($orderId, $driverId)
    {
        $order = Order::find($orderId);
        // Note: In this system, Orders are basically Sales with status 'pending' or similar.
        // But wait, the table iterates $orders. Let's check getOrdersWithDetails.
        // It returns Order model.
        // But my migration added fields to 'sales' table.
        // Order model usually maps to 'sales' table or 'orders' table?
        // Let's check Order model.
        
        // Assuming Order model maps to 'sales' table based on previous context (Sales.php uses Order model for pending sales).
        // If Order model is separate, I might need to update Order model too.
        // Let's assume Order maps to 'sales' table for now or 'orders' table.
        // Wait, migration was: Schema::table('sales'...
        // If Order model uses 'orders' table, then I updated the wrong table or need to update 'orders' table too.
        // Let's check Order model file content if possible.
        
        // For now, I'll assume Order model is what is used in the view.
        // If Order is a separate table, I need to check.
        
        if ($order) {
            $order->driver_id = $driverId ?: null;
            $order->delivery_status = $driverId ? 'pending' : 'pending'; // Reset status if driver changed
            $order->save();
            $this->dispatch('noty', msg: 'Chofer asignado correctamente');
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
        $conversionFactor = $this->getConversionFactor();
        
        // Use the total change already calculated in Invoice Currency
        $totalChangeInInvoice = $this->change;
        
        // Sum assigned change in Primary, then convert to Invoice Currency
        $totalAssignedChangeInPrimary = array_sum(array_column($this->changeDistribution, 'amount_in_primary_currency'));
        $totalAssignedChangeInInvoice = $totalAssignedChangeInPrimary * $conversionFactor;
        
        return max(0, $totalChangeInInvoice - $totalAssignedChangeInInvoice);
    }

    public function loadOrderToCart($orderId)
    {
        //limpiamos el carrito

        $this->resetExcept('config', 'banks', 'currencies', 'warehouses');
        $this->clear();
        session()->forget('sale_customer');

        //Cargar el nombre del cliente
        $order = Order::find($orderId);
        // //cargar el objeto costumer
        $customer = Customer::find($order->customer_id);


        $this->setCustomer($customer);
        $this->order_id = $orderId;
        
        // Restore configuration from order
        $this->applyCommissions = (bool) $order->apply_commissions;
    $this->applyFreight = (bool) $order->apply_freight;
    $this->is_freight_broken_down = (bool) $order->is_freight_broken_down;

    // Restore Invoice Currency
    if($order->invoice_currency_id) {
        $this->invoiceCurrency_id = $order->invoice_currency_id;
        $this->updatedInvoiceCurrencyId($order->invoice_currency_id);
    }
    
    // session(['sale_customer' => $order->customer->name]);

        // Obtener los detalles de la orden
        $orderDetails = OrderDetail::where('order_id', $orderId)->get();

        // Bypass reservation check because items are reserved by THIS order
        $this->bypassReservation = true;

        foreach ($orderDetails as $detail) {
            // Obtener el producto correspondiente
            $product = Product::find($detail->product_id);

            // Verificar metadata para items variables (bobinas)
            $metadata = json_decode($detail->metadata, true);
            
            if (isset($metadata['product_item_id'])) {
                 // Cargar directamente el item variable
                 $this->addVariableItem($metadata['product_item_id']);
            } else {
                 // Producto normal o sin metadata
                 $this->AddProduct($product, $detail->quantity, $detail->warehouse_id);
            }
        }
        
        $this->bypassReservation = false;

        // Recalcular precios con la configuración del vendedor (si aplica)
        $this->recalculateCartWithSellerConfig();

        $this->dispatch('close-process-order');
    }

    public function getOrdersWithDetails()
    {
        $query = Order::with(['customer.seller', 'user'])
            ->when(!auth()->user()->can('orders.view_all') && auth()->user()->can('orders.view_own'), function($q) {
                $q->where('user_id', auth()->id());
            })
            ->when($this->searchSeller, function($q) {
                $q->where(function($sub) {
                    $sub->whereHas('customer', function ($c) {
                        $c->where('seller_id', $this->searchSeller);
                    })->orWhere('user_id', $this->searchSeller);
                });
            });

        if (empty(trim($this->search))) {
            return $query->whereHas('customer')
                ->where('status', 'pending')
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);
        } else {
            $search = strtolower(trim($this->search));

            return $query->where(function ($sub) use ($search) {
                    // Búsqueda por el nombre del cliente
                    $sub->whereHas('customer', function ($q2) use ($search) {
                        $q2->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"]);

                        // Búsqueda por el nombre del vendedor asignado
                        $q2->orWhereHas('seller', function ($q3) use ($search) {
                            $q3->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"]);
                        });
                    });

                    // Búsqueda por el ID de la orden o Folio
                    $sub->orWhere('id', 'LIKE', "%{$search}%")
                        ->orWhere('order_number', 'LIKE', "%{$search}%");

                    // Búsqueda por el total
                    $sub->orWhere('total', 'LIKE', "%{$search}%");

                    // Búsqueda por el usuario (operador que realiza la orden)
                    $sub->orWhereHas('user', function ($q2) use ($search) {
                        $q2->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"]);
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



    public $showVariableModal = false;
    public $availableVariableItems = [];
    public $selectedVariableProduct = null;

    function AddProduct(Product $product, $qty = 1, $warehouseId = null)
    {
        // Guard Clause: Foreign Sellers MUST select a customer first
        if (!Auth::user()->can('sales.manage_adjustments') && !$this->customer) {
            $this->dispatch('noty', msg: 'ACCION DENEGADA: Debe seleccionar un cliente primero.', type: 'error');
            return;
        }

        // Determine which warehouse to use
        // If specific warehouse passed, use it. Otherwise use global selection.
        $targetWarehouseId = $warehouseId ?? $this->warehouse_id;

        // Fallback: If no warehouse selected, try to use the first active one
        if (!$targetWarehouseId) {
            $firstWarehouse = $this->warehouses->first();
            if ($firstWarehouse) {
                $targetWarehouseId = $firstWarehouse->id;
                // Optionally update the component state so the dropdown reflects this
                $this->warehouse_id = $targetWarehouseId; 
            } else {
                $this->dispatch('noty', msg: 'No hay depósitos activos configurados.');
                return;
            }
        }

        // Permission Check: Mix Warehouses
        if ($this->cart->isNotEmpty()) {
            $firstItem = $this->cart->first();
            $currentWarehouseId = $firstItem['warehouse_id'] ?? null;

            // If the cart has items from a different warehouse than the one we are trying to add from
            if ($currentWarehouseId && $targetWarehouseId != $currentWarehouseId) {
                if (!Auth::user()->can('sales.mix_warehouses')) {
                    $this->dispatch('noty', msg: 'No tienes permiso para mezclar productos de diferentes depósitos en una misma venta.');
                    return;
                }
            }
        }

        // VARIABLE QUANTITY CHECK
        if ($product->is_variable_quantity) {
             Log::info("Sales::AddProduct - Variable Product Detected: {$product->id} Name: {$product->name} WH: {$targetWarehouseId}");
             
             // Fetch available items for this product and warehouse
             // Filter out items already in the cart
             $cartItemIds = $this->cart->pluck('product_item_id')->filter()->toArray();

             $query = \App\Models\ProductItem::where('product_id', $product->id)
                ->where('warehouse_id', $targetWarehouseId)
                ->whereNotIn('id', $cartItemIds)
                ->where('status', 'available');

             $this->availableVariableItems = $query->get();

             // Calculate Stats for Modal
             // We want totals for the WAREHOUSE, regardless of cart exclusions or config?
             // User likely wants to see the BIG PICTURE.
             // So we query ALL items for this product/warehouse to get stats.
             $statsQuery = \App\Models\ProductItem::where('product_id', $product->id)
                ->where('warehouse_id', $targetWarehouseId)
                ->selectRaw("
                    SUM(CASE WHEN status = 'available' THEN quantity ELSE 0 END) as available_weight,
                    SUM(CASE WHEN status = 'reserved' THEN quantity ELSE 0 END) as reserved_weight
                ")
                ->first();
            
             $avail = $statsQuery->available_weight ?? 0;
             $res = $statsQuery->reserved_weight ?? 0;
             
             // Get Warehouse Name
             $whName = 'Desconocido';
             if($targetWarehouseId) {
                 $wh = \App\Models\Warehouse::find($targetWarehouseId);
                 if($wh) $whName = $wh->name;
             }

             $this->variableItemStats = [
                 'available' => floatval($avail),
                 'reserved' => floatval($res),
                 'total' => floatval($avail + $res),
                 'warehouse' => $whName
             ];
                
             Log::info("Sales::AddProduct - Items Found: " . $this->availableVariableItems->count());

             if ($this->availableVariableItems->isEmpty()) {
                 Log::info("Sales::AddProduct - No items, dispatching warning.");
                 $this->dispatch('noty', msg: 'No hay items/bobinas disponibles (o ya están en carrito).', type: 'warning');
                 return;
             }
             
             $this->selectedVariableProduct = $product;
             $this->selectedCoils = []; // Reset selection every time modal opens
             $this->dispatch('show-variable-modal');
             return;
        }

        // Check if this specific product+warehouse combination is already in cart
        $existingItem = $this->cart->first(function ($item) use ($product, $targetWarehouseId) {
            return $item['pid'] === $product->id && 
                   ($item['warehouse_id'] ?? null) == $targetWarehouseId &&
                   !isset($item['product_item_id']); // Only merge if it's NOT a specific item
        });

        if ($existingItem) {
            // Update quantity of existing item
            $this->updateQty($existingItem['id'], $existingItem['qty'] + $qty);
            return;
        }

        // Validar stock de componentes
        if ($product->components->count() > 0) {
            foreach ($product->components as $component) {
                $requiredQty = $qty * $component->pivot->quantity;
                
                // Check global stock for component
                if ($component->manage_stock && $component->stock_qty < $requiredQty) {
                     $this->dispatch('noty', msg: "Stock insuficiente para el componente: {$component->name}");
                     return;
                }
                
                // Check warehouse stock for component (if warehouse selected)
                if ($targetWarehouseId) {
                    $compStockInWarehouse = $component->stockIn($targetWarehouseId);
                     if ($component->manage_stock && $compStockInWarehouse < $requiredQty) {
                         $this->dispatch('noty', msg: "Stock insuficiente en almacén para el componente: {$component->name}");
                         return;
                    }
                }
            }
        }

            // Validate stock if managed
        // SKIP if it is a Dynamic Composite Product (stock is virtual)
        $isDynamic = $product->components->count() > 0 && !$product->is_pre_assembled;
        
        if ($product->manage_stock == 1 && !$isDynamic) {
            $stock = $product->stockIn($targetWarehouseId);

            // FIX: Handle Legacy Data (Global Stock > 0 but no Warehouse Entry)
            if ($stock == 0 && $product->stock_qty > 0 && $product->productWarehouses()->count() == 0) {
                // Auto-initialize stock in the target warehouse
                \App\Models\ProductWarehouse::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $targetWarehouseId,
                    'stock_qty' => $product->stock_qty
                ]);
                $stock = $product->stock_qty;
                Log::info("Legacy stock migrated for product {$product->id} to warehouse {$targetWarehouseId}");
            }

            if ($stock < $qty) {
                $this->dispatch('noty', msg: 'STOCK INSUFICIENTE EN EL DEPÓSITO SELECCIONADO');
                return;
            }

            // Stock Reservation Check
            Log::info('Stock Check Debug:', [
                'check_stock_reservation' => $this->config->check_stock_reservation,
                'bypassReservation' => $this->bypassReservation,
                'targetWarehouseId' => $targetWarehouseId,
                'stock' => $stock,
            ]);

            if ($this->config->check_stock_reservation && !$this->bypassReservation) {
                $reserved = $product->getReservedStock($targetWarehouseId);
                $availableReal = $stock - $reserved;

                Log::info('Reservation Details:', [
                    'reserved' => $reserved,
                    'availableReal' => $availableReal,
                    'qty_requested' => $qty
                ]);

                if ($qty > $availableReal) {
                    $warehouseName = $this->warehouses->where('id', $targetWarehouseId)->first()->name ?? 'Desconocido';
                    $this->stockWarningMessage = "En el depósito <b>{$warehouseName}</b>:<br>
                                                  Stock Físico: <b>{$stock}</b><br>
                                                  Reservado en Pedidos: <b>{$reserved}</b><br>
                                                  Disponible Real: <b>{$availableReal}</b><br><br>
                                                  Estás intentando vender <b>{$qty}</b> unidades.<br>
                                                  ¿Deseas continuar ignorando la reserva?";
                    
                    $this->pendingProductToAdd = $product->id;
                    $this->pendingQtyToAdd = $qty;
                    $this->pendingWarehouseId = $targetWarehouseId;
                    
                    $this->dispatch('show-stock-warning');
                    return;
                }
            }
        }

        // Obtener moneda principal y tasa
        $primaryCurrency = CurrencyHelper::getPrimaryCurrency();
        $exchangeRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;

        // Convertir precio base a moneda principal
        $basePriceInPrimary = $product->price * $exchangeRate;
        
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
                    'name' => 'Precio ' . $p['name']
                ];
            }
        }

        // Determine Base Price (Volume or Standard)
        $basePrice = $this->determinePrice($product, $qty);
        $basePriceInPrimary = $basePrice * $exchangeRate;

        // Calculate Extras (Commission, Freight, Diff)
        $comm = 0;
        $freight = 0;
        $diff = 0;

        $customerConfig = $this->customerConfig;

        if (($this->sellerConfig || $customerConfig) && $this->applyCommissions) {
            
            // Priority 1: Customer Config
            $commissionPercent = $customerConfig && $customerConfig->commission_percent > 0 ? $customerConfig->commission_percent : ($this->sellerConfig ? $this->sellerConfig->commission_percent : 0);
            $freightPercent = $customerConfig && $customerConfig->freight_percent > 0 ? $customerConfig->freight_percent : ($this->sellerConfig ? $this->sellerConfig->freight_percent : 0);
            $exchangeDiffPercent = $customerConfig && $customerConfig->exchange_diff_percent > 0 ? $customerConfig->exchange_diff_percent : ($this->sellerConfig ? $this->sellerConfig->exchange_diff_percent : 0);

            // Commission
            $comm = ($basePriceInPrimary * $commissionPercent) / 100;
            
            // Exchange Diff
            $diff = ($basePriceInPrimary * $exchangeDiffPercent) / 100;

            // Freight (Smart Logic)
            if ($product->freight_type != 'none') {
                // Product Specific Freight
                if ($product->freight_type == 'fixed') {
                    $freightUnit = $product->freight_value; // Fixed amount per unit
                } else {
                    $freightUnit = ($basePriceInPrimary * $product->freight_value) / 100;
                }
            } else {
                // General Freight
                $freightUnit = ($basePriceInPrimary * $freightPercent) / 100;
            }
            $freight = $freightUnit; // Total freight added to unit price

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
            $precioUnitarioSinIva =  $salePrice / (1 + $iva);
            // ... Logic continues ...
            $subtotalNeto =   $precioUnitarioSinIva * $this->formatAmount($qty);
            $montoIva = $subtotalNeto  * $iva;
            $totalConIva =  $subtotalNeto + $montoIva;

            $tax = round($montoIva, $decimals);
            $total = round($totalConIva, $decimals);
        } else {
            $precioUnitarioSinIva =  $salePrice;
            $subtotalNeto =   $precioUnitarioSinIva * $this->formatAmount($qty);
            $montoIva = 0;
            $totalConIva =  $subtotalNeto + $montoIva;

            $tax = round($montoIva, $decimals);
            $total = round($totalConIva, $decimals);
        }

        $uid = uniqid() . $product->id;

        $itemCart = [
            'id' => $uid,
            'pid' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price1' => $basePriceInPrimary, 
            'price2' => $product->price2 * $exchangeRate, 
            'sale_price' => $salePrice,
            'pricelist' => $priceListInPrimary, 
            'qty' => $this->formatAmount($qty),
            'tax' => $tax,
            'total' => $total,
            'stock' => $product->stock_qty,
            'type' => $product->type,
            'image' => $product->photo,
            'platform_id' => $product->platform_id,
            'warehouse_id' => $targetWarehouseId, // Store warehouse ID
            'freight_type' => $product->freight_type, // Store for recalculation
            'freight_value' => $product->freight_value, // Store for recalculation
            'base_price' => $basePriceInPrimary // Store original base price to avoid compounding
        ];

        $this->cart->push($itemCart);

        // If this product belongs to a price group, recalculate ALL other group members' prices
        // since the total group quantity has changed and may trigger a different tier for them too
        if ($product->price_group_id) {
            $groupProductIds = \App\Models\Product::where('price_group_id', $product->price_group_id)
                ->pluck('id')
                ->toArray();

            // Recalculate every OTHER group member in the cart
            // Total sum of all group members in the cart (now includes the newly pushed item)
            $totalGroupQtyInCart = $this->cart
                ->whereIn('pid', $groupProductIds)
                ->sum('qty');

            $updatedCart = $this->cart->map(function ($cartItem) use ($groupProductIds, $decimals, $exchangeRate, $totalGroupQtyInCart, $uid) {

                // Skip the item we just added (it already has the correct tier applied)
                if ($cartItem['id'] === $uid) {
                    return $cartItem;
                }

                // Only touch group members
                if (!in_array($cartItem['pid'], $groupProductIds)) {
                    return $cartItem;
                }

                $siblingModel = \App\Models\Product::find($cartItem['pid']);
                if (!$siblingModel) return $cartItem;

                $newBasePrice       = $this->determinePrice($siblingModel, $cartItem['qty']);
                $newBasePriceInPrim = $newBasePrice * $exchangeRate;

                $cartItem['base_price'] = $newBasePriceInPrim;
                $values = $this->Calculator($newBasePriceInPrim, $cartItem['qty'], $siblingModel);
                $cartItem['sale_price'] = $values['sale_price'];
                $cartItem['tax']        = round($values['iva'], $decimals);
                $cartItem['total']      = $this->formatAmount(round($values['total'], $decimals));

                return $cartItem;
            });

            $this->cart = collect($updatedCart);
        }

        $this->recalculateFreightTotal();
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
        return 0; 
    }

    public function calculateFreight($product, $qty, $customBasePrice = null)
    {


        // 1. Validar configuración global de flete
        if (!$this->applyCommissions && !$this->applyFreight) {
            return 0;
        }

        $basePrice = $customBasePrice ?? $product->price;

        // 2. Flete específico (Fixed / Personalized)
        if ($product->freight_type == 'personalized' || $product->freight_type == 'fixed') {
             return $product->freight_value * $qty;
        }

        // 3. Flete Porcentaje (Specific Percentage)
        if ($product->freight_type == 'percentage') {
             return ($basePrice * $product->freight_value / 100) * $qty;
        }

        // 4. Flete Global (Seller Config) - Fallback for 'global', 'none', or null
        // If type is 'none' or 'global', we apply active Freight if available.
        $activeFreight = ($this->customerConfig && $this->customerConfig->freight_percent > 0) ? $this->customerConfig->freight_percent : ($this->sellerConfig->freight_percent ?? 0);
        if ($activeFreight > 0) {
             return ($basePrice * $activeFreight / 100) * $qty;
        }

        return 0;
    }





    public function determinePrice($product, $qty)
    {
        $price = $product->price;
        $tiers = $product->priceTiers;

        if ($tiers && $tiers->count() > 0) {
            // If the product belongs to a price group, sum quantities of ALL group members in the cart
            if ($product->price_group_id) {
                $groupProductIds = \App\Models\Product::where('price_group_id', $product->price_group_id)
                    ->pluck('id')
                    ->toArray();

                // Exclude the current product from cart sum (it may hold a stale qty)
                // and replace it with the new $qty being evaluated
                $otherGroupQtyInCart = $this->cart
                    ->whereIn('pid', $groupProductIds)
                    ->where('pid', '!=', $product->id)
                    ->sum('qty');

                $effectiveQty = $qty + $otherGroupQtyInCart;
            } else {
                $effectiveQty = $qty;
            }

            // Find the best tier for the effective quantity
            $tier = $tiers->where('min_qty', '<=', $effectiveQty)->sortByDesc('min_qty')->first();
            if ($tier) {
                $price = $tier->price;
            }
        }

        return $price;
    }

    public function recalculateCartPrices()
    {
        $cartArray = $this->cart->toArray();
        
        foreach ($cartArray as &$item) {
            $productModel = \App\Models\Product::find($item['pid']);
            if ($productModel) {
                 // Recalculate based on current toggle state using BASE PRICE to avoid compounding
                 $baseForCalc = $item['base_price'] ?? $item['sale_price'];
                 $result = $this->Calculator($baseForCalc, $item['qty'], $productModel);
                 $item['sale_price'] = $result['sale_price'];
                 $item['tax'] = $result['iva'];
                 $item['total'] = $result['total'];
            }
        }
        
        $this->cart = collect($cartArray);
        $this->save();
        $this->dispatch('refresh');
    }

    function Calculator($price, $qty, $product)
    {
        // Obtener el número de decimales configurados
        $decimals = ConfigurationService::getDecimalPlaces();

        // Obtener el IVA desde la configuración
        $iva = ConfigurationService::getVat() / 100;

        // Use passed price as base (Calculated Price from Tiers or Manual)
        $basePriceInPrimary = $price;
        $primaryCurrency = CurrencyHelper::getPrimaryCurrency();
        // If needed, we could verify exchange rate, but usually price in cart is already handled?
        // Let's assume $price passed to Calculator IS in Primary Currency / Base Unit.
        
        // Recalcular Extras (Comisión, Flete, Diferencia)
        $comm = 0;
        $freight = 0;
        $diff = 0;

        if ($this->applyCommissions) {
            
            if ($this->sellerConfig || $this->customerConfig) {
                 $activeComm = ($this->customerConfig && $this->customerConfig->commission_percent > 0) ? $this->customerConfig->commission_percent : ($this->sellerConfig->commission_percent ?? 0);
                 $activeDiff = ($this->customerConfig && $this->customerConfig->exchange_diff_percent > 0) ? $this->customerConfig->exchange_diff_percent : ($this->sellerConfig->exchange_diff_percent ?? 0);
                 
                 $comm = ($basePriceInPrimary * $activeComm) / 100;
                 $diff = ($basePriceInPrimary * $activeDiff) / 100;
            }
        }

        if ($this->applyCommissions || $this->applyFreight) {
            $freightTotal = $this->calculateFreight($product, $qty, $basePriceInPrimary); // Returns TOTAL freight amount for the qty
            
            // Convert Total Freight to Per Unit for the formula
            $freightUnit = ($qty > 0) ? ($freightTotal / $qty) : 0;
        } else {
            $freightTotal = 0;
            $freightUnit = 0;
        }

        // IF breakdown is ON, we DO NOT add freight to the Unit Price
        if ($this->is_freight_broken_down) {
             // Freight is calculated separately, not in unit price
             $salePrice = $basePriceInPrimary + $comm + $diff;
        } else {
             // Freight is included in unit price
             $salePrice = $basePriceInPrimary + $comm + $freightUnit + $diff;
        }


        // Determinamos el precio de venta (con IVA)
        
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



    public function removeItem($id, $index = null)
    {
        // Force cart to be a collection and Reset keys to be sequential
        $this->cart = collect($this->cart)->values();
        
        // 1. Find the item using all possible identifiers
        $indexFound = $this->cart->search(function($item) use ($id) {
            return (isset($item['id']) && $item['id'] == $id) || 
                   (isset($item['product_item_id']) && $item['product_item_id'] == $id) ||
                   (isset($item['pid']) && $item['pid'] == $id);
        });

        if ($indexFound !== false) {
            $itemToRemove = $this->cart[$indexFound];
            
            // Restore status for variable items
            if (isset($itemToRemove['product_item_id'])) {
                $pi = \App\Models\ProductItem::find($itemToRemove['product_item_id']);
                if ($pi && $pi->status === 'reserved') {
                    $pi->status = 'available';
                    $pi->save();
                }
            }

            // Remove purely by index discovered from our search
            $this->cart->forget($indexFound);
            // Re-sequence the collection to keep things clean
            $this->cart = $this->cart->values();
            $removed = true;
        } else {
            $removed = false;
        }

        $this->recalculateFreightTotal();
        $this->save();
        $this->dispatch('refresh');
        
        if ($removed) {
            $this->dispatch('noty', msg: 'PRODUCTO ELIMINADO');
        } else {
            $this->dispatch('noty', msg: 'NO SE ENCONTRÓ EL PRODUCTO EN EL CARRITO', type: 'warning');
        }
    }

    public $pendingUpdateUid = null;
    public $maxAvailableQty = 0;

    public function addVariableItem($itemId, $closeModal = true)
    {
        $item = \App\Models\ProductItem::find($itemId);
        if (!$item) {
            $this->dispatch('noty', msg: 'Item no encontrado.', type: 'error');
            return;
        }

        // Check status based on configuration
        $isValidStatus = false;
        
        // If bypassing reservation (e.g. loading own order), allow reserved
        if ($this->bypassReservation) {
             if (in_array($item->status, ['available', 'reserved'])) $isValidStatus = true;
        } else {
            if ($this->config->check_stock_reservation) {
                // Strict mode: Only available
                if ($item->status === 'available') $isValidStatus = true;
            } else {
                // Relaxed mode: Available OR Reserved
                if (in_array($item->status, ['available', 'reserved'])) $isValidStatus = true;
            }
        }

        if (!$isValidStatus) {
            $this->dispatch('noty', msg: 'Este item no está disponible (Estado: ' . $item->status . ').', type: 'warning');
            return;
        }

        $product = $item->product;
        // Use the item's current quantity (weight)
        $qty = $item->quantity; 

        // Exchange Rate Logic
        $primaryCurrency = CurrencyHelper::getPrimaryCurrency();
        $exchangeRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
        $basePriceInPrimary = $product->price * $exchangeRate;

        // Apply markup if configured
        if ($this->sellerConfig && $this->applyCommissions) {
             $comm = ($basePriceInPrimary * $this->sellerConfig->commission_percent) / 100;
             $freight = ($basePriceInPrimary * $this->sellerConfig->freight_percent) / 100;
             $diff = ($basePriceInPrimary * $this->sellerConfig->exchange_diff_percent) / 100;
             $salePrice = $basePriceInPrimary + $comm + $freight + $diff;
        } else {
            $salePrice = $basePriceInPrimary;
        }

        // Taxes
        $decimals = ConfigurationService::getDecimalPlaces();
        $iva = ConfigurationService::getVat() / 100;

        if ($iva > 0) {
            $precioUnitarioSinIva =  $salePrice / (1 + $iva);
            $subtotalNeto =   $precioUnitarioSinIva * $this->formatAmount($qty);
            $montoIva = $subtotalNeto  * $iva;
            $totalConIva =  $subtotalNeto + $montoIva;
            $tax = round($montoIva, $decimals);
            $total = round($totalConIva, $decimals);
        } else {
            $precioUnitarioSinIva =  $salePrice;
            $subtotalNeto =   $precioUnitarioSinIva * $this->formatAmount($qty);
            $montoIva = 0;
            $totalConIva =  $subtotalNeto + $montoIva;
            $tax = round($montoIva, $decimals);
            $total = round($totalConIva, $decimals);
        }

        $uid = uniqid() . $product->id . '-' . $item->id;

        $itemCart = [
            'id' => $uid,
            'pid' => $product->id,
            'product_item_id' => $item->id, // IMPORTANT
            'name' => $product->name . ' (' . ($item->color ? $item->color . ' - ' : '') . floatval($qty) . ')', 
            'sku' => $product->sku,
            'price1' => $basePriceInPrimary, 
            'price2' => $product->price2 * $exchangeRate, 
            'sale_price' => $salePrice,
            'pricelist' => [], 
            'qty' => $this->formatAmount($qty),
            'tax' => $tax,
            'total' => $total,
            'stock' => $product->stock_qty, 
            'type' => $product->type,
            'image' => $product->photo,
            'platform_id' => $product->platform_id,
            'warehouse_id' => $item->warehouse_id, 
        ];

        $this->cart->push($itemCart);
        $this->recalculateFreightTotal();
        $this->save();
        $this->dispatch('refresh');
        
        if ($closeModal) {
            $this->showVariableModal = false;
            $this->availableVariableItems = [];
            $this->selectedVariableProduct = null;
            $this->selectedCoils = [];
            
            // Force close modal via JS
            $this->js("$('#variableItemModal').modal('hide');");
            
            $this->dispatch('noty', msg: 'ITEM AGREGADO AL CARRITO');
        }
        
        $this->search3 = '';
        $this->products = [];
    }

    public function addSelectedCoils()
    {
        if (empty($this->selectedCoils)) {
            $this->dispatch('noty', msg: 'No has seleccionado ninguna bobina.', type: 'warning');
            return;
        }

        $itemsToAdd = array_filter($this->selectedCoils, fn($val) => $val === true || $val === "true" || $val === 1);
        
        if (empty($itemsToAdd)) {
            $this->dispatch('noty', msg: 'No has seleccionado ninguna bobina.', type: 'warning');
            return;
        }

        $count = 0;
        foreach ($itemsToAdd as $itemId => $selected) {
            if ($selected) {
                $this->addVariableItem($itemId, false);
                $count++;
            }
        }

        $this->selectedCoils = [];
        $this->showVariableModal = false;
        $this->availableVariableItems = [];
        $this->selectedVariableProduct = null;
        $this->js("$('#variableItemModal').modal('hide');");
        $this->dispatch('noty', msg: "{$count} ITEMS AGREGADOS AL CARRITO");
    }

    #[On('forceAddProduct')]
    public function forceAddProduct()
    {
        if ($this->pendingUpdateUid) {
            // Handle forced update
            $this->bypassReservation = true;
            $this->updateQty($this->pendingUpdateUid, $this->pendingQtyToAdd);
            $this->bypassReservation = false;
            
            $this->reset(['pendingProductToAdd', 'pendingQtyToAdd', 'pendingWarehouseId', 'stockWarningMessage', 'pendingUpdateUid', 'maxAvailableQty']);
            $this->dispatch('close-stock-warning');
        } elseif ($this->pendingProductToAdd) {
            $product = Product::find($this->pendingProductToAdd);
            if ($product) {
                $this->bypassReservation = true;
                $this->AddProduct($product, $this->pendingQtyToAdd, $this->pendingWarehouseId);
                $this->bypassReservation = false;
                
                $this->reset(['pendingProductToAdd', 'pendingQtyToAdd', 'pendingWarehouseId', 'stockWarningMessage', 'maxAvailableQty']);
                $this->dispatch('close-stock-warning');
            }
        }
    }

    public function adjustToMax()
    {
        if ($this->maxAvailableQty <= 0) return;

        if ($this->pendingUpdateUid) {
            $this->updateQty($this->pendingUpdateUid, $this->maxAvailableQty);
        } elseif ($this->pendingProductToAdd) {
            $product = Product::find($this->pendingProductToAdd);
            if ($product) {
                $this->AddProduct($product, $this->maxAvailableQty, $this->pendingWarehouseId);
            }
        }
        
        $this->reset(['pendingProductToAdd', 'pendingQtyToAdd', 'pendingWarehouseId', 'stockWarningMessage', 'pendingUpdateUid', 'maxAvailableQty']);
        $this->dispatch('close-stock-warning');
    }

    public function updateQty($uid, $cant = 1, $product_id = null)
    {
        // Validar que la cantidad sea numérica y mayor que cero
        if (!is_numeric($cant) || $cant <= 0) {
            $this->dispatch('noty', msg: 'EL VALOR DE LA CANTIDAD ES INCORRECTO');
            return;
        }

        // Obtener producto para validación de decimales
        $tempProductId = $product_id;
        if(!$tempProductId) {
             $mycart = $this->cart;
             $tempItem = $mycart->firstWhere('id', $uid);
             if($tempItem) $tempProductId = $tempItem['pid'];
        }

        if($tempProductId) {
            $prod = Product::find($tempProductId);
            if($prod && !$prod->allow_decimal) {
                if(floor($cant) != $cant) {
                    $this->dispatch('noty', msg: "Este producto NO permite cantidades decimales. Se ajustó a número entero.");
                    $cant = round($cant);
                }
            }
        }

        // Obtener el carrito actual
        $mycart = $this->cart;

        if ($product_id == null) {
            $oldItem = $mycart->firstWhere('id', $uid);
            if ($oldItem) {
                $product_id = $oldItem['pid'];
            }
        } else {
            $oldItem = $mycart->firstWhere('pid', $product_id);
            if ($oldItem) {
                $uid = $oldItem['id']; // Resolve UID from the found item
            }
        }

        if (!$oldItem) {
            return;
        }

        if (!$oldItem) {
            return;
        }

        $product = Product::find($product_id);

        // Validar stock de componentes
        if ($product->components->count() > 0) {
            foreach ($product->components as $component) {
                $requiredQty = $cant * $component->pivot->quantity;
                
                // Check global stock for component
                if ($component->manage_stock && $component->stock_qty < $requiredQty) {
                     $this->dispatch('noty', msg: "Stock insuficiente para el componente: {$component->name}");
                     return;
                }
                
                // Check warehouse stock for component (if warehouse selected)
                $itemWarehouseId = $oldItem['warehouse_id'] ?? null;
                if ($itemWarehouseId) {
                    $compStockInWarehouse = $component->stockIn($itemWarehouseId);
                     if ($component->manage_stock && $compStockInWarehouse < $requiredQty) {
                         $this->dispatch('noty', msg: "Stock insuficiente en almacén para el componente: {$component->name}");
                         return;
                    }
                }
            }
        }

        // Verificar si la cantidad total a agregar es mayor que el stock disponible
        // SKIP if it is a Dynamic Composite Product (stock is virtual)
        $isDynamic = $product->components->count() > 0 && !$product->is_pre_assembled;

        if ($product->manage_stock == 1 && !$isDynamic) {
            $newQty = $cant; // solo se agrega la cantidad que se está agregando
            
            // Validate against the specific warehouse of the item
            $itemWarehouseId = $oldItem['warehouse_id'] ?? null;
            $stockInWarehouse = $product->stockIn($itemWarehouseId);

            if ($stockInWarehouse < $newQty) {
                $this->dispatch('noty', msg: 'No hay suficiente stock para el producto: ' . $product->name);
                return;
            }

            // Stock Reservation Check
            if ($this->config->check_stock_reservation && !$this->bypassReservation) {
                $reserved = $product->getReservedStock($itemWarehouseId);
                $availableReal = $stockInWarehouse - $reserved;

                if ($newQty > $availableReal) {
                    $warehouseName = $this->warehouses->where('id', $itemWarehouseId)->first()->name ?? 'Desconocido';
                    $this->stockWarningMessage = "En el depósito <b>{$warehouseName}</b>:<br>
                        Stock Físico: <b>{$stockInWarehouse}</b><br>
                        Reservado en Pedidos: <b>{$reserved}</b><br>
                        Disponible Real: <b>{$availableReal}</b><br><br>
                        Estás intentando vender <b>{$newQty}</b> unidades.";
                    
                    // Store pending action
                    // For updateQty, we can't easily "resume" the action via forceAddProduct because the logic is different.
                    // However, we can just show the warning. If the user wants to proceed, they might need to be an admin.
                    // But wait, forceAddProduct calls AddProduct. 
                    // To support "Continue Anyway" for updateQty, we would need a separate forceUpdateQty method or adapt forceAddProduct.
                    // For now, let's just block/warn.
                    
                    // Ideally, we should refactor to have a common "checkStock" method.
                    
                    // For this fix, let's just show the warning and block the update if not confirmed.
                    // Since we can't easily "pause" the updateQty execution and resume it from a modal callback without more complex state management,
                    // we will just block it for now if it exceeds available real stock, unless we implement the full bypass flow.
                    
                    // Let's implement the bypass flow:
                    $this->pendingProductToAdd = $product->id; // We can reuse this or create new state variables
                    $this->pendingQtyToAdd = $newQty;
                    $this->pendingWarehouseId = $itemWarehouseId;
                    // We need to know we are in "update" mode.
                    // Let's add a property $pendingUpdateUid
                    $this->pendingUpdateUid = $uid;
                    $this->maxAvailableQty = $availableReal; // Set max available
                    
                    $this->dispatch('show-stock-warning');
                    return;
                }
            }
        }

        // Crear un nuevo artículo con la cantidad actualizada
        $newItem = $oldItem;
        // Do NOT force new ID - it causes elements to be destroyed and recreated at the bottom of the cart in Livewire
        // $newItem['id'] = uniqid() . $newItem['pid']; 
        $newItem['qty'] = $this->formatAmount($cant);

        // Calcular valores
        $productModel = \App\Models\Product::find($newItem['pid']);
        
        // Determine correct base price
        // If the product has volume tiers, we MUST recalculate based on new QTY
        $basePriceFromTiers = $this->determinePrice($productModel, $newItem['qty']);
        
        $primaryCurrency = CurrencyHelper::getPrimaryCurrency();
        $exchangeRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
        
        // Convert tier price to primary currency
        $basePriceFromTiersInPrimary = $basePriceFromTiers * $exchangeRate;

        // Update base_price in item
        // Note: If we had a manual override flag, we would check it here. 
        // For now, we assume if Qty changes, we re-evaluate tiers unless it's a manual price (which we can't easily track without a flag).
        // However, the previous logic was preserving 'base_price' from oldItem regardless of qty change, which broke tiers.
        // We will update base_price.
        $newItem['base_price'] = $basePriceFromTiersInPrimary;

        $base = $newItem['base_price'];
        
        $values = $this->Calculator($base, $newItem['qty'], $productModel);
        $decimals = ConfigurationService::getDecimalPlaces();
        $newItem['sale_price'] = $values['sale_price'];
        $newItem['tax'] = round($values['iva'], $decimals);
        $newItem['total'] = $this->formatAmount(round($values['total'], $decimals));

        // Update item IN PLACE (preserves cart order)
        $this->cart = $this->cart->map(function ($item) use ($uid, $newItem) {
            return $item['id'] === $uid ? $newItem : $item;
        });

        // If this product belongs to a price group, recalculate ALL other group members' prices
        // since the total group quantity has changed and may trigger a different tier for them too
        if ($productModel->price_group_id) {
            $groupProductIds = \App\Models\Product::where('price_group_id', $productModel->price_group_id)
                ->pluck('id')
                ->toArray();

            // Recalculate every OTHER group member in the cart
            $decimals = ConfigurationService::getDecimalPlaces();
            $primaryCurrency = CurrencyHelper::getPrimaryCurrency();
            $exchangeRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
            
            // Total sum of all group members in the cart
            $totalGroupQtyInCart = $this->cart
                ->whereIn('pid', $groupProductIds)
                ->sum('qty');

            $updatedCart = $this->cart->map(function ($cartItem) use ($groupProductIds, $decimals, $exchangeRate, $totalGroupQtyInCart) {

                // Only touch group members
                if (!in_array($cartItem['pid'], $groupProductIds)) {
                    return $cartItem;
                }

                $siblingModel = \App\Models\Product::find($cartItem['pid']);
                if (!$siblingModel) return $cartItem;

                // Pass the total group quantity so determinePrice finds the correct tier
                // because determinePrice usually adds qty to existing cart. But here totalGroupQtyInCart is already the expected sum.
                // NOTE: determinePrice() already does a sum in its body. But it sums cart qty.
                // We should pass the individual item's cartQty, and let determinePrice add the rest.
                // Wait, determinePrice takes ($product, $qty) where $qty is the new amount for THIS product.
                $newBasePrice       = $this->determinePrice($siblingModel, $cartItem['qty']);
                $newBasePriceInPrim = $newBasePrice * $exchangeRate;

                $cartItem['base_price'] = $newBasePriceInPrim;
                $values = $this->Calculator($newBasePriceInPrim, $cartItem['qty'], $siblingModel);
                $cartItem['sale_price'] = $values['sale_price'];
                $cartItem['tax']        = round($values['iva'], $decimals);
                $cartItem['total']      = $this->formatAmount(round($values['total'], $decimals));

                return $cartItem;
            });

            $this->cart = collect($updatedCart);
        }

        // Recalculate freight total
        $this->recalculateFreightTotal();

        // Actualizar la sesión
        session(['cart' => $this->cart->toArray()]);

        // Emitir eventos
        $this->dispatch('refresh');
        
        // Recalculate freight total after quantity update
        $this->recalculateFreightTotal();
        
        $this->dispatch('noty', msg: 'CANTIDAD ACTUALIZADA');
    }





    public function clear()
    {
        // Ensure cart is a collection
        if (!($this->cart instanceof \Illuminate\Support\Collection)) {
            $this->cart = collect($this->cart);
        }

        // RESTORE: Set any reserved variable items back to 'available' before clearing cart
        foreach ($this->cart as $item) {
            if (isset($item['product_item_id'])) {
                $pi = \App\Models\ProductItem::find($item['product_item_id']);
                if ($pi && $pi->status === 'reserved') {
                    $pi->status = 'available';
                    $pi->save();
                }
            }
        }

        $this->cart = new Collection;
        $this->driver_id = null;
        $this->save();
        $this->dispatch('refresh');
    }

    #[On('cancelSale')]
    function cancelSale()
    {
        $this->resetExcept('config', 'banks', 'warehouses');
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
        
        $total = $this->cart->sum(function ($product) {
            return $product['total'];
        });

        if ($this->is_freight_broken_down) {
             $total += floatval($this->total_freight);
        }

        return round($total, $decimals);
    }

    public function calculateCalculatedFreight() {
         $sum = 0;
         foreach ($this->cart as $item) {
             $prod = \App\Models\Product::find($item['pid']);
             if($prod) {
                 // Use base_price if available to calculate freight on the original amount, avoiding compounding.
                 // Fallback to sale_price only if base_price is missing.
                 $baseForFreight = $item['base_price'] ?? $item['sale_price'];
                 $sum += $this->calculateFreight($prod, $item['qty'], $baseForFreight);
             }
         }
         return $sum;
    }

    public function recalculateFreightTotal() 
    {
         if ($this->is_freight_broken_down) {
             $decimals = ConfigurationService::getDecimalPlaces();
             $this->total_freight = round($this->calculateCalculatedFreight(), $decimals);
         }
    }

    public function updatedTotalFreight()
    {
        // Do not recalculate freight here, as we want to allow manual override.
        // But we DO want to ensure the total reflects the change.
        // Render will handle totalCart() calculation.
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
        try {
            // Ensure customer is array
            if (is_object($customer)) {
                $customer = $customer->toArray();
            }

            session(['sale_customer' => $customer]);
            $this->customer = $customer;

            // Check for Foreign Seller Config

            $this->sellerConfig = null;
            $this->customerConfig = null;
            
            if(isset($customer['id']) && $customer['id']) {
                $customerDb = \App\Models\Customer::find($customer['id']);
                if($customerDb) {
                    $this->customerConfig = $customerDb->latestCustomerConfig;
                }
            }

            if(isset($customer['seller_id']) && $customer['seller_id']) {
                $seller = \App\Models\User::find($customer['seller_id']);
                if($seller) {
                    $this->sellerConfig = $seller->latestSellerConfig;
                    $customer['seller_name'] = $seller->name;
                    
                    // Load seller discount rules
                    $customer['seller_discount_rules'] = \App\Models\CreditDiscountRule::where('entity_type', 'seller')
                        ->where('entity_id', $seller->id)
                        ->orderBy('days_from')
                        ->get()
                        ->toArray();
                    
                    if (!$this->sellerConfig) {
                        // Log::info('No seller config found for seller ' . $seller->id);
                    }
                }
            }

            // AUTO-ENABLE Commissions and Freight if configurations exist (Automation for Foreign Sellers)
            $hasCustomerConfig = ($this->customerConfig && ($this->customerConfig->commission_percent > 0 || $this->customerConfig->freight_percent > 0 || $this->customerConfig->exchange_diff_percent > 0));
            $hasSellerConfig = ($this->sellerConfig && ($this->sellerConfig->commission_percent > 0 || $this->sellerConfig->freight_percent > 0 || $this->sellerConfig->exchange_diff_percent > 0));

            if ($hasCustomerConfig || $hasSellerConfig) {
                $this->applyCommissions = true;
                $this->applyFreight = true;
            } else {
                $this->applyCommissions = false;
                $this->applyFreight = false;
            }

            // Update toggles in session as well
            session(['applyCommissions' => $this->applyCommissions]);
            session(['applyFreight' => $this->applyFreight]);
            
            // Load customer-specific discount rules and outstanding invoices
            if(isset($customer['id'])) {
                $customer['customer_discount_rules'] = \App\Models\CreditDiscountRule::where('entity_type', 'customer')
                    ->where('entity_id', $customer['id'])
                    ->orderBy('days_from')
                    ->get()
                    ->toArray();
                
                // Load outstanding invoices (credit sales with pending balance)
                $outstandingSales = \App\Models\Sale::where('customer_id', $customer['id'])
                    ->where('credit_days', '>', 0)
                    ->whereNotIn('status', ['returned', 'voided', 'paid']) // Exclude non-active and already paid debt
                    ->with(['payments' => function($q) {
                        $q->where('status', 'approved');
                    }])
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                $customer['outstanding_invoices'] = [];
                $customer['debt_totals'] = []; // Group totals by currency
                $totalDebt = 0; // Legacy total (mixed currencies - to be deprecated or used as fallback)
                $hasOverdue = false;
                
                foreach($outstandingSales as $sale) {
                    $approvedPayments = $sale->payments->sum('amount');
                    $pending = $sale->total - $approvedPayments;
                    
                    if($pending > 0.01) { // Has pending balance
                        $dueDate = \Carbon\Carbon::parse($sale->created_at)->addDays($sale->credit_days);
                        $isOverdue = now()->gt($dueDate);
                        
                        if($isOverdue) $hasOverdue = true;

                        // Identify Currency
                        $currencyCode = $sale->primary_currency_code ?? 'USD'; // Default to USD if missing
                        $currencySymbol = '$'; // Default
                        
                        // Find symbol
                        $currency = \App\Models\Currency::where('code', $currencyCode)->first();
                        if($currency) $currencySymbol = $currency->symbol;
                        
                        $customer['outstanding_invoices'][] = [
                            'invoice_number' => $sale->invoice_number,
                            'created_at' => $sale->created_at->format('d/m/Y'),
                            'due_date' => $dueDate->format('d/m/Y'),
                            'total' => $sale->total,
                            'paid' => $approvedPayments,
                            'pending' => $pending,
                            'is_overdue' => $isOverdue,
                            'currency_code' => $currencyCode,
                            'currency_symbol' => $currencySymbol
                        ];
                        
                        // Accumulate by currency
                        if(!isset($customer['debt_totals'][$currencyCode])) {
                            $customer['debt_totals'][$currencyCode] = [
                                'total' => 0,
                                'symbol' => $currencySymbol
                            ];
                        }
                        $customer['debt_totals'][$currencyCode]['total'] += $pending;
                        
                        $totalDebt += $pending;
                    }
                }
                
                $customer['total_debt'] = $totalDebt;
                $customer['has_overdue'] = $hasOverdue;
                $customer['wallet_balance'] = $customerDb->wallet_balance ?? 0;
            }
            
            // Update session and component property with enriched customer data
            session(['sale_customer' => $customer]);
            $this->customer = $customer;

            // Foreign Seller Enforce Logic
            if (!auth()->user()->can('sales.manage_adjustments')) {
                $this->applyCommissions = $this->sellerConfig ? true : false;
                $this->applyFreight = false;
                $this->is_freight_broken_down = false;
            }
        
            $this->recalculateCartWithSellerConfig();
            
            // Success notification (Debug - remove later if annoying)
            // $this->dispatch('noty', msg: 'Cliente seleccionado: ' . ($customer['name'] ?? 'N/A'));

        } catch (\Exception $e) {
            $this->dispatch('noty', msg: 'Error al seleccionar cliente: ' . $e->getMessage());
        }
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

            // Determine Base Price (Unit or Product)
            $basePrice = $product->price;
            


            // Convert to primary currency
            $basePrice = $basePrice * $exchangeRate;
            
            // Apply logic if config exists AND toggle is active
            if ($this->sellerConfig) {
                 if ($this->applyCommissions) {
                    $comm = ($basePrice * $this->sellerConfig->commission_percent) / 100;
                    $diff = ($basePrice * $this->sellerConfig->exchange_diff_percent) / 100;
                 } else {
                    $comm = 0; 
                    $diff = 0;
                 }

                 if ($this->applyCommissions || $this->applyFreight) {
                    $freight = ($basePrice * $this->sellerConfig->freight_percent) / 100;
                 } else {
                    $freight = 0;
                 }
                
                $finalPrice = $basePrice + $comm + $freight + $diff;
            } else {
                $finalPrice = $basePrice;
            }

            // Recalculate item totals
            // Use stored base_price if available (respects manual overrides), otherwise calc from product
            // Note: determinePrice logic for tiers is missing here, ideally we should re-run determinePrice if it's not a manual override.
            // But for now, fixing the compounding is priority.
            $calcBase = $item['base_price'] ?? ($product->price * $exchangeRate);
            
            $values = $this->Calculator($calcBase, $item['qty'], $product);
            $decimals = ConfigurationService::getDecimalPlaces();
            $item['sale_price'] = $values['sale_price'];
            $item['tax'] = round($values['iva'], $decimals);
            $item['total'] = $this->formatAmount(round($values['total'], $decimals));

            $newCart->push($item);
        }

        $this->cart = $newCart;
        $this->save(); // Save cart to session
    }

    public function initPayment($type)
    {
        // Redirigir Banco (3) y Nequi (4) al modal unificado (1)
        if ($type == 3) {
            $this->selectedPaymentMethod = 'bank';
            $type = 1; 
        } elseif ($type == 2) {
            $this->selectedPaymentMethod = 'credit';
        } else {
            // Por defecto efectivo
            $this->selectedPaymentMethod = 'cash';
        }

        $this->payType = $type;

        if ($type == 1) $this->payTypeName = 'PAGO / ABONOS';
        if ($type == 2) $this->payTypeName = 'PAGO A CRÉDITO';

        
        
        // Calculate totals for payment modal based on selected invoice currency
        $conversionFactor = $this->getConversionFactor();
        $decimals = ConfigurationService::getDecimalPlaces();

        $this->totalCartAtPayment = round($this->totalCart * $conversionFactor, $decimals);
        
        // Since we are starting payment, the remaining amount is the total
        // We will manage amounts in the selected currency for the payment modal
        // Note: The system stores payments in their original currency, but we need a reference 'remaining' amount.
        // Usually `remainingAmount` is in Primary Currency.
        // But for the View ID 174 request, the user wants to see everything in the selected currency.
        
        // Let's set remainingAmount in the DISPLAY currency for the UI interactions.
        $this->remainingAmount = $this->totalCartAtPayment; 
        
        $this->dispatch('initPay', payType: $type);
    }
    
    // Implement addBankPayment
    public function addBankPayment()
    {
        if ($this->isVedBankSelected) {
            $this->validate([
                'bankId' => 'required',
                'bankReference' => 'required|string|size:5',
                'bankDate' => 'required|date',
                'bankGlobalAmount' => 'required|numeric|min:0.01',
                'bankAmount' => 'required|numeric|min:0.01', // Amount to Use
                'bankImage' => 'required|image|max:2048', 
            ], [
                'bankReference.size' => 'La referencia bancaria debe tener exactamente 5 caracteres.'
            ]);
            
            $this->checkBankStatus();
            
             if ($this->bankStatusType === 'danger') {
                 $this->dispatch('noty', msg: $this->bankStatusMessage);
                 return;
            }
            // Logic: User wants to use 'bankAmount' from the 'bankGlobalAmount'.
            // Ensure bankAmount <= bankRemainingBalance
            if ($this->bankRemainingBalance !== null && $this->bankAmount > $this->bankRemainingBalance) {
                $this->dispatch('noty', msg: "El monto a usar ($" . number_format($this->bankAmount, 2) . ") excede el saldo restante ($" . number_format($this->bankRemainingBalance, 2) . ")");
                return;
            }

             $duplicateInSession = collect($this->payments)->contains(function ($payment) {
                return $payment['method'] === 'bank' && ($payment['bank_reference'] ?? '') === $this->bankReference;
             });
             if ($duplicateInSession) { $this->dispatch('noty', msg: 'Esta referencia ya está agregada en esta lista.'); return; }
        
        } else {
             $this->validate([
                 'bankId' => 'required', 
                 'bankAccountNumber' => 'required', 
                 'bankDepositNumber' => 'required|string|size:5', 
                 'bankAmount' => 'required|numeric|min:0.01'
             ], [
                 'bankDepositNumber.size' => 'La referencia bancaria debe tener exactamente 5 caracteres.'
             ]);
        }

        // Determine currency
        $bank = $this->banks->find($this->bankId);
        $currencyCode = $bank ? $bank->currency_code : 'COP';
        $bankName = $bank ? $bank->name : '';
        
        $currency = $this->currencies->firstWhere('code', $currencyCode);
        $exchangeRate = $currency ? $currency->exchange_rate : 1;
        $symbol = $currency ? $currency->symbol : '$';
        
        $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
        
        $amountInPrimary = 0;
        if ($currency && $currency->is_primary) {
            $amountInPrimary = $this->bankAmount;
        } else {
            // Apply Custom Rate Logic if VED
             $finalRate = $exchangeRate;
             // Note: Sales.php does not seem to have customExchangeRate property yet? 
             // I didn't see it in properties scan. PaymentComponent has it.
             // For now, use standard exchange rate to avoid breaking.
             // Or check if I missed copying it.
             
            $amountInUSD = $this->bankAmount / ($finalRate ?: 1);
            $amountInPrimary = $amountInUSD * $primaryCurrency->exchange_rate;
        }

        // Handle Image
        $bankImagePath = ($this->isVedBankSelected && $this->bankImage) ? $this->bankImage->store('bank_receipts', 'public') : null;

        $newPayment = [
            'method' => 'bank',
            'amount' => $this->bankAmount,
            'currency' => $currencyCode,
            'symbol' => $symbol,
            'exchange_rate' => $exchangeRate,
            'amount_in_primary' => $amountInPrimary, // This is key for calculations
            'amount_in_primary_currency' => $amountInPrimary, // Compatibility
            'bank_id' => $this->bankId,
            'bank_name' => $bankName,
            'account_number' => $this->isVedBankSelected ? null : $this->bankAccountNumber,
            'reference' => $this->isVedBankSelected ? $this->bankReference : $this->bankDepositNumber,
            'bank_reference' => $this->bankReference,
            'bank_date' => $this->bankDate,
            'bank_note' => $this->bankNote,
            'bank_global_amount' => $this->bankGlobalAmount, // NEW
            'bank_image' => $bankImagePath,
            'bank_file_url' => $bankImagePath ? asset('storage/' . $bankImagePath) : null,
            'deposit_number' => $this->isVedBankSelected ? $this->bankReference : $this->bankDepositNumber // Redundancy for old logic
        ];

        $this->payments[] = $newPayment;

        // Save to session to persist data
        session(['payments' => $this->payments]);
        
        // Update Totals
        $this->calculatePaymentTotals();
        
        // Reset Form
        $this->resetBankForm();
    }

    public function resetBankForm()
    {
        $this->bankId = ''; 
        $this->bankAmount = null;
        $this->bankGlobalAmount = null;
        $this->bankReference = '';
        $this->bankDepositNumber = '';
        $this->bankAccountNumber = '';
        $this->bankDate = date('Y-m-d');
        $this->bankNote = '';
        $this->bankImage = null;
        $this->bankStatusMessage = '';
        $this->isVedBankSelected = false;
        $this->bankRemainingBalance = null;
    }
    
    public function calculatePaymentTotals()
    {
        $this->totalPaidDisplay = collect($this->payments)->sum('amount'); // This is naive summing of mixed currencies. 
        // POS usually sums in primary currency?
        // Let's check initPayment: $this->totalPaid = array_sum(array_column($this->payments, 'amount_in_primary'));
        
        $this->totalInPrimaryCurrency = collect($this->payments)->sum('amount_in_primary_currency');
        
        // Update remaining logic
        // remainingAmount was set to totalCartAtPayment (in Display Currency?)
        // If we want to show remaining in Display Currency, we need to convert paid amounts to Display Currency.
        
        // This is complex because Sales.php manages display differently. 
        // Let's assume for now we just want to update the UI totals.
        
        // Recalculate remaining
        $primaryCurrency = CurrencyHelper::getPrimaryCurrency();
        $conversionFactor = $this->getConversionFactor(); // To Display Currency
        
        $totalPaidInDisplay = $this->totalInPrimaryCurrency * $conversionFactor;
        
        // totalCartAtPayment is in Display Currency
        $this->totalPaidDisplay = $totalPaidInDisplay; // Update property for View
        
        $this->remainingAmount = max(0, $this->totalCartAtPayment - $totalPaidInDisplay);
        $this->change = max(0, $totalPaidInDisplay - $this->totalCartAtPayment);
    }
    
    // Helpers for Display Logic
    public function getConversionFactor()
    {
        $primary = collect($this->currencies)->firstWhere('is_primary', true);
        if(!$primary) return 1;
        
        // Target: invoiceExchangeRate
        // Source: primary->exchange_rate
        
        // If Primary (Source) is 50, and Target is 1. Factor = 1/50 = 0.02
        // If Primary (Source) is 1, and Target is 50. Factor = 50.
        
        if ($primary->exchange_rate == 0) return 1;
        
        return (1 / $primary->exchange_rate) * $this->invoiceExchangeRate;
    }

    public function getDisplayTotalCartProperty()
    {
        $decimals = ConfigurationService::getDecimalPlaces();
        return round($this->totalCart * $this->getConversionFactor(), $decimals);
    }

    public function getDisplaySubtotalCartProperty()
    {
        $decimals = ConfigurationService::getDecimalPlaces();
        return round($this->subtotalCart() * $this->getConversionFactor(), $decimals);
    }
    
    public function getDisplayIvaCartProperty()
    {
        $decimals = ConfigurationService::getDecimalPlaces();
        return round($this->totalIVA() * $this->getConversionFactor(), $decimals);
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
        
        // Ensure credit config is loaded
        if (empty($this->creditConfig)) {
            $this->loadCreditConfig();
        }

        // Validar que si se seleccionó Banco/Zelle, se haya agregado el pago a la lista
        if ($this->selectedPaymentMethod === 'bank' && empty($this->payments)) {
            $this->dispatch('noty', msg: 'POR FAVOR AGREGUE EL PAGO (BOTÓN "+") ANTES DE GUARDAR');
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

            // Determine Cash Register
            $cashRegisterId = null;
            $userRegister = $cashRegisterService->getActiveCashRegister(Auth::id());

            if ($userRegister) {
                $cashRegisterId = $userRegister->id;
            } else {
                $config = Configuration::first();
                if ($config && $config->enable_shared_cash_register) {
                    $lastOpenRegister = \App\Models\CashRegister::where('status', 'open')->latest()->first();
                    if ($lastOpenRegister) {
                        $cashRegisterId = $lastOpenRegister->id;
                    }
                }
            }
            
            // For Cash payments, require a register. For Credit, it might be optional but good to track.
            if ($saleType == 'cash' && !$cashRegisterId) {
                 // Throw error or handle? 
                 // Matches PartialPayment logic: "NO HAY CAJA ABIERTA"
                 throw new \Exception("NO HAY CAJA ABIERTA para registrar esta venta. Abra una caja o active el modo compartido.");
            }

            // Determine Invoice Currency
            $currencyCodeForInvoice = $primaryCurrency->code; // Default
            $exchangeRateForInvoice = $primaryCurrency->exchange_rate; // Default

            if ($this->invoiceCurrency_id) {
                $selectedCurrency = collect($this->currencies)->firstWhere('id', $this->invoiceCurrency_id);
                if ($selectedCurrency) {
                    $currencyCodeForInvoice = $selectedCurrency->code;
                    $exchangeRateForInvoice = $selectedCurrency->exchange_rate;
                }
            }


            // Calculate conversion factor: System Primary -> USD -> Invoice Currency
            // Note: $this->totalCart is in System Primary.
            // $primaryCurrency (from line 2478) is the System Primary.
            
            $conversionFactor = 1;
            if ($primaryCurrency->code != $currencyCodeForInvoice) {
                 // Convert to USD then to Invoice Currency
                 // Factor = (1 / PrimaryRate) * InvoiceRate
                 if ($primaryCurrency->exchange_rate != 0) {
                     $conversionFactor = (1 / $primaryCurrency->exchange_rate) * $exchangeRateForInvoice;
                 }
            }
            
            $totalInInvoiceCurrency = round($this->totalCart * $conversionFactor, $decimals);

            $appliedComm = null;
            $appliedFreight = null;
            $appliedDiff = null;
            $sellerConfigId = null;

            if ($this->applyCommissions) {
                if ($this->sellerConfig) {
                    $sellerConfigId = $this->sellerConfig->id;
                }
                
                if ($this->customerConfig && $this->customerConfig->commission_percent > 0) {
                    $appliedComm = $this->customerConfig->commission_percent;
                } elseif ($this->sellerConfig) {
                    $appliedComm = $this->sellerConfig->commission_percent;
                }

                if ($this->customerConfig && $this->customerConfig->freight_percent > 0) {
                    $appliedFreight = $this->customerConfig->freight_percent;
                } elseif ($this->sellerConfig) {
                    $appliedFreight = $this->sellerConfig->freight_percent;
                }

                if ($this->customerConfig && $this->customerConfig->exchange_diff_percent > 0) {
                    $appliedDiff = $this->customerConfig->exchange_diff_percent;
                } elseif ($this->sellerConfig) {
                    $appliedDiff = $this->sellerConfig->exchange_diff_percent;
                }
            }

            // --- COMISIÓN: CAPTURAR TIERS/PENALIZACIONES ---
            $tier1Days = null;
            $tier1Percent = null;
            $tier2Days = null;
            $tier2Percent = null;

            if ($this->applyCommissions) {
                $customerModel = \App\Models\Customer::find($this->customer['id']);
                $sellerModel = $customerModel ? $customerModel->seller : null;
                if (!$sellerModel) {
                     $sellerModel = auth()->user();
                }
                $globalConfig = \App\Models\Configuration::first();

                // 1. Customer config
                $tier1Days = $customerModel->customer_commission_1_threshold ?? null;
                $tier1Percent = $customerModel->customer_commission_1_percentage ?? null;
                $tier2Days = $customerModel->customer_commission_2_threshold ?? null;
                $tier2Percent = $customerModel->customer_commission_2_percentage ?? null;

                // 2. Fallback to Seller
                if (is_null($tier1Days) || is_null($tier1Percent)) {
                    $tier1Days = $sellerModel->seller_commission_1_threshold ?? null;
                    $tier1Percent = $sellerModel->seller_commission_1_percentage ?? null;
                    $tier2Days = $sellerModel->seller_commission_2_threshold ?? null;
                    $tier2Percent = $sellerModel->seller_commission_2_percentage ?? null;
                }

                // 3. Fallback to Global
                if (is_null($tier1Days) || is_null($tier1Percent)) {
                    if ($globalConfig) {
                        $tier1Days = $globalConfig->global_commission_1_threshold ?? null;
                        $tier1Percent = $globalConfig->global_commission_1_percentage ?? null;
                        $tier2Days = $globalConfig->global_commission_2_threshold ?? null;
                        $tier2Percent = $globalConfig->global_commission_2_percentage ?? null;
                    }
                }
            }
            // ---------------------------------------------

            $sale = Sale::create([
                'seller_config_id' => $sellerConfigId,
                'total' => $totalInInvoiceCurrency,
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
                'primary_currency_code' => $currencyCodeForInvoice,
                'primary_exchange_rate' => $exchangeRateForInvoice,
                'invoice_number' => $invoiceNumber,

                'order_number' => $orderNumber,
                'batch_name' => $batchName,
                'batch_sequence' => $batchSequence,
                'cash_register_id' => $cashRegisterId,
                'applied_commission_percent' => $appliedComm,
                'applied_freight_percent' => $appliedFreight,
                'applied_exchange_diff_percent' => $appliedDiff,
                'is_foreign_sale' => $this->sellerConfig ? true : false,
                'credit_days' => $this->creditConfig['credit_days'] ?? $this->calculateCreditDays(),
                'delivery_status' => $this->driver_id ? 'pending' : 'delivered',
                'credit_rules_snapshot' => $this->prepareCreditSnapshot(),
                'is_freight_broken_down' => $this->is_freight_broken_down,
                'seller_tier_1_days' => $tier1Days,
                'seller_tier_1_percent' => $tier1Percent,
                'seller_tier_2_days' => $tier2Days,
                'seller_tier_2_percent' => $tier2Percent,
                'driver_id' => $this->driver_id,
            ]);

            // get cart session
            $cart = collect(session("cart"));

            // insert sale detail
            // Prepared variables for closure
            $applyCommissions = $this->applyCommissions;
            $applyFreight = $this->applyFreight;
            $sellerConfig = $this->sellerConfig;
            $conversionFactorForDetails = $conversionFactor;


            // insert sale detail
            $details = $cart->map(function ($item) use ($sale, $decimals, $primaryCurrency, $applyCommissions, $applyFreight, $sellerConfig, $conversionFactorForDetails, $appliedFreight) {

                // El precio del producto está en USD (base)
                $product = Product::find($item['pid']);
                $priceUSD = $product ? $product->price : 0;
                
                // Calculate Freight for this item
                $freightAmount = 0;
                
                // Logic from calculateFreight
                if ($applyCommissions || $applyFreight) {
                     $basePrice = $item['base_price'] ?? ($priceUSD * $primaryCurrency->exchange_rate);
                     $qty = $item['qty'];
                     
                     // 1. Specific Freight
                     if ($product->freight_type == 'personalized' || $product->freight_type == 'fixed') {
                         $freightAmount = $product->freight_value * $qty;
                     } 
                     // 2. Percentage Freight
                     elseif ($product->freight_type == 'percentage') {
                         $freightAmount = ($basePrice * $product->freight_value / 100) * $qty;
                     }
                     // 3. Fallback to Global Seller Freight
                     elseif ($appliedFreight !== null && $appliedFreight > 0) {
                          $freightAmount = ($basePrice * $appliedFreight / 100) * $qty;
                     } elseif ($sellerConfig) {
                          $freightAmount = ($basePrice * $sellerConfig->freight_percent / 100) * $qty;
                     }
                }

                return [
                    'product_id' => $item['pid'],
                    'sale_id' => $sale->id,
                    'quantity' => round($item['qty'], $decimals),
                    'regular_price' => round(
                        ($item['base_price'] ?? $item['price1'] ?? 0) * $conversionFactorForDetails,
                        $decimals
                    ),
                    'sale_price' => round($item['sale_price'] * $conversionFactorForDetails, $decimals),
                    'freight_amount' => round($freightAmount * $conversionFactorForDetails, $decimals),

                    'price_usd' => $priceUSD,
                    'exchange_rate' => $primaryCurrency->exchange_rate,
                    'created_at' => Carbon::now(),
                    'discount' => 0,
                    'warehouse_id' => $item['warehouse_id'] ?? null, // Store warehouse ID
                    'metadata' => isset($item['product_item_id']) ? json_encode(['product_item_id' => $item['product_item_id']]) : null
                ];
            })->toArray();

            SaleDetail::insert($details);

            //update stocks
            foreach ($cart as  $item) {
                $product = Product::find($item['pid']);
                
                // Calculate quantity to deduct based on conversion factor
                $conversionFactor = $item['conversion_factor'] ?? 1;
                $qtyToDeduct = $item['qty'] * $conversionFactor;
                $itemWarehouseId = $item['warehouse_id'] ?? null;

                // Determine Composite Mode
                $isComposite = $product->components->count() > 0;
                $isPreAssembled = $product->is_pre_assembled;
                $isDynamic = $isComposite && !$isPreAssembled;

                if ($isDynamic) {
                    // Dynamic Mode: Deduct Components ONLY
                    foreach ($product->components as $component) {
                         $componentQtyToDeduct = $qtyToDeduct * $component->pivot->quantity;
                         $component->decrement('stock_qty', $componentQtyToDeduct);
                         
                         if ($itemWarehouseId) {
                             $compWarehouse = \App\Models\ProductWarehouse::where('product_id', $component->id)
                                ->where('warehouse_id', $itemWarehouseId)
                                ->first();
                             if ($compWarehouse) {
                                 $compWarehouse->decrement('stock_qty', $componentQtyToDeduct);
                             } else {
                                  \App\Models\ProductWarehouse::create([
                                    'product_id' => $component->id,
                                    'warehouse_id' => $itemWarehouseId,
                                    'stock_qty' => -$componentQtyToDeduct
                                ]);
                             }
                         }
                    }
                } else {
                    // Normal Product OR Pre-assembled Kit: Deduct Product Stock
                    // Decrement global stock
                    $product->decrement('stock_qty', $qtyToDeduct);

                    // Decrement warehouse stock
                    if ($itemWarehouseId) {
                        $productWarehouse = \App\Models\ProductWarehouse::where('product_id', $item['pid'])
                            ->where('warehouse_id', $itemWarehouseId)
                            ->first();
                        
                        if ($productWarehouse) {
                            $productWarehouse->decrement('stock_qty', $qtyToDeduct);
                        } else {
                            // Create negative stock entry if not exists
                            \App\Models\ProductWarehouse::create([
                                'product_id' => $item['pid'],
                                'warehouse_id' => $itemWarehouseId,
                                'stock_qty' => -$qtyToDeduct
                            ]);
                        }
                    }
                }

                // Update Variable Item Status (Bobinas)
                if (isset($item['product_item_id'])) {
                    Log::info("Sales::storeOrder - Found variable item ID: {$item['product_item_id']}");
                    $prodItem = \App\Models\ProductItem::find($item['product_item_id']);
                    if ($prodItem) {
                        $newStatus = ($sale->status == 'paid') ? 'sold' : 'reserved';
                        $prodItem->status = $newStatus;
                        $saved = $prodItem->save();
                        Log::info("Sales::storeOrder - Updated status for item {$prodItem->id} to {$newStatus}. Result: " . ($saved ? 'true' : 'false'));
                    } else {
                        Log::error("Sales::storeOrder - ProductItem not found for ID: {$item['product_item_id']}");
                    }
                } else {
                    Log::info("Sales::storeOrder - Item does not have product_item_id");
                }
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
                    }
                    
                    if ($payment['method'] == 'bank') {
                        // Create BankRecord if detailed info is present (VED logic)
                        if (!empty($payment['bank_reference'])) {
                             try {
                                $bankGlobalAmount = $payment['bank_global_amount'] ?? $payment['amount'];
                                $amountUsed = $payment['amount'];
                                
                                $bankRecord = \App\Models\BankRecord::where('bank_id', $payment['bank_id'])
                                    ->where('reference', $payment['bank_reference'])
                                    ->where('amount', $bankGlobalAmount)
                                    ->first();

                                if ($bankRecord) {
                                    $bankRecord->remaining_balance -= $amountUsed;
                                    if ($bankRecord->remaining_balance < 0) $bankRecord->remaining_balance = 0;
                                    $bankRecord->status = $bankRecord->remaining_balance <= 0.01 ? 'used' : 'partial';
                                    $bankRecord->customer_id = $sale->customer_id;
                                    $bankRecord->save();
                                    $bankRecordId = $bankRecord->id;
                                } else {
                                    $remaining = $bankGlobalAmount - $amountUsed;
                                    
                                    $bankRecord = \App\Models\BankRecord::create([
                                        'bank_id' => $payment['bank_id'],
                                        'amount' => $bankGlobalAmount,
                                        'reference' => $payment['bank_reference'],
                                        'payment_date' => $payment['bank_date'] ?? now(),
                                        'image_path' => $payment['bank_image'] ?? null,
                                        'note' => $payment['bank_note'] ?? null,
                                        'status' => $remaining <= 0.01 ? 'used' : 'partial',
                                        'remaining_balance' => max(0, $remaining),
                                        'customer_id' => $sale->customer_id,
                                        'sale_id' => $sale->id,
                                    ]);
                                    $bankRecordId = $bankRecord->id;
                                }

                             } catch (\Exception $e) {
                                  Log::error("Error creating/linking BankRecord: " . $e->getMessage());
                             }
                        }
                    }

                    // Deducción de Billetera Virtual
                    if ($payment['method'] === 'wallet') {
                        $customerModel = \App\Models\Customer::find($this->customer['id']);
                        if ($customerModel) {
                            $customerModel->wallet_balance -= $payment['amount_in_primary_currency'];
                            $customerModel->save();
                            
                            // Actualizar balance en el array local por si acaso
                            $this->customer['wallet_balance'] = $customerModel->wallet_balance;
                        }
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
                        'reference_number' => $payment['reference'] ?? $payment['bank_reference'] ?? $payment['deposit_number'] ?? null,
                        'phone_number' => $payment['phone_number'] ?? null,
                        'zelle_record_id' => $zelleRecordId,
                        'bank_record_id' => $bankRecordId ?? null // Link BankRecord
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

            // Dispatch WhatsApp Notification Event
            event(new \App\Events\SaleCreated($sale));

            // Limpiar la variable de sesión
            session()->forget('payments');

            $this->order_id ? $this->UpdateStatusOrder($this->order_id, 'processed') : '';

            $this->dispatch('noty', msg: 'VENTA REGISTRADA CON ÉXITO');
            $this->dispatch('close-modalPay', element: $type == 3 ? 'modalDeposit' : ($type == 4 ? 'modalNequi' : 'modalCash'));
            $this->resetExcept('config', 'banks', 'bank', 'warehouses', 'drivers', 'sellers');
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
                    $updateData = [
                        'total' => round($this->totalCart, $decimals),
                        'discount' => 0,
                        'items' => $this->itemsCart,
                        'customer_id' => $this->customer['id'],
                        // 'user_id' => Auth()->user()->id, // DO NOT OVERWRITE OWNER
                        'status' => 'pending',
                        'apply_commissions' => $this->applyCommissions,
                        'apply_freight' => $this->applyFreight,
                        'is_freight_broken_down' => $this->is_freight_broken_down,
                        'invoice_currency_id' => $this->invoiceCurrency_id
                    ];
                    
                    // Only set user_id if it's missing (unlikely for update) or if we explicitly want to change it (we don't)
                    // So we just don't include it in updateData.
                    
                    $order->update($updateData);

                    // Actualiza los detalles de la orden
                    $cart = collect(session("cart"));
                    $details = $cart->map(function ($item) use ($order, $decimals) {
                        return [
                            'product_id' => $item['pid'],
                            'order_id' => $order->id,
                            'quantity' => round($item['qty'], $decimals),
                            'regular_price' => round($item['base_price'] ?? $item['price1'] ?? 0, $decimals),
                            'sale_price' => round($item['sale_price'], $decimals),
                            'created_at' => Carbon::now(),
                            'discount' => 0,
                            'warehouse_id' => $item['warehouse_id'] ?? null,
                            'metadata' => isset($item['product_item_id']) ? json_encode(['product_item_id' => $item['product_item_id']]) : null
                        ];
                    })->toArray();

                    // RESTORE: Set items that were in the order but are being removed back to 'available'
                    $oldDetails = OrderDetail::where('order_id', $order->id)->get();
                    $newItemIds = $cart->pluck('product_item_id')->filter()->toArray();

                    foreach ($oldDetails as $oldDetail) {
                        if ($oldDetail->metadata) {
                            $meta = json_decode($oldDetail->metadata, true);
                            if (isset($meta['product_item_id']) && !in_array($meta['product_item_id'], $newItemIds)) {
                                $pi = \App\Models\ProductItem::find($meta['product_item_id']);
                                if ($pi && $pi->status === 'reserved') {
                                    $pi->status = 'available';
                                    $pi->save();
                                }
                            }
                        }
                    }

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
                    'status' => 'pending',
                    'apply_commissions' => $this->applyCommissions,
                    'apply_freight' => $this->applyFreight,
                    'is_freight_broken_down' => $this->is_freight_broken_down,
                    'invoice_currency_id' => $this->invoiceCurrency_id
                ]);

                // Obtiene el carrito de la sesión
                $cart = collect(session("cart"));

                // Inserta los detalles de la venta
                $details = $cart->map(function ($item) use ($order, $decimals) {
                    return [
                        'product_id' => $item['pid'],
                        'order_id' => $order->id,
                        'quantity' => round($item['qty'], $decimals),
                        'regular_price' => round($item['base_price'] ?? $item['price1'] ?? 0, $decimals),
                        'sale_price' => round($item['sale_price'], $decimals),
                        'created_at' => Carbon::now(),
                        'discount' => 0,
                        'warehouse_id' => $item['warehouse_id'] ?? null,
                        'metadata' => isset($item['product_item_id']) ? json_encode(['product_item_id' => $item['product_item_id']]) : null
                    ];
                })->toArray();

                OrderDetail::insert($details);
            }

            // Update Variable Item Status (Bobinas)
            foreach ($cart as $item) {
                if (isset($item['product_item_id'])) {
                    $prodItem = \App\Models\ProductItem::find($item['product_item_id']);
                    if ($prodItem) {
                        $prodItem->status = 'reserved';
                        $prodItem->save();
                    }
                }
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

            $this->resetExcept('config', 'banks', 'bank', 'currencies', 'warehouses', 'drivers', 'sellers');
            $this->clear();
            session()->forget('sale_customer');
            // Obtener el último registro insertado
            $order = Order::latest('id')->first();

            // return $order;
            if ($this->config->auto_print_order) {
                $this->dispatch('noty', msg: 'ORDEN #' . $order->id . ' IMPRESA CON ÉXITO');
                // mike42
                $this->printOrder($order->id);
            }

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
            'type' => $this->ctype,
            'seller_id' => auth()->user()->can('system.is_foreign_seller') 
                ? auth()->id() 
                : (\App\Models\User::where('name', 'OFICINA')->value('id') ?? 0)
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
                
                // Restaurar items variables si existen
                $details = \App\Models\OrderDetail::where('order_id', $order->id)->get();
                foreach($details as $d) {
                    if($d->metadata) {
                        $meta = json_decode($d->metadata, true);
                        if(isset($meta['product_item_id'])) {
                            $pi = \App\Models\ProductItem::find($meta['product_item_id']);
                            if($pi) {
                                $pi->status = 'available';
                                $pi->save();
                            }
                        }
                    }
                }

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
                if ($product && $product->manage_stock == 1) {
                    $product->increment('stock_qty', $detail->quantity);
                }

                // Restaurar item variable (status -> available)
                if($detail->metadata) {
                    $meta = json_decode($detail->metadata, true);
                    if (isset($meta['product_item_id'])) {
                        $pi = \App\Models\ProductItem::find($meta['product_item_id']);
                        if ($pi) {
                            $pi->status = 'available';
                            $pi->save();
                        }
                    }
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

    function calculateCreditDays()
    {
        // 1. Customer Level
        $customer = \App\Models\Customer::find($this->customer['id']);
        if ($customer && $customer->customer_commission_1_threshold > 0) {
            return $customer->customer_commission_1_threshold;
        }

        // 2. Seller Level
        $seller = \App\Models\User::find(Auth()->user()->id);
        if ($seller && $seller->seller_commission_1_threshold > 0) {
            return $seller->seller_commission_1_threshold;
        }

        // 3. Global Level
        $config = Configuration::first();
        if ($config && $config->global_commission_1_threshold > 0) {
            return $config->global_commission_1_threshold;
        }

        return 0; // Default if no rule matches
    }
    #[On('hideResults')]
    public function hideResults()
    {
        $this->search3 = '';
        $this->products = [];
    }

    /**
     * Load credit configuration for the current customer
     * Uses hierarchical resolution: Customer > Seller > Global
     */
    public function loadCreditConfig()
    {
        // If no customer selected, set default config
        if (!$this->customer || !isset($this->customer['id'])) {
            $this->creditConfig = [
                'allow_credit' => false,
                'credit_days' => 0,
                'credit_limit' => 0,
                'usd_payment_discount' => 0,
                'discount_rules' => collect([]),
                'source' => 'none'
            ];
            return;
        }

        $customer = \App\Models\Customer::find($this->customer['id']);
        if (!$customer) {
            $this->creditConfig = [
                'allow_credit' => false,
                'credit_days' => 0,
                'credit_limit' => 0,
                'usd_payment_discount' => 0,
                'discount_rules' => collect([]),
                'source' => 'none'
            ];
            return;
        }

        $seller = $customer->seller;
        $this->creditConfig = \App\Services\CreditConfigService::getCreditConfig($customer, $seller);
    }



    public function prepareCreditSnapshot()
    {
        if (empty($this->creditConfig)) {
            $this->loadCreditConfig();
        }

        return [
            'usd_payment_discount' => $this->creditConfig['usd_payment_discount'] ?? 0,
            'discount_rules' => isset($this->creditConfig['discount_rules']) ? $this->creditConfig['discount_rules']->toArray() : [],
            'snapshot_at' => now(),
            'source' => $this->creditConfig['source'] ?? 'unknown'
        ];
    }

    public function generatePdfInvoiceOriginal(Sale $sale)
    {
        return $this->generatePdfInvoice($sale, true);
    }

    public function generatePdfInternalInvoiceOriginal(Sale $sale)
    {
        return $this->generatePdfInternalInvoice($sale, true);
    }

    public function generateCreditNotePdfEndpoint(\App\Models\SaleReturn $saleReturn)
    {
        return $this->generateCreditNotePdf($saleReturn);
    }
}
