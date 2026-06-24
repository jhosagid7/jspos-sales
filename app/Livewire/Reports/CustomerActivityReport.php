<?php
 
namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Customer;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class CustomerActivityReport extends Component
{
    public $searchCustomer = '';
    public $selectedCustomers = [];
    public $periodType = 'monthly'; // weekly, monthly, quarterly, yearly
    public $dateFrom = '';
    public $dateTo = '';
    public $metric = 'amount'; // amount, count
    public $showReport = false;
    public $showPdfModal = false;
    public $pdfUrl = '';

    public function mount()
    {
        session(['pos' => 'Reporte de Actividad de Clientes']);
        $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function searchData()
    {
        if (empty($this->selectedCustomers)) {
            $this->dispatch('noty', msg: 'DEBE SELECCIONAR AL MENOS UN CLIENTE', type: 'error');
            return;
        }

        $this->showReport = true;
        
        $chartData = $this->getChartData();
        $this->dispatch('updateChart', labels: $chartData['labels'], datasets: $chartData['datasets']);
        $this->dispatch('noty', msg: 'REPORTE ACTUALIZADO');
    }

    public function openPdfPreview()
    {
        $params = [
            'selectedCustomers' => implode(',', $this->selectedCustomers),
            'periodType' => $this->periodType,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'metric' => $this->metric,
        ];

        $this->pdfUrl = route('reports.customer.activity.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    public function getChartData()
    {
        if (empty($this->selectedCustomers)) {
            return ['labels' => [], 'datasets' => []];
        }

        $dateFrom = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null;
        $dateTo = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null;

        $selectExpression = "";
        if ($this->periodType === 'weekly') {
            $selectExpression = "DATE_FORMAT(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY), '%Y-%m-%d')";
        } elseif ($this->periodType === 'quarterly') {
            $selectExpression = "CONCAT(YEAR(created_at), '-T', QUARTER(created_at))";
        } elseif ($this->periodType === 'yearly') {
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
            ->whereIn('customer_id', $this->selectedCustomers)
            ->where('status', '<>', 'returned')
            ->whereNull('deletion_approved_at')
            ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo))
            ->groupBy(['customer_id', DB::raw("$selectExpression")])
            ->orderBy('period_label')
            ->get();

        $results->transform(function ($row) {
            if ($this->periodType === 'weekly') {
                $dt = Carbon::parse($row->period_label);
                $monthName = strtoupper($dt->locale('es')->monthName);
                $weekNumber = sprintf('%02d', $dt->weekOfYear);
                $row->period_label = "{$dt->year}-{$monthName}-{$dt->day}-S{$weekNumber}";
            } elseif ($this->periodType === 'monthly') {
                $dt = Carbon::parse($row->period_label . '-01');
                $monthName = strtoupper($dt->locale('es')->monthName);
                $row->period_label = "{$dt->year}-{$monthName}";
            }
            return $row;
        });

        $labels = $results->pluck('period_label')->unique()->sort()->values()->toArray();
        $customers = Customer::whereIn('id', $this->selectedCustomers)->get();

        $datasets = [];
        $colors = [
            ['bg' => 'rgba(26, 35, 126, 0.1)', 'border' => 'rgba(26, 35, 126, 1)'],
            ['bg' => 'rgba(192, 57, 43, 0.1)', 'border' => 'rgba(192, 57, 43, 1)'],
            ['bg' => 'rgba(39, 174, 96, 0.1)', 'border' => 'rgba(39, 174, 96, 1)'],
            ['bg' => 'rgba(243, 156, 18, 0.1)', 'border' => 'rgba(243, 156, 18, 1)'],
            ['bg' => 'rgba(142, 68, 173, 0.1)', 'border' => 'rgba(142, 68, 173, 1)'],
            ['bg' => 'rgba(22, 160, 133, 0.1)', 'border' => 'rgba(22, 160, 133, 1)'],
        ];

        foreach ($customers as $index => $customer) {
            $customerData = [];
            foreach ($labels as $label) {
                $match = $results->first(fn($row) => $row->customer_id == $customer->id && $row->period_label == $label);
                if ($this->metric === 'count') {
                    $customerData[] = $match ? (int)$match->sales_count : 0;
                } else {
                    $customerData[] = $match ? (float)$match->total_amount : 0.0;
                }
            }
            
            $color = $colors[$index % count($colors)];

            $datasets[] = [
                'label' => $customer->name,
                'data' => $customerData,
                'backgroundColor' => $color['bg'],
                'borderColor' => $color['border'],
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0.3
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'results' => $results,
            'customers' => $customers
        ];
    }

    public function getKpis()
    {
        if (empty($this->selectedCustomers)) {
            return [];
        }

        $dateFrom = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null;
        $dateTo = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null;

        $kpis = [];
        $customers = Customer::whereIn('id', $this->selectedCustomers)->get();

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
                'last_purchase_at' => $lastSale ? Carbon::parse($lastSale->created_at)->format('d/m/Y') : 'Nunca ha comprado',
                'top_products' => $topProducts,
            ];
        }

        return $kpis;
    }

    public function getReportData()
    {
        if (!$this->showReport) {
            return [];
        }

        $chartData = $this->getChartData();
        $kpis = $this->getKpis();

        return [
            'labels' => $chartData['labels'],
            'datasets' => $chartData['datasets'],
            'kpis' => $kpis,
        ];
    }

    public function render()
    {
        // Filter customer list dynamically
        $customersQuery = Customer::orderBy('name');
        if (!empty($this->searchCustomer)) {
            $customersQuery->where('name', 'like', "%{$this->searchCustomer}%");
        }
        if (!empty($this->selectedCustomers)) {
            $customersQuery->orWhereIn('id', $this->selectedCustomers);
        }
        $customersList = $customersQuery->take(50)->get();

        return view('livewire.reports.customer-activity-report', [
            'customersList' => $customersList,
            'reportData' => $this->getReportData(),
        ]);
    }
}
