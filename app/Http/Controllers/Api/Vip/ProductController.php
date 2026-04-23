<?php

namespace App\Http\Controllers\Api\Vip;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Configuration;

class ProductController extends Controller
{
    /**
     * Display a listing of products with VIP Customer tailored prices.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $customer = $request->user();
        
        $globalConfig = Configuration::first();
        $checkReservation = $globalConfig->check_stock_reservation ?? false;

        // Get Config (Customer specific or Seller fallback)
        $config = $customer->latestCustomerConfig;
        
        // If customer doesn't have a specific config, inherit from their seller
        if (!$config && $customer->seller) {
            $config = $customer->seller->latestSellerConfig;
        }

        // Warehouse determination: From Seller -> Default global
        $warehouseId = $customer->seller->warehouse_id ?? $globalConfig->default_warehouse_id ?? 1;

        $products = Product::query()
            ->when($search, function ($query, $search) {
                return $query->search($search);
            })
            ->limit(50)
            ->get();

        $primaryCurrency = \App\Models\Currency::where('is_primary', 1)->first();
        $exchangeRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;

        $products->transform(function ($product) use ($config, $exchangeRate, $warehouseId, $checkReservation) {
            $basePrice = $product->price * $exchangeRate;
            $finalPrice = $basePrice;

            if ($config) {
                $comm = ($basePrice * ($config->commission_percent ?? 0)) / 100;
                $diff = ($basePrice * ($config->exchange_diff_percent ?? 0)) / 100;
                
                if ($product->freight_type != 'none') {
                    if ($product->freight_type == 'fixed') {
                        $freight = $product->freight_value * $exchangeRate;
                    } else {
                        $freight = ($basePrice * $product->freight_value) / 100;
                    }
                } else {
                    $freight = ($basePrice * ($config->freight_percent ?? 0)) / 100;
                }

                $finalPrice = $basePrice + $comm + $diff + $freight;
            }

            $physicalStock = (float) $product->stockIn($warehouseId);
            $reservedStock = (float) $product->getReservedStock($warehouseId);
            $availableStock = $checkReservation ? ($physicalStock - $reservedStock) : $physicalStock;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) round($finalPrice, 2),
                'stock' => $physicalStock,
                'reserved_stock' => $reservedStock,
                'available_stock' => $availableStock > 0 ? $availableStock : 0,
                'check_reservation' => $checkReservation,
                'image_path' => $product->photo ?? 'noimage.jpg',
            ];
        });

        return response()->json($products);
    }
}
