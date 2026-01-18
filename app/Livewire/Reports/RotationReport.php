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
    // public $selectAll = false;

    public function mount()
    {
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        
        $config = \App\Models\Configuration::first();
        $this->coverageDays = $config->purchasing_coverage_days ?? 30;
        
        $this->selectedProducts = [];
    }

    // SelectAll logic removed as per user request

    // Reset pagination when search changes
    public function updatedSearch()
    {
        $this->resetPage();
    }

    private function cleanString($string)
    {
        if (is_null($string)) return '';

        // 1. Ensure valid UTF-8
        // mb_convert_encoding with UTF-8 to UTF-8 will replace invalid sequences with ?
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        
        // 2. Remove control characters (0-31 except 9=Tab, 10=LF, 13=CR) and 127=DEL
        // We do NOT use /u modifier here to avoid PCRE UTF-8 validity checks crashing
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
        
        return trim($string);
    }

    public function render()
    {
        $data = $this->getRotationData();

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

        // Build conditional SQL for customer filter
        $customerCondition = "";
        $bindings = [$startDate, $endDate];
        
        if ($this->customerId > 0) {
            $customerCondition = " AND sales.customer_id = ?";
            $bindings[] = $this->customerId;
        }

        // Duplicate bindings for the second COUNT(DISTINCT) clause
        $allBindings = array_merge($bindings, $bindings);

        $query = Product::query()
            ->leftJoin('sale_details', 'products.id', '=', 'sale_details.product_id')
            ->leftJoin('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->select(
                'products.id',
                'products.name',
                'products.stock_qty',
                'products.cost',
                'products.price',
                DB::raw("COALESCE(SUM(CASE WHEN sales.status = 'PAID' AND sales.created_at BETWEEN ? AND ? $customerCondition THEN sale_details.quantity ELSE 0 END), 0) as total_sold"),
                DB::raw("COUNT(DISTINCT CASE WHEN sales.status = 'PAID' AND sales.created_at BETWEEN ? AND ? $customerCondition THEN DATE(sales.created_at) END) as days_with_sales")
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
        
        // Status Filter (HAVING)
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

    public function getRotationData()
    {
        $products = $this->getQuery()->paginate($this->pagination);
        return $this->processMetrics($products);
    }

    public function processMetrics($products)
    {
        $startDate = Carbon::parse($this->dateFrom)->startOfDay();
        $endDate = Carbon::parse($this->dateTo)->endOfDay();
        $daysDiff = $startDate->diffInDays($endDate) ?: 1;

        // Handle both Paginator and Collection
        $collection = $products instanceof \Illuminate\Pagination\LengthAwarePaginator ? $products->getCollection() : $products;

        $collection->transform(function ($product) use ($daysDiff) {
            // Robust Sanitize UTF-8 for name
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

            return $product;
        });

        return $products;
    }

    public function generatePdf()
    {
        try {
            // DEBUG: Step 4 - Restore Real Data
            if (count($this->selectedProducts) > 0) {
                $query = $this->getQuery();
                $query->whereIn('products.id', $this->selectedProducts);
                $data = $query->get();
            } else {
                $data = $this->getQuery()->get();
            }

            $data = $this->processMetrics($data);
            
            $config = \App\Models\Configuration::first();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('livewire.reports.rotation-report-pdf', [
                'data' => $data,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'coverageDays' => $this->coverageDays,
                'config' => $config
            ]);

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'Reporte_Rotacion.pdf');

        } catch (\Exception $e) {

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
