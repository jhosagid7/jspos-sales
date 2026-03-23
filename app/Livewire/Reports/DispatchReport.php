<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class DispatchReport extends Component
{
    use WithPagination;

    public $pagination = 10;
    public $dateFrom, $dateTo;
    public $driver_id = 'all';
    public $drivers = [];
    public $showReport = false;
    
    public $showPdfModal = false;
    public $pdfUrl = '';
    
    // Filtros adicionales
    public $seller_id = 'all';
    public $sellers = [];
    
    // Configuración de columnas (basado en el requerimiento del usuario)
    public $columns = [
        'invoice' => true,
        'destination' => true,
        'customer' => true,
        'base' => true,
        'percent' => true,
        'commission' => true,
        'freight' => true,
        'differential' => true,
        'total' => true,
        'date' => false
    ];

    public $signatures = [
        'chofer' => true,
        'entregado' => true,
        'recibido' => true,
        'vendedor' => false,
        'administrador' => false,
        'gerente' => false,
        'operador' => false
    ];

    public function mount()
    {
        // El reporte solo debe ser accesible si el módulo de despacho está activo
        if (!in_array('module_delivery', config('tenant.modules', []))) {
            return redirect()->to('/dashboard');
        }

        $this->dateFrom = Carbon::now()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        
        // Cargar choferes de forma segura (solo roles que existan para evitar errores de Spatie)
        $possibleRoles = ['driver', 'chofer', 'repartidor', 'Driver', 'Chofer'];
        $existingRoles = \Spatie\Permission\Models\Role::whereIn('name', $possibleRoles)->pluck('name')->toArray();
        
        if (!empty($existingRoles)) {
            $this->drivers = User::role($existingRoles)->orderBy('name')->get();
        } else {
            $this->drivers = User::all();
        }

        // Cargar vendedores únicos que tengan ventas registradas o simplemente todos con rol de vendedor
        $this->sellers = User::whereHas('customers')->orderBy('name')->get();
        if($this->sellers->isEmpty()){
             $this->sellers = User::all();
        }
        
        session(['pos' => 'Reporte de Despacho']);
    }

    public function render()
    {
        $sales = $this->getSalesData();

        return view('livewire.reports.dispatch-report', [
            'sales' => $sales
        ]);
    }

    public function getSalesData()
    {
        if (!$this->showReport) return [];

        $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dTo = Carbon::parse($this->dateTo)->endOfDay();

        return Sale::with(['customer.seller', 'driver', 'sellerConfig.user'])
            ->whereNotNull('driver_id')
            ->whereBetween('created_at', [$dFrom, $dTo])
            ->when($this->driver_id !== 'all', function($q) {
                $q->where('driver_id', $this->driver_id);
            })
            ->when($this->seller_id !== 'all', function($q) {
                $q->whereHas('customer', function($c) {
                    $c->where('seller_id', $this->seller_id);
                });
            })
            ->orderBy('driver_id')
            ->orderBy('created_at')
            ->paginate($this->pagination);
    }

    public function generateReport()
    {
        $this->showReport = true;
        $this->resetPage();
    }

    public function openPdfPreview()
    {
        $params = [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'driver_id' => $this->driver_id,
            'seller_id' => $this->seller_id,
            'columns' => json_encode($this->columns),
            'signatures' => json_encode($this->signatures)
        ];

        $this->pdfUrl = route('reports.dispatch.pdf', $params);
        $this->showPdfModal = true;
    }

    public function openSettlementPreview()
    {
        $params = [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'driver_id' => $this->driver_id,
            'seller_id' => $this->seller_id
        ];

        $this->pdfUrl = route('reports.settlement.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }
}
