<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class Backups extends Component
{
    public $backups = [];
    public $selectedPath;

    public function mount()
    {
        $this->loadBackups();
    }

    public function loadBackups()
    {
        // Use the 'backup' disk configured in filesystems.php
        // The folder name is usually the app name (slugified) or defined in backup.php
        $backupName = config('backup.backup.name');
        $disk = Storage::disk('backup');
        
        if (!$disk->exists($backupName)) {
            $this->backups = [];
            return;
        }

        $files = $disk->files($backupName);
        
        $this->backups = collect($files)->map(function ($file) use ($disk) {
            return [
                'path' => str_replace('\\', '/', $file),
                'name' => basename($file),
                'size' => $this->formatSize($disk->size($file)),
                'date' => Carbon::createFromTimestamp($disk->lastModified($file))->format('Y-m-d H:i:s'),
                'timestamp' => $disk->lastModified($file)
            ];
        })->sortByDesc('timestamp')->values()->all();
    }

    public function create($option = 'only-db')
    {
        try {
            $flags = [];
            if ($option === 'only-db') {
                $flags['--only-db'] = true;
            } elseif ($option === 'only-files') {
                $flags['--only-files'] = true;
            }
            // 'full' implies no flags (both)

            Artisan::call('backup:run', $flags);
            
            $this->loadBackups();
            $this->dispatch('noty', msg: 'Copia de seguridad creada exitosamente');
        } catch (\Exception $e) {
            $this->dispatch('noty', msg: 'Error al crear copia: ' . $e->getMessage(), type: 'error');
        }
    }

    #[\Livewire\Attributes\On('restore-backup')]
    public function restore($path)
    {
        $disk = Storage::disk('backup');
        if (!$disk->exists($path)) {
            $this->dispatch('noty', msg: 'El archivo de copia de seguridad no existe.', type: 'error');
            return;
        }

        try {
            // 1. Unzip to temporary folder
            $tempPath = storage_path('app/backup-temp/' . uniqid());
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0777, true);
            }

            $zip = new \ZipArchive;
            if ($zip->open($disk->path($path)) === TRUE) {
                $zip->extractTo($tempPath);
                $zip->close();
            } else {
                throw new \Exception("No se pudo abrir el archivo ZIP.");
            }

            // 2. Find Database Dump
            // Spatie backup usually puts dumps in 'db-dumps' folder inside the zip
            $dumpFile = glob($tempPath . '/db-dumps/*.sql');
            
            if (empty($dumpFile)) {
                // Try searching recursively if structure is different
                $dumpFile = glob($tempPath . '/**/*.sql');
            }

            if (empty($dumpFile)) {
                throw new \Exception("No se encontró un archivo SQL en la copia de seguridad.");
            }

            $sqlFile = $dumpFile[0];

            // 3. Restore Database
            // We use DB::unprepared to run raw SQL. 
            // WARNING: This might fail for very large dumps due to memory/timeout.
            // For larger dumps, using mysql command line is better but requires shell access/config.
            
            \Illuminate\Support\Facades\DB::disableQueryLog();
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            $sql = file_get_contents($sqlFile);
            \Illuminate\Support\Facades\DB::unprepared($sql);
            
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // 4. Cleanup
            $this->deleteDirectory($tempPath);

            $this->dispatch('noty', msg: 'Base de datos restaurada exitosamente.');
            
        } catch (\Exception $e) {
            $this->dispatch('noty', msg: 'Error al restaurar: ' . $e->getMessage(), type: 'error');
        }
    }

    private function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }

    public function deleteBackup()
    {
        $path = $this->selectedPath;
        \Illuminate\Support\Facades\Log::info('Attempting to delete backup via hidden button: ' . $path);
        
        if (Storage::disk('backup')->exists($path)) {
            Storage::disk('backup')->delete($path);
            $this->loadBackups();
            $this->dispatch('noty', msg: 'Copia de seguridad eliminada');
            \Illuminate\Support\Facades\Log::info('Backup deleted successfully.');
        } else {
            \Illuminate\Support\Facades\Log::error('Backup file not found: ' . $path);
            $this->dispatch('noty', msg: 'Archivo no encontrado: ' . $path, type: 'error');
        }
    }

    #[\Livewire\Attributes\On('delete-backup')]
    public function delete($path)
    {
        \Illuminate\Support\Facades\Log::info('Attempting to delete backup: ' . $path);
        
        if (Storage::disk('backup')->exists($path)) {
            Storage::disk('backup')->delete($path);
            $this->loadBackups();
            $this->dispatch('noty', msg: 'Copia de seguridad eliminada');
            \Illuminate\Support\Facades\Log::info('Backup deleted successfully.');
        } else {
            \Illuminate\Support\Facades\Log::error('Backup file not found: ' . $path);
            $this->dispatch('noty', msg: 'Archivo no encontrado: ' . $path, type: 'error');
        }
    }

    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function render()
    {
        return view('livewire.settings.backups')
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
