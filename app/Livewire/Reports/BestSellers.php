<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\SaleDetail;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BestSellers extends Component
{
    public $dateFrom, $dateTo;
    public $chartType = 'column'; // Highcharts type
    public $isDetailed = false;
    public $limit = 10;

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function render()
    {
        $data = $this->getData();
        $highchartsData = $this->getHighchartsData($data);

        $this->dispatch('chart-updated', data: $highchartsData, type: $this->chartType);

        return view('livewire.reports.best-sellers', [
            'data' => $data,
            'highchartsData' => $highchartsData
        ])->layout('layouts.theme.app');
    }

    public function getData()
    {
        // 1. Aggregate First (Fast Query)
        // Only query sales and sale_details to get the top product IDs and totals.
        // This avoids joining products and categories tables for every single sale detail row before grouping.
        $aggregated = SaleDetail::query()
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->select(
                'sale_details.product_id',
                DB::raw('SUM(sale_details.quantity) as total_qty'),
                DB::raw('SUM(sale_details.quantity * sale_details.sale_price) as total_sales')
            )
            ->whereBetween('sales.created_at', [$this->dateFrom . ' 00:00:00', $this->dateTo . ' 23:59:59'])
            ->where('sales.status', 'paid')
            ->groupBy('sale_details.product_id')
            ->orderBy('total_qty', 'desc');

        if (!$this->isDetailed) {
            $aggregated->limit($this->limit);
        }

        $results = $aggregated->get();

        if ($results->isEmpty()) {
            return collect();
        }

        // 2. Fetch Details Later (Deferred Join)
        // Now fetch the product details only for the aggregated results.
        $productIds = $results->pluck('product_id');
        
        $products = \App\Models\Product::with(['category', 'images'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        // Calculate grand totals for percentages
        $grandTotalQty = $results->sum('total_qty');
        $grandTotalSales = $results->sum('total_sales');

        // 3. Merge and Process
        $processed = $results->map(function($item) use ($products, $grandTotalQty, $grandTotalSales) {
            $product = $products->get($item->product_id);
            
            if (!$product) {
                // Handle case where product might have been deleted but sales exist
                return null;
            }

            $totalCost = $item->total_qty * $product->cost;
            $profit = $item->total_sales - $totalCost;
            $margin = $item->total_sales > 0 ? ($profit / $item->total_sales) * 100 : 0;
            
            $image = $product->photo;
            // Normalize image to full URL
            if ($image && !str_starts_with($image, 'http')) {
                $image = asset($image);
            }

            return (object) [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name ?? 'Sin Categoría',
                'image' => $image,
                'barcode' => $product->sku, 
                'sku' => $product->sku,
                'total_qty' => $item->total_qty,
                'total_sales' => $item->total_sales,
                'total_cost' => $totalCost,
                'profit' => $profit,
                'margin' => $margin,
                'percentage_qty' => $grandTotalQty > 0 ? ($item->total_qty / $grandTotalQty) * 100 : 0,
                'percentage_sales' => $grandTotalSales > 0 ? ($item->total_sales / $grandTotalSales) * 100 : 0,
            ];
        })->filter(); // Remove nulls

        return $processed;
    }

    public function getHighchartsData($data)
    {
        // Group data by category
        $grouped = $data->groupBy('category');
        
        $grandTotal = $data->sum('total_qty');
        
        $seriesData = [];
        $drilldownSeries = [];

        foreach ($grouped as $categoryName => $items) {
            $categoryTotal = $items->sum('total_qty');
            $categoryPercentage = $grandTotal > 0 ? ($categoryTotal / $grandTotal) * 100 : 0;

            // Main Series Data (Categories)
            $seriesData[] = [
                'name' => $categoryName,
                'y' => floatval(number_format($categoryPercentage, 2)),
                'drilldown' => $categoryName
            ];

            // Drilldown Data (Products in Category)
            $drilldownData = [];
            foreach ($items as $item) {
                $productPercentage = $grandTotal > 0 ? ($item->total_qty / $grandTotal) * 100 : 0;
                // Or should it be percentage within category? 
                // Usually drilldown shows contribution to the whole or the category.
                // Highcharts drilldown usually sums up to the category value if stacked, but here it's a separate view.
                // Let's use percentage of the GRAND TOTAL to be consistent with the main view, 
                // OR percentage of the CATEGORY. 
                // The user's code: "Total percent market share".
                // Let's stick to percentage of Grand Total for now.
                
                $drilldownData[] = [
                    $item->name,
                    floatval(number_format($productPercentage, 2))
                ];
            }

            $drilldownSeries[] = [
                'name' => $categoryName,
                'id' => $categoryName,
                'data' => $drilldownData
            ];
        }

        return [
            'series' => json_encode($seriesData),
            'drilldown' => json_encode($drilldownSeries)
        ];
    }
}
