<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    /**
     * Display a listing of products with optional search.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $user = $request->user();
        $warehouseId = $user->warehouse_id ?? \App\Models\Configuration::first()->default_warehouse_id;
        $globalConfig = \App\Models\Configuration::first();
        $checkReservation = $globalConfig->check_stock_reservation ?? false;

        // Get Config (Customer specific or Seller fallback)
        $customerId = $request->input('customer_id');
        $config = null;

        if ($customerId) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $config = $customer->latestCustomerConfig;
            }
        }

        if (!$config) {
            $config = $user->latestSellerConfig;
        }

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
                $comm = ($basePrice * $config->commission_percent) / 100;
                $diff = ($basePrice * $config->exchange_diff_percent) / 100;
                
                if ($product->freight_type != 'none') {
                    if ($product->freight_type == 'fixed') {
                        $freight = $product->freight_value * $exchangeRate;
                    } else {
                        $freight = ($basePrice * $product->freight_value) / 100;
                    }
                } else {
                    $freight = ($basePrice * $config->freight_percent) / 100;
                }

                $finalPrice = $basePrice + $comm + $diff + $freight;
            }

            $warehouseId = $warehouseId ?? 1; // Seguridad: Si todo falla, usa la oficina (ID 1)
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

