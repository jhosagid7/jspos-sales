<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Sale;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class SalesAnalysisReport extends Component
{
    public $selectedSellers = [];
    public $periodType = 'monthly'; // daily, weekly, biweekly, monthly, yearly
    public $dateFrom = '';
    public $dateTo = '';
    public $metric = 'amount'; // amount, count, commission, net_sales
    public $showReport = false;
    public $showPdfModal = false;
    public $pdfUrl = '';

    public function mount()
    {
        session(['map' => '', 'child' => '', 'rest' => '', 'pos' => 'AnÃ¡lisis de Ventas']);
        $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function searchData()
    {
        $this->showReport = true;
        
        $chartData = $this->getChartData();
        $this->dispatch('updateChart', labels: $chartData['labels'], datasets: $chartData['datasets']);
        $this->dispatch('noty', msg: 'ANÃLISIS DE VENTAS ACTUALIZADO');
    }

    public function updated($propertyName)
    {
        if ($this->showReport) {
            $chartData = $this->getChartData();
            $this->dispatch('updateChart', labels: $chartData['labels'], datasets: $chartData['datasets']);
        }
    }

    public function openPdfPreview()
    {
        $params = [
            'selectedSellers' => implode(',', $this->selectedSellers),
            'periodType' => $this->periodType,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'metric' => $this->metric,
        ];

        $this->pdfUrl = route('reports.sales.analysis.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    /**
     * Get aggregated sales data grouped by selected period type.
     */
    public function getChartData()
    {
        $dateFrom = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null;
        $dateTo = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null;

        $selectExpression = "";
        if ($this->periodType === 'daily') {
            $selectExpression = "DATE_FORMAT(sales.created_at, '%Y-%m-%d')";
        } elseif ($this->periodType === 'weekly') {
            $selectExpression = "DATE_FORMAT(DATE_SUB(sales.created_at, INTERVAL WEEKDAY(sales.created_at) DAY), '%Y-%m-%d')";
        } elseif ($this->periodType === 'biweekly') {
            $selectExpression = "CASE WHEN DAY(sales.created_at) <= 15 THEN CONCAT(DATE_FORMAT(sales.created_at, '%Y-%m'), '-01') ELSE CONCAT(DATE_FORMAT(sales.created_at, '%Y-%m'), '-16') END";
        } elseif ($this->periodType === 'yearly') {
            $selectExpression = "CAST(YEAR(sales.created_at) AS CHAR)";
        } else { // monthly
            $selectExpression = "DATE_FORMAT(sales.created_at, '%Y-%m')";
        }

        // Subquery or join with customers to filter by their assigned seller
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

        if (!empty($this->selectedSellers)) {
            $query->whereIn('customers.seller_id', $this->selectedSellers);
        }

        $results = $query->groupBy(DB::raw("$selectExpression"))
            ->orderBy('period_label')
            ->get();

        // Transform period labels to human readable Spanish format
        $results->transform(function ($row) {
            $row->raw_period = $row->period_label;
            $dt = Carbon::parse(explode('-', $row->period_label)[0] === $row->period_label ? $row->period_label . '-01-01' : $row->period_label);
            $monthName = strtoupper($dt->locale('es')->monthName);

            if ($this->periodType === 'daily') {
                $row->period_label = $dt->format('d/m/Y');
            } elseif ($this->periodType === 'weekly') {
                $weekNumber = sprintf('%02d', $dt->weekOfYear);
                $row->period_label = "{$dt->year}-{$monthName}-{$dt->day}-S{$weekNumber}";
            } elseif ($this->periodType === 'biweekly') {
                $fortnight = $dt->day <= 15 ? 'Q1' : 'Q2';
                $row->period_label = "{$dt->year}-{$monthName}-{$fortnight}";
            } elseif ($this->periodType === 'monthly') {
                $row->period_label = "{$dt->year}-{$monthName}";
            } else { // yearly
                $row->period_label = "{$dt->year}";
            }
            return $row;
        });

        // Sort results collection by raw_period to ensure perfect chronological order
        $results = $results->sortBy('raw_period')->values();

        // Prepare chart structure
        $labels = $results->pluck('period_label')->toArray();
        $dataValues = [];

        foreach ($results as $row) {
            if ($this->metric === 'count') {
                $dataValues[] = (int)$row->sales_count;
            } elseif ($this->metric === 'commission') {
                $dataValues[] = (float)$row->total_commission;
            } elseif ($this->metric === 'net_sales') {
                $dataValues[] = (float)$row->net_sales;
            } else { // amount
                $dataValues[] = (float)$row->total_amount;
            }
        }

        $metricLabel = match ($this->metric) {
            'count' => 'Cantidad de Facturas',
            'commission' => 'Comisiones USD',
            'net_sales' => 'Ventas Netas (Margen)',
            default => 'Monto Ventas USD',
        };

        $datasets = [[
            'name' => $metricLabel,
            'data' => $dataValues
        ]];

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'results' => $results
        ];
    }

    /**
     * Compute core metrics and period-over-period growth indicators.
     */
    public function getSummaryKpis()
    {
        $dateFrom = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null;
        $dateTo = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null;

        // Current period metrics
        $currentQuery = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.status', '<>', 'returned')
            ->whereNull('sales.deletion_approved_at')
            ->when($dateFrom, fn($q) => $q->where('sales.created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('sales.created_at', '<=', $dateTo));

        if (!empty($this->selectedSellers)) {
            $currentQuery->whereIn('customers.seller_id', $this->selectedSellers);
        }

        $currentTotal = $currentQuery->sum('sales.total_usd');
        $currentCount = $currentQuery->count();
        $currentCommission = $currentQuery->sum('sales.final_commission_amount');
        $currentAvgTicket = $currentCount > 0 ? $currentTotal / $currentCount : 0;
        $currentNetSales = $currentTotal - $currentCommission;

        // Calculate previous period dates for comparison
        $growthPercent = 0;
        $growthClass = 'text-muted';
        $growthArrow = '';

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

            if (!empty($this->selectedSellers)) {
                $prevQuery->whereIn('customers.seller_id', $this->selectedSellers);
            }

            $prevTotal = $prevQuery->sum('sales.total_usd');

            if ($prevTotal > 0) {
                $growthPercent = (($currentTotal - $prevTotal) / $prevTotal) * 100;
            } else {
                $growthPercent = $currentTotal > 0 ? 100 : 0;
            }

            if ($growthPercent > 0) {
                $growthClass = 'text-success';
                $growthArrow = 'â†‘';
            } elseif ($growthPercent < 0) {
                $growthClass = 'text-danger';
                $growthArrow = 'â†“';
            }
        }

        return [
            'total_sales' => $currentTotal,
            'sales_count' => $currentCount,
            'avg_ticket' => $currentAvgTicket,
            'total_commission' => $currentCommission,
            'net_sales' => $currentNetSales,
            'growth_percent' => $growthPercent,
            'growth_class' => $growthClass,
            'growth_arrow' => $growthArrow,
        ];
    }

    public function getReportData()
    {
        if (!$this->showReport) {
            return [];
        }

        $chartData = $this->getChartData();
        $kpis = $this->getSummaryKpis();

        return [
            'labels' => $chartData['labels'],
            'datasets' => $chartData['datasets'],
            'results' => $chartData['results'],
            'kpis' => $kpis
        ];
    }

    public function render()
    {
        // Load all users who act as sellers (Office and Foreign sellers)
        $sellersList = User::sellers()->orderBy('name')->get();

        return view('livewire.reports.sales-analysis-report', [
            'sellersList' => $sellersList,
            'reportData' => $this->getReportData(),
        ]);
    }
}

