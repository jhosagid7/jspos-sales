<?php

namespace App\Livewire;

use App\Models\Payment;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use App\Models\Currency;
use App\Models\SaleChangeDetail;
use App\Models\SalePaymentDetail;
use App\Traits\PrintTrait;
use Livewire\Component;
use Livewire\Attributes\On;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashCount extends Component
{
    use PrintTrait;

    public $users = [], $user, $user_id = 0, $totales = 0, $dateFrom, $dateTo;
    public $totalDeposit = 0, $totalNequi = 0, $totalCash = 0, $totalSales = 0, $totalCreditSales = 0, $totalPayments = 0, $totalPaymentsDeposit = 0, $totalPaymentsCash = 0, $totalPaymentsNequi = 0;
    
    // New properties for currency breakdown
    public $salesByCurrency = [];
    public $paymentsByCurrency = [];
    public $currencies;


    function mount()
    {
        session(['map' => "", 'child' => '', 'pos' => 'Arqueo de Caja']);

        $this->users = User::orderBy('name')->get();
        $this->currencies = Currency::orderBy('is_primary', 'desc')->get();
    }


    public function render()
    {
        $this->user = session('cashcount_user', 0);

        return view('livewire.cash-count');
    }


    function updatedUserId()
    {
        session(['cashcount_user' => User::find($this->user_id)]);
        $this->user = session('cashcount_user');
    }

    function getSalesBetweenDates()
    {
        if ($this->user_id == null && $this->dateFrom == null && $this->dateTo == null) {
            $this->dispatch('noty', msg: 'SELECCIONA EL USUARIO Y/O LAS FECHAS DE CONSULTA');
            return;
        }

        if (($this->dateFrom != null && $this->dateTo == null) || ($this->dateFrom == null && $this->dateTo != null)) {
            $this->dispatch('noty', msg: 'SELECCIONA LA FECHA DESDE Y HASTA');
            return;
        }


        sleep(1);

        $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dTo = Carbon::parse($this->dateTo)->endOfDay();

        try {
            $sales = Sale::whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->user_id != 0, function ($qry) {
                    $qry->where('user_id', $this->user_id);
                })
                ->select('id', 'total', 'cash', 'change', 'type', 'primary_exchange_rate')
                ->get();

            // Get primary currency
            $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
            $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;

            // Convert all totals to primary currency
            $this->totalSales = $sales->sum(function($sale) use ($primaryRate) {
                $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                // Convert to USD first, then to primary currency
                $totalUSD = $sale->total / $saleRate;
                return $totalUSD * $primaryRate;
            });

            // --- NEW CALCULATION LOGIC USING SalePaymentDetail ---
            
            // 1. Get all payment details for these sales
            $saleIds = $sales->pluck('id');
            $paymentDetails = SalePaymentDetail::whereIn('sale_id', $saleIds)->get();
            
            // 2. Calculate totals from details (New Sales)
            $totalCashDetails = $paymentDetails->where('payment_method', 'cash')->sum('amount_in_primary_currency');
            $totalDepositDetails = $paymentDetails->where('payment_method', 'bank')->sum('amount_in_primary_currency');
            
            // 3. Calculate totals from legacy sales (Sales without details)
            $salesWithDetailsIds = $paymentDetails->pluck('sale_id')->unique();
            $legacySales = $sales->whereNotIn('id', $salesWithDetailsIds);
            
            $totalCashLegacy = $legacySales->sum(function($sale) use ($primaryRate) {
                // Only count if type implies cash
                if (in_array($sale->type, ['cash', 'cash/nequi', 'mixed'])) {
                    $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                    $netCash = $sale->cash - $sale->change;
                    $netCashUSD = $netCash / $saleRate;
                    return $netCashUSD * $primaryRate;
                }
                return 0;
            });

            $totalDepositLegacy = $legacySales->where('type', 'deposit')->sum(function($sale) use ($primaryRate) {
                $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                $totalUSD = $sale->total / $saleRate;
                return $totalUSD * $primaryRate;
            });

            // 4. Sum both
            $this->totalCash = $totalCashDetails + $totalCashLegacy;
            $this->totalDeposit = $totalDepositDetails + $totalDepositLegacy;
            $this->totalNequi = 0; // Nequi removed
            
            $this->totalCreditSales = $sales->where('type', 'credit')->sum(function($sale) use ($primaryRate) {
                $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                $totalUSD = $sale->total / $saleRate;
                return $totalUSD * $primaryRate;
            });

            $payments = Payment::whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->user_id != 0, function ($qry) {
                    $qry->where('user_id', $this->user_id);
                })
                ->select('id', 'pay_way', 'amount', 'type', 'bank', 'currency', 'exchange_rate', 'primary_exchange_rate')
                ->get();
            // dd($payments);

            // Convert payment totals to primary currency
            $this->totalPaymentsCash = $payments->where('pay_way', 'cash')->sum(function($payment) use ($primaryRate) {
                $paymentRate = $payment->exchange_rate ?? 1;
                $paymentPrimaryRate = $payment->primary_exchange_rate ?? $primaryRate;
                $amountUSD = $payment->amount / $paymentRate;
                return $amountUSD * $paymentPrimaryRate;
            });

            $this->totalPaymentsDeposit = $payments->where('pay_way', 'deposit')->sum(function($payment) use ($primaryRate) {
                $paymentRate = $payment->exchange_rate ?? 1;
                $paymentPrimaryRate = $payment->primary_exchange_rate ?? $primaryRate;
                $amountUSD = $payment->amount / $paymentRate;
                return $amountUSD * $paymentPrimaryRate;
            });

            $this->totalPaymentsNequi = $payments->where('pay_way', 'nequi')->sum(function($payment) use ($primaryRate) {
                $paymentRate = $payment->exchange_rate ?? 1;
                $paymentPrimaryRate = $payment->primary_exchange_rate ?? $primaryRate;
                $amountUSD = $payment->amount / $paymentRate;
                return $amountUSD * $paymentPrimaryRate;
            });

            $this->totalPayments = $payments->sum(function($payment) use ($primaryRate) {
                $paymentRate = $payment->exchange_rate ?? 1;
                $paymentPrimaryRate = $payment->primary_exchange_rate ?? $primaryRate;
                $amountUSD = $payment->amount / $paymentRate;
                return $amountUSD * $paymentPrimaryRate;
            });

            // Aggregate by currency
            $this->salesByCurrency = $this->aggregateSalesByCurrency($sales);
            $this->paymentsByCurrency = $this->aggregatePaymentsByCurrency($payments);

            $this->dispatch('noty', msg: 'Info actualizada');
            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al obtener la información de las ventas por fecha: {$th->getMessage()} ");
        }
    }

    function getDailySales()
    {
        sleep(1);

        $dFrom = Carbon::today()->startOfDay();
        $dTo = Carbon::today()->endOfDay();
        $this->dateFrom = $dFrom;
        $this->dateTo = $dTo;

        try {
            $sales = Sale::whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->user_id != 0, function ($qry) {
                    $qry->where('user_id', $this->user_id);
                })
                ->where('status', '<>', 'returned')
                ->select('id', 'total', 'cash', 'change', 'type', 'primary_exchange_rate')
                ->get();

            // Get primary currency
            $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
            $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;

            // Convert all totals to primary currency
            $this->totalSales = $sales->sum(function($sale) use ($primaryRate) {
                $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                // Convert to USD first, then to primary currency
                $totalUSD = $sale->total / $saleRate;
                return $totalUSD * $primaryRate;
            });

            // --- NEW CALCULATION LOGIC USING SalePaymentDetail ---
            
            // 1. Get all payment details for these sales
            $saleIds = $sales->pluck('id');
            $paymentDetails = SalePaymentDetail::whereIn('sale_id', $saleIds)->get();
            
            // 2. Calculate totals from details (New Sales)
            $totalCashDetails = $paymentDetails->where('payment_method', 'cash')->sum('amount_in_primary_currency');
            $totalDepositDetails = $paymentDetails->where('payment_method', 'bank')->sum('amount_in_primary_currency');
            
            // 3. Calculate totals from legacy sales (Sales without details)
            $salesWithDetailsIds = $paymentDetails->pluck('sale_id')->unique();
            $legacySales = $sales->whereNotIn('id', $salesWithDetailsIds);
            
            $totalCashLegacy = $legacySales->sum(function($sale) use ($primaryRate) {
                // Only count if type implies cash
                if (in_array($sale->type, ['cash', 'cash/nequi', 'mixed'])) {
                    $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                    $netCash = $sale->cash - $sale->change;
                    $netCashUSD = $netCash / $saleRate;
                    return $netCashUSD * $primaryRate;
                }
                return 0;
            });

            $totalDepositLegacy = $legacySales->where('type', 'deposit')->sum(function($sale) use ($primaryRate) {
                $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                $totalUSD = $sale->total / $saleRate;
                return $totalUSD * $primaryRate;
            });

            // 4. Sum both
            $this->totalCash = $totalCashDetails + $totalCashLegacy;
            $this->totalDeposit = $totalDepositDetails + $totalDepositLegacy;
            $this->totalNequi = 0; // Nequi removed
            
            $this->totalCreditSales = $sales->where('type', 'credit')->sum(function($sale) use ($primaryRate) {
                $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                $totalUSD = $sale->total / $saleRate;
                return $totalUSD * $primaryRate;
            });

            $payments = Payment::whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->user_id != 0, function ($qry) {
                    $qry->where('user_id', $this->user_id);
                })
                ->select('id', 'pay_way', 'amount', 'bank', 'currency', 'exchange_rate', 'primary_exchange_rate')
                ->get();

            // Convert payment totals to primary currency
            $this->totalPaymentsCash = $payments->where('pay_way', 'cash')->sum(function($payment) use ($primaryRate) {
                $paymentRate = $payment->exchange_rate ?? 1;
                $paymentPrimaryRate = $payment->primary_exchange_rate ?? $primaryRate;
                $amountUSD = $payment->amount / $paymentRate;
                return $amountUSD * $paymentPrimaryRate;
            });

            $this->totalPaymentsDeposit = $payments->where('pay_way', 'deposit')->sum(function($payment) use ($primaryRate) {
                $paymentRate = $payment->exchange_rate ?? 1;
                $paymentPrimaryRate = $payment->primary_exchange_rate ?? $primaryRate;
                $amountUSD = $payment->amount / $paymentRate;
                return $amountUSD * $paymentPrimaryRate;
            });

            $this->totalPaymentsNequi = $payments->where('pay_way', 'nequi')->sum(function($payment) use ($primaryRate) {
                $paymentRate = $payment->exchange_rate ?? 1;
                $paymentPrimaryRate = $payment->primary_exchange_rate ?? $primaryRate;
                $amountUSD = $payment->amount / $paymentRate;
                return $amountUSD * $paymentPrimaryRate;
            });

            $this->totalPayments = $payments->sum(function($payment) use ($primaryRate) {
                $paymentRate = $payment->exchange_rate ?? 1;
                $paymentPrimaryRate = $payment->primary_exchange_rate ?? $primaryRate;
                $amountUSD = $payment->amount / $paymentRate;
                return $amountUSD * $paymentPrimaryRate;
            });

            // Aggregate by currency
            $this->salesByCurrency = $this->aggregateSalesByCurrency($sales);
            $this->paymentsByCurrency = $this->aggregatePaymentsByCurrency($payments);

            $this->dispatch('noty', msg: 'Info actualizada');
            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al obtener la información de las ventas del día:  {$th->getMessage()} ");
        }
    }

    function printCC()
    {
        $username = $this->user_id == 0 ? 'Todos los usuarios' : User::find($this->user_id)->name;
        $this->printCashCount(
            $username, 
            $this->dateFrom, 
            $this->dateTo, 
            $this->totales, // This seems to be unused or incorrect in original call, but keeping for now if signature matches
            $this->totalSales, // Corrected mapping based on original call
            $this->totalCash, 
            $this->totalNequi, 
            $this->totalDeposit, 
            $this->totalPayments, 
            $this->totalCreditSales, 
            $this->totalPaymentsCash, 
            $this->totalPaymentsDeposit, 
            $this->totalPaymentsNequi,
            $this->salesByCurrency,
            $this->paymentsByCurrency
        );

        $this->dispatch('noty', msg: 'Impresión de corte enviada');
    }

    /**
     * Aggregate sales by payment method and currency
     */
    /**
     * Aggregate sales by payment method and currency
     */
    private function aggregateSalesByCurrency($sales)
    {
        $aggregated = [
            'cash' => [],
            'nequi' => [],
            'deposit' => [],
        ];

        // Get primary currency for default
        $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'COP';

        // Get all sale IDs
        $saleIds = $sales->pluck('id')->toArray();

        // Query payment details for these sales
        $paymentDetails = SalePaymentDetail::whereIn('sale_id', $saleIds)->get();

        // Group payment details by sale to determine payment method
        $paymentsBySale = $paymentDetails->groupBy('sale_id');

        foreach ($sales as $sale) {
            $paymentType = $sale->type;
            
            // Check if we have payment details for this sale
            if (isset($paymentsBySale[$sale->id])) {
                // Use actual payment details - categorize by payment_method
                foreach ($paymentsBySale[$sale->id] as $paymentDetail) {
                    $currency = $paymentDetail->currency_code;
                    $bankName = $paymentDetail->bank_name;
                    $paymentMethod = $paymentDetail->payment_method ?? 'cash'; // Default to cash if not set
                    
                    // Determine category based on payment_method
                    $category = match($paymentMethod) {
                        'cash' => 'cash',
                        'nequi' => 'nequi',
                        'bank' => 'deposit',
                        default => 'cash'
                    };
                    
                    if ($category == 'deposit' && $bankName) {
                        // Group by Bank Name -> Currency
                        if (!isset($aggregated['deposit'][$bankName])) {
                            $aggregated['deposit'][$bankName] = [];
                        }
                        if (!isset($aggregated['deposit'][$bankName][$currency])) {
                            $aggregated['deposit'][$bankName][$currency] = 0;
                        }
                        $aggregated['deposit'][$bankName][$currency] += $paymentDetail->amount;
                    } else {
                        // Standard grouping by Currency
                        if (!isset($aggregated[$category][$currency])) {
                            $aggregated[$category][$currency] = 0;
                        }
                        $aggregated[$category][$currency] += $paymentDetail->amount;
                    }
                }
            } else {
                Log::warning("No payment details for sale ID: {$sale->id}");
                // Fallback: use cash - change in primary currency (for old sales without payment details)
                // Map payment types to our categories (for legacy sales)
                $category = match($paymentType) {
                    'cash', 'cash/nequi', 'mixed' => 'cash',
                    'nequi' => 'nequi',
                    'deposit', 'bank' => 'deposit',
                    default => null
                };

                if ($category === null || $paymentType === 'credit') {
                    continue; // Skip credit sales
                }
                
                $netAmount = $sale->cash - $sale->change;
                
                if ($netAmount > 0) {
                    if (!isset($aggregated[$category][$primaryCode])) {
                        $aggregated[$category][$primaryCode] = 0;
                    }
                    
                    $aggregated[$category][$primaryCode] += $netAmount;
                }
            }
        }

        return $aggregated;
    }

    /**
     * Aggregate credit payments by payment method and currency
     */
    private function aggregatePaymentsByCurrency($payments)
    {
        $aggregated = [
            'cash' => [],
            'nequi' => [],
            'deposit' => [],
        ];

        // Get primary currency for default
        $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'COP';

        foreach ($payments as $payment) {
            $payWay = $payment->pay_way;
            
            // Determine currency - use payment currency if available, otherwise primary
            $currency = $payment->currency ?? $primaryCode;

            if ($payWay == 'deposit' && !empty($payment->bank)) {
                // Group by Bank Name -> Currency
                $bankName = $payment->bank;
                
                if (!isset($aggregated['deposit'][$bankName])) {
                    $aggregated['deposit'][$bankName] = [];
                }
                if (!isset($aggregated['deposit'][$bankName][$currency])) {
                    $aggregated['deposit'][$bankName][$currency] = 0;
                }
                $aggregated['deposit'][$bankName][$currency] += $payment->amount;
            } else {
                // Standard grouping
                if (!isset($aggregated[$payWay][$currency])) {
                    $aggregated[$payWay][$currency] = 0;
                }
                $aggregated[$payWay][$currency] += $payment->amount;
            }
        }

        return $aggregated;
    }
}

