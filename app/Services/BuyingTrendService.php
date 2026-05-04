<?php

namespace App\Services;

use App\Models\SaleDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuyingTrendService
{
    /**
     * Get buying trends for a customer in a specific warehouse.
     *
     * @param int $customerId
     * @param int $warehouseId
     * @param int $limit
     * @return Collection
     */
    public function getTrends(int $customerId, int $warehouseId, int $limit = 10): Collection
    {
        $cutoffDate = now()->subDays(90)->toDateTimeString();

        return SaleDetail::query()
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->where('sales.customer_id', $customerId)
            ->whereNull('sale_details.deleted_at')
            ->whereNull('sales.deleted_at')
            ->select(
                'sale_details.product_id',
                DB::raw("SUM(CASE WHEN sale_details.created_at >= '$cutoffDate' THEN 1 ELSE 0 END) * 0.7 + COUNT(*) * 0.3 as trend_score")
            )
            ->groupBy('sale_details.product_id')
            ->orderByDesc('trend_score')
            ->limit($limit * 2) // Get more initially to filter by stock
            ->get()
            ->map(function ($detail) use ($warehouseId) {
                $product = \App\Models\Product::with(['warehouses' => function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }])->find($detail->product_id);

                if (!$product || $product->deleted_at) {
                    return null;
                }

                $stock = $product->warehouses->first()->pivot->stock_qty ?? 0;
                if ($stock <= 0) {
                    return null;
                }

                $product->trend_score = $detail->trend_score;
                return $product;
            })
            ->filter()
            ->take($limit);
    }
}
