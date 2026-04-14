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
            $query->where('seller_id', $user->id);
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
                  ->where('status', 'pending');
            }])
            ->orderBy('name', 'asc')
            ->limit(200) // Increased limit to be safer
            ->get();

        $formatted = $customers->map(function ($c) {
            $totalDebt = 0;
            $hasOverdue = false;
            $pendingSales = [];

            foreach ($c->sales as $sale) {
                $debt = $sale->debt;
                if ($debt > 0.05) { // Real debt threshold
                    $totalDebt += $debt;
                    $pendingSales[] = $sale;
                    if ($sale->days_overdue > 0) {
                        $hasOverdue = true;
                    }
                }
            }

            return [
                'id' => $c->id,
                'name' => $c->name,
                'total_debt' => round($totalDebt, 2),
                'pending_count' => count($pendingSales),
                'has_overdue' => $hasOverdue
            ];
        });

        return response()->json($formatted);
    }
}
