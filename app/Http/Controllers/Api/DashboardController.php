<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // 1. Total Sales (Progress towards goal - Matching SalesReport logic)
        $monthlySales = Sale::whereHas('customer', function($q) use ($user) {
                $q->whereIn('seller_id', $user->getSharedSellerIds());
            })
            ->where('is_foreign_sale', true)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->sum('total_usd');

        // 2. Collections of the Month (Strictly approved cash in)
        $monthlyCollections = \App\Models\Payment::whereHas('sale.customer', function($q) use ($user) {
                $q->whereIn('seller_id', $user->getSharedSellerIds());
            })
            ->whereIn('status', ['approved', 'settled'])
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->sum(function($p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                // We don't include discounts in "cash in" collection, matching standard income reports
                return $p->amount / $rate;
            });

        // 3. Total Debt on the Street (Matching AccountsReceivableReport exactly)
        $activeSales = Sale::whereHas('customer', function($q) use ($user) {
                $q->whereIn('seller_id', $user->getSharedSellerIds());
            })
            ->where('type', 'credit')
            ->whereNotIn('status', ['paid', 'voided', 'cancelled', 'anulated', 'returned'])
            ->with(['payments', 'returns', 'paymentDetails'])
            ->get();

        $totalDebt = $activeSales->sum(function($s) {
            // Approved payments (USD)
            $totalPaidUSD = $s->payments->whereIn('status', ['approved', 'settled'])->sum(function($p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                return $p->amount / $rate;
            });

            // Initial payments (USD)
            $initialPaidUSD = $s->paymentDetails->sum(function($detail) {
                $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                return $detail->amount / $rate;
            });

            // Returns applied to debt reduction (USD)
            $totalReturnsUSD = $s->returns->where('refund_method', 'debt_reduction')->where('status', 'approved')->sum(function($ret) use ($s) {
                $rate = $s->primary_exchange_rate > 0 ? $s->primary_exchange_rate : 1;
                return $ret->total_returned / $rate;
            });

            $debt = $s->total_usd - ($totalPaidUSD + $initialPaidUSD + $totalReturnsUSD);
            return max(0, $debt);
        });

        // 2. Commissions (Earned pending payment by the company)
        // We match exactly the Web Panel logic via our private helper
        $commissionsPendingQuery = $this->getCommissionsQuery($user, 'pending');
        $commissionsPending = (clone $commissionsPendingQuery)->sum('final_commission_amount');

        // Commissions Paid (History)
        $commissionsPaidQuery = $this->getCommissionsQuery($user, 'paid');
        $commissionsPaidThisMonth = (clone $commissionsPaidQuery)->sum('final_commission_amount');

        // Goal & Progress
        $monthlyGoal = (float)($user->monthly_goal ?? 0);
        $progress = $monthlyGoal > 0 ? ($monthlySales / $monthlyGoal) * 100 : 0;

        // Sales count of the month
        $salesCount = Sale::whereHas('customer', function($q) use ($user) {
                $q->whereIn('seller_id', $user->getSharedSellerIds());
            })
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'seller_name' => $user->name,
                'month_name' => Carbon::now()->translatedFormat('F'),
                'metrics' => [
                    'monthly_sales' => round($monthlySales, 2),
                    'monthly_collections' => round($monthlyCollections, 2),
                    'total_debt' => round($totalDebt, 2),
                    'commissions_earned_pending' => round($commissionsPending, 2),
                    'commissions_paid_this_month' => round($commissionsPaidThisMonth, 2),
                    'monthly_goal' => $monthlyGoal,
                    'goal_progress_percent' => round($progress, 1),
                    'sales_count' => $salesCount, // Actual volume of invoices
                ]
            ]
        ]);
    }

    /**
     * Get detailed commission breakdown for the mobile app.
     */
    /**
     * Unified query for commissions to match Web Panel logic.
     */
    private function getCommissionsQuery($user, $type = 'all')
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $query = Sale::whereHas('customer', function($q) use ($user) {
                $q->whereIn('seller_id', $user->getSharedSellerIds());
            })
            ->where('is_foreign_sale', true)
            ->whereNotIn('status', ['returned', 'voided', 'cancelled', 'anulated'])
            ->where('applied_commission_percent', '>', 0)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

        if ($type === 'pending') {
            $query->where('commission_status', '!=', 'paid')
                  ->where('final_commission_amount', '>', 0);
        } elseif ($type === 'paid') {
            $query->where('commission_status', 'paid');
        }

        return $query;
    }

    public function commissions(Request $request)
    {
        $user = $request->user();

        // 1. Pending (Matches Web Panel "PENDIENTE")
        $pendingQuery = $this->getCommissionsQuery($user, 'pending');
        $pending = (clone $pendingQuery)->orderBy('created_at', 'desc')->get()->map(function($sale) {
            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'date' => $sale->created_at->format('Y-m-d'),
                'customer_name' => $sale->customer->name,
                'total_usd' => round($sale->total_usd, 2),
                'commission_amount' => round($sale->final_commission_amount, 2),
                'commission_percent' => $sale->applied_commission_percent,
                'status' => 'pending'
            ];
        });

        // 2. Paid (Matches Web Panel "PAGADA")
        $paidQuery = $this->getCommissionsQuery($user, 'paid');
        $paid = (clone $paidQuery)->orderBy('created_at', 'desc')->get()->map(function($sale) {
            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'date' => $sale->created_at->format('Y-m-d'),
                'paid_at' => $sale->commission_paid_at ? $sale->commission_paid_at->format('Y-m-d') : null,
                'customer_name' => $sale->customer->name,
                'total_usd' => round($sale->total_usd, 2),
                'commission_amount' => round($sale->final_commission_amount, 2),
                'commission_percent' => $sale->applied_commission_percent,
                'status' => 'paid'
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'pending' => $pending,
                'paid' => $paid,
                'summary' => [
                    'pending_total' => round($pendingQuery->sum('final_commission_amount'), 2),
                    'paid_total' => round($paidQuery->sum('final_commission_amount'), 2),
                    'total_earned_this_month' => round($pendingQuery->sum('final_commission_amount') + $paidQuery->sum('final_commission_amount'), 2)
                ]
            ]
        ]);
    }

    /**
     * Get detailed debt (Accounts Receivable) breakdown for the mobile app.
     */
    public function debt(Request $request)
    {
        $user = $request->user();

        $activeSales = Sale::whereHas('customer', function($q) use ($user) {
                $q->whereIn('seller_id', $user->getSharedSellerIds());
            })
            ->where('type', 'credit')
            ->whereNotIn('status', ['paid', 'voided', 'cancelled', 'anulated', 'returned'])
            ->with(['customer', 'payments', 'returns', 'paymentDetails'])
            ->orderBy('created_at', 'asc')
            ->get();

        $today = Carbon::now();

        $debtList = $activeSales->map(function($sale) use ($today) {
            // 1. Calculate Debt balance exactly as in AccountsReceivableReport
            $totalPaidUSD = $sale->payments->whereIn('status', ['approved', 'settled'])->sum(function($p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                return $p->amount / $rate;
            });

            $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                return $detail->amount / $rate;
            });

            $totalReturnsUSD = $sale->returns->where('refund_method', 'debt_reduction')->where('status', 'approved')->sum(function($ret) use ($sale) {
                $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                return $ret->total_returned / $rate;
            });

            $remainingDebt = round($sale->total_usd - ($totalPaidUSD + $initialPaidUSD + $totalReturnsUSD), 2);

            // 2. Calculate Aging
            $baseDate = $sale->delivered_at ?: $sale->created_at;
            $dueDate = Carbon::parse($baseDate)->addDays($sale->credit_days ?: 0);
            $overdueDays = $today->diffInDays($dueDate, false) * -1; // Positive = Overdue

            // 3. Dynamic Commission Tier Logic
            $currentCommPercent = (float)$sale->applied_commission_percent;
            $commAmount = (float)$sale->final_commission_amount;
            $commStatus = 'safe'; // green/safe
            $color = 'blue';
            $daysLeftForNextTier = null;

            if ($overdueDays > 0) {
                // Check against Tier 2 first (the worst case)
                if ($sale->seller_tier_2_days > 0 && $overdueDays > $sale->seller_tier_2_days) {
                    $currentCommPercent = 0;
                    $commAmount = 0;
                    $commStatus = 'lost';
                    $color = 'red';
                } 
                // Check against Tier 1
                else if ($sale->seller_tier_1_days > 0 && $overdueDays > $sale->seller_tier_1_days) {
                    $currentCommPercent = (float)$sale->seller_tier_2_percent;
                    $commAmount = round(($currentCommPercent / 100) * $sale->total_usd, 2);
                    $commStatus = 'warning';
                    $color = 'orange';
                    if ($sale->seller_tier_2_days > 0) {
                        $daysLeftForNextTier = $sale->seller_tier_2_days - $overdueDays;
                    }
                } 
                else {
                    // Still in Tier 1 range but Overdue (due date passed)
                    $currentCommPercent = (float)$sale->seller_tier_1_percent;
                    $commAmount = round(($currentCommPercent / 100) * $sale->total_usd, 2);
                    $commStatus = 'due';
                    $color = 'orange';
                    if ($sale->seller_tier_1_days > 0) {
                        $daysLeftForNextTier = $sale->seller_tier_1_days - $overdueDays;
                    }
                }
            } else {
                // Not overdue yet - Max Commission Safe
                $currentCommPercent = (float)$sale->seller_tier_1_percent;
                $commAmount = round(($currentCommPercent / 100) * $sale->total_usd, 2);
                $commStatus = 'safe';
                $color = 'blue';
                // Days remaining until it becomes overdue
                $daysLeftForNextTier = abs($overdueDays); 
            }

            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'customer_name' => $sale->customer->name,
                'customer_phone' => $sale->customer->phone,
                'date' => $sale->created_at->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'total_usd' => round($sale->total_usd, 2),
                'remaining_debt_usd' => $remainingDebt,
                'overdue_days' => $overdueDays,
                'projected_commission_percent' => $currentCommPercent,
                'projected_commission_amount' => $commAmount,
                'comm_status' => $commStatus,
                'days_left_tier' => $daysLeftForNextTier,
                'aging_color' => $color,
            ];
        })->filter(function($item) {
            return $item['remaining_debt_usd'] > 0;
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'debt_list' => $debtList,
                'summary' => [
                    'total_overdue_count' => $debtList->where('overdue_days', '>', 0)->count(),
                    'total_debt_amount' => round($debtList->sum('remaining_debt_usd'), 2),
                    'critical_debt_amount' => round($debtList->where('aging_status', 'overdue_critical')->sum('remaining_debt_usd'), 2),
                ]
            ]
        ]);
    }
}
