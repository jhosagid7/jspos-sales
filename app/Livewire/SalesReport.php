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
    public $currencies = [];
    public $sellers = [], $seller_id;
    public $customer; // New property for customer filter

    function mount()
    {
        session()->forget('sale_customer'); // Clear session
        session(['map' => "TOTAL COSTO $0.00", 'child' => 'TOTAL VENTA $0.00', 'rest' => 'GANANCIA: $0.00 / MARGEN: 0.00%', 'pos' => 'Reporte de Ventas']);

        $this->users = User::orderBy('name')->get();
        $this->sellers = User::role('Vendedor')->orderBy('name')->get();
        $this->currencies = \App\Models\Currency::orderBy('id')->get();
    }

    public function render()
    {
        $this->customer = session('sale_customer', null); // Get customer from session

        return view('livewire.reports.salesr', [
            'sales' => $this->getReport()
        ]);
    }

    #[On('sale_customer')] // Listener for customer selection
    function setCustomer($customer)
    {
        session(['sale_customer' => $customer]);
        $this->customer = $customer;
    }

    function getReport()
    {
        if (!$this->showReport) return [];

        // Allow empty filters (return all)
        // if ($this->user_id == null && $this->dateFrom == null && $this->dateTo == null && $this->seller_id == null && $this->customer == null) {
        //     $this->dispatch('noty', msg: 'SELECCIONA LOS FILTROS PARA CONSULTAR LAS VENTAS');
        //     return;
        // }
        
        // ... (date validation logic remains similar, maybe adjust if needed)

        try {
            $dFrom = null;
            $dTo = null;
            
            if($this->dateFrom && $this->dateTo) {
                $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = Carbon::parse($this->dateTo)->endOfDay();
            }

            $sales = Sale::with(['customer', 'details', 'user', 'paymentDetails', 'changeDetails'])
                ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                    $q->whereBetween('created_at', [$dFrom, $dTo]);
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                })
                ->when($this->customer != null, function ($query) {
                     $query->where('customer_id', $this->customer['id']);
                })
                ->when($this->type != 0, function ($qry) {
                    $qry->where('type', $this->type);
                })
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);

            // Calcular totales globales (sin paginación)
            $salesQuery = Sale::when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                    $q->whereBetween('created_at', [$dFrom, $dTo]);
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                })
                ->when($this->customer != null, function ($query) {
                     $query->where('customer_id', $this->customer['id']);
                })
                ->when($this->type != 0, function ($qry) {
                    $qry->where('type', $this->type);
                })
                ->where('status', '<>', 'returned');

            $totalSale = $salesQuery->sum('total');

            // Calcular costo total
            $totalCostQuery = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->join('products', 'sale_details.product_id', '=', 'products.id')
                ->join('customers', 'sales.customer_id', '=', 'customers.id') 
                ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                    $q->whereBetween('sales.created_at', [$dFrom, $dTo]);
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('sales.user_id', $this->user_id);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->where('customers.seller_id', $this->seller_id);
                })
                ->when($this->customer != null, function ($query) {
                     $query->where('sales.customer_id', $this->customer['id']);
                })
                ->when($this->type != 0, function ($qry) {
                    $qry->where('sales.type', $this->type);
                })
                ->where('sales.status', '<>', 'returned');
                
            $totalCost = $totalCostQuery->sum(DB::raw('sale_details.quantity * products.cost'));

            $profit = $totalSale - $totalCost;
            $this->totales = $totalSale;

            // Actualizar header
            $map = "TOTAL COSTO $" . number_format($totalCost, 2);
            $child = "TOTAL VENTA $" . number_format($totalSale, 2);
            $margin = $totalSale > 0 ? ($profit / $totalSale) * 100 : 0;
            $rest = " GANANCIA: $" . number_format($profit, 2) . " / MARGEN: " . number_format($margin, 2) . "%";

            session(['map' => $map, 'child' => $child, 'rest' => $rest, 'pos' => 'Reporte de Ventas']);
            
            $this->dispatch('update-header', map: $map, child: $child, rest: $rest);
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
