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
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->limit(100)
            ->get();

        return response()->json($customers);
    }
}
