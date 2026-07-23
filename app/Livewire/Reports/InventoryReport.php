<?php

namespace App\Livewire\Reports;

use App\Models\Category;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PriceCalculatorService;
use App\Services\CreditConfigService;
use App\Services\FooterCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryReport extends Component
{
    use WithPagination;

    public $pagination = 25;
    public $search = '';
    public $supplier_id = 'all';
    public $category_id = 'all';
    public $product_type = 'products'; // products, raw_materials, all
    
    // ConfiguraciÃ³n de columnas
    public $columns = [
        'sku' => true,
        'name' => true,
        'category' => true,
        'supplier' => true,
        'stock' => true,
        'physical_inventory' => false,
        'cost' => true,
        'price' => true,
        'utility_percent' => false,
        'valuation_cost' => false,
        'valuation_price' => false
    ];

    // ConfiguraciÃ³n de firmas
    public $signatures = [
        'elaborado' => true,
        'autorizado' => false,
        'gerente' => false,
        'auditoria' => true
    ];

    // Status report
    public $showReport = true;
    public $showPdfModal = false;
    public $pdfUrl = '';

    // Filtros de DepÃ³sito
    public $selected_warehouses = [];
    public $show_total_stock = true;

    // SelecciÃ³n de productos para imprimir
    public $selected_products = [];
    public $selectAll = false;
    public $show_only_selected = false;

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected_products = Product::where('status', 'available')
                ->when($this->product_type === 'products', fn($q) => $q->where('is_raw_material', false))
                ->when($this->product_type === 'raw_materials', fn($q) => $q->where('is_raw_material', true))
                ->when($this->supplier_id !== 'all', fn($q) => $q->where('supplier_id', $this->supplier_id))
                ->when($this->category_id !== 'all', fn($q) => $q->where('category_id', $this->category_id))
                ->when($this->search !== '', function ($query) {
                    $tokens = explode(' ', trim($this->search));
                    $query->where(function($q) use ($tokens) {
                        $q->where(function($andQuery) use ($tokens) {
                            foreach ($tokens as $token) {
                                if (!empty($token)) {
                                    $andQuery->where(function($subQuery) use ($token) {
                                        $subQuery->where('name', 'like', "%{$token}%")
                                                 ->orWhere('sku', 'like', "%{$token}%")
                                                 ->orWhereHas('category', function ($catQuery) use ($token) {
                                                     $catQuery->where('name', 'like', "%{$token}%");
                                                 });
                                    });
                                }
                            }
                        })
                        ->orWhereHas('tags', function ($tagQuery) use ($tokens) {
                            $tagQuery->where(function($sub) use ($tokens) {
                                foreach ($tokens as $token) {
                                    if (!empty($token)) {
                                        $sub->orWhere('name', 'like', "%{$token}%");
                                    }
                                }
                            });
                        });
                    });
                })->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selected_products = [];
        }
    }

    public function mount()
    {
        session(['map' => '', 'child' => '', 'rest' => '', 'pos' => 'Reporte de Inventario / Stock']);
    }

    public function updated($propertyName)
    {
        // Reset selection ONLY if specifically requested or if logic dictates, 
        // but NOT on search to allow cumulative selection as requested by user.
        if (in_array($propertyName, ['supplier_id', 'category_id', 'product_type'])) {
            $this->resetPage();
        }

        if ($propertyName == 'search') {
            $this->resetPage();
        }
    }

    public function render()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $warehouses = \App\Models\Warehouse::where('is_active', 1)->orderBy('name')->get();

        $products = $this->getProductsData();

        return view('livewire.reports.inventory-report', [
            'suppliers' => $suppliers,
            'categories' => $categories,
            'warehouses' => $warehouses,
            'products' => $products
        ]);
    }

    public function getProductsData()
    {
        return Product::where('status', 'available')
            ->when($this->product_type === 'products', function ($q) {
                $q->where('is_raw_material', false);
            })
            ->when($this->product_type === 'raw_materials', function ($q) {
                $q->where('is_raw_material', true);
            })
            ->when($this->show_only_selected, function ($q) {
                $q->whereIn('id', $this->selected_products);
            })
            ->when(!$this->show_only_selected && $this->supplier_id !== 'all', function ($q) {
                $q->where('supplier_id', $this->supplier_id);
            })
            ->when(!$this->show_only_selected && $this->category_id !== 'all', function ($q) {
                $q->where('category_id', $this->category_id);
            })
            ->when(!$this->show_only_selected && $this->search !== '', function ($query) {
                $tokens = explode(' ', trim($this->search));
                $query->where(function($q) use ($tokens) {
                    $q->where(function($andQuery) use ($tokens) {
                        foreach ($tokens as $token) {
                            if (!empty($token)) {
                                $andQuery->where(function($subQuery) use ($token) {
                                    $subQuery->where('name', 'like', "%{$token}%")
                                             ->orWhere('sku', 'like', "%{$token}%")
                                             ->orWhereHas('category', function ($catQuery) use ($token) {
                                                 $catQuery->where('name', 'like', "%{$token}%");
                                             });
                                });
                            }
                        }
                    })
                    ->orWhereHas('tags', function ($tagQuery) use ($tokens) {
                        $tagQuery->where(function($sub) use ($tokens) {
                            foreach ($tokens as $token) {
                                if (!empty($token)) {
                                    $sub->orWhere('name', 'like', "%{$token}%");
                                }
                            }
                        });
                    });
                });
            })
            ->with(['category', 'supplier', 'warehouses'])
            ->orderBy('name')
            ->paginate($this->pagination);
    }

    public function openPdfPreview()
    {
        $params = [
            'supplier_id' => $this->supplier_id,
            'category_id' => $this->category_id,
            'columns' => json_encode($this->columns),
            'signatures' => json_encode($this->signatures),
            'search' => $this->search,
            'warehouses' => json_encode($this->selected_warehouses),
            'show_total' => $this->show_total_stock,
            'selected_ids' => count($this->selected_products) > 0 ? implode(',', $this->selected_products) : null,
            'product_type' => $this->product_type
        ];

        $this->pdfUrl = route('reports.inventory.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }
}

