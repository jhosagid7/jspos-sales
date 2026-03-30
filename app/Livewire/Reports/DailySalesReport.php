<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use App\Models\Product;
use Livewire\Component;
use App\Models\SaleDetail;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class DailySalesReport extends Component
{
    use WithPagination;

    public $pagination = 10, $users = [], $user_id, $dateFrom, $dateTo, $type = 0;
    public $currencies = [];
    public $sellers = [], $seller_id;
    public $customer;

    public $showReport = false;
    public $totales = 0;
    public $searchFolio = '';
    public $reportFormat = 'detailed'; // detailed, summarized
    public $includeDetails = false;
    public $groupBy = 'none';

    public $showPdfModal = false;
    public $pdfUrl = '';

    function mount()
    {
        session()->forget('daily_sale_customer');
        session(['map' => "TOTAL COSTO $0.00", 'child' => 'TOTAL VENTA $0.00', 'rest' => 'GANANCIA: $0.00 / MARGEN: 0.00%', 'pos' => 'Reporte de Ventas Diarias']);

        $this->users = User::orderBy('name')->get();
        $this->sellers = User::role(['Vendedor', 'Vendedor foraneo'])->orderBy('name')->get();
        $this->currencies = \App\Models\Currency::orderBy('id')->get();
    }

    public function render()
    {
        $this->customer = session('daily_sale_customer', null);
        $sales = $this->getReport();

        return view('livewire.reports.daily-sales-report', [
            'sales' => $sales ?? []
        ]);
    }

    #[On('daily_sale_customer')]
    function setCustomer($customer)
    {
        session(['daily_sale_customer' => $customer]);
        $this->customer = $customer;
    }

    function getReport()
    {
        if (!$this->showReport) return [];

        try {
            $dFrom = null;
            $dTo = null;
            
            if($this->dateFrom && $this->dateTo) {
                $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = Carbon::parse($this->dateTo)->endOfDay();
            }

            $sales = Sale::with(['customer', 'details', 'user', 'paymentDetails', 'changeDetails', 'returns'])
                ->when($this->searchFolio, function($q) {
                    $q->where('id', 'like', "%{$this->searchFolio}%")
                      ->orWhere('invoice_number', 'like', "%{$this->searchFolio}%");
                })
                ->when($dFrom && $dTo && !$this->searchFolio, function($q) use ($dFrom, $dTo) {
                    $q->whereBetween('created_at', [$dFrom, $dTo]);
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                })
                ->when($this->customer != null, function ($query) {
                     $query->where('customer_id', $this->customer['id']);
                })
                ->when($this->type != 0, function ($qry) {
                    $qry->where('type', $this->type);
                })
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);

            // Calculate totals
             $salesQuery = Sale::when($this->searchFolio, function($q) {
                    $q->where('id', 'like', "%{$this->searchFolio}%")
                      ->orWhere('invoice_number', 'like', "%{$this->searchFolio}%");
                })
                ->when($dFrom && $dTo && !$this->searchFolio, function($q) use ($dFrom, $dTo) {
                    $q->whereBetween('created_at', [$dFrom, $dTo]);
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                })
                ->when($this->customer != null, function ($query) {
                     $query->where('customer_id', $this->customer['id']);
                })
                ->when($this->type != 0, function ($qry) {
                    $qry->where('type', $this->type);
                })
                ->where('status', '<>', 'returned');

            $totalSale = $salesQuery->sum('total_usd');
            
            // Use the actual collection to calculate accurate net totals for the header
            $allSales = $salesQuery->with('returns')->get();
            $netTotalUSD = $allSales->sum(function($s) {
                $retUSD = $s->returns->where('status', 'approved')->sum(function($r) use ($s) {
                    $rate = $s->primary_exchange_rate > 0 ? $s->primary_exchange_rate : 1;
                    return $r->total_returned / $rate;
                });
                return $s->total_usd - $retUSD;
            });

            $this->totales = $netTotalUSD;

            // Calculate Total Cost (Netted by returns)
            $totalCostQuery = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->join('products', 'sale_details.product_id', '=', 'products.id')
                ->join('customers', 'sales.customer_id', '=', 'customers.id') 
                ->when($this->searchFolio, function($q) {
                    $q->where('sales.id', 'like', "%{$this->searchFolio}%")
                      ->orWhere('sales.invoice_number', 'like', "%{$this->searchFolio}%");
                })
                ->when($dFrom && $dTo && !$this->searchFolio, function($q) use ($dFrom, $dTo) {
                    $q->whereBetween('sales.created_at', [$dFrom, $dTo]);
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('sales.user_id', $this->user_id);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->where('customers.seller_id', $this->seller_id);
                })
                ->when($this->customer != null, function ($query) {
                     $query->where('sales.customer_id', $this->customer['id']);
                })
                ->when($this->type != 0, function ($qry) {
                    $qry->where('sales.type', $this->type);
                })
                ->where('sales.status', '<>', 'returned');
            
            // Subtract returned quantities from total cost
            $totalCost = $totalCostQuery->sum(DB::raw('sale_details.quantity * products.cost'));
            
            $allReturns = \App\Models\SaleReturnDetail::whereIn('sale_return_id', function($q) use($dFrom, $dTo) {
                $q->select('id')->from('sale_returns')->where('status', 'approved')
                ->when($dFrom && $dTo, function($sq) use ($dFrom, $dTo) {
                    $sq->whereBetween('created_at', [$dFrom, $dTo]);
                });
            })->with('product')->get();

            $returnedCost = $allReturns->sum(function($rd) {
                return $rd->quantity_returned * ($rd->product->cost ?? 0);
            });

            $totalCost = $totalCost - $returnedCost;

            $profit = round($netTotalUSD, 4) - round($totalCost, 4);
            $margin = $netTotalUSD > 0 ? ($profit / $netTotalUSD) * 100 : 0;

            // Update Header
            $map = "TOTAL COSTO $" . number_format($totalCost, 2);
            $child = "TOTAL VENTA $" . number_format($netTotalUSD, 2);
            $rest = " GANANCIA: $" . number_format($profit, 2) . " / MARGEN: " . number_format($margin, 2) . "%";

            session(['map' => $map, 'child' => $child, 'rest' => $rest, 'pos' => 'Reporte de Ventas Diarias']);
            $this->dispatch('update-header', map: $map, child: $child, rest: $rest);

            return $sales;

        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar obtener el reporte \n {$th->getMessage()}");
            return [];
        }
    }



    public function generatePdf()
    {
        $dFrom = null;
        $dTo = null;
        if($this->dateFrom && $this->dateTo) {
            $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
            $dTo = Carbon::parse($this->dateTo)->endOfDay();
        }

        $sales = Sale::with(['customer', 'details', 'user', 'paymentDetails', 'changeDetails', 'returns'])
            ->when($this->searchFolio, function($q) {
                $q->where('id', 'like', "%{$this->searchFolio}%")
                  ->orWhere('invoice_number', 'like', "%{$this->searchFolio}%");
            })
            ->when($dFrom && $dTo && !$this->searchFolio, function($q) use ($dFrom, $dTo) {
                $q->whereBetween('created_at', [$dFrom, $dTo]);
            })
            ->when($this->user_id != null && $this->user_id != 0, function ($query) {
                $query->where('user_id', $this->user_id);
            })
            ->when($this->seller_id != null && $this->seller_id != 0, function ($query) {
                $query->whereHas('customer', function($q) {
                    $q->where('seller_id', $this->seller_id);
                });
            })
            ->when($this->customer != null, function ($query) {
                    $query->where('customer_id', $this->customer['id']);
            })
            ->when($this->type != 0, function ($qry) {
                $qry->where('type', $this->type);
            })
            ->where('status', '<>', 'returned')
            ->orderBy('id', 'desc')
            ->get();

        $data = [];
        if ($this->groupBy == 'none') {
            $data['ALL'] = ['name' => 'TODOS', 'sales' => $sales, 'total_usd' => $sales->sum('total_usd')];
        } else {
            foreach ($sales as $sale) {
                $key = ''; $name = '';
                if ($this->groupBy == 'customer_id') {
                    $key = $sale->customer_id; $name = $sale->customer->name;
                } elseif ($this->groupBy == 'user_id') {
                    $key = $sale->user_id; $name = $sale->user->name;
                } elseif ($this->groupBy == 'seller_id') {
                    $key = $sale->customer->seller_id ?? 'NA';
                    $name = $sale->customer->seller->name ?? 'SIN VENDEDOR';
                } elseif ($this->groupBy == 'date') {
                    $key = $sale->created_at->format('Y-m-d'); $name = $sale->created_at->format('d/m/Y');
                }
                if (!isset($data[$key])) { $data[$key] = ['name' => $name, 'sales' => [], 'total_usd' => 0, 'net_total_usd' => 0]; }
                $data[$key]['sales'][] = $sale;
                $data[$key]['total_usd'] += $sale->total_usd;
                
                // Calculate Net for grouping
                $ret = $sale->returns->where('status', 'approved')->sum('total_returned');
                $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $data[$key]['net_total_usd'] += ($sale->total_usd - ($ret / $rate));
            }
        }

        $banks = \App\Models\Bank::all();
        $totalsByCategory = [];
        foreach($this->currencies as $c) { $totalsByCategory["EFECTIVO " . strtoupper($c->code)] = 0; }
        foreach($banks as $b) { $totalsByCategory[strtoupper($b->name)] = 0; }
        $totalsByCategory['ZELLE'] = 0;
        $totalsByCurrency = [];
        foreach($this->currencies as $c) { $totalsByCurrency[$c->code] = 0; }

        // Fetch Returns for the list table in PDF
        $returns = \App\Models\SaleReturn::with(['sale', 'requester', 'approver'])
            ->where('status', 'approved')
            ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                $q->whereBetween('created_at', [$dFrom, $dTo]);
            })
            ->get();

        // Fetch Deleted Sales
        $deletedSales = Sale::with(['customer', 'user', 'requester', 'approver'])
            ->whereNotNull('deletion_approved_at')
            ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                $q->whereBetween('deletion_approved_at', [$dFrom, $dTo]);
            })
            ->when($this->user_id && $this->user_id != 0, function ($query) {
                $query->where('user_id', $this->user_id);
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

        $totalNCUSD = 0;
        $totalWalletUsedUSD = 0; // Balance virtual usado hoy para pagar
        $totalWalletAddedUSD = 0; // Devoluciones a billetera hoy (Custodia)

        foreach ($sales as $sale) {
            $r_rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
            
            // 1. Sum approved returns for this sale
            // 1. Sum approved returns for this sale - USE FLEXIBLE FILTERING
            $returnsForSale = \App\Models\SaleReturn::where('sale_id', (string)$sale->id)
                ->where(function($q) {
                    $q->where('status', 'approved')
                      ->orWhere('status', 'aprobado')
                      ->orWhere('status', 'APPROVED');
                })
                ->get();
            
            $retAmtUSD = 0;
            foreach ($returnsForSale as $r) {
                // Determine rate based on sale
                $rt_rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $retAmtUSD += ($r->total_returned / $rt_rate);
            }
            
            $netSaleUSD = $sale->total_usd - $retAmtUSD;
            $totalNCUSD += $retAmtUSD;
            
            // Track how much of today's NC went to Wallet (Custodia)
            foreach ($returnsForSale as $r) {
                $rt_rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                if (str_contains(strtolower($r->refund_method), 'wallet')) {
                    $totalWalletAddedUSD += ($r->total_returned / $rt_rate);
                }
            }

            // 2. Add to global summary
            $summary['total_bruto'] += round($netSaleUSD, 4);
            $summary['total_flete'] += round($sale->total_freight ?? 0, 4);
            
            $salePaidUSD = 0;
            
            // 3. Process Payments
            foreach($sale->paymentDetails as $payment) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                $amtUSD = $payment->amount / $rate;
                $salePaidUSD += $amtUSD;
                
                if(isset($totalsByCurrency[$payment->currency_code])) {
                    $totalsByCurrency[$payment->currency_code] += $payment->amount;
                }

                if ($payment->payment_method == 'wallet') {
                    $totalWalletUsedUSD += $amtUSD;
                    $totalsByCategory['PAGO BILLETERA'] = ($totalsByCategory['PAGO BILLETERA'] ?? 0) + $amtUSD;
                } else {
                    $salePaidUSD += $amtUSD;
                    if ($payment->payment_method == 'bank' || $payment->payment_method == 'deposit') {
                        $bankName = $payment->bank_name ?? 'BANCO';
                        $totalsByCategory[$bankName] = ($totalsByCategory[$bankName] ?? 0) + $amtUSD;
                    } elseif ($payment->payment_method == 'zelle') {
                        $totalsByCategory['ZELLE'] = ($totalsByCategory['ZELLE'] ?? 0) + $amtUSD;
                    } else {
                        $key = "EFECTIVO " . strtoupper($payment->currency_code);
                        $totalsByCategory[$key] = ($totalsByCategory[$key] ?? 0) + $amtUSD;
                    }
                }
            }
            
            // 4. Subtract Changes (Vueltos)
            foreach($sale->changeDetails as $change) {
                $rateC = $change->exchange_rate > 0 ? $change->exchange_rate : 1;
                $amtUSD_C = $change->amount / $rateC;
                $salePaidUSD -= $amtUSD_C;

                if(isset($totalsByCurrency[$change->currency_code])) {
                    $totalsByCurrency[$change->currency_code] -= $change->amount;
                }

                $keyC = "EFECTIVO " . strtoupper($change->currency_code);
                $totalsByCategory[$keyC] = ($totalsByCategory[$keyC] ?? 0) - $amtUSD_C;
            }

            // 5. Handle Direct Cash Sales (Legacy)
            if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                $code = $sale->primary_currency_code ?? 'USD';
                $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $amtPaidNetUSD = ($sale->cash - $sale->change) / $rate;
                $salePaidUSD += $amtPaidNetUSD;
                
                if(isset($totalsByCurrency[$code])) { $totalsByCurrency[$code] += ($sale->cash - $sale->change); }
                $key = "EFECTIVO " . strtoupper($code);
                $totalsByCategory[$key] = ($totalsByCategory[$key] ?? 0) + $amtPaidNetUSD;
            }

            // 6. Final Income Categorization
            $vedUSD = 0;
            foreach($sale->paymentDetails as $p) {
                if($p->currency_code == 'VED' || $p->currency_code == 'VES') {
                    $r = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                    $vedUSD += ($p->amount / $r);
                }
            }
            foreach($sale->changeDetails as $c) {
                if($c->currency_code == 'VED' || $c->currency_code == 'VES') {
                    $r = $c->exchange_rate > 0 ? $c->exchange_rate : 1;
                    $vedUSD -= ($c->amount / $r);
                }
            }
            $summary['total_ved'] += $vedUSD;
            $summary['total_divisa'] += ($salePaidUSD - $vedUSD);

            $summary['total_contado'] += $salePaidUSD;
            if($sale->status != 'paid') {
                $summary['total_credito'] += max(0, $netSaleUSD - $salePaidUSD);
            }
        }

        // Final Summary Adjustments for the TOP BLOCK
        $summary['total_wallet_added'] = $totalWalletAddedUSD;
        $summary['total_nc_raw'] = $totalNCUSD;
        $summary['total_wallet_used'] = $totalWalletUsedUSD;

        // total_bruto is already calculated as Net (Sale - Return) in the loop above.
        $summary['total_contado'] = $summary['total_bruto']; 
        $summary['total_divisa'] = ($summary['total_contado'] - $summary['total_ved']);
        $summary['total_final'] = $summary['total_contado'] + $summary['total_flete'] + $summary['total_credito'];

        // 3. Final Segregation: Subtract ALL returns from physical totals to get Net Physical Flow
        foreach ($returns as $ret) {
            if (!$ret->sale) continue;
            $saleCurrCode = strtoupper($ret->sale->primary_currency_code ?? 'USD');
            $rt_rate = $ret->sale->primary_exchange_rate > 0 ? $ret->sale->primary_exchange_rate : 1;
            $retAmtUSD = ($ret->total_returned / $rt_rate) * $primaryRate;

            // Subtract from Physical Original Currency tracker
            if (isset($totalsByCurrencyPhys[$saleCurrCode])) {
                $totalsByCurrencyPhys[$saleCurrCode] -= $ret->total_returned;
            }

            // Subtract from Category Breakdown (we assume returns affect Cash/EFECTIVO by default)
            $key = "EFECTIVO " . $saleCurrCode;
            if (isset($totalsByCategory[$key])) {
                $totalsByCategory[$key] -= $retAmtUSD;
            } else {
                $totalsByCategory['EFECTIVO USD'] = ($totalsByCategory['EFECTIVO USD'] ?? 0) - $retAmtUSD;
            }
        }

        // 4. Segregation of Custody: Re-label Today's NC-to-wallet as Custody
        if ($totalWalletAddedUSD > 0.0001) {
            $totalsByCategory['BILLETERA (CUSTODIA HOY)'] = $totalWalletAddedUSD;
        }

        $salesSubtotal = 0;
        foreach ($totalsByCategory as $k => $v) {
            if (!str_contains($k, 'BILLETERA') && !str_contains($k, 'PAGO BILLETERA')) {
                $salesSubtotal += $v;
            }
        }
        
        // Total Deliver = Net Physical Sales + Today's Custody
        $grandTotalIncomeUSD = $salesSubtotal + $totalWalletAddedUSD;


        $config = \App\Models\Configuration::first();
        $user = auth()->user();

        $pdf = Pdf::loadView('reports.daily-sales-report-new-pdf', [
            'data' => $data,
            'summary' => $summary,
            'returns' => $returns,
            'deletedSales' => $deletedSales,
            'totalDeleted' => $totalDeleted,
            'totalsByCategory' => $totalsByCategory,
            'totalsByCurrency' => $totalsByCurrency,
            'totalsByCurrencyPhys' => $totalsByCurrencyPhys,
            'grandTotalIncomeUSD' => $grandTotalIncomeUSD,
            'config' => $config,
            'user' => $user,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'groupBy' => $this->groupBy
        ])->setPaper('a4', 'landscape');


        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Reporte_Ventas_Diarias.pdf');
    }

    public function openPdfPreview()
    {
        $params = [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'user_id' => $this->user_id,
            'seller_id' => $this->seller_id,
            'customer_id' => $this->customer ? $this->customer['id'] : null,
            'type' => $this->type,
            'searchFolio' => $this->searchFolio,
            'reportFormat' => $this->reportFormat,
            'includeDetails' => $this->includeDetails,
            'groupBy' => $this->groupBy,
        ];

        $this->pdfUrl = route('reports.daily.sales.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }
}
