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

    function mount()
    {
        session()->forget('daily_sale_customer');
        session(['map' => "TOTAL COSTO $0.00", 'child' => 'TOTAL VENTA $0.00', 'rest' => 'GANANCIA: $0.00 / MARGEN: 0.00%', 'pos' => 'Reporte de Ventas Diarias']);

        $this->users = User::orderBy('name')->get();
        $this->sellers = User::role('Vendedor')->orderBy('name')->get();
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

            $sales = Sale::with(['customer', 'details', 'user', 'paymentDetails', 'changeDetails'])
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

            $totalSale = $salesQuery->sum('total');
            $this->totales = $totalSale;

            // Calculate Total Cost
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
                
            $totalCost = $totalCostQuery->sum(DB::raw('sale_details.quantity * products.cost'));

            $profit = $totalSale - $totalCost;
            $margin = $totalSale > 0 ? ($profit / $totalSale) * 100 : 0;

            // Update Header
            $map = "TOTAL COSTO $" . number_format($totalCost, 2);
            $child = "TOTAL VENTA $" . number_format($totalSale, 2);
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

        $sales = Sale::with(['customer', 'details', 'user', 'paymentDetails', 'changeDetails'])
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
            ->where('status', '<>', 'returned')
            ->orderBy('id', 'desc')
            ->get();

        $data = [];
        $totalNeto = 0;
        $totalCredit = 0;
        $totalPaidPerCurrency = [];

        foreach($this->currencies as $currency) {
            $totalPaidPerCurrency[$currency->code] = 0;
        }

        if ($this->groupBy == 'none') {
            $data['ALL'] = [
                'name' => 'TODOS',
                'sales' => $sales,
                'total_usd' => $sales->sum('total_usd')
            ];
        } else {
            foreach ($sales as $sale) {
                $key = '';
                $name = '';

                if ($this->groupBy == 'customer_id') {
                    $key = $sale->customer_id;
                    $name = $sale->customer->name;
                } elseif ($this->groupBy == 'user_id') {
                    $key = $sale->user_id;
                    $name = $sale->user->name;
                } elseif ($this->groupBy == 'seller_id') {
                    $key = $sale->customer->seller_id ?? 'NA';
                    $name = $sale->customer->seller->name ?? 'SIN VENDEDOR';
                } elseif ($this->groupBy == 'date') {
                    $key = $sale->created_at->format('Y-m-d');
                    $name = $sale->created_at->format('d/m/Y');
                }

                if (!isset($data[$key])) {
                    $data[$key] = [
                        'name' => $name,
                        'sales' => [],
                        'total_usd' => 0
                    ];
                }
                
                $data[$key]['sales'][] = $sale;
                $data[$key]['total_usd'] += $sale->total_usd;
            }
        }

        // Calculate Grand Totals for Columns
        $totalNeto = 0;
        $totalCredit = 0;
        $totalPaidPerCurrency = [];
        $totalPaidPerBank = [];
        
        $banks = \App\Models\Bank::orderBy('name')->get();

        foreach($this->currencies as $currency) {
            $totalPaidPerCurrency[$currency->code] = 0;
        }
        foreach($banks as $bank) {
            $totalPaidPerBank[$bank->id] = 0;
        }

        $totalPaidPerCurrencySummarized = [];
        foreach($this->currencies as $currency) {
            $totalPaidPerCurrencySummarized[$currency->code] = 0;
        }

        foreach ($sales as $sale) {
            $totalNeto += $sale->total;
            
            $totalPaidUSD = 0;
            foreach($sale->paymentDetails as $payment) {
                if($payment->method == 'bank' && $payment->bank_id) {
                    if(isset($totalPaidPerBank[$payment->bank_id])) {
                        $totalPaidPerBank[$payment->bank_id] += $payment->amount;
                    }
                } elseif(isset($totalPaidPerCurrency[$payment->currency_code])) {
                    $totalPaidPerCurrency[$payment->currency_code] += $payment->amount;
                }

                // Summarized Total (All payments by currency)
                if(isset($totalPaidPerCurrencySummarized[$payment->currency_code])) {
                    $totalPaidPerCurrencySummarized[$payment->currency_code] += $payment->amount;
                }

                 $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                $totalPaidUSD += ($payment->amount / $rate);
            }
            
            if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                $code = $sale->primary_currency_code ?? 'VED';
                 if(isset($totalPaidPerCurrency[$code])) {
                    $totalPaidPerCurrency[$code] += $sale->cash;
                }
                if(isset($totalPaidPerCurrencySummarized[$code])) {
                    $totalPaidPerCurrencySummarized[$code] += $sale->cash;
                }
                 $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $totalPaidUSD += ($sale->cash / $rate);
            }
            
             if($sale->status != 'paid' && $sale->status != 'returned') {
                $totalCredit += max(0, $sale->total_usd - $totalPaidUSD);
            }
        }

        $pdf = Pdf::loadView('reports.daily-sales-report-pdf', [
            'data' => $data,
            'currencies' => $this->currencies,
            'banks' => $banks,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'groupBy' => $this->groupBy,
            'totalNeto' => $totalNeto,
            'totalCredit' => $totalCredit,
            'totalPaidPerCurrency' => $totalPaidPerCurrency,
            'totalPaidPerCurrencySummarized' => $totalPaidPerCurrencySummarized,
            'totalPaidPerBank' => $totalPaidPerBank,
            'reportFormat' => $this->reportFormat,
            'includeDetails' => $this->includeDetails
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Reporte_Ventas_Diarias.pdf');
    }
}
