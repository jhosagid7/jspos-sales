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

        // Base query: Sales associated with the salesman through the Customer relationship
        $baseQuery = Sale::whereHas('customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
            })
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned']);

        // 1. Total Sales (Logged/Created this month, regardless of payment status)
        $totalSales = (clone $baseQuery)->sum('total_usd');

        // 2. Paid Sales (Already settled)
        $paidSales = (clone $baseQuery)->where('status', 'paid')->sum('total_usd');

        // 3. Pending Sales (Still on credit/debt)
        $pendingSales = $totalSales - $paidSales;

        // 4. Accumulated Commissions (Only from sales that are ALREADY PAID)
        $totalCommission = (clone $baseQuery)
            ->where('status', 'paid')
            ->whereNotNull('final_commission_amount')
            ->sum('final_commission_amount');

        // 5. Goal & Progress (Based on Total Sales)
        $monthlyGoal = (float)($user->monthly_goal ?? 0);
        $progress = $monthlyGoal > 0 ? ($totalSales / $monthlyGoal) * 100 : 0;

        // 6. Counts
        $salesCount = (clone $baseQuery)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'seller_name' => $user->name,
                'month_name' => Carbon::now()->translatedFormat('F'),
                'metrics' => [
                    'total_sales' => round($totalSales, 2),
                    'paid_sales' => round($paidSales, 2),
                    'pending_sales' => round($pendingSales, 2),
                    'total_commission' => round($totalCommission, 2),
                    'monthly_goal' => $monthlyGoal,
                    'goal_progress_percent' => round($progress, 1),
                    'sales_count' => $salesCount,
                ]
            ]
        ]);
    }
}
