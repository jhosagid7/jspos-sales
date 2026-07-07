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
    public $showInterpretationModal = false;

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

    public function toggleInterpretationModal()
    {
        $this->showInterpretationModal = !$this->showInterpretationModal;
    }

    public function getInterpretation()
    {
        $data = $this->getDashboardData();
        $current = $data['current'];
        $prev = $data['prev'];
        $yearAgo = $data['yearAgo'];
        $patrimony = $data['patrimony'];
        $abc = $data['abc'];
        $productMargins = $data['productMargins'];
        $monthName = $data['monthName'];

        // Calculations & Comparisons
        $netSales = $current['netSales'];
        $netProfit = $current['netProfit'];
        $grossMargin = $current['grossMarginPercent'];
        $netMargin = $current['netMarginPercent'];
        $opex = $current['opex'];
        $cogs = $current['cogs'];

        $diffPrevSales = $prev['netSales'] > 0 ? (($current['netSales'] - $prev['netSales']) / $prev['netSales']) * 100 : 0; 
        $diffYearSales = $yearAgo['netSales'] > 0 ? (($current['netSales'] - $yearAgo['netSales']) / $yearAgo['netSales']) * 100 : 0;

        $diffPrevProfit = $prev['netProfit'] > 0 ? (($current['netProfit'] - $prev['netProfit']) / $prev['netProfit']) * 100 : 0; 
        $diffYearProfit = $yearAgo['netProfit'] > 0 ? (($current['netProfit'] - $yearAgo['netProfit']) / $yearAgo['netProfit']) * 100 : 0;

        // Opex vs Net Sales ratio
        $opexSalesRatio = $netSales > 0 ? ($opex / $netSales) * 100 : 0;

        // Patrimonio metrics
        $inventoryValue = $patrimony['inventoryValue'];
        $totalCxC = $patrimony['totalCxC'];
        $totalCash = $patrimony['totalCash'];
        $totalCxP = $patrimony['totalCxP'];
        $netEquity = $patrimony['netEquity'];

        // Assets = inventory + CxC + Cash
        $totalAssets = $inventoryValue + $totalCxC + $totalCash;
        $debtRatio = $totalAssets > 0 ? ($totalCxP / $totalAssets) * 100 : 0;

        // ABC Client Stats
        $countA = isset($abc['A']) ? count($abc['A']) : 0;
        $countB = isset($abc['B']) ? count($abc['B']) : 0;
        $countC = isset($abc['C']) ? count($abc['C']) : 0;
        $totalClients = $countA + $countB + $countC;

        // Top 3 profitable and low margin products
        $topProducts = collect($productMargins['top'])->take(3);
        $lowProducts = collect($productMargins['low'])->take(3);

        $html = '';
        $html .= "<div class='p-2'>";
        $html .= "<h5 class='text-primary mb-3'><i class='fas fa-chart-line mr-2'></i> <b>Análisis Estratégico y de Crecimiento del Periodo:</b> $monthName</h5>";
        $html .= "<p class='text-muted'>Este informe presenta una interpretación inteligente sobre la salud financiera, operativa y patrimonial de tu negocio para el mes seleccionado:</p>";

        // Block 1: Rentabilidad y Eficiencia Operativa
        $html .= "<div class='row mt-4'>";
        $html .= "<div class='col-md-6 mb-3'>";
        $html .= "<div class='p-3 bg-light rounded border h-100'>";
        $html .= "<h6><i class='fas fa-calculator text-success mr-2'></i> <b>Rentabilidad y Márgenes</b></h6>";
        $html .= "<p class='mb-1'>• Ventas Netas: <b>$" . number_format($netSales, 2) . "</b></p>";
        $html .= "<p class='mb-1'>• Margen Bruto: <b>" . number_format($grossMargin, 1) . "%</b></p>";
        $html .= "<p class='mb-1'>• Utilidad Neta Real: <b>$" . number_format($netProfit, 2) . "</b></p>";
        $html .= "<p class='mb-0'>• Margen Neto: <b>" . number_format($netMargin, 1) . "%</b></p>";
        $html .= "</div>";
        $html .= "</div>";

        // Block 2: Crecimiento y Comparativa
        $html .= "<div class='col-md-6 mb-3'>";
        $html .= "<div class='p-3 bg-light rounded border h-100'>";
        $html .= "<h6><i class='fas fa-balance-scale text-primary mr-2'></i> <b>Comparativa de Crecimiento</b></h6>";
        
        $salesPrevText = $diffPrevSales >= 0 ? "<span class='text-success font-weight-bold'>+" . number_format($diffPrevSales, 1) . "%</span>" : "<span class='text-danger font-weight-bold'>" . number_format($diffPrevSales, 1) . "%</span>";
        $salesYearText = $diffYearSales >= 0 ? "<span class='text-success font-weight-bold'>+" . number_format($diffYearSales, 1) . "%</span>" : "<span class='text-danger font-weight-bold'>" . number_format($diffYearSales, 1) . "%</span>";
        $profitPrevText = $diffPrevProfit >= 0 ? "<span class='text-success font-weight-bold'>+" . number_format($diffPrevProfit, 1) . "%</span>" : "<span class='text-danger font-weight-bold'>" . number_format($diffPrevProfit, 1) . "%</span>";
        
        $html .= "<p class='mb-1'>• Ventas vs Mes Anterior: $salesPrevText</p>";
        $html .= "<p class='mb-1'>• Ventas vs Año Anterior: $salesYearText</p>";
        $html .= "<p class='mb-0'>• Utilidad vs Mes Anterior: $profitPrevText</p>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";

        // Block 3: Estructura Financiera y Patrimonio
        $html .= "<div class='p-3 bg-light rounded border mb-3 mt-2'>";
        $html .= "<h6><i class='fas fa-wallet text-info mr-2'></i> <b>Patrimonio y Solvencia Activa</b></h6>";
        $html .= "<div class='row'>";
        $html .= "<div class='col-sm-6'>";
        $html .= "<p class='mb-1'>• Inventario a Costo: <b>$" . number_format($inventoryValue, 2) . "</b></p>";
        $html .= "<p class='mb-1'>• Cuentas por Cobrar (CxC): <b>$" . number_format($totalCxC, 2) . "</b></p>";
        $html .= "<p class='mb-1'>• Efectivo / Bancos: <b>$" . number_format($totalCash, 2) . "</b></p>";
        $html .= "</div>";
        $html .= "<div class='col-sm-6'>";
        $html .= "<p class='mb-1'>• Cuentas por Pagar (CxP): <b>$" . number_format($totalCxP, 2) . "</b></p>";
        $html .= "<p class='mb-1'>• Patrimonio Neto: <b class='text-primary'>$" . number_format($netEquity, 2) . "</b></p>";
        $html .= "<p class='mb-0'>• Nivel de Deuda (CxP/Activos): <b>" . number_format($debtRatio, 1) . "%</b></p>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";

        // Block 4: Análisis de Clientes (ABC)
        if ($totalClients > 0) {
            $html .= "<div class='p-3 bg-light rounded border mb-3 mt-2'>";
            $html .= "<h6><i class='fas fa-users text-warning mr-2'></i> <b>Concentración de Clientes (Pareto 80/20)</b></h6>";
            $html .= "<p class='mb-1'>• Tienes un total de <b>$totalClients</b> clientes con movimientos en este mes.</p>";
            $html .= "<p class='mb-0'>• <b>Clase A:</b> $countA clientes representan el 80% de tus ganancias. <b>Clase B:</b> $countB clientes representan el 15%. <b>Clase C:</b> $countC clientes representan el 5% restante.</p>";
            $html .= "</div>";
        }

        // Block 5: Productos Destacados
        if ($topProducts->count() > 0) {
            $html .= "<h6 class='mt-4 font-weight-bold text-dark'><i class='fas fa-medal text-success mr-2'></i> <b>Top 3 Productos con Mayor Contribución de Utilidad:</b></h6>";
            $html .= "<div class='list-group mb-3'>";
            foreach ($topProducts as $index => $p) {
                $html .= "<div class='list-group-item list-group-item-action flex-column align-items-start'>";
                $html .= "<div class='d-flex w-100 justify-content-between'>";
                $html .= "<h6 class='mb-1 font-weight-bold text-info'>#" . ($index + 1) . " - {$p['name']}</h6>";
                $html .= "<span class='font-weight-bold text-success'>$" . number_format($p['total_profit'], 2) . "</span>";
                $html .= "</div>";
                $html .= "<p class='mb-1 small text-muted'>Vendidos: <b>{$p['qty_sold']} uds</b> | Precio Promedio: <b>$" . number_format($p['avg_price'], 2) . "</b> | Costo: <b>$" . number_format($p['cost'], 2) . "</b> | Margen: <b>" . number_format($p['margin_percent'], 1) . "%</b></p>";
                $html .= "</div>";
            }
            $html .= "</div>";
        }

        // Block 6: Diagnóstico & Recomendaciones (IA)
        $html .= "<div class='alert alert-warning mt-4 border-0 shadow-sm'>";
        $html .= "<h5><i class='fas fa-lightbulb text-dark mr-2'></i> <b>Diagnóstico Estratégico y Acciones Recomendadas</b></h5>";
        $html .= "<hr class='my-2 bg-dark'>";
        
        // Diagnose 1: Opex vs Sales
        if ($opexSalesRatio > 30) {
            $html .= "<p class='small mb-2'>⚠️ <b>Alerta de Costos Operativos:</b> El OPEX representa el <b>" . number_format($opexSalesRatio, 1) . "%</b> de tus ventas netas. Esto es un indicador alto. Te sugerimos revisar las categorías de gastos y aplicar recortes o buscar economías de escala.</p>";
        } else {
            $html .= "<p class='small mb-2'>✅ <b>Eficiencia Operativa:</b> El OPEX está bajo control, representando un saludable <b>" . number_format($opexSalesRatio, 1) . "%</b> de tus ventas netas.</p>";
        }

        // Diagnose 2: Margins
        if ($grossMargin < 20) {
            $html .= "<p class='small mb-2'>⚠️ <b>Alerta de Margen Bruto:</b> Tu margen bruto promedio es de <b>" . number_format($grossMargin, 1) . "%</b>. Se recomienda revisar la lista de precios de los productos identificados con bajo margen en la pestaña correspondiente o renegociar costos con proveedores.</p>";
        } else {
            $html .= "<p class='small mb-2'>✅ <b>Márgenes Saludables:</b> Tu margen bruto del <b>" . number_format($grossMargin, 1) . "%</b> es óptimo para la sostenibilidad del negocio.</p>";
        }

        // Diagnose 3: Debt & Cash flow
        if ($debtRatio > 50) {
            $html .= "<p class='small mb-0'>⚠️ <b>Riesgo de Liquidez:</b> Las cuentas por pagar representan el <b>" . number_format($debtRatio, 1) . "%</b> de tus activos totales. Se recomienda acelerar la cobranza de las CxC ($" . number_format($totalCxC, 2) . ") o lanzar promociones rápidas de inventario inactivo para inyectar liquidez a cajas y bancos.</p>";
        } elseif ($totalCash < $totalCxP) {
            $html .= "<p class='small mb-0'>ℹ️ <b>Sugerencia de Tesorería:</b> Tu efectivo disponible en bancos ($" . number_format($totalCash, 2) . ") es menor que tus cuentas por pagar ($" . number_format($totalCxP, 2) . "). Prioriza el cobro a clientes a crédito y el control estricto de gastos en la próxima semana.</p>";
        } else {
            $html .= "<p class='small mb-0'>✅ <b>Solvencia de Caja:</b> Cuentas con suficiente saldo líquido ($" . number_format($totalCash, 2) . ") para cubrir tus compromisos inmediatos con proveedores ($" . number_format($totalCxP, 2) . ").</p>";
        }

        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }
}
