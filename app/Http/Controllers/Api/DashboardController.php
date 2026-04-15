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

        // 1. Total Sales in USD (Current Month)
        // Note: total_usd is a column in the sales table.
        $totalSales = Sale::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['voided', 'cancelled', 'anulated'])
            ->sum('total_usd');

        // 2. Accumulated Commissions (Commissions earned from sales settled this month)
        // If we want commissions of sales CREATED this month:
        $totalCommission = Sale::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotNull('final_commission_amount')
            ->sum('final_commission_amount');

        // 3. Goal & Progress
        $monthlyGoal = (float)($user->monthly_goal ?? 0);
        $progress = $monthlyGoal > 0 ? ($totalSales / $monthlyGoal) * 100 : 0;

        // 4. Counts
        $salesCount = Sale::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['voided', 'cancelled', 'anulated'])
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'seller_name' => $user->name,
                'month_name' => Carbon::now()->translatedFormat('F'),
                'metrics' => [
                    'total_sales' => round($totalSales, 2),
                    'total_commission' => round($totalCommission, 2),
                    'monthly_goal' => $monthlyGoal,
                    'goal_progress_percent' => round($progress, 1),
                    'sales_count' => $salesCount,
                ]
            ]
        ]);
    }
}
