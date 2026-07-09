<?php

namespace App\Http\Controllers\Api\Soplados;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function current()
    {
        $shift = \App\Models\Shift::with('users')->where('status', 'open')->latest()->first();

        if (!$shift) {
            return response()->json(['success' => true, 'shift' => null]);
        }

        // Add opened_at alias for the Flutter app (DB column is start_time)
        $data = $shift->toArray();
        $data['opened_at'] = $shift->start_time;

        // Calculate Shift Stats (Good vs Damaged)
        $stats = \App\Models\ProductionLog::where('shift_id', $shift->id)
            ->with(['outputs' => function($q) {
                $q->select('production_log_id', 'quality', 'quantity');
            }])
            ->get();

        $good = 0;
        $damaged = 0;
        
        foreach ($stats as $log) {
            foreach ($log->outputs as $out) {
                if ($out->quality == '1st' || $out->quality == '2nd') {
                    $good += $out->quantity;
                } else if ($out->quality == 'damaged') {
                    $damaged += $out->quantity;
                }
            }
        }

        $data['stats'] = [
            'good_production' => $good,
            'damaged_production' => $damaged,
            'total' => $good + $damaged,
            'yield' => ($good + $damaged) > 0 ? round(($good / ($good + $damaged)) * 100, 2) : 0
        ];

        return response()->json(['success' => true, 'shift' => $data]);
    }

    public function open(Request $request)
    {
        // Accept both Spanish (diurno/nocturno) and English (day/night) type values
        $typeMap = ['diurno' => 'day', 'nocturno' => 'night', 'day' => 'day', 'night' => 'night'];

        $request->validate([
            'type' => ['required', \Illuminate\Validation\Rule::in(array_keys($typeMap))],
        ]);

        // Check if there is already an open shift
        $active = \App\Models\Shift::where('status', 'open')->first();
        if ($active) {
            return response()->json(['success' => false, 'message' => 'Ya existe un turno abierto.'], 400);
        }

        $authUserId = auth()->id();
        $resolvedType = $typeMap[$request->type] ?? $request->type;

        $config = \App\Models\Configuration::first();
        $soplados_id = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;

        $shift = \App\Models\Shift::create([
            'type'         => $resolvedType,
            'start_time'   => now(),
            'status'       => 'open',
            'user_id'      => $authUserId,
            'warehouse_id' => auth()->user()->warehouse_id ?? $soplados_id,
        ]);

        // Attach authenticated user (and any extra user_ids if provided)
        $userIds = collect($request->input('user_ids', []))->push($authUserId)->unique()->filter()->values()->all();
        $shift->users()->attach($userIds);

        // Add opened_at alias for the Flutter app
        $shiftData = $shift->load('users')->toArray();
        $shiftData['opened_at'] = $shift->start_time;

        return response()->json(['success' => true, 'message' => 'Turno abierto correctamente', 'shift' => $shiftData]);
    }

    public function history(Request $request)
    {
        $query = \App\Models\Shift::with(['users', 'productionLogs.outputs'])
            ->orderBy('id', 'desc');

        if ($request->date_from) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        $shifts = $query->paginate(20);

        $shifts->getCollection()->transform(function($shift) {
            $good = 0;
            $damaged = 0;
            
            foreach ($shift->productionLogs as $log) {
                foreach ($log->outputs as $out) {
                    if ($out->quality == '1st' || $out->quality == '2nd') {
                        $good += $out->quantity;
                    } else if ($out->quality == 'damaged') {
                        $damaged += $out->quantity;
                    }
                }
            }

            $data = $shift->toArray();
            $data['stats'] = [
                'good' => $good,
                'damaged' => $damaged,
                'total' => $good + $damaged,
                'yield' => ($good + $damaged) > 0 ? round(($good / ($good + $damaged)) * 100, 2) : 0
            ];
            return $data;
        });

        return response()->json(['success' => true, 'data' => $shifts]);
    }

    public function close(Request $request)
    {
        $shift = \App\Models\Shift::where('status', 'open')->latest()->first();

        if (!$shift) {
            return response()->json(['success' => false, 'message' => 'No hay turno abierto para cerrar.'], 400);
        }

        $shift->update([
            'end_time' => now(),
            'status' => 'closed',
            'notes' => $request->notes
        ]);

        // Send Email Notification
        $this->sendShiftCloseEmail($shift->id);

        return response()->json(['success' => true, 'message' => 'Turno cerrado correctamente']);
    }

    public function compileShiftData($shift)
    {
        $goodQuantity = 0;
        $damagedQuantity = 0;
        $productionOutputs = [];
        $materialsConsumed = [];

        foreach ($shift->productionLogs as $log) {
            foreach ($log->outputs as $out) {
                $pName = $out->product->name ?? 'Producto';
                $qty = floatval($out->quantity);
                if (in_array($out->quality, ['1st', '2nd'])) {
                    $goodQuantity += $qty;
                } else if ($out->quality === 'damaged') {
                    $damagedQuantity += $qty;
                }
                
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

        $totalProduced = $goodQuantity + $damagedQuantity;
        $efficiency = $totalProduced > 0 ? ($goodQuantity / $totalProduced) * 100 : 100;

        $operatorsList = $shift->users->pluck('name')->implode(', ');
        if (empty($operatorsList)) {
            $operatorsList = $shift->user->name ?? 'Operador';
        }

        return [
            'shift' => $shift,
            'goodQuantity' => $goodQuantity,
            'damagedQuantity' => $damagedQuantity,
            'totalProduced' => $totalProduced,
            'efficiency' => $efficiency,
            'productionOutputs' => $productionOutputs,
            'materialsConsumed' => $materialsConsumed,
            'operatorsList' => $operatorsList,
            'config' => \App\Models\Configuration::first()
        ];
    }

    private function sendShiftCloseEmail($shiftId)
    {
        try {
            $config = \App\Models\Configuration::first();
            if (!$config) {
                return;
            }

            $emailRecipients = $config->soplados_email_recipients ?: [];
            $waGroups = $config->whatsapp_soplados_shift_groups ?: [];
            $waUsers = $config->whatsapp_soplados_shift_users ?: [];

            if (empty($emailRecipients) && empty($waGroups) && empty($waUsers)) {
                return;
            }

            $shift = \App\Models\Shift::with([
                'users',
                'warehouse',
                'productionLogs.outputs.product',
                'productionLogs.materials.product'
            ])->find($shiftId);

            if (!$shift) {
                return;
            }

            // Compile data
            $data = $this->compileShiftData($shift);

            // Generate PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.soplados-shift', $data);

            // Ensure directory exists
            $reportsDir = storage_path('app/public/reports/soplados');
            if (!\Illuminate\Support\Facades\File::exists($reportsDir)) {
                \Illuminate\Support\Facades\File::makeDirectory($reportsDir, 0755, true);
            }

            $fileName = 'Reporte_Turno_Soplados_' . $shift->id . '.pdf';
            $filePath = $reportsDir . '/' . $fileName;

            // Save PDF permanently
            \Illuminate\Support\Facades\File::put($filePath, $pdf->output());

            $goodQuantity = $data['goodQuantity'];
            $damagedQuantity = $data['damagedQuantity'];
            $totalProduced = $data['totalProduced'];
            $efficiency = $data['efficiency'];
            $productionOutputs = $data['productionOutputs'];
            $materialsConsumed = $data['materialsConsumed'];
            $operatorsList = $data['operatorsList'];

            $resumenProductionRows = [];
            foreach ($productionOutputs as $name => $qty) {
                $resumenProductionRows[] = "• {$name}: " . number_format($qty, 0) . " unidades";
            }
            $resumenProduction = !empty($resumenProductionRows) ? implode("\n", $resumenProductionRows) : '• Ninguno';

            $resumenMaterialsRows = [];
            foreach ($materialsConsumed as $name => $qty) {
                $resumenMaterialsRows[] = "• {$name}: " . number_format($qty, 2) . " Kg";
            }
            $resumenMaterials = !empty($resumenMaterialsRows) ? implode("\n", $resumenMaterialsRows) : '• Ninguno';

            $date = $shift->start_time ? $shift->start_time->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') : now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY');
            $user = auth()->user()->name ?? ($shift->user->name ?? 'Supervisor');
            
            $hour = now()->hour;
            $greeting = 'Buenas noches';
            if ($hour >= 5 && $hour < 12) $greeting = 'Buenos días';
            elseif ($hour >= 12 && $hour < 19) $greeting = 'Buenas tardes';

            $tipoTurno = $shift->type ?? 'Desconocido';
            $horaInicio = $shift->start_time ? $shift->start_time->format('h:i A') : 'N/A';
            $horaFin = $shift->end_time ? $shift->end_time->format('h:i A') : 'N/A';
            $almacenName = $shift->warehouse->name ?? 'Planta Soplados';
            $businessName = $config->business_name ?? 'Empresa';
            
            $subject = $config->soplados_email_subject ?: '[SALUDO], Reporte del Turno de Soplado - [FECHA] ([TIPO_TURNO]) - [EMPRESA]';
            $subject = str_replace('[FECHA]', $date, $subject);
            $subject = str_replace('[SALUDO]', $greeting, $subject);
            $subject = str_replace('[USUARIO]', $user, $subject);
            $subject = str_replace('[TIPO_TURNO]', $tipoTurno, $subject);
            $subject = str_replace('[EMPRESA]', $businessName, $subject);

            $body = $config->soplados_email_body ?: "[SALUDO],\n\nAdjunto a este correo electrónico se encuentra el reporte oficial correspondiente al cierre del turno de soplado y manufactura de botellones/envases del [FECHA].\n\nA continuación, se presenta un resumen de los resultados del turno:\n\n==================================================\n📝 DATOS GENERALES DEL TURNO\n==================================================\n• Tipo de Turno: [TIPO_TURNO]\n• Horario del Turno: [HORA_INICIO] a [HORA_FIN]\n• Planta / Almacén: [ALMACEN]\n• Operadores del Turno: [OPERADORES]\n• Empresa: [EMPRESA]\n\n==================================================\n📊 TOTALES Y RENDIMIENTO DEL TURNO\n==================================================\n• Total Producido (1ra y 2da Calidad): [BUENA_CANTIDAD] unidades\n• Unidades Defectuosas (Merma/Desecho): [DESECHADA_CANTIDAD] unidades\n• Total Procesado (Buena + Defectuosa): [TOTAL_PRODUCIDO] unidades\n• Eficiencia del Turno (Yield): [EFICIENCIA]%\n\n==================================================\n📦 DETALLE DE ENVASES SOPLADOS (1RA Y 2DA CALIDAD)\n==================================================\n[RESUMEN_PRODUCCION]\n\n==================================================\n⚙️ MATERIALES Y MATERIA PRIMA CONSUMIDA\n==================================================\n[RESUMEN_MATERIALES]\n\n==================================================\n🔍 OBSERVACIONES / EVENTUALIDADES DEL TURNO\n==================================================\n[NOTA]\n\n--------------------------------------------------\nEste es un reporte automático de manufactura de Soplados emitido por [EMPRESA].\n\nQuedamos atentos a cualquier consulta técnica o administrativa.\n\nAtentamente,\nDepartamento de Control de Calidad y Soplado\n[EMPRESA]";

            // Replacements
            $body = str_replace('[FECHA]', $date, $body);
            $body = str_replace('[SALUDO]', $greeting, $body);
            $body = str_replace('[USUARIO]', $user, $body);
            $body = str_replace('[TIPO_TURNO]', $tipoTurno, $body);
            $body = str_replace('[HORA_INICIO]', $horaInicio, $body);
            $body = str_replace('[HORA_FIN]', $horaFin, $body);
            $body = str_replace('[ALMACEN]', $almacenName, $body);
            $body = str_replace('[OPERADORES]', $operatorsList, $body);
            $body = str_replace('[BUENA_CANTIDAD]', number_format($goodQuantity, 0), $body);
            $body = str_replace('[DESECHADA_CANTIDAD]', number_format($damagedQuantity, 0), $body);
            $body = str_replace('[TOTAL_PRODUCIDO]', number_format($totalProduced, 0), $body);
            $body = str_replace('[EFICIENCIA]', number_format($efficiency, 2), $body);
            $body = str_replace('[RESUMEN_PRODUCCION]', $resumenProduction, $body);
            $body = str_replace('[RESUMEN_MATERIALES]', $resumenMaterials, $body);
            $body = str_replace('[NOTA]', $shift->notes ?? 'Sin observaciones', $body);
            $body = str_replace('[EMPRESA]', $businessName, $body);

            $bodyHtml = nl2br($body);

            // Send Email
            if (!empty($emailRecipients)) {
                \Illuminate\Support\Facades\Mail::to($emailRecipients)
                    ->queue(new \App\Mail\SopladosShiftReportMail($subject, $bodyHtml, $filePath));
            }

            // Send WhatsApp
            try {
                $whatsappService = app(\App\Services\WhatsappService::class);
                if ($whatsappService->checkStatus()) {
                    $companyName = strtoupper($config->business_name ?: 'SISTEMA');
                    $waMessage = "📄 *REPORTE DE CIERRE DE TURNO - SOPLADOS*\n" .
                                 "🏢 *{$companyName}*\n" .
                                 "📅 *{$date}*\n" .
                                 "-----------------------------------\n" .
                                 "• *Turno:* {$tipoTurno}\n" .
                                 "• *Horario:* {$horaInicio} a {$horaFin}\n" .
                                 "• *Operadores:* {$operatorsList}\n" .
                                 "• *Rendimiento (Yield):* " . number_format($efficiency, 2) . "%\n" .
                                 "• *Prod. Buena:* " . number_format($goodQuantity, 0) . " unds\n" .
                                 "• *Merma:* " . number_format($damagedQuantity, 0) . " unds\n" .
                                 "-----------------------------------\n" .
                                 "Adjunto encontrarás el reporte oficial en PDF.";

                    // Send to Groups
                    if (!empty($waGroups)) {
                        foreach ($waGroups as $groupId) {
                            $whatsappService->sendMessage($groupId, $waMessage, $filePath);
                        }
                    }

                    // Send to specific Users
                    if (!empty($waUsers)) {
                        $users = \App\Models\User::whereIn('id', $waUsers)->whereNotNull('phone')->get();
                        foreach ($users as $u) {
                            $whatsappService->sendMessage($u->phone, $waMessage, $filePath);
                        }
                    }
                }
            } catch (\Exception $waEx) {
                \Illuminate\Support\Facades\Log::error("Failed to send Soplados shift report to WhatsApp: " . $waEx->getMessage());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send Soplados shift report email: " . $e->getMessage());
        }
    }
}
