<?php

namespace App\Livewire\Reports;

use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\TransferStockLayer;
use App\Models\SaleLayerConsumption;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class PartnerSalesLiquidationReport extends Component
{
    use WithPagination;

    public $dateFrom;
    public $dateTo;
    public $origin_warehouse_id = 'all';
    public $destination_warehouse_id = 'all';
    public $searchProduct = '';
    public $viewMode = 'summary'; // 'summary', 'detailed', 'origin_stock', 'consignment_stock'
    public $pagination = 15;

    public $showPdfModal = false;
    public $pdfUrl = '';

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        session(['pos' => 'Liquidación y Ventas por Socio']);
    }

    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }
    public function updatingOriginWarehouseId() { $this->resetPage(); }
    public function updatingDestinationWarehouseId() { $this->resetPage(); }
    public function updatingSearchProduct() { $this->resetPage(); }
    public function updatingViewMode() { $this->resetPage(); }

    public function render()
    {
        $partnerWarehouses = Warehouse::where('is_partner_warehouse', true)->orderBy('name')->get();
        $destinationWarehouses = Warehouse::orderBy('name')->get();

        $kpis = $this->calculateKpis();

        if ($this->viewMode === 'summary') {
            $data = $this->getSummaryData();
        } elseif ($this->viewMode === 'detailed') {
            $data = $this->getDetailedData();
        } elseif ($this->viewMode === 'origin_stock') {
            $data = $this->getOriginStockData();
        } elseif ($this->viewMode === 'consignment_stock') {
            $data = $this->getConsignmentStockData();
        } else {
            $data = $this->getSummaryData();
        }

        return view('livewire.reports.partner-sales-liquidation-report', [
            'warehouses' => $destinationWarehouses,
            'partnerWarehouses' => $partnerWarehouses,
            'destinationWarehouses' => $destinationWarehouses,
            'kpis' => $kpis,
            'items' => $data,
            'viewMode' => $this->viewMode,
            'showPdfModal' => $this->showPdfModal,
            'pdfUrl' => $this->pdfUrl,
        ]);
    }

    /**
     * Compute aggregated KPIs without N+1 query problem using single aggregated SQL.
     */
    protected function calculateKpis(): array
    {
        $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dTo = Carbon::parse($this->dateTo)->endOfDay();

        $query = SaleLayerConsumption::join('sales', 'sale_layer_consumptions.sale_id', '=', 'sales.id')
            ->join('products', 'sale_layer_consumptions.product_id', '=', 'products.id')
            ->join('warehouses as origin_wh', 'sale_layer_consumptions.origin_warehouse_id', '=', 'origin_wh.id')
            ->whereNull('sales.deleted_at')
            ->whereBetween('sales.created_at', [$dFrom, $dTo]);

        if ($this->origin_warehouse_id !== 'all') {
            $query->where('sale_layer_consumptions.origin_warehouse_id', $this->origin_warehouse_id);
        } else {
            $query->where('origin_wh.is_partner_warehouse', true);
        }

        if ($this->destination_warehouse_id !== 'all') {
            $query->where('sales.warehouse_id', $this->destination_warehouse_id);
        }

        if (!empty($this->searchProduct)) {
            $term = '%' . $this->searchProduct . '%';
            $query->where(function($q) use ($term) {
                $q->where('products.name', 'like', $term)
                  ->orWhere('products.sku', 'like', $term);
            });
        }

        $metrics = (clone $query)->select([
            DB::raw('COALESCE(SUM(sale_layer_consumptions.quantity), 0) as total_qty_sold'),
            DB::raw('COALESCE(SUM(sale_layer_consumptions.total_price), 0) as total_amount_sold'),
            DB::raw('COALESCE(SUM(COALESCE(sale_layer_consumptions.total_cost, sale_layer_consumptions.quantity * products.cost, 0)), 0) as total_cost_sold'),
            DB::raw('COUNT(DISTINCT sale_layer_consumptions.sale_id) as total_sales_count'),
            DB::raw('COUNT(DISTINCT sale_layer_consumptions.product_id) as total_distinct_products'),
        ])->first();

        // Remaining partner stock in destination warehouses (layers remaining_quantity)
        $layersQuery = TransferStockLayer::join('warehouses as origin_wh', 'transfer_stock_layers.origin_warehouse_id', '=', 'origin_wh.id')
            ->where('transfer_stock_layers.remaining_quantity', '>', 0);

        if ($this->origin_warehouse_id !== 'all') {
            $layersQuery->where('transfer_stock_layers.origin_warehouse_id', $this->origin_warehouse_id);
        } else {
            $layersQuery->where('origin_wh.is_partner_warehouse', true);
        }

        if ($this->destination_warehouse_id !== 'all') {
            $layersQuery->where('transfer_stock_layers.destination_warehouse_id', $this->destination_warehouse_id);
        }
        $remainingInStore = (float) $layersQuery->sum('transfer_stock_layers.remaining_quantity');

        // Physical stock currently sitting in partner warehouses (not yet transferred)
        $originStockQuery = ProductWarehouse::join('warehouses as wh', 'product_warehouse.warehouse_id', '=', 'wh.id')
            ->where('product_warehouse.stock_qty', '>', 0);

        if ($this->origin_warehouse_id !== 'all') {
            $originStockQuery->where('product_warehouse.warehouse_id', $this->origin_warehouse_id);
        } else {
            $originStockQuery->where('wh.is_partner_warehouse', true);
        }

        if (!empty($this->searchProduct)) {
            $term = '%' . $this->searchProduct . '%';
            $originStockQuery->join('products', 'product_warehouse.product_id', '=', 'products.id')
                ->where(function($q) use ($term) {
                    $q->where('products.name', 'like', $term)
                      ->orWhere('products.sku', 'like', $term);
                });
        }
        $originPhysicalStock = (float) $originStockQuery->sum('product_warehouse.stock_qty');

        $totalQtySold = (float) ($metrics->total_qty_sold ?? 0);
        $totalAmountSold = (float) ($metrics->total_amount_sold ?? 0);
        $totalCostSold = (float) ($metrics->total_cost_sold ?? 0);
        $totalProfit = round($totalAmountSold - $totalCostSold, 2);
        $marginPercentage = $totalAmountSold > 0 ? round(($totalProfit / $totalAmountSold) * 100, 2) : 0;

        return [
            'total_qty_sold' => $totalQtySold,
            'total_amount_sold' => $totalAmountSold,
            'total_cost_sold' => $totalCostSold,
            'total_profit' => $totalProfit,
            'margin_percentage' => $marginPercentage,
            'total_sales_count' => (int) ($metrics->total_sales_count ?? 0),
            'total_distinct_products' => (int) ($metrics->total_distinct_products ?? 0),
            'remaining_in_store' => $remainingInStore,
            'origin_physical_stock' => $originPhysicalStock,
        ];
    }

    /**
     * Grouped summary by Partner Warehouse and Product with eager loading and zero N+1.
     */
    protected function getSummaryData()
    {
        $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dTo = Carbon::parse($this->dateTo)->endOfDay();

        $query = SaleLayerConsumption::join('sales', 'sale_layer_consumptions.sale_id', '=', 'sales.id')
            ->join('products', 'sale_layer_consumptions.product_id', '=', 'products.id')
            ->join('warehouses as origin_wh', 'sale_layer_consumptions.origin_warehouse_id', '=', 'origin_wh.id')
            ->whereNull('sales.deleted_at')
            ->whereBetween('sales.created_at', [$dFrom, $dTo]);

        if ($this->origin_warehouse_id !== 'all') {
            $query->where('sale_layer_consumptions.origin_warehouse_id', $this->origin_warehouse_id);
        } else {
            $query->where('origin_wh.is_partner_warehouse', true);
        }

        if ($this->destination_warehouse_id !== 'all') {
            $query->where('sales.warehouse_id', $this->destination_warehouse_id);
        }

        if (!empty($this->searchProduct)) {
            $term = '%' . $this->searchProduct . '%';
            $query->where(function($q) use ($term) {
                $q->where('products.name', 'like', $term)
                  ->orWhere('products.sku', 'like', $term);
            });
        }

        return $query->select([
            'sale_layer_consumptions.origin_warehouse_id',
            'origin_wh.name as origin_warehouse_name',
            'sale_layer_consumptions.product_id',
            'products.name as product_name',
            'products.sku as product_barcode',
            DB::raw('SUM(sale_layer_consumptions.quantity) as total_quantity'),
            DB::raw('SUM(sale_layer_consumptions.total_price) as total_sales_amount'),
            DB::raw('AVG(sale_layer_consumptions.unit_price) as avg_unit_price'),
            DB::raw('AVG(COALESCE(sale_layer_consumptions.unit_cost, products.cost, 0)) as avg_unit_cost'),
            DB::raw('SUM(COALESCE(sale_layer_consumptions.total_cost, sale_layer_consumptions.quantity * products.cost, 0)) as total_cost_amount'),
            DB::raw('(SUM(sale_layer_consumptions.total_price) - SUM(COALESCE(sale_layer_consumptions.total_cost, sale_layer_consumptions.quantity * products.cost, 0))) as total_profit'),
            DB::raw('CASE WHEN SUM(sale_layer_consumptions.total_price) > 0 THEN ((SUM(sale_layer_consumptions.total_price) - SUM(COALESCE(sale_layer_consumptions.total_cost, sale_layer_consumptions.quantity * products.cost, 0))) / SUM(sale_layer_consumptions.total_price)) * 100 ELSE 0 END as margin_percentage'),
        ])
        ->groupBy(
            'sale_layer_consumptions.origin_warehouse_id',
            'origin_wh.name',
            'sale_layer_consumptions.product_id',
            'products.name',
            'products.sku'
        )
        ->orderBy('origin_wh.name', 'asc')
        ->orderBy('total_sales_amount', 'desc')
        ->paginate($this->pagination);
    }

    /**
     * Detailed row-by-row invoice items with eager loading and zero N+1.
     */
    protected function getDetailedData()
    {
        $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dTo = Carbon::parse($this->dateTo)->endOfDay();

        $query = SaleLayerConsumption::with([
            'originWarehouse',
            'product',
            'sale.customer',
            'sale.user',
            'transferStockLayer.destinationWarehouse'
        ])
        ->join('sales', 'sale_layer_consumptions.sale_id', '=', 'sales.id')
        ->join('products', 'sale_layer_consumptions.product_id', '=', 'products.id')
        ->join('warehouses as origin_wh', 'sale_layer_consumptions.origin_warehouse_id', '=', 'origin_wh.id')
        ->whereNull('sales.deleted_at')
        ->whereBetween('sales.created_at', [$dFrom, $dTo]);

        if ($this->origin_warehouse_id !== 'all') {
            $query->where('sale_layer_consumptions.origin_warehouse_id', $this->origin_warehouse_id);
        } else {
            $query->where('origin_wh.is_partner_warehouse', true);
        }

        if ($this->destination_warehouse_id !== 'all') {
            $query->where('sales.warehouse_id', $this->destination_warehouse_id);
        }

        if (!empty($this->searchProduct)) {
            $term = '%' . $this->searchProduct . '%';
            $query->where(function($q) use ($term) {
                $q->where('products.name', 'like', $term)
                  ->orWhere('products.sku', 'like', $term);
            });
        }

        return $query->select('sale_layer_consumptions.*')
            ->orderBy('sale_layer_consumptions.id', 'desc')
            ->paginate($this->pagination);
    }

    /**
     * Physical stock directly stored in partner warehouses (before or outside of transfers).
     */
    protected function getOriginStockData()
    {
        $query = ProductWarehouse::with(['warehouse', 'product.category'])
            ->join('warehouses as wh', 'product_warehouse.warehouse_id', '=', 'wh.id')
            ->join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->where('product_warehouse.stock_qty', '>', 0);

        if ($this->origin_warehouse_id !== 'all') {
            $query->where('product_warehouse.warehouse_id', $this->origin_warehouse_id);
        } else {
            $query->where('wh.is_partner_warehouse', true);
        }

        if (!empty($this->searchProduct)) {
            $term = '%' . $this->searchProduct . '%';
            $query->where(function($q) use ($term) {
                $q->where('products.name', 'like', $term)
                  ->orWhere('products.sku', 'like', $term);
            });
        }

        return $query->select('product_warehouse.*')
            ->orderBy('wh.name', 'asc')
            ->orderBy('products.name', 'asc')
            ->paginate($this->pagination);
    }

    /**
     * Consignment layers in store waiting for POS sale with remaining quantity > 0.
     */
    protected function getConsignmentStockData()
    {
        $query = TransferStockLayer::with([
            'originWarehouse',
            'destinationWarehouse',
            'product',
            'transfer'
        ])
        ->join('warehouses as origin_wh', 'transfer_stock_layers.origin_warehouse_id', '=', 'origin_wh.id')
        ->join('products', 'transfer_stock_layers.product_id', '=', 'products.id')
        ->where('transfer_stock_layers.remaining_quantity', '>', 0);

        if ($this->origin_warehouse_id !== 'all') {
            $query->where('transfer_stock_layers.origin_warehouse_id', $this->origin_warehouse_id);
        } else {
            $query->where('origin_wh.is_partner_warehouse', true);
        }

        if ($this->destination_warehouse_id !== 'all') {
            $query->where('transfer_stock_layers.destination_warehouse_id', $this->destination_warehouse_id);
        }

        if (!empty($this->searchProduct)) {
            $term = '%' . $this->searchProduct . '%';
            $query->where(function($q) use ($term) {
                $q->where('products.name', 'like', $term)
                  ->orWhere('products.sku', 'like', $term);
            });
        }

        return $query->select('transfer_stock_layers.*')
            ->orderBy('transfer_stock_layers.id', 'desc')
            ->paginate($this->pagination);
    }

    public function openPdf()
    {
        $params = http_build_query([
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'origin_warehouse_id' => $this->origin_warehouse_id,
            'destination_warehouse_id' => $this->destination_warehouse_id,
            'searchProduct' => $this->searchProduct,
            'viewMode' => $this->viewMode,
        ]);

        $this->pdfUrl = route('reports.partner.sales.pdf') . '?' . $params;
        $this->showPdfModal = true;
    }
}