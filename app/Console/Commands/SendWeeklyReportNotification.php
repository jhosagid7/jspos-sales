<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Configuration;
use App\Services\WhatsappService;
use App\Livewire\Reports\WeeklyIncomeReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class SendWeeklyReportNotification extends Command
{
    protected $signature = 'app:send-weekly-report {date?}';
    protected $description = 'Generate weekly income PDF and send it via WhatsApp';

    public function handle()
    {
        $date = $this->argument('date') ?: Carbon::today()->toDateString();
        
        $config = Configuration::first();
        if (!$config) {
            $this->error('No configuration found.');
            return 1;
        }

        // Initialize Livewire component to get report data
        $reportComponent = new WeeklyIncomeReport();
        $reportComponent->mount();
        $reportComponent->selectedDate = $date;
        $reportComponent->updatedSelectedDate();

        $data = $reportComponent->getReportData();
        
        // Add additional variables for the PDF view
        $data['weekLabel'] = $reportComponent->weekLabel;
        $data['config'] = $config;
        $data['user'] = \App\Models\User::first() ?: (object) ['name' => 'SISTEMA'];
        $data['dateGenerated'] = Carbon::now()->format('d/m/Y H:i');

        // Generate PDF
        $pdf = Pdf::loadView('reports.weekly-income-report-pdf', $data)
            ->setPaper('a4', 'landscape');

        // Ensure temp directory exists
        $tempDir = storage_path('app/temp');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $mondayDate = $reportComponent->mondayDate;
        $fileName = 'Reporte_Ingresos_Semanal_' . $mondayDate . '.pdf';
        $filePath = $tempDir . '/' . $fileName;

        // Save PDF to temp file
        File::put($filePath, $pdf->output());

        $companyName = strtoupper($config->business_name ?: 'SISTEMA');
        $weekLabel = $reportComponent->weekLabel;

        $waMessage = "📄 *REPORTE SEMANAL DE INGRESOS*\n" .
                     "🏢 *{$companyName}*\n" .
                     "📅 *{$weekLabel}*\n" .
                     "-----------------------------------\n" .
                     "Adjunto encontrarás el reporte detallado en PDF correspondiente a la semana.";

        // Send via WhatsApp
        try {
            $whatsappService = app(WhatsappService::class);
            if ($whatsappService->checkStatus()) {
                $selectedGroups = $config->whatsapp_weekly_report_groups ?: [];
                if (empty($selectedGroups)) {
                    $whatsappService->sendToGroupByName('Diferencial', $waMessage, $filePath);
                } else {
                    foreach ($selectedGroups as $groupId) {
                        $whatsappService->sendMessage($groupId, $waMessage, $filePath);
                    }
                }
                $this->info("Weekly report PDF sent successfully.");
            } else {
                $this->warn("WhatsApp client is offline. Skipping send.");
            }
        } catch (\Exception $e) {
            $this->error("Error sending WhatsApp PDF notification: " . $e->getMessage());
        } finally {
            // Clean up temp file
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        return 0;
    }
}
