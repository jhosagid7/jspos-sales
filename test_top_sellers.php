<?php

use App\Models\SaleDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$topSellers = SaleDetail::query()
    ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
    ->join('products', 'sale_details.product_id', '=', 'products.id')
    ->join('customers', 'sales.customer_id', '=', 'customers.id')
    ->join('users', 'customers.seller_id', '=', 'users.id')
    ->join('model_has_roles', function($join) {
        $join->on('users.id', '=', 'model_has_roles.model_id')
             ->where('model_has_roles.model_type', 'App\Models\User');
    })
    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
    ->select(
        'users.name as seller_name',
        DB::raw('SUM((sale_details.sale_price - COALESCE(products.cost, 0)) * sale_details.quantity) as total_profit')
    )
    ->where('sales.status', 'paid')
    ->whereMonth('sales.created_at', Carbon::now()->month)
    ->where('roles.name', 'Vendedor')
    ->groupBy('users.name')
    ->orderByDesc('total_profit')
    ->take(5)
    ->get();

dump($topSellers->toArray());
