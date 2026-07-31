<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter'); // 'debt', 'overdue'
        $user = $request->user();

        $query = Customer::query();

        // Security: Administrative profiles see everything; others see only their own
        $isAdmin = $user->hasRole(['Admin', 'Super Admin']) || $user->profile === 'Admin' || $user->profile === 'Super Admin';
        
        if (!$isAdmin && !$user->can('customers.view_all')) {
            $query->whereIn('seller_id', $user->getSharedSellerIds());
        }

        // Apply filters in SQL before fetching to bypass limit issues
        if ($filter == 'debt') {
            $query->whereHas('sales', function($q) {
                $q->where('type', 'credit')
                  ->where('status', 'pending');
            });
        } elseif ($filter == 'overdue') {
            $query->whereHas('sales', function($q) {
                $q->where('type', 'credit')
                  ->where('status', 'pending')
                  ->whereRaw('DATE_ADD(COALESCE(delivered_at, created_at), INTERVAL credit_days DAY) < NOW()');
            });
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $customers = $query->with(['sales' => function($q) {
                $q->where('type', 'credit')
                  ->where('status', 'pending')
                  ->with(['payments', 'returns', 'paymentDetails']);
            }])
            ->orderBy('name', 'asc')
            ->get();

        $formatted = $customers->map(function ($c) {
            $totalDebtUSD = 0;
            $hasOverdue = false;
            $pendingCount = 0;

            foreach ($c->sales as $sale) {
                // USD Debt logic (mirrored from PaymentController)
                $totalPaidUSD = $sale->payments->whereNotIn('status', ['pending', 'rejected'])->sum(function($p) {
                    $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                    $amountUSD = $p->amount / $rate; 
                    $discountVal = $p->discount_applied ?? 0;
                    return ($p->rule_type === 'overdue') ? ($amountUSD - $discountVal) : ($amountUSD + $discountVal);
                });
                
                $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                    $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                    return $detail->amount / $rate;
                });

                $totalReturnsUSD = $sale->returns->where('refund_method', 'debt_reduction')->sum('total_returned') / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1);
                
                $totalUSD = $sale->total_usd ?: ($sale->total / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1));
                
                $debtUSD = max(0, $totalUSD - ($totalPaidUSD + $initialPaidUSD + $totalReturnsUSD));

                if ($debtUSD > 0.05) {
                    $totalDebtUSD += $debtUSD;
                    $pendingCount++;
                    
                    // Overdue check
                    $startDate = $sale->delivered_at ? \Carbon\Carbon::parse($sale->delivered_at) : \Carbon\Carbon::parse($sale->created_at);
                    $creditDays = $sale->credit_days ?? ($sale->customer->credit_days ?? 0);
                    $dueDate = $startDate->copy()->addDays($creditDays);
                    if ($dueDate->isPast() && !$dueDate->isToday()) {
                        $hasOverdue = true;
                    }
                }
            }

            return [
                'id' => $c->id,
                'name' => $c->name,
                'total_debt' => round($totalDebtUSD, 2),
                'pending_count' => $pendingCount,
                'has_overdue' => $hasOverdue
            ];
        });

        return response()->json($formatted);
    }
}
