<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use App\Models\Product;
use Livewire\Component;
use App\Models\SaleDetail;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class SalesReport extends Component
{
    use  WithPagination;

    public $pagination = 10, $users = [], $user_id, $dateFrom, $dateTo, $showReport = false, $type = 0;
    public $totales = 0, $sale_id, $sale_status, $details = [];
    public $salesObt;
    public $sale_note;

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

            $this->totales = $sales->where('status', '<>', 'returned')->sum(function ($sale) {
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
        // dd($sale->status);
        $this->salesObt = $sale;
        $this->sale_id = $sale->id;
        $this->sale_status = $sale->status;
        $this->details = $sale->details;
        $this->dispatch('show-detail');
    }

    function getSaleDetailNote(Sale $sale)
    {
        $this->salesObt = $sale;
        $this->sale_id = $sale->id;
        $this->details = $sale->details;
        $this->sale_note = $sale->notes; // Populate the sale_note property
        $this->dispatch('show-detail-note');
    }


    public function saveSaleNote()
    {
        $this->validate([
            'sale_note' => 'nullable|string',
        ]);

        $this->salesObt->update([
            'notes' => $this->sale_note,
        ]);

        $this->dispatch('noty', msg: 'Nota de venta actualizada correctamente');
        $this->dispatch('close-detail-note'); // Close the modal
        return;
    }

    #[On('DestroySale')]
    public function DestroySale($saleId)
    {
        // dd($saleId);
        try {
            DB::beginTransaction();

            $sale = Sale::findOrFail($saleId);
            $sale->update([
                'status' => 'returned', // o 'deleted'
                'deleted_at' => Carbon::now(),
            ]);

            $saleDetails = SaleDetail::where('sale_id', $saleId)->get();

            foreach ($saleDetails as $detail) {
                Product::find($detail->product_id)->increment('stock_qty', $detail->quantity);
            }

            DB::commit();

            $this->dispatch('noty', msg: 'Venta eliminada correctamente');
            return;
        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al intentar eliminar la venta \n {$th->getMessage()}");
            return;
        }
    }
}
