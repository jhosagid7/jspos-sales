<?php

namespace App\Livewire;

use Livewire\Component;

class ProductionReport extends Component
{
    use \Livewire\WithPagination;

    public $dateFrom, $dateTo;
    private $pagination = 10;

    public function mount()
    {
        $this->dateFrom = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = \Carbon\Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $query = \App\Models\ProductionLog::with(['shift', 'user', 'materials.product', 'outputs.product.productionTarget'])
            ->orderBy('id', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $data = $query->paginate($this->pagination);

        // Calculate Summaries for the selected period
        $summaryQuery = \App\Models\ProductionLog::with(['materials', 'outputs'])
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo);

        $totalGood = 0;
        $totalDamaged = 0;
        $totalMaterials = 0;

        foreach ($summaryQuery->get() as $log) {
            $totalGood += $log->outputs->whereIn('quality', ['1st', '2nd'])->sum('quantity');
            $totalDamaged += $log->outputs->where('quality', 'damaged')->sum('quantity');
            $totalMaterials += $log->materials->sum('quantity');
        }

        $yield = $totalMaterials > 0 ? ($totalGood / $totalMaterials) * 100 : 0;

        return view('livewire.production-report', [
            'data' => $data,
            'stats' => [
                'totalGood' => $totalGood,
                'totalDamaged' => $totalDamaged,
                'totalMaterials' => $totalMaterials,
                'yield' => $yield
            ]
        ])->extends('layouts.theme.app')->section('content');
    }

