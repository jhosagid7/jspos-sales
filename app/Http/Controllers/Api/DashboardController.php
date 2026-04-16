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
                $q->where('seller_id', $user->id);
            })
            ->where('is_foreign_sale', true)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->sum('total_usd');

        // 2. Collections of the Month (Strictly approved cash in)
        $monthlyCollections = \App\Models\Payment::whereHas('sale.customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
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
                $q->where('seller_id', $user->id);
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

        $commissionsPending = Sale::whereHas('customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
            })
            ->where('is_foreign_sale', true)
            ->whereNotIn('status', ['returned', 'voided', 'cancelled', 'anulated'])
            ->where('applied_commission_percent', '>', 0)
            ->where('status', 'paid') 
            ->where('commission_status', 'pending_payment')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('final_commission_amount');

        // 5. Commissions Already Paid to Salesman (This month history)
        $commissionsPaidThisMonth = Sale::whereHas('customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
            })
            ->where('is_foreign_sale', true)
            ->whereNotIn('status', ['returned', 'voided', 'cancelled', 'anulated'])
            ->where('commission_status', 'paid')
            ->whereBetween('commission_paid_at', [$startOfMonth, $endOfMonth])
            ->sum('final_commission_amount');

        // Goal & Progress
        $monthlyGoal = (float)($user->monthly_goal ?? 0);
        $progress = $monthlyGoal > 0 ? ($monthlySales / $monthlyGoal) * 100 : 0;

        // Sales count of the month
        $salesCount = Sale::whereHas('customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
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
    public function commissions(Request $request)
    {
        $user = $request->user();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

         // 1. Pending (Earned because client paid, but company hasn't paid salesman yet)
         $pending = Sale::whereHas('customer', function($q) use ($user) {
                 $q->where('seller_id', $user->id);
             })
             ->where('is_foreign_sale', true)
             ->whereNotIn('status', ['returned', 'voided', 'cancelled', 'anulated'])
             ->where('applied_commission_percent', '>', 0)
             ->where('status', 'paid') 
             ->where('commission_status', 'pending_payment')
             ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
             ->with('customer')
             ->orderBy('created_at', 'desc')
             ->get()
             ->map(function($sale) {
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

        // 2. Paid (Already paid by company to salesman this month)
        $paid = Sale::whereHas('customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
            })
            ->where('is_foreign_sale', true)
            ->whereNotIn('status', ['returned', 'voided', 'cancelled', 'anulated'])
            ->where('commission_status', 'paid')
            ->whereBetween('commission_paid_at', [$startOfMonth, $endOfMonth])
            ->with('customer')
            ->orderBy('commission_paid_at', 'desc')
            ->get()
            ->map(function($sale) {
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
                    'pending_total' => round($pending->sum('commission_amount'), 2),
                    'paid_total' => round($paid->sum('commission_amount'), 2),
                    'total_earned' => round($pending->sum('commission_amount') + $paid->sum('commission_amount'), 2)
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
                $q->where('seller_id', $user->id);
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

            // 3. Assign Aging Status & Color
            $status = 'on_time';
            $color = 'blue';

            if ($overdueDays > 0) {
                if ($overdueDays <= 15) {
                    $status = 'overdue_recent';
                    $color = 'orange';
                } else {
                    $status = 'overdue_critical';
                    $color = 'red';
                }
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
                'aging_status' => $status,
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
