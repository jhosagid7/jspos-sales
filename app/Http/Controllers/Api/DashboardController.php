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

        // 1. Total Sales (Created in April - regardless of payment)
        $monthlySales = Sale::whereHas('customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
            })
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->sum('total_usd');

        // 2. Collections of the Month (Any payment received in April, even for old sales)
        // Calculating USD amount manually from payments table
        $monthlyCollections = \App\Models\Payment::whereHas('sale.customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
            })
            ->where('status', 'approved')
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->sum(function($p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                return ($p->amount / $rate) + ($p->discount_applied ?: 0);
            });

        // 3. Total Debt on the Street (All unpaid balance for this salesman's customers)
        $totalDebt = Sale::whereHas('customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
            })
            ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned', 'paid'])
            ->get()
            ->sum(function($s) {
                // We use a simplified calculation here, assuming debt() or similar is available or calculating via payments
                // For performance in dash, we might want a column, but let's use the model logic for now
                return $s->total_usd - $s->payments->where('status', 'approved')->sum(function($p) {
                    $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                    return ($p->amount / $rate) + ($p->discount_applied ?: 0);
                });
            });

        // 4. Earned Commissions (Strictly following 'Gestión de Comisiones' web logic)
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

        // 5. Commissions Already Paid to Salesman (This month - Matching web logic)
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
}
