<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SaleDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductStatisticsService
{
    /**
     * Calculate average daily sales over the last X days.
     */
    /**
     * Calculate average daily sales.
     * Respects 'purchasing_calculation_mode' from configuration.
     */
    public function calculateVelocity(Product $product, $days = 30)
    {
        $config = \App\Models\Configuration::first();
        $mode = $config->purchasing_calculation_mode ?? 'recent';

        if ($mode === 'seasonal') {
            // Seasonal: Same period last year (e.g., last 30 days but 1 year ago)
            $startDate = Carbon::now()->subYear()->subDays($days);
            $endDate = Carbon::now()->subYear();
            
            $totalSold = SaleDetail::where('product_id', $product->id)
                ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate])
                      ->where('status', 'PAID');
                })
                ->sum('quantity');
                
            return $days > 0 ? round($totalSold / $days, 2) : 0;
        } else {
            // Recent: Last X days (default behavior)
            $startDate = Carbon::now()->subDays($days);
            
            $totalSold = SaleDetail::where('product_id', $product->id)
                ->whereHas('sale', function ($q) use ($startDate) {
                    $q->where('created_at', '>=', $startDate)
                      ->where('status', 'PAID');
                })
                ->sum('quantity');

            return $days > 0 ? round($totalSold / $days, 2) : 0;
        }
    }

    /**
     * Get the last sale details.
     */
    public function getLastSale(Product $product)
    {
        $lastDetail = SaleDetail::where('product_id', $product->id)
            ->whereHas('sale', function ($q) {
                $q->where('status', 'PAID');
            })
            ->with(['sale.customer'])
            ->latest()
            ->first();

        if (!$lastDetail) {
            return null;
        }

        return [
            'date' => $lastDetail->created_at->format('d/m/Y H:i'),
            'customer' => $lastDetail->sale->customer->name ?? 'Cliente General',
            'quantity' => $lastDetail->quantity,
            'price' => $lastDetail->sale_price
        ];
    }

    /**
     * Calculate sales frequency (days with sales / total days).
     */
    public function getSalesFrequency(Product $product, $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);
        
        $daysWithSales = SaleDetail::where('product_id', $product->id)
            ->whereHas('sale', function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate)
                  ->where('status', 'PAID');
            })
            ->distinct()
            ->count(DB::raw('DATE(created_at)'));

        return [
            'days_with_sales' => $daysWithSales,
            'total_days' => $days,
            'frequency_percentage' => $days > 0 ? round(($daysWithSales / $days) * 100, 1) : 0
        ];
    }

    /**
     * Get monthly sales trend for the last 12 months.
     */
    public function getSalesTrend(Product $product, $months = 12)
    {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        
        $sales = SaleDetail::where('product_id', $product->id)
            ->whereHas('sale', function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate)
                  ->where('status', 'PAID');
            })
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(quantity) as total_qty')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $trend = [];
        $current = $startDate->copy();
        $end = Carbon::now()->endOfMonth();

        while ($current <= $end) {
            $key = $current->format('Y-m');
            $label = $current->format('M Y');
            
            $record = $sales->first(function ($item) use ($current) {
                return $item->year == $current->year && $item->month == $current->month;
            });

            $trend[] = [
                'label' => $label,
                'value' => $record ? (float)$record->total_qty : 0
            ];

            $current->addMonth();
        }

        return $trend;
    }

    /**
     * Get top customers for this product.
     */
    public function getTopCustomers(Product $product, $limit = 5)
    {
        return SaleDetail::where('product_id', $product->id)
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.status', 'PAID')
            ->selectRaw('customers.name, SUM(sale_details.quantity) as total_qty, SUM(sale_details.quantity * sale_details.sale_price) as total_amount')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'quantity' => (float)$item->total_qty,
                    'amount' => (float)$item->total_amount
                ];
            });
    }

    /**
     * Calculate purchase suggestion based on days coverage.
     * Uses 'purchasing_coverage_days' from configuration.
     */
    public function getPurchaseSuggestion(Product $product)
    {
        $config = \App\Models\Configuration::first();
        $daysCoverage = $config->purchasing_coverage_days ?? 15;

        $velocity = $this->calculateVelocity($product, 30); // Use 30 days average (or seasonal 30 days)
        $requiredStock = $velocity * $daysCoverage;
        $currentStock = $product->stock_qty;
        
        $suggestion = $requiredStock - $currentStock;

        return [
            'velocity' => $velocity,
            'days_coverage' => $daysCoverage,
            'required_stock' => round($requiredStock, 2),
            'current_stock' => $currentStock,
            'suggestion' => $suggestion > 0 ? ceil($suggestion) : 0
        ];
    }
}
