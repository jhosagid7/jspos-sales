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
    public $selectedDay;
    public $comparisonScope = 'monthly'; // 'daily', 'weekly', 'monthly', 'quarterly', 'yearly'
    public $activeTab = 'growth';
    public $showInterpretationModal = false;

    // OPEX Form Properties
    public $opexCategory = 'Nómina';
    public $opexAmount;
    public $opexDescription;
    public $availableCategories = ['Nómina', 'Alquiler', 'Servicios', 'Impuestos', 'Otros'];

    public function mount()
    {
        session(['map' => '', 'child' => '', 'rest' => '', 'pos' => 'Análisis Estratégico']);
        $this->selectedMonth = Carbon::today()->format('Y-m');
        $this->selectedDay = Carbon::today()->format('Y-m-d');
    }

    public function updatedSelectedMonth($value)
    {
        if ($value) {
            $this->selectedDay = Carbon::parse($value . '-01')->format('Y-m-d');
        }
    }

    public function updatedSelectedDay($value)
    {
        if ($value) {
            $this->selectedMonth = Carbon::parse($value)->format('Y-m');
        }
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
        $currencies = \App\Models\Currency::all();
        $primaryCurrency = $currencies->where('is_primary', 1)->first() ?? $currencies->first();
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'USD';

        $refDate = Carbon::parse($this->selectedDay);
        
        $currentStart = null;
        $currentEnd = null;
        $prevStart = null;
        $prevEnd = null;
        $yearAgoStart = null;
        $yearAgoEnd = null;

        $periodLabel = '';
        $prevLabel = '';
        $yearAgoLabel = '';

        if ($this->comparisonScope === 'daily') {
            $currentStart = $refDate->copy()->startOfDay();
            $currentEnd = $refDate->copy()->endOfDay();
            
            $prevStart = $refDate->copy()->subDay()->startOfDay();
            $prevEnd = $refDate->copy()->subDay()->endOfDay();
            
            $yearAgoStart = $refDate->copy()->subYear()->startOfDay();
            $yearAgoEnd = $refDate->copy()->subYear()->endOfDay();

            $periodLabel = 'Día ' . $refDate->format('d/m/Y');
            $prevLabel = 'vs día anterior';
            $yearAgoLabel = 'vs año anterior';
        } elseif ($this->comparisonScope === 'weekly') {
            $currentStart = $refDate->copy()->startOfWeek();
            $currentEnd = $refDate->copy()->endOfWeek();
            
            $prevStart = $refDate->copy()->subWeek()->startOfWeek();
            $prevEnd = $refDate->copy()->subWeek()->endOfWeek();
            
            $yearAgoStart = $refDate->copy()->subYear()->startOfWeek();
            $yearAgoEnd = $refDate->copy()->subYear()->endOfWeek();

            $periodLabel = 'Semana ' . $refDate->format('W') . ' (' . $currentStart->format('d/m') . ' al ' . $currentEnd->format('d/m') . ')';
            $prevLabel = 'vs semana anterior';
            $yearAgoLabel = 'vs año anterior';
        } elseif ($this->comparisonScope === 'quarterly') {
            $currentStart = $refDate->copy()->startOfQuarter();
            $currentEnd = $refDate->copy()->endOfQuarter();
            
            $prevStart = $refDate->copy()->subQuarter()->startOfQuarter();
            $prevEnd = $refDate->copy()->subQuarter()->endOfQuarter();
            
            $yearAgoStart = $refDate->copy()->subYear()->startOfQuarter();
            $yearAgoEnd = $refDate->copy()->subYear()->endOfQuarter();

            $periodLabel = 'Trimestre Q' . ceil($refDate->month / 3) . ' ' . $refDate->year;
            $prevLabel = 'vs trimestre anterior';
            $yearAgoLabel = 'vs año anterior';
        } elseif ($this->comparisonScope === 'yearly') {
            $currentStart = $refDate->copy()->startOfYear();
            $currentEnd = $refDate->copy()->endOfYear();
            
            $prevStart = $refDate->copy()->subYear()->startOfYear();
            $prevEnd = $refDate->copy()->subYear()->endOfYear();
            
            $yearAgoStart = $refDate->copy()->subYears(2)->startOfYear();
            $yearAgoEnd = $refDate->copy()->subYears(2)->endOfYear();

            $periodLabel = 'Año ' . $refDate->year;
            $prevLabel = 'vs año anterior';
            $yearAgoLabel = 'vs hace 2 años';
        } else { // monthly
            $currentStart = $refDate->copy()->startOfMonth();
            $currentEnd = $refDate->copy()->endOfMonth();
            
            $prevStart = $refDate->copy()->subMonth()->startOfMonth();
            $prevEnd = $refDate->copy()->subMonth()->endOfMonth();
            
            $yearAgoStart = $refDate->copy()->subYear()->startOfMonth();
            $yearAgoEnd = $refDate->copy()->subYear()->endOfMonth();

            $periodLabel = strtoupper($refDate->locale('es')->monthName) . ' ' . $refDate->year;
            $prevLabel = 'vs mes anterior';
            $yearAgoLabel = 'vs año anterior';
        }

        $currentPeriod = $this->calculateRangeMetrics($currentStart, $currentEnd);
        $prevPeriod = $this->calculateRangeMetrics($prevStart, $prevEnd);
        $yearAgoPeriod = $this->calculateRangeMetrics($yearAgoStart, $yearAgoEnd);

        $linearTrend = $this->getLinearGrowthTrend($this->comparisonScope, $refDate);
        $breakdownData = $this->calculateDetailedBreakdown($this->comparisonScope, $refDate);
        $patrimonyData = $this->calculateCurrentPatrimony();
        $abcData = $this->calculateCustomerABC($refDate->month, $refDate->year);
        $productMargins = $this->calculateProductMargins($refDate->month, $refDate->year);

        $startDateStr = $currentStart->format('Y-m-d');
        $endDateStr = $currentEnd->format('Y-m-d');
        $yearMonthStr = $refDate->format('Y-m');

        $manualOpexList = OperationalExpense::where('year_month', $yearMonthStr)
            ->orderBy('id', 'desc')
            ->get();

        $bankExpensesList = \App\Models\BankExpense::with(['bank', 'category'])
            ->whereBetween('expense_date', [$startDateStr, $endDateStr])
            ->get();

        $unifiedOpexList = collect();

        foreach ($manualOpexList as $item) {
            $amount = (float)$item->amount;
            if ($this->comparisonScope === 'daily') {
                $amount = $amount / $refDate->daysInMonth;
            } elseif ($this->comparisonScope === 'weekly') {
                $amount = ($amount / $refDate->daysInMonth) * 7;
            }

            $unifiedOpexList->push((object)[
                'id' => $item->id,
                'category' => $item->category,
                'description' => $item->description . ($this->comparisonScope !== 'monthly' ? ' (Proporcional)' : ''),
                'amount' => $amount,
                'is_bank' => false,
                'bank_name' => null,
                'date' => null,
            ]);
        }

        foreach ($bankExpensesList as $item) {
            $rate = 1.0;
            $currencyCode = $item->bank->currency_code;
            if ($currencyCode !== $primaryCode) {
                $curr = $currencies->where('code', $currencyCode)->first();
                $rate = $curr && $curr->exchange_rate > 0 ? $curr->exchange_rate : 1.0;
            }
            $amountInPrimary = $rate > 0 ? ($item->amount / $rate) : $item->amount;

            $desc = $item->description;
            if ($item->beneficiary) {
                $desc = ($desc ? $desc . ' | ' : '') . 'Beneficiario: ' . $item->beneficiary;
            }
            if ($item->reference) {
                $desc = ($desc ? $desc . ' | ' : '') . 'Ref: ' . $item->reference;
            }

            $unifiedOpexList->push((object)[
                'id' => $item->id,
                'category' => $item->category ? $item->category->name : 'Gasto Bancario',
                'description' => $desc,
                'amount' => (float)$amountInPrimary,
                'is_bank' => true,
                'bank_name' => $item->bank->name,
                'date' => Carbon::parse($item->expense_date)->format('d/m/Y'),
            ]);
        }

        $opexList = $unifiedOpexList->sortByDesc('amount');

        return [
            'current' => $currentPeriod,
            'prev' => $prevPeriod,
            'yearAgo' => $yearAgoPeriod,
            'weeklyBreakdown' => $breakdownData,
            'linearTrend' => $linearTrend,
            'patrimony' => $patrimonyData,
            'abc' => $abcData,
            'productMargins' => $productMargins,
            'opexList' => $opexList,
            'monthName' => $periodLabel,
            'prevLabel' => $prevLabel,
            'yearAgoLabel' => $yearAgoLabel,
        ];
    }

    private function calculateRangeMetrics($start, $end)
    {
        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->endOfDay();

        $grossSales = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->whereNotIn('sales.status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->whereNull('sales.deletion_approved_at')
            ->where('products.is_raw_material', false)
            ->sum(DB::raw('sale_details.quantity * (sale_details.sale_price / COALESCE(NULLIF(sales.primary_exchange_rate, 0), 1))'));

        $returns = DB::table('sale_return_details')
            ->join('sale_returns', 'sale_return_details.sale_return_id', '=', 'sale_returns.id')
            ->join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->join('products', 'sale_return_details.product_id', '=', 'products.id')
            ->whereBetween('sale_returns.created_at', [$startDate, $endDate])
            ->where('sale_returns.status', 'approved')
            ->where('products.is_raw_material', false)
            ->sum(DB::raw('sale_return_details.subtotal / COALESCE(NULLIF(sales.primary_exchange_rate, 0), 1)'));

        $netSales = max(0, $grossSales - $returns);

        $cogs = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereNotIn('sales.status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->whereNull('sales.deletion_approved_at')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('products.is_raw_material', false)
            ->sum(DB::raw('sale_details.quantity * COALESCE(products.cost, 0)'));

        $grossProfit = max(0, $netSales - $cogs);
        $grossMarginPercent = $netSales > 0 ? ($grossProfit / $netSales) * 100 : 0;

        $manualOpex = $this->getManualOpexForRange($startDate, $endDate);
        
        $bankOpexAnalysis = \App\Services\BankTreasuryService::getGlobalExpenseAnalysis($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        $bankOpex = $bankOpexAnalysis['total_amount'] ?? 0.0;

        $opex = $manualOpex + $bankOpex;

        $netProfit = $grossProfit - $opex;
        $netMarginPercent = $netSales > 0 ? ($netProfit / $netSales) * 100 : 0;

        return [
            'grossSales' => (float)$grossSales,
            'returns' => (float)$returns,
            'netSales' => (float)$netSales,
            'cogs' => (float)$cogs,
            'grossProfit' => (float)$grossProfit,
            'grossMarginPercent' => (float)$grossMarginPercent,
            'opex' => (float)$opex,
            'netProfit' => (float)$netProfit,
            'netMarginPercent' => (float)$netMarginPercent,
        ];
    }

    private function getManualOpexForRange($start, $end)
    {
        $total = 0.0;
        
        $months = [];
        $temp = $start->copy()->startOfDay();
        $endDay = $end->copy()->endOfDay();
        while ($temp <= $endDay) {
            $months[] = $temp->format('Y-m');
            $temp->addMonth();
        }
        $months = array_unique($months);

        $opexByMonth = OperationalExpense::whereIn('year_month', $months)
            ->select('year_month', DB::raw('SUM(amount) as total'))
            ->groupBy('year_month')
            ->pluck('total', 'year_month')
            ->toArray();

        $curr = $start->copy()->startOfDay();
        while ($curr <= $endDay) {
            $ym = $curr->format('Y-m');
            $monthOpex = $opexByMonth[$ym] ?? 0.0;
            $daysInMonth = $curr->daysInMonth;
            $total += $monthOpex / $daysInMonth;
            $curr->addDay();
        }

        return $total;
    }

    private function getLinearGrowthTrend($scope, $refDate)
    {
        $labels = [];
        $sales = [];
        $profit = [];

        if ($scope === 'daily') {
            for ($i = 14; $i >= 0; $i--) {
                $day = $refDate->copy()->subDays($i);
                $metrics = $this->calculateRangeMetrics($day, $day);
                $labels[] = $day->format('d/m');
                $sales[] = round($metrics['netSales'], 2);
                $profit[] = round($metrics['netProfit'], 2);
            }
        } elseif ($scope === 'weekly') {
            for ($i = 7; $i >= 0; $i--) {
                $week = $refDate->copy()->subWeeks($i);
                $start = $week->copy()->startOfWeek();
                $end = $week->copy()->endOfWeek();
                $metrics = $this->calculateRangeMetrics($start, $end);
                $labels[] = $start->format('d/m') . '-' . $end->format('d/m');
                $sales[] = round($metrics['netSales'], 2);
                $profit[] = round($metrics['netProfit'], 2);
            }
        } elseif ($scope === 'quarterly') {
            for ($i = 5; $i >= 0; $i--) {
                $q = $refDate->copy()->subMonths($i * 3);
                $start = $q->copy()->startOfQuarter();
                $end = $q->copy()->endOfQuarter();
                $metrics = $this->calculateRangeMetrics($start, $end);
                $labels[] = 'Q' . ceil($q->month / 3) . ' ' . $q->format('y');
                $sales[] = round($metrics['netSales'], 2);
                $profit[] = round($metrics['netProfit'], 2);
            }
        } elseif ($scope === 'yearly') {
            for ($i = 3; $i >= 0; $i--) {
                $yr = $refDate->copy()->subYears($i);
                $start = $yr->copy()->startOfYear();
                $end = $yr->copy()->endOfYear();
                $metrics = $this->calculateRangeMetrics($start, $end);
                $labels[] = $yr->format('Y');
                $sales[] = round($metrics['netSales'], 2);
                $profit[] = round($metrics['netProfit'], 2);
            }
        } else { // monthly
            for ($i = 11; $i >= 0; $i--) {
                $m = $refDate->copy()->subMonths($i);
                $start = $m->copy()->startOfMonth();
                $end = $m->copy()->endOfMonth();
                $metrics = $this->calculateRangeMetrics($start, $end);
                $labels[] = $m->locale('es')->shortMonthName . ' ' . $m->format('y');
                $sales[] = round($metrics['netSales'], 2);
                $profit[] = round($metrics['netProfit'], 2);
            }
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'profit' => $profit,
        ];
    }

    private function calculateDetailedBreakdown($scope, $refDate)
    {
        $labels = [];
        $sales = [];
        $profit = [];

        if ($scope === 'daily' || $scope === 'weekly') {
            $startOfWeek = $refDate->copy()->startOfWeek();
            for ($i = 0; $i < 7; $i++) {
                $day = $startOfWeek->copy()->addDays($i);
                $metrics = $this->calculateRangeMetrics($day, $day);
                
                $daysEs = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
                $labels[] = $daysEs[$i] . ' ' . $day->format('d/m');
                $sales[] = round($metrics['netSales'], 2);
                $profit[] = round($metrics['grossProfit'], 2);
            }
        } elseif ($scope === 'yearly') {
            $startOfYear = $refDate->copy()->startOfYear();
            for ($i = 0; $i < 12; $i++) {
                $month = $startOfYear->copy()->addMonths($i);
                $start = $month->copy()->startOfMonth();
                $end = $month->copy()->endOfMonth();
                $metrics = $this->calculateRangeMetrics($start, $end);
                
                $monthsEs = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                $labels[] = $monthsEs[$i];
                $sales[] = round($metrics['netSales'], 2);
                $profit[] = round($metrics['grossProfit'], 2);
            }
        } else { // monthly or quarterly
            $startOfMonth = $refDate->copy()->startOfMonth();
            $endOfMonth = $refDate->copy()->endOfMonth();

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

            $weekIndex = 1;
            foreach ($daysByWeek as $weekKey => $days) {
                $start = collect($days)->min()->startOfDay();
                $end = collect($days)->max()->endOfDay();
                $metrics = $this->calculateRangeMetrics($start, $end);

                $labels[] = "Semana " . $weekIndex . " (" . $start->format('d/m') . ")";
                $sales[] = round($metrics['netSales'], 2);
                $profit[] = round($metrics['grossProfit'], 2);
                $weekIndex++;
            }
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'profit' => $profit,
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
            $start = $dt->copy()->startOfMonth();
            $end = $dt->copy()->endOfMonth();
            $metrics = $this->calculateRangeMetrics($start, $end);
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
            $html .= "<p class='small mb-2'>âš ï¸ <b>Alerta de Costos Operativos:</b> El OPEX representa el <b>" . number_format($opexSalesRatio, 1) . "%</b> de tus ventas netas. Esto es un indicador alto. Te sugerimos revisar las categorías de gastos y aplicar recortes o buscar economías de escala.</p>";
        } else {
            $html .= "<p class='small mb-2'>âœ… <b>Eficiencia Operativa:</b> El OPEX está bajo control, representando un saludable <b>" . number_format($opexSalesRatio, 1) . "%</b> de tus ventas netas.</p>";
        }

        // Diagnose 2: Margins
        if ($grossMargin < 20) {
            $html .= "<p class='small mb-2'>âš ï¸ <b>Alerta de Margen Bruto:</b> Tu margen bruto promedio es de <b>" . number_format($grossMargin, 1) . "%</b>. Se recomienda revisar la lista de precios de los productos identificados con bajo margen en la pestaña correspondiente o renegociar costos con proveedores.</p>";
        } else {
            $html .= "<p class='small mb-2'>âœ… <b>Márgenes Saludables:</b> Tu margen bruto del <b>" . number_format($grossMargin, 1) . "%</b> es óptimo para la sostenibilidad del negocio.</p>";
        }

        // Diagnose 3: Debt & Cash flow
        if ($debtRatio > 50) {
            $html .= "<p class='small mb-0'>âš ï¸ <b>Riesgo de Liquidez:</b> Las cuentas por pagar representan el <b>" . number_format($debtRatio, 1) . "%</b> de tus activos totales. Se recomienda acelerar la cobranza de las CxC ($" . number_format($totalCxC, 2) . ") o lanzar promociones rápidas de inventario inactivo para inyectar liquidez a cajas y bancos.</p>";
        } elseif ($totalCash < $totalCxP) {
            $html .= "<p class='small mb-0'>â„¹ï¸ <b>Sugerencia de Tesorería:</b> Tu efectivo disponible en bancos ($" . number_format($totalCash, 2) . ") es menor que tus cuentas por pagar ($" . number_format($totalCxP, 2) . "). Prioriza el cobro a clientes a crédito y el control estricto de gastos en la próxima semana.</p>";
        } else {
            $html .= "<p class='small mb-0'>âœ… <b>Solvencia de Caja:</b> Cuentas con suficiente saldo líquido ($" . number_format($totalCash, 2) . ") para cubrir tus compromisos inmediatos con proveedores ($" . number_format($totalCxP, 2) . ").</p>";
        }

        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }
}

