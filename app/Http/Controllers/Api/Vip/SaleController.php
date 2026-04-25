<?php

namespace App\Http\Controllers\Api\Vip;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;

class SaleController extends Controller
{
    /**
     * Get VIP Customer's own sales (purchases)
     */
    public function index(Request $request)
    {
        $customer = $request->user();
        
        $sales = Sale::with(['details.product'])
            ->where('customer_id', $customer->id)
            ->orderBy('id', 'desc')
            ->limit(100) 
            ->get();

        return response()->json($sales);
    }
}
