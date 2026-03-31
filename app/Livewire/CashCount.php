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
    public $totalDeposit = 0, $totalNequi = 0, $totalCash = 0, $totalSales = 0, $totalCreditSales = 0, $totalPayments = 0, $totalPaymentsDeposit = 0, $totalPaymentsCash = 0, $totalPaymentsNequi = 0, $totalPaymentsWallet = 0;
    public $totalWalletAddedToday = 0, $totalWalletUsedToday = 0, $grandTotalIncomeUSD = 0;

    
    // New properties for currency breakdown
    public $salesByCurrency = [];
    public $paymentsByCurrency = [];
    public $currencies;
    public $totalCashDetails = [];
    public $totalBankDetails = [];
    public $totalZelleDetails = [];
    public $showPdfModal = false;
    public $pdfUrl = '';


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

        $this->processCalculations($dFrom, $dTo);
    }

    function getDailySales()
    {
        sleep(1);
        $dFrom = Carbon::today()->startOfDay();
        $dTo = Carbon::today()->endOfDay();
        $this->dateFrom = $dFrom->format('Y/m/d');
        $this->dateTo = $dTo->format('Y/m/d');

        $this->processCalculations($dFrom, $dTo);
    }

    private function processCalculations($dFrom, $dTo)
    {
        try {
            $sales = Sale::whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->user_id != 0, function ($qry) {
                    $qry->where('user_id', $this->user_id);
                })
                ->where('status', '<>', 'returned')
                ->select('id', 'total', 'cash', 'change', 'type', 'primary_exchange_rate', 'total_usd')
                ->get();

            // Get primary currency
            $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
            $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;

            // 1. Convert all net totals (sale - returns) to primary currency
            $this->totalSales = $sales->sum(function($sale) use ($primaryRate) {
                $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                $returnsUSD = \App\Models\SaleReturn::where('sale_id', $sale->id)
                    ->where('status', 'approved')
                    ->sum('total_returned') / $saleRate;

                $netUSD = ($sale->total / $saleRate) - $returnsUSD;
                return $netUSD * $primaryRate;
            });

            // 2. Process Payments details for these sales
            $saleIds = $sales->pluck('id');
            $paymentDetails = SalePaymentDetail::whereIn('sale_id', $saleIds)->get();
            
            $this->totalWalletUsedToday = $paymentDetails->where('payment_method', 'wallet')->sum('amount_in_primary_currency');
            
            $totalCashDetails = $paymentDetails->where('payment_method', 'cash')->sum('amount_in_primary_currency');
            $totalDepositDetails = $paymentDetails->where('payment_method', 'bank')->sum('amount_in_primary_currency');
            
            // 3. Process Legacy sales
            $salesWithDetailsIds = $paymentDetails->pluck('sale_id')->unique();
            $legacySales = $sales->whereNotIn('id', $salesWithDetailsIds);
            
            $totalCashLegacy = $legacySales->sum(function($sale) use ($primaryRate) {
                if (in_array($sale->type, ['cash', 'cash/nequi', 'mixed'])) {
                    $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                    return (($sale->cash - $sale->change) / $saleRate) * $primaryRate;
                }
                return 0;
            });

            $totalDepositLegacy = $legacySales->where('type', 'deposit')->sum(function($sale) use ($primaryRate) {
                $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                return ($sale->total / $saleRate) * $primaryRate;
            });

            $this->totalCash = $totalCashDetails + $totalCashLegacy;
            $this->totalDeposit = $totalDepositDetails + $totalDepositLegacy;
            $this->totalNequi = 0; 
            
            $this->totalCreditSales = $sales->where('type', 'credit')->sum(function($sale) use ($primaryRate) {
                $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
                return ($sale->total / $saleRate) * $primaryRate;
            });

            // 4. Process Standalone Payments (Credit payments)
            $payments = \App\Models\Payment::with(['zelleRecord', 'bankRecord'])->whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->user_id != 0, function ($qry) {
                    $qry->where('user_id', $this->user_id);
                })
                ->where('status', 'approved')
                ->select('id', 'pay_way', 'amount', 'bank', 'currency', 'exchange_rate', 'primary_exchange_rate', 'zelle_record_id', 'bank_record_id')
                ->get();

            $this->totalPaymentsCash = $payments->where('pay_way', 'cash')->sum(function($p) use ($primaryRate) {
                return ($p->amount / ($p->exchange_rate ?: 1)) * ($p->primary_exchange_rate ?: $primaryRate);
            });

            $this->totalPaymentsDeposit = $payments->where('pay_way', 'deposit')->sum(function($p) use ($primaryRate) {
                return ($p->amount / ($p->exchange_rate ?: 1)) * ($p->primary_exchange_rate ?: $primaryRate);
            });

            $this->totalPaymentsWallet = $payments->where('pay_way', 'wallet')->sum(function($p) use ($primaryRate) {
                return ($p->amount / ($p->exchange_rate ?: 1)) * ($p->primary_exchange_rate ?: $primaryRate);
            });

            $this->totalPayments = $payments->sum(function($p) use ($primaryRate) {
                return ($p->amount / ($p->exchange_rate ?: 1)) * ($p->primary_exchange_rate ?: $primaryRate);
            });

            // 5. Calculate Wallet Generated (Custodia)
            $this->totalWalletAddedToday = \App\Models\SaleReturn::whereBetween('created_at', [$dFrom, $dTo])
                ->where('refund_method', 'wallet')
                ->where('status', 'approved')
                ->get()
                ->sum(function($r) use ($primaryRate) {
                    $sale = $r->sale;
                    $rate = ($sale && $sale->primary_exchange_rate > 0) ? $sale->primary_exchange_rate : $primaryRate;
                    return ($r->total_returned / $rate) * $primaryRate;
                });

            // 6. Aggregate by currency for UI
            $this->salesByCurrency = $this->aggregateSalesByCurrency($sales);
            $this->paymentsByCurrency = $this->aggregatePaymentsByCurrency($payments);

            // 7. Segregation: Sum Custody Today to have it in the breakdown 
            // We NO LONGER subtract it from the cash list here because it was already subtracted
            // in aggregateSalesByCurrency to get the NET revenue.
            if ($this->totalWalletAddedToday > 0.0001) {
                $this->salesByCurrency['cash']['_CUSTODIA_'] = $this->totalWalletAddedToday;
            }

            $this->grandTotalIncomeUSD = $this->totalSales + $this->totalPayments - $this->totalCreditSales - $this->totalWalletUsedToday + $this->totalWalletAddedToday;

            $this->calculateTotalBreakdowns();

            $this->dispatch('noty', msg: 'Info actualizada');

        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al procesar el arqueo: {$th->getMessage()} ");
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
            $this->paymentsByCurrency,
            $this->totalWalletAddedToday,
            $this->totalWalletUsedToday,
            $this->grandTotalIncomeUSD
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
            'zelle' => [],
        ];

        // Get primary currency for default
        $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'COP';

        // Get all sale IDs
        $saleIds = $sales->pluck('id')->toArray();

        // Query payment details for these sales
        $paymentDetails = SalePaymentDetail::with(['zelleRecord', 'bankRecord'])->whereIn('sale_id', $saleIds)->get();

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
                        'cash'   => 'cash',
                        'nequi'  => 'nequi',
                        'bank'   => 'deposit',
                        'zelle'  => 'zelle',
                        'wallet' => 'wallet', // Skip or handle separately
                        default => 'cash'
                    };

                    if ($category == 'wallet') continue; // Wallet payments are virtual
                    
                    if ($category == 'deposit' && $bankName) {
                        // Group by Bank Name -> Currency
                        if (!isset($aggregated['deposit'][$bankName])) {
                            $aggregated['deposit'][$bankName] = [];
                        }
                        if (!isset($aggregated['deposit'][$bankName][$currency])) {
                            $aggregated['deposit'][$bankName][$currency] = 0;
                        }
                        $aggregated['deposit'][$bankName][$currency] += $paymentDetail->amount;
                    } elseif ($category == 'zelle') {
                         $sender = 'Desconocido (ID: ' . ($paymentDetail->zelle_record_id ?? 'N/A') . ')';
                         if ($paymentDetail->zelleRecord) {
                             $sender = $paymentDetail->zelleRecord->sender_name . ' (Ref: ' . $paymentDetail->zelleRecord->reference . ')';
                         }
                         if (!isset($aggregated['zelle'][$sender])) {
                             $aggregated['zelle'][$sender] = 0;
                         }
                         $aggregated['zelle'][$sender] += $paymentDetail->amount;
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

            // Subtract ALL returns from the 'cash' category of this sale to get NET physical flow
            // This ensures the breakdown cards in UI match the net final total
            $saleReturns = \App\Models\SaleReturn::where('sale_id', $sale->id)->where('status', 'approved')->get();
            foreach ($saleReturns as $return) {
                $currCode = $sale->primary_currency_code ?? $primaryCode;
                $aggregated['cash'][$currCode] = ($aggregated['cash'][$currCode] ?? 0) - $return->total_returned;
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
            'zelle' => [],
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
            } elseif ($payWay == 'zelle') {
                  $sender = 'Desconocido (ID: ' . ($payment->zelle_record_id ?? 'N/A') . ')';
                  if ($payment->zelleRecord) {
                      $sender = $payment->zelleRecord->sender_name . ' (Ref: ' . $payment->zelleRecord->reference . ')';
                  }
                 if (!isset($aggregated['zelle'][$sender])) {
                     $aggregated['zelle'][$sender] = 0;
                 }
                 $aggregated['zelle'][$sender] += $payment->amount;
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

    private function calculateTotalBreakdowns()
    {
        // 1. Total Cash Breakdown
        $this->totalCashDetails = [];
        
        // Add Sales Cash
        if (isset($this->salesByCurrency['cash'])) {
            foreach ($this->salesByCurrency['cash'] as $currency => $amount) {
                if (!isset($this->totalCashDetails[$currency])) {
                    $this->totalCashDetails[$currency] = 0;
                }
                $this->totalCashDetails[$currency] += $amount;
            }
        }

        // Add Payments Cash
        if (isset($this->paymentsByCurrency['cash'])) {
            foreach ($this->paymentsByCurrency['cash'] as $currency => $amount) {
                if (!isset($this->totalCashDetails[$currency])) {
                    $this->totalCashDetails[$currency] = 0;
                }
                $this->totalCashDetails[$currency] += $amount;
            }
        }

        // 2. Total Bank Breakdown
        $this->totalBankDetails = [];

        // Add Sales Bank (Deposit)
        if (isset($this->salesByCurrency['deposit'])) {
            foreach ($this->salesByCurrency['deposit'] as $key => $value) {
                if (is_array($value)) {
                    // Structure: BankName -> Currency -> Amount
                    $bankName = $key;
                    foreach ($value as $currency => $amount) {
                        if (!isset($this->totalBankDetails[$bankName])) {
                            $this->totalBankDetails[$bankName] = [];
                        }
                        if (!isset($this->totalBankDetails[$bankName][$currency])) {
                            $this->totalBankDetails[$bankName][$currency] = 0;
                        }
                        $this->totalBankDetails[$bankName][$currency] += $amount;
                    }
                } else {
                    // Legacy Structure: Currency -> Amount (No Bank Name)
                    $currency = $key;
                    $amount = $value;
                    $bankName = 'Otros'; // Fallback for legacy data
                    
                    if (!isset($this->totalBankDetails[$bankName])) {
                        $this->totalBankDetails[$bankName] = [];
                    }
                    if (!isset($this->totalBankDetails[$bankName][$currency])) {
                        $this->totalBankDetails[$bankName][$currency] = 0;
                    }
                    $this->totalBankDetails[$bankName][$currency] += $amount;
                }
            }
        }

        // Add Payments Bank (Deposit)
        if (isset($this->paymentsByCurrency['deposit'])) {
            foreach ($this->paymentsByCurrency['deposit'] as $bankName => $currencies) {
                foreach ($currencies as $currency => $amount) {
                    if (!isset($this->totalBankDetails[$bankName])) {
                        $this->totalBankDetails[$bankName] = [];
                    }
                    if (!isset($this->totalBankDetails[$bankName][$currency])) {
                        $this->totalBankDetails[$bankName][$currency] = 0;
                    }
                    $this->totalBankDetails[$bankName][$currency] += $amount;
                }
            }
        }

        
        // 3. Total Zelle Breakdown
        $this->totalZelleDetails = [];
        if (isset($this->salesByCurrency['zelle'])) {
            foreach ($this->salesByCurrency['zelle'] as $sender => $amount) {
                if (!isset($this->totalZelleDetails[$sender])) {
                    $this->totalZelleDetails[$sender] = 0;
                }
                $this->totalZelleDetails[$sender] += $amount;
            }
        }
        if (isset($this->paymentsByCurrency['zelle'])) {
             foreach ($this->paymentsByCurrency['zelle'] as $sender => $amount) {
                if (!isset($this->totalZelleDetails[$sender])) {
                    $this->totalZelleDetails[$sender] = 0;
                }
                $this->totalZelleDetails[$sender] += $amount;
            }
        }
    }
    public function openPdfPreview()
    {
        // If dates are not set, default to Today's date (formatted for URL consistency)
        $dFrom = $this->dateFrom ?: Carbon::today()->format('Y/m/d');
        $dTo = $this->dateTo ?: Carbon::today()->format('Y/m/d');

        $params = [
            'dateFrom' => $dFrom,
            'dateTo' => $dTo,
            'user_id' => $this->user_id,
        ];

        $this->pdfUrl = route('reports.cash.count.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }
}

