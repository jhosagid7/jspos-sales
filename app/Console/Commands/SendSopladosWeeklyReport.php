<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Configuration;
use App\Services\WhatsappService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class SendSopladosWeeklyReport extends Command
{
    protected $signature = 'app:send-soplados-weekly-report {date?}';
    protected $description = 'Generate weekly Soplados consolidado PDF and send it via WhatsApp and Email';

    public function handle()
    {
        $date = $this->argument('date') ?: Carbon::today()->toDateString();
        
        $config = Configuration::first();
        if (!$config) {
            $this->error('No configuration found.');
            return 1;
        }

        // We consolidate the last 7 days of closed shifts
        $end = Carbon::parse($date)->endOfDay();
        $start = Carbon::parse($date)->subDays(6)->startOfDay();

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

        $totalGood = 0;
        $totalDamaged = 0;
        $productionOutputs = [];
        $materialsConsumed = [];

        foreach ($shifts as $shift) {
            foreach ($shift->productionLogs as $log) {
                foreach ($log->outputs as $out) {
                    if (!$out->product) continue;
                    $qty = floatval($out->quantity);
                    if (in_array($out->quality, ['1st', '2nd'])) {
                        $totalGood += $qty;
                    } else if ($out->quality === 'damaged') {
                        $totalDamaged += $qty;
                    }
                    
                    // Resolve target product (parent) if it has one
                    $targetProduct = $out->product->productionTarget ?? $out->product;
                    $pName = $targetProduct->name;

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

        $totalWeekProduced = $totalGood + $totalDamaged;
        $weekEfficiency = $totalWeekProduced > 0 ? ($totalGood / $totalWeekProduced) * 100 : 100;

        // Fetch targets and calculate shift compliance
        $targets = \App\Models\SopladosProductionTarget::with('product')->get()->keyBy('product_id');
        $shiftsData = [];

        foreach ($shifts as $shift) {
            $shiftGood = 0;
            $shiftDamaged = 0;
            $shiftOutputs = [];

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

                    // Resolve target product ID and details for grouping
                    $targetProductId = $out->product->production_target_id ?? $pId;
                    $targetProduct = $out->product->productionTarget ?? $out->product;
                    $pName = $targetProduct->name;

                    if (!isset($shiftOutputs[$targetProductId])) {
                        $target = $targets->get($targetProductId);
                        $shiftOutputs[$targetProductId] = [
                            'name' => $pName,
                            'quantity' => 0,
                            'min' => $target ? $target->min_target : 0,
                            'max' => $target ? $target->max_target : 0,
                        ];
                    }
                    if (in_array($out->quality, ['1st', '2nd'])) {
                        $shiftOutputs[$targetProductId]['quantity'] += $qty;
                    }
                }
            }

            foreach ($shiftOutputs as $pId => &$outData) {
                $qty = $outData['quantity'];
                $min = $outData['min'];
                $max = $outData['max'];

                if ($min > 0) {
                    $outData['compliance_pct'] = round(($qty / $min) * 100, 2);
                    if ($qty >= $min) {
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

        // Query raw material entries for Soplados (ID 3) and Zona (ID 4)
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

        // Current stock in Soplados and Zona
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

        // Retrieve the last physical inventory of Soplados
        $lastInventory = \App\Models\SopladosInventory::with(['details.product', 'supervisor', 'operator'])
            ->orderBy('created_at', 'desc')
            ->first();

        // Render PDF
        $pdf = Pdf::loadView('pdf.soplados-weekly', [
            'dateFrom' => $start->toDateString(),
            'dateTo' => $end->toDateString(),
            'shifts' => $shifts,
            'totalGood' => $totalGood,
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

        // Ensure temp directory exists
        $tempDir = storage_path('app/temp');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $fileName = 'Reporte_Consolidado_Soplados_' . $start->format('Ymd') . '_al_' . $end->format('Ymd') . '.pdf';
        $filePath = $tempDir . '/' . $fileName;

        // Save PDF to temp file
        File::put($filePath, $pdf->output());

        // Send via Email
        try {
            $emailRecipients = $config->email_soplados_weekly_recipients ?: [];
            if (!empty($emailRecipients)) {
                $companyName = strtoupper($config->business_name ?: 'SISTEMA');
                $subject = "📄 Reporte Semanal Consolidado de Soplados - {$companyName}";
                $emailBody = "Estimados,\n\nAdjunto a este correo encontrarán el reporte semanal consolidado correspondiente al período del " . $start->format('d/m/Y') . " al " . $end->format('d/m/Y') . " de la planta de Soplados.\n\nEste reporte consolida los turnos de manufactura y el último inventario físico cargado al sistema.\n\nAtentamente,\nControl de Calidad y Soplado";

                \Illuminate\Support\Facades\Mail::to($emailRecipients)->send(new \App\Mail\GenericNotificationMail(
                    $subject,
                    $emailBody,
                    $filePath
                ));
                $this->info("Weekly Soplados consolidado PDF sent successfully via email.");
            }
        } catch (\Exception $e) {
            $this->error("Error sending weekly Soplados email: " . $e->getMessage());
        }

        // Send via WhatsApp
        try {
            $whatsappService = app(WhatsappService::class);
            if ($whatsappService->checkStatus()) {
                $companyName = strtoupper($config->business_name ?: 'SISTEMA');
                $waMessage = "📄 *REPORTE CONSOLIDADO SEMANAL - SOPLADOS*\n" .
                             "🏢 *{$companyName}*\n" .
                             "📅 *Período:* " . $start->format('d/m/Y') . " al " . $end->format('d/m/Y') . "\n" .
                             "-----------------------------------\n" .
                             "• *Turnos Cerrados:* " . $shifts->count() . "\n" .
                             "• *Rendimiento (Yield):* " . number_format($weekEfficiency, 2) . "%\n" .
                             "• *Prod. Buena:* " . number_format($totalGood, 0) . " unds\n" .
                             "• *Merma:* " . number_format($totalDamaged, 0) . " unds\n" .
                             "-----------------------------------\n" .
                             "Adjunto encontrarás el consolidado semanal en PDF.";

                // Send to Groups
                $selectedGroups = $config->whatsapp_soplados_weekly_groups ?: [];
                if (!empty($selectedGroups)) {
                    foreach ($selectedGroups as $groupId) {
                        $whatsappService->sendMessage($groupId, $waMessage, $filePath);
                    }
                }

                // Send to specific Users
                $selectedUsers = $config->whatsapp_soplados_weekly_users ?: [];
                if (!empty($selectedUsers)) {
                    $users = \App\Models\User::whereIn('id', $selectedUsers)->whereNotNull('phone')->get();
                    foreach ($users as $user) {
                        $whatsappService->sendMessage($user->phone, $waMessage, $filePath);
                    }
                }
                $this->info("Weekly Soplados consolidado PDF sent successfully via WhatsApp.");
            } else {
                $this->warn("WhatsApp client is offline. Skipping WhatsApp send.");
            }
        } catch (\Exception $e) {
            $this->error("Error sending WhatsApp consolidado weekly: " . $e->getMessage());
        } finally {
            // Clean up temp file
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        return 0;
    }
}
