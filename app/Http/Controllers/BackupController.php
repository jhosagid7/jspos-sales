<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function download($fileName)
    {
        $disk = Storage::disk('backup');
        
        // 1. Direct check in config name folder
        $path = config('backup.backup.name') . '/' . $fileName;
        if ($disk->exists($path)) {
            return $disk->download($path);
        }

        // 2. Direct check if fileName is relative path
        if ($disk->exists($fileName)) {
            return $disk->download($fileName);
        }

        // 3. Search across all files in backup disk
        $allFiles = $disk->allFiles();
        foreach ($allFiles as $file) {
            if (basename($file) === $fileName) {
                return $disk->download($file);
            }
        }

        abort(404, 'Backup file not found.');
    }
}
