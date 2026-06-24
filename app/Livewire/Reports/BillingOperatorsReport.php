<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Sale;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class BillingOperatorsReport extends Component
{
    public $selectedOperators = [];
    public $periodType = 'monthly'; // daily, weekly, biweekly, monthly, yearly
    public $dateFrom = '';
    public $dateTo = '';
    public $metric = 'precision_score'; // precision_score, invoices_count, amount_usd, modified_count, voided_count, returned_count
    public $showReport = false;
    public $showPdfModal = false;
    public $pdfUrl = '';

    public function mount()
    {
        session(['pos' => 'Eficiencia de Operadores']);
        $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function searchData()
    {
        $this->showReport = true;
        
        $chartData = $this->getChartData();
        $this->dispatch('updateChart', labels: $chartData['labels'], datasets: $chartData['datasets']);
        $this->dispatch('noty', msg: 'ANÁLISIS DE OPERADORES ACTUALIZADO');
    }

    public function openPdfPreview()
    {
        $params = [
            'selectedOperators' => implode(',', $this->selectedOperators),
            'periodType' => $this->periodType,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'metric' => $this->metric,
        ];

        $this->pdfUrl = route('reports.operators.precision.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    /**
     * Precision quality score formula:
     * Score = max(0, 100 - (((Facturas Anuladas * 1.5) + Facturas Modificadas + (Facturas con Devolución * 1.2)) / Total Facturas * 100))
     */
    public static function calculatePrecisionScore($total, $voided, $modified, $returned)
    {
        if ($total == 0) {
            return 100.0;
        }
        $penalty = ($voided * 1.5) + $modified + ($returned * 1.2);
        $score = 100.0 - (($penalty / $total) * 100.0);
        return max(0.0, round($score, 2));
    }

    /**
     * Get aggregated data for the chart.
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

        // Query raw database records grouped by user_id and period_label
        $query = DB::table('sales')
            ->select([
                'sales.user_id',
                DB::raw("$selectExpression as period_label"),
                DB::raw("COUNT(*) as total_sales"),
                DB::raw("SUM(sales.total_usd) as total_amount"),
                DB::raw("SUM(CASE WHEN sales.status IN ('voided', 'cancelled', 'anulated') OR sales.deletion_approved_at IS NOT NULL THEN 1 ELSE 0 END) as voided_count"),
                DB::raw("SUM(CASE WHEN (SELECT COUNT(*) FROM sale_history_logs WHERE sale_history_logs.sale_id = sales.id) > 0 THEN 1 ELSE 0 END) as modified_count"),
                DB::raw("SUM(CASE WHEN (SELECT COUNT(*) FROM sale_returns WHERE sale_returns.sale_id = sales.id AND sale_returns.status = 'approved') > 0 THEN 1 ELSE 0 END) as returned_count"),
            ])
            ->when($dateFrom, fn($q) => $q->where('sales.created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('sales.created_at', '<=', $dateTo));

        if (!empty($this->selectedOperators)) {
            $query->whereIn('sales.user_id', $this->selectedOperators);
        }

        $rawResults = $query->groupBy(['sales.user_id', DB::raw("$selectExpression")])
            ->orderBy('period_label')
            ->get();

        // Get unique periods mapped and sorted chronologically
        $periodsMap = [];
        foreach ($rawResults as $row) {
            if (!$row->period_label) {
                continue;
            }
            $dt = Carbon::parse(explode('-', $row->period_label)[0] === $row->period_label ? $row->period_label . '-01-01' : $row->period_label);
            $monthName = strtoupper($dt->locale('es')->monthName);

            $label = $row->period_label;
            if ($this->periodType === 'daily') {
                $label = $dt->format('d/m/Y');
            } elseif ($this->periodType === 'weekly') {
                $weekNumber = sprintf('%02d', $dt->weekOfYear);
                $label = "{$dt->year}-{$monthName}-{$dt->day}-S{$weekNumber}";
            } elseif ($this->periodType === 'biweekly') {
                $fortnight = $dt->day <= 15 ? 'Q1' : 'Q2';
                $label = "{$dt->year}-{$monthName}-{$fortnight}";
            } elseif ($this->periodType === 'monthly') {
                $label = "{$dt->year}-{$monthName}";
            } else { // yearly
                $label = "{$dt->year}";
            }

            $periodsMap[$row->period_label] = $label;
        }

        ksort($periodsMap);
        $labels = array_values($periodsMap);

        // Fetch selected operators
        $operatorsQuery = User::query();
        if (!empty($this->selectedOperators)) {
            $operatorsQuery->whereIn('id', $this->selectedOperators);
        } else {
            // Default to users with sales
            $userIdsWithSales = DB::table('sales')->distinct()->pluck('user_id')->filter()->toArray();
            if (!empty($userIdsWithSales)) {
                $operatorsQuery->whereIn('id', $userIdsWithSales);
            }
        }
        $operators = $operatorsQuery->orderBy('name')->get();

        $datasets = [];
        $colors = [
            'rgba(26, 35, 126, 1)',   // Blue
            'rgba(192, 57, 43, 1)',   // Red
            'rgba(39, 174, 96, 1)',   // Green
            'rgba(243, 156, 18, 1)',  // Orange
            'rgba(142, 68, 173, 1)',  // Purple
            'rgba(22, 160, 133, 1)',  // Teal
        ];

        foreach ($operators as $index => $operator) {
            $operatorData = [];
            foreach (array_keys($periodsMap) as $rawPeriod) {
                $match = $rawResults->first(fn($row) => $row->user_id == $operator->id && $row->period_label == $rawPeriod);

                if ($this->metric === 'invoices_count') {
                    $operatorData[] = $match ? (int)$match->total_sales : 0;
                } elseif ($this->metric === 'amount_usd') {
                    $operatorData[] = $match ? (float)round($match->total_amount, 2) : 0.0;
                } elseif ($this->metric === 'modified_count') {
                    $operatorData[] = $match ? (int)$match->modified_count : 0;
                } elseif ($this->metric === 'voided_count') {
                    $operatorData[] = $match ? (int)$match->voided_count : 0;
                } elseif ($this->metric === 'returned_count') {
                    $operatorData[] = $match ? (int)$match->returned_count : 0;
                } else { // precision_score
                    if ($match) {
                        $operatorData[] = self::calculatePrecisionScore(
                            $match->total_sales,
                            $match->voided_count,
                            $match->modified_count,
                            $match->returned_count
                        );
                    } else {
                        $operatorData[] = 100.0; // Default score
                    }
                }
            }

            $datasets[] = [
                'name' => $operator->name,
                'data' => $operatorData,
                'color' => $colors[$index % count($colors)]
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    /**
     * Get summary metrics and detailed table per operator.
     */
    public function getOperatorsSummary()
    {
        $dateFrom = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null;
        $dateTo = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null;

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

        if (!empty($this->selectedOperators)) {
            $query->whereIn('sales.user_id', $this->selectedOperators);
        }

        $summaryResults = $query->groupBy('sales.user_id')->get();

        // Get user details
        $userIds = $summaryResults->pluck('user_id')->filter()->toArray();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

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

            $score = self::calculatePrecisionScore(
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

        // Sort operators by name alphabetically
        usort($operators, fn($a, $b) => strcmp($a['name'], $b['name']));

        // Ponderated average score:
        $avgScore = self::calculatePrecisionScore(
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

        return [
            'operators' => $operators,
            'kpis' => $kpis
        ];
    }

    public function getReportData()
    {
        if (!$this->showReport) {
            return [];
        }

        $chartData = $this->getChartData();
        $summary = $this->getOperatorsSummary();

        return [
            'labels' => $chartData['labels'],
            'datasets' => $chartData['datasets'],
            'operators' => $summary['operators'] ?? [],
            'kpis' => $summary['kpis'] ?? [],
        ];
    }

    public function render()
    {
        // Select all users sorted by name
        $operatorsList = User::orderBy('name')->get();

        return view('livewire.reports.billing-operators-report', [
            'operatorsList' => $operatorsList,
            'reportData' => $this->getReportData(),
        ]);
    }
}
