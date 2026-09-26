<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\LicenseService;

class CloudSyncBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cloud-sync {--force : Forzar subida aunque el archivo ya haya sido subido}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza y sube el último respaldo local hacia el servidor central de licencias en la nube (licencias.jhonnypirela.dev)';

    /**
     * Execute the console command.
     */
    public function handle(LicenseService $licenseService)
    {
        $this->info('Verificando respaldos locales para sincronización en la nube...');

        $disk = Storage::disk('backup');
        $files = collect($disk->allFiles())
            ->filter(fn ($f) => str_ends_with(strtolower($f), '.zip'))
            ->sortByDesc(fn ($f) => $disk->lastModified($f));

        if ($files->isEmpty()) {
            $this->warn('No se encontraron archivos de respaldo (.zip) en el almacenamiento local.');
            return 0;
        }

        $latestFile = $files->first();
        $filePath = $disk->path($latestFile);
        $fileName = basename($latestFile);
        $fileSize = $disk->size($latestFile);

        $clientId = $licenseService->getClientId();
        $serverIp = $licenseService->getLicenseServerIp();

        $this->info("Último respaldo detectado: {$fileName} (" . round($fileSize / 1024 / 1024, 2) . " MB)");
        $this->info("Destino en la nube: {$serverIp}");

        $protocol = (str_contains($serverIp, '.dev') || str_contains($serverIp, '.com')) ? 'https://' : 'http://';
        $url = "{$protocol}{$serverIp}/api/clients/backup";

        try {
            $response = Http::timeout(120)
                ->attach('backup_file', file_get_contents($filePath), $fileName)
                ->post($url, [
                    'client_id' => $clientId,
                    'client_system_id' => $clientId,
                    'filename' => $fileName,
                    'file_size' => $fileSize,
                ]);

            if ($response->successful()) {
                $this->info("✅ [OK] Respaldo sincronizado exitosamente con el servidor central ({$serverIp}).");
                Log::info("Backup cloud-sync: {$fileName} subido exitosamente a {$url}");
                return 0;
            }

            $msg = $response->body();
            $this->warn("⚠️ El servidor de licencias respondió con estado {$response->status()}: " . substr(strip_tags($msg), 0, 150));
            Log::warning("Backup cloud-sync warning: Servidor respondió {$response->status()} - {$msg}");
            return 0;
        } catch (\Throwable $e) {
            $this->warn("⚠️ No se pudo conectar con el servidor en la nube: " . $e->getMessage());
            Log::warning("Backup cloud-sync connection error: " . $e->getMessage());
            return 0;
        }
    }
}
