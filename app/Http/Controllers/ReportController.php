<?php
namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Configuration;
use App\Models\User;
use App\Models\Bank;
use App\Models\Currency;
use App\Models\CollectionSheet;
use App\Models\Payment;
use App\Models\SalePaymentDetail;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function collectionRelationshipPdf(CollectionSheet $sheet, Request $request)
    {
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        $operatorId = $request->get('operator_id');
        $sellerId = $request->get('seller_id');
        $batchName = $request->get('batch_name');
        $zone = $request->get('zone');
        $invoiceFrom = $request->get('invoice_from');
        $invoiceTo = $request->get('invoice_to');

        $query = $sheet->payments()->with(['sale.customer', 'user', 'zelleRecord'])->whereIn('status', ['approved', 'voided']);

        if ($operatorId) {
            $query->where('user_id', $operatorId);
        }

        if ($sellerId || $batchName || $zone || ($invoiceFrom && $invoiceTo)) {
            $query->whereHas('sale', function($q) use ($sellerId, $batchName, $zone, $invoiceFrom, $invoiceTo) {
                if ($sellerId) $q->where('seller_id', $sellerId);
                if ($batchName) $q->where('batch_name', 'like', "%{$batchName}%");
                if ($zone) {
                    $q->whereHas('customer', function($c) use ($zone) {
                        $c->where('zone', 'like', "%{$zone}%");
                    });
                }
                if ($invoiceFrom && $invoiceTo) {
                    $invFrom = 0; $invTo = 0;
                    if (is_numeric($invoiceFrom)) $invFrom = (int)$invoiceFrom;
                    elseif (preg_match('/^[Ff]0*([1-9][0-9]*)$/', $invoiceFrom, $matches)) $invFrom = (int)$matches[1];
                    
                    if (is_numeric($invoiceTo)) $invTo = (int)$invoiceTo;
                    elseif (preg_match('/^[Ff]0*([1-9][0-9]*)$/', $invoiceTo, $matches)) $invTo = (int)$matches[1];

                    if ($invFrom > 0 && $invTo > 0) $q->whereBetween('id', [$invFrom, $invTo]);
                }
            });
        }

        $payments = $query->get();
        $returns = SaleReturn::where('collection_sheet_id', $sheet->id)->with(['sale.customer', 'user'])->get();
        $config = Configuration::first();
        $user = auth()->user();
        $date = Carbon::now()->format('d/m/Y H:i');

        $currencies = Currency::all();
        $banks = Bank::all();
        
        $totalsByCategory = [];
        foreach($currencies as $c) {
            $totalsByCategory["EFECTIVO " . strtoupper($c->code)] = 0;
        }
        foreach($banks as $b) {
            $totalsByCategory[strtoupper($b->name)] = 0;
        }
        $totalsByCategory['NOTAS DE CREDITO (NC)'] = $returns->sum(function($r) {
            $rate = $r->sale->primary_exchange_rate > 0 ? $r->sale->primary_exchange_rate : 1;
            return $r->total_returned / $rate;
        });

        foreach($payments as $p) {
            if ($p->status == 'voided') continue;
            
            $amtUSD = $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1);
            if ($p->pay_way == 'cash') {
                $key = "EFECTIVO " . strtoupper($p->currency);
                $totalsByCategory[$key] = ($totalsByCategory[$key] ?? 0) + $amtUSD;
            } else {
                $bankName = $p->bank ? strtoupper($p->bank) : ($p->pay_way == 'zelle' ? 'ZELLE' : null);
                if ($bankName) {
                    $totalsByCategory[$bankName] = ($totalsByCategory[$bankName] ?? 0) + $amtUSD;
                } else {
                    $othersKey = 'OTROS (BANCOS/MEDIOS)';
                    $totalsByCategory[$othersKey] = ($totalsByCategory[$othersKey] ?? 0) + $amtUSD;
                }
            }
        }

        $totalsByCurrency = [];
        $uniqueCurrencies = $payments->pluck('currency')->unique();
        foreach($uniqueCurrencies as $currencyCode) {
            $totalsByCurrency[$currencyCode] = $payments->where('currency', $currencyCode)->where('status', 'approved')->sum('amount');
        }

        $dateFromFormatted = $dateFrom ?: $sheet->opened_at->format('Y-m-d');
        $dateToFormatted = $dateTo ?: $sheet->opened_at->format('Y-m-d');

        $pdf = Pdf::loadView('reports.collection-relationship-new-pdf', compact('sheet', 'payments', 'returns', 'config', 'user', 'date', 'totalsByCategory', 'totalsByCurrency', 'dateFrom', 'dateTo'));
        
        return $pdf->stream('Relacion_Cobros_' . $sheet->sheet_number . '.pdf');
    }

    public function dailySalesPdf(Request $request)
    {
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        $user_id = $request->get('user_id');
        $seller_id = $request->get('seller_id');
        $customer_id = $request->get('customer_id');
        $type = $request->get('type', 0);
        $searchFolio = $request->get('searchFolio');
        $groupBy = $request->get('groupBy', 'none');

        $dFrom = null;
        $dTo = null;
        $dFrom = null; $dTo = null;
        if($dateFrom && $dateTo) {
            $dFrom = \Carbon\Carbon::parse($dateFrom)->startOfDay();
            $dTo = \Carbon\Carbon::parse($dateTo)->endOfDay();
        }

        $sales = \App\Models\Sale::with(['customer', 'details', 'user', 'paymentDetails.bankRecord.bank'])
            ->when($searchFolio, function($q) use ($searchFolio) {
                $q->where('id', 'like', "%{$searchFolio}%")
                  ->orWhere('invoice_number', 'like', "%{$searchFolio}%");
            })
            ->when($dFrom && $dTo && !$searchFolio, function($q) use ($dFrom, $dTo) {
                $q->whereBetween('created_at', [$dFrom, $dTo]);
            })
            ->when($user_id != null && $user_id != 0, function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->when($seller_id != null && $seller_id != 0, function ($query) use ($seller_id) {
                $query->whereHas('customer', function($q) use ($seller_id) {
                    $q->where('seller_id', $seller_id);
                });
            })
            ->when($customer_id != null, function ($query) use ($customer_id) {
                $query->where('customer_id', $customer_id);
            })
            ->when($type != 0, function ($qry) use ($type) {
                $qry->where('type', $type);
            })
            ->where('status', '<>', 'returned')
            ->orderBy('id', 'desc')
            ->get();

        $data = [];
        if ($groupBy == 'none') {
            $data['ALL'] = ['name' => 'TODOS', 'sales' => $sales, 'total_usd' => $sales->sum('total_usd')];
        } else {
            foreach ($sales as $sale) {
                $key = ''; $name = '';
                if ($groupBy == 'customer_id') {
                    $key = $sale->customer_id; $name = $sale->customer->name;
                } elseif ($groupBy == 'user_id') {
                    $key = $sale->user_id; $name = $sale->user->name;
                } elseif ($groupBy == 'seller_id') {
                    $key = $sale->customer->seller_id ?? 'NA';
                    $name = $sale->customer->seller->name ?? 'SIN VENDEDOR';
                } elseif ($groupBy == 'date') {
                    $key = $sale->created_at->format('Y-m-d'); $name = $sale->created_at->format('d/m/Y');
                }
                if (!isset($data[$key])) { $data[$key] = ['name' => $name, 'sales' => [], 'total_usd' => 0]; }
                $data[$key]['sales'][] = $sale;
                $data[$key]['total_usd'] += $sale->total_usd;
            }
        }

        $currencies = \App\Models\Currency::all();
        $banks = \App\Models\Bank::all();
        
        $totalsByCategory = [];
        foreach($currencies as $c) { $totalsByCategory["EFECTIVO " . strtoupper($c->code)] = 0; }
        foreach($banks as $b) { $totalsByCategory[strtoupper($b->name)] = 0; }
        $totalsByCategory['ZELLE'] = 0;
        $totalsByCategory['NOTAS DE CREDITO (NC)'] = 0;

        $totalsByCurrency = [];
        foreach($currencies as $c) { $totalsByCurrency[$c->code] = 0; }

        // Calculate NC from returns in the period
        $returns = \App\Models\SaleReturn::with(['sale', 'requester', 'approver'])
            ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                $q->whereBetween('created_at', [$dFrom, $dTo]);
            })
            ->when($user_id && $user_id != 0, function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->get();
        
        foreach($returns as $r) {
            $rate = ($r->sale && $r->sale->primary_exchange_rate > 0) ? $r->sale->primary_exchange_rate : 1;
            $totalsByCategory['NOTAS DE CREDITO (NC)'] += ($r->total_returned / $rate);
        }

        $deletedSales = \App\Models\Sale::with(['customer', 'user', 'requester', 'approver'])
            ->whereNotNull('deletion_approved_at')
            ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                $q->whereBetween('deletion_approved_at', [$dFrom, $dTo]);
            })
            ->when($user_id && $user_id != 0, function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->get();
        
        $totalDeleted = $deletedSales->sum('total_usd');

        $summary = [
            'total_bruto' => 0,
            'total_flete' => 0,
            'total_contado' => 0,
            'total_credito' => 0,
            'total_count' => $sales->count(),
            'total_ved' => 0,
            'total_divisa' => 0
        ];

        foreach ($sales as $sale) {
            $summary['total_bruto'] += $sale->total_usd;
            $summary['total_flete'] += $sale->total_freight ?? 0;
            
            $salePaidUSD = 0;
            foreach($sale->paymentDetails as $payment) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                $amtUSD = $payment->amount / $rate;
                $salePaidUSD += $amtUSD;
                
                if(isset($totalsByCurrency[$payment->currency_code])) {
                    $totalsByCurrency[$payment->currency_code] += $payment->amount;
                }

                if ($payment->method == 'bank' || $payment->method == 'deposit') {
                    $bankName = 'BANCO';
                    if ($payment->bankRecord && $payment->bankRecord->bank) {
                        $bankName = strtoupper($payment->bankRecord->bank->name);
                    } elseif ($payment->bank_name) {
                        $bankName = strtoupper($payment->bank_name);
                    }
                    $totalsByCategory[$bankName] = ($totalsByCategory[$bankName] ?? 0) + $amtUSD;
                } elseif ($payment->method == 'zelle') {
                    $totalsByCategory['ZELLE'] += $amtUSD;
                } else {
                    $key = "EFECTIVO " . strtoupper($payment->currency_code);
                    $totalsByCategory[$key] = ($totalsByCategory[$key] ?? 0) + $amtUSD;
                }

                if($payment->currency_code == 'VED' || $payment->currency_code == 'VES') {
                    $summary['total_ved'] += $amtUSD;
                } else {
                    $summary['total_divisa'] += $amtUSD;
                }
            }
            
            if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                $code = $sale->primary_currency_code ?? 'USD';
                $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $amtUSD = $sale->cash / $rate;
                $salePaidUSD += $amtUSD;
                
                if(isset($totalsByCurrency[$code])) { $totalsByCurrency[$code] += $sale->cash; }
                $key = "EFECTIVO " . strtoupper($code);
                $totalsByCategory[$key] = ($totalsByCategory[$key] ?? 0) + $amtUSD;

                if($code == 'VED' || $code == 'VES') {
                    $summary['total_ved'] += $amtUSD;
                } else {
                    $summary['total_divisa'] += $amtUSD;
                }
            }

            $summary['total_contado'] += $salePaidUSD;
            if($sale->status != 'paid') {
                $summary['total_credito'] += max(0, $sale->total_usd - $salePaidUSD);
            }
        }

        $config = \App\Models\Configuration::first();
        $user = auth()->user();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.daily-sales-report-new-pdf', [
            'data' => $data,
            'summary' => $summary,
            'returns' => $returns,
            'deletedSales' => $deletedSales,
            'totalDeleted' => $totalDeleted,
            'totalsByCategory' => $totalsByCategory,
            'totalsByCurrency' => $totalsByCurrency,
            'config' => $config,
            'user' => $user,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'groupBy' => $groupBy
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Reporte_Ventas_Diarias.pdf');
    }

    public function dispatchPdf(Request $request)
    {
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        $driver_id = $request->get('driver_id');
        $seller_id = $request->get('seller_id');
        $columns = json_decode($request->get('columns'), true) ?? [];
        $signatures = json_decode($request->get('signatures'), true) ?? [];

        $dFrom = Carbon::parse($dateFrom)->startOfDay();
        $dTo = Carbon::parse($dateTo)->endOfDay();

        $sales = Sale::with(['customer.seller', 'driver', 'sellerConfig.user', 'paymentDetails'])
            ->whereNotNull('driver_id')
            ->whereBetween('created_at', [$dFrom, $dTo])
            ->when($driver_id && $driver_id !== 'all', function($q) use ($driver_id) {
                $q->where('driver_id', $driver_id);
            })
            ->when($seller_id && $seller_id !== 'all', function($q) use ($seller_id) {
                $q->whereHas('customer', function($c) use ($seller_id) {
                    $c->where('seller_id', $seller_id);
                });
            })
            ->orderBy('driver_id')
            ->orderBy('id')
            ->get();

        $data = [];
        $overallTotalBase = 0;
        $overallTotalFreight = 0;
        $overallTotalCommission = 0;
        $overallTotalDiff = 0;
        $overallTotalFinal = 0;

        foreach ($sales as $sale) {
            $driverKey = $sale->driver_id;
            $driverName = $sale->driver->name ?? 'N/A';
            
            // Get Seller through Customer instead of Sale->seller_id
            $seller = $sale->customer->seller ?? null;
            $sellerId = $seller ? $seller->id : 0;
            $sellerName = $seller ? strtoupper($seller->name) : 'SIN VENDEDOR';

            if (!isset($data[$driverKey])) {
                $data[$driverKey] = [
                    'name' => strtoupper($driverName),
                    'sellers' => [],
                    'total_base' => 0,
                    'total_final' => 0
                ];
            }

            if (!isset($data[$driverKey]['sellers'][$sellerId])) {
                $data[$driverKey]['sellers'][$sellerId] = [
                    'name' => $sellerName,
                    'sales' => [],
                    'total_base' => 0,
                    'total_final' => 0
                ];
            }

            // Calculations
            $totalFac = $sale->total_usd;
            $incPercent = ($sale->applied_commission_percent + $sale->applied_freight_percent + $sale->applied_exchange_diff_percent);
            $baseAmount = $totalFac / (1 + ($incPercent / 100));
            
            // Amounts by concept
            $commAmt = $baseAmount * ($sale->applied_commission_percent / 100);
            $freightAmt = $baseAmount * ($sale->applied_freight_percent / 100);
            $diffAmt = $baseAmount * ($sale->applied_exchange_diff_percent / 100);

            $saleObj = (object)[
                'invoice_number' => $sale->invoice_number ?? $sale->id,
                'customer_name' => $sale->customer->name,
                'destination' => $sale->customer->city ?? 'N/A',
                'base' => $baseAmount,
                'commission_amt' => $commAmt,
                'freight_amt' => $freightAmt,
                'diff_amt' => $diffAmt,
                'inc_percent' => $incPercent,
                'total' => $totalFac,
                'date' => $sale->created_at->format('d/m/Y')
            ];

            $data[$driverKey]['sellers'][$sellerId]['sales'][] = $saleObj;
            $data[$driverKey]['sellers'][$sellerId]['total_base'] += $baseAmount;
            $data[$driverKey]['sellers'][$sellerId]['total_final'] += $totalFac;
            
            $data[$driverKey]['total_base'] += $baseAmount;
            $data[$driverKey]['total_final'] += $totalFac;

            $overallTotalBase += $baseAmount;
            $overallTotalFreight += $freightAmt;
            $overallTotalCommission += $commAmt;
            $overallTotalDiff += $diffAmt;
            $overallTotalFinal += $totalFac;
        }

        $config = Configuration::first();
        $user = auth()->user();

        $pdf = Pdf::loadView('reports.dispatch-report-pdf', [
            'data' => $data,
            'config' => $config,
            'user' => $user,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'columns' => $columns,
            'signatures' => $signatures,
            'overall' => [
                'base' => $overallTotalBase,
                'freight' => $overallTotalFreight,
                'commission' => $overallTotalCommission,
                'diff' => $overallTotalDiff,
                'total' => $overallTotalFinal
            ]
        ])->setPaper('a4', 'landscape');

        if ($request->has('download')) {
            return $pdf->download('Reporte_Despacho.pdf');
        }

        return $pdf->stream('Reporte_Despacho.pdf');
    }

    public function settlementPdf(Request $request)
    {
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        $driver_id = $request->get('driver_id');
        $seller_id = $request->get('seller_id');

        $dFrom = Carbon::parse($dateFrom)->startOfDay();
        $dTo = Carbon::parse($dateTo)->endOfDay();

        $sales = Sale::with(['customer', 'driver', 'deliveryCollections.payments.currency'])
            ->whereNotNull('driver_id')
            ->whereBetween('created_at', [$dFrom, $dTo])
            ->when($driver_id && $driver_id !== 'all', function($q) use ($driver_id) {
                $q->where('driver_id', $driver_id);
            })
            ->when($seller_id && $seller_id !== 'all', function($q) use ($seller_id) {
                $q->whereHas('customer', function($c) use ($seller_id) {
                    $c->where('seller_id', $seller_id);
                });
            })
            ->orderBy('driver_id')
            ->orderBy('id')
            ->get();

        $config = Configuration::first();
        $user = auth()->user();

        $pdf = Pdf::loadView('reports.delivery-settlement-pdf', [
            'sales' => $sales,
            'config' => $config,
            'user' => $user,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Liquidacion_Ruta.pdf');
    }

    public function cashCountPdf(Request $request)
    {
        $dateFrom = $request->get('dateFrom') ?: Carbon::today()->format('Y/m/d');
        $dateTo = $request->get('dateTo') ?: Carbon::today()->format('Y/m/d');
        $user_id = $request->get('user_id', 0);

        $dFrom = Carbon::parse($dateFrom)->startOfDay();
        $dTo = Carbon::parse($dateTo)->endOfDay();

        $currencies = Currency::orderBy('is_primary', 'desc')->get();
        $primaryCurrency = $currencies->firstWhere('is_primary', 1);
        $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'COP';
        $symbol = $primaryCurrency ? $primaryCurrency->symbol : '$';

        $sales = Sale::whereBetween('created_at', [$dFrom, $dTo])
                ->when($user_id != 0, function ($qry) use ($user_id) {
                    $qry->where('user_id', $user_id);
                })
                ->where('status', '<>', 'returned')
                ->select('id', 'total', 'cash', 'change', 'type', 'primary_exchange_rate', 'customer_id')
                ->get();

        $totalSales = $sales->sum(function($sale) use ($primaryRate) {
            $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
            $totalUSD = $sale->total / $saleRate;
            return $totalUSD * $primaryRate;
        });

        $saleIds = $sales->pluck('id');
        $paymentDetails = SalePaymentDetail::whereIn('sale_id', $saleIds)->get();
        
        $salesByCurrency = $this->aggregateSalesByCurrency($sales, $paymentDetails, $currencies);

        $totalCreditSales = $sales->where('type', 'credit')->sum(function($sale) use ($primaryRate) {
            $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
            $totalUSD = $sale->total / $saleRate;
            return $totalUSD * $primaryRate;
        });

        $payments = Payment::whereBetween('created_at', [$dFrom, $dTo])
            ->when($user_id != 0, function ($qry) use ($user_id) {
                $qry->where('user_id', $user_id);
            })
            ->where('status', 'approved')
            ->select('id', 'pay_way', 'amount', 'bank', 'currency', 'exchange_rate', 'primary_exchange_rate')
            ->get();

        $totalPayments = $payments->sum(function($payment) use ($primaryRate) {
            $paymentRate = $payment->exchange_rate ?? 1;
            $paymentPrimaryRate = $payment->primary_exchange_rate ?? $primaryRate;
            $amountUSD = $payment->amount / $paymentRate;
            return $amountUSD * $paymentPrimaryRate;
        });

        $paymentsByCurrency = $this->aggregatePaymentsByCurrency($payments, $currencies);

        $totalCashDetails = [];
        if (isset($salesByCurrency['cash'])) {
            foreach ($salesByCurrency['cash'] as $currency => $amount) {
                $totalCashDetails[$currency] = ($totalCashDetails[$currency] ?? 0) + $amount;
            }
        }
        if (isset($paymentsByCurrency['cash'])) {
            foreach ($paymentsByCurrency['cash'] as $currency => $amount) {
                $totalCashDetails[$currency] = ($totalCashDetails[$currency] ?? 0) + $amount;
            }
        }

        $totalBankDetails = [];
        if (isset($salesByCurrency['deposit'])) {
            foreach ($salesByCurrency['deposit'] as $bankName => $value) {
                if (is_array($value)) {
                    foreach ($value as $currency => $amount) {
                        $totalBankDetails[$bankName][$currency] = ($totalBankDetails[$bankName][$currency] ?? 0) + $amount;
                    }
                } else {
                    $totalBankDetails['Otros'][$bankName] = ($totalBankDetails['Otros'][$bankName] ?? 0) + $value;
                }
            }
        }
        if (isset($paymentsByCurrency['deposit'])) {
            foreach ($paymentsByCurrency['deposit'] as $bankName => $currenciesInBank) {
                foreach ($currenciesInBank as $currency => $amount) {
                    $totalBankDetails[$bankName][$currency] = ($totalBankDetails[$bankName][$currency] ?? 0) + $amount;
                }
            }
        }

        $totalZelleDetails = [];
        if (isset($salesByCurrency['zelle'])) {
            foreach ($salesByCurrency['zelle'] as $sender => $amount) {
                $totalZelleDetails[$sender] = ($totalZelleDetails[$sender] ?? 0) + $amount;
            }
        }
        if (isset($paymentsByCurrency['zelle'])) {
            foreach ($paymentsByCurrency['zelle'] as $sender => $amount) {
                $totalZelleDetails[$sender] = ($totalZelleDetails[$sender] ?? 0) + $amount;
            }
        }

        $config = Configuration::first();
        $user_name = $user_id == 0 ? 'Todos los usuarios' : User::find($user_id)->name;

        $getLabel = function($code) use ($currencies) {
            $c = $currencies->firstWhere('code', $code);
            return $c ? $c->label : $code;
        };

        $convertToPrimary = function($amount, $currencyCode) use ($currencies, $primaryRate) {
             if ($currencyCode == 'USD') { return $amount * $primaryRate; }
             $curr = $currencies->firstWhere('code', $currencyCode);
             if (!$curr) return $amount;
             $rate = $curr->exchange_rate > 0 ? $curr->exchange_rate : 1;
             $amtUSD = $amount / $rate;
             return $amtUSD * $primaryRate;
        };

        $pdf = Pdf::loadView('reports.cash-count-pdf', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'user_name' => $user_name,
            'salesTotal' => $totalSales,
            'credit' => $totalCreditSales,
            'payments' => $totalPayments,
            'salesByCurrency' => $salesByCurrency,
            'paymentsByCurrency' => $paymentsByCurrency,
            'totalCashDetails' => $totalCashDetails,
            'totalBankDetails' => $totalBankDetails,
            'totalZelleDetails' => $totalZelleDetails,
            'config' => $config,
            'symbol' => $symbol,
            'getLabel' => $getLabel,
            'convertToPrimary' => $convertToPrimary
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("Corte_Caja_{$dateFrom}.pdf");
    }

    private function aggregateSalesByCurrency($sales, $paymentDetails, $currencies)
    {
        $aggregated = ['cash' => [], 'nequi' => [], 'deposit' => [], 'zelle' => []];
        $primaryCurrency = $currencies->firstWhere('is_primary', 1);
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'COP';
        $paymentsBySale = $paymentDetails->groupBy('sale_id');

        foreach ($sales as $sale) {
            if (isset($paymentsBySale[$sale->id])) {
                foreach ($paymentsBySale[$sale->id] as $paymentDetail) {
                    $currency = $paymentDetail->currency_code;
                    $bankName = $paymentDetail->bank_name;
                    $paymentMethod = $paymentDetail->payment_method ?? 'cash';
                    $category = match($paymentMethod) { 'cash' => 'cash', 'nequi' => 'nequi', 'bank' => 'deposit', 'zelle' => 'zelle', default => 'cash' };
                    
                    if ($category == 'deposit' && $bankName) {
                        $aggregated['deposit'][$bankName][$currency] = ($aggregated['deposit'][$bankName][$currency] ?? 0) + $paymentDetail->amount;
                    } elseif ($category == 'zelle') {
                         $sender = 'Desconocido';
                         if ($paymentDetail->zelleRecord) { $sender = $paymentDetail->zelleRecord->sender_name . ' (Ref: ' . $paymentDetail->zelleRecord->reference . ')'; }
                         $aggregated['zelle'][$sender] = ($aggregated['zelle'][$sender] ?? 0) + $paymentDetail->amount;
                    } else {
                        $aggregated[$category][$currency] = ($aggregated[$category][$currency] ?? 0) + $paymentDetail->amount;
                    }
                }
            } else {
                $category = match($sale->type) { 'cash', 'cash/nequi', 'mixed' => 'cash', 'nequi' => 'nequi', 'deposit', 'bank' => 'deposit', default => null };
                if ($category === null || $sale->type === 'credit') continue;
                $netAmount = $sale->cash - $sale->change;
                if ($netAmount > 0) { $aggregated[$category][$primaryCode] = ($aggregated[$category][$primaryCode] ?? 0) + $netAmount; }
            }
        }
        return $aggregated;
    }

    private function aggregatePaymentsByCurrency($payments, $currencies)
    {
        $aggregated = ['cash' => [], 'nequi' => [], 'deposit' => [], 'zelle' => []];
        $primaryCurrency = $currencies->firstWhere('is_primary', 1);
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'COP';

        foreach ($payments as $payment) {
            $payWay = $payment->pay_way;
            $currency = $payment->currency ?? $primaryCode;

            if ($payWay == 'deposit' && !empty($payment->bank)) {
                $bankName = $payment->bank;
                $aggregated['deposit'][$bankName][$currency] = ($aggregated['deposit'][$bankName][$currency] ?? 0) + $payment->amount;
            } elseif ($payWay == 'zelle') {
                 $sender = 'Desconocido';
                 if ($payment->zelleRecord) { $sender = $payment->zelleRecord->sender_name . ' (Ref: ' . $payment->zelleRecord->reference . ')'; }
                 $aggregated['zelle'][$sender] = ($aggregated['zelle'][$sender] ?? 0) + $payment->amount;
            } else {
                $aggregated[$payWay][$currency] = ($aggregated[$payWay][$currency] ?? 0) + $payment->amount;
            }
        }
        return $aggregated;
    }

    public function inventoryPdf(Request $request)
    {
        $supplier_id = $request->get('supplier_id');
        $category_id = $request->get('category_id');
        $columns = json_decode($request->get('columns'), true) ?? [];
        $signatures = json_decode($request->get('signatures'), true) ?? [];
        $search = $request->get('search');

        $products = \App\Models\Product::where('status', 'available')
            ->when($supplier_id && $supplier_id !== 'all', function ($q) use ($supplier_id) {
                $q->where('supplier_id', $supplier_id);
            })
            ->when($category_id && $category_id !== 'all', function ($q) use ($category_id) {
                $q->where('category_id', $category_id);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->with(['category', 'supplier'])
            ->orderBy('name')
            ->get();

        $config = Configuration::first();
        $user = auth()->user();

        $supplier_name = 'Todos';
        if($supplier_id && $supplier_id !== 'all'){
            $s = \App\Models\Supplier::find($supplier_id);
            $supplier_name = $s ? $s->name : 'N/A';
        }

        $category_name = 'Todas';
        if($category_id && $category_id !== 'all'){
            $c = \App\Models\Category::find($category_id);
            $category_name = $c ? $c->name : 'N/A';
        }

        $totals = [
            'cost' => $products->sum(fn($p) => $p->stock_qty * $p->cost),
            'price' => $products->sum(fn($p) => $p->stock_qty * $p->price),
            'items' => $products->sum('stock_qty')
        ];

        $pdf = Pdf::loadView('reports.inventory-report-pdf', [
            'products' => $products,
            'config' => $config,
            'user' => $user,
            'columns' => $columns,
            'signatures' => $signatures,
            'supplier_name' => $supplier_name,
            'category_name' => $category_name,
            'totals' => $totals
        ])->setPaper('a4', 'portrait');

        if ($request->has('download')) {
            return $pdf->download('Reporte_Inventario.pdf');
        }

        return $pdf->stream('Reporte_Inventario.pdf');
    }

    public function accountsReceivablePdf(Request $request)
    {
        $customer_id = $request->get('customer_id');
        $seller_id = $request->get('seller_id');
        $user_id = $request->get('user_id');
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        $status = $request->get('status');
        $groupBy = $request->get('groupBy', 'customer_id');
        $searchFactura = $request->get('searchFactura');

        // Security check matching Livewire component
        if (!auth()->user()->can('sales.view_all')) {
            $user_id = auth()->id();
        }

        $query = Sale::with(['customer', 'details', 'user', 'paymentDetails'])
            ->where('type', 'credit')
            ->where('status', '<>', 'returned');

        if ($customer_id) {
            $query->where('customer_id', $customer_id);
        }
        if ($seller_id) {
            $query->whereHas('customer', function($q) use ($seller_id) {
                $q->where('seller_id', $seller_id);
            });
        }
        if ($user_id) {
            $query->where('user_id', $user_id);
        }
        if ($dateFrom && $dateTo) {
            $dFrom = Carbon::parse($dateFrom)->startOfDay();
            $dTo = Carbon::parse($dateTo)->endOfDay();
            $query->whereBetween('created_at', [$dFrom, $dTo]);
        }
        
        if ($searchFactura) {
             $numericSearch = is_numeric($searchFactura) ? (int)$searchFactura : null;
             if (preg_match('/^[Ff]0*([1-9][0-9]*)$/', $searchFactura, $matches)) {
                 $numericSearch = (int)$matches[1];
             }
             
             $query->where(function($q) use ($searchFactura, $numericSearch) {
                 if ($numericSearch !== null) {
                     $q->where('id', $numericSearch);
                 } else {
                     $q->where('invoice_number', 'like', "%{$searchFactura}%");
                 }
             });
        }
        
        if ($status && $status != '0') {
            $query->where('status', $status);
        }

        $sales = $query->orderBy('id', 'asc')->get();

        if ($sales->isEmpty()) {
            return response('No hay datos para generar el reporte.', 404);
        }

        $data = [];
        $grandTotalDebt = 0;

        foreach ($sales as $sale) {
            $totalPaidUSD = $sale->payments->whereNotIn('status', ['pending', 'rejected', 'voided'])->sum(function($payment) use ($sale) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : ($payment->currency == 'USD' ? 1 : ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1));
                return $payment->amount / $rate;
            });
            
            $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                return $detail->amount / $rate;
            });

            $totalReturnsOrig = $sale->returns->where('refund_method', 'debt_reduction')->where('status', 'approved')->sum('total_returned');
            $exchangeRateReturns = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
            $totalReturnsUSD = $totalReturnsOrig / $exchangeRateReturns;

            $totalUSD = $sale->total_usd;
            if (!$totalUSD || $totalUSD == 0) {
                $exchangeRate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $totalUSD = $sale->total / $exchangeRate;
            }

            $balance = max(0, $totalUSD - ($totalPaidUSD + $initialPaidUSD + $totalReturnsUSD));
            $balance_before_nc = max(0, $totalUSD - ($totalPaidUSD + $initialPaidUSD));

            if ($status != 'paid' && $balance < 0.01) continue;

             $key = '';
             $name = '';
 
             if ($groupBy == 'customer_id') {
                 $key = $sale->customer_id;
                 $name = $sale->customer->name ?? 'SIN CLIENTE';
             } elseif ($groupBy == 'user_id') {
                 $key = $sale->user_id;
                 $name = $sale->user->name ?? 'SIN USUARIO';
             } elseif ($groupBy == 'seller_id') {
                 $key = $sale->customer->seller_id ?? 'NA';
                 $name = $sale->customer->seller->name ?? 'SIN VENDEDOR';
             } elseif ($groupBy == 'date') {
                 $key = $sale->created_at->format('Y-m-d');
                 $name = $sale->created_at->format('d/m/Y');
             } else {
                 $key = 'ALL';
                 $name = 'TODOS';
             }
 
             if (!isset($data[$key])) {
                 $data[$key] = [
                     'name' => $name,
                     'invoices' => [],
                     'total_debt' => 0,
                     'customer' => $sale->customer
                 ];
             }
 
             $dueDate = clone $sale->created_at;
             $dueDate->addDays(30);
             if($sale->customer && $sale->customer->payment_terms) {
                 $dueDate = $sale->created_at->copy()->addDays(intval($sale->customer->payment_terms));
             }

             $daysOverdue = 0;
             if ($sale->days_overdue !== null) {
                 $daysOverdue = intval($sale->days_overdue);
             } else {
                 $daysOverdue = floor(Carbon::now()->diffInDays($dueDate, false) * -1); 
             }

             $creditNotes = [];
             $sum_nc = 0;
             foreach($sale->returns->where('refund_method', 'debt_reduction')->where('status', 'approved') as $return) {
                 $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                 $returnAmt = $return->total_returned / $rate;
                 $sum_nc += $returnAmt;
                 $creditNotes[] = [
                     'operation' => 'N/C',
                     'date' => $return->created_at->format('d/m/Y'),
                     'due_date' => $return->created_at->format('d/m/Y'),
                     'days' => $daysOverdue,
                     'doc_no' => str_pad($return->id, 8, '0', STR_PAD_LEFT),
                     'description' => 'Factnr:' .  ($sale->invoice_number ?? $sale->id) . ' Doc:' . str_pad($return->id, 8, '0', STR_PAD_LEFT),
                     'amount' => -1 * $returnAmt
                 ];
             }

             $data[$key]['invoices'][] = [
                 'operation' => 'Factura',
                 'date' => $sale->created_at->format('d/m/Y'),
                 'due_date' => $dueDate->format('d/m/Y'),
                 'days' => $daysOverdue * -1, 
                 'doc_no' => str_pad($sale->invoice_number ?? $sale->id, 8, '0', STR_PAD_LEFT),
                 'description' => 'Factnr:' .  ($sale->invoice_number ?? $sale->id) . ' Doc:' . str_pad($sale->invoice_number ?? $sale->id, 8, '0', STR_PAD_LEFT),
                 'total' => $totalUSD,
                 'balance' => $balance_before_nc, // This is explicitly passed instead of $balance to make visual sums accurate
                 'credit_notes' => $creditNotes
             ];
             
             // To ensure visual totals match what the user reads on the paper exactly:
             $visualDebtLine = $balance_before_nc - $sum_nc;
             $data[$key]['total_debt'] += $visualDebtLine;
             $grandTotalDebt += $visualDebtLine;
        }

        if (empty($data)) {
            return response('NO HAY CUENTAS POR COBRAR PENDIENTES', 404);
        }

        $config = Configuration::first();
        $user = auth()->user();
        $date = Carbon::now()->format('d/m/Y');
        $time = Carbon::now()->format('h:i a');
        $seller_name = $seller_id ? \App\Models\User::find($seller_id)->name : null;

        $pdf = Pdf::loadView('reports.accounts-receivable-pdf', compact('data', 'config', 'user', 'date', 'time', 'groupBy', 'grandTotalDebt', 'seller_name'))
            ->setPaper('a4', 'portrait');

        if ($request->has('download')) {
             return $pdf->download('Cuentas_Por_Cobrar_' . Carbon::now()->format('YmdHis') . '.pdf');
        }

        return $pdf->stream('Cuentas_Por_Cobrar_' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    public function productMovementsPdf(Request $request)
    {
        $product_id = $request->get('product_id');
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        $selected_warehouse_id = $request->get('warehouse_id', 'all');

        $product = \App\Models\Product::with(['category', 'supplier'])->findOrFail($product_id);
        
        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->endOfDay();

        // 1. Initial Stock
        $inBefore = DB::table('purchase_details')
                    ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
                    ->where('product_id', $product_id)->where('purchase_details.created_at', '<', $start)
                    ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                        $q->where('purchases.warehouse_id', $selected_warehouse_id);
                    })->sum('quantity')
                  + DB::table('cargo_details')->where('product_id', $product_id)->where('cargo_details.created_at', '<', $start)
                        ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                            $q->join('cargos', 'cargos.id', '=', 'cargo_details.cargo_id')
                                ->where('cargos.warehouse_id', $selected_warehouse_id);
                        })->sum('quantity')
                  + DB::table('sale_return_details')
                        ->join('sale_details', 'sale_details.id', '=', 'sale_return_details.sale_detail_id')
                        ->where('sale_return_details.product_id', $product_id)
                        ->where('sale_return_details.created_at', '<', $start)
                        ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                            $q->where('sale_details.warehouse_id', $selected_warehouse_id);
                        })->sum('quantity_returned')
                  + DB::table('transfer_details')->join('transfers', 'transfers.id', '=', 'transfer_details.transfer_id')
                                ->where('product_id', $product_id)->where('transfer_details.created_at', '<', $start)
                                ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                                    $q->where('transfers.to_warehouse_id', $selected_warehouse_id);
                                })->sum('quantity');

        $outBefore = DB::table('sale_details')->where('product_id', $product_id)->where('sale_details.created_at', '<', $start)
                        ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                            $q->where('warehouse_id', $selected_warehouse_id);
                        })->sum('quantity')
                   + DB::table('descargo_details')->where('product_id', $product_id)->where('descargo_details.created_at', '<', $start)
                        ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                            $q->join('descargos', 'descargos.id', '=', 'descargo_details.descargo_id')
                                ->where('descargos.warehouse_id', $selected_warehouse_id);
                        })->sum('quantity')
                    + DB::table('transfer_details')->join('transfers', 'transfers.id', '=', 'transfer_details.transfer_id')
                                ->where('product_id', $product_id)->where('transfer_details.created_at', '<', $start)
                                ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                                    $q->where('transfers.from_warehouse_id', $selected_warehouse_id);
                                })->sum('quantity');

        $initialStock = $inBefore - $outBefore;

        // 2. Movements
        $v = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->join('customers as c', 'c.id', '=', 's.customer_id')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sd.warehouse_id')
            ->where('sd.product_id', $product_id)
            ->whereBetween('sd.created_at', [$start, $end])
            ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                $q->where('sd.warehouse_id', $selected_warehouse_id);
            })
            ->select('sd.created_at as movement_date', DB::raw("'Venta' as type"), 's.invoice_number as reference', 'u.name as operator', 'c.name as detail', 'w.name as warehouse_name', DB::raw("0 as quantity_in"), 'sd.quantity as quantity_out');

        $co = DB::table('purchase_details as pd')
            ->join('purchases as p', 'p.id', '=', 'pd.purchase_id')
            ->join('suppliers as su', 'su.id', '=', 'p.supplier_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'p.warehouse_id')
            ->where('pd.product_id', $product_id)
            ->whereBetween('pd.created_at', [$start, $end])
            ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                $q->where('p.warehouse_id', $selected_warehouse_id);
            })
            ->select('pd.created_at as movement_date', DB::raw("'Compra' as type"), 'p.id as reference', 'u.name as operator', 'su.name as detail', DB::raw("COALESCE(w.name, 'Principal (Compras)') as warehouse_name"), 'pd.quantity as quantity_in', DB::raw("0 as quantity_out"));

        $ca = DB::table('cargo_details as cd')
            ->join('cargos as car', 'car.id', '=', 'cd.cargo_id')
            ->join('users as u', 'u.id', '=', 'car.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'car.warehouse_id')
            ->where('cd.product_id', $product_id)
            ->whereBetween('cd.created_at', [$start, $end])
            ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                $q->where('car.warehouse_id', $selected_warehouse_id);
            })
            ->select('cd.created_at as movement_date', DB::raw("'Cargo (Ajuste)' as type"), 'car.id as reference', 'u.name as operator', 'car.motive as detail', 'w.name as warehouse_name', 'cd.quantity as quantity_in', DB::raw("0 as quantity_out"));

        $de = DB::table('descargo_details as dd')
            ->join('descargos as des', 'des.id', '=', 'dd.descargo_id')
            ->join('users as u', 'u.id', '=', 'des.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'des.warehouse_id')
            ->where('dd.product_id', $product_id)
            ->whereBetween('dd.created_at', [$start, $end])
            ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                $q->where('des.warehouse_id', $selected_warehouse_id);
            })
            ->select('dd.created_at as movement_date', DB::raw("'Descargo (Salida)' as type"), 'des.id as reference', 'u.name as operator', 'des.motive as detail', 'w.name as warehouse_name', DB::raw("0 as quantity_in"), 'dd.quantity as quantity_out');

        $re = DB::table('sale_return_details as rd')
            ->join('sale_returns as sr', 'sr.id', '=', 'rd.sale_return_id')
            ->join('sale_details as sd_orig', 'sd_orig.id', '=', 'rd.sale_detail_id')
            ->join('sales as s', 's.id', '=', 'sr.sale_id')
            ->join('customers as cl', 'cl.id', '=', 's.customer_id')
            ->join('users as u', 'u.id', '=', 'sr.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sd_orig.warehouse_id')
            ->where('rd.product_id', $product_id)
            ->whereBetween('rd.created_at', [$start, $end])
            ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                $q->where('sd_orig.warehouse_id', $selected_warehouse_id);
            })
            ->select('rd.created_at as movement_date', DB::raw("'Devolución (NC)' as type"), 'sr.id as reference', 'u.name as operator', 'cl.name as detail', DB::raw("COALESCE(w.name, 'Principal (NC)') as warehouse_name"), 'rd.quantity_returned as quantity_in', DB::raw("0 as quantity_out"));

        $trIn = DB::table('transfer_details as td')
            ->join('transfers as t', 't.id', '=', 'td.transfer_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 't.to_warehouse_id')
            ->leftJoin('warehouses as wf', 'wf.id', '=', 't.from_warehouse_id')
            ->where('td.product_id', $product_id)
            ->whereBetween('td.created_at', [$start, $end])
            ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                $q->where('t.to_warehouse_id', $selected_warehouse_id);
            })
            ->select('td.created_at as movement_date', DB::raw("'Transferencia (Entrada)' as type"), 't.id as reference', 'u.name as operator', DB::raw("CONCAT(COALESCE(wf.name, 'N/A'), ' -> ', COALESCE(w.name, 'N/A')) as detail"), 'w.name as warehouse_name', 'td.quantity as quantity_in', DB::raw("0 as quantity_out"));

        $trOut = DB::table('transfer_details as td')
            ->join('transfers as t', 't.id', '=', 'td.transfer_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 't.from_warehouse_id')
            ->leftJoin('warehouses as wt', 'wt.id', '=', 't.to_warehouse_id')
            ->where('td.product_id', $product_id)
            ->whereBetween('td.created_at', [$start, $end])
            ->when($selected_warehouse_id != 'all', function($q) use($selected_warehouse_id) {
                $q->where('t.from_warehouse_id', $selected_warehouse_id);
            })
            ->select('td.created_at as movement_date', DB::raw("'Transferencia (Salida)' as type"), 't.id as reference', 'u.name as operator', DB::raw("CONCAT(COALESCE(w.name, 'N/A'), ' -> ', COALESCE(wt.name, 'N/A')) as detail"), 'w.name as warehouse_name', DB::raw("0 as quantity_in"), 'td.quantity as quantity_out');

        $movements = $v->unionAll($co)->unionAll($ca)->unionAll($de)->unionAll($re)->unionAll($trIn)->unionAll($trOut)->orderBy('movement_date', 'asc')->get();

        $totalIn = $movements->sum('quantity_in');
        $totalOut = $movements->sum('quantity_out');
        $finalStock = $initialStock + $totalIn - $totalOut;

        $config = Configuration::first();
        $user = auth()->user();
        $warehouse_name = $selected_warehouse_id != 'all' ? \App\Models\Warehouse::find($selected_warehouse_id)->name : 'TODOS LOS DEPÓSITOS';

        $pdf = Pdf::loadView('reports.product-movements-pdf', compact('product', 'movements', 'initialStock', 'totalIn', 'totalOut', 'finalStock', 'config', 'user', 'dateFrom', 'dateTo', 'warehouse_name'));

        return $pdf->stream('Kardex_' . $product->sku . '.pdf');
    }
}
