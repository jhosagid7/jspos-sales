<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Purchase;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class PurchasesReport extends Component
{
    use  WithPagination;

    public $pagination = 10, $supplier_id, $dateFrom, $dateTo, $type = 0, $showReport = false;
    public $totales = 0, $purchase_id, $details = [];

    function mount()
    {
        session(['map' => "", 'child' => '', 'pos' => 'Reporte de Compras']);
    }

    public function render()
    {
        $this->supplier_id =  session('purchase_supplier', null);

        return view('livewire.reports.purchasesr', [
            'purchases' => $this->getReport()
        ]);
    }

    #[On('purchase_supplier')]
    function setSupplier($supplier)
    {
        session(['purchase_supplier' => $supplier]);
        $this->supplier_id = $supplier;
    }


    function getReport()
    {
        if (!$this->showReport) return [];

        if ($this->dateFrom == null && $this->dateTo == null) {
            $this->dispatch('noty', msg: 'SELECCIONA LAS FECHAS PARA CONSULTAR LAS COMPRAS');
            return [];
        }
        if ($this->dateFrom != null && $this->dateTo == null) {
            $this->dispatch('noty', msg: 'SELECCIONA LA FECHA DESDE Y HASTA');
            return [];
        }
        if ($this->dateFrom == null && $this->dateTo != null) {
            $this->dispatch('noty', msg: 'SELECCIONA LA FECHA DESDE Y HASTA');
            return [];
        }


        try {
            $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
            $dTo = Carbon::parse($this->dateTo)->endOfDay();

            $purchases = Purchase::with(['supplier', 'details', 'user'])->whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->supplier_id != null, function ($query) {
                    $query->where('supplier_id', $this->supplier_id['id']);
                })
                ->when($this->type != 0, function ($query) {
                    $query->where('purchases.type', $this->type);
                })
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);


            $this->totales = $purchases->sum(function ($purchase) {
                return $purchase->total;
            });

            return $purchases;
            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar obtener el reporte de compras \n {$th->getMessage()}");
            return [];
        }
    }

    function getPurchaseDetail(Purchase $purchase)
    {
        $this->purchase_id = $purchase->id;
        $this->details = $purchase->details;
        $this->dispatch('show-detail');
    }
}
