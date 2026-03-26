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
    
    // Configuración de columnas
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

    // Configuración de firmas
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

    public function mount()
    {
        session(['pos' => 'Reporte de Inventario / Stock']);
    }

    public function render()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        $products = $this->getProductsData();

        return view('livewire.reports.inventory-report', [
            'suppliers' => $suppliers,
            'categories' => $categories,
            'products' => $products
        ]);
    }

    public function getProductsData()
    {
        return Product::where('status', 'available')
            ->when($this->supplier_id !== 'all', function ($q) {
                $q->where('supplier_id', $this->supplier_id);
            })
            ->when($this->category_id !== 'all', function ($q) {
                $q->where('category_id', $this->category_id);
            })
            ->when($this->search !== '', function ($q) {
                $q->where(function($qq){
                    $qq->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%');
                });
            })
            ->with(['category', 'supplier'])
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
            'search' => $this->search
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
