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

    public $pagination = 10, $users = [], $user_id, $dateFrom, $dateTo, $showReport = false, $type = 0;
    public $totales = 0;
    public $currencies = [];
    public $sellers = [], $seller_id;
    public $customer;

    function mount()
    {
        session()->forget('daily_sale_customer');
        session(['map' => "", 'child' => '', 'rest' => '', 'pos' => 'Reporte de Ventas Diarias']);

        $this->users = User::orderBy('name')->get();
        $this->sellers = User::role('Vendedor')->orderBy('name')->get();
        $this->currencies = \App\Models\Currency::orderBy('id')->get();
    }

    public function render()
    {
        $this->customer = session('daily_sale_customer', null);

        return view('livewire.reports.daily-sales-report', [
            'sales' => $this->getReport()
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
                ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
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

            // Calculate totals (similar logic to SalesReport if needed, or just for display)
            // For now, we'll keep it simple or copy the total calculation if requested.
            // The user asked for the PDF structure to match the table.
            
            // Re-using total calculation logic for the header summary
             $salesQuery = Sale::when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
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

            $this->totales = $salesQuery->sum('total');

            return $sales;

        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar obtener el reporte \n {$th->getMessage()}");
            return [];
        }
    }

    public $groupBy = 'none'; // Default group by

    public function generatePdf()
    {
        $dFrom = null;
        $dTo = null;
        
        if($this->dateFrom && $this->dateTo) {
            $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
            $dTo = Carbon::parse($this->dateTo)->endOfDay();
        }

        $sales = Sale::with(['customer', 'details', 'user', 'paymentDetails', 'changeDetails'])
            ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
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
        foreach ($sales as $sale) {
            $totalNeto += $sale->total_usd;
            
            $paidPerCurrency = [];
             foreach($this->currencies as $currency) {
                $paidPerCurrency[$currency->code] = 0;
            }

            $totalPaidUSD = 0;
            foreach($sale->paymentDetails as $payment) {
                if(isset($totalPaidPerCurrency[$payment->currency_code])) {
                    $totalPaidPerCurrency[$payment->currency_code] += $payment->amount;
                }
                 $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                $totalPaidUSD += ($payment->amount / $rate);
            }
            
            if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                $code = $sale->primary_currency_code ?? 'VED';
                 if(isset($totalPaidPerCurrency[$code])) {
                    $totalPaidPerCurrency[$code] += $sale->cash;
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
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'groupBy' => $this->groupBy,
            'totalNeto' => $totalNeto,
            'totalCredit' => $totalCredit,
            'totalPaidPerCurrency' => $totalPaidPerCurrency
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Reporte_Ventas_Diarias.pdf');
    }
}
