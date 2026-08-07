<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Services\UpdateService;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\File;

class UpdateSystem extends Component
{
    use WithFileUploads;

    public $currentVersion;
    public $newVersion;
    public $hasUpdate = false;
    public $updateUrl;
    public $releaseBody;
    public $currentReleaseNotes;
    public $status = ''; // idle, checking, backing_up, downloading, updating, done, error
    public $progress = 0;
    public $progressStatus = '';
    public $rollbacks = [];
    public $selectedBackupFolder = '';
    public $logLines = '';
    public $manualZip;

    public function mount(UpdateService $updater)
    {
        $this->currentVersion = $updater->getCurrentVersion();
        $this->rollbacks = $updater->getAvailableRollbacks();
        try {
            $this->currentReleaseNotes = $this->getReleaseNotes($this->currentVersion);
        } catch (\Exception $e) {
            $this->currentReleaseNotes = "Error al cargar las notas de la versión: " . $e->getMessage();
        }
    }

    public function getReleaseNotes($version)
    {
        $path = base_path('CHANGELOG.md');
        if (!file_exists($path)) return '';

        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $notes = [];
        $capturing = false;

        // Normalize version for search (e.g., v1.3.0 -> [1.3.0])
        $searchVersion = str_replace('v', '', $version);
        $startPattern = "/^## \[" . preg_quote($searchVersion, '/') . "\]/";

        foreach ($lines as $line) {
            // Start capturing when we find the version header
            if (preg_match($startPattern, $line)) {
                $capturing = true;
                continue;
            }

            // Stop capturing when we hit the next version header
            if ($capturing && preg_match('/^## \[/', $line)) {
                break;
            }

            if ($capturing) {
                $notes[] = $line;
            }
        }

        return trim(implode("\n", $notes));
    }

    public function checkUpdate(UpdateService $updater)
    {
        $this->status = 'checking';
        
        $result = $updater->checkUpdate();
        
        if (!empty($result['has_update'])) {
            $this->hasUpdate = true;
            $this->newVersion = $result['new_version'];
            $this->updateUrl = $result['url'];
            $this->releaseBody = $result['body'];
            $this->status = 'available';
        } else {
            $this->hasUpdate = false;
            if (!empty($result['error'])) {
                $this->status = 'error';
                $this->addError('update', $result['error']);
            } else {
                $this->status = 'up_to_date';
            }
        }
    }

    public function processManualZip(UpdateService $updater)
    {
        $this->validate([
            'manualZip' => 'required|file|max:204800', // max 200MB
        ], [
            'manualZip.required' => 'Debe seleccionar un archivo ZIP de actualización.',
            'manualZip.file' => 'El archivo cargado no es válido.',
            'manualZip.max' => 'El archivo comprimido supera el tamaño máximo permitido (200 MB).',
        ]);

        $extension = strtolower($this->manualZip->getClientOriginalExtension());
        if ($extension !== 'zip') {
            $this->addError('manualZip', 'El archivo debe tener extensión .zip');
            return;
        }

        $this->status = 'updating';
        $this->progress = 10;
        $this->progressStatus = 'Procesando archivo ZIP cargado manualmente...';

        try {
            $tempPath = storage_path('app/temp_update.zip');
            File::copy($this->manualZip->getRealPath(), $tempPath);
            session(['latest_downloaded_update_zip' => $tempPath]);

            $updater->createRollbackBackup($this->currentVersion);
            $this->progress = 40;
            $this->progressStatus = 'Instalando actualización...';

            $updater->installUpdate();
            $this->progress = 80;
            $this->progressStatus = 'Ejecutando migraciones y limpiando...';

            $updater->runMigrations();
            $updater->cleanup();

            $this->finish();
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function startUpdate()
    {
        if (!$this->hasUpdate) return;
        
        $this->status = 'updating';
        $this->progress = 0;
        $this->progressStatus = 'Iniciando actualización...';
        
        // Step 1: Backup
        $this->dispatch('run-backup');
    }

    public function runBackup(UpdateService $updater)
    {
        $this->progressStatus = 'Creando copia de seguridad...';
        $this->progress = 10;
        
        try {
            $updater->createRollbackBackup($this->currentVersion);
            $this->rollbacks = $updater->getAvailableRollbacks(); // Reload rollbacks list
            $this->dispatch('run-download');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function download(UpdateService $updater)
    {
        $this->progressStatus = 'Descargando archivos...';
        $this->progress = 30;

        try {
            $updater->downloadUpdate($this->updateUrl);
            $this->dispatch('run-install');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function install(UpdateService $updater)
    {
        $this->progressStatus = 'Descomprimiendo e instalando...';
        $this->progress = 60;

        try {
            $updater->installUpdate($this->newVersion);
            $this->dispatch('run-migrate');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function migrate(UpdateService $updater)
    {
        $this->progressStatus = 'Actualizando base de datos...';
        $this->progress = 80;

        try {
            $updater->runMigrations();
            $this->dispatch('run-cleanup');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function cleanup(UpdateService $updater)
    {
        $this->progressStatus = 'Limpiando archivos temporales...';
        $this->progress = 90;

        try {
            $updater->cleanup();
            $updater->sendUpdateNotificationEmail($this->newVersion, $this->currentVersion);
            $this->finish();
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function finish()
    {
        $this->progress = 100;
        $this->progressStatus = '¡Actualización completada!';
        $this->status = 'done';
        
        $this->dispatch('noty', msg: 'Sistema actualizado correctamente a la versión ' . $this->newVersion);
        
        // Reload after a short delay
        $this->dispatch('reload-page');
    }

    public function rollbackToVersion($backupFolder)
    {
        $this->status = 'updating';
        $this->progress = 30;
        $this->progressStatus = 'Restaurando código fuente y base de datos...';
        $this->selectedBackupFolder = $backupFolder;

        $this->dispatch('run-rollback');
    }

    public function runRollback(UpdateService $updater)
    {
        try {
            $updater->restoreFromBackup($this->selectedBackupFolder);
            $this->progress = 100;
            $this->progressStatus = '¡Restauración completada!';
            $this->status = 'done';
            
            $this->dispatch('noty', msg: 'Sistema restaurado correctamente a la versión anterior.');
            $this->dispatch('reload-page');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function deleteRollback($backupFolder, UpdateService $updater)
    {
        try {
            $updater->deleteRollbackBackup($backupFolder);
            $this->rollbacks = $updater->getAvailableRollbacks();
            $this->dispatch('noty', msg: 'Copia de seguridad eliminada con éxito.');
        } catch (\Exception $e) {
            $this->dispatch('msg-error', msg: 'Error al eliminar copia: ' . $e->getMessage());
        }
    }

    protected function handleError(\Exception $e)
    {
        $this->status = 'error';
        $this->progressStatus = 'Error: ' . $e->getMessage();
        $this->addError('update', $e->getMessage());
    }

    public function loadLogs(UpdateService $updater)
    {
        $this->logLines = $updater->getLatestLogLines(150);
    }

    public function clearLogs(UpdateService $updater)
    {
        $updater->clearLog();
        $this->loadLogs($updater);
        $this->dispatch('noty', msg: 'Registro de errores (laravel.log) vaciado con éxito.');
    }



    public function render()
    {
        return view('livewire.settings.update-system')
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
