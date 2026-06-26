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

        $dns2d = new \Milon\Barcode\DNS2D();
        $qrCodeUrl = route('audit.sheet.detail', ['sheet' => $sheet->id]);
        $qrCode = $dns2d->getBarcodePNG($qrCodeUrl, 'QRCODE', 4, 4);

        $pdf = Pdf::loadView('reports.collection-relationship-new-pdf', compact('sheet', 'payments', 'returns', 'config', 'user', 'date', 'totalsByCategory', 'totalsByCurrency', 'dateFrom', 'dateTo', 'qrCode'));
        
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

        $dFrom = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->startOfDay() : null;
        $dTo = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : null;

        $sales = \App\Models\Sale::with([
                'customer', 
                'details', 
                'user', 
                'paymentDetails' => function($q) use ($dFrom, $dTo) {
                    if ($dFrom && $dTo) $q->whereBetween('created_at', [$dFrom, $dTo]);
                    $q->with(['zelleRecord', 'bankRecord.bank']);
                },
                'changeDetails' => function($q) use ($dFrom, $dTo) {
                    if ($dFrom && $dTo) $q->whereBetween('created_at', [$dFrom, $dTo]);
                },
                'returns' => function($q) use ($dFrom, $dTo) {
                    if ($dFrom && $dTo) $q->whereBetween('created_at', [$dFrom, $dTo]);
                }
            ])
            ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                $q->whereBetween('created_at', [$dFrom, $dTo]);
            })
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
        $reportDate = $dFrom ? $dFrom->format('Y-m-d') : Carbon::now()->format('Y-m-d');

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

    public function generalSalesPdf(Request $request)
    {
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        $user_id = $request->get('user_id');
        $seller_id = $request->get('seller_id');
        $customer_id = $request->get('customer_id');
        $type = $request->get('type', 0);
        $searchFactura = $request->get('searchFactura');
        $driver_id = $request->get('driver_id');

        $dFrom = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $dTo = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        $sales = Sale::with(['customer', 'details', 'user', 'paymentDetails'])
            ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                $q->whereBetween('created_at', [$dFrom, $dTo]);
            })
            ->when(!empty(trim($searchFactura ?? '')), function($q) use ($searchFactura) {
                $searchValue = trim($searchFactura);
                $q->where(function($sub) use ($searchValue) {
                    $sub->where('id', 'like', "%{$searchValue}%")
                        ->orWhere('invoice_number', 'like', "%{$searchValue}%");
                });
            })
            ->when($user_id != null && $user_id != 0, function($q) use ($user_id) {
                $q->where('user_id', $user_id);
            })
            ->when($seller_id != null && $seller_id != 0, function($q) use ($seller_id) {
                $q->whereHas('customer', function($c) use ($seller_id) {
                    $c->where('seller_id', $seller_id);
                });
            })
            ->when($customer_id != null, function($q) use ($customer_id) {
                $q->where('customer_id', $customer_id);
            })
            ->when($type != 0, function($q) use ($type) {
                $q->where('type', $type);
            })
            ->when($driver_id !== null && $driver_id !== 'all', function($q) use ($driver_id) {
                if ($driver_id === 'with_route') {
                    $q->whereNotNull('driver_id');
                } elseif ($driver_id === 'without_route') {
                    $q->whereNull('driver_id');
                } else {
                    $q->where('driver_id', $driver_id);
                }
            })
            ->orderBy('id', 'desc')
            ->get();

        $selectedGroupsStr = $request->get('selectedGroups');
        $groupBy = $request->get('groupBy', 'none');
        if ($groupBy !== 'none' && $selectedGroupsStr !== null && $selectedGroupsStr !== '') {
            $selectedGroups = explode(',', $selectedGroupsStr);
            $sales = $sales->filter(function($sale) use ($groupBy, $selectedGroups) {
                $key = 'NA';
                if ($groupBy == 'customer_id') {
                    $key = $sale->customer_id ?? 'NA';
                } elseif ($groupBy == 'user_id') {
                    $key = $sale->user_id ?? 'NA';
                } elseif ($groupBy == 'seller_id') {
                    $key = $sale->customer?->seller_id ?? 'NA';
                } elseif ($groupBy == 'driver_id') {
                    $key = $sale->driver_id ?? 'NA';
                } elseif ($groupBy == 'date') {
                    $key = $sale->created_at->format('Y-m-d');
                }
                return in_array((string)$key, $selectedGroups);
            });
        }

        // Build filter info string for the PDF header
        $filterParts = [];
        if ($user_id && $user_id != 0) {
            $userName = User::find($user_id)?->name;
            if ($userName) $filterParts[] = "Usuario: {$userName}";
        }
        if ($seller_id && $seller_id != 0) {
            $sellerName = User::find($seller_id)?->name;
            if ($sellerName) $filterParts[] = "Vendedor: {$sellerName}";
        }
        if ($customer_id) {
            $customerName = \App\Models\Customer::find($customer_id)?->name;
            if ($customerName) $filterParts[] = "Cliente: {$customerName}";
        }
        if ($type != 0) {
            $filterParts[] = "Tipo: " . ($type == 'cash' ? 'Contado' : 'Crédito');
        }
        if ($driver_id && $driver_id !== 'all') {
            if ($driver_id === 'with_route') {
                $filterParts[] = "Chofer: Con Ruta Asignada";
            } elseif ($driver_id === 'without_route') {
                $filterParts[] = "Chofer: Sin Ruta Asignada";
            } else {
                $driverName = User::find($driver_id)?->name;
                if ($driverName) $filterParts[] = "Chofer: {$driverName}";
            }
        }
        $filterInfo = !empty($filterParts) ? implode(' | ', $filterParts) : null;

        $columns = json_decode($request->get('columns'), true) ?? [
            'folio' => true, 'cliente' => true, 'operador' => false, 'vendedor' => false, 'base' => true, 'porcentaje' => true,
            'comision' => true, 'flete' => true, 'recargo' => true, 'diferencial' => true, 'total' => true,
            'credito' => true, 'acuerdo' => false, 'articulos' => true, 'estatus' => true, 'tipo' => true, 'fecha' => true,
        ];

        // Build summary
        $summary = [
            'total_count' => $sales->count(),
            'total_items' => $sales->sum('items'),
            'total_base' => 0,
            'total_usd' => $sales->sum('total_usd'),
            'total_credit' => 0,
            'count_cash' => $sales->where('type', 'cash')->count(),
            'count_credit' => $sales->where('type', 'credit')->count(),
        ];

        // Calculate total base and credit
        $cutOffDate = \App\Services\ConfigurationService::getSequentialCutOffDate();
        foreach ($sales as $sale) {
            $base = $sale->base_amount > 0 ? floatval($sale->base_amount) : 0;
            $commPercent = $sale->resolved_commission_percent;
            $freightPercent = $sale->resolved_freight_percent;
            $diffPercent = $sale->resolved_exchange_diff_percent;
            $markupPercent = $sale->resolved_base_markup_percent;
            $isSequential = $sale->created_at >= $cutOffDate;

            if ($isSequential) {
                $surchargePercent = (((1 + ($commPercent + $freightPercent + $markupPercent) / 100) * (1 + $diffPercent / 100)) - 1) * 100;
            } else {
                $surchargePercent = $commPercent + $freightPercent + $diffPercent + $markupPercent;
            }

            if ($base == 0 && $sale->total_usd > 0) {
                if (!$isSequential) {
                    $base = $surchargePercent > 0 ? $sale->total_usd / (1 + ($surchargePercent / 100)) : $sale->total_usd;
                } else {
                    $base = ($sale->total_usd / (1 + ($diffPercent / 100))) / (1 + (($commPercent + $freightPercent + $markupPercent) / 100));
                }
            }

            // Guard: fix if base stored in local currency
            if ($base > ($sale->total_usd * 1.5) && $sale->primary_exchange_rate > 1) {
                $base = $base / $sale->primary_exchange_rate;
            }

            $summary['total_base'] += $base;

            // Credit calculation
            $totalPaidUSD = 0;
            foreach ($sale->paymentDetails as $payment) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                $totalPaidUSD += ($payment->amount / $rate);
            }
            if ($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $totalPaidUSD += ($sale->cash / $rate);
            }
            if ($sale->status != 'paid' && $sale->status != 'returned') {
                $summary['total_credit'] += max(0, $sale->total_usd - $totalPaidUSD);
            }
        }

        $config = Configuration::first();
        $user = auth()->user();

        $groupBy = $request->get('groupBy', 'none');
        $isGrouped = $groupBy !== 'none';
        $groupedSales = null;

        if ($isGrouped) {
            $groupedData = [];
            foreach ($sales as $sale) {
                $key = ''; 
                $name = '';
                
                if ($groupBy == 'customer_id') {
                    $key = $sale->customer_id ?? 'NA'; 
                    $name = $sale->customer?->name ?? 'SIN CLIENTE';
                } elseif ($groupBy == 'user_id') {
                    $key = $sale->user_id ?? 'NA'; 
                    $name = $sale->user?->name ?? 'SIN OPERADOR';
                } elseif ($groupBy == 'seller_id') {
                    $key = $sale->customer?->seller_id ?? 'NA';
                    $name = $sale->customer?->seller?->name ?? 'SIN VENDEDOR';
                } elseif ($groupBy == 'driver_id') {
                    $key = $sale->driver_id ?? 'NA';
                    $name = $sale->driver?->name ?? 'SIN CHOFER';
                } elseif ($groupBy == 'date') {
                    $key = $sale->created_at->format('Y-m-d'); 
                    $name = $sale->created_at->format('d/m/Y');
                }

                if (!isset($groupedData[$key])) { 
                    $groupedData[$key] = ['name' => $name, 'sales' => [], 'total_usd' => 0]; 
                }
                $groupedData[$key]['sales'][] = $sale;
                $groupedData[$key]['total_usd'] += $sale->total_usd;
            }
            
            if ($groupBy == 'date') {
                krsort($groupedData);
            } else {
                uasort($groupedData, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
            }
            $groupedSales = $groupedData;
        }

        $pdf = Pdf::loadView('reports.general-sales-report-pdf', [
            'sales' => $sales,
            'groupedSales' => $groupedSales,
            'isGrouped' => $isGrouped,
            'summary' => $summary,
            'config' => $config,
            'user' => $user,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'filterInfo' => $filterInfo,
            'columns' => $columns,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Reporte_Ventas_General.pdf');
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
        $selected_ids = $request->get('selected_ids') ? explode(',', $request->get('selected_ids')) : null;

        $sales = Sale::with(['customer.seller', 'driver', 'sellerConfig.user', 'paymentDetails'])
            ->whereNotNull('driver_id')
            ->whereNotIn('status', ['returned', 'voided', 'cancelled', 'anulated'])
            ->when($selected_ids, function($q) use ($selected_ids) {
                $q->whereIn('id', $selected_ids);
            })
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
            $commPercent = $sale->resolved_commission_percent;
            $freightPercent = $sale->resolved_freight_percent;
            $diffPercent = $sale->resolved_exchange_diff_percent;
            $markupPercent = $sale->resolved_base_markup_percent;
            $incPercent = $commPercent + $freightPercent + $diffPercent + $markupPercent;
            
            if ($sale->created_at >= \App\Services\ConfigurationService::getSequentialCutOffDate()) {
                $baseAmount = ($totalFac / (1 + $diffPercent / 100)) / (1 + ($commPercent + $freightPercent + $markupPercent) / 100);
                $commAmt = $baseAmount * ($commPercent / 100);
                $freightAmt = $baseAmount * ($freightPercent / 100);
                $markupAmt = $baseAmount * ($markupPercent / 100);
                $intermediateTotal = $baseAmount + $commAmt + $freightAmt + $markupAmt;
                $diffAmt = $intermediateTotal * ($diffPercent / 100);
            } else {
                $baseAmount = $totalFac / (1 + ($incPercent / 100));
                $commAmt = $baseAmount * ($commPercent / 100);
                $freightAmt = $baseAmount * ($freightPercent / 100);
                $diffAmt = $baseAmount * ($diffPercent / 100);
            }

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
        $selected_ids = $request->get('selected_ids') ? explode(',', $request->get('selected_ids')) : null;

        $sales = Sale::with(['customer', 'driver', 'deliveryCollections.payments.currency'])
            ->whereNotNull('driver_id')
            ->whereNotIn('status', ['returned', 'voided', 'cancelled', 'anulated'])
            ->when($selected_ids, function($q) use ($selected_ids) {
                $q->whereIn('id', $selected_ids);
            })
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

        $sheets = \App\Models\CollectionSheet::whereBetween('opened_at', [$dFrom, $dTo])->get();
        $sheetIds = $sheets->pluck('id');

        $payments = Payment::with(['zelleRecord', 'bankRecord'])
            ->whereIn('collection_sheet_id', $sheetIds)
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

    public function cashCountDetailedPdf(Request $request)
    {
        $dateFrom = $request->get('dateFrom') ?: Carbon::today()->format('Y/m/d');
        $dateTo = $request->get('dateTo') ?: Carbon::today()->format('Y/m/d');
        $user_id = $request->get('user_id', 0);
        $includeCash = $request->get('includeCash', 1) == 1;
        $unify = $request->get('unify', 0) == 1;

        $dFrom = Carbon::parse($dateFrom)->startOfDay();
        $dTo = Carbon::parse($dateTo)->endOfDay();

        $currencies = Currency::orderBy('is_primary', 'desc')->get();
        $primaryCurrency = $currencies->firstWhere('is_primary', 1);
        $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'COP';
        $symbol = $primaryCurrency ? $primaryCurrency->symbol : '$';

        $config = Configuration::first();
        $user_name = $user_id == 0 ? 'Todos los usuarios' : User::find($user_id)->name;

        // 1. Query Sales matching filters
        $sales = Sale::whereBetween('created_at', [$dFrom, $dTo])
                ->when($user_id != 0, function ($qry) use ($user_id) {
                    $qry->where('user_id', $user_id);
                })
                ->where('status', '<>', 'returned')
                ->whereNull('deletion_approved_at')
                ->get();

        $saleIds = $sales->pluck('id');

        // Get detailed payments for daily sales
        $salePaymentDetails = SalePaymentDetail::with(['sale', 'sale.customer', 'zelleRecord', 'bankRecord'])
            ->whereIn('sale_id', $saleIds)
            ->whereBetween('created_at', [$dFrom, $dTo])
            ->get();

        $sheets = \App\Models\CollectionSheet::whereBetween('opened_at', [$dFrom, $dTo])->get();
        $sheetIds = $sheets->pluck('id');

        // 2. Query credit payments
        $creditPayments = Payment::with(['sale.customer', 'zelleRecord', 'bankRecord'])
            ->whereIn('collection_sheet_id', $sheetIds)
            ->when($user_id != 0, function ($qry) use ($user_id) {
                $qry->where('user_id', $user_id);
            })
            ->where('status', 'approved')
            ->get();

        // Helpers to process dates, bank names, and payment types
        $getVoucherDate = function($payment, $method) {
            if ($method === 'zelle' && $payment->zelleRecord) {
                return Carbon::parse($payment->zelleRecord->zelle_date ?? $payment->payment_date ?? $payment->created_at)->format('d/m/Y');
            }
            if (($method === 'bank' || $method === 'deposit') && $payment->bankRecord) {
                return Carbon::parse($payment->bankRecord->payment_date ?? $payment->payment_date ?? $payment->created_at)->format('d/m/Y');
            }
            $date = $payment->payment_date ?? $payment->created_at;
            return Carbon::parse($date)->format('d/m/Y');
        };

        $getCreditPaymentStatus = function($payment) {
            if (!$payment->sale) return 'Pago de Deuda';
            $sale = $payment->sale;
            if ($sale->debt <= 0.01) {
                $otherPaysCount = $sale->payments->where('status', 'approved')->where('id', '!=', $payment->id)->count();
                return $otherPaysCount > 0 ? 'Cancelación Deuda' : 'Pago Completo (Cred)';
            }
            return 'Abono Parcial';
        };

        $configuredBanks = \App\Models\Bank::all();
        $getBankAccountSuffix = function($bankName, $recordAccountNumber = null) use ($configuredBanks) {
            $accNum = null;
            
            // 1. Try to use record account number first (only if it contains at least 6 digits)
            if (!empty($recordAccountNumber)) {
                $cleanRec = preg_replace('/[^0-9]/', '', $recordAccountNumber);
                if (strlen($cleanRec) >= 6) {
                    $accNum = $recordAccountNumber;
                }
            }
            
            // 2. If record account number is not valid, look up from configured banks using robust normalization
            if (empty($accNum) && !empty($bankName)) {
                $normalizedInput = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(trim($bankName)));
                
                $match = $configuredBanks->first(function($b) use ($normalizedInput) {
                    $normalizedBankName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(trim($b->name)));
                    return $normalizedBankName === $normalizedInput;
                });
                
                if ($match && !empty($match->account_number)) {
                    $accNum = $match->account_number;
                }
            }
            
            // 3. Format and return suffix
            if (!empty($accNum)) {
                $cleanAcc = preg_replace('/[^0-9]/', '', $accNum);
                if (strlen($cleanAcc) >= 6) {
                    return ' (*' . substr($cleanAcc, -6) . ')';
                }
                return ' (*' . $accNum . ')';
            }
            return '';
        };

        // Prepare Currency aggregations for Cash if includeCash is ON
        $cashDetails = [
            'sales' => [],
            'credits' => [],
            'unified' => []
        ];

        if ($includeCash) {
            // Process cash from sales
            foreach($salePaymentDetails->where('payment_method', 'cash') as $pd) {
                $curr = $pd->currency_code;
                $cashDetails['sales'][$curr] = ($cashDetails['sales'][$curr] ?? 0) + $pd->amount;
                $cashDetails['unified'][$curr] = ($cashDetails['unified'][$curr] ?? 0) + $pd->amount;
            }
            // Process cash from credits
            foreach($creditPayments->where('pay_way', 'cash') as $p) {
                $curr = $p->currency ?? $primaryCode;
                $cashDetails['credits'][$curr] = ($cashDetails['credits'][$curr] ?? 0) + $p->amount;
                $cashDetails['unified'][$curr] = ($cashDetails['unified'][$curr] ?? 0) + $p->amount;
            }

            // Deduct returns for net cash flow to match summary totals
            $returns = \App\Models\SaleReturn::whereBetween('created_at', [$dFrom, $dTo])
                ->where('status', 'approved')
                ->whereIn('sale_id', $saleIds)
                ->get();
            foreach ($returns as $ret) {
                if ($ret->refund_method !== 'wallet' && $ret->refund_method !== 'debt_reduction') {
                    $curr = $ret->sale->primary_currency_code ?? $primaryCode;
                    $cashDetails['sales'][$curr] = ($cashDetails['sales'][$curr] ?? 0) - $ret->total_returned;
                    $cashDetails['unified'][$curr] = ($cashDetails['unified'][$curr] ?? 0) - $ret->total_returned;
                }
            }
        }

        // Structure Detailed Digital Payments (Bancos / Zelle)
        $digitalPayments = [
            'sales' => ['bank' => [], 'zelle' => []],
            'credits' => ['bank' => [], 'zelle' => []],
            'unified' => ['bank' => [], 'zelle' => []]
        ];

        // Process Sales Digital Payments
        foreach($salePaymentDetails->whereIn('payment_method', ['bank', 'deposit', 'zelle']) as $pd) {
            $method = $pd->payment_method === 'zelle' ? 'zelle' : 'bank';
            $bankName = $pd->bank_name ?? 'Banco / Otros';
            $curr = $pd->currency_code;
            $voucherDate = $getVoucherDate($pd, $pd->payment_method);
            
            $suffix = $getBankAccountSuffix($bankName, $pd->account_number);
            $bankKey = $bankName . $suffix;

            $item = [
                'date' => $voucherDate,
                'raw_date' => $pd->zelleRecord->zelle_date ?? $pd->bankRecord->payment_date ?? $pd->created_at,
                'origin' => 'VENTA',
                'ref' => $pd->reference_number ?? ($pd->zelleRecord->reference ?? 'N/A'),
                'invoice' => $pd->sale->invoice_number ?? $pd->sale->id,
                'customer' => $pd->sale->customer->name ?? 'Consumidor Final',
                'type' => 'Pago Venta',
                'amount' => $pd->amount,
                'currency' => $curr,
                'equiv_usd' => $pd->amount / ($pd->exchange_rate > 0 ? $pd->exchange_rate : 1),
                'zelle_sender' => $pd->zelleRecord->sender_name ?? 'N/A',
                'zelle_total' => $pd->zelleRecord->amount ?? $pd->amount
            ];

            if ($method === 'bank') {
                $digitalPayments['sales']['bank'][$bankKey][$curr][] = $item;
                $digitalPayments['unified']['bank'][$bankKey][$curr][] = $item;
            } else {
                $digitalPayments['sales']['zelle'][] = $item;
                $digitalPayments['unified']['zelle'][] = $item;
            }
        }

        // Process Credit Digital Payments
        foreach($creditPayments->whereIn('pay_way', ['bank', 'deposit', 'zelle']) as $p) {
            $method = $p->pay_way === 'zelle' ? 'zelle' : 'bank';
            $bankName = $p->bank ?? 'Banco / Otros';
            $curr = $p->currency ?? $primaryCode;
            $voucherDate = $getVoucherDate($p, $p->pay_way);
            $payStatus = $getCreditPaymentStatus($p);

            $suffix = $getBankAccountSuffix($bankName, $p->account_number);
            $bankKey = $bankName . $suffix;

            $item = [
                'date' => $voucherDate,
                'raw_date' => $p->zelleRecord->zelle_date ?? $p->bankRecord->payment_date ?? $p->created_at,
                'origin' => 'CRÉDITO',
                'ref' => $p->deposit_number ?? ($p->zelleRecord->reference ?? 'N/A'),
                'invoice' => $p->sale->invoice_number ?? $p->sale->id ?? 'N/A',
                'customer' => $p->sale->customer->name ?? 'Cliente Crédito',
                'type' => $payStatus,
                'amount' => $p->amount,
                'currency' => $curr,
                'equiv_usd' => $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1),
                'zelle_sender' => $p->zelleRecord->sender_name ?? 'N/A',
                'zelle_total' => $p->zelleRecord->amount ?? $p->amount
            ];

            if ($method === 'bank') {
                $digitalPayments['credits']['bank'][$bankKey][$curr][] = $item;
                $digitalPayments['unified']['bank'][$bankKey][$curr][] = $item;
            } else {
                $digitalPayments['credits']['zelle'][] = $item;
                $digitalPayments['unified']['zelle'][] = $item;
            }
        }

        // Sort inside each group chronologically by date
        $sortItems = function(&$items) {
            usort($items, function($a, $b) {
                return strtotime($a['raw_date']) <=> strtotime($b['raw_date']);
            });
        };

        // Sort Sales Bank Groups
        foreach($digitalPayments['sales']['bank'] as $bank => &$currenciesInBank) {
            foreach($currenciesInBank as $c => &$items) {
                $sortItems($items);
            }
        }
        unset($currenciesInBank);
        unset($items);
        $sortItems($digitalPayments['sales']['zelle']);

        // Sort Credits Bank Groups
        foreach($digitalPayments['credits']['bank'] as $bank => &$currenciesInBank) {
            foreach($currenciesInBank as $c => &$items) {
                $sortItems($items);
            }
        }
        unset($currenciesInBank);
        unset($items);
        $sortItems($digitalPayments['credits']['zelle']);

        // Sort Unified Bank Groups
        foreach($digitalPayments['unified']['bank'] as $bank => &$currenciesInBank) {
            foreach($currenciesInBank as $c => &$items) {
                $sortItems($items);
            }
        }
        unset($currenciesInBank);
        unset($items);
        $sortItems($digitalPayments['unified']['zelle']);

        // Calculate Grand Totals
        $grandTotalUSD = 0;
        
        // Add Cash Totals in USD
        if ($includeCash) {
            foreach($cashDetails['unified'] as $curr => $amt) {
                $currObj = $currencies->firstWhere('code', $curr);
                $rate = $currObj ? $currObj->exchange_rate : 1;
                $grandTotalUSD += $amt / ($rate > 0 ? $rate : 1);
            }
        }

        // Add Bank Totals in USD
        foreach($digitalPayments['unified']['bank'] as $bank => $currenciesInBank) {
            foreach($currenciesInBank as $c => $items) {
                foreach($items as $item) {
                    $grandTotalUSD += $item['equiv_usd'];
                }
            }
        }

        // Add Zelle Totals in USD
        foreach($digitalPayments['unified']['zelle'] as $item) {
            $grandTotalUSD += $item['equiv_usd'];
        }

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

        $pdf = Pdf::loadView('reports.cash-count-detailed-pdf', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'user_name' => $user_name,
            'includeCash' => $includeCash,
            'unify' => $unify,
            'cashDetails' => $cashDetails,
            'digitalPayments' => $digitalPayments,
            'grandTotalIncomeUSD' => $grandTotalUSD,
            'config' => $config,
            'symbol' => $symbol,
            'getLabel' => $getLabel,
            'convertToPrimary' => $convertToPrimary
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("Corte_Caja_Detallado_{$dateFrom}.pdf");
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
        $selected_ids = $request->get('selected_ids') ? explode(',', $request->get('selected_ids')) : [];
        $selected_warehouses = json_decode($request->get('warehouses'), true) ?? [];
        $show_total = $request->get('show_total', true);

        $products = \App\Models\Product::where('status', 'available')
            ->when(!empty($selected_ids), function ($q) use ($selected_ids) {
                $q->whereIn('id', $selected_ids);
            })
            ->when(empty($selected_ids) && $supplier_id && $supplier_id !== 'all', function ($q) use ($supplier_id) {
                $q->where('supplier_id', $supplier_id);
            })
            ->when(empty($selected_ids) && $category_id && $category_id !== 'all', function ($q) use ($category_id) {
                $q->where('category_id', $category_id);
            })
            ->when(empty($selected_ids) && $search, function ($query) use ($search) {
                $tokens = explode(' ', trim($search));
                foreach ($tokens as $token) {
                    if (!empty($token)) {
                        $query->where(function($q) use ($token) {
                            $q->where('name', 'like', "%{$token}%")
                              ->orWhere('sku', 'like', "%{$token}%")
                              ->orWhereHas('category', function ($subQuery) use ($token) {
                                  $subQuery->where('name', 'like', "%{$token}%");
                              });
                        });
                    }
                }
            })
            ->with(['category', 'supplier', 'warehouses'])
            ->orderBy('name')
            ->get();

        $config = Configuration::first();
        $user = auth()->user();

        $warehouses = \App\Models\Warehouse::whereIn('id', $selected_warehouses)->orderBy('name')->get();

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
            'totals' => $totals,
            'warehouses' => $warehouses,
            'show_total' => $show_total
        ])->setPaper('a4', count($selected_warehouses) > 2 ? 'landscape' : 'portrait');

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

    public function customerStatementPdf(Request $request)
    {
        $customerId = $request->get('customer_id');
        $dateFrom = $request->get('dateFrom') ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->get('dateTo') ?: Carbon::now()->format('Y-m-d');
        $search = $request->get('referenceSearch');

        if (!auth()->user()->can('customer_statement.index')) {
            abort(403, 'No tienes permiso para acceder a este reporte.');
        }

        $customer = \App\Models\Customer::findOrFail($customerId);

        // Privacy check
        if (!auth()->user()->can('customer_statement.view_all')) {
            if (auth()->user()->can('customer_statement.view_own')) {
                if (!in_array($customer->seller_id, auth()->user()->getSharedSellerIds())) {
                    abort(403, 'No tiene permiso para ver el estado de cuenta de este cliente.');
                }
            } else {
                abort(403, 'No tiene permisos suficientes para consultar estados de cuenta.');
            }
        }

        $config = \App\Models\Configuration::first();
        $user = auth()->user();

        $from = $dateFrom . ' 00:00:00';
        $to = $dateTo . ' 23:59:59';

        // Bindings for security
        $cid = (int)$customerId;

        $ledger = DB::table(DB::raw("(
            SELECT 
                created_at as t_date, 
                CAST(COALESCE(invoice_number, id) AS CHAR) as reference, 
                'VENTA' as concept, 
                total as debit_native,
                primary_exchange_rate as rate,
                (CASE WHEN total_usd > 0 THEN total_usd ELSE total / (CASE WHEN primary_exchange_rate > 0 THEN primary_exchange_rate ELSE 1 END) END) as debit_usd,
                0 as credit_usd,
                'SALE' as type
            FROM sales 
            WHERE customer_id = $cid AND status NOT IN ('voided', 'returned')
            AND created_at BETWEEN '$from' AND '$to'
            " . ($search ? "AND (id LIKE '%$search%' OR invoice_number LIKE '%$search%')" : "") . "

            UNION ALL

            SELECT 
                payment_date as t_date, 
                CAST(p.id AS CHAR) as reference, 
                CONCAT(
                    'PAGO ', UPPER(p.pay_way), 
                    COALESCE(CONCAT(' ', UPPER(p.bank)), ''),
                    COALESCE(CONCAT(' #', p.deposit_number), ''),
                    ' (', p.currency, ' ', FORMAT(p.amount, 2), ' @ ', FORMAT(p.exchange_rate, 2), ')',
                    ' ($', FORMAT(p.amount / (CASE WHEN p.exchange_rate > 0 THEN p.exchange_rate ELSE 1 END), 2), ')',
                    CASE WHEN p.discount_applied > 0 THEN 
                        CONCAT(' + DESC. ', 
                            CASE 
                                WHEN p.rule_type = 'early_payment' OR p.discount_tag LIKE '%Pronto%' THEN 'PP'
                                WHEN p.rule_type = 'usd_payment' OR p.discount_tag LIKE '%Divisa%' OR p.discount_tag LIKE '%USD%' THEN 'PD'
                                ELSE UPPER(COALESCE(p.discount_tag, 'DESC'))
                            END,
                            '($', FORMAT(p.discount_applied, 2), ')'
                        ) 
                    ELSE '' END,
                    ' [FACT. #', COALESCE(s.invoice_number, CAST(s.id AS CHAR)), ']'
                ) as concept, 
                p.amount as debit_native,
                p.exchange_rate as rate,
                0 as debit_usd,
                (
                    (p.amount / (CASE WHEN p.exchange_rate > 0 THEN p.exchange_rate ELSE 1 END)) 
                    + (CASE WHEN p.rule_type = 'overdue' THEN -1 ELSE 1 END * COALESCE(p.discount_applied, 0))
                ) as credit_usd,
                'PAYMENT' as type
            FROM payments p
            JOIN sales s ON p.sale_id = s.id
            WHERE s.customer_id = $cid AND p.status = 'approved'
            AND payment_date BETWEEN '$from' AND '$to'
            " . ($search ? "AND (p.id LIKE '%$search%' OR s.invoice_number LIKE '%$search%' OR s.id LIKE '%$search%')" : "") . "

            UNION ALL

            SELECT 
                r.created_at as t_date, 
                CAST(r.id AS CHAR) as reference, 
                CONCAT('DEVOLUCION [FACT. #', COALESCE(s.invoice_number, CAST(s.id AS CHAR)), '] ', COALESCE(r.reason, '')) as concept, 
                0 as debit_native,
                0 as rate,
                0 as debit_usd,
                r.total_returned as credit_usd,
                'RETURN' as type
            FROM sale_returns r
            JOIN sales s ON r.sale_id = s.id
            WHERE r.customer_id = $cid AND r.status = 'approved'
            AND r.created_at BETWEEN '$from' AND '$to'
            " . ($search ? "AND (r.id LIKE '%$search%' OR s.invoice_number LIKE '%$search%' OR s.id LIKE '%$search%')" : "") . "
        ) as combined"))->orderBy('t_date', 'asc')->get();

        $totals = [
            'totalSales' => $ledger->where('type', 'SALE')->sum('debit_usd'),
            'totalPayments' => $ledger->where('type', 'PAYMENT')->sum('credit_usd'),
            'totalReturns' => $ledger->where('type', 'RETURN')->sum('credit_usd'),
            'balance' => $ledger->sum('debit_usd') - $ledger->sum('credit_usd')
        ];

        $pdf = Pdf::loadView('reports.customer-statement-detailed-pdf', compact('customer', 'ledger', 'config', 'user', 'dateFrom', 'dateTo', 'totals'));
        
        return $pdf->stream('Estado_Cuenta_' . $customer->name . '.pdf');
    }

    public function customerPaymentRelationshipPdf(Request $request)
    {
        // dd($request->all());
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        $customer_id = $request->get('customer_id');
        $invoiceFrom = $request->get('invoice_from');
        $invoiceTo = $request->get('invoice_to');
        $sellerIdSelection = $request->get('seller_id');
        $operatorIdSelection = $request->get('operator_id');
        $currencySelection = $request->get('currency');

        $query = Payment::query()->with(['sale.customer', 'zelleRecord', 'bankRecord.bank'])->where('status', 'approved');

        if ($dateFrom) {
            $query->where('payment_date', '>=', Carbon::parse($dateFrom)->startOfDay());
        }
        if ($dateTo) {
            $query->where('payment_date', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        if ($customer_id) {
            $query->whereHas('sale', function($q) use ($customer_id) {
                $q->where('customer_id', $customer_id);
            });
        }

        if ($invoiceFrom && $invoiceTo) {
            $invFrom = 0; $invTo = 0;
            if (is_numeric($invoiceFrom)) $invFrom = (int)$invoiceFrom;
            elseif (preg_match('/^[Ff]0*([1-9][0-9]*)$/', $invoiceFrom, $matches)) $invFrom = (int)$matches[1];
            
            if (is_numeric($invoiceTo)) $invTo = (int)$invoiceTo;
            elseif (preg_match('/^[Ff]0*([1-9][0-9]*)$/', $invoiceTo, $matches)) $invTo = (int)$matches[1];

            if ($invFrom > 0 && $invTo > 0) {
                $query->whereHas('sale', function($q) use ($invFrom, $invTo) {
                    $q->whereBetween('id', [$invFrom, $invTo]);
                });
            }
        }

        if ($sellerIdSelection != 'all' && $sellerIdSelection != 0) {
            $query->whereHas('sale.customer', function($q) use ($sellerIdSelection) {
                $q->where('seller_id', $sellerIdSelection);
            });
        }

        if ($operatorIdSelection != 'all' && $operatorIdSelection != 0) {
            $query->where('user_id', $operatorIdSelection);
        }

        if ($currencySelection != 'all' && !empty($currencySelection)) {
            $query->where('currency', $currencySelection);
        }

        $payments = $query->get();

        $customerIds = $payments->pluck('sale.customer_id')->unique();
        $returns = collect();
        if ($customerIds->isNotEmpty()) {
            $returns = SaleReturn::whereIn('customer_id', $customerIds)
                ->where('status', 'approved')
                ->whereBetween('created_at', [
                    Carbon::parse($dateFrom)->startOfDay(),
                    Carbon::parse($dateTo)->endOfDay()
                ])
                ->with(['sale.customer'])
                ->get();
        }

        $activity = collect();
        $saleGroups = $payments->groupBy('sale_id');
        
        foreach($saleGroups as $saleId => $salePayments) {
            $cashPayments = $salePayments->where('pay_way', 'cash');
            $otherPayments = $salePayments->where('pay_way', '!=', 'cash');

            if ($cashPayments->count() > 0) {
                $p = $cashPayments->first();
                $totalUsd = 0;
                $descriptions = [];
                foreach($cashPayments as $cp) {
                    $rate = $cp->exchange_rate > 0 ? $cp->exchange_rate : 1;
                    $usdEquivalent = $cp->amount / $rate;
                    $totalUsd += $usdEquivalent;
                    $descriptions[] = "[(Tasa: " . number_format($rate, 4) . " | (" . number_format($cp->amount, 4) . " " . $cp->currency . ") = $" . number_format($usdEquivalent, 4) . "]";
                }

                $activity->push([
                    'type' => 'Pago',
                    'sale_id' => $p->sale_id,
                    'customer_id' => $p->sale->customer_id,
                    'customer_name' => $p->sale->customer->name,
                    'customer_doc' => $p->sale->customer->taxpayer_id,
                    'date_pay' => Carbon::parse($p->payment_date),
                    'date_emit' => Carbon::parse($p->sale->created_at),
                    'days' => $this->internalCalculateDays($p->sale, $p->payment_date),
                    'doc_number' => $p->sale->invoice_number ?? $p->sale->id,
                    'description' => "CASH " . implode("; ", $descriptions),
                    'monto' => $totalUsd,
                    'ingreso' => $totalUsd
                ]);
            }

            foreach($otherPayments as $p) {
                $methodStr = strtoupper($p->pay_way);
                if ($p->pay_way == 'zelle' && $p->zelleRecord) {
                    $methodStr .= " (Ref: {$p->zelleRecord->reference})";
                } elseif (($p->pay_way == 'bank' || $p->pay_way == 'deposit') && $p->bank) {
                    $methodStr .= ": " . ($p->deposit_number ? "{$p->bank}: {$p->deposit_number}" : "{$p->bank}");
                }

                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                $usdEquivalent = $p->amount / $rate;
                $description = "{$methodStr} [(Tasa: " . number_format($rate, 4) . ") | (" . number_format($p->amount, 4) . " " . $p->currency . ") = $" . number_format($usdEquivalent, 4) . "]";

                if ($p->discount_applied > 0) {
                    $description .= " [Desc: $" . number_format($p->discount_applied, 2) . "]";
                }

                $activity->push([
                    'type' => 'Pago',
                    'sale_id' => $p->sale_id,
                    'customer_id' => $p->sale->customer_id,
                    'customer_name' => $p->sale->customer->name,
                    'customer_doc' => $p->sale->customer->taxpayer_id,
                    'date_pay' => Carbon::parse($p->payment_date),
                    'date_emit' => Carbon::parse($p->sale->created_at),
                    'days' => $this->internalCalculateDays($p->sale, $p->payment_date),
                    'doc_number' => $p->sale->invoice_number ?? $p->sale->id,
                    'description' => $description,
                    'monto' => $usdEquivalent,
                    'ingreso' => ($p->pay_way == 'advance' || $p->pay_way == 'adelanto') ? 0 : $usdEquivalent
                ]);
            }
        }

        foreach($returns as $r) {
            $amtUsd = $r->total_returned / ($r->sale->primary_exchange_rate > 0 ? $r->sale->primary_exchange_rate : 1);
            $activity->push([
                'type' => 'N/C',
                'sale_id' => $r->sale_id,
                'customer_id' => $r->customer_id,
                'customer_name' => $r->customer->name,
                'customer_doc' => $r->customer->taxpayer_id,
                'date_pay' => Carbon::parse($r->created_at),
                'date_emit' => Carbon::parse($r->sale->created_at),
                'days' => 0,
                'doc_number' => $r->sale->invoice_number ?? $r->sale->id,
                'description' => "N/C #{$r->id}: " . ($r->reason ?? 'Devolución'),
                'monto' => $amtUsd,
                'ingreso' => 0
            ]);
        }

        $grouped = $activity->sortBy([
            ['customer_name', 'asc'],
            ['date_emit', 'asc'],
            ['sale_id', 'asc'],
            ['date_pay', 'asc']
        ])->groupBy(['customer_id', 'sale_id']);
        
        $summary = $this->internalCalculateSummary($payments);
        $totalsByCurrency = [];
        foreach ($payments->groupBy('currency') as $curr => $group) {
            $totalsByCurrency[$curr] = $group->sum('amount');
        }

        $config = Configuration::first();
        $user = auth()->user();
        $totalMonto = $activity->sum('monto');
        $totalIngreso = $activity->sum('ingreso');

        $pdf = Pdf::loadView('reports.customer-payment-relationship-pdf', [
            'grouped' => $grouped,
            'summary' => $summary,
            'totalsByCurrency' => $totalsByCurrency,
            'config' => $config,
            'user' => $user,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalMonto' => $totalMonto,
            'totalIngreso' => $totalIngreso,
            'customer_id' => $customer_id,
            'invoice_from' => $invoiceFrom,
            'invoice_to' => $invoiceTo,
            'seller_id' => $sellerIdSelection,
            'operator_id' => $operatorIdSelection,
            'currency' => $currencySelection
        ])->setPaper('a4', 'landscape');

        if ($request->has('download')) {
            return $pdf->download('Relacion_Cobros_Cliente_' . now()->format('YmdHis') . '.pdf');
        }

        return $pdf->stream('Relacion_Cobros_Cliente.pdf');
    }

    private function internalCalculateDays($sale, $paymentDate)
    {
        $dateEmit = Carbon::parse($sale->created_at);
        $datePay = Carbon::parse($paymentDate);
        $creditDays = $sale->credit_days ?? 0;
        $dueDate = $dateEmit->copy()->addDays($creditDays);
        return $dueDate->diffInDays($datePay, false);
    }

    private function internalCalculateSummary($payments)
    {
        $summary = [];
        $currencies = Currency::all();
        $banks = Bank::orderBy('sort')->get();
        $knownBanks = $banks->pluck('name')->toArray();

        foreach ($currencies as $currency) {
            $cashPays = $payments->where('pay_way', 'cash')->where('currency', $currency->code);
            $amt = $cashPays->sum('amount');
            if ($amt > 0) {
                $equiv = $cashPays->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); });
                $summary[] = ['name' => "Efectivo {$currency->code}", 'amount' => $amt, 'equiv' => $equiv];
            }
        }

        foreach ($banks as $bank) {
            $bPays = $payments->whereIn('pay_way', ['bank', 'deposit'])->where('bank', $bank->name);
            foreach ($bPays->groupBy('currency') as $curr => $group) {
                $amt = $group->sum('amount');
                $equiv = $group->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); });
                $summary[] = ['name' => strtoupper($bank->name) . " ({$curr})", 'amount' => $amt, 'equiv' => $equiv];
            }
        }

        $otherBanks = $payments->whereIn('pay_way', ['bank', 'deposit'])->whereNotIn('bank', $knownBanks);
        foreach ($otherBanks->groupBy(['bank', 'currency']) as $bankName => $currenciesInBank) {
            foreach($currenciesInBank as $curr => $group) {
                $amt = $group->sum('amount');
                $equiv = $group->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); });
                $summary[] = ['name' => strtoupper($bankName ?: 'OTROS') . " ({$curr})", 'amount' => $amt, 'equiv' => $equiv];
            }
        }

        $zellePays = $payments->where('pay_way', 'zelle');
        if ($zellePays->count() > 0) {
            $amt = $zellePays->sum('amount');
            $equiv = $zellePays->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); });
            $summary[] = ['name' => 'ZELLE', 'amount' => $amt, 'equiv' => $equiv];
        }

        return $summary;
    }

    public function weeklyIncomeReportPdf(\Illuminate\Http\Request $request)
    {
        $selectedDate = $request->get('date', \Carbon\Carbon::today()->toDateString());
        $dt = \Carbon\Carbon::parse($selectedDate);
        $mon = $dt->startOfWeek(\Carbon\Carbon::MONDAY);
        $mondayDate = $mon->toDateString();
        $saturdayDate = $mon->copy()->addDays(5)->toDateString();
        $monFormatted = $mon->format('d/m/Y');
        $satFormatted = $mon->copy()->addDays(5)->format('d/m/Y');
        $weekLabel = "Semana del {$monFormatted} al {$satFormatted}";

        $days = [
            1 => ['name' => 'LUNES', 'date' => $mondayDate],
            2 => ['name' => 'MARTES', 'date' => $mon->copy()->addDays(1)->toDateString()],
            3 => ['name' => 'MIERCOLES', 'date' => $mon->copy()->addDays(2)->toDateString()],
            4 => ['name' => 'JUEVES', 'date' => $mon->copy()->addDays(3)->toDateString()],
            5 => ['name' => 'VIERNES', 'date' => $mon->copy()->addDays(4)->toDateString()],
            6 => ['name' => 'SABADO', 'date' => $mon->copy()->addDays(5)->toDateString()],
        ];

        $categories = [
            'DOLARES' => 'DOLARES',
            'PESOS' => 'PESOS',
            'EFECTIVO BS' => 'EFECTIVO BS',
            'BANCO DE VENEZUELA' => 'BANCO DE VENEZUELA',
            'BANCO PROVINCIAL' => 'BANCO PROVINCIAL',
            'BANCO MERCANTIL' => 'BANCO MERCANTIL',
            'ZELLE' => 'ZELLE'
        ];

        $report = [];
        $weeklyTotals = [];
        foreach ($categories as $cat) {
            $weeklyTotals[$cat] = [
                'contado' => 0.0,
                'cobranza' => 0.0
            ];
        }

        $allSheetsClosedAndAudited = true;
        $hasSheets = false;

        foreach ($days as $num => $dayInfo) {
            $date = $dayInfo['date'];
            $dayName = $dayInfo['name'];

            $dayData = [];
            foreach ($categories as $cat) {
                $dayData[$cat] = [
                    'contado' => 0.0,
                    'cobranza' => 0.0
                ];
            }

            // CONTADO
            $sales = Sale::whereBetween('created_at', [
                    \Carbon\Carbon::parse($date)->startOfDay(), 
                    \Carbon\Carbon::parse($date)->endOfDay()
                ])
                ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
                ->get();

            foreach ($sales as $sale) {
                $details = $sale->paymentDetails;
                if ($details->count() > 0) {
                    foreach ($details as $d) {
                        $rate = $d->exchange_rate > 0 ? $d->exchange_rate : 1;
                        $amtUSD = $d->amount / $rate;
                        $method = strtolower($d->payment_method);
                        $curr = strtoupper($d->currency_code);
                        $bank = strtoupper($d->bank_name ?? '');

                        if ($method === 'cash') {
                            if ($curr === 'USD') {
                                $dayData['DOLARES']['contado'] += $amtUSD;
                            } elseif ($curr === 'COP') {
                                $dayData['PESOS']['contado'] += $amtUSD;
                            } elseif ($curr === 'VES' || $curr === 'VED') {
                                $dayData['EFECTIVO BS']['contado'] += $amtUSD;
                            }
                        } elseif ($method === 'zelle' || str_contains($bank, 'ZELLE')) {
                            $dayData['ZELLE']['contado'] += $amtUSD;
                        } elseif ($method === 'bank' || $method === 'deposit') {
                            if (str_contains($bank, 'PROVINCIAL')) {
                                $dayData['BANCO PROVINCIAL']['contado'] += $amtUSD;
                            } elseif (str_contains($bank, 'MERCANTIL')) {
                                $dayData['BANCO MERCANTIL']['contado'] += $amtUSD;
                            } elseif (str_contains($bank, 'VENEZUELA') || str_contains($bank, 'BDV')) {
                                $dayData['BANCO DE VENEZUELA']['contado'] += $amtUSD;
                            }
                        }
                    }

                    // Subtract change
                    $changes = $sale->changeDetails;
                    foreach ($changes as $c) {
                        $rate = $c->exchange_rate > 0 ? $c->exchange_rate : 1;
                        $amtUSD = $c->amount / $rate;
                        $curr = strtoupper($c->currency_code);
                        if ($curr === 'USD') {
                            $dayData['DOLARES']['contado'] -= $amtUSD;
                        } elseif ($curr === 'COP') {
                            $dayData['PESOS']['contado'] -= $amtUSD;
                        } elseif ($curr === 'VES' || $curr === 'VED') {
                            $dayData['EFECTIVO BS']['contado'] -= $amtUSD;
                        }
                    }
                } else {
                    if ($sale->type !== 'credit') {
                        $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                        $netAmt = ($sale->cash - $sale->change) / $rate;
                        $curr = strtoupper($sale->primary_currency_code ?? 'USD');

                        if ($sale->type === 'cash') {
                            if ($curr === 'USD') {
                                $dayData['DOLARES']['contado'] += $netAmt;
                            } elseif ($curr === 'COP') {
                                $dayData['PESOS']['contado'] += $netAmt;
                            } elseif ($curr === 'VES' || $curr === 'VED') {
                                $dayData['EFECTIVO BS']['contado'] += $netAmt;
                            }
                        } elseif ($sale->type === 'zelle') {
                            $dayData['ZELLE']['contado'] += $netAmt;
                        } elseif ($sale->type === 'bank') {
                            $bank = strtoupper($sale->bank_name ?? '');
                            if (str_contains($bank, 'PROVINCIAL')) {
                                $dayData['BANCO PROVINCIAL']['contado'] += $netAmt;
                            } elseif (str_contains($bank, 'MERCANTIL')) {
                                $dayData['BANCO MERCANTIL']['contado'] += $netAmt;
                            } elseif (str_contains($bank, 'VENEZUELA') || str_contains($bank, 'BDV')) {
                                $dayData['BANCO DE VENEZUELA']['contado'] += $netAmt;
                            }
                        }
                    }
                }
            }

            // COBRANZA
            $sheet = CollectionSheet::whereDate('opened_at', $date)->first();
            if ($sheet) {
                $hasSheets = true;
                if ($sheet->status !== 'closed') {
                    $allSheetsClosedAndAudited = false;
                }

                $payments = Payment::where('collection_sheet_id', $sheet->id)
                    ->where('status', 'approved')
                    ->get();

                foreach ($payments as $p) {
                    $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                    $amtUSD = $p->amount / $rate;
                    $payWay = strtolower($p->pay_way);
                    $curr = strtoupper($p->currency);
                    $bank = strtoupper($p->bank ?? '');

                    if ($payWay === 'cash') {
                        if ($curr === 'USD') {
                            $dayData['DOLARES']['cobranza'] += $amtUSD;
                        } elseif ($curr === 'COP') {
                            $dayData['PESOS']['cobranza'] += $amtUSD;
                        } elseif ($curr === 'VES' || $curr === 'VED') {
                            $dayData['EFECTIVO BS']['cobranza'] += $amtUSD;
                        }
                    } elseif ($payWay === 'zelle' || str_contains($bank, 'ZELLE')) {
                        $dayData['ZELLE']['cobranza'] += $amtUSD;
                    } elseif ($payWay === 'bank' || $payWay === 'deposit') {
                        if (str_contains($bank, 'PROVINCIAL')) {
                            $dayData['BANCO PROVINCIAL']['cobranza'] += $amtUSD;
                        } elseif (str_contains($bank, 'MERCANTIL')) {
                            $dayData['BANCO MERCANTIL']['cobranza'] += $amtUSD;
                        } elseif (str_contains($bank, 'VENEZUELA') || str_contains($bank, 'BDV')) {
                            $dayData['BANCO DE VENEZUELA']['cobranza'] += $amtUSD;
                        }
                    }
                }
            }

            // VENTAS A CREDITO
            $creditSales = Sale::whereBetween('created_at', [
                    \Carbon\Carbon::parse($date)->startOfDay(), 
                    \Carbon\Carbon::parse($date)->endOfDay()
                ])
                ->where('type', 'credit')
                ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
                ->get();

            $dayCreditTotal = 0.0;
            foreach ($creditSales as $sale) {
                // Subtract approved returns
                $returnsForSale = \App\Models\SaleReturn::where('sale_id', $sale->id)
                    ->where('status', 'approved')
                    ->get();
                $retAmtUSD = 0.0;
                foreach ($returnsForSale as $ret) {
                    $rt_rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                    $retAmtUSD += ($ret->total_returned / $rt_rate);
                }
                $netSaleUSD = $sale->total_usd - $retAmtUSD;

                $pdSum = $sale->paymentDetails->sum(function($d) {
                    $rate = $d->exchange_rate > 0 ? $d->exchange_rate : 1;
                    return $d->amount / $rate;
                });
                $dayCreditTotal += max(0.0, $netSaleUSD - $pdSum);
            }

            $subtotalContado = 0.0;
            $subtotalCobranza = 0.0;
            foreach ($categories as $cat) {
                $subtotalContado += $dayData[$cat]['contado'];
                $subtotalCobranza += $dayData[$cat]['cobranza'];

                $weeklyTotals[$cat]['contado'] += $dayData[$cat]['contado'];
                $weeklyTotals[$cat]['cobranza'] += $dayData[$cat]['cobranza'];
            }

            $ventasMasCreditoContado = $subtotalContado + $dayCreditTotal;
            $totalGeneral = $ventasMasCreditoContado + $subtotalCobranza;
            $totalRecibido = $subtotalContado + $subtotalCobranza;

            $report[$dayName] = [
                'date' => $date,
                'data' => $dayData,
                'subtotal_contado' => $subtotalContado,
                'subtotal_cobranza' => $subtotalCobranza,
                'ventas_credito' => $dayCreditTotal,
                'ventas_mas_credito' => $ventasMasCreditoContado,
                'total_general' => $totalGeneral,
                'total_recibido' => $totalRecibido
            ];
        }

        $weeklySubtotalContado = 0.0;
        $weeklySubtotalCobranza = 0.0;
        $weeklyCreditTotal = 0.0;
        foreach ($report as $dName => $dVal) {
            $weeklyCreditTotal += $dVal['ventas_credito'];
        }

        foreach ($categories as $cat) {
            $weeklySubtotalContado += $weeklyTotals[$cat]['contado'];
            $weeklySubtotalCobranza += $weeklyTotals[$cat]['cobranza'];
        }

        $weeklyVentasMasCredito = $weeklySubtotalContado + $weeklyCreditTotal;
        $weeklyTotalGeneral = $weeklyVentasMasCredito + $weeklySubtotalCobranza;
        $weeklyTotalRecibido = $weeklySubtotalContado + $weeklySubtotalCobranza;

        $isPreliminar = !$hasSheets || !$allSheetsClosedAndAudited || (\Carbon\Carbon::parse($mondayDate)->startOfDay() <= \Carbon\Carbon::today());
        $statusText = $isPreliminar ? 'PRELIMINAR / EN CURSO' : 'CONSOLIDADO / AUDITADO';

        $config = Configuration::first();
        $user = auth()->user();
        $dateGenerated = \Carbon\Carbon::now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reports.weekly-income-report-pdf', compact(
            'report', 'weeklyTotals', 'weeklySubtotalContado', 'weeklySubtotalCobranza',
            'weeklyCreditTotal', 'weeklyVentasMasCredito', 'weeklyTotalGeneral', 'weeklyTotalRecibido',
            'isPreliminar', 'statusText', 'weekLabel', 'config', 'user', 'dateGenerated'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('Reporte_Ingresos_Semanal_' . $mondayDate . '.pdf');
    }

    public function monthlyIncomeReportPdf(\Illuminate\Http\Request $request)
    {
        $selectedMonth = $request->get('month', \Carbon\Carbon::today()->format('Y-m'));
        $dt = \Carbon\Carbon::parse($selectedMonth . '-01');
        $monthName = strtoupper($dt->locale('es')->monthName);
        $year = $dt->year;
        $monthLabel = "Mes de {$monthName} {$year}";

        $startOfMonth = $dt->copy()->startOfMonth();
        $endOfMonth = $dt->copy()->endOfMonth();

        // Divide month into weeks (Monday to Saturday, excluding Sunday)
        $daysByWeek = [];
        $currentDate = $startOfMonth->copy();
        while ($currentDate <= $endOfMonth) {
            if ($currentDate->dayOfWeek !== \Carbon\Carbon::SUNDAY) {
                $weekKey = $currentDate->format('o-W');
                if (!isset($daysByWeek[$weekKey])) {
                    $daysByWeek[$weekKey] = [];
                }
                $daysByWeek[$weekKey][] = $currentDate->copy();
            }
            $currentDate->addDay();
        }

        $weeks = [];
        $weekIndex = 1;
        foreach ($daysByWeek as $weekKey => $days) {
            $minDate = collect($days)->min()->toDateString();
            $maxDate = collect($days)->max()->toDateString();
            
            $minFormatted = \Carbon\Carbon::parse($minDate)->format('d/m');
            $maxFormatted = \Carbon\Carbon::parse($maxDate)->format('d/m');
            
            $weeks[$weekKey] = [
                'index' => $weekIndex,
                'label' => "Semana {$weekIndex} ({$minFormatted} - {$maxFormatted})",
                'start' => $minDate,
                'end' => $maxDate
            ];
            $weekIndex++;
        }

        $categories = [
            'DOLARES' => 'DOLARES',
            'PESOS' => 'PESOS',
            'EFECTIVO BS' => 'EFECTIVO BS',
            'BANCO DE VENEZUELA' => 'BANCO DE VENEZUELA',
            'BANCO PROVINCIAL' => 'BANCO PROVINCIAL',
            'BANCO MERCANTIL' => 'BANCO MERCANTIL',
            'ZELLE' => 'ZELLE'
        ];

        $report = [];
        foreach ($categories as $cat) {
            $report[$cat] = [];
            foreach ($weeks as $wKey => $wVal) {
                $report[$cat][$wKey] = [
                    'contado' => 0.0,
                    'cobranza' => 0.0
                ];
            }
        }

        $weeklyMetrics = [];
        foreach ($weeks as $wKey => $wVal) {
            $weeklyMetrics[$wKey] = [
                'subtotal_contado' => 0.0,
                'subtotal_cobranza' => 0.0,
                'ventas_credito' => 0.0,
                'ventas_mas_credito' => 0.0,
                'total_general' => 0.0,
                'total_recibido' => 0.0
            ];
        }

        $allSheetsClosedAndAudited = true;
        $hasSheets = false;

        foreach ($weeks as $wKey => $wVal) {
            $start = \Carbon\Carbon::parse($wVal['start'])->startOfDay();
            $end = \Carbon\Carbon::parse($wVal['end'])->endOfDay();

            // A. CONTADO
            $sales = Sale::whereBetween('created_at', [$start, $end])
                ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
                ->get();

            foreach ($sales as $sale) {
                $details = $sale->paymentDetails;
                if ($details->count() > 0) {
                    foreach ($details as $d) {
                        $rate = $d->exchange_rate > 0 ? $d->exchange_rate : 1;
                        $amtUSD = $d->amount / $rate;
                        $method = strtolower($d->payment_method);
                        $curr = strtoupper($d->currency_code);
                        $bank = strtoupper($d->bank_name ?? '');

                        if ($method === 'cash') {
                            if ($curr === 'USD') {
                                $report['DOLARES'][$wKey]['contado'] += $amtUSD;
                            } elseif ($curr === 'COP') {
                                $report['PESOS'][$wKey]['contado'] += $amtUSD;
                            } elseif ($curr === 'VES' || $curr === 'VED') {
                                $report['EFECTIVO BS'][$wKey]['contado'] += $amtUSD;
                            }
                        } elseif ($method === 'zelle' || str_contains($bank, 'ZELLE')) {
                            $report['ZELLE'][$wKey]['contado'] += $amtUSD;
                        } elseif ($method === 'bank' || $method === 'deposit') {
                            if (str_contains($bank, 'PROVINCIAL')) {
                                $report['BANCO PROVINCIAL'][$wKey]['contado'] += $amtUSD;
                            } elseif (str_contains($bank, 'MERCANTIL')) {
                                $report['BANCO MERCANTIL'][$wKey]['contado'] += $amtUSD;
                            } elseif (str_contains($bank, 'VENEZUELA') || str_contains($bank, 'BDV')) {
                                $report['BANCO DE VENEZUELA'][$wKey]['contado'] += $amtUSD;
                            }
                        }
                    }

                    // Subtract change
                    $changes = $sale->changeDetails;
                    foreach ($changes as $c) {
                        $rate = $c->exchange_rate > 0 ? $c->exchange_rate : 1;
                        $amtUSD = $c->amount / $rate;
                        $curr = strtoupper($c->currency_code);
                        if ($curr === 'USD') {
                            $report['DOLARES'][$wKey]['contado'] -= $amtUSD;
                        } elseif ($curr === 'COP') {
                            $report['PESOS'][$wKey]['contado'] -= $amtUSD;
                        } elseif ($curr === 'VES' || $curr === 'VED') {
                            $report['EFECTIVO BS'][$wKey]['contado'] -= $amtUSD;
                        }
                    }
                } else {
                    if ($sale->type !== 'credit') {
                        $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                        $netAmt = ($sale->cash - $sale->change) / $rate;
                        $curr = strtoupper($sale->primary_currency_code ?? 'USD');

                        if ($sale->type === 'cash') {
                            if ($curr === 'USD') {
                                $report['DOLARES'][$wKey]['contado'] += $netAmt;
                            } elseif ($curr === 'COP') {
                                $report['PESOS'][$wKey]['contado'] += $netAmt;
                            } elseif ($curr === 'VES' || $curr === 'VED') {
                                $report['EFECTIVO BS'][$wKey]['contado'] += $netAmt;
                            }
                        } elseif ($sale->type === 'zelle') {
                            $report['ZELLE'][$wKey]['contado'] += $netAmt;
                        } elseif ($sale->type === 'bank') {
                            $bank = strtoupper($sale->bank_name ?? '');
                            if (str_contains($bank, 'PROVINCIAL')) {
                                $report['BANCO PROVINCIAL'][$wKey]['contado'] += $netAmt;
                            } elseif (str_contains($bank, 'MERCANTIL')) {
                                $report['BANCO MERCANTIL'][$wKey]['contado'] += $netAmt;
                            } elseif (str_contains($bank, 'VENEZUELA') || str_contains($bank, 'BDV')) {
                                $report['BANCO DE VENEZUELA'][$wKey]['contado'] += $netAmt;
                            }
                        }
                    }
                }
            }

            // B. COBRANZA
            $sheets = CollectionSheet::whereBetween('opened_at', [$start, $end])->get();
            foreach ($sheets as $sheet) {
                $hasSheets = true;
                if ($sheet->status !== 'closed') {
                    $allSheetsClosedAndAudited = false;
                }

                $payments = Payment::where('collection_sheet_id', $sheet->id)
                    ->where('status', 'approved')
                    ->get();

                foreach ($payments as $p) {
                    $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                    $amtUSD = $p->amount / $rate;
                    $payWay = strtolower($p->pay_way);
                    $curr = strtoupper($p->currency);
                    $bank = strtoupper($p->bank ?? '');

                    if ($payWay === 'cash') {
                        if ($curr === 'USD') {
                            $report['DOLARES'][$wKey]['cobranza'] += $amtUSD;
                        } elseif ($curr === 'COP') {
                            $report['PESOS'][$wKey]['cobranza'] += $amtUSD;
                        } elseif ($curr === 'VES' || $curr === 'VED') {
                            $report['EFECTIVO BS'][$wKey]['cobranza'] += $amtUSD;
                        }
                    } elseif ($payWay === 'zelle' || str_contains($bank, 'ZELLE')) {
                        $report['ZELLE'][$wKey]['cobranza'] += $amtUSD;
                    } elseif ($payWay === 'bank' || $payWay === 'deposit') {
                        if (str_contains($bank, 'PROVINCIAL')) {
                            $report['BANCO PROVINCIAL'][$wKey]['cobranza'] += $amtUSD;
                        } elseif (str_contains($bank, 'MERCANTIL')) {
                            $report['BANCO MERCANTIL'][$wKey]['cobranza'] += $amtUSD;
                        } elseif (str_contains($bank, 'VENEZUELA') || str_contains($bank, 'BDV')) {
                            $report['BANCO DE VENEZUELA'][$wKey]['cobranza'] += $amtUSD;
                        }
                    }
                }
            }

            // C. VENTAS A CREDITO
            $creditSales = Sale::whereBetween('created_at', [$start, $end])
                ->where('type', 'credit')
                ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
                ->get();

            $weekCreditTotal = 0.0;
            foreach ($creditSales as $sale) {
                // Subtract approved returns
                $returnsForSale = \App\Models\SaleReturn::where('sale_id', $sale->id)
                    ->where('status', 'approved')
                    ->get();
                $retAmtUSD = 0.0;
                foreach ($returnsForSale as $ret) {
                    $rt_rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                    $retAmtUSD += ($ret->total_returned / $rt_rate);
                }
                $netSaleUSD = $sale->total_usd - $retAmtUSD;

                $pdSum = $sale->paymentDetails->sum(function($d) {
                    $rate = $d->exchange_rate > 0 ? $d->exchange_rate : 1;
                    return $d->amount / $rate;
                });
                $weekCreditTotal += max(0.0, $netSaleUSD - $pdSum);
            }

            $subtotalContado = 0.0;
            $subtotalCobranza = 0.0;
            foreach ($categories as $cat) {
                $subtotalContado += $report[$cat][$wKey]['contado'];
                $subtotalCobranza += $report[$cat][$wKey]['cobranza'];
            }

            $weeklyMetrics[$wKey]['subtotal_contado'] = $subtotalContado;
            $weeklyMetrics[$wKey]['subtotal_cobranza'] = $subtotalCobranza;
            $weeklyMetrics[$wKey]['ventas_credito'] = $weekCreditTotal;
            $weeklyMetrics[$wKey]['ventas_mas_credito'] = $subtotalContado + $weekCreditTotal;
            $weeklyMetrics[$wKey]['total_general'] = $subtotalContado + $weekCreditTotal + $subtotalCobranza;
            $weeklyMetrics[$wKey]['total_recibido'] = $subtotalContado + $subtotalCobranza;
        }

        // D. MONTHLY TOTALS (Accumulation)
        $monthlyTotals = [];
        $monthlySubtotalContado = 0.0;
        $monthlySubtotalCobranza = 0.0;
        $monthlyCreditTotal = 0.0;

        foreach ($categories as $cat) {
            $monthlyTotals[$cat] = [
                'contado' => 0.0,
                'cobranza' => 0.0
            ];
            foreach ($weeks as $wKey => $wVal) {
                $monthlyTotals[$cat]['contado'] += $report[$cat][$wKey]['contado'];
                $monthlyTotals[$cat]['cobranza'] += $report[$cat][$wKey]['cobranza'];
            }
            $monthlySubtotalContado += $monthlyTotals[$cat]['contado'];
            $monthlySubtotalCobranza += $monthlyTotals[$cat]['cobranza'];
        }

        foreach ($weeklyMetrics as $wKey => $metrics) {
            $monthlyCreditTotal += $metrics['ventas_credito'];
        }

        $monthlyVentasMasCredito = $monthlySubtotalContado + $monthlyCreditTotal;
        $monthlyTotalGeneral = $monthlyVentasMasCredito + $monthlySubtotalCobranza;
        $monthlyTotalRecibido = $monthlySubtotalContado + $monthlySubtotalCobranza;

        $isCurrentOrFutureMonth = \Carbon\Carbon::parse($selectedMonth . '-01')->startOfMonth() <= \Carbon\Carbon::today()->startOfMonth();
        $isPreliminar = !$hasSheets || !$allSheetsClosedAndAudited || $isCurrentOrFutureMonth;
        $statusText = $isPreliminar ? 'PRELIMINAR / EN CURSO' : 'CONSOLIDADO / AUDITADO';

        $config = Configuration::first();
        $user = auth()->user();
        $dateGenerated = \Carbon\Carbon::now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reports.monthly-income-report-pdf', compact(
            'weeks', 'report', 'weeklyMetrics', 'monthlyTotals',
            'monthlySubtotalContado', 'monthlySubtotalCobranza', 'monthlyCreditTotal',
            'monthlyVentasMasCredito', 'monthlyTotalGeneral', 'monthlyTotalRecibido',
            'isPreliminar', 'statusText', 'monthLabel', 'config', 'user', 'dateGenerated'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('Reporte_Ingresos_Mensual_' . $selectedMonth . '.pdf');
    }

    public function customersPdf(Request $request)
    {
        $selectedSellersStr = $request->get('selectedSellers');
        $selectedSellers = $selectedSellersStr ? explode(',', $selectedSellersStr) : [];
        $selectedSellers = array_filter($selectedSellers);

        $groupBy = $request->get('groupBy', 'none');
        $showDeleted = $request->get('showDeleted', 0) == 1;
        $inactivityDays = (int)$request->get('inactivityDays', 0);
        
        $columnsJson = $request->get('columns');
        $columns = $columnsJson ? json_decode($columnsJson, true) : [
            'name' => true,
            'taxpayer_id' => false,
            'address' => true,
            'city' => true,
            'phone' => true,
            'seller' => true,
            'wallet_balance' => false,
            'zone' => false,
            'allow_credit' => false,
            'credit_limit' => false,
            'credit_days' => false,
            'notifications' => false,
            'status' => false,
            'risk_level' => false,
        ];

        $query = \App\Models\Customer::with('seller')
            ->select('customers.*')
            ->selectSub(function ($q) {
                $q->selectRaw('max(created_at)')
                    ->from('sales')
                    ->whereColumn('sales.customer_id', 'customers.id')
                    ->where('sales.status', '<>', 'returned')
                    ->whereNull('sales.deletion_approved_at');
            }, 'last_purchase_at')
            ->selectSub(function ($q) {
                $q->selectRaw('coalesce(sum(total_usd), 0)')
                    ->from('sales')
                    ->whereColumn('sales.customer_id', 'customers.id')
                    ->where('sales.status', '<>', 'returned')
                    ->whereNull('sales.deletion_approved_at');
            }, 'total_purchased_usd')
            ->when($showDeleted, function ($q) {
                $q->withTrashed();
            })
            ->when(!empty($selectedSellers), function ($q) use ($selectedSellers) {
                $q->whereIn('seller_id', $selectedSellers);
            })
            ->when($inactivityDays > 0, function ($q) use ($inactivityDays) {
                $threshold = \Carbon\Carbon::now()->subDays($inactivityDays)->toDateTimeString();
                $q->where(function ($sub) use ($threshold) {
                    $sub->whereRaw('(select max(created_at) from sales where sales.customer_id = customers.id and sales.status <> "returned" and sales.deletion_approved_at is null) < ?', [$threshold])
                        ->orWhereRaw('not exists (select 1 from sales where sales.customer_id = customers.id and sales.status <> "returned" and sales.deletion_approved_at is null)');
                });
            })
            ->orderBy('name');

        $customers = $query->get();

        $isGrouped = ($groupBy === 'seller_id');
        if ($isGrouped) {
            $customersData = $customers->groupBy(function ($customer) {
                return $customer->seller ? $customer->seller->name : 'Sin Vendedor';
            });
        } else {
            $customersData = ['' => $customers];
        }

        $config = \App\Models\Configuration::first();
        $user = auth()->user();
        $date = \Carbon\Carbon::now()->format('d/m/Y H:i');

        $activeColumnsCount = count(array_filter($columns));
        $orientation = $activeColumnsCount > 6 ? 'landscape' : 'portrait';

        $pdf = Pdf::loadView('reports.customer-report-pdf', compact(
            'customersData', 'isGrouped', 'columns', 'config', 'user', 'date', 'showDeleted'
        ))->setPaper('a4', $orientation);

        return $pdf->stream('Reporte_Clientes_' . \Carbon\Carbon::now()->format('Ymd_His') . '.pdf');
    }

    public function customersTrackingPdf(Request $request)
    {
        $selectedSellersStr = $request->get('selectedSellers');
        $selectedSellers = $selectedSellersStr ? explode(',', $selectedSellersStr) : [];
        $selectedSellers = array_filter($selectedSellers);

        $groupBy = $request->get('groupBy', 'none');
        $showDeleted = $request->get('showDeleted', 0) == 1;
        $inactivityDays = (int)$request->get('inactivityDays', 0);
        $columnsJson = $request->get('columns');
        $columns = $columnsJson ? json_decode($columnsJson, true) : [
            'name' => true,
            'taxpayer_id' => true,
            'address' => true,
            'city' => true,
            'phone' => true,
            'seller' => true,
            'wallet_balance' => true,
            'zone' => true,
            'allow_credit' => true,
            'credit_limit' => true,
            'credit_days' => true,
            'notifications' => true,
            'status' => true,
        ];

        $query = \App\Models\Customer::with('seller')
            ->when($showDeleted, function ($q) {
                $q->withTrashed();
            })
            ->when(!empty($selectedSellers), function ($q) use ($selectedSellers) {
                $q->whereIn('seller_id', $selectedSellers);
            })
            ->when($inactivityDays > 0, function ($q) use ($inactivityDays) {
                $threshold = \Carbon\Carbon::now()->subDays($inactivityDays)->toDateTimeString();
                $q->where(function ($sub) use ($threshold) {
                    $sub->whereRaw('(select max(created_at) from sales where sales.customer_id = customers.id and sales.status <> "returned" and sales.deletion_approved_at is null) < ?', [$threshold])
                        ->orWhereRaw('not exists (select 1 from sales where sales.customer_id = customers.id and sales.status <> "returned" and sales.deletion_approved_at is null)');
                });
            })
            ->orderBy('name');

        $customers = $query->get();

        $isGrouped = ($groupBy === 'seller_id');
        if ($isGrouped) {
            $customersData = $customers->groupBy(function ($customer) {
                return $customer->seller ? $customer->seller->name : 'Sin Vendedor';
            });
        } else {
            $customersData = ['' => $customers];
        }

        $config = \App\Models\Configuration::first();
        $user = auth()->user();
        $date = \Carbon\Carbon::now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reports.customer-tracking-pdf', compact(
            'customersData', 'isGrouped', 'config', 'user', 'date', 'showDeleted', 'columns'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('Planilla_Seguimiento_' . \Carbon\Carbon::now()->format('Ymd_His') . '.pdf');
    }

    public function customersRecoveryPdf(Request $request)
    {
        $selectedSellersStr = $request->get('selectedSellers');
        $selectedSellers = $selectedSellersStr ? explode(',', $selectedSellersStr) : [];
        $selectedSellers = array_filter($selectedSellers);

        $groupBy = $request->get('groupBy', 'none');
        $showDeleted = $request->get('showDeleted', 0) == 1;
        $inactivityDays = (int)$request->get('inactivityDays', 0);
        $columnsJson = $request->get('columns');
        $columns = $columnsJson ? json_decode($columnsJson, true) : [
            'name' => true,
            'taxpayer_id' => true,
            'address' => true,
            'city' => true,
            'phone' => true,
            'seller' => true,
            'wallet_balance' => true,
            'zone' => true,
            'allow_credit' => true,
            'credit_limit' => true,
            'credit_days' => true,
            'notifications' => true,
            'status' => true,
            'last_purchase' => true,
            'total_purchased' => true,
            'risk_level' => true,
        ];

        $query = \App\Models\Customer::with('seller')
            ->select('customers.*')
            ->selectSub(function ($q) {
                $q->selectRaw('max(created_at)')
                    ->from('sales')
                    ->whereColumn('sales.customer_id', 'customers.id')
                    ->where('sales.status', '<>', 'returned')
                    ->whereNull('sales.deletion_approved_at');
            }, 'last_purchase_at')
            ->selectSub(function ($q) {
                $q->selectRaw('coalesce(sum(total_usd), 0)')
                    ->from('sales')
                    ->whereColumn('sales.customer_id', 'customers.id')
                    ->where('sales.status', '<>', 'returned')
                    ->whereNull('sales.deletion_approved_at');
            }, 'total_purchased_usd')
            ->when($showDeleted, function ($q) {
                $q->withTrashed();
            })
            ->when(!empty($selectedSellers), function ($q) use ($selectedSellers) {
                $q->whereIn('seller_id', $selectedSellers);
            })
            ->when($inactivityDays > 0, function ($q) use ($inactivityDays) {
                $threshold = \Carbon\Carbon::now()->subDays($inactivityDays)->toDateTimeString();
                $q->where(function ($sub) use ($threshold) {
                    $sub->whereRaw('(select max(created_at) from sales where sales.customer_id = customers.id and sales.status <> "returned" and sales.deletion_approved_at is null) < ?', [$threshold])
                        ->orWhereRaw('not exists (select 1 from sales where sales.customer_id = customers.id and sales.status <> "returned" and sales.deletion_approved_at is null)');
                });
            })
            ->orderBy('total_purchased_usd', 'desc'); // Order by historical volume to prioritize high-value clients

        $customers = $query->get();

        $isGrouped = ($groupBy === 'seller_id');
        if ($isGrouped) {
            $customersData = $customers->groupBy(function ($customer) {
                return $customer->seller ? $customer->seller->name : 'Sin Vendedor';
            });
        } else {
            $customersData = ['' => $customers];
        }

        $config = \App\Models\Configuration::first();
        $user = auth()->user();
        $date = \Carbon\Carbon::now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reports.customer-recovery-pdf', compact(
            'customersData', 'isGrouped', 'config', 'user', 'date', 'showDeleted', 'inactivityDays', 'columns'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('Reporte_Recuperacion_' . \Carbon\Carbon::now()->format('Ymd_His') . '.pdf');
    }

    public function customerActivityPdf(Request $request)
    {
        $selectedCustomersStr = $request->get('selectedCustomers');
        $selectedCustomers = $selectedCustomersStr ? explode(',', $selectedCustomersStr) : [];
        $selectedCustomers = array_filter($selectedCustomers);

        $periodType = $request->get('periodType', 'monthly');
        $dateFromStr = $request->get('dateFrom');
        $dateToStr = $request->get('dateTo');
        $metric = $request->get('metric', 'amount');

        $dateFrom = $dateFromStr ? \Carbon\Carbon::parse($dateFromStr)->startOfDay() : null;
        $dateTo = $dateToStr ? \Carbon\Carbon::parse($dateToStr)->endOfDay() : null;

        if (empty($selectedCustomers)) {
            abort(400, 'Debe seleccionar al menos un cliente.');
        }

        $selectExpression = "";
        if ($periodType === 'weekly') {
            $selectExpression = "DATE_FORMAT(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY), '%Y-%m-%d')";
        } elseif ($periodType === 'quarterly') {
            $selectExpression = "CONCAT(YEAR(created_at), '-T', QUARTER(created_at))";
        } elseif ($periodType === 'yearly') {
            $selectExpression = "CAST(YEAR(created_at) AS CHAR)";
        } else { // monthly
            $selectExpression = "DATE_FORMAT(created_at, '%Y-%m')";
        }

        $results = DB::table('sales')
            ->select([
                'customer_id',
                DB::raw("$selectExpression as period_label"),
                DB::raw("SUM(total_usd) as total_amount"),
                DB::raw("COUNT(*) as sales_count"),
            ])
            ->whereIn('customer_id', $selectedCustomers)
            ->where('status', '<>', 'returned')
            ->whereNull('deletion_approved_at')
            ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo))
            ->groupBy(['customer_id', DB::raw("$selectExpression")])
            ->orderBy('period_label')
            ->get();

        $results->transform(function ($row) use ($periodType) {
            $row->raw_period = $row->period_label;
            if ($periodType === 'weekly') {
                $dt = \Carbon\Carbon::parse($row->period_label);
                $monthName = strtoupper($dt->locale('es')->monthName);
                $weekNumber = sprintf('%02d', $dt->weekOfYear);
                $row->period_label = "{$dt->year}-{$monthName}-{$dt->day}-S{$weekNumber}";
            } elseif ($periodType === 'monthly') {
                $dt = \Carbon\Carbon::parse($row->period_label . '-01');
                $monthName = strtoupper($dt->locale('es')->monthName);
                $row->period_label = "{$dt->year}-{$monthName}";
            }
            return $row;
        });

        $periodsMap = [];
        foreach ($results as $row) {
            $rawKey = $row->raw_period ?? $row->period_label;
            $periodsMap[$rawKey] = $row->period_label;
        }
        ksort($periodsMap);
        $labels = array_values($periodsMap);
        $customers = \App\Models\Customer::whereIn('id', $selectedCustomers)->get();

        $datasets = [];
        foreach ($customers as $customer) {
            $customerData = [];
            foreach ($labels as $label) {
                $match = $results->first(fn($row) => $row->customer_id == $customer->id && $row->period_label == $label);
                if ($metric === 'count') {
                    $customerData[] = $match ? (int)$match->sales_count : 0;
                } else {
                    $customerData[] = $match ? (float)$match->total_amount : 0.0;
                }
            }
            $datasets[] = [
                'label' => $customer->name,
                'data' => $customerData,
            ];
        }

        // Calculate KPIs
        $kpis = [];
        foreach ($customers as $customer) {
            $customerSales = DB::table('sales')
                ->where('customer_id', $customer->id)
                ->where('status', '<>', 'returned')
                ->whereNull('deletion_approved_at')
                ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo));

            $totalAmount = $customerSales->sum('total_usd');
            $countSales = $customerSales->count();
            $avgTicket = $countSales > 0 ? $totalAmount / $countSales : 0;
            
            $lastSale = DB::table('sales')
                ->where('customer_id', $customer->id)
                ->where('status', '<>', 'returned')
                ->whereNull('deletion_approved_at')
                ->latest('created_at')
                ->first();

            $topProducts = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->join('products', 'sale_details.product_id', '=', 'products.id')
                ->select([
                    'sale_details.product_id',
                    'products.name as product_name',
                    DB::raw('SUM(sale_details.quantity) as total_qty'),
                    DB::raw('SUM(sale_details.quantity * sale_details.sale_price) as total_usd'),
                ])
                ->where('sales.customer_id', $customer->id)
                ->where('sales.status', '<>', 'returned')
                ->whereNull('sales.deletion_approved_at')
                ->when($dateFrom, fn($q) => $q->where('sales.created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->where('sales.created_at', '<=', $dateTo))
                ->groupBy('sale_details.product_id', 'products.name')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get()
                ->toArray();

            $kpis[$customer->id] = [
                'name' => $customer->name,
                'total_amount' => $totalAmount,
                'sales_count' => $countSales,
                'avg_ticket' => $avgTicket,
                'last_purchase_at' => $lastSale ? \Carbon\Carbon::parse($lastSale->created_at)->format('d/m/Y') : 'Nunca ha comprado',
                'top_products' => $topProducts,
            ];
        }

        // Detailed sales list
        $detailedSales = \App\Models\Sale::with(['customer', 'user'])
            ->whereIn('customer_id', $selectedCustomers)
            ->where('status', '<>', 'returned')
            ->whereNull('deletion_approved_at')
            ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo))
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get();

        $config = \App\Models\Configuration::first();
        $user = auth()->user();
        $date = \Carbon\Carbon::now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reports.customer-activity-pdf', compact(
            'labels', 'datasets', 'kpis', 'detailedSales', 'metric', 'periodType', 
            'config', 'user', 'date', 'dateFromStr', 'dateToStr'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('Reporte_Actividad_Clientes_' . \Carbon\Carbon::now()->format('Ymd_His') . '.pdf');
    }

    public function salesAnalysisPdf(Request $request)
    {
        $selectedSellersStr = $request->get('selectedSellers');
        $selectedSellers = $selectedSellersStr ? explode(',', $selectedSellersStr) : [];
        $selectedSellers = array_filter($selectedSellers);

        $periodType = $request->get('periodType', 'monthly');
        $dateFromStr = $request->get('dateFrom');
        $dateToStr = $request->get('dateTo');
        $metric = $request->get('metric', 'amount');

        $dateFrom = $dateFromStr ? \Carbon\Carbon::parse($dateFromStr)->startOfDay() : null;
        $dateTo = $dateToStr ? \Carbon\Carbon::parse($dateToStr)->endOfDay() : null;

        $selectExpression = "";
        if ($periodType === 'daily') {
            $selectExpression = "DATE_FORMAT(sales.created_at, '%Y-%m-%d')";
        } elseif ($periodType === 'weekly') {
            $selectExpression = "DATE_FORMAT(DATE_SUB(sales.created_at, INTERVAL WEEKDAY(sales.created_at) DAY), '%Y-%m-%d')";
        } elseif ($periodType === 'biweekly') {
            $selectExpression = "CASE WHEN DAY(sales.created_at) <= 15 THEN CONCAT(DATE_FORMAT(sales.created_at, '%Y-%m'), '-01') ELSE CONCAT(DATE_FORMAT(sales.created_at, '%Y-%m'), '-16') END";
        } elseif ($periodType === 'yearly') {
            $selectExpression = "CAST(YEAR(sales.created_at) AS CHAR)";
        } else { // monthly
            $selectExpression = "DATE_FORMAT(sales.created_at, '%Y-%m')";
        }

        $query = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select([
                DB::raw("$selectExpression as period_label"),
                DB::raw("SUM(sales.total_usd) as total_amount"),
                DB::raw("COUNT(*) as sales_count"),
                DB::raw("SUM(sales.final_commission_amount) as total_commission"),
                DB::raw("SUM(sales.total_usd - IFNULL(sales.final_commission_amount, 0)) as net_sales"),
            ])
            ->where('sales.status', '<>', 'returned')
            ->whereNull('sales.deletion_approved_at')
            ->when($dateFrom, fn($q) => $q->where('sales.created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('sales.created_at', '<=', $dateTo));

        if (!empty($selectedSellers)) {
            $query->whereIn('customers.seller_id', $selectedSellers);
        }

        $results = $query->groupBy(DB::raw("$selectExpression"))
            ->orderBy('period_label')
            ->get();

        $results->transform(function ($row) use ($periodType) {
            $row->raw_period = $row->period_label;
            $dt = \Carbon\Carbon::parse(explode('-', $row->period_label)[0] === $row->period_label ? $row->period_label . '-01-01' : $row->period_label);
            $monthName = strtoupper($dt->locale('es')->monthName);

            if ($periodType === 'daily') {
                $row->period_label = $dt->format('d/m/Y');
            } elseif ($periodType === 'weekly') {
                $weekNumber = sprintf('%02d', $dt->weekOfYear);
                $row->period_label = "{$dt->year}-{$monthName}-{$dt->day}-S{$weekNumber}";
            } elseif ($periodType === 'biweekly') {
                $fortnight = $dt->day <= 15 ? 'Q1' : 'Q2';
                $row->period_label = "{$dt->year}-{$monthName}-{$fortnight}";
            } elseif ($periodType === 'monthly') {
                $row->period_label = "{$dt->year}-{$monthName}";
            } else { // yearly
                $row->period_label = "{$dt->year}";
            }
            return $row;
        });

        // Sort results collection by raw_period to ensure perfect chronological order
        $results = $results->sortBy('raw_period')->values();

        // Current KPIs
        $currentQuery = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.status', '<>', 'returned')
            ->whereNull('sales.deletion_approved_at')
            ->when($dateFrom, fn($q) => $q->where('sales.created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('sales.created_at', '<=', $dateTo));

        if (!empty($selectedSellers)) {
            $currentQuery->whereIn('customers.seller_id', $selectedSellers);
        }

        $totalSales = $currentQuery->sum('sales.total_usd');
        $salesCount = $currentQuery->count();
        $totalCommission = $currentQuery->sum('sales.final_commission_amount');
        $avgTicket = $salesCount > 0 ? $totalSales / $salesCount : 0;
        $netSales = $totalSales - $totalCommission;

        // Calculate growth against previous period
        $growthPercent = 0;
        if ($dateFrom && $dateTo) {
            $daysDiff = $dateFrom->diffInDays($dateTo) + 1;
            $prevDateFrom = $dateFrom->copy()->subDays($daysDiff);
            $prevDateTo = $dateFrom->copy()->subDay()->endOfDay();

            $prevQuery = DB::table('sales')
                ->join('customers', 'sales.customer_id', '=', 'customers.id')
                ->where('sales.status', '<>', 'returned')
                ->whereNull('sales.deletion_approved_at')
                ->where('sales.created_at', '>=', $prevDateFrom)
                ->where('sales.created_at', '<=', $prevDateTo);

            if (!empty($selectedSellers)) {
                $prevQuery->whereIn('customers.seller_id', $selectedSellers);
            }

            $prevTotal = $prevQuery->sum('sales.total_usd');
            if ($prevTotal > 0) {
                $growthPercent = (($totalSales - $prevTotal) / $prevTotal) * 100;
            } else {
                $growthPercent = $totalSales > 0 ? 100 : 0;
            }
        }

        $kpis = [
            'total_sales' => $totalSales,
            'sales_count' => $salesCount,
            'avg_ticket' => $avgTicket,
            'total_commission' => $totalCommission,
            'net_sales' => $netSales,
            'growth_percent' => $growthPercent
        ];

        // Detailed sales list for the report PDF
        $detailedSales = \App\Models\Sale::with(['customer', 'user'])
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select('sales.*')
            ->where('sales.status', '<>', 'returned')
            ->whereNull('sales.deletion_approved_at')
            ->when($dateFrom, fn($q) => $q->where('sales.created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('sales.created_at', '<=', $dateTo));

        if (!empty($selectedSellers)) {
            $detailedSales->whereIn('customers.seller_id', $selectedSellers);
        }

        $detailedSales = $detailedSales->orderBy('sales.created_at', 'desc')->take(100)->get();

        $config = \App\Models\Configuration::first();
        $user = auth()->user();
        $date = \Carbon\Carbon::now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reports.sales-analysis-pdf', compact(
            'results', 'kpis', 'detailedSales', 'metric', 'periodType', 
            'config', 'user', 'date', 'dateFromStr', 'dateToStr'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('Reporte_Analisis_Ventas_' . \Carbon\Carbon::now()->format('Ymd_His') . '.pdf');
    }

    public function sellersPerformancePdf(Request $request)
    {
        $selectedSellersStr = $request->get('selectedSellers');
        $selectedSellers = $selectedSellersStr ? explode(',', $selectedSellersStr) : [];
        $selectedSellers = array_filter($selectedSellers);

        $periodType = $request->get('periodType', 'monthly');
        $dateFromStr = $request->get('dateFrom');
        $dateToStr = $request->get('dateTo');
        $metric = $request->get('metric', 'amount');

        $dateFrom = $dateFromStr ? \Carbon\Carbon::parse($dateFromStr)->startOfDay() : null;
        $dateTo = $dateToStr ? \Carbon\Carbon::parse($dateToStr)->endOfDay() : null;

        $sales = \App\Models\Sale::with(['paymentDetails', 'payments', 'returns', 'debitNotes', 'customer'])
            ->where('status', '<>', 'returned')
            ->whereNull('deletion_approved_at')
            ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo))
            ->get();

        $sellersQuery = \App\Models\User::sellers();
        if (!empty($selectedSellers)) {
            $sellersQuery->whereIn('id', $selectedSellers);
        }
        $sellers = $sellersQuery->orderBy('name')->get();

        $summary = [];
        $totalSales = 0;
        $totalCommission = 0;
        $totalNetSales = 0;
        $totalDebt = 0;
        $totalOverdue = 0;

        foreach ($sellers as $seller) {
            $oficinaUser = \App\Models\User::where('name', 'OFICINA')->first();
            $oficinaId = $oficinaUser ? $oficinaUser->id : null;

            $sellerSales = $sales->filter(function($sale) use ($seller, $oficinaId) {
                $sId = $sale->customer->seller_id ?? null;
                return $sId == $seller->id || (is_null($sId) && $seller->id == $oficinaId);
            });
            
            $grossSales = $sellerSales->sum('total_usd');
            $invoicesCount = $sellerSales->count();
            $commissions = $sellerSales->sum('final_commission_amount');
            $netSales = $grossSales - $commissions;
            $marginPercent = $grossSales > 0 ? ($netSales / $grossSales) * 100 : 0;
            
            $activeCustomers = $sellerSales->pluck('customer_id')->unique()->count();

            $pendingDebt = 0;
            $overdueDebt = 0;
            $weightedOverdueSum = 0;

            foreach ($sellerSales as $sale) {
                $debt = \App\Livewire\Reports\SellersPerformanceReport::calculateSaleDebtUsd($sale);
                if ($debt > 0) {
                    $pendingDebt += $debt;
                    
                    $daysOverdue = $sale->days_overdue;
                    if ($daysOverdue > 0) {
                        $overdueDebt += $debt;
                        $weightedOverdueSum += ($daysOverdue * $debt);
                    }
                }
            }

            $avgDaysOverdue = $overdueDebt > 0 ? $weightedOverdueSum / $overdueDebt : 0;

            $summary['sellers'][] = [
                'name' => $seller->name,
                'gross_sales' => $grossSales,
                'invoices_count' => $invoicesCount,
                'commissions' => $commissions,
                'net_sales' => $netSales,
                'margin_percent' => $marginPercent,
                'active_customers' => $activeCustomers,
                'pending_debt' => $pendingDebt,
                'overdue_debt' => $overdueDebt,
                'avg_days_overdue' => $avgDaysOverdue,
            ];

            $totalSales += $grossSales;
            $totalCommission += $commissions;
            $totalNetSales += $netSales;
            $totalDebt += $pendingDebt;
            $totalOverdue += $overdueDebt;
        }

        $kpis = [
            'total_sales' => $totalSales,
            'total_commission' => $totalCommission,
            'net_sales' => $totalNetSales,
            'margin_percent' => $totalSales > 0 ? ($totalNetSales / $totalSales) * 100 : 0,
            'total_debt' => $totalDebt,
            'total_overdue' => $totalOverdue,
        ];

        $detailedSales = \App\Models\Sale::with(['customer', 'user'])
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select('sales.*')
            ->where('sales.status', '<>', 'returned')
            ->whereNull('sales.deletion_approved_at')
            ->when($dateFrom, fn($q) => $q->where('sales.created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('sales.created_at', '<=', $dateTo));

        $oficinaUser = \App\Models\User::where('name', 'OFICINA')->first();
        $oficinaId = $oficinaUser ? $oficinaUser->id : null;

        if (!empty($selectedSellers)) {
            $detailedSales->where(function($q) use ($selectedSellers, $oficinaId) {
                $q->whereIn('customers.seller_id', $selectedSellers);
                if ($oficinaId && in_array($oficinaId, $selectedSellers)) {
                    $q->orWhereNull('customers.seller_id');
                }
            });
        }

        $detailedSales = $detailedSales->orderBy('sales.created_at', 'desc')->take(100)->get();

        $config = \App\Models\Configuration::first();
        $user = auth()->user();
        $date = \Carbon\Carbon::now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reports.sellers-performance-pdf', [
            'sellers' => $summary['sellers'] ?? [],
            'kpis' => $kpis,
            'detailedSales' => $detailedSales,
            'metric' => $metric,
            'periodType' => $periodType,
            'config' => $config,
            'user' => $user,
            'date' => $date,
            'dateFromStr' => $dateFromStr,
            'dateToStr' => $dateToStr
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Reporte_Desempeno_Vendedores_' . \Carbon\Carbon::now()->format('Ymd_His') . '.pdf');
    }

    public function billingOperatorsPdf(Request $request)
    {
        $selectedOperatorsStr = $request->get('selectedOperators');
        $selectedOperators = $selectedOperatorsStr ? explode(',', $selectedOperatorsStr) : [];
        $selectedOperators = array_filter($selectedOperators);

        $periodType = $request->get('periodType', 'monthly');
        $dateFromStr = $request->get('dateFrom');
        $dateToStr = $request->get('dateTo');
        $metric = $request->get('metric', 'precision_score');

        $dateFrom = $dateFromStr ? \Carbon\Carbon::parse($dateFromStr)->startOfDay() : null;
        $dateTo = $dateToStr ? \Carbon\Carbon::parse($dateToStr)->endOfDay() : null;

        $query = DB::table('sales')
            ->select([
                'sales.user_id',
                DB::raw("COUNT(*) as total_sales"),
                DB::raw("SUM(sales.total_usd) as total_amount"),
                DB::raw("SUM(CASE WHEN sales.status IN ('voided', 'cancelled', 'anulated') OR sales.deletion_approved_at IS NOT NULL THEN 1 ELSE 0 END) as voided_count"),
                DB::raw("SUM(CASE WHEN (SELECT COUNT(*) FROM sale_history_logs WHERE sale_history_logs.sale_id = sales.id) > 0 THEN 1 ELSE 0 END) as modified_count"),
                DB::raw("SUM(CASE WHEN (SELECT COUNT(*) FROM sale_returns WHERE sale_returns.sale_id = sales.id AND sale_returns.status = 'approved') > 0 THEN 1 ELSE 0 END) as returned_count"),
                DB::raw("COUNT(DISTINCT DATE(sales.created_at)) as active_days")
            ])
            ->when($dateFrom, fn($q) => $q->where('sales.created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('sales.created_at', '<=', $dateTo));

        if (!empty($selectedOperators)) {
            $query->whereIn('sales.user_id', $selectedOperators);
        }

        $summaryResults = $query->groupBy('sales.user_id')->get();

        // Get user details
        $userIds = $summaryResults->pluck('user_id')->filter()->toArray();
        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        $operators = [];
        $totalSales = 0;
        $totalAmount = 0;
        $totalVoided = 0;
        $totalModified = 0;
        $totalReturned = 0;
        $totalErrors = 0;

        foreach ($summaryResults as $row) {
            if (!$row->user_id) {
                continue;
            }
            $user = $users->get($row->user_id);
            $name = $user ? $user->name : 'Operador Desconocido (' . $row->user_id . ')';

            $score = \App\Livewire\Reports\BillingOperatorsReport::calculatePrecisionScore(
                $row->total_sales,
                $row->voided_count,
                $row->modified_count,
                $row->returned_count
            );

            $efficiency = $row->active_days > 0 ? round($row->total_sales / $row->active_days, 1) : 0.0;
            $errors = $row->voided_count + $row->modified_count + $row->returned_count;

            $operators[] = [
                'id' => $row->user_id,
                'name' => $name,
                'total_sales' => $row->total_sales,
                'total_amount' => (float)$row->total_amount,
                'voided_count' => (int)$row->voided_count,
                'modified_count' => (int)$row->modified_count,
                'returned_count' => (int)$row->returned_count,
                'precision_score' => $score,
                'active_days' => (int)$row->active_days,
                'efficiency' => $efficiency,
                'errors_count' => $errors
            ];

            $totalSales += $row->total_sales;
            $totalAmount += $row->total_amount;
            $totalVoided += $row->voided_count;
            $totalModified += $row->modified_count;
            $totalReturned += $row->returned_count;
            $totalErrors += $errors;
        }

        usort($operators, fn($a, $b) => strcmp($a['name'], $b['name']));

        $avgScore = \App\Livewire\Reports\BillingOperatorsReport::calculatePrecisionScore(
            $totalSales,
            $totalVoided,
            $totalModified,
            $totalReturned
        );

        $kpis = [
            'total_sales' => $totalSales,
            'total_amount' => $totalAmount,
            'avg_precision_score' => $avgScore,
            'total_errors' => $totalErrors,
            'total_voided' => $totalVoided,
            'total_modified' => $totalModified,
            'total_returned' => $totalReturned,
        ];

        // Fetch detailed sales list (max 100)
        $detailedSalesQuery = \App\Models\Sale::with(['customer', 'user', 'returns', 'history'])
            ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo));

        if (!empty($selectedOperators)) {
            $detailedSalesQuery->whereIn('user_id', $selectedOperators);
        }

        $detailedSales = $detailedSalesQuery->orderBy('created_at', 'desc')->take(100)->get();

        $config = \App\Models\Configuration::first();
        $user = auth()->user();
        $date = \Carbon\Carbon::now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reports.billing-operators-pdf', [
            'operators' => $operators,
            'kpis' => $kpis,
            'detailedSales' => $detailedSales,
            'metric' => $metric,
            'periodType' => $periodType,
            'config' => $config,
            'user' => $user,
            'date' => $date,
            'dateFromStr' => $dateFromStr,
            'dateToStr' => $dateToStr
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Reporte_Eficiencia_Operadores_' . \Carbon\Carbon::now()->format('Ymd_His') . '.pdf');
    }

    public function cashFlowForecastPdf(Request $request)
    {
        $report = new \App\Livewire\Reports\CashFlowForecastReport();
        $report->dateFrom = $request->get('dateFrom');
        $report->dateTo = $request->get('dateTo');
        $report->customer_id = $request->get('customer_id');
        $report->seller_id = $request->get('seller_id');

        $sales = $report->getProcessedSales();
        $metrics = $report->getCalculatedMetrics($sales);

        // Sorting
        $sortField = $request->get('sortField', 'due_date');
        $sortDirection = $request->get('sortDirection', 'asc');
        
        $sortedSales = $sales->sortBy(function($item) use ($sortField) {
            return $item[$sortField] ?? '';
        }, SORT_REGULAR, $sortDirection === 'desc');

        $config = \App\Models\Configuration::first();
        $user = auth()->user();
        $date = \Carbon\Carbon::now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('livewire.reports.cash-flow-forecast-report-pdf', [
            'sales' => $sortedSales,
            'metrics' => $metrics,
            'config' => $config,
            'user' => $user,
            'date' => $date,
            'dateFrom' => $report->dateFrom,
            'dateTo' => $report->dateTo
        ]);

        $pdf->setPaper('a4', 'landscape');

        $fileName = 'Proyeccion_Flujo_Cobranza_' . \Carbon\Carbon::now()->format('YmdHis') . '.pdf';

        return $pdf->stream($fileName);
    }
}

