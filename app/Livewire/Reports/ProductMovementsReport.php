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
    
    // PDF Properties
    public $reportData = [];
    public $showPdfModal = false;
    public $pdfUrl = '';

    // Corte de Inventario Properties
    public $cut_warehouses = [];
    public $cut_notes = '';

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        session(['map' => '', 'child' => '', 'rest' => '', 'pos' => 'Resumen de Movimientos de Producto']);
    }

    public function render()
    {
        $movements = collect();

        if ($this->product_id) {
            $this->calculateMovements();
            $movements = $this->getMovements();
        }

        $warehouses = \App\Models\Warehouse::where('is_active', 1)->orderBy('id')->get();

        return view('livewire.reports.product-movements-report', [
            'movements' => $movements,
            'warehouses' => $warehouses
        ]);
    }

    public function updatedSearch()
    {
        $this->searchProducts();
    }

    public function updatedSelectedWarehouseId()
    {
        $this->calculateMovements();
    }

    public function updatedDateFrom()
    {
        $this->calculateMovements();
    }

    public function updatedDateTo()
    {
        $this->calculateMovements();
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

    public function openCutModal()
    {
        if (!$this->product_id) {
            $this->dispatch('noty', msg: 'Seleccione un producto para realizar el corte de inventario.');
            return;
        }

        $product = Product::find($this->product_id);
        if (!$product) return;

        $warehouses = DB::table('warehouses')->where('is_active', 1)->get();
        $this->cut_warehouses = [];

        foreach ($warehouses as $wh) {
            $pw = DB::table('product_warehouse')
                ->where('product_id', $this->product_id)
                ->where('warehouse_id', $wh->id)
                ->first();

            $stock = $pw ? floatval($pw->stock_qty) : 0;

            $this->cut_warehouses[$wh->id] = [
                'name' => $wh->name,
                'current_stock' => $stock,
                'counted_stock' => $stock < 0 ? 0 : $stock,
            ];
        }

        $this->cut_notes = 'Corte y toma física de inventario al ' . Carbon::now()->format('d/m/Y H:i');
        $this->dispatch('show-cut-modal');
    }

    public function saveInventoryCut()
    {
        if (!$this->product_id) return;

        foreach ($this->cut_warehouses as $whId => $data) {
            if (!isset($data['counted_stock']) || !is_numeric($data['counted_stock']) || floatval($data['counted_stock']) < 0) {
                $this->dispatch('noty', msg: 'Las cantidades contadas deben ser valores numéricos mayores o iguales a 0.');
                return;
            }
        }

        DB::beginTransaction();
        try {
            $product = Product::find($this->product_id);
            $userId = auth()->id() ?? 1;
            $totalCounted = 0;
            $now = Carbon::now();

            $cut = \App\Models\InventoryCut::create([
                'product_id' => $this->product_id,
                'user_id' => $userId,
                'cut_date' => $now,
                'total_stock' => 0,
                'notes' => $this->cut_notes ?: 'Corte físico de inventario',
            ]);

            foreach ($this->cut_warehouses as $whId => $data) {
                $counted = floatval($data['counted_stock']);
                $current = floatval($data['current_stock']);
                $diff = $counted - $current;
                $totalCounted += $counted;

                \App\Models\InventoryCutDetail::create([
                    'inventory_cut_id' => $cut->id,
                    'warehouse_id' => $whId,
                    'counted_stock' => $counted,
                    'previous_stock' => $current,
                ]);

                if ($diff > 0) {
                    $cargo = \App\Models\Cargo::create([
                        'user_id' => $userId,
                        'warehouse_id' => $whId,
                        'date' => $now,
                        'motive' => 'Corte de Inventario: ' . ($this->cut_notes ?: 'Ajuste inicial'),
                        'status' => 'approved',
                    ]);

                    \App\Models\CargoDetail::create([
                        'cargo_id' => $cargo->id,
                        'product_id' => $this->product_id,
                        'quantity' => $diff,
                        'cost' => $product->cost ?? 0,
                    ]);
                } elseif ($diff < 0) {
                    $descargo = \App\Models\Descargo::create([
                        'user_id' => $userId,
                        'warehouse_id' => $whId,
                        'date' => $now,
                        'motive' => 'Corte de Inventario: ' . ($this->cut_notes ?: 'Ajuste inicial'),
                        'status' => 'approved',
                    ]);

                    \App\Models\DescargoDetail::create([
                        'descargo_id' => $descargo->id,
                        'product_id' => $this->product_id,
                        'quantity' => abs($diff),
                        'cost' => $product->cost ?? 0,
                    ]);
                }

                $pw = \App\Models\ProductWarehouse::firstOrCreate(
                    ['product_id' => $this->product_id, 'warehouse_id' => $whId],
                    ['stock_qty' => 0]
                );
                $pw->stock_qty = $counted;
                $pw->save();
            }

            $cut->total_stock = $totalCounted;
            $cut->save();

            $product->stock_qty = $totalCounted;
            $product->save();

            // Ajustar fecha 'Desde' al día de hoy para arrancar limpio el reporte desde el corte
            $this->dateFrom = $now->format('Y-m-d');

            DB::commit();

            $this->dispatch('hide-cut-modal');
            $this->dispatch('noty', msg: '¡Corte de inventario aplicado con éxito! El stock inicial quedó fijado en ' . number_format($totalCounted, 2) . ' unidades.');
            $this->calculateMovements();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('noty', msg: 'Error al aplicar el corte de inventario: ' . $e->getMessage());
        }
    }

    public function calculateMovements()
    {
        if (!$this->product_id) return;

        $start = Carbon::parse($this->dateFrom)->startOfDay();
        $end = Carbon::parse($this->dateTo)->endOfDay();
        $warehouseId = $this->selected_warehouse_id;
        
        // 1. Buscar si hay cortes de inventario
        // Prioridad A: Corte en o antes de la fecha inicial (corte anterior a dateFrom)
        $latestCutBeforeStart = DB::table('inventory_cuts as ic')
            ->leftJoin('inventory_cut_details as icd', function($join) use ($warehouseId) {
                $join->on('icd.inventory_cut_id', '=', 'ic.id');
                if ($warehouseId != 'all') {
                    $join->where('icd.warehouse_id', $warehouseId);
                }
            })
            ->where('ic.product_id', $this->product_id)
            ->where('ic.cut_date', '<=', $start)
            ->orderBy('ic.cut_date', 'desc')
            ->select(
                'ic.id',
                'ic.cut_date',
                DB::raw($warehouseId != 'all' ? 'COALESCE(SUM(icd.counted_stock), 0) as baseline_stock' : 'ic.total_stock as baseline_stock')
            )
            ->groupBy('ic.id', 'ic.cut_date', 'ic.total_stock')
            ->first();

        // Prioridad B: Corte ocurrido dentro del rango (cuando no hay corte anterior)
        $latestCutInRange = DB::table('inventory_cuts as ic')
            ->leftJoin('inventory_cut_details as icd', function($join) use ($warehouseId) {
                $join->on('icd.inventory_cut_id', '=', 'ic.id');
                if ($warehouseId != 'all') {
                    $join->where('icd.warehouse_id', $warehouseId);
                }
            })
            ->where('ic.product_id', $this->product_id)
            ->whereBetween('ic.cut_date', [$start, $end])
            ->orderBy('ic.cut_date', 'asc')
            ->select(
                'ic.id',
                'ic.cut_date',
                DB::raw($warehouseId != 'all' ? 'COALESCE(SUM(icd.counted_stock), 0) as baseline_stock' : 'ic.total_stock as baseline_stock')
            )
            ->groupBy('ic.id', 'ic.cut_date', 'ic.total_stock')
            ->first();

        if ($latestCutBeforeStart) {
            $cutDateBoundary = $latestCutBeforeStart->cut_date;
            $baselineStock = floatval($latestCutBeforeStart->baseline_stock);
        } elseif ($latestCutInRange) {
            $cutDateBoundary = $latestCutInRange->cut_date;
            $baselineStock = floatval($latestCutInRange->baseline_stock);
        } else {
            $cutDateBoundary = null;
            $baselineStock = 0;
        }

        // --- Calcular Stock Inicial (Todo entre cutDateBoundary y dateFrom) ---
        $inBefore = 0;
        $outBefore = 0;

        if ($latestCutBeforeStart) {
            // Compras
            $inBefore += DB::table('purchase_details')
                ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
                ->where('product_id', $this->product_id)
                ->where('purchase_details.created_at', '<', $start)
                ->where('purchase_details.created_at', '>', $cutDateBoundary)
                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                    $q->where('purchases.warehouse_id', $warehouseId);
                })
                ->sum('quantity');

            // Cargos (Ajustes +) excluyendo motivos de corte
            $inBefore += DB::table('cargo_details')
                ->join('cargos', 'cargos.id', '=', 'cargo_details.cargo_id')
                ->where('product_id', $this->product_id)
                ->where('cargos.motive', 'NOT LIKE', '%Corte de Inventario%')
                ->where('cargo_details.created_at', '<', $start)
                ->where('cargo_details.created_at', '>', $cutDateBoundary)
                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                    $q->where('cargos.warehouse_id', $warehouseId);
                })
                ->sum('quantity');

            // Devoluciones (Ajustes +) - Solo Aprobadas
            $inBefore += DB::table('sale_return_details')
                ->join('sale_details', 'sale_details.id', '=', 'sale_return_details.sale_detail_id')
                ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_details.sale_return_id')
                ->where('sale_return_details.product_id', $this->product_id)
                ->where('sale_returns.status', 'approved')
                ->where('sale_return_details.created_at', '<', $start)
                ->where('sale_return_details.created_at', '>', $cutDateBoundary)
                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                    $q->where('sale_details.warehouse_id', $warehouseId);
                })
                ->sum('quantity_returned');

            // Ventas Anuladas
            $inBefore += DB::table('sale_details')
                ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                ->where('product_id', $this->product_id)
                ->where(function($q) {
                    $q->whereNotNull('sales.deleted_at')
                      ->orWhereNotNull('sales.deletion_approved_at')
                      ->orWhereIn('sales.status', ['cancelled', 'voided', 'anulated']);
                })
                ->where(DB::raw("COALESCE(sales.deletion_approved_at, sales.deleted_at, sales.updated_at)"), '<', $start)
                ->where(DB::raw("COALESCE(sales.deletion_approved_at, sales.deleted_at, sales.updated_at)"), '>', $cutDateBoundary)
                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                    $q->where('sale_details.warehouse_id', $warehouseId);
                })
                ->sum('quantity');

            // Transferencias (Entrada)
            $inBefore += DB::table('transfer_details')
                ->join('transfers', 'transfers.id', '=', 'transfer_details.transfer_id')
                ->where('product_id', $this->product_id)
                ->where('transfer_details.created_at', '<', $start)
                ->where('transfer_details.created_at', '>', $cutDateBoundary)
                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                    $q->where('transfers.to_warehouse_id', $warehouseId);
                })
                ->sum('quantity');

            // Producción de Planta (Entrada)
            $inBefore += DB::table('production_outputs as po')
                ->join('production_logs as pl', 'pl.id', '=', 'po.production_log_id')
                ->join('shifts as sh', 'sh.id', '=', 'pl.shift_id')
                ->where('po.product_id', $this->product_id)
                ->whereIn('po.quality', ['1st', '2nd'])
                ->where('po.created_at', '<', $start)
                ->where('po.created_at', '>', $cutDateBoundary)
                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                    $q->where('sh.warehouse_id', $warehouseId);
                })
                ->sum('po.quantity');

            // Ventas
            $outBefore += DB::table('sale_details')
                ->where('product_id', $this->product_id)
                ->where('sale_details.created_at', '<', $start)
                ->where('sale_details.created_at', '>', $cutDateBoundary)
                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                })
                ->sum('quantity');

            // Descargos (Ajustes -) excluyendo motivos de corte
            $outBefore += DB::table('descargo_details')
                ->join('descargos', 'descargos.id', '=', 'descargo_details.descargo_id')
                ->where('product_id', $this->product_id)
                ->where('descargos.motive', 'NOT LIKE', '%Corte de Inventario%')
                ->where('descargo_details.created_at', '<', $start)
                ->where('descargo_details.created_at', '>', $cutDateBoundary)
                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                    $q->where('descargos.warehouse_id', $warehouseId);
                })
                ->sum('quantity');

            // Transferencias (Salida)
            $outBefore += DB::table('transfer_details')
                ->join('transfers', 'transfers.id', '=', 'transfer_details.transfer_id')
                ->where('product_id', $this->product_id)
                ->where('transfer_details.created_at', '<', $start)
                ->where('transfer_details.created_at', '>', $cutDateBoundary)
                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                    $q->where('transfers.from_warehouse_id', $warehouseId);
                })
                ->sum('quantity');

            // Consumo de Materia Prima en Planta (Salida)
            $outBefore += DB::table('production_materials as pm')
                ->join('production_logs as pl', 'pl.id', '=', 'pm.production_log_id')
                ->join('shifts as sh', 'sh.id', '=', 'pl.shift_id')
                ->where('pm.product_id', $this->product_id)
                ->where('pm.created_at', '<', $start)
                ->where('pm.created_at', '>', $cutDateBoundary)
                ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                    $q->where('sh.warehouse_id', $warehouseId);
                })
                ->sum('pm.quantity');
        }

        $this->initialStock = $baselineStock + $inBefore - $outBefore;

        // --- 2. Calcular Totales y Existencia Final del Rango ---
        $movements = $this->getMovements();
        $this->totalIn = $movements->where('is_cut_reset', 0)->sum('quantity_in');
        $this->totalOut = $movements->where('is_cut_reset', 0)->sum('quantity_out');

        $currentBal = $this->initialStock;
        foreach ($movements as $m) {
            if (isset($m->is_cut_reset) && $m->is_cut_reset == 1) {
                $currentBal = floatval($m->quantity_in);
            } else {
                $currentBal += (floatval($m->quantity_in) - floatval($m->quantity_out));
            }
        }
        $this->finalStock = $currentBal;
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
                'sd.quantity as quantity_out',
                DB::raw("0 as is_cut_reset")
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
                DB::raw("0 as quantity_out"),
                DB::raw("0 as is_cut_reset")
            );

        $ca = DB::table('cargo_details as cd')
            ->join('cargos as c', 'c.id', '=', 'cd.cargo_id')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'c.warehouse_id')
            ->where('cd.product_id', $this->product_id)
            ->where('c.motive', 'NOT LIKE', '%Corte de Inventario%')
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
                DB::raw("0 as quantity_out"),
                DB::raw("0 as is_cut_reset")
            );

        $de = DB::table('descargo_details as dd')
            ->join('descargos as d', 'd.id', '=', 'dd.descargo_id')
            ->join('users as u', 'u.id', '=', 'd.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'd.warehouse_id')
            ->where('dd.product_id', $this->product_id)
            ->where('d.motive', 'NOT LIKE', '%Corte de Inventario%')
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
                'dd.quantity as quantity_out',
                DB::raw("0 as is_cut_reset")
            );

        $re = DB::table('sale_return_details as rd')
            ->join('sale_returns as r', 'r.id', '=', 'rd.sale_return_id')
            ->join('sale_details as sd_orig', 'sd_orig.id', '=', 'rd.sale_detail_id')
            ->join('sales as s', 's.id', '=', 'r.sale_id')
            ->join('customers as c', 'c.id', '=', 's.customer_id')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sd_orig.warehouse_id')
            ->where('rd.product_id', $this->product_id)
            ->where('r.status', 'approved')
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
                DB::raw("0 as quantity_out"),
                DB::raw("0 as is_cut_reset")
            );

        // Ventas Anuladas - ENTRADA
        $va = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->join('customers as c', 'c.id', '=', 's.customer_id')
            ->leftJoin('users as u', 'u.id', '=', 's.deletion_approved_by')
            ->leftJoin('users as u2', 'u2.id', '=', 's.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sd.warehouse_id')
            ->where('sd.product_id', $this->product_id)
            ->where(function($q) {
                $q->whereNotNull('s.deleted_at')
                  ->orWhereNotNull('s.deletion_approved_at')
                  ->orWhereIn('s.status', ['cancelled', 'voided', 'anulated']);
            })
            ->whereBetween(DB::raw("COALESCE(s.deletion_approved_at, s.deleted_at, s.updated_at)"), [$start, $end])
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('sd.warehouse_id', $warehouseId);
            })
            ->select(
                DB::raw("COALESCE(s.deletion_approved_at, s.deleted_at, s.updated_at) as movement_date"),
                DB::raw("'Venta Anulada (Reingreso)' as type"),
                's.invoice_number as reference',
                DB::raw("COALESCE(u.name, u2.name, 'Sistema') as operator"),
                DB::raw("CONCAT('Anulación: ', COALESCE(s.deletion_reason, 'N/A')) as detail"),
                'w.name as warehouse_name',
                'sd.quantity as quantity_in',
                DB::raw("0 as quantity_out"),
                DB::raw("0 as is_cut_reset")
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
                DB::raw("0 as quantity_out"),
                DB::raw("0 as is_cut_reset")
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
                'td.quantity as quantity_out',
                DB::raw("0 as is_cut_reset")
            );

        // Producción Soplados - ENTRADA
        $prodIn = DB::table('production_outputs as po')
            ->join('production_logs as pl', 'pl.id', '=', 'po.production_log_id')
            ->join('shifts as sh', 'sh.id', '=', 'pl.shift_id')
            ->join('users as u', 'u.id', '=', 'pl.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sh.warehouse_id')
            ->where('po.product_id', $this->product_id)
            ->whereIn('po.quality', ['1st', '2nd'])
            ->whereBetween('po.created_at', [$start, $end])
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('sh.warehouse_id', $warehouseId);
            })
            ->select(
                'po.created_at as movement_date',
                DB::raw("'Producción' as type"),
                DB::raw("CONCAT('Lote #', pl.id) as reference"),
                'u.name as operator',
                DB::raw("CONCAT('Producción Soplados (Calidad ', UPPER(po.quality), ')') as detail"),
                'w.name as warehouse_name',
                'po.quantity as quantity_in',
                DB::raw("0 as quantity_out"),
                DB::raw("0 as is_cut_reset")
            );

        // Consumo de Materia Prima en Planta - SALIDA
        $prodMatOut = DB::table('production_materials as pm')
            ->join('production_logs as pl', 'pl.id', '=', 'pm.production_log_id')
            ->join('shifts as sh', 'sh.id', '=', 'pl.shift_id')
            ->join('users as u', 'u.id', '=', 'pl.user_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sh.warehouse_id')
            ->where('pm.product_id', $this->product_id)
            ->whereBetween('pm.created_at', [$start, $end])
            ->when($warehouseId != 'all', function($q) use ($warehouseId) {
                $q->where('sh.warehouse_id', $warehouseId);
            })
            ->select(
                'pm.created_at as movement_date',
                DB::raw("'Consumo Producción' as type"),
                DB::raw("CONCAT('Lote #', pl.id) as reference"),
                'u.name as operator',
                DB::raw("'Consumo Materia Prima en Planta' as detail"),
                'w.name as warehouse_name',
                DB::raw("0 as quantity_in"),
                'pm.quantity as quantity_out',
                DB::raw("0 as is_cut_reset")
            );

        // Cortes de Inventario Físico - REINICIO DE BALANZA (BASELINE)
        $cuts = DB::table('inventory_cuts as ic')
            ->leftJoin('users as u', 'u.id', '=', 'ic.user_id')
            ->where('ic.product_id', $this->product_id)
            ->whereBetween('ic.cut_date', [$start, $end])
            ->select(
                'ic.cut_date as movement_date',
                DB::raw("'Corte de Inventario' as type"),
                DB::raw("CONCAT('CORTE #', ic.id) as reference"),
                DB::raw("COALESCE(u.name, 'Administrador') as operator"),
                DB::raw("CONCAT('Conteo Físico Establecido (', COALESCE(ic.notes, 'Corte de Inventario'), ')') as detail"),
                DB::raw($warehouseId != 'all' 
                    ? "(SELECT w.name FROM warehouses w WHERE w.id = " . intval($warehouseId) . ") as warehouse_name" 
                    : "'TODOS LOS DEPÓSITOS' as warehouse_name"),
                DB::raw($warehouseId != 'all' 
                    ? "COALESCE((SELECT icd.counted_stock FROM inventory_cut_details icd WHERE icd.inventory_cut_id = ic.id AND icd.warehouse_id = " . intval($warehouseId) . "), 0) as quantity_in" 
                    : "ic.total_stock as quantity_in"),
                DB::raw("0 as quantity_out"),
                DB::raw("1 as is_cut_reset")
            );

        return $v->unionAll($co)
            ->unionAll($ca)
            ->unionAll($de)
            ->unionAll($re)
            ->unionAll($trIn)
            ->unionAll($trOut)
            ->unionAll($va)
            ->unionAll($prodIn)
            ->unionAll($prodMatOut)
            ->unionAll($cuts)
            ->orderBy('movement_date', 'asc')
            ->get();
    }
}
