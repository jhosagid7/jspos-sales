<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Services\LicenseService;

class CloudSyncBackupTest extends TestCase
{
    public function test_cloud_sync_backup_with_no_files_handles_gracefully()
    {
        Storage::fake('backup');

        $this->artisan('backup:cloud-sync')
            ->expectsOutput('Verificando respaldos locales para sincronización en la nube...')
            ->assertExitCode(0);
    }

    public function test_cloud_sync_backup_successfully_uploads_latest_backup()
    {
        Storage::fake('backup');
        Storage::disk('backup')->put('laravel-backup/2026-09-26-test-backup.zip', 'dummy zip binary content');

        Http::fake([
            'https://licencias.jhonnypirela.dev/api/clients/backup' => Http::response([
                'status' => 'success',
                'message' => 'Respaldo recibido correctamente'
            ], 200),
        ]);

        $this->artisan('backup:cloud-sync')
            ->expectsOutput('Verificando respaldos locales para sincronización en la nube...')
            ->assertExitCode(0);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/clients/backup') &&
                   $request->hasFile('backup_file');
        });
    }

    public function test_cloud_sync_backup_handles_network_error_gracefully()
    {
        Storage::fake('backup');
        Storage::disk('backup')->put('laravel-backup/2026-09-26-test-backup.zip', 'dummy zip binary content');

        Http::fake([
            'https://licencias.jhonnypirela.dev/api/clients/backup' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $this->artisan('backup:cloud-sync')
            ->assertExitCode(0);
    }
}
