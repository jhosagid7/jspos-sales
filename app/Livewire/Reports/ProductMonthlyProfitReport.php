<?php

namespace App\Livewire\Reports;

use App\Models\Product;
use App\Models\Warehouse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProductMonthlyProfitReport extends Component
{
    public $product_id;
    public $search = '';
    public $selected_product_name = '';
    public $selected_product_sku = '';
    public $products_results = [];
    public $dateFrom;
    public $dateTo;
    public $selected_warehouse_id = 'all';

    public $monthlyData = [];
    public $totals = [
        'total_qty' => 0,
        'total_sold' => 0,
        'total_cost' => 0,
        'total_profit' => 0,
        'margin_percent' => 0,
    ];

    public $showPdfModal = false;
    public $pdfUrl = '';

    public $hideZeroMonths = false;

    public function mount()
    {
        session([
            'map' => '',
            'child' => '',
            'rest' => '',
            'pos' => 'Rentabilidad y Precios Mensuales por Producto'
        ]);

        // Por defecto: desde inicio del año en curso hasta hoy (totalmente editable a cualquier rango)
        $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function setPreset($preset)
    {
        $now = Carbon::now();
        switch ($preset) {
            case 'this_year':
                $this->dateFrom = $now->copy()->startOfYear()->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;
            case 'since_may':
                $this->dateFrom = Carbon::create($now->year, 5, 1)->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;
            case 'last_3_months':
                $this->dateFrom = $now->copy()->subMonths(2)->startOfMonth()->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;
            case 'last_6_months':
                $this->dateFrom = $now->copy()->subMonths(5)->startOfMonth()->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;
        }
        $this->calculateReport();
    }

    public function render()
    {
        $warehouses = Warehouse::where('is_active', 1)->orderBy('name')->get();

        return view('livewire.reports.product-monthly-profit-report', [
            'warehouses' => $warehouses,
        ]);
    }

    public function updatedSearch()
    {
        $this->searchProducts();
    }

    public function updatedDateFrom()
    {
        $this->calculateReport();
    }

    public function updatedDateTo()
    {
        $this->calculateReport();
    }

    public function updatedHideZeroMonths()
    {
        $this->calculateReport();
    }

    public function updatedSelectedWarehouseId()
    {
        $this->calculateReport();
    }

    public function searchProducts()
    {
        $search = trim($this->search);

        if (strlen($search) > 0) {
            $query = Product::query();
            $tokens = explode(' ', $search);

            foreach ($tokens as $token) {
                if (!empty($token)) {
                    $query->where(function ($q) use ($token) {
                        $q->where('name', 'like', "%{$token}%")
                          ->orWhere('sku', 'like', "%{$token}%");
                    });
                }
            }

            $this->products_results = $query->take(10)->get();
        } else {
            $this->products_results = [];
        }
    }

    public function selectProduct($id)
    {
        $product = Product::find($id);
        if ($product) {
            $this->product_id = $id;
            $this->selected_product_name = $product->name;
            $this->selected_product_sku = $product->sku;
            $this->search = $product->sku ? ($product->sku . ' - ' . $product->name) : $product->name;
            $this->products_results = [];
            $this->calculateReport();
        }
    }

    public function calculateReport()
    {
        if (!$this->product_id) {
            $this->monthlyData = [];
            $this->totals = [
                'total_qty' => 0,
                'total_sold' => 0,
                'total_cost' => 0,
                'total_profit' => 0,
                'margin_percent' => 0,
            ];
            return;
        }

        $product = Product::find($this->product_id);
        if (!$product) {
            return;
        }

        $start = Carbon::parse($this->dateFrom)->startOfMonth();
        $end = Carbon::parse($this->dateTo)->endOfMonth();

        // Generar lista de meses
        $period = CarbonPeriod::create($start, '1 month', $end);

        $data = [];
        $totalQty = 0;
        $totalSold = 0;
        $totalCost = 0;

        foreach ($period as $dt) {
            $mStart = $dt->copy()->startOfMonth()->startOfDay();
            $mEnd = $dt->copy()->endOfMonth()->endOfDay();
            $monthKey = $dt->format('Y-m');
            $monthName = ucfirst($dt->locale('es')->monthName) . ' ' . $dt->year;

            // 1. Ventas del mes
            $salesQuery = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->where('sale_details.product_id', $this->product_id)
                ->where('sales.status', '<>', 'returned')
                ->whereNull('sales.deletion_approved_at')
                ->whereBetween('sales.created_at', [$mStart, $mEnd]);

            if ($this->selected_warehouse_id !== 'all') {
                $salesQuery->where('sale_details.warehouse_id', $this->selected_warehouse_id);
            }

            // Desglose de precios de venta en el mes
            $pricesRows = (clone $salesQuery)
                ->select([
                    'sale_details.sale_price',
                    DB::raw('SUM(sale_details.quantity) as qty'),
                    DB::raw('SUM(sale_details.quantity * sale_details.sale_price) as subtotal')
                ])
                ->groupBy('sale_details.sale_price')
                ->orderBy('sale_details.sale_price')
                ->get();

            $soldQty = floatval($pricesRows->sum('qty'));
            $soldAmount = floatval($pricesRows->sum('subtotal'));
            $hasPriceChanges = count($pricesRows) > 1;

            $priceBreakdown = [];
            foreach ($pricesRows as $pr) {
                $priceBreakdown[] = [
                    'price' => floatval($pr->sale_price),
                    'qty' => floatval($pr->qty),
                    'subtotal' => floatval($pr->subtotal),
                ];
            }

            // 2. Costos del mes (Compras, Cargos o Costo base)
            $purchasesInMonth = DB::table('purchase_details')
                ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                ->where('purchase_details.product_id', $this->product_id)
                ->whereBetween('purchases.created_at', [$mStart, $mEnd])
                ->select([
                    'purchase_details.cost',
                    DB::raw('SUM(purchase_details.quantity) as qty')
                ])
                ->groupBy('purchase_details.cost')
                ->get();

            $costBreakdown = [];
            $sumCostQty = 0;
            $sumCostAmount = 0;

            foreach ($purchasesInMonth as $pur) {
                $c = floatval($pur->cost);
                $q = floatval($pur->qty);
                $costBreakdown[] = [
                    'cost' => $c,
                    'qty' => $q,
                    'source' => 'Compra',
                ];
                $sumCostQty += $q;
                $sumCostAmount += ($c * $q);
            }

            // Cargos de inventario (producción / ajustes)
            $cargosInMonth = DB::table('cargo_details')
                ->join('cargos', 'cargo_details.cargo_id', '=', 'cargos.id')
                ->where('cargo_details.product_id', $this->product_id)
                ->where('cargos.status', 'approved')
                ->whereBetween('cargos.date', [$mStart, $mEnd])
                ->select([
                    'cargo_details.cost',
                    DB::raw('SUM(cargo_details.quantity) as qty')
                ])
                ->groupBy('cargo_details.cost')
                ->get();

            foreach ($cargosInMonth as $crg) {
                $c = floatval($crg->cost);
                $q = floatval($crg->qty);
                $costBreakdown[] = [
                    'cost' => $c,
                    'qty' => $q,
                    'source' => 'Cargo/Producción',
                ];
                $sumCostQty += $q;
                $sumCostAmount += ($c * $q);
            }

            // Determinar costo unitario efectivo del mes:
            if ($sumCostQty > 0) {
                // Si hubo compras o entradas este mes, usar el promedio ponderado del mes
                $effectiveCost = $sumCostAmount / $sumCostQty;
            } else {
                // Si no hubo compras en este mes, buscar la última compra previa
                $lastPurchase = DB::table('purchase_details')
                    ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                    ->where('purchase_details.product_id', $this->product_id)
                    ->where('purchases.created_at', '<=', $mEnd)
                    ->orderBy('purchases.created_at', 'desc')
                    ->value('purchase_details.cost');

                if ($lastPurchase !== null) {
                    $effectiveCost = floatval($lastPurchase);
                } else {
                    $effectiveCost = floatval($product->cost ?? 0);
                }
            }

            $hasCostChanges = count($costBreakdown) > 1;

            $costAmount = $soldQty * $effectiveCost;
            $profitAmount = $soldAmount - $costAmount;
            $marginPercent = $soldAmount > 0 ? ($profitAmount / $soldAmount) * 100 : 0.0;

            if ($this->hideZeroMonths && $soldQty <= 0) {
                continue;
            }

            $data[] = [
                'period' => $monthName,
                'raw_period' => $monthKey,
                'sold_qty' => $soldQty,
                'sold_amount' => $soldAmount,
                'cost_unit' => $effectiveCost,
                'cost_amount' => $costAmount,
                'profit_amount' => $profitAmount,
                'margin_percent' => $marginPercent,
                'has_price_changes' => $hasPriceChanges,
                'price_breakdown' => $priceBreakdown,
                'has_cost_changes' => $hasCostChanges,
                'cost_breakdown' => $costBreakdown,
            ];

            $totalQty += $soldQty;
            $totalSold += $soldAmount;
            $totalCost += $costAmount;
        }

        $this->monthlyData = $data;

        $totalProfit = $totalSold - $totalCost;
        $totalMargin = $totalSold > 0 ? ($totalProfit / $totalSold) * 100 : 0.0;

        $this->totals = [
            'total_qty' => $totalQty,
            'total_sold' => $totalSold,
            'total_cost' => $totalCost,
            'total_profit' => $totalProfit,
            'margin_percent' => $totalMargin,
        ];

        session([
            'map' => 'TOTAL COSTO: $' . number_format($totalCost, 2),
            'child' => 'TOTAL VENTA: $' . number_format($totalSold, 2),
            'rest' => 'GANANCIA: $' . number_format($totalProfit, 2) . ' / MARGEN: ' . number_format($totalMargin, 2) . '%',
            'pos' => 'Rentabilidad y Precios Mensuales por Producto'
        ]);
    }

    public function openPdfPreview()
    {
        if (!$this->product_id) {
            $this->dispatch('noty', msg: 'Debe seleccionar un producto primero');
            return;
        }

        $this->pdfUrl = route('reports.product.monthly.profit.pdf', [
            'product_id' => $this->product_id,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'warehouse_id' => $this->selected_warehouse_id
        ]);

        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }
}
