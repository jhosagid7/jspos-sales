<?php

namespace App\Livewire\Reports;

use App\Models\Product;
use App\Models\SaleDetail;
use App\Models\PurchaseDetail;
use App\Models\CargoDetail;
use App\Models\DescargoDetail;
use App\Models\SaleReturnDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProductMovementsReport extends Component
{
    public $product_id;
    public $search = '';
    public $products_results = [];
    public $dateFrom;
    public $dateTo;
    public $initialStock = 0;
    public $totalIn = 0;
    public $totalOut = 0;
    public $finalStock = 0;
    
    // Filtros
    public $selected_warehouse_id = 'all';
    public $warehouses_list = [];
    
    // PDF Properties
    public $reportData = [];
    public $showPdfModal = false;
    public $pdfUrl = '';

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->warehouses_list = DB::table('warehouses')->where('is_active', 1)->get();
        session(['pos' => 'Resumen de Movimientos de Producto']);
    }

    public function render()
    {
        $movements = collect();

        if ($this->product_id) {
            $this->calculateMovements();
            $movements = $this->getMovements();
        }

        return view('livewire.reports.product-movements-report', [
            'movements' => $movements
        ]);
    }

    public function updatedSearch()
    {
        $this->searchProducts();
    }

    public function searchProducts()
    {
        $search = trim($this->search);
        
        if (strlen($search) > 0) {
            $query = Product::query();
            
            $tokens = explode(' ', $search);
            
            foreach ($tokens as $token) {
                if (!empty($token)) {
                    $query->where(function($q) use ($token) {
                        $q->where('name', 'like', "%{$token}%")
                          ->orWhere('sku', 'like', "%{$token}%")
                          ->orWhereHas('category', function ($subQuery) use ($token) {
                              $subQuery->where('name', 'like', "%{$token}%");
                          })
                          ->orWhereHas('tags', function ($subQuery) use ($token) {
                              $subQuery->where('name', 'like', "%{$token}%");
                          });
                    });
                }
            }
            
            $this->products_results = $query->take(10)->get();
        } else {
            $this->products_results = [];
        }
    }

    public function selectProduct($id)
    {
        $product = Product::find($id);
        if ($product) {
            $this->product_id = $id;
            $this->search = $product->sku . ' - ' . $product->name;
            $this->products_results = [];
            $this->calculateMovements();
        }
    }

    public function openModalPdf()
    {
        if (!$this->product_id) {
            $this->dispatch('noty', msg: 'Debe seleccionar un producto primero');
            return;
        }

        $this->calculateMovements();
        $this->pdfUrl = route('reports.product.movements.pdf', [
            'product_id' => $this->product_id,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'warehouse_id' => $this->selected_warehouse_id
        ]);

        $this->showPdfModal = true;
        $this->dispatch('show-modal-pdf');
    }

    public function closeModalPdf()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    public function calculateMovements()
    {
        if (!$this->product_id) return;

        $start = Carbon::parse($this->dateFrom)->startOfDay();
        $warehouseId = $this->selected_warehouse_id;
        
        // --- 1. Calcular Stock Inicial (Todo antes de dateFrom) ---
        $inBefore = 0;
        $outBefore = 0;

        // Compras
        $inBefore += DB::table('purchase_details')
            ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
            ->where('product_id', $this->product_id)
            ->where('purchase_details.created_at', '<', $start)
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('purchases.warehouse_id', $warehouseId);
            })
            ->sum('quantity');

        // Cargos (Ajustes +)
        $inBefore += DB::table('cargo_details')
            ->where('product_id', $this->product_id)
            ->where('cargo_details.created_at', '<', $start)
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->join('cargos', 'cargos.id', '=', 'cargo_details.cargo_id')
                  ->where('cargos.warehouse_id', $warehouseId);
            })
            ->sum('quantity');

        // Devoluciones (Ajustes +)
        $inBefore += DB::table('sale_return_details')
            ->join('sale_details', 'sale_details.id', '=', 'sale_return_details.sale_detail_id')
            ->where('sale_return_details.product_id', $this->product_id)
            ->where('sale_return_details.created_at', '<', $start)
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('sale_details.warehouse_id', $warehouseId);
            })
            ->sum('quantity_returned');

        // Transferencias (Entrada)
        $inBefore += DB::table('transfer_details')
            ->join('transfers', 'transfers.id', '=', 'transfer_details.transfer_id')
            ->where('product_id', $this->product_id)
            ->where('transfer_details.created_at', '<', $start)
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('transfers.to_warehouse_id', $warehouseId);
            })
            ->sum('quantity');

        // Ventas
        $outBefore += DB::table('sale_details')
            ->where('product_id', $this->product_id)
            ->where('sale_details.created_at', '<', $start)
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            })
            ->sum('quantity');

        // Descargos (Ajustes -)
        $outBefore += DB::table('descargo_details')
            ->where('product_id', $this->product_id)
            ->where('descargo_details.created_at', '<', $start)
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->join('descargos', 'descargos.id', '=', 'descargo_details.descargo_id')
                  ->where('descargos.warehouse_id', $warehouseId);
            })
            ->sum('quantity');

        // Transferencias (Salida)
        $outBefore += DB::table('transfer_details')
            ->join('transfers', 'transfers.id', '=', 'transfer_details.transfer_id')
            ->where('product_id', $this->product_id)
            ->where('transfer_details.created_at', '<', $start)
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('transfers.from_warehouse_id', $warehouseId);
            })
            ->sum('quantity');

        $this->initialStock = $inBefore - $outBefore;

        // --- 2. Calcular Totales del Rango ---
        $end = Carbon::parse($this->dateTo)->endOfDay();

        $this->totalIn = DB::table('purchase_details')
                        ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
                        ->where('product_id', $this->product_id)->whereBetween('purchase_details.created_at', [$start, $end])
                        ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                            $q->where('purchases.warehouse_id', $warehouseId);
                        })->sum('quantity')
                       + DB::table('cargo_details')->where('product_id', $this->product_id)->whereBetween('cargo_details.created_at', [$start, $end])
                                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                                    $q->join('cargos', 'cargos.id', '=', 'cargo_details.cargo_id')->where('cargos.warehouse_id', $warehouseId);
                                })->sum('quantity')
                       + DB::table('sale_return_details')
                                ->join('sale_details', 'sale_details.id', '=', 'sale_return_details.sale_detail_id')
                                ->where('sale_return_details.product_id', $this->product_id)->whereBetween('sale_return_details.created_at', [$start, $end])
                                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                                    $q->where('sale_details.warehouse_id', $warehouseId);
                                })->sum('quantity_returned')
                       + DB::table('transfer_details')->join('transfers', 'transfers.id', '=', 'transfer_details.transfer_id')
                                ->where('product_id', $this->product_id)->whereBetween('transfer_details.created_at', [$start, $end])
                                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                                    $q->where('transfers.to_warehouse_id', $warehouseId);
                                })->sum('quantity');

        $this->totalOut = DB::table('sale_details')->where('product_id', $this->product_id)->whereBetween('sale_details.created_at', [$start, $end])
                                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                                    $q->where('warehouse_id', $warehouseId);
                                })->sum('quantity')
                        + DB::table('descargo_details')->where('product_id', $this->product_id)->whereBetween('descargo_details.created_at', [$start, $end])
                                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                                    $q->join('descargos', 'descargos.id', '=', 'descargo_details.descargo_id')->where('descargos.warehouse_id', $warehouseId);
                                })->sum('quantity')
                        + DB::table('transfer_details')->join('transfers', 'transfers.id', '=', 'transfer_details.transfer_id')
                                ->where('product_id', $this->product_id)->whereBetween('transfer_details.created_at', [$start, $end])
                                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                                    $q->where('transfers.from_warehouse_id', $warehouseId);
                                })->sum('quantity');

        $this->finalStock = $this->initialStock + $this->totalIn - $this->totalOut;
    }

    public function getMovements()
    {
        if (!$this->product_id) return collect();

        $start = Carbon::parse($this->dateFrom)->startOfDay();
        $end = Carbon::parse($this->dateTo)->endOfDay();
        $warehouseId = $this->selected_warehouse_id;

        $v = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->join('customers as c', 'c.id', '=', 's.customer_id')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sd.warehouse_id')
            ->where('sd.product_id', $this->product_id)
            ->whereBetween('sd.created_at', [$start, $end])
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('sd.warehouse_id', $warehouseId);
            })
            ->select(
                'sd.created_at as movement_date',
                DB::raw("'Venta' as type"),
                's.invoice_number as reference',
                'u.name as operator',
                'c.name as detail',
                'w.name as warehouse_name',
                DB::raw("0 as quantity_in"),
                'sd.quantity as quantity_out'
            );

        $co = DB::table('purchase_details as pd')
            ->join('purchases as p', 'p.id', '=', 'pd.purchase_id')
            ->join('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'p.warehouse_id')
            ->where('pd.product_id', $this->product_id)
            ->whereBetween('pd.created_at', [$start, $end])
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('p.warehouse_id', $warehouseId);
            })
            ->select(
                'pd.created_at as movement_date',
                DB::raw("'Compra' as type"),
                'p.id as reference',
                'u.name as operator',
                's.name as detail',
                DB::raw("COALESCE(w.name, 'Principal (Compras)') as warehouse_name"),
                'pd.quantity as quantity_in',
                DB::raw("0 as quantity_out")
            );

        $ca = DB::table('cargo_details as cd')
            ->join('cargos as c', 'c.id', '=', 'cd.cargo_id')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'c.warehouse_id')
            ->where('cd.product_id', $this->product_id)
            ->whereBetween('cd.created_at', [$start, $end])
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('c.warehouse_id', $warehouseId);
            })
            ->select(
                'cd.created_at as movement_date',
                DB::raw("'Cargo (Ajuste)' as type"),
                'c.id as reference',
                'u.name as operator',
                'c.motive as detail',
                'w.name as warehouse_name',
                'cd.quantity as quantity_in',
                DB::raw("0 as quantity_out")
            );

        $de = DB::table('descargo_details as dd')
            ->join('descargos as d', 'd.id', '=', 'dd.descargo_id')
            ->join('users as u', 'u.id', '=', 'd.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'd.warehouse_id')
            ->where('dd.product_id', $this->product_id)
            ->whereBetween('dd.created_at', [$start, $end])
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('d.warehouse_id', $warehouseId);
            })
            ->select(
                'dd.created_at as movement_date',
                DB::raw("'Descargo (Salida)' as type"),
                'd.id as reference',
                'u.name as operator',
                'd.motive as detail',
                'w.name as warehouse_name',
                DB::raw("0 as quantity_in"),
                'dd.quantity as quantity_out'
            );

        $re = DB::table('sale_return_details as rd')
            ->join('sale_returns as r', 'r.id', '=', 'rd.sale_return_id')
            ->join('sale_details as sd_orig', 'sd_orig.id', '=', 'rd.sale_detail_id')
            ->join('sales as s', 's.id', '=', 'r.sale_id')
            ->join('customers as c', 'c.id', '=', 's.customer_id')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sd_orig.warehouse_id')
            ->where('rd.product_id', $this->product_id)
            ->whereBetween('rd.created_at', [$start, $end])
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('sd_orig.warehouse_id', $warehouseId);
            })
            ->select(
                'rd.created_at as movement_date',
                DB::raw("'Devolución (NC)' as type"),
                'r.id as reference',
                'u.name as operator',
                'c.name as detail',
                DB::raw("COALESCE(w.name, 'Principal (NC)') as warehouse_name"),
                'rd.quantity_returned as quantity_in',
                DB::raw("0 as quantity_out")
            );

        // Transferencias - ENTRADA
        $trIn = DB::table('transfer_details as td')
            ->join('transfers as t', 't.id', '=', 'td.transfer_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 't.to_warehouse_id')
            ->leftJoin('warehouses as wf', 'wf.id', '=', 't.from_warehouse_id')
            ->where('td.product_id', $this->product_id)
            ->whereBetween('td.created_at', [$start, $end])
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('t.to_warehouse_id', $warehouseId);
            })
            ->select(
                'td.created_at as movement_date',
                DB::raw("'Transferencia (Entrada)' as type"),
                't.id as reference',
                'u.name as operator',
                DB::raw("CONCAT(COALESCE(wf.name, 'N/A'), ' -> ', COALESCE(w.name, 'N/A')) as detail"),
                'w.name as warehouse_name',
                'td.quantity as quantity_in',
                DB::raw("0 as quantity_out")
            );

        // Transferencias - SALIDA
        $trOut = DB::table('transfer_details as td')
            ->join('transfers as t', 't.id', '=', 'td.transfer_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 't.from_warehouse_id')
            ->leftJoin('warehouses as wt', 'wt.id', '=', 't.to_warehouse_id')
            ->where('td.product_id', $this->product_id)
            ->whereBetween('td.created_at', [$start, $end])
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('t.from_warehouse_id', $warehouseId);
            })
            ->select(
                'td.created_at as movement_date',
                DB::raw("'Transferencia (Salida)' as type"),
                't.id as reference',
                'u.name as operator',
                DB::raw("CONCAT(COALESCE(w.name, 'N/A'), ' -> ', COALESCE(wt.name, 'N/A')) as detail"),
                'w.name as warehouse_name',
                DB::raw("0 as quantity_in"),
                'td.quantity as quantity_out'
            );

        return $v->unionAll($co)
            ->unionAll($ca)
            ->unionAll($de)
            ->unionAll($re)
            ->unionAll($trIn)
            ->unionAll($trOut)
            ->orderBy('movement_date', 'asc')
            ->get();
    }
}
