<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Sale;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class PaymentRelationshipReport extends Component
{
    use WithPagination;

    public $pagination = 10, $customer, $dateFrom, $dateTo, $showReport = false;
    public $totales = 0;
    public $sellers = [], $seller_id;
    public $users = [], $user_id; // New properties

    function mount()
    {
        session()->forget('relationship_customer');
        $this->sellers = \App\Models\User::role('Vendedor')->orderBy('name')->get();
        $this->users = \App\Models\User::orderBy('name')->get(); // Load users
        session(['map' => "TOTAL COSTO $0.00", 'child' => 'TOTAL VENTA $0.00', 'rest' => 'GANANCIA: $0.00 / MARGEN: 0.00%', 'pos' => 'Reporte Relación de Pagos']);
    }

    public function render()
    {
        $this->customer = session('relationship_customer', null);

        return view('livewire.reports.payment-relationship-report', [
            'sales' => $this->getReport()
        ]);
    }

    #[On('relationship_customer')]
    function setCustomer($customer)
    {
        session(['relationship_customer' => $customer]);
        $this->customer = $customer;
    }

    function getReport()
    {
        if (!$this->showReport) return [];

        // if ($this->customer == null && $this->dateFrom == null && $this->dateTo == null && $this->seller_id == null) {
        //     $this->dispatch('noty', msg: 'SELECCIONA LOS FILTROS PARA CONSULTAR');
        //     return [];
        // }

        if (($this->dateFrom != null && $this->dateTo == null) || ($this->dateFrom == null && $this->dateTo != null)) {
            $this->dispatch('noty', msg: 'SELECCIONA AMBAS FECHAS (DESDE Y HASTA)');
            return [];
        }

        try {
            $query = Sale::with(['customer', 'payments'])
                ->where('type', 'credit')
                ->when($this->customer != null, function ($query) {
                    $query->where('customer_id', $this->customer['id']);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                });

            if ($this->dateFrom != null && $this->dateTo != null) {
                $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = Carbon::parse($this->dateTo)->endOfDay();
                $query->whereBetween('created_at', [$dFrom, $dTo]);
            }

            $sales = $query->orderBy('id', 'desc')->paginate($this->pagination);

            // Calculate Totals for Header
            $salesQuery = Sale::where('type', 'credit')
                ->when($this->customer != null, function ($query) {
                    $query->where('customer_id', $this->customer['id']);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                });

            if ($this->dateFrom != null && $this->dateTo != null) {
                $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = Carbon::parse($this->dateTo)->endOfDay();
                $salesQuery->whereBetween('created_at', [$dFrom, $dTo]);
            }

            $totalSale = $salesQuery->sum('total');
            
            // Calculate Total Cost
            $totalCostQuery = \Illuminate\Support\Facades\DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->join('products', 'sale_details.product_id', '=', 'products.id')
                ->join('customers', 'sales.customer_id', '=', 'customers.id') 
                ->where('sales.type', 'credit')
                ->when($this->customer != null, function ($query) {
                     $query->where('sales.customer_id', $this->customer['id']);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->where('customers.seller_id', $this->seller_id);
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('sales.user_id', $this->user_id);
                });

            if ($this->dateFrom != null && $this->dateTo != null) {
                $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = Carbon::parse($this->dateTo)->endOfDay();
                $totalCostQuery->whereBetween('sales.created_at', [$dFrom, $dTo]);
            }

            $totalCost = $totalCostQuery->sum(\Illuminate\Support\Facades\DB::raw('sale_details.quantity * products.cost'));

            $profit = $totalSale - $totalCost;
            $margin = $totalSale > 0 ? ($profit / $totalSale) * 100 : 0;

            // Update Header
            $map = "TOTAL COSTO $" . number_format($totalCost, 2);
            $child = "TOTAL VENTA $" . number_format($totalSale, 2);
            $rest = " GANANCIA: $" . number_format($profit, 2) . " / MARGEN: " . number_format($margin, 2) . "%";

            session(['map' => $map, 'child' => $child, 'rest' => $rest, 'pos' => 'Reporte Relación de Pagos']);
            $this->dispatch('update-header', map: $map, child: $child, rest: $rest);

            return $sales;

        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar obtener el reporte \n {$th->getMessage()}");
            return [];
        }
    }

    public $groupBy = 'customer_id'; // Default group by

    function generatePdf()
    {
        if ($this->customer == null && $this->dateFrom == null && $this->dateTo == null && $this->seller_id == null && $this->user_id == null) {
             $query = Sale::with(['customer', 'payments'])->where('type', 'credit');
        } else {
            $query = Sale::with(['customer', 'payments'])
                ->where('type', 'credit')
                ->when($this->customer != null, function ($query) {
                    $query->where('customer_id', $this->customer['id']);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                });

            if ($this->dateFrom != null && $this->dateTo != null) {
                $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = Carbon::parse($this->dateTo)->endOfDay();
                $query->whereBetween('created_at', [$dFrom, $dTo]);
            }
        }

        $sales = $query->orderBy('id', 'desc')->get();

        if ($sales->isEmpty()) {
            $this->dispatch('noty', msg: 'NO HAY DATOS PARA GENERAR EL REPORTE');
            return;
        }

        // Group data
        $data = [];
        $grandTotalDebt = 0;

        foreach ($sales as $sale) {
             $totalPaidUSD = $sale->payments->sum(function($payment) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                return $payment->amount / $rate;
            });
            
            $totalUSD = $sale->total_usd;
            if (!$totalUSD || $totalUSD == 0) {
                $exchangeRate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $totalUSD = $sale->total / $exchangeRate;
            }
            
            $balanceUSD = $totalUSD - $totalPaidUSD;
            if($balanceUSD < 0) $balanceUSD = 0;

            // Prepare payments data
            $paymentsData = [];
            foreach($sale->payments as $payment) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                $amountUSD = $payment->amount / $rate;
                
                $paymentsData[] = [
                    'date' => Carbon::parse($payment->created_at)->format('d/m/Y'),
                    'method' => $payment->pay_way,
                    'currency' => $payment->currency,
                    'amount_original' => $payment->amount,
                    'rate' => $rate,
                    'amount_usd' => $amountUSD
                ];
            }

            // Grouping Logic
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
            } else {
                $key = 'ALL';
                $name = 'TODOS';
            }

            if (!isset($data[$key])) {
                $data[$key] = [
                    'name' => $name,
                    'invoices' => [],
                    'total_debt' => 0
                ];
            }

            $data[$key]['invoices'][] = [
                'folio' => $sale->invoice_number ?? $sale->id,
                'date' => Carbon::parse($sale->created_at)->format('d/m/Y'),
                'due_date' => Carbon::parse($sale->created_at)->addDays(30)->format('d/m/Y'),
                'total' => $totalUSD,
                'paid' => $totalPaidUSD,
                'balance' => $balanceUSD,
                'status' => $sale->status,
                'payments' => $paymentsData
            ];
            
            $data[$key]['total_debt'] += $balanceUSD;
            $grandTotalDebt += $balanceUSD;
        }

        $config = \App\Models\Configuration::first();
        $user = Auth()->user();
        $date = Carbon::now()->format('d/m/Y H:i');
        $groupBy = $this->groupBy;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.payment-relationship-pdf', compact('data', 'config', 'user', 'date', 'groupBy', 'grandTotalDebt'));
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Relacion_Pagos_' . Carbon::now()->format('YmdHis') . '.pdf');
    }
}
