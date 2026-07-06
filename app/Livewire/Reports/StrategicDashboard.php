<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OperationalExpense;
use App\Livewire\Reports\CashFlowForecastReport;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StrategicDashboard extends Component
{
    public $selectedMonth;
    public $activeTab = 'growth';

    // OPEX Form Properties
    public $opexCategory = 'Nómina';
    public $opexAmount;
    public $opexDescription;
    public $availableCategories = ['Nómina', 'Alquiler', 'Servicios', 'Impuestos', 'Otros'];

    public function mount()
    {
        session(['pos' => 'Análisis Estratégico']);
        $this->selectedMonth = Carbon::today()->format('Y-m');
    }

    public function addOpex()
    {
        $this->validate([
            'opexCategory' => 'required|string',
            'opexAmount' => 'required|numeric|min:0.01',
            'opexDescription' => 'nullable|string|max:255',
        ]);

        OperationalExpense::create([
            'year_month' => $this->selectedMonth,
            'category' => $this->opexCategory,
            'amount' => $this->opexAmount,
            'description' => $this->opexDescription,
        ]);

        $this->reset(['opexAmount', 'opexDescription']);
        $this->dispatch('noty', msg: 'Gasto operativo registrado con éxito.');
    }

    public function deleteOpex($id)
    {
        $expense = OperationalExpense::find($id);
        if ($expense) {
            $expense->delete();
            $this->dispatch('noty', msg: 'Gasto operativo eliminado.');
        }
    }

    public function render()
    {
        if (!Auth::user()->can('reports.sales')) {
            abort(403, 'No tiene permisos para ver este reporte.');
        }

        $data = $this->getDashboardData();

        $this->dispatch('chart-updated');

        return view('livewire.reports.strategic-dashboard', $data)
            ->layout('layouts.theme.app');
    }

    private function getDashboardData()
    {
        $dt = Carbon::parse($this->selectedMonth . '-01');
        $year = $dt->year;
        $month = $dt->month;

        // Current Period Data
        $currentPeriod = $this->calculatePeriodMetrics($month, $year);

        // Previous Month Data
        $prevDt = $dt->copy()->subMonth();
        $prevPeriod = $this->calculatePeriodMetrics($prevDt->month, $prevDt->year);

        // Same Month Last Year Data
        $yearAgoDt = $dt->copy()->subYear();
        $yearAgoPeriod = $this->calculatePeriodMetrics($yearAgoDt->month, $yearAgoDt->year);

        // Calculate Weekly Breakdown for Current Month
        $weeklyBreakdown = $this->calculateWeeklyBreakdown($dt);

        // Calculate Current Patrimonio Neto (Wealth Snapshot)
        $patrimonyData = $this->calculateCurrentPatrimony();

        // Calculate Customer ABC Analysis (All time or current year to be meaningful)
        $abcData = $this->calculateCustomerABC($month, $year);

        // Calculate Product Margin Contrib (Current Month)
        $productMargins = $this->calculateProductMargins($month, $year);

        // Load OPEX List for the current selected month
        $opexList = OperationalExpense::where('year_month', $this->selectedMonth)
            ->orderBy('id', 'desc')
            ->get();

        return [
            'current' => $currentPeriod,
            'prev' => $prevPeriod,
            'yearAgo' => $yearAgoPeriod,
            'weeklyBreakdown' => $weeklyBreakdown,
            'patrimony' => $patrimonyData,
            'abc' => $abcData,
            'productMargins' => $productMargins,
            'opexList' => $opexList,
            'monthName' => strtoupper($dt->locale('es')->monthName) . ' ' . $year,
        ];
    }

    private function calculatePeriodMetrics($month, $year)
    {
        // 1. Gross Sales (USD)
        $grossSales = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereMonth('sales.created_at', $month)
            ->whereYear('sales.created_at', $year)
            ->whereNotIn('sales.status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->whereNull('sales.deletion_approved_at')
            ->where('products.is_raw_material', false)
            ->sum(DB::raw('sale_details.quantity * (sale_details.sale_price / COALESCE(NULLIF(sales.primary_exchange_rate, 0), 1))'));

        // 2. Returns (USD)
        $returns = DB::table('sale_return_details')
            ->join('sale_returns', 'sale_return_details.sale_return_id', '=', 'sale_returns.id')
            ->join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->join('products', 'sale_return_details.product_id', '=', 'products.id')
            ->whereMonth('sale_returns.created_at', $month)
            ->whereYear('sale_returns.created_at', $year)
            ->where('sale_returns.status', 'approved')
            ->where('products.is_raw_material', false)
            ->sum(DB::raw('sale_return_details.subtotal / COALESCE(NULLIF(sales.primary_exchange_rate, 0), 1)'));

        $netSales = max(0, $grossSales - $returns);

        // 3. Cost of Goods Sold (COGS)
        $cogs = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereNotIn('sales.status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->whereNull('sales.deletion_approved_at')
            ->whereMonth('sales.created_at', $month)
            ->whereYear('sales.created_at', $year)
            ->where('products.is_raw_material', false)
            ->sum(DB::raw('sale_details.quantity * COALESCE(products.cost, 0)'));

        $grossProfit = max(0, $netSales - $cogs);
        $grossMarginPercent = $netSales > 0 ? ($grossProfit / $netSales) * 100 : 0;

        // 4. OPEX (Operational Expenses)
        $yearMonth = sprintf('%04d-%02d', $year, $month);
        $opex = OperationalExpense::where('year_month', $yearMonth)->sum('amount');

        $netProfit = $grossProfit - $opex;
        $netMarginPercent = $netSales > 0 ? ($netProfit / $netSales) * 100 : 0;

        return [
            'grossSales' => $grossSales,
            'returns' => $returns,
            'netSales' => $netSales,
            'cogs' => $cogs,
            'grossProfit' => $grossProfit,
            'grossMarginPercent' => $grossMarginPercent,
            'opex' => $opex,
            'netProfit' => $netProfit,
            'netMarginPercent' => $netMarginPercent,
        ];
    }

    private function calculateWeeklyBreakdown($dt)
    {
        $startOfMonth = $dt->copy()->startOfMonth();
        $endOfMonth = $dt->copy()->endOfMonth();

        $daysByWeek = [];
        $currentDate = $startOfMonth->copy();
        while ($currentDate <= $endOfMonth) {
            $weekKey = $currentDate->format('o-W');
            if (!isset($daysByWeek[$weekKey])) {
                $daysByWeek[$weekKey] = [];
            }
            $daysByWeek[$weekKey][] = $currentDate->copy();
            $currentDate->addDay();
        }

        $weeks = [];
        $weekLabels = [];
        $salesData = [];
        $profitData = [];

        $weekIndex = 1;
        foreach ($daysByWeek as $weekKey => $days) {
            $start = collect($days)->min()->startOfDay();
            $end = collect($days)->max()->endOfDay();

            // Weekly Net Sales
            $grossSales = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->join('products', 'sale_details.product_id', '=', 'products.id')
                ->whereBetween('sales.created_at', [$start, $end])
                ->whereNotIn('sales.status', ['voided', 'cancelled', 'anulated', 'returned'])
                ->whereNull('sales.deletion_approved_at')
                ->where('products.is_raw_material', false)
                ->sum(DB::raw('sale_details.quantity * (sale_details.sale_price / COALESCE(NULLIF(sales.primary_exchange_rate, 0), 1))'));

            $returns = DB::table('sale_return_details')
                ->join('sale_returns', 'sale_return_details.sale_return_id', '=', 'sale_returns.id')
                ->join('sales', 'sale_returns.sale_id', '=', 'sales.id')
                ->join('products', 'sale_return_details.product_id', '=', 'products.id')
                ->whereBetween('sale_returns.created_at', [$start, $end])
                ->where('sale_returns.status', 'approved')
                ->where('products.is_raw_material', false)
                ->sum(DB::raw('sale_return_details.subtotal / COALESCE(NULLIF(sales.primary_exchange_rate, 0), 1)'));

            $netSales = max(0, $grossSales - $returns);

            // Weekly COGS
            $cogs = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->join('products', 'sale_details.product_id', '=', 'products.id')
                ->whereNotIn('sales.status', ['voided', 'cancelled', 'anulated', 'returned'])
                ->whereNull('sales.deletion_approved_at')
                ->whereBetween('sales.created_at', [$start, $end])
                ->where('products.is_raw_material', false)
                ->sum(DB::raw('sale_details.quantity * COALESCE(products.cost, 0)'));

            $grossProfit = max(0, $netSales - $cogs);

            $weekLabels[] = "Semana " . $weekIndex . " (" . $start->format('d/m') . ")";
            $salesData[] = round($netSales, 2);
            $profitData[] = round($grossProfit, 2);

            $weekIndex++;
        }

        return [
            'labels' => $weekLabels,
            'sales' => $salesData,
            'profit' => $profitData,
        ];
    }

    private function calculateCurrentPatrimony()
    {
        // 1. Inventory Value (cost * stock)
        $inventoryValue = Product::where('status', 'available')
            ->where('is_raw_material', false)
            ->get()
            ->sum(function($p) {
                return $p->stock_qty * ($p->cost ?? 0);
            });

        // 2. Accounts Receivable (CxC)
        $creditSales = Sale::where('type', 'credit')
            ->whereNotIn('status', ['returned', 'voided', 'cancelled', 'anulated'])
            ->whereNull('deletion_approved_at')
            ->get();
        
        $totalCxC = $creditSales->sum(function($sale) {
            return CashFlowForecastReport::calculateSaleDebtUsd($sale);
        });

        // 3. Accounts Payable (CxP)
        $purchases = Purchase::where('type', 'credit')
            ->whereNotIn('status', ['paid', 'returned', 'voided'])
            ->get();
        
        $totalCxP = $purchases->sum(function($p) {
            return $p->debt;
        });

        // 4. Cash and Banks Ledger Balance (cumulative inflows - outflows)
        $totalInflowsSales = DB::table('sale_payment_details')
            ->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));

        $totalInflowsStandalone = DB::table('payments')
            ->where('status', 'approved')
            ->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));

        $totalOutflowsChange = DB::table('sale_change_details')
            ->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));

        $totalOutflowsPayables = DB::table('payables')
            ->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));

        $totalOutflowsOpex = DB::table('operational_expenses')
            ->sum('amount');

        $totalCash = ($totalInflowsSales + $totalInflowsStandalone) - ($totalOutflowsChange + $totalOutflowsPayables + $totalOutflowsOpex);
        if ($totalCash < 0) {
            $totalCash = 0.0;
        }

        $netEquity = ($inventoryValue + $totalCxC + $totalCash) - $totalCxP;

        // Calculate historical equity trend (last 6 months)
        $historyLabels = [];
        $historyEquity = [];
        for ($i = 5; $i >= 0; $i--) {
            $hDt = Carbon::today()->subMonths($i);
            $historyLabels[] = strtoupper($hDt->locale('es')->monthName);
            
            // Simple model to estimate historical equity
            // Since daily historical stock tables aren't present, we extrapolate equity backwards
            // based on monthly profits and opex.
            $historyEquity[] = max(0, $netEquity - $this->estimateEquityDeltaForMonths($i));
        }

        return [
            'inventoryValue' => $inventoryValue,
            'totalCxC' => $totalCxC,
            'totalCxP' => $totalCxP,
            'totalCash' => $totalCash,
            'netEquity' => $netEquity,
            'historyLabels' => $historyLabels,
            'historyEquity' => $historyEquity,
        ];
    }

    private function estimateEquityDeltaForMonths($monthsAgo)
    {
        if ($monthsAgo == 0) return 0;
        
        $delta = 0;
        for ($i = 0; $i < $monthsAgo; $i++) {
            $dt = Carbon::today()->subMonths($i);
            $metrics = $this->calculatePeriodMetrics($dt->month, $dt->year);
            // Profit added to net equity
            $delta += $metrics['netProfit'];
        }
        return $delta;
    }

    private function calculateCustomerABC($month, $year)
    {
        // Calculate utility contribution of all customers in the selected month
        $customersData = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select(
                'customers.id',
                'customers.name',
                DB::raw('SUM(sale_details.quantity * (sale_details.sale_price / COALESCE(NULLIF(sales.primary_exchange_rate, 0), 1))) as sales_usd'),
                DB::raw('SUM(sale_details.quantity * (sale_details.sale_price / COALESCE(NULLIF(sales.primary_exchange_rate, 0), 1) - COALESCE(products.cost, 0))) as profit_usd')
            )
            ->whereNotIn('sales.status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->whereNull('sales.deletion_approved_at')
            ->whereMonth('sales.created_at', $month)
            ->whereYear('sales.created_at', $year)
            ->where('products.is_raw_material', false)
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('profit_usd')
            ->get();

        $totalProfit = $customersData->sum('profit_usd');
        if ($totalProfit <= 0) return ['A' => [], 'B' => [], 'C' => []];

        $runningSum = 0;
        $classA = [];
        $classB = [];
        $classC = [];

        foreach ($customersData as $customer) {
            $runningSum += $customer->profit_usd;
            $percent = ($runningSum / $totalProfit) * 100;

            $cData = [
                'name' => $customer->name,
                'sales' => $customer->sales_usd,
                'profit' => $customer->profit_usd,
                'accum_percent' => $percent,
            ];

            if ($percent <= 80) {
                $classA[] = $cData;
            } elseif ($percent <= 95) {
                $classB[] = $cData;
            } else {
                $classC[] = $cData;
            }
        }

        return [
            'A' => $classA,
            'B' => $classB,
            'C' => $classC,
        ];
    }

    private function calculateProductMargins($month, $year)
    {
        $products = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->select(
                'products.sku',
                'products.name',
                DB::raw('SUM(sale_details.quantity) as qty_sold'),
                DB::raw('AVG(sale_details.sale_price / COALESCE(NULLIF(sales.primary_exchange_rate, 0), 1)) as avg_price_usd'),
                DB::raw('COALESCE(products.cost, 0) as unit_cost_usd')
            )
            ->whereNotIn('sales.status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->whereNull('sales.deletion_approved_at')
            ->whereMonth('sales.created_at', $month)
            ->whereYear('sales.created_at', $year)
            ->where('products.is_raw_material', false)
            ->groupBy('products.sku', 'products.name', 'products.cost')
            ->get();

        $processed = $products->map(function($p) {
            $marginUSD = max(0, $p->avg_price_usd - $p->unit_cost_usd);
            $totalProfitUSD = $marginUSD * $p->qty_sold;
            $marginPercent = $p->avg_price_usd > 0 ? ($marginUSD / $p->avg_price_usd) * 100 : 0;

            return [
                'sku' => $p->sku,
                'name' => $p->name,
                'qty_sold' => $p->qty_sold,
                'avg_price' => $p->avg_price_usd,
                'cost' => $p->unit_cost_usd,
                'margin_usd' => $marginUSD,
                'margin_percent' => $marginPercent,
                'total_profit' => $totalProfitUSD,
            ];
        });

        // Top 10 most profitable products
        $topProfitable = $processed->sortByDesc('total_profit')->take(10)->values()->all();

        // Top 10 low/negative margin products (where price <= cost or margin < 5%)
        $lowMargins = $processed->filter(function($p) {
            return $p['avg_price'] <= $p['cost'] || $p['margin_percent'] < 5;
        })->sortBy('margin_percent')->take(10)->values()->all();

        return [
            'top' => $topProfitable,
            'low' => $lowMargins,
        ];
    }
}
