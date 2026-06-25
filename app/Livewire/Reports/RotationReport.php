<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RotationReport extends Component
{
    use WithPagination;

    public $categoryId = 0;
    public $supplierId = 0;
    public $customerId = 0;
    public $dateFrom;
    public $dateTo;
    public $pagination = 10;
    public $coverageDays = 30;
    public $status = ''; // low, high, none
    public $search = '';
    public $selectedProducts = [];

    // KPI Properties
    public $totalCapital = 0;
    public $idleCapital = 0;
    public $totalMargin = 0;
    public $avgMarginPercent = 0;

    // Charts Properties
    public $abcChartData = [];
    public $topProfitChartData = [];

    // Auxiliary map for ABC classification
    public $abcMap = [];

    public function mount()
    {
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        
        $config = \App\Models\Configuration::first();
        $this->coverageDays = $config->purchasing_coverage_days ?? 30;
        
        $this->selectedProducts = [];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedCategoryId()
    {
        $this->resetPage();
    }

    public function updatedSupplierId()
    {
        $this->resetPage();
    }

    public function updatedCustomerId()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    private function cleanString($string)
    {
        if (is_null($string)) return '';
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
        return trim($string);
    }

    public function render()
    {
        $data = $this->getRotationData();

        $this->dispatch('updateRotationCharts', 
            abcData: $this->abcChartData, 
            topProfitData: $this->topProfitChartData
        );

        $categories = Category::select('id', 'name')->orderBy('name')->get()->transform(function($item) {
            $item->name = $this->cleanString($item->name);
            return $item;
        });

        $suppliers = Supplier::select('id', 'name')->orderBy('name')->get()->transform(function($item) {
            $item->name = $this->cleanString($item->name);
            return $item;
        });

        $customers = Customer::select('id', 'name')->orderBy('name')->get()->transform(function($item) {
            $item->name = $this->cleanString($item->name);
            return $item;
        });

        return view('livewire.reports.rotation-report', [
            'data' => $data,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'customers' => $customers,
            'selectedProducts' => $this->selectedProducts
        ])->extends('layouts.theme.app')
          ->section('content');
    }

    public function getQuery()
    {
        $startDate = Carbon::parse($this->dateFrom)->startOfDay();
        $endDate = Carbon::parse($this->dateTo)->endOfDay();
        $daysDiff = $startDate->diffInDays($endDate) ?: 1;

        $customerCondition = "";
        $bindings = [$startDate, $endDate];
        
        if ($this->customerId > 0) {
            $customerCondition = " AND sales.customer_id = ?";
            $bindings[] = $this->customerId;
        }

        $allBindings = array_merge($bindings, $bindings, $bindings);

        $query = Product::query()
            ->leftJoin('sale_details', 'products.id', '=', 'sale_details.product_id')
            ->leftJoin('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->select(
                'products.id',
                'products.name',
                'products.stock_qty',
                'products.cost',
                'products.price',
                DB::raw("COALESCE(SUM(CASE WHEN sales.status IN ('PAID', 'PENDING', 'paid', 'pending') AND sales.created_at BETWEEN ? AND ? $customerCondition THEN sale_details.quantity ELSE 0 END), 0) as total_sold"),
                DB::raw("COALESCE(SUM(CASE WHEN sales.status IN ('PAID', 'PENDING', 'paid', 'pending') AND sales.created_at BETWEEN ? AND ? $customerCondition THEN sale_details.quantity * sale_details.sale_price ELSE 0 END), 0) as total_sold_usd"),
                DB::raw("COUNT(DISTINCT CASE WHEN sales.status IN ('PAID', 'PENDING', 'paid', 'pending') AND sales.created_at BETWEEN ? AND ? $customerCondition THEN DATE(sales.created_at) END) as days_with_sales")
            )
            ->setBindings($allBindings, 'select');

        if ($this->categoryId > 0) {
            $query->where('products.category_id', $this->categoryId);
        }

        if ($this->supplierId > 0) {
            $query->where('products.supplier_id', $this->supplierId);
        }

        if (strlen($this->search) > 0) {
            $query->where('products.name', 'like', '%' . $this->search . '%');
        }

        $query->groupBy('products.id', 'products.name', 'products.stock_qty', 'products.cost', 'products.price');
        
        if ($this->status) {
            if ($this->status == 'low') {
                $query->havingRaw('total_sold > 0 AND (total_sold / ?) < 1', [$daysDiff]);
            } elseif ($this->status == 'high') {
                $query->havingRaw('(total_sold / ?) >= 1', [$daysDiff]);
            } elseif ($this->status == 'none') {
                $query->havingRaw('total_sold = 0');
            }
        }

        $query->orderByDesc('total_sold');
        
        return $query->toBase();
    }

    public function precalculateAbcAndKpis($allProducts)
    {
        $totalSalesUsd = $allProducts->sum('total_sold_usd');
        
        // Sort descending by sales USD
        $sorted = $allProducts->sortByDesc('total_sold_usd');
        
        $cumulative = 0;
        $abcMap = [];
        $abcCounts = ['A' => 0, 'B' => 0, 'C' => 0];
        
        foreach ($sorted as $product) {
            if ($product->total_sold_usd <= 0) {
                $abcMap[$product->id] = 'C';
                $abcCounts['C']++;
                continue;
            }
            $cumulative += $product->total_sold_usd;
            $percent = $totalSalesUsd > 0 ? ($cumulative / $totalSalesUsd) * 100 : 100;
            
            if ($percent <= 80.0001) {
                $abcMap[$product->id] = 'A';
                $abcCounts['A']++;
            } elseif ($percent <= 95.0001) {
                $abcMap[$product->id] = 'B';
                $abcCounts['B']++;
            } else {
                $abcMap[$product->id] = 'C';
                $abcCounts['C']++;
            }
        }
        
        $this->abcMap = $abcMap;

        // Calculate KPIs
        $this->totalCapital = 0;
        $this->idleCapital = 0;
        $this->totalMargin = 0;
        $totalSales = 0;

        foreach ($allProducts as $product) {
            $cost = floatval($product->cost);
            $stock = floatval($product->stock_qty);
            $sold = floatval($product->total_sold);
            $soldUsd = floatval($product->total_sold_usd);

            $this->totalCapital += $stock * $cost;
            if ($sold == 0) {
                $this->idleCapital += $stock * $cost;
            }
            
            $costUsd = $sold * $cost;
            $margin = $soldUsd - $costUsd;
            
            $this->totalMargin += $margin;
            $totalSales += $soldUsd;
        }

        $this->avgMarginPercent = $totalSales > 0 ? round(($this->totalMargin / $totalSales) * 100, 2) : 0;

        // Format charts data
        $this->abcChartData = [
            ['name' => 'Clase A (Alta Relevancia)', 'y' => $abcCounts['A'], 'color' => '#2ec4b6'],
            ['name' => 'Clase B (Relevancia Media)', 'y' => $abcCounts['B'], 'color' => '#ff9f1c'],
            ['name' => 'Clase C (Baja Relevancia/Sin Mov)', 'y' => $abcCounts['C'], 'color' => '#e71d36'],
        ];

        // Top 10 profitable products
        $profitableProducts = $sorted->map(function($product) {
            $cost = floatval($product->cost);
            $sold = floatval($product->total_sold);
            $soldUsd = floatval($product->total_sold_usd);
            $margin = $soldUsd - ($sold * $cost);
            return [
                'name' => $this->cleanString($product->name),
                'margin' => round($margin, 2)
            ];
        })->filter(fn($p) => $p['margin'] > 0)->sortByDesc('margin')->take(10)->values()->toArray();

        $this->topProfitChartData = $profitableProducts;
    }

    public function getRotationData()
    {
        // 1. Get all filtered products to precalculate global ABC & KPIs
        $allProducts = $this->getQuery()->get();
        $this->precalculateAbcAndKpis($allProducts);

        // 2. Paginate over the database query
        $products = $this->getQuery()->paginate($this->pagination);
        return $this->processMetrics($products);
    }

    public function processMetrics($products)
    {
        $startDate = Carbon::parse($this->dateFrom)->startOfDay();
        $endDate = Carbon::parse($this->dateTo)->endOfDay();
        $daysDiff = $startDate->diffInDays($endDate) ?: 1;

        $collection = $products instanceof \Illuminate\Pagination\LengthAwarePaginator ? $products->getCollection() : $products;

        $collection->transform(function ($product) use ($daysDiff) {
            $product->name = $this->cleanString($product->name);
            
            $product->velocity = round($product->total_sold / $daysDiff, 2);
            $product->coverage_days = $product->velocity > 0 ? round($product->stock_qty / $product->velocity) : 999;
            
            $daysToCover = max(1, intval($this->coverageDays));
            $product->monthly_demand = ceil($product->velocity * $daysToCover);
            
            $product->suggested_order = max(0, $product->monthly_demand - $product->stock_qty);

            if ($product->velocity == 0) {
                $product->rotation_status = 'Sin Movimiento';
                $product->status_color = 'danger';
            } elseif ($product->velocity >= 1) {
                $product->rotation_status = 'Alta Rotacion';
                $product->status_color = 'success';
            } else {
                $product->rotation_status = 'Baja Rotacion';
                $product->status_color = 'warning';
            }

            // Margins & Financial Calculations
            $product->sales_usd = floatval($product->total_sold_usd);
            $product->cost_usd = floatval($product->total_sold) * floatval($product->cost);
            $product->margin_usd = $product->sales_usd - $product->cost_usd;
            $product->margin_percent = $product->sales_usd > 0 ? round(($product->margin_usd / $product->sales_usd) * 100, 2) : 0;
            $product->stock_value = floatval($product->stock_qty) * floatval($product->cost);
            $product->abc_class = $this->abcMap[$product->id] ?? 'C';

            return $product;
        });

        return $products;
    }

    public function generatePdf()
    {
        try {
            if (count($this->selectedProducts) > 0) {
                $query = $this->getQuery();
                $query->whereIn('products.id', $this->selectedProducts);
                $data = $query->get();
            } else {
                $data = $this->getQuery()->get();
            }

            $this->precalculateAbcAndKpis($data);
            $data = $this->processMetrics($data);
            
            $config = \App\Models\Configuration::first();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('livewire.reports.rotation-report-pdf', [
                'data' => $data,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'coverageDays' => $this->coverageDays,
                'config' => $config,
                'totalCapital' => $this->totalCapital,
                'idleCapital' => $this->idleCapital,
                'totalMargin' => $this->totalMargin,
                'avgMarginPercent' => $this->avgMarginPercent,
            ])->setPaper('a4', 'landscape');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'Reporte_Rotacion.pdf');

        } catch (\Exception $e) {
            $this->dispatch('noty', msg: "Error al generar PDF: " . $e->getMessage());
            return;
        }
    }

    public function createPurchaseOrder()
    {
        if (count($this->selectedProducts) == 0) {
            $this->dispatch('noty', msg: 'Selecciona al menos un producto');
            return;
        }

        $query = $this->getQuery();
        $query->whereIn('products.id', $this->selectedProducts);
        $products = $query->get();
        
        $this->precalculateAbcAndKpis($products);
        $products = $this->processMetrics($products);
        
        $orderItems = [];
        
        foreach ($products as $product) {
            if ($product->suggested_order > 0) {
                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $product->suggested_order
                ];
            }
        }

        if (count($orderItems) == 0) {
            $this->dispatch('noty', msg: 'Los productos seleccionados no tienen sugerencia de compra (Stock suficiente)');
            return;
        }

        session(['purchase_order_from_report' => $orderItems]);
        
        return redirect()->to('/purchases');
    }
}
