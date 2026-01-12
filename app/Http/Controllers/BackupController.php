<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function download($fileName)
    {
        $disk = Storage::disk('backup');
        $path = config('backup.backup.name') . '/' . $fileName;

        if ($disk->exists($path)) {
            return $disk->download($path);
        }

        abort(404, 'Backup file not found.');
    }
}
