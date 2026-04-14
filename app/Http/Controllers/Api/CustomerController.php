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
        $user = $request->user();

        // Reps should only see their own customers? 
        // Based on Customer model, there is a seller_id.
        $customers = Customer::query()
            ->when(!$user->can('customers.view_all') && $user->can('customers.view_own'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($request->input('search'), function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->with(['sales' => function($q) {
                $q->where('type', 'credit')
                  ->where('status', 'pending');
            }])
            ->orderBy('name', 'asc')
            ->limit(100)
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
