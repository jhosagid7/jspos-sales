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

        $dFrom = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
        $dTo = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : \Carbon\Carbon::today()->endOfDay();

        $sales = \App\Models\Sale::with([
                'customer', 
                'details', 
                'user', 
                'paymentDetails' => fn($q) => $q->whereBetween('created_at', [$dFrom, $dTo])->with(['zelleRecord', 'bankRecord.bank']),
                'changeDetails' => fn($q) => $q->whereBetween('created_at', [$dFrom, $dTo]),
                'returns' => fn($q) => $q->whereBetween('created_at', [$dFrom, $dTo])
            ])
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
            ->whereNull('deletion_approved_at')
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

        $totalsByCurrency = [];
        foreach($currencies as $c) { $totalsByCurrency[$c->code] = 0; }

        // Fetch Returns for list table in PDF (no sum here - summing done per-sale below)
        $returns = \App\Models\SaleReturn::with(['sale', 'requester', 'approver'])
            ->where('status', 'approved')
            ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                $q->whereBetween('created_at', [$dFrom, $dTo]);
            })
            ->when($user_id && $user_id != 0, function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->get();

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
            'total_bruto'   => 0,
            'total_flete'   => 0,
            'total_contado' => 0,
            'total_credito' => 0,
            'total_count'   => $sales->count(),
            'total_ved'     => 0,
            'total_divisa'  => 0,
            'total_nc_raw'  => 0,
        ];

        $totalNCUSD          = 0;
        $totalWalletAddedUSD = 0; 
        $totalWalletUsedUSD  = 0; 
        
        $grandTotalNeto      = 0;
        $grandTotalCredit    = 0;
        $grandRawVed         = 0;
        $grandRawCop         = 0;

        // LEFT TABLE: Categories in USD
        $totalsByCategory = [
            'EFECTIVO USD'       => 0,
            'EFECTIVO VED'       => 0,
            'EFECTIVO COP'       => 0,
            'BANCOLOMBIA'        => 0,
            'BANCO DE VENEZUELA' => 0,
            'ZELLE'              => 0,
            'BANESCO'            => 0,
            'PROVINCIAL'         => 0,
        ];

        // RIGHT TABLE: Totals in Physical Original Currency
        $totalsByCurrencyPhys = [];
        $totalDivisaPaid = 0;
        
        foreach ($sales as $sale) {
            $r_rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;

            // 1. Net: subtract approved returns for this sale
            $returnsForSale = \App\Models\SaleReturn::where('sale_id', $sale->id)
                ->where('status', 'approved')
                ->get();

            $retAmtUSD = 0;
            foreach ($returnsForSale as $ret) {
                $rt_rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $retAmtUSD += ($ret->total_returned / $rt_rate);
                if (str_contains(strtolower($ret->refund_method ?? ''), 'wallet')) {
                    $totalWalletAddedUSD += ($ret->total_returned / $rt_rate);
                }
            }

            $netSaleUSD = $sale->total_usd - $retAmtUSD;
            $totalNCUSD += $retAmtUSD;

            // 2. Accumulate net summary
            $summary['total_bruto'] += round($netSaleUSD, 4);
            $summary['total_flete'] += round($sale->total_freight ?? 0, 4);

            $salePaidUSD = 0;
            $saleDivisaPaid = 0;

            // 3. Process payments
            foreach($sale->paymentDetails as $payment) {
                $rate   = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                $amtUSD = $payment->amount / $rate;
                $salePaidUSD += $amtUSD;

                if(isset($totalsByCurrency[$payment->currency_code])) {
                    $totalsByCurrency[$payment->currency_code] += $payment->amount;
                }

                $pCurr = strtoupper($payment->currency_code);

                if ($payment->payment_method == 'wallet') {
                    $totalWalletUsedUSD += $amtUSD;
                    $totalsByCategory['PAGO BILLETERA'] = ($totalsByCategory['PAGO BILLETERA'] ?? 0) + $amtUSD;
                } else {
                    // RIGHT TABLE (PHYSICAL)
                    $totalsByCurrencyPhys[$pCurr] = ($totalsByCurrencyPhys[$pCurr] ?? 0) + $payment->amount;

                    // LEFT TABLE (USD CATEGORIES)
                    if ($payment->payment_method == 'bank' || $payment->payment_method == 'deposit') {
                        $bankName = 'BANCO';
                        if ($payment->bankRecord && $payment->bankRecord->bank) {
                            $bankName = strtoupper($payment->bankRecord->bank->name);
                        } elseif ($payment->bank_name) {
                            $bankName = strtoupper($payment->bank_name);
                        }
                        $totalsByCategory[$bankName] = ($totalsByCategory[$bankName] ?? 0) + $amtUSD;
                    } elseif ($payment->payment_method == 'zelle') {
                        $totalsByCategory['ZELLE'] = ($totalsByCategory['ZELLE'] ?? 0) + $amtUSD;
                    } else {
                        $key = "EFECTIVO " . $pCurr;
                        $totalsByCategory[$key] = ($totalsByCategory[$key] ?? 0) + $amtUSD;
                    }

                    // Is it Divisa? (USD/Zelle/etc, basically NOT VED/COP)
                    if($pCurr != 'VED' && $pCurr != 'VES' && $pCurr != 'COP') {
                        $saleDivisaPaid += $amtUSD;
                    }
                }

                if($payment->currency_code == 'VED' || $payment->currency_code == 'VES') {
                    $summary['total_ved'] += $amtUSD;
                    $grandRawVed += $payment->amount;
                }
                if($payment->currency_code == 'COP') {
                    $grandRawCop += $payment->amount;
                }
            }

            // 4. Subtract change (vueltos)
            foreach($sale->changeDetails as $change) {
                $rateC   = $change->exchange_rate > 0 ? $change->exchange_rate : 1;
                $amtUSD_C = $change->amount / $rateC;
                $cCurr = strtoupper($change->currency_code);
                
                $salePaidUSD -= $amtUSD_C;

                // Subtract from Physical Original Currency
                $totalsByCurrencyPhys[$cCurr] = ($totalsByCurrencyPhys[$cCurr] ?? 0) - $change->amount;

                $keyC = "EFECTIVO " . $cCurr;
                $totalsByCategory[$keyC] = ($totalsByCategory[$keyC] ?? 0) - $amtUSD_C;

                if($cCurr != 'VED' && $cCurr != 'VES' && $cCurr != 'COP') {
                    $saleDivisaPaid -= $amtUSD_C;
                }
            }

            // 5. Legacy cash sales (no paymentDetails)
            if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                $code   = $sale->primary_currency_code ?? 'USD';
                $rate   = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $amtUSD = ($sale->cash - $sale->change) / $rate;
                $salePaidUSD += $amtUSD;
                if(isset($totalsByCurrency[$code])) { $totalsByCurrency[$code] += ($sale->cash - $sale->change); }
                
                $totalsByCurrencyPhys[strtoupper($code)] = ($totalsByCurrencyPhys[strtoupper($code)] ?? 0) + ($sale->cash - $sale->change);

                $key = "EFECTIVO " . strtoupper($code);
                $totalsByCategory[$key] = ($totalsByCategory[$key] ?? 0) + $amtUSD;
                if($code == 'VED' || $code == 'VES') { 
                    $summary['total_ved'] += $amtUSD; 
                } else if($code != 'COP') {
                    $saleDivisaPaid += $amtUSD;
                }
            }

            $summary['total_contado'] += $salePaidUSD;
            $totalDivisaPaid += $saleDivisaPaid;
            
            $grandTotalNeto += $sale->total_usd;

            // Immutable Credit calculation: Total Neto - Payments made during the report range
            $remainingAsCredit = max(0, $netSaleUSD - $salePaidUSD);
            if ($remainingAsCredit > 0.01) {
                $grandTotalCredit += $remainingAsCredit;
                $summary['total_credito'] += $remainingAsCredit;
                $sale->is_historical_credit = true; // Flag for PDF if needed
            }
        }

        // Handle returns (NC)
        $totalNCRawToday = 0;
        $totalNCRawOld   = 0;
        
        // Use dFrom if available, otherwise Today (fixes the format() on null crash)
        $reportDate = ($dFrom ?? Carbon::now())->format('Y-m-d');

        foreach ($returns as $ret) {
            if (!$ret->sale) continue;
            $saleDate = \Carbon\Carbon::parse($ret->sale->created_at)->format('Y-m-d');
            $saleCurrCode = strtoupper($ret->sale->primary_currency_code ?? 'USD');
            $rt_rate = $ret->sale->primary_exchange_rate > 0 ? $ret->sale->primary_exchange_rate : 1;
            $retAmtUSD = $ret->total_returned / $rt_rate;

            if ($saleDate === $reportDate) {
                $totalNCRawToday += $retAmtUSD;
            } else {
                $totalNCRawOld += $retAmtUSD;
                $ret->is_old_sale = true; // Flag for Blade
            }

            // Physical Count (Right Table) - only if cash actually left the drawer TODAY
            if ($ret->refund_method === 'cash') {
                if (isset($totalsByCurrencyPhys[$saleCurrCode])) {
                    $totalsByCurrencyPhys[$saleCurrCode] -= $ret->total_returned;
                }
            }
        }

        $summary['total_nc_raw'] = $totalNCRawToday;
        $summary['total_divisa'] = $totalDivisaPaid - $totalNCRawToday; // Net for top summary
        $summary['total_final']  = $summary['total_contado'] + $summary['total_flete'] + $summary['total_credito'] - $totalNCRawToday;

        $totalWalletAddedUSD = \App\Models\SaleReturn::whereBetween('created_at', [$dFrom, $dTo])
            ->where('refund_method', 'wallet')
            ->where('status', 'approved')
            ->get()
            ->sum(function($r) {
                $rate = ($r->sale && $r->sale->primary_exchange_rate > 0) ? $r->sale->primary_exchange_rate : 1;
                return $r->total_returned / $rate;
            });
            
        // Explicitly add NC category for the left table summary
        if ($totalNCRawToday > 0.0001) {
            $totalsByCategory['(-) DEVOLUCIONES (NC HOY)'] = -$totalNCRawToday;
        }

        if ($totalNCRawOld > 0.0001) {
            $totalsByCategory['NC FACT. ANTIGUAS (NO AFECTA CAJA)'] = 0; // Info only
            $summary['total_nc_old'] = $totalNCRawOld; // Added for Blade
        }

        if ($totalWalletAddedUSD > 0.0001) {
            // Only relevant if it was for a sale made TODAY
            // But to avoid complexity, we can show it as custody if it's physical cash staying in drawer
            $totalsByCategory['(+) CUSTODIA (NC BILLETERA)'] = $totalWalletAddedUSD;
        }

        $salesSubtotal = 0;
        foreach ($totalsByCategory as $k => $v) {
            if (!str_contains(strtoupper($k), 'BILLETERA') && !str_contains(strtoupper($k), 'ANTIGUAS')) {
                $salesSubtotal += $v;
            }
        }
        
        $grandTotalIncomeUSD = $salesSubtotal + $totalWalletAddedUSD;


        // Adjust totalsByCurrency to show NET amounts (subtract returns TODAY per currency)
        foreach ($returns as $ret) {
            if (!$ret->sale) continue;
            $saleDate = \Carbon\Carbon::parse($ret->sale->created_at)->format('Y-m-d');
            if ($saleDate !== $reportDate) continue; 
            
            $saleCurrCode = $ret->sale->primary_currency_code ?? 'USD';
            if (isset($totalsByCurrency[$saleCurrCode])) {
                $totalsByCurrency[$saleCurrCode] -= $ret->total_returned;
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
            'totalsByCurrencyPhys' => $totalsByCurrencyPhys,
            'config' => $config,
            'user' => $user,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'groupBy' => $groupBy,
            'grandTotalIncomeUSD' => $grandTotalIncomeUSD,
            'grandTotalDivisa' => $summary['total_divisa'],
            'grandRawVed' => $grandRawVed,
            'grandRawCop' => $grandRawCop,
            'grandTotalNeto' => $grandTotalNeto,
            'grandTotalCredit' => $grandTotalCredit,
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
                ->whereNull('deletion_approved_at')
                ->select('id', 'total', 'cash', 'change', 'type', 'primary_exchange_rate', 'customer_id')
                ->get();

        $totalSales = $sales->sum(function($sale) use ($primaryRate) {
            $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
            $totalUSD = $sale->total / $saleRate;
            return $totalUSD * $primaryRate;
        });

        $saleIds = $sales->pluck('id');
        $paymentDetails = SalePaymentDetail::with(['zelleRecord', 'bankRecord'])->whereIn('sale_id', $saleIds)->get();
        
        $totalNCUSD = \App\Models\SaleReturn::whereBetween('created_at', [$dFrom, $dTo])
            ->where('status', 'approved')
            ->whereIn('sale_id', $saleIds) // ONLY NCs of visible sales
            ->get()
            ->sum(function($r) use ($primaryRate) {
                $rate = ($r->sale && $r->sale->primary_exchange_rate > 0) ? $r->sale->primary_exchange_rate : $primaryRate;
                return ($r->total_returned / $rate) * $primaryRate;
            });

        $totalSales = $totalSales - $totalNCUSD;

        $totalWalletAddedToday = \App\Models\SaleReturn::whereBetween('created_at', [$dFrom, $dTo])
            ->where('refund_method', 'wallet')
            ->where('status', 'approved')
            ->get() // ALL wallet additions (including ghost sales)
            ->sum(function($r) use ($primaryRate) {
                $rate = ($r->sale && $r->sale->primary_exchange_rate > 0) ? $r->sale->primary_exchange_rate : $primaryRate;
                return ($r->total_returned / $rate) * $primaryRate;
            });


        $totalWalletUsedUSD = $paymentDetails->where('payment_method', 'wallet')->sum('amount_in_primary_currency');
        
        $salesByCurrency = $this->aggregateSalesByCurrency($sales, $paymentDetails, $currencies);
        
        $totalCreditSales = $sales->where('type', 'credit')->sum(function($sale) use ($primaryRate) {
            $saleRate = $sale->primary_exchange_rate ?? $primaryRate;
            $totalUSD = $sale->total / $saleRate;
            return $totalUSD * $primaryRate;
        });

        $payments = Payment::with(['zelleRecord', 'bankRecord'])->whereBetween('created_at', [$dFrom, $dTo])
            ->when($user_id != 0, function ($qry) use ($user_id) {
                $qry->where('user_id', $user_id);
            })
            ->where('status', 'approved')
            ->select('id', 'pay_way', 'amount', 'bank', 'currency', 'exchange_rate', 'primary_exchange_rate', 'zelle_record_id', 'bank_record_id')
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

        // To keep it simple and consistent with DailySalesReport:
        $totalsByCategory = [];
        foreach($currencies as $c) { $totalsByCategory["EFECTIVO " . strtoupper($c->code)] = 0; }
        
        foreach($totalCashDetails as $code => $amt) {
             $totalsByCategory["EFECTIVO " . strtoupper($code)] = $amt;
        }
        
        // Final Segregation logic for the report: Subtract returns correctly BEFORE calculating totals
        $returnsForC = \App\Models\SaleReturn::whereBetween('created_at', [$dFrom, $dTo])
            ->where('status', 'approved')
            ->get();

        foreach ($returnsForC as $ret) {
            if (!$ret->sale) continue;
            $saleCurrCode = strtoupper($ret->sale->primary_currency_code ?? 'USD');
            $rt_rate = $ret->sale->primary_exchange_rate > 0 ? $ret->sale->primary_exchange_rate : 1;
            $retAmtRAW = $ret->total_returned; 
            $retAmtUSD = ($ret->total_returned / $rt_rate) * $primaryRate;
            $retMethod = strtolower($ret->refund_method ?? 'cash');

            $key = "EFECTIVO " . $saleCurrCode;
            if (isset($totalsByCategory[$key])) {
                $totalsByCategory[$key] -= $retAmtUSD;
            } else {
                $totalsByCategory['EFECTIVO USD'] = ($totalsByCategory['EFECTIVO USD'] ?? 0) - $retAmtUSD;
            }

            if ($retMethod !== 'wallet' && $retMethod !== 'debt_reduction') {
                if (isset($totalCashDetails[$saleCurrCode])) {
                    $totalCashDetails[$saleCurrCode] -= $retAmtRAW;
                }
            }
        }

        $salesSubtotal = 0;
        foreach ($totalsByCategory as $k => $v) {
            $currCode = str_replace('EFECTIVO ', '', $k);
            $salesSubtotal += $this->convertToPrimaryLocal($v, $currCode, $currencies, $primaryRate);
        }

        // Bank and Zelle subtotals
        $bankSubtotalUSD = 0;
        foreach($totalBankDetails as $bn => $currs) foreach($currs as $curr => $amt) $bankSubtotalUSD += $this->convertToPrimaryLocal($amt, $curr, $currencies, $primaryRate);
        $zelleSubtotalUSD = 0;
        foreach($totalZelleDetails as $s => $a) $zelleSubtotalUSD += $a; 

        $salesSubtotal += $bankSubtotalUSD + $zelleSubtotalUSD;

        if ($totalWalletAddedToday > 0.0001) {
            $totalsByCategory['BILLETERA (CUSTODIA HOY)'] = $totalWalletAddedToday;
        }

        $grandTotalIncomeUSD = $salesSubtotal + $totalWalletAddedToday;


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
             return ($amount / $rate) * $primaryRate;
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
            'totalsByCategory' => $totalsByCategory,
            'totalWalletAddedToday' => $totalWalletAddedToday,
            'totalWalletUsedToday' => $totalWalletUsedUSD,
            'grandTotalIncomeUSD' => $grandTotalIncomeUSD,
            'config' => $config,
            'symbol' => $symbol,
            'getLabel' => $getLabel,
            'convertToPrimary' => $convertToPrimary
        ])->setPaper('a4', 'portrait');


        return $pdf->stream("Corte_Caja_{$dateFrom}.pdf");
    }

    private function convertToPrimaryLocal($amount, $currencyCode, $currencies, $primaryRate) {
        if ($currencyCode == 'USD') return $amount * $primaryRate;
        $curr = $currencies->firstWhere('code', $currencyCode);
        $rate = ($curr && $curr->exchange_rate > 0) ? $curr->exchange_rate : 1;
        return ($amount / $rate) * $primaryRate;
    }


    private function aggregateSalesByCurrency($sales, $paymentDetails, $currencies)
    {
        $aggregated = ['cash' => [], 'nequi' => [], 'deposit' => [], 'zelle' => [], 'wallet' => []];
        $primaryCurrency = $currencies->firstWhere('is_primary', 1);
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'COP';
        $paymentsBySale = $paymentDetails->groupBy('sale_id');

        foreach ($sales as $sale) {
            if (isset($paymentsBySale[$sale->id])) {
                foreach ($paymentsBySale[$sale->id] as $paymentDetail) {
                    $currency = $paymentDetail->currency_code;
                    $bankName = $paymentDetail->bank_name;
                    $paymentMethod = $paymentDetail->payment_method ?? 'cash';
                    $category = match($paymentMethod) { 
                        'cash' => 'cash', 
                        'nequi' => 'nequi', 
                        'bank' => 'deposit', 
                        'zelle' => 'zelle', 
                        'wallet' => 'wallet',
                        default => 'cash' 
                    };
                    
                    if ($category == 'wallet') {
                        $aggregated['wallet'][$currency] = ($aggregated['wallet'][$currency] ?? 0) + $paymentDetail->amount;
                    } elseif ($category == 'deposit' && $bankName) {
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
                $category = match($sale->type) { 'cash', 'cash/nequi', 'mixed' => 'cash', 'nequi' => 'nequi', 'deposit', 'bank' => 'deposit', 'wallet' => 'wallet', default => null };
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
        $overdue_filter = $request->get('overdue_filter', 'all');

        // Security check matching Livewire component
        if (!auth()->user()->can('sales.view_all')) {
            $user_id = auth()->id();
        }

        $query = Sale::with(['customer', 'details', 'user', 'paymentDetails', 'payments', 'returns'])
            ->where('type', 'credit')
            ->whereNotIn('status', ['returned', 'voided', 'cancelled', 'anulated']);

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
        } else {
            // Default: Hide paid invoices for Accounts Receivable
            $query->where('status', '<>', 'paid');
        }

        if ($overdue_filter != 'all') {
            $query->where(function($q) use ($overdue_filter) {
                // Force the same date reference as PHP to avoid environment mismatches
                $today = \Carbon\Carbon::today()->format('Y-m-d');
                $sql = "DATEDIFF('$today', DATE_ADD(COALESCE(delivered_at, created_at), INTERVAL credit_days DAY))";
                if ($overdue_filter == 'overdue') {
                    $q->whereRaw("$sql > 0");
                } elseif ($overdue_filter == 'in_time') {
                    $q->whereRaw("$sql <= 0");
                }
            });
        }

        $sales = $query->orderBy('id', 'asc')->get();

        if ($sales->isEmpty()) {
            return response('No hay datos para generar el reporte.', 404);
        }

        $data = [];
        $grandTotalDebt = 0;

        foreach ($sales as $sale) {
            // Use the model's logic for consistency
            $daysOverdue = (int)$sale->days_overdue;
            $dueDate = Carbon::parse($sale->delivered_at ?? $sale->created_at)->addDays($sale->credit_days ?? 0);
            
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

            $balance = round($totalUSD - ($totalPaidUSD + $initialPaidUSD + $totalReturnsUSD), 4);
            $balance_before_nc = round($totalUSD - ($totalPaidUSD + $initialPaidUSD), 4);

            // Logic Fix: Skip if no debt and not specifically looking for paid ones
            if (($status == '0' || empty($status) || $status != 'paid') && $balance < 0.0001) continue;

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
 
             // Use the model's official logic for absolute consistency
             $daysOverdue = (int)$sale->days_overdue;
             
             // Calculate DueDate exactly like the model does
             $startDate = $sale->delivered_at ? \Carbon\Carbon::parse($sale->delivered_at) : \Carbon\Carbon::parse($sale->created_at);
             $dueDate = $startDate->copy()->addDays($sale->credit_days ?? 0);

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
                 'days' => $daysOverdue, 
                 'doc_no' => str_pad($sale->invoice_number ?? $sale->id, 8, '0', STR_PAD_LEFT),
                 'description' => 'Factnr:' .  ($sale->invoice_number ?? $sale->id) . ' Doc:' . str_pad($sale->invoice_number ?? $sale->id, 8, '0', STR_PAD_LEFT),
                 'customer_name' => $sale->customer->name ?? 'N/A',
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

        $pdf = Pdf::loadView('reports.accounts-receivable-pdf', compact('data', 'config', 'user', 'date', 'time', 'groupBy', 'grandTotalDebt', 'seller_name', 'overdue_filter'))
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
