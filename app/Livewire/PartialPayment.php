<?php

namespace App\Livewire;

use App\Models\Bank;
use App\Models\Currency;
use App\Models\Payment;
use App\Models\Sale;
use App\Traits\PrintTrait;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class PartialPayment extends Component
{
    use PrintTrait;

    public $sales, $banks, $pays;
    public $currencies, $paymentCurrency;
    public  $search, $sale_selected_id, $customer_name, $debt, $debt_usd;
    public $amount, $acountNumber, $depositNumber, $bank, $phoneNumber;
    public $paymentMethod = 'cash'; // cash, nequi, deposit

    function mount($key = null)
    {
        $this->banks = Bank::orderBy('sort')->get();
        $this->currencies = Currency::orderBy('is_primary', 'desc')->orderBy('id', 'asc')->get();
        $this->paymentCurrency = $this->currencies->firstWhere('is_primary', 1)->code ?? 'COP';
        $this->bank = 0;
        $this->paymentMethod = 'cash';

        $this->sales = [];
        $this->pays = [];
        $this->amount = null;
        $this->search = null;
        $this->sale_selected_id = null;
        $this->customer_name = null;
    }


    public function render()
    {

        $this->getSalesWithDetails();

        return view('livewire.payments.partial-payment');
    }

    public  function getSalesWithDetails()
    {
        $query = Sale::whereHas('customer', function ($query) {
            if (!empty(trim($this->search))) {
                $query->where('name', 'like', "%{$this->search}%");
            }
        })
            ->where('type', 'credit')
            ->where('status', 'pending')
            ->with(['customer', 'payments'])
            ->take(15)
            ->orderBy('sales.id', 'desc');

        $sales = $query->get();
        
        // Obtener moneda principal
        $primaryCurrency = Currency::where('is_primary', true)->first();
        
        // Calcular totales correctos
        $sales->map(function($sale) use ($primaryCurrency) {
            // Calcular total pagado en USD
            $totalPaidUSD = $sale->payments->sum(function($payment) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                return $payment->amount / $rate;
            });
            
            // Si la venta no tiene total_usd (ventas antiguas), calcularlo
            $totalUSD = $sale->total_usd;
            if (!$totalUSD || $totalUSD == 0) {
                $exchangeRate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $totalUSD = $sale->total / $exchangeRate;
            }
            
            $debtUSD = $totalUSD - $totalPaidUSD;
            
            // Asignar valores para la vista (convertidos a moneda principal actual)
            $sale->total_display = $totalUSD * $primaryCurrency->exchange_rate;
            $sale->total_paid_display = $totalPaidUSD * $primaryCurrency->exchange_rate;
            $sale->debt_display = $debtUSD * $primaryCurrency->exchange_rate;
            
            return $sale;
        });

        if (!empty(trim($this->search))) {
            $this->search = null;
            $this->dispatch('clear-search');
        }
        
        $this->sales = $sales;
    }



    function initPay($sale_id, $customer, $debt)
    {
        $sale = Sale::find($sale_id);
        
        if (!$sale) {
            $this->dispatch('noty', msg: 'Venta no encontrada');
            return;
        }
        
        // Calcular deuda en USD (moneda base)
        $totalPaidUSD = $sale->payments->sum(function($payment) {
            // Convertir cada pago a USD
            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
            return $payment->amount / $rate;
        });
        
        $debtUSD = $sale->total_usd - $totalPaidUSD;
        
        // Convertir deuda a moneda principal para mostrar al usuario
        $primaryCurrency = Currency::where('is_primary', true)->first();
        $debtInPrimary = $debtUSD * $primaryCurrency->exchange_rate;
        
        $this->sale_selected_id = $sale_id;
        $this->customer_name = $customer;
        $this->debt = round($debtInPrimary, 2);
        $this->debt_usd = round($debtUSD, 2); // Guardar también en USD para validaciones
        $this->dispatch('focus-partialPayInput');
    }

    function doPayment()
    {
        $this->resetValidation();

        if ($this->paymentMethod == 'deposit') {
            if ($this->bank == 0) {
                $this->addError('bank', 'SELECCIONA EL BANCO');
            }
            if (empty($this->acountNumber)) {
                $this->addError('nacount', 'INGRESA EL NÚMERO DE CUENTA');
            }
            if (empty($this->depositNumber)) {
                $this->addError('ndeposit',  'INGRESA EL NÚMERO DE DEPÓSITO');
            }
        }
        
        if ($this->paymentMethod == 'nequi') {
             if (empty($this->phoneNumber)) {
                $this->addError('phoneNumber', 'INGRESA EL NÚMERO DE TELÉFONO');
            }
        }

        if (empty($this->amount) || strlen($this->amount) < 1) {
            $this->addError('amount', 'INGRESA EL MONTO');
        }
        if (floatval($this->amount) <= 0) {
            $this->addError('amount', 'MONTO DEBE SER MAYOR A CERO');
        }

        if (count($this->getErrorBag()) > 0) {
            return;
        }

        $type = null;
        $amount = floatval($this->amount);
        if (floatval($this->amount) >= floatval($this->debt)) {
            $type = 'settled'; //liquida crédito
        } else {
            $type = 'pay'; // abono
        }

        if (floatval($this->amount) > floatval($this->debt)) {
            $amount = $this->debt;
        }


        DB::beginTransaction();

        try {
            // Determine currency and exchange rate based on payment method
            $currencyCode = 'COP';
            $exchangeRate = 1;

            if ($this->paymentMethod == 'cash') {
                $currencyCode = $this->paymentCurrency;
                $selectedCurrency = $this->currencies->firstWhere('code', $currencyCode);
                $exchangeRate = $selectedCurrency ? $selectedCurrency->exchange_rate : 1;
            } elseif ($this->paymentMethod == 'deposit') {
                $bank = $this->banks->find($this->bank);
                $currencyCode = $bank ? $bank->currency_code : 'COP';
                // Find exchange rate for bank's currency
                $selectedCurrency = $this->currencies->firstWhere('code', $currencyCode);
                $exchangeRate = $selectedCurrency ? $selectedCurrency->exchange_rate : 1;
            } elseif ($this->paymentMethod == 'nequi') {
                $currencyCode = 'COP';
                $exchangeRate = 1;
            }

            // Calculate amount in primary currency if needed
            $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
            $amountInPrimary = $amount;
            
            // If currency is not primary, convert to primary through USD base
            $currencyObj = $this->currencies->firstWhere('code', $currencyCode);
            if ($currencyObj && $currencyObj->is_primary != 1) {
                // Convert to USD first, then to primary currency
                $amountInUSD = $amount / $exchangeRate;
                $amountInPrimary = $amountInUSD * $primaryCurrency->exchange_rate;
            } else {
                // If it's the primary currency, convert to USD
                $amountInUSD = $amount / $primaryCurrency->exchange_rate;
            }

            // Re-validate against debt in USD (source of truth)
             if ($amountInUSD > $this->debt_usd) {
                $amountInUSD = $this->debt_usd;
                // Recalculate amounts based on capped USD amount
                $amountInPrimary = $amountInUSD * $primaryCurrency->exchange_rate;
                $amount = $amountInUSD * $exchangeRate;
            }
            
            if ($amountInUSD >= $this->debt_usd) {
                $type = 'settled';
            }

            //crear pago
            $pay =  Payment::create(
                [
                    'user_id' => Auth()->user()->id,
                    'sale_id' => $this->sale_selected_id,
                    'amount' => floatval($amount),
                    'currency' => $currencyCode,
                    'exchange_rate' => $exchangeRate,
                    'primary_exchange_rate' => $primaryCurrency->exchange_rate,
                    'pay_way' => $this->paymentMethod,
                    'type' => $type,
                    'bank' => ($this->paymentMethod == 'deposit' && $this->bank != 0 ? $this->banks->where('id', $this->bank)->first()->name : ''),
                    'account_number' => $this->acountNumber,
                    'deposit_number' => $this->depositNumber,
                    'phone_number' => $this->phoneNumber
                ]
            );

            // actualizar status venta
            if ($type == 'settled') {
                Sale::where('id', $this->sale_selected_id)->update([
                    'status' => 'paid'
                ]);
            }

            DB::commit();

            // Calculate Commission if sale is settled
            if ($type == 'settled') {
                $sale = Sale::find($this->sale_selected_id);
                if ($sale && $sale->applied_commission_percent > 0) {
                    \App\Services\CommissionService::calculateCommission($sale);
                }
            }

            $this->printPayment($pay->id);
            $this->dispatch('noty', msg: 'PAGO REGISTRADO CON ÉXITO');
            $this->dispatch('close-modal');
            
            // Limpiar datos de la venta seleccionada
            $this->sale_selected_id = null;
            $this->customer_name = null;
            $this->debt = null;
            $this->debt_usd = null;
            $this->amount = null;
            $this->acountNumber = null;
            $this->depositNumber = null;
            $this->phoneNumber = null;
            $this->bank = 0;
            $this->paymentMethod = 'cash';
            
            $this->resetExcept('banks', 'pays', 'currencies');

            //
        } catch (\Exception $th) {
            DB::rollBack();

            $this->dispatch('noty', msg: "Error al intentar registrar el pago parcial: {$th->getMessage()}");
        }
    }

    function cancelPay()
    {
        $this->sale_selected_id = null;
        $this->customer_name = null;
        $this->debt = null;
        $this->debt_usd = null;
        $this->amount = null;
        $this->acountNumber = null;
        $this->depositNumber = null;
        $this->phoneNumber = null;
        $this->bank = 0;
        $this->paymentMethod = 'cash';
        $this->dispatch('close-modal');
    }

    function historyPayments(Sale $sale)
    {
        $this->pays = $sale->payments;
        $this->dispatch('show-payhistory');
    }

    function printReceipt($payment_id)
    {
        $this->printPayment($payment_id);
        $this->dispatch('noty', msg: 'IMPRIMIENDO RECIBO DE PAGO...');
    }

    function printHistory()
    {
        if (empty($this->pays) || count($this->pays) == 0) {
            $this->dispatch('noty', msg: 'NO HAY PAGOS PARA IMPRIMIR');
            return;
        }

        $saleId = $this->pays[0]->sale_id;
        $this->printPaymentHistory($saleId);
        $this->dispatch('noty', msg: 'IMPRIMIENDO HISTORIAL DE PAGOS...');
    }
}