    public function downloadSopladosReport()
    {
        $start = \Carbon\Carbon::parse($this->dateFrom)->startOfDay();
        $end = \Carbon\Carbon::parse($this->dateTo)->endOfDay();
        
        $config = \App\Models\Configuration::first();
        $sopladosWarehouseId = $config->soplados_warehouse_id ?? 3;
        $zonaWarehouse = \App\Models\Warehouse::where('name', 'like', '%ZONA%')->first();
        $zonaWarehouseId = $zonaWarehouse ? $zonaWarehouse->id : 4;

        $shifts = \App\Models\Shift::with([
            'users',
            'warehouse',
            'productionLogs.outputs.product.productionTarget',
            'productionLogs.materials.product'
        ])->where('warehouse_id', $sopladosWarehouseId)
          ->whereBetween('start_time', [$start, $end])
          ->where('status', 'closed')
          ->get();

        $total1st = 0;
        $total2nd = 0;
        $totalDamaged = 0;
        $productionOutputs = [];
        $materialsConsumed = [];

        foreach ($shifts as $shift) {
            foreach ($shift->productionLogs as $log) {
                foreach ($log->outputs as $out) {
                    if (!$out->product) continue;
                    $qty = floatval($out->quantity);
                    if ($out->quality === '1st') {
                        $total1st += $qty;
                    } else if ($out->quality === '2nd') {
                        $total2nd += $qty;
                    } else if ($out->quality === 'damaged') {
                        $totalDamaged += $qty;
                    }
                    
                    // Use actual product name for detail table
                    $pName = $out->product->name;

                    $qualityLabel = $out->quality === '1st' ? '1ra Calidad' : ($out->quality === '2nd' ? '2da Calidad' : 'Defectuoso');
                    $key = "{$pName} ({$qualityLabel})";
                    $productionOutputs[$key] = ($productionOutputs[$key] ?? 0) + $qty;
                }
                
                foreach ($log->materials as $mat) {
                    $pName = $mat->product->name ?? 'Material';
                    $qty = floatval($mat->quantity);
                    $materialsConsumed[$pName] = ($materialsConsumed[$pName] ?? 0) + $qty;
                }
            }
        }

        $totalWeekProduced = $total1st + $total2nd + $totalDamaged;
        $weekEfficiency = $totalWeekProduced > 0 ? (($total1st + $total2nd) / $totalWeekProduced) * 100 : 100;

        $targets = \App\Models\SopladosProductionTarget::with('product')->get()->keyBy('product_id');
        $shiftsData = [];

        foreach ($shifts as $shift) {
            $shiftGood = 0;
            $shiftDamaged = 0;
            $shiftOutputs = [];

            // 1. Calculate family totals for the shift
            $familyTotals = [];
            foreach ($shift->productionLogs as $log) {
                foreach ($log->outputs as $out) {
                    if (!$out->product) continue;
                    if (in_array($out->quality, ['1st', '2nd'])) {
                        $qty = floatval($out->quantity);
                        $familyId = $out->product->production_target_id ?? $out->product_id;
                        $familyTotals[$familyId] = ($familyTotals[$familyId] ?? 0) + $qty;
                    }
                }
            }

            // 2. Build rows by individual product ID
            foreach ($shift->productionLogs as $log) {
                foreach ($log->outputs as $out) {
                    if (!$out->product) continue;
                    $qty = floatval($out->quantity);
                    if (in_array($out->quality, ['1st', '2nd'])) {
                        $shiftGood += $qty;
                    } else if ($out->quality === 'damaged') {
                        $shiftDamaged += $qty;
                    }

                    $pId = $out->product_id;
                    $familyId = $out->product->production_target_id ?? $pId;

                    if (!isset($shiftOutputs[$pId])) {
                        $target = $targets->get($familyId);
                        $shiftOutputs[$pId] = [
                            'name' => $out->product->name,
                            'quantity' => 0,
                            'family_id' => $familyId,
                            'min' => $target ? $target->min_target : 0,
                            'max' => $target ? $target->max_target : 0,
                        ];
                    }
                    if (in_array($out->quality, ['1st', '2nd'])) {
                        $shiftOutputs[$pId]['quantity'] += $qty;
                    }
                }
            }

            // 3. Calculate compliance using family totals
            foreach ($shiftOutputs as $pId => &$outData) {
                $familyId = $outData['family_id'];
                $familyQty = $familyTotals[$familyId] ?? 0;
                $min = $outData['min'];
                $max = $outData['max'];

                if ($min > 0) {
                    $outData['compliance_pct'] = round(($familyQty / $min) * 100, 2);
                    if ($familyQty >= $min) {
                        $outData['status'] = 'Cumplido';
                    } else {
                        $outData['status'] = 'No Cumplido';
                    }
                } else {
                    $outData['compliance_pct'] = 100;
                    $outData['status'] = 'Sin Meta';
                }
            }

            $shiftsData[] = [
                'id' => $shift->id,
                'date' => $shift->start_time->format('d/m/Y'),
                'type' => $shift->type,
                'users' => $shift->users->pluck('name')->implode(', '),
                'outputs' => $shiftOutputs,
                'good' => $shiftGood,
                'damaged' => $shiftDamaged,
                'total' => $shiftGood + $shiftDamaged,
                'efficiency' => ($shiftGood + $shiftDamaged) > 0 ? ($shiftGood / ($shiftGood + $shiftDamaged)) * 100 : 100
            ];
        }

        $sopladosRawMaterialIds = \App\Models\Product::whereHas('tags', function($q) {
            $q->where('name', 'soplados');
        })->where('is_raw_material', true)->pluck('id')->all();

        $weeklyPurchases = \App\Models\PurchaseDetail::whereIn('product_id', $sopladosRawMaterialIds)
            ->whereHas('purchase', function($q) use ($start, $end, $sopladosWarehouseId, $zonaWarehouseId) {
                $q->whereBetween('created_at', [$start, $end])
                  ->whereIn('warehouse_id', [$sopladosWarehouseId, $zonaWarehouseId]);
            })
            ->with(['purchase.warehouse', 'product'])
            ->get();

        $weeklyCargos = \App\Models\CargoDetail::whereIn('product_id', $sopladosRawMaterialIds)
            ->whereHas('cargo', function($q) use ($start, $end, $sopladosWarehouseId, $zonaWarehouseId) {
                $q->whereBetween('approval_date', [$start, $end])
                  ->where('status', 'approved')
                  ->whereIn('warehouse_id', [$sopladosWarehouseId, $zonaWarehouseId]);
            })
            ->with(['cargo.warehouse', 'product'])
            ->get();

        $weeklyTransfers = \App\Models\TransferDetail::whereIn('product_id', $sopladosRawMaterialIds)
            ->whereHas('transfer', function($q) use ($start, $end, $sopladosWarehouseId, $zonaWarehouseId) {
                $q->whereBetween('updated_at', [$start, $end])
                  ->whereIn('status', ['completed', 'completed_partial'])
                  ->whereIn('to_warehouse_id', [$sopladosWarehouseId, $zonaWarehouseId]);
            })
            ->with(['transfer.fromWarehouse', 'transfer.toWarehouse', 'product'])
            ->get();

        $weeklyEntries = [];

        foreach ($weeklyPurchases as $detail) {
            $destName = $detail->purchase->warehouse->name ?? 'Almacén';
            $weeklyEntries[] = [
                'date' => $detail->created_at->format('d/m/Y'),
                'product' => $detail->product->name ?? 'Materia Prima',
                'quantity' => floatval($detail->quantity),
                'destination' => $destName,
                'source' => 'Compra Directa',
            ];
        }

        foreach ($weeklyCargos as $detail) {
            $destName = $detail->cargo->warehouse->name ?? 'Almacén';
            $weeklyEntries[] = [
                'date' => ($detail->cargo->approval_date ?? $detail->created_at)->format('d/m/Y'),
                'product' => $detail->product->name ?? 'Materia Prima',
                'quantity' => floatval($detail->quantity),
                'destination' => $destName,
                'source' => 'Cargo (' . ($detail->cargo->motive ?? 'Ajuste') . ')',
            ];
        }

        foreach ($weeklyTransfers as $detail) {
            $destName = $detail->transfer->toWarehouse->name ?? 'Planta Soplados';
            $fromName = $detail->transfer->fromWarehouse->name ?? 'Almacén Origen';
            $weeklyEntries[] = [
                'date' => $detail->updated_at->format('d/m/Y'),
                'product' => $detail->product->name ?? 'Materia Prima',
                'quantity' => floatval($detail->received_quantity ?? $detail->quantity),
                'destination' => $destName,
                'source' => "Traspaso desde {$fromName}",
            ];
        }

        usort($weeklyEntries, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        $rawMaterialStocks = [];
        $rawMaterialsList = \App\Models\Product::whereHas('tags', function($q) {
            $q->where('name', 'soplados');
        })->where('is_raw_material', true)->get();

        foreach ($rawMaterialsList as $rm) {
            $sopladosStock = \App\Models\ProductWarehouse::where('product_id', $rm->id)
                ->where('warehouse_id', $sopladosWarehouseId)
                ->value('stock_qty') ?? 0;

            $zonaStock = \App\Models\ProductWarehouse::where('product_id', $rm->id)
                ->where('warehouse_id', $zonaWarehouseId)
                ->value('stock_qty') ?? 0;

            $rawMaterialStocks[] = [
                'name' => $rm->name,
                'soplados_stock' => floatval($sopladosStock),
                'zona_stock' => floatval($zonaStock),
                'total_stock' => floatval($sopladosStock + $zonaStock),
                'unit' => $rm->allow_decimal ? 'Kg' : 'Unds'
            ];
        }

        $lastInventory = \App\Models\SopladosInventory::with(['details.product', 'supervisor', 'operator'])
            ->orderBy('created_at', 'desc')
            ->first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.soplados-weekly', [
            'dateFrom' => $start->toDateString(),
            'dateTo' => $end->toDateString(),
            'shifts' => $shifts,
            'totalGood' => $total1st,
            'totalSecond' => $total2nd,
            'totalDamaged' => $totalDamaged,
            'totalWeekProduced' => $totalWeekProduced,
            'weekEfficiency' => $weekEfficiency,
            'productionOutputs' => $productionOutputs,
            'materialsConsumed' => $materialsConsumed,
            'lastInventory' => $lastInventory,
            'shiftsData' => $shiftsData,
            'weeklyEntries' => $weeklyEntries,
            'rawMaterialStocks' => $rawMaterialStocks,
            'config' => $config
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'Reporte_Consolidado_Soplados_' . $start->format('Ymd') . '_al_' . $end->format('Ymd') . '.pdf'
        );
    }
}
