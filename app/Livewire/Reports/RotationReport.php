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
    public $rawMaterialFilter = 'finished'; // finished, raw_materials, all
    public $selectedProducts = [];
    public $tagId = 0;
    public $showInterpretationModal = false;

    public $selectedPdfColumns = [
        'product',
        'abc_class',
        'stock_qty',
        'stock_value',
        'total_sold',
        'sales_usd',
        'margin_usd',
        'margin_percent',
        'velocity',
        'suggested_order',
        'coverage_days',
        'rotation_status',
    ];

    public $availablePdfColumns = [
        'product' => 'Producto',
        'abc_class' => 'Clase ABC',
        'stock_qty' => 'Stock Actual',
        'stock_value' => 'Valor Stock',
        'total_sold' => 'Vendido',
        'sales_usd' => 'Ventas USD',
        'margin_usd' => 'Margen USD',
        'margin_percent' => 'Margen %',
        'velocity' => 'Velocidad',
        'suggested_order' => 'Sugerencia',
        'coverage_days' => 'Cobertura',
        'rotation_status' => 'Estado',
    ];

    public $selectedKpis = [
        'totalCapital',
        'idleCapital',
        'totalMargin',
        'avgMarginPercent',
    ];

    public $availableKpis = [
        'totalCapital' => 'Capital en Inventario',
        'idleCapital' => 'Capital Ocioso (Sin Mov)',
        'totalMargin' => 'Ganancia Bruta Ventas',
        'avgMarginPercent' => 'Margen Promedio (%)',
    ];

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

    public function updatedRawMaterialFilter()
    {
        $this->resetPage();
    }

    public function updatedTagId()
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

        $tags = \App\Models\Tag::select('id', 'name')->orderBy('name')->get()->transform(function($item) {
            $item->name = $this->cleanString($item->name);
            return $item;
        });

        return view('livewire.reports.rotation-report', [
            'data' => $data,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'customers' => $customers,
            'tags' => $tags,
            'selectedProducts' => $this->selectedProducts,
            'selectedCount' => count($this->getSelectedIds()),
            'availablePdfColumns' => $this->availablePdfColumns,
            'availableKpis' => $this->availableKpis,
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
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.stock_qty',
                'products.cost',
                'products.price',
                'categories.name as category_name',
                DB::raw("COALESCE(SUM(CASE WHEN sales.status IN ('PAID', 'PENDING', 'paid', 'pending') AND sales.created_at BETWEEN ? AND ? $customerCondition THEN sale_details.quantity ELSE 0 END), 0) as total_sold"),
                DB::raw("COALESCE(SUM(CASE WHEN sales.status IN ('PAID', 'PENDING', 'paid', 'pending') AND sales.created_at BETWEEN ? AND ? $customerCondition THEN sale_details.quantity * (sale_details.sale_price / COALESCE(NULLIF(sales.primary_exchange_rate, 0), 1)) ELSE 0 END), 0) as total_sold_usd"),
                DB::raw("COUNT(DISTINCT CASE WHEN sales.status IN ('PAID', 'PENDING', 'paid', 'pending') AND sales.created_at BETWEEN ? AND ? $customerCondition THEN DATE(sales.created_at) END) as days_with_sales")
            )
            ->setBindings($allBindings, 'select');

        if ($this->categoryId > 0) {
            $query->where('products.category_id', $this->categoryId);
        }

        if ($this->tagId > 0) {
            $query->whereHas('tags', function ($q) {
                $q->where('tags.id', $this->tagId);
            });
        }

        if ($this->supplierId > 0) {
            $query->where('products.supplier_id', $this->supplierId);
        }

        if (strlen($this->search) > 0) {
            $query->where('products.name', 'like', '%' . $this->search . '%');
        }

        if ($this->rawMaterialFilter === 'finished') {
            $query->where('products.is_raw_material', false);
        } elseif ($this->rawMaterialFilter === 'raw_materials') {
            $query->where('products.is_raw_material', true);
        }

        $query->groupBy('products.id', 'products.name', 'products.sku', 'products.stock_qty', 'products.cost', 'products.price', 'categories.name');
        
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

    public function getSelectedIds()
    {
        $ids = [];
        if (is_array($this->selectedProducts)) {
            foreach ($this->selectedProducts as $key => $val) {
                // Case 1: associative array [ '12' => true ] or [ '12' => '12' ]
                if ($val === true || $val === 'true' || (string)$key === (string)$val) {
                    $ids[] = $key;
                }
                // Case 2: flat array [ 0 => '12', 1 => '18' ] where value is not boolean
                elseif ($val !== false && $val !== null && $val !== '' && $val !== 0 && $val !== '0') {
                    $ids[] = $val;
                }
            }
        }
        return array_unique(array_filter($ids));
    }

    public function moveProductUp($productId)
    {
        $selectedIds = $this->getSelectedIds();
        $index = array_search($productId, $selectedIds);
        if ($index !== false && $index > 0) {
            $temp = $selectedIds[$index - 1];
            $selectedIds[$index - 1] = $selectedIds[$index];
            $selectedIds[$index] = $temp;
            $this->selectedProducts = $selectedIds;
        }
    }

    public function moveProductDown($productId)
    {
        $selectedIds = $this->getSelectedIds();
        $index = array_search($productId, $selectedIds);
        if ($index !== false && $index < count($selectedIds) - 1) {
            $temp = $selectedIds[$index + 1];
            $selectedIds[$index + 1] = $selectedIds[$index];
            $selectedIds[$index] = $temp;
            $this->selectedProducts = $selectedIds;
        }
    }

    public function reorderProducts($from, $to)
    {
        $selectedIds = $this->getSelectedIds();
        if (isset($selectedIds[$from]) && isset($selectedIds[$to])) {
            $item = array_splice($selectedIds, $from, 1)[0];
            array_splice($selectedIds, $to, 0, $item);
            $this->selectedProducts = $selectedIds;
        }
    }

    public function generatePdf()
    {
        try {
            $selectedIds = $this->getSelectedIds();
            if (count($selectedIds) > 0) {
                $query = $this->getQuery();
                $query->whereIn('products.id', $selectedIds);
                $data = $query->get();
                $data = $data->sortBy(function($item) use ($selectedIds) {
                    return array_search($item->id, $selectedIds);
                })->values();
            } else {
                $data = $this->getQuery()->get();
            }

            $this->precalculateAbcAndKpis($data);
            $data = $this->processMetrics($data);
            $config = \App\Models\Configuration::first();
            $tagName = $this->tagId > 0 ? (\App\Models\Tag::find($this->tagId)?->name ?? 'N/A') : 'Todos';

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
                'tagName' => $tagName,
                'selectedPdfColumns' => !empty($this->selectedPdfColumns) ? $this->selectedPdfColumns : array_keys($this->availablePdfColumns),
                'selectedKpis' => $this->selectedKpis,
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
        $selectedIds = $this->getSelectedIds();
        if (count($selectedIds) == 0) {
            $this->dispatch('noty', msg: 'Selecciona al menos un producto');
            return;
        }

        $query = $this->getQuery();
        $query->whereIn('products.id', $selectedIds);
        $products = $query->get();
        $products = $products->sortBy(function($item) use ($selectedIds) {
            return array_search($item->id, $selectedIds);
        })->values();
        
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

    public function toggleInterpretationModal()
    {
        $this->showInterpretationModal = !$this->showInterpretationModal;
    }

    public function generateCatalogPdf()
    {
        try {
            if ($this->customerId == 0) {
                $this->dispatch('noty', msg: 'Debe seleccionar un cliente para generar el catálogo.');
                return;
            }

            $query = $this->getQuery();
            $data = $query->get();
            $this->precalculateAbcAndKpis($data);
            $processed = $this->processMetrics($data);

            // Filter only products NOT purchased by the customer in this period and have stock
            $unsoldProducts = $processed->filter(fn($p) => $p->total_sold == 0 && $p->stock_qty > 0);

            if ($unsoldProducts->count() == 0) {
                $this->dispatch('noty', msg: 'No hay productos con stock para ofrecer en este catálogo.');
                return;
            }

            $config = \App\Models\Configuration::first();
            $customer = \App\Models\Customer::find($this->customerId);
            $tagName = $this->tagId > 0 ? (\App\Models\Tag::find($this->tagId)?->name ?? 'N/A') : 'Todos';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('livewire.reports.rotation-report-catalog-pdf', [
                'products' => $unsoldProducts,
                'customer' => $customer,
                'config' => $config,
                'tagName' => $tagName,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
            ])->setPaper('a4', 'portrait');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'Catalogo_Ofertas_' . str_replace(' ', '_', $customer->name) . '.pdf');

        } catch (\Exception $e) {
            $this->dispatch('noty', msg: "Error al generar catálogo: " . $e->getMessage());
            return;
        }
    }

    public function getInterpretation()
    {
        $data = $this->getQuery()->get();
        $this->precalculateAbcAndKpis($data);
        $processed = $this->processMetrics($data);

        $totalProds = count($processed);
        $soldProds = $processed->filter(fn($p) => $p->total_sold > 0)->count();
        $unsoldProds = $totalProds - $soldProds;
        
        $idleRatio = $this->totalCapital > 0 ? ($this->idleCapital / $this->totalCapital) * 100 : 0;
        
        // Find top 3 most profitable products
        $top3 = $processed->sortByDesc('margin_usd')->take(3);
        
        $html = '';

        if ($this->customerId > 0) {
            $customer = \App\Models\Customer::find($this->customerId);
            $custName = $customer ? strtoupper($customer->name) : 'N/A';
            
            $html .= "<div class='p-2'>";
            $html .= "<h5 class='text-primary mb-3'><i class='fas fa-user-tie mr-2'></i> <b>Análisis de Compras de Cliente:</b> $custName</h5>";
            $html .= "<p class='text-muted'>Este análisis detalla el comportamiento de compra de <b>$custName</b> en tu tienda y su relación con tu stock disponible:</p>";
            
            $html .= "<div class='row mt-4'>";
            $html .= "<div class='col-md-6 mb-3'>";
            $html .= "<div class='p-3 bg-light rounded border h-100'>";
            $html .= "<h6><i class='fas fa-chart-line text-success mr-2'></i> <b>Ganancia y Rentabilidad</b></h6>";
            $html .= "<p class='mb-1'>• Margen Generado: <b>$" . number_format($this->totalMargin, 2) . "</b></p>";
            $html .= "<p class='mb-0'>• Rentabilidad Promedio: <b>" . number_format($this->avgMarginPercent, 2) . "%</b></p>";
            $html .= "</div>";
            $html .= "</div>";

            $html .= "<div class='col-md-6 mb-3'>";
            $html .= "<div class='p-3 bg-light rounded border h-100'>";
            $html .= "<h6><i class='fas fa-boxes text-info mr-2'></i> <b>Portafolio de Productos</b></h6>";
            if ($totalProds > 0) {
                $purchasedPercent = round(($soldProds / $totalProds) * 100, 1);
                $html .= "<p class='mb-1'>• Diversificación de Compra: Compra el <b>$purchasedPercent%</b> de tu catálogo.</p>";
            }
            $html .= "<p class='mb-0'>• Ha comprado <b>$soldProds de $totalProds</b> productos disponibles.</p>";
            $html .= "</div>";
            $html .= "</div>";
            $html .= "</div>";
            
            $html .= "<div class='p-3 bg-light rounded border mb-3 mt-2'>";
            $html .= "<h6><i class='fas fa-coins text-warning mr-2'></i> <b>Inventario Ocioso (Sin Compras de este Cliente)</b></h6>";
            $html .= "<p class='mb-1'>• Actualmente posees un capital de stock de <b>$" . number_format($this->idleCapital, 2) . "</b> en productos que este cliente <b>no te compra</b>.</p>";
            $html .= "<p class='mb-0'>• Esto representa el <b>" . number_format($idleRatio, 1) . "%</b> de tu inversión total de inventario actual.</p>";
            $html .= "</div>";
            
            if ($top3->count() > 0) {
                $html .= "<h6 class='mt-4 font-weight-bold text-dark'><i class='fas fa-award text-primary mr-2'></i> <b>Top 3 Productos Más Rentables para este Cliente:</b></h6>";
                $html .= "<div class='list-group mb-3'>";
                foreach ($top3 as $index => $p) {
                    $html .= "<div class='list-group-item list-group-item-action flex-column align-items-start'>";
                    $html .= "<div class='d-flex w-100 justify-content-between'>";
                    $html .= "<h6 class='mb-1 font-weight-bold text-info'>#" . ($index + 1) . " - {$p->name}</h6>";
                    $html .= "<span class='text-success font-weight-bold'>$" . number_format($p->margin_usd, 2) . "</span>";
                    $html .= "</div>";
                    $html .= "<p class='mb-1 small text-muted'>Compró: <b>{$p->total_sold} unidades</b> | Margen Unitario: <b>" . number_format($p->margin_percent, 1) . "%</b></p>";
                    $html .= "</div>";
                }
                $html .= "</div>";
            }

            // Recommendations
            $html .= "<div class='alert alert-info mt-4 border-0 shadow-sm'>";
            $html .= "<h5><i class='fas fa-lightbulb text-warning mr-2'></i> <b>Recomendaciones de Venta Cruzada (Cross-Selling)</b></h5>";
            $html .= "<hr class='my-2 bg-info'>";
            if ($idleRatio > 50) {
                $html .= "<p class='mb-0 small'>El cliente tiene un gran potencial de compra sin explotar, ya que no adquiere más del 50% de tu catálogo actual. Te sugerimos armar un catálogo impreso de sus productos \"Clase C / Ociosos\" o enviarle muestras de los productos Clase A generales del negocio para diversificar sus pedidos.</p>";
            } else {
                $html .= "<p class='mb-0 small'>El cliente posee una excelente diversificación y compra una gran parte de tu inventario. Concéntrate en fidelizarlo, asegurar stock de sus productos preferidos para evitar quiebres y ofrecerle descuentos por volumen en sus productos Clase B.</p>";
            }
            $html .= "</div>";
            $html .= "</div>";

        } else {
            // General Analysis
            $html .= "<div class='p-2'>";
            $html .= "<h5 class='text-primary mb-3'><i class='fas fa-heartbeat mr-2'></i> <b>Análisis de Salud de Inventario Global</b></h5>";
            $html .= "<p class='text-muted'>Este análisis diagnostica la rotación general de tu stock y la rentabilidad del negocio en base a los movimientos de este periodo:</p>";
            
            $html .= "<div class='row mt-4'>";
            $html .= "<div class='col-md-6 mb-3'>";
            $html .= "<div class='p-3 bg-light rounded border h-100'>";
            $html .= "<h6><i class='fas fa-chart-line text-success mr-2'></i> <b>Ganancia y Margen General</b></h6>";
            $html .= "<p class='mb-1'>• Margen Comercial: <b>$" . number_format($this->totalMargin, 2) . "</b></p>";
            $html .= "<p class='mb-0'>• Margen Ponderado Promedio: <b>" . number_format($this->avgMarginPercent, 2) . "%</b></p>";
            $html .= "</div>";
            $html .= "</div>";

            $html .= "<div class='col-md-6 mb-3'>";
            $html .= "<div class='p-3 bg-light rounded border h-100'>";
            $html .= "<h6><i class='fas fa-box-open text-primary mr-2'></i> <b>Capital Total Invertido</b></h6>";
            $html .= "<p class='mb-1'>• Valor del Inventario: <b>$" . number_format($this->totalCapital, 2) . "</b></p>";
            $classACount = collect($this->abcMap)->filter(fn($c) => $c === 'A')->count();
            $html .= "<p class='mb-0'>• Productos Clase A: <b>$classACount productos</b> (80% de tus ventas).</p>";
            $html .= "</div>";
            $html .= "</div>";
            $html .= "</div>";

            $html .= "<div class='p-3 bg-light rounded border mb-3 mt-2'>";
            $html .= "<h6><i class='fas fa-exclamation-triangle text-danger mr-2'></i> <b>Capital Inmovilizado / Ocioso</b></h6>";
            $html .= "<p class='mb-1'>• Tienes un capital estancado de <b>$" . number_format($this->idleCapital, 2) . "</b> en productos sin ventas en este periodo.</p>";
            $html .= "<p class='mb-0'>• Esto representa el <b class='text-danger'>" . number_format($idleRatio, 1) . "%</b> de tu capital inmovilizado en almacén.</p>";
            $html .= "</div>";
            
            if ($top3->count() > 0) {
                $html .= "<h6 class='mt-4 font-weight-bold text-dark'><i class='fas fa-medal text-primary mr-2'></i> <b>Top 3 Productos que Generan Más Utilidad al Negocio:</b></h6>";
                $html .= "<div class='list-group mb-3'>";
                foreach ($top3 as $index => $p) {
                    $html .= "<div class='list-group-item list-group-item-action flex-column align-items-start'>";
                    $html .= "<div class='d-flex w-100 justify-content-between'>";
                    $html .= "<h6 class='mb-1 font-weight-bold text-info'>#" . ($index + 1) . " - {$p->name}</h6>";
                    $spanColor = $p->margin_percent > 30 ? 'text-success' : 'text-primary';
                    $html .= "<span class='font-weight-bold {$spanColor}'>$" . number_format($p->margin_usd, 2) . "</span>";
                    $html .= "</div>";
                    $html .= "<p class='mb-1 small text-muted'>Vendido: <b>{$p->total_sold} unidades</b> | Valor de Stock Actual: <b>$" . number_format($p->stock_value, 2) . "</b></p>";
                    $html .= "</div>";
                }
                $html .= "</div>";
            }

            // Recommendations
            $html .= "<div class='alert alert-warning mt-4 border-0 shadow-sm'>";
            $html .= "<h5><i class='fas fa-tools text-dark mr-2'></i> <b>Acciones de Optimización de Inventario</b></h5>";
            $html .= "<hr class='my-2 bg-dark'>";
            if ($idleRatio > 40) {
                $html .= "<p class='mb-0 small'><b>Alerta de Inventario Inactivo:</b> Más del 40% de tu capital está estancado en stock sin rotación. Te sugerimos realizar promociones especiales, combos de venta cruzada (atando productos sin rotación a productos de Clase A) o descuentos por liquidación para liberar flujo de caja de inmediato.</p>";
            } else {
                $html .= "<p class='mb-0 small'><b>Inventario Controlado:</b> Tu nivel de inventario ocioso está en rangos saludables. Te sugerimos seguir abasteciendo los productos Clase A y Clase B utilizando el botón automático de \"Generar Orden de Compra\" en base a la velocidad de venta.</p>";
            }
            $html .= "</div>";
            $html .= "</div>";
        }

        return $html;
    }
}
