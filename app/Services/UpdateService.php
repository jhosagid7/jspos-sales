<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class UpdateService
{
    protected $owner;
    protected $repo;
    protected $currentVersion;

    public function __construct()
    {
        $this->owner = env('GITHUB_REPO_OWNER', 'jhosagid7');
        $this->repo = env('GITHUB_REPO_NAME', 'jspos-sales');
        // Get current version from CHANGELOG or config. For now assuming config or hardcoded for dev.
        // Ideally we parse CHANGELOG.md or have a version file.
        // Let's assume the user will define APP_VERSION in .env or we parse it.
        // For now, we'll fetch the latest tag from git if available, or use a fallback.
    }

    public function getCurrentVersion()
    {
        $path = base_path('version.txt');
        if (File::exists($path)) {
            return trim(File::get($path));
        }
        return 'v1.0.0';
    }

    public function checkUpdate()
    {
        $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/releases/latest";
        
        try {
            $response = Http::get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                $latestVersion = $data['tag_name'];
                $currentVersion = $this->getCurrentVersion();

                // Simple string comparison or version_compare
                // Remove 'v' prefix if present for comparison
                $v1 = ltrim($latestVersion, 'v');
                $v2 = ltrim($currentVersion, 'v');

                if (version_compare($v1, $v2, '>')) {
                    return [
                        'new_version' => $latestVersion,
                        'current_version' => $currentVersion,
                        'url' => $data['zipball_url'], // or assets
                        'body' => $data['body'],
                        'has_update' => true
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error("Update check failed: " . $e->getMessage());
        }

        return [
            'has_update' => false,
            'current_version' => $this->getCurrentVersion()
        ];
    }

    public function runBackup()
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true]);
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Backup failed: " . $e->getMessage());
        }
    }

    public function downloadUpdate($downloadUrl)
    {
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'temp_update_' . uniqid() . '.zip';
        $maxAttempts = 5; // Increased from 2 to 5 for production stability
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                Log::info("Update download attempt {$attempt} of {$maxAttempts}: {$downloadUrl}");
                
                $response = Http::withHeaders([
                    'User-Agent' => 'JSPOS-Updater'
                ])
                ->withOptions([
                    'curl' => [
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // Force HTTP/1.1 to avoid common SSL/TLS EOF errors in HTTP/2
                        CURLOPT_SSL_VERIFYPEER => false,               // Temporary fix for SSL certificate issues if any
                        CURLOPT_TCP_KEEPALIVE => 1,                   // Help keep connection alive
                    ]
                ])
                ->timeout(900)          // Increased to 15 minutes
                ->connectTimeout(60)    // Increased to 60 seconds
                ->sink($tempPath)
                ->get($downloadUrl);

                if (!$response->successful()) {
                    throw new \Exception("Download failed with status: " . $response->status());
                }

                Log::info("Update downloaded successfully on attempt {$attempt}.");
                
                // Store path in session for the install phase
                session(['latest_downloaded_update_zip' => $tempPath]);
                
                return true;

            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning("Download attempt {$attempt} failed: " . $e->getMessage());
                
                // Clean partial download before retry
                if (File::exists($tempPath)) {
                    File::delete($tempPath);
                }

                if ($attempt < $maxAttempts) {
                    // Exponential backoff
                    $sleepTime = $attempt * 5; 
                    sleep($sleepTime); 
                }
            }
        }

        throw new \Exception("Download failed after {$maxAttempts} attempts: " . $lastException->getMessage());
    }

    public function installUpdate($newVersion = null)
    {
        $tempPath = session('latest_downloaded_update_zip');
        if (!$tempPath || !File::exists($tempPath)) {
            // Fallback to legacy path
            $tempPath = storage_path('app/temp_update.zip');
        }
        
        if (!File::exists($tempPath)) {
            throw new \Exception("No se encontró el archivo temporal de actualización (" . basename($tempPath) . ").");
        }
        
        $zip = new ZipArchive;
        
        if ($zip->open($tempPath) === TRUE) {
            $extractPath = storage_path('app/temp_extract');
            File::makeDirectory($extractPath, 0755, true, true);
            
            $zip->extractTo($extractPath);
            $zip->close();

            $files = File::directories($extractPath);
            if (count($files) > 0) {
                $source = $files[0];
                File::copyDirectory($source, base_path());
            }

            // Explicitly update version.txt
            if ($newVersion) {
                File::put(base_path('version.txt'), $newVersion);
            }

            File::deleteDirectory($extractPath);
            File::delete($tempPath);
            
            // Clean session
            session()->forget('latest_downloaded_update_zip');
            
            return true;
        } else {
            throw new \Exception("Failed to unzip update.");
        }
    }

    public function runMigrations()
    {
        Artisan::call('migrate', ['--force' => true]);
        // Also run permission seeder to ensure new features are accessible
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);

        // Fix: Manual repair for warehouse_id in order_details if they are null
        \App\Models\OrderDetail::whereNull('warehouse_id')->update(['warehouse_id' => 1]);

        // Fix: Create personal_access_tokens table if it doesn't exist (Required for Sanctum API)
        if (!\Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
            \Illuminate\Support\Facades\Schema::create('personal_access_tokens', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // Fix: Add sent_at to email_messages if missing (seen in logs)
        if (\Illuminate\Support\Facades\Schema::hasTable('email_messages')) {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('email_messages', 'sent_at')) {
                \Illuminate\Support\Facades\Schema::table('email_messages', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->timestamp('sent_at')->nullable()->after('status');
                });
            }
        }

        return true;
    }

    public function cleanup()
    {
        Artisan::call('optimize:clear');
        \Illuminate\Support\Facades\Cache::forget('system_update_available');
        return true;
    }

    /**
     * Genera una copia de seguridad (rollback point) de los archivos y base de datos.
     */
    public function createRollbackBackup($currentVersion, array $directories = null, array $files = null)
    {
        $backupDir = storage_path('backups/antes_de_v' . $currentVersion);
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true, true);
        }

        // 1. Respaldar base de datos
        $dbBackupPath = $backupDir . '/database_backup.sql';
        $this->exportDatabaseSql($dbBackupPath);

        // 2. Respaldar archivos
        $filesBackupPath = $backupDir . '/files_backup.zip';
        $directories = $directories ?? ['app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'lang'];
        $files = $files ?? ['composer.json', 'composer.lock', 'version.txt', 'artisan'];
        
        $this->zipDirectories($directories, $files, $filesBackupPath);

        // 3. Limitar automáticamente a los últimos 3 backups para evitar llenar disco
        $this->pruneOldBackups();

        return true;
    }

    /**
     * Prunea backups viejos manteniendo solo los 3 más recientes.
     */
    protected function pruneOldBackups()
    {
        $available = $this->getAvailableRollbacks();
        if (count($available) > 3) {
            // Sort by date ascending (oldest first)
            usort($available, function($a, $b) {
                return $a['timestamp'] <=> $b['timestamp'];
            });

            // Delete oldest until we have 3
            while (count($available) > 3) {
                $oldest = array_shift($available);
                $this->deleteRollbackBackup($oldest['folder']);
            }
        }
    }

    /**
     * Exporta la base de datos de forma nativa en PHP para máxima compatibilidad.
     */
    public function exportDatabaseSql($filePath)
    {
        $tablesResult = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
        if (empty($tablesResult)) {
            throw new \Exception("No tables found in database.");
        }
        
        $keys = array_keys((array)$tablesResult[0]);
        $dbNameKey = $keys[0];

        $tables = [];
        foreach ($tablesResult as $row) {
            $tables[] = $row->{$dbNameKey};
        }

        $sql = "-- JSPOS Database Backup\n";
        $sql .= "-- Generated: " . now()->toIso8601String() . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Structure
            $createTable = \Illuminate\Support\Facades\DB::select("SHOW CREATE TABLE `{$table}`");
            $createTableSql = (array)$createTable[0];
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createTableSql['Create Table'] . ";\n\n";

            // Data using offset pagination for memory efficiency
            $count = \Illuminate\Support\Facades\DB::table($table)->count();
            $chunkSize = 1000;

            for ($offset = 0; $offset < $count; $offset += $chunkSize) {
                $rows = \Illuminate\Support\Facades\DB::table($table)->offset($offset)->limit($chunkSize)->get();
                foreach ($rows as $row) {
                    $rowArray = (array)$row;
                    $keysList = array_map(function($k) { return "`{$k}`"; }, array_keys($rowArray));
                    $valuesList = array_map(function($value) {
                        if ($value === null) return "NULL";
                        return "'" . addslashes($value) . "'";
                    }, array_values($rowArray));

                    $sql .= "INSERT INTO `{$table}` (" . implode(', ', $keysList) . ") VALUES (" . implode(', ', $valuesList) . ");\n";
                }
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        File::put($filePath, $sql);
    }

    /**
     * Comprime recursivamente directorios y archivos específicos.
     */
    public function zipDirectories(array $directories, array $files, $outZipPath)
    {
        $zip = new ZipArchive();
        if ($zip->open($outZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("No se pudo crear el archivo ZIP para rollback.");
        }

        foreach ($directories as $dir) {
            $realPath = base_path($dir);
            if (!File::exists($realPath)) continue;

            $filesInDir = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($realPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($filesInDir as $file) {
                $filePath = $file->getRealPath();
                if ($filePath === false) {
                    continue;
                }

                // Skip directories (including junction points) and symbolic links
                if (is_dir($filePath) || is_link($filePath) || $file->isLink()) {
                    continue;
                }

                if (!$file->isDir()) {
                    // Exclude storage symlinks in public or cache folders
                    if (str_contains($filePath, 'public\\storage') || str_contains($filePath, 'public/storage') || str_contains($filePath, 'bootstrap\\cache') || str_contains($filePath, 'bootstrap/cache')) {
                        continue;
                    }
                    
                    $relativePath = $dir . '/' . substr($filePath, strlen($realPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }

        foreach ($files as $file) {
            $realPath = base_path($file);
            if (File::exists($realPath)) {
                $zip->addFile($realPath, $file);
            }
        }

        $zip->close();
    }

    /**
     * Retorna la lista de rollbacks disponibles.
     */
    public function getAvailableRollbacks()
    {
        $backupsDir = storage_path('backups');
        if (!File::exists($backupsDir)) {
            return [];
        }

        $directories = File::directories($backupsDir);
        $rollbacks = [];

        foreach ($directories as $dir) {
            $folderName = basename($dir);
            if (str_starts_with($folderName, 'antes_de_v')) {
                $version = str_replace('antes_de_v', '', $folderName);
                
                $sqlPath = $dir . '/database_backup.sql';
                $zipPath = $dir . '/files_backup.zip';
                
                if (File::exists($sqlPath) && File::exists($zipPath)) {
                    $timestamp = File::lastModified($dir);
                    $date = date('Y-m-d H:i:s', $timestamp);
                    $size = File::size($sqlPath) + File::size($zipPath);
                    $sizeFormatted = number_format($size / (1024 * 1024), 2) . ' MB';

                    $rollbacks[] = [
                        'folder' => $folderName,
                        'version' => $version,
                        'date' => $date,
                        'timestamp' => $timestamp,
                        'size' => $sizeFormatted,
                    ];
                }
            }
        }

        return $rollbacks;
    }

    /**
     * Elimina una carpeta de rollback.
     */
    public function deleteRollbackBackup($backupFolder)
    {
        $backupDir = storage_path('backups/' . $backupFolder);
        if (File::exists($backupDir)) {
            File::deleteDirectory($backupDir);
            return true;
        }
        return false;
    }

    /**
     * Realiza la restauración a partir de un punto de rollback.
     */
    public function restoreFromBackup($backupFolder)
    {
        $backupDir = storage_path('backups/' . $backupFolder);
        if (!File::exists($backupDir)) {
            throw new \Exception("Directorio de restauración no encontrado.");
        }

        $zipPath = $backupDir . '/files_backup.zip';
        $sqlPath = $backupDir . '/database_backup.sql';

        if (!File::exists($zipPath) || !File::exists($sqlPath)) {
            throw new \Exception("Archivos de copia de seguridad incompletos en el punto de restauración.");
        }

        // 1. Restaurar archivos
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo(base_path());
            $zip->close();
        } else {
            throw new \Exception("No se pudo descompilar el ZIP de archivos.");
        }

        // 2. Restaurar base de datos
        $this->importDatabaseSql($sqlPath);

        // 3. Limpiar caches
        $this->cleanup();

        return true;
    }

    /**
     * Importa un archivo SQL ejecutando las sentencias individualmente.
     *
     * Nota: DB::unprepared() con un dump multi-sentencia puede fallar en algunos
     * drivers MySQL porque no todos soportan multi-statement por defecto.
     * Dividimos el SQL en sentencias individuales y las ejecutamos una a una
     * para mayor compatibilidad y control de errores.
     */
    public function importDatabaseSql($filePath)
    {
        if (!File::exists($filePath)) {
            throw new \Exception("Archivo SQL no encontrado.");
        }

        $sql = File::get($filePath);

        // Primero desactivar FKs explícitamente (independiente del contenido del dump)
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Dividir en sentencias individuales por punto y coma al final de línea
        // Ignorar comentarios (líneas que empiezan con --)
        $lines = explode("\n", $sql);
        $currentStatement = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Ignorar líneas de comentario
            if (str_starts_with($trimmed, '--') || $trimmed === '') {
                continue;
            }

            $currentStatement .= $line . "\n";

            // Cuando la línea termina en ; es el fin de la sentencia
            if (str_ends_with($trimmed, ';')) {
                $stmt = trim($currentStatement);
                if (!empty($stmt) && $stmt !== ';') {
                    // Omitir las sentencias SET FOREIGN_KEY_CHECKS ya que las manejamos manualmente
                    if (!preg_match('/^SET\s+FOREIGN_KEY_CHECKS\s*=/i', $stmt)) {
                        \Illuminate\Support\Facades\DB::unprepared($stmt);
                    }
                }
                $currentStatement = '';
            }
        }

        // Ejecutar cualquier sentencia restante sin punto y coma
        $remaining = trim($currentStatement);
        if (!empty($remaining)) {
            \Illuminate\Support\Facades\DB::unprepared($remaining);
        }

        // Reactivar claves foráneas
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
