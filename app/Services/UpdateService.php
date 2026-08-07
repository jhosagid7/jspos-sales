<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        $currentVersion = $this->getCurrentVersion();
        $v2 = ltrim($currentVersion, 'v');
        $errorMessage = null;

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'JSPOS-Updater'
            ])->timeout(25)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                $latestVersion = $data['tag_name'] ?? '';
                $v1 = ltrim($latestVersion, 'v');

                if (version_compare($v1, $v2, '>')) {
                    return [
                        'new_version' => 'v' . $v1,
                        'current_version' => $currentVersion,
                        'url' => $data['zipball_url'] ?? "https://github.com/{$this->owner}/{$this->repo}/archive/refs/tags/{$latestVersion}.zip",
                        'body' => $data['body'] ?? '',
                        'has_update' => true
                    ];
                }
                
                return [
                    'has_update' => false,
                    'current_version' => $currentVersion
                ];
            } else {
                Log::warning("GitHub releases check returned status {$response->status()}: " . $response->body());
                $errorMessage = "Respuesta de GitHub (" . $response->status() . "): " . ($response->status() === 403 ? 'Límite de consultas excedido en esta red IP' : 'No se pudo consultar el servidor de versiones');
            }
        } catch (\Exception $e) {
            Log::error("Update check via releases failed: " . $e->getMessage());
            $errorMessage = "Error de conexión: " . $e->getMessage();
        }

        // Fallback: Check tags endpoint if releases endpoint was rate-limited or failed
        try {
            $tagsUrl = "https://api.github.com/repos/{$this->owner}/{$this->repo}/tags";
            $tagsResponse = Http::withHeaders([
                'User-Agent' => 'JSPOS-Updater'
            ])->timeout(25)->get($tagsUrl);

            if ($tagsResponse->successful()) {
                $tags = $tagsResponse->json();
                if (is_array($tags) && count($tags) > 0) {
                    $latestTag = $tags[0]['name'] ?? '';
                    $v1 = ltrim($latestTag, 'v');

                    if (version_compare($v1, $v2, '>')) {
                        return [
                            'new_version' => 'v' . $v1,
                            'current_version' => $currentVersion,
                            'url' => "https://github.com/{$this->owner}/{$this->repo}/archive/refs/tags/{$latestTag}.zip",
                            'body' => 'Nueva versión detectada desde repositorio oficial de etiquetas.',
                            'has_update' => true
                        ];
                    }
                    
                    return [
                        'has_update' => false,
                        'current_version' => $currentVersion
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error("Update check via tags failed: " . $e->getMessage());
        }

        return [
            'has_update' => false,
            'current_version' => $currentVersion,
            'error' => $errorMessage ?? 'No se pudo conectar con el servidor de actualizaciones. Por favor verifique su conexión a internet.'
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
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);
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
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);
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
                
                // 1. Detener servicios de Windows para liberar los archivos
                $services = ['JSPOS_WhatsApp_API', 'JSPOS_Queue_Worker', 'JSPOS_Scheduler'];
                $nssmPath = base_path('nssm/nssm.exe');
                if (File::exists($nssmPath)) {
                    foreach ($services as $service) {
                        try {
                            @shell_exec('"' . $nssmPath . '" stop ' . $service);
                            Log::info("Updater: Deteniendo servicio de Windows: {$service}");
                        } catch (\Exception $e) {
                            Log::warning("Updater: No se pudo detener el servicio {$service} antes de copiar: " . $e->getMessage());
                        }
                    }
                    // Esperar 1 segundo para dar tiempo a liberar los archivos
                    sleep(1);
                }

                // 2. Copiar archivos del lote .bat primero
                $batFiles = ['instalar_servicios.bat', 'desinstalar-servicio.bat', 'backup.bat', 'backup_cliente.bat'];
                foreach ($batFiles as $batFile) {
                    $srcFile = $source . '/' . $batFile;
                    $dstFile = base_path($batFile);
                    if (File::exists($srcFile)) {
                        try {
                            if (File::exists($dstFile)) {
                                @chmod($dstFile, 0777); // Try to remove read-only or locks
                            }
                            File::copy($srcFile, $dstFile);
                            Log::info("Updater: Force updated batch file {$batFile}");
                        } catch (\Exception $e) {
                            Log::error("Updater: Failed to update batch file {$batFile}: " . $e->getMessage());
                        }
                    }
                }

                // 3. Copiar el resto del proyecto rastreando fallos
                $failedFiles = [];
                $this->copyDirectoryWithTracking($source, base_path(), $failedFiles);

                // 4. Levantar los servicios de Windows de nuevo
                if (File::exists($nssmPath)) {
                    foreach ($services as $service) {
                        try {
                            @shell_exec('"' . $nssmPath . '" start ' . $service);
                            Log::info("Updater: Iniciando servicio de Windows: {$service}");
                        } catch (\Exception $e) {
                            Log::warning("Updater: No se pudo iniciar el servicio {$service} después de copiar: " . $e->getMessage());
                        }
                    }
                }

                // 5. Si hubo fallos de copia, lanzar error
                if (!empty($failedFiles)) {
                    $fileList = implode(', ', $failedFiles);
                    throw new \Exception("La actualización se completó parcialmente. Algunos archivos no pudieron ser sobrescritos porque están bloqueados o en uso por el sistema. Por favor detenga manualmente los servicios en la máquina del cliente e intente de nuevo. Archivos fallidos: " . $fileList);
                }
            }

            // Explicitly update version.txt
            if ($newVersion) {
                File::put(base_path('version.txt'), $newVersion);
            }

            // Auto-run DB migrations & seeders automatically during installation
            try {
                $this->runMigrations();
            } catch (\Exception $e) {
                Log::error("Updater: Automatic migration during install failed: " . $e->getMessage());
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

    /**
     * Copia un directorio rastreando y recolectando los archivos que fallen.
     */
    protected function copyDirectoryWithTracking($source, $destination, &$failedFiles = [])
    {
        if (!File::isDirectory($source)) {
            return;
        }

        if (!File::exists($destination)) {
            File::makeDirectory($destination, 0755, true, true);
        }

        $items = new \FilesystemIterator($source, \FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            $target = $destination . '/' . $item->getFilename();

            if ($item->isDir()) {
                $this->copyDirectoryWithTracking($item->getPathname(), $target, $failedFiles);
            } else {
                try {
                    // Intentar quitar atributos de solo lectura si existe
                    if (File::exists($target)) {
                        @chmod($target, 0777);
                    }
                    // Copiar y registrar si falla
                    if (!@copy($item->getPathname(), $target)) {
                        $failedFiles[] = str_replace(base_path() . '/', '', $target);
                    }
                } catch (\Exception $e) {
                    $failedFiles[] = str_replace(base_path() . '/', '', $target) . " (" . $e->getMessage() . ")";
                }
            }
        }
    }

    public function runMigrations()
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);
        Artisan::call('migrate', ['--force' => true]);
        // Also run permission seeder to ensure new features are accessible
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'SopladosProductionTargetSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'SopladosSecondQualityLinkerSeeder', '--force' => true]);

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

        // Create the AutoMigrate flag file so AutoMigrate middleware recognizes completion for this version
        $versionFile = base_path('version.txt');
        if (File::exists($versionFile)) {
            $currentVersion = trim(File::get($versionFile));
            $flagFile = storage_path('framework/migrated_' . str_replace('.', '_', $currentVersion) . '.log');
            File::put($flagFile, 'Migrated on: ' . now()->toDateTimeString());
        }

        Artisan::call('optimize:clear');

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
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);
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

        $fileHandle = fopen($filePath, 'w');
        if (!$fileHandle) {
            throw new \Exception("Cannot open backup file for writing: " . $filePath);
        }

        fwrite($fileHandle, "-- JSPOS Database Backup\n");
        fwrite($fileHandle, "-- Generated: " . now()->toIso8601String() . "\n\n");
        fwrite($fileHandle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tables as $table) {
            // Structure
            $createTable = \Illuminate\Support\Facades\DB::select("SHOW CREATE TABLE `{$table}`");
            $createTableSql = (array)$createTable[0];
            fwrite($fileHandle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($fileHandle, $createTableSql['Create Table'] . ";\n\n");

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

                    fwrite($fileHandle, "INSERT INTO `{$table}` (" . implode(', ', $keysList) . ") VALUES (" . implode(', ', $valuesList) . ");\n");
                }
            }
            fwrite($fileHandle, "\n");
        }

        fwrite($fileHandle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fileHandle);
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
                    $pathname = $file->getPathname();
                    
                    // Exclude storage symlinks in public or cache folders (check both realpath and pathname for Windows junctions)
                    $isExcludeFolder = str_contains($filePath, 'public\\storage') || str_contains($filePath, 'public/storage') ||
                                       str_contains($pathname, 'public\\storage') || str_contains($pathname, 'public/storage') ||
                                       str_contains($filePath, 'bootstrap\\cache') || str_contains($filePath, 'bootstrap/cache') ||
                                       str_contains($pathname, 'bootstrap\\cache') || str_contains($pathname, 'bootstrap/cache');
                                       
                    // Exclude large non-code media and installers to optimize update backup size and speed
                    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    $isExcludeExtension = in_array($extension, [
                        'apk', 'mp4', 'zip', 'rar', 'tar', 'gz',
                        'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico',
                        'map', 'pdf', 'mp3', 'wav', 'ogg', 'wma', 'aac'
                    ]);

                    if ($isExcludeFolder || $isExcludeExtension) {
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
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);
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

    /**
     * Retorna las últimas N líneas del archivo laravel.log de forma eficiente.
     */
    public function getLatestLogLines($lines = 150)
    {
        $logPath = storage_path('logs/laravel.log');
        if (!File::exists($logPath)) {
            return "No hay registros de errores disponibles.";
        }

        $file = @fopen($logPath, 'r');
        if (!$file) {
            return "No se pudo abrir el archivo de registros.";
        }

        $chunkSize = 4096;
        fseek($file, 0, SEEK_END);
        $fileSize = ftell($file);
        $pos = $fileSize;

        $output = '';
        $lineCount = 0;

        // Read backwards in chunks until we have enough lines or reach start of file
        while ($pos > 0 && $lineCount <= $lines) {
            $readSize = min($chunkSize, $pos);
            $pos -= $readSize;
            fseek($file, $pos);
            $chunk = fread($file, $readSize);
            
            $output = $chunk . $output;
            $lineCount = substr_count($output, "\n");
        }

        fclose($file);

        // Split into lines
        $logLines = explode("\n", $output);
        
        // If the last element is empty due to a trailing newline, remove it
        if (end($logLines) === '') {
            array_pop($logLines);
        }

        // Return only the requested number of lines
        $slice = array_slice($logLines, -$lines);
        return implode("\n", $slice);
    }

    /**
     * Limpia el archivo laravel.log vaciando su contenido.
     */
    public function clearLog()
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
            clearstatcache(true, $logPath);
            return true;
        }
        return false;
    }

    /**
     * Envía una notificación de correo al completar una actualización.
     */
    public function sendUpdateNotificationEmail($newVersion, $oldVersion)
    {
        try {
            $recipients = config('backup.notifications.mail.to');
            if (empty($recipients)) {
                $recipients = array_filter(array_map('trim', explode(',', env('BACKUP_MAIL_TO', 'jhosagid7@gmail.com'))));
            }
            if (empty($recipients)) {
                return;
            }

            $subject = "🟢 Sistema JSPOS Actualizado a v{$newVersion}";
            
            // Get release notes if available
            $releaseNotes = '';
            $changelogPath = base_path('CHANGELOG.md');
            if (file_exists($changelogPath)) {
                $content = file_get_contents($changelogPath);
                $lines = explode("\n", $content);
                $notes = [];
                $capturing = false;
                $searchVersion = str_replace('v', '', $newVersion);
                $startPattern = "/^## \[" . preg_quote($searchVersion, '/') . "\]/";

                foreach ($lines as $line) {
                    if (preg_match($startPattern, $line)) {
                        $capturing = true;
                        continue;
                    }
                    if ($capturing && preg_match('/^## \[/', $line)) {
                        break;
                    }
                    if ($capturing) {
                        $notes[] = $line;
                    }
                }
                $releaseNotes = trim(implode("\n", $notes));
            }

            $body = "Hola,\n\nEl sistema JSPOS ha sido actualizado exitosamente de la versión v{$oldVersion} a la versión v{$newVersion}.\n\n";
            if (!empty($releaseNotes)) {
                $body .= "📋 NOTAS DE LA VERSIÓN:\n--------------------------------------------------\n{$releaseNotes}\n\n";
            }
            $body .= "Se ha creado automáticamente un punto de restauración antes de realizar la actualización en storage/backups/antes_de_v{$oldVersion}.\n\nAtentamente,\nSistema de Actualización Automatizado.";

            Mail::to($recipients)->queue(new \App\Mail\GenericNotificationMail(
                $subject,
                $body
            ));
            Log::info("Update notification email sent to: " . implode(', ', $recipients));
        } catch (\Exception $e) {
            Log::error("Failed to send update notification email: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la ruta al archivo laravel.log.
     */
    public function getLogPath()
    {
        return storage_path('logs/laravel.log');
    }
}
