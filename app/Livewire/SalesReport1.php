<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class SalesReport extends Component
{
    use  WithPagination;

    public $pagination = 10, $users = [], $user_id, $dateFrom, $dateTo, $showReport = false, $type = 0;
    public $totales = 0, $sale_id, $details = [];
    public $salesObt;

    function mount()
    {
        session(['map' => "", 'child' => '', 'pos' => 'Reporte de Ventas']);

        $this->users = User::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.reports.salesr', [
            'sales' => $this->getReport()
        ]);
    }

    function getReport()
    {
        if (!$this->showReport) return [];

        if ($this->user_id == null && $this->dateFrom == null && $this->dateTo == null) {
            $this->dispatch('noty', msg: 'SELECCIONA EL USUARIO Y/O LAS FECHAS PARA CONSULTAR LAS VENTAS');
            return;
        }
        if ($this->dateFrom != null && $this->dateTo == null) {
            $this->dispatch('noty', msg: 'SELECCIONA LA FECHA DESDE Y HASTA');
            return;
        }
        if ($this->dateFrom == null && $this->dateTo != null) {
            $this->dispatch('noty', msg: 'SELECCIONA LA FECHA DESDE Y HASTA');
            return;
        }

        //$this->resetPage();

        try {
            $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
            $dTo = Carbon::parse($this->dateTo)->endOfDay();

            $sales = Sale::with(['customer', 'details', 'user'])
                ->whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                })
                ->when($this->type != 0, function ($qry) {
                    $qry->where('type', $this->type);
                })
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);

            //$this->showReport = false;

            $this->totales = $sales->sum(function ($sale) {
                return $sale->total;
            });

            $this->dispatch('noty', msg: 'INFO ACTUALIZADA');
            return $sales;
            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar obtener el reporte de ventas \n {$th->getMessage()}");
            return [];
        }
    }

    function getSaleDetail(Sale $sale)
    {
        $this->salesObt = $sale;
        $this->sale_id = $sale->id;
        $this->details = $sale->details;
        $this->dispatch('show-detail');
    }
}
