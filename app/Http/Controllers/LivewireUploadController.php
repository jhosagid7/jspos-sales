<?php

namespace App\Http\Controllers;

use Livewire\Features\SupportFileUploads\FileUploadController as BaseController;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LivewireUploadController extends BaseController
{
    public function validateAndStore($files, $disk)
    {
        // 1. Verify if any uploaded file failed at PHP level (e.g. upload_max_filesize exceeded or unreadable tmp)
        foreach ((array) $files as $file) {
            if ($file && method_exists($file, 'isValid') && !$file->isValid()) {
                $errorCode = method_exists($file, 'getError') ? $file->getError() : 0;
                $msg = 'El archivo seleccionado no se pudo subir al servidor.';

                if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
                    $msg = 'El archivo excede el tamaño máximo permitido de subida en la configuración del servidor.';
                } elseif ($errorCode === UPLOAD_ERR_NO_TMP_DIR || $errorCode === UPLOAD_ERR_CANT_WRITE) {
                    $msg = 'No hay permisos de escritura en la carpeta temporal del servidor.';
                }

                throw ValidationException::withMessages([
                    'files' => [$msg]
                ]);
            }

            if (!$file || !method_exists($file, 'getRealPath') || empty($file->getRealPath()) || !file_exists($file->getRealPath())) {
                throw ValidationException::withMessages([
                    'files' => ['No se pudo leer la ruta temporal del archivo subido.']
                ]);
            }
        }

        // 2. Validate against Livewire rules
        Validator::make(['files' => $files], [
            'files.*' => FileUploadConfiguration::rules()
        ])->validate();

        // 3. Store files safely
        $fileHashPaths = collect($files)->map(function ($file) use ($disk) {
            $filename = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded($file);

            return $file->storeAs('/'.FileUploadConfiguration::path(), $filename, [
                'disk' => $disk
            ]);
        });

        // Strip out the temporary upload directory from the paths.
        return $fileHashPaths->map(function ($path) { 
            return str_replace(FileUploadConfiguration::path('/'), '', $path); 
        });
    }
}
