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
            // Fallback: si no hay roles específicos, buscar usuarios con permisos de delivery o simplemente vacíos
            $this->drivers = User::all(); // O podrías filtrar por un permiso específico si existe
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

        return Sale::with(['customer', 'driver', 'sellerConfig.user'])
            ->whereNotNull('driver_id')
            ->whereBetween('created_at', [$dFrom, $dTo])
            ->when($this->driver_id !== 'all', function($q) {
                $q->where('driver_id', $this->driver_id);
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
            'driver_id' => $this->driver_id
        ];

        // Definiremos esta ruta en el archivo web.php luego
        $this->pdfUrl = route('reports.dispatch.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }
}
