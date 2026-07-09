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

        $shifts = \App\Models\Shift::with([
            'users',
            'warehouse',
            'productionLogs.outputs.product',
            'productionLogs.materials.product'
        ])->whereBetween('start_time', [$start, $end])
          ->where('status', 'closed')
          ->get();

        $totalGood = 0;
        $totalDamaged = 0;
        $productionOutputs = [];
        $materialsConsumed = [];

        foreach ($shifts as $shift) {
            foreach ($shift->productionLogs as $log) {
                foreach ($log->outputs as $out) {
                    $pName = $out->product->name ?? 'Producto';
                    $qty = floatval($out->quantity);
                    if (in_array($out->quality, ['1st', '2nd'])) {
                        $totalGood += $qty;
                    } else if ($out->quality === 'damaged') {
                        $totalDamaged += $qty;
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
        }

        $totalWeekProduced = $totalGood + $totalDamaged;
        $weekEfficiency = $totalWeekProduced > 0 ? ($totalGood / $totalWeekProduced) * 100 : 100;

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

                \Illuminate\Support\Facades\Mail::to($emailRecipients)->queue(new \App\Mail\GenericNotificationMail(
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
