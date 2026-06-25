<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Sale;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class SellersPerformanceReport extends Component
{
    public $selectedSellers = [];
    public $periodType = 'monthly'; // daily, weekly, biweekly, monthly, yearly
    public $dateFrom = '';
    public $dateTo = '';
    public $metric = 'amount'; // amount, count, commission, net_sales, pending_debt
    public $showReport = false;
    public $showPdfModal = false;
    public $pdfUrl = '';

    public function mount()
    {
        session(['pos' => 'Desempeño de Vendedores']);
        $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function searchData()
    {
        $this->showReport = true;
        
        $chartData = $this->getChartData();
        $this->dispatch('updateChart', labels: $chartData['labels'], datasets: $chartData['datasets']);
        $this->dispatch('noty', msg: 'ANÁLISIS DE VENDEDORES ACTUALIZADO');
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

        $this->pdfUrl = route('reports.sellers.performance.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    /**
     * Helper method to calculate outstanding debt in USD for a sale.
     */
    public static function calculateSaleDebtUsd($sale)
    {
        if ($sale->status === 'paid' || $sale->status === 'returned' || $sale->status === 'voided' || $sale->status === 'cancelled' || $sale->status === 'anulated' || $sale->deletion_approved_at !== null) {
            return 0.0;
        }

        // 1. Initial payments (at checkout)
        $initialPaidUSD = 0;
        if ($sale->paymentDetails) {
            foreach ($sale->paymentDetails as $detail) {
                $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                $initialPaidUSD += ($detail->amount / $rate);
            }
        }

        // 2. Subsequent payments (payments table)
        $subsequentPaidUSD = 0;
        if ($sale->payments) {
            foreach ($sale->payments->where('status', 'approved') as $p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                $amountUSD = $p->amount / $rate;
                $adjustmentUSD = $p->discount_applied ?? 0;
                
                if ($p->rule_type === 'overdue') {
                    $subsequentPaidUSD += ($amountUSD - $adjustmentUSD);
                } else {
                    $subsequentPaidUSD += ($amountUSD + $adjustmentUSD);
                }
            }
        }

        // 3. Returns applied to debt
        $returnsUSD = 0;
        if ($sale->returns) {
            $totalReturnsOrig = $sale->returns->where('refund_method', 'debt_reduction')->where('status', 'approved')->sum('total_returned');
            $exchangeRateReturns = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
            $returnsUSD = $totalReturnsOrig / $exchangeRateReturns;
        }

        // 4. Debit notes
        $debitNotesUSD = 0;
        if ($sale->debitNotes) {
            $debitNotesUSD = $sale->debitNotes->where('status', '<>', 'voided')->sum(function($dn) {
                $rate = $dn->exchange_rate > 0 ? $dn->exchange_rate : 1;
                $amountDN = $dn->amount / $rate;
                return $amountDN;
            });
        }

        $debt = ($sale->total_usd + $debitNotesUSD) - ($initialPaidUSD + $subsequentPaidUSD + $returnsUSD);
        return $debt > 0 ? (float)round($debt, 2) : 0.0;
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

        // Query raw database records first
        $query = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select([
                'customers.seller_id',
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

        $oficinaUser = User::where('name', 'OFICINA')->first();
        $oficinaId = $oficinaUser ? $oficinaUser->id : null;

        if (!empty($this->selectedSellers)) {
            $query->where(function($q) use ($oficinaId) {
                $q->whereIn('customers.seller_id', $this->selectedSellers);
                if ($oficinaId && in_array($oficinaId, $this->selectedSellers)) {
                    $q->orWhereNull('customers.seller_id');
                }
            });
        }

        $rawResults = $query->groupBy(['customers.seller_id', DB::raw("$selectExpression")])
            ->orderBy('period_label')
            ->get();

        // If analyzing debt, we need Eloquent relationships to calculate outstanding balances
        $salesForDebt = [];
        if ($this->metric === 'pending_debt') {
            $eloquentQuery = Sale::with(['paymentDetails', 'payments', 'returns', 'debitNotes', 'customer'])
                ->where('status', '<>', 'returned')
                ->whereNull('deletion_approved_at')
                ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo));

            if (!empty($this->selectedSellers)) {
                $eloquentQuery->whereHas('customer', function($c) use ($oficinaId) {
                    $c->where(function($q) use ($oficinaId) {
                        $q->whereIn('seller_id', $this->selectedSellers);
                        if ($oficinaId && in_array($oficinaId, $this->selectedSellers)) {
                            $q->orWhereNull('seller_id');
                        }
                    });
                });
            }
            $salesForDebt = $eloquentQuery->get();
        }

        // Get unique periods mapped and sorted chronologically
        $periodsMap = [];
        foreach ($rawResults as $row) {
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

        // In case metric is pending_debt, periods might exist from sales that aren't in rawResults if we had none,
        // but typically rawResults covers all sales. Just in case, let's map periods based on all sales.
        if ($this->metric === 'pending_debt') {
            foreach ($salesForDebt as $sale) {
                $rawLabel = "";
                if ($this->periodType === 'daily') {
                    $rawLabel = $sale->created_at->format('Y-m-d');
                } elseif ($this->periodType === 'weekly') {
                    $rawLabel = $sale->created_at->copy()->subDays($sale->created_at->dayOfWeekIso - 1)->format('Y-m-d');
                } elseif ($this->periodType === 'biweekly') {
                    $rawLabel = $sale->created_at->day <= 15 ? $sale->created_at->format('Y-m') . '-01' : $sale->created_at->format('Y-m') . '-16';
                } elseif ($this->periodType === 'yearly') {
                    $rawLabel = (string)$sale->created_at->year;
                } else { // monthly
                    $rawLabel = $sale->created_at->format('Y-m');
                }

                if (!isset($periodsMap[$rawLabel])) {
                    $dt = Carbon::parse(explode('-', $rawLabel)[0] === $rawLabel ? $rawLabel . '-01-01' : $rawLabel);
                    $monthName = strtoupper($dt->locale('es')->monthName);

                    $label = $rawLabel;
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
                    $periodsMap[$rawLabel] = $label;
                }
            }
        }

        ksort($periodsMap);
        $labels = array_values($periodsMap);

        // Fetch selected sellers
        $sellersQuery = User::sellers();
        if (!empty($this->selectedSellers)) {
            $sellersQuery->whereIn('id', $this->selectedSellers);
        }
        $sellers = $sellersQuery->get();

        $datasets = [];
        $colors = [
            'rgba(26, 35, 126, 1)',   // Blue
            'rgba(192, 57, 43, 1)',   // Red
            'rgba(39, 174, 96, 1)',   // Green
            'rgba(243, 156, 18, 1)',  // Orange
            'rgba(142, 68, 173, 1)',  // Purple
            'rgba(22, 160, 133, 1)',  // Teal
        ];

        foreach ($sellers as $index => $seller) {
            $sellerData = [];
            foreach (array_keys($periodsMap) as $rawPeriod) {
                if ($this->metric === 'pending_debt') {
                    // Sum debt for this seller in this period
                    $periodSales = $salesForDebt->filter(function($sale) use ($seller, $rawPeriod, $oficinaId) {
                        $sId = $sale->customer->seller_id ?? null;
                        if ($sId != $seller->id && !(is_null($sId) && $seller->id == $oficinaId)) {
                            return false;
                        }

                        $saleRaw = "";
                        if ($this->periodType === 'daily') {
                            $saleRaw = $sale->created_at->format('Y-m-d');
                        } elseif ($this->periodType === 'weekly') {
                            $saleRaw = $sale->created_at->copy()->subDays($sale->created_at->dayOfWeekIso - 1)->format('Y-m-d');
                        } elseif ($this->periodType === 'biweekly') {
                            $saleRaw = $sale->created_at->day <= 15 ? $sale->created_at->format('Y-m') . '-01' : $sale->created_at->format('Y-m') . '-16';
                        } elseif ($this->periodType === 'yearly') {
                            $saleRaw = (string)$sale->created_at->year;
                        } else { // monthly
                            $saleRaw = $sale->created_at->format('Y-m');
                        }
                        return $saleRaw === $rawPeriod;
                    });

                    $debtSum = 0;
                    foreach ($periodSales as $sale) {
                        $debtSum += self::calculateSaleDebtUsd($sale);
                    }
                    $sellerData[] = round($debtSum, 2);
                } else {
                    $match = $rawResults->first(function($row) use ($seller, $oficinaId, $rawPeriod) {
                        if ($row->period_label != $rawPeriod) {
                            return false;
                        }
                        return $row->seller_id == $seller->id || (is_null($row->seller_id) && $seller->id == $oficinaId);
                    });
                    
                    if ($this->metric === 'count') {
                        $sellerData[] = $match ? (int)$match->sales_count : 0;
                    } elseif ($this->metric === 'commission') {
                        $sellerData[] = $match ? (float)$match->total_commission : 0.0;
                    } elseif ($this->metric === 'net_sales') {
                        $sellerData[] = $match ? (float)$match->net_sales : 0.0;
                    } else { // amount
                        $sellerData[] = $match ? (float)$match->total_amount : 0.0;
                    }
                }
            }

            $datasets[] = [
                'name' => $seller->name,
                'data' => $sellerData,
                'color' => $colors[$index % count($colors)]
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    /**
     * Get summary metrics and detailed table per seller.
     */
    public function getSellersSummary()
    {
        $dateFrom = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null;
        $dateTo = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null;

        // Fetch sales with relationships for debt calculation
        $sales = Sale::with(['paymentDetails', 'payments', 'returns', 'debitNotes', 'customer'])
            ->where('status', '<>', 'returned')
            ->whereNull('deletion_approved_at')
            ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo))
            ->get();

        // Get selected sellers
        $sellersQuery = User::sellers();
        if (!empty($this->selectedSellers)) {
            $sellersQuery->whereIn('id', $this->selectedSellers);
        }
        $sellers = $sellersQuery->orderBy('name')->get();

        $summary = [];
        $totalSales = 0;
        $totalCommission = 0;
        $totalNetSales = 0;
        $totalDebt = 0;
        $totalOverdue = 0;

        foreach ($sellers as $seller) {
            $oficinaUser = User::where('name', 'OFICINA')->first();
            $oficinaId = $oficinaUser ? $oficinaUser->id : null;

            $sellerSales = $sales->filter(function($sale) use ($seller, $oficinaId) {
                $sId = $sale->customer->seller_id ?? null;
                return $sId == $seller->id || (is_null($sId) && $seller->id == $oficinaId);
            });
            
            $grossSales = $sellerSales->sum('total_usd');
            $invoicesCount = $sellerSales->count();
            $commissions = $sellerSales->sum('final_commission_amount');
            $netSales = $grossSales - $commissions;
            $marginPercent = $grossSales > 0 ? ($netSales / $grossSales) * 100 : 0;
            
            // Distinct active customers
            $activeCustomers = $sellerSales->pluck('customer_id')->unique()->count();

            // Debt calculations
            $pendingDebt = 0;
            $overdueDebt = 0;
            $weightedOverdueSum = 0;
            $overdueInvoicesCount = 0;

            foreach ($sellerSales as $sale) {
                $debt = self::calculateSaleDebtUsd($sale);
                if ($debt > 0) {
                    $pendingDebt += $debt;
                    
                    // Check if overdue
                    $daysOverdue = $sale->days_overdue; // dynamic attribute in Sale
                    if ($daysOverdue > 0) {
                        $overdueDebt += $debt;
                        $weightedOverdueSum += ($daysOverdue * $debt);
                        $overdueInvoicesCount++;
                    }
                }
            }

            $avgDaysOverdue = $overdueDebt > 0 ? $weightedOverdueSum / $overdueDebt : 0;

            $summary['sellers'][] = [
                'id' => $seller->id,
                'name' => $seller->name,
                'gross_sales' => $grossSales,
                'invoices_count' => $invoicesCount,
                'commissions' => $commissions,
                'net_sales' => $netSales,
                'margin_percent' => $marginPercent,
                'active_customers' => $activeCustomers,
                'pending_debt' => $pendingDebt,
                'overdue_debt' => $overdueDebt,
                'avg_days_overdue' => $avgDaysOverdue,
            ];

            $totalSales += $grossSales;
            $totalCommission += $commissions;
            $totalNetSales += $netSales;
            $totalDebt += $pendingDebt;
            $totalOverdue += $overdueDebt;
        }

        $summary['kpis'] = [
            'total_sales' => $totalSales,
            'total_commission' => $totalCommission,
            'net_sales' => $totalNetSales,
            'margin_percent' => $totalSales > 0 ? ($totalNetSales / $totalSales) * 100 : 0,
            'total_debt' => $totalDebt,
            'total_overdue' => $totalOverdue,
        ];

        return $summary;
    }

    public function getReportData()
    {
        if (!$this->showReport) {
            return [];
        }

        $chartData = $this->getChartData();
        $summary = $this->getSellersSummary();

        return [
            'labels' => $chartData['labels'],
            'datasets' => $chartData['datasets'],
            'sellers' => $summary['sellers'] ?? [],
            'kpis' => $summary['kpis'] ?? [],
        ];
    }

    public function render()
    {
        $sellersList = User::sellers()->orderBy('name')->get();

        return view('livewire.reports.sellers-performance-report', [
            'sellersList' => $sellersList,
            'reportData' => $this->getReportData(),
        ]);
    }
}
