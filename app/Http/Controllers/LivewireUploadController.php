<?php

namespace App\Http\Controllers;

use Livewire\Features\SupportFileUploads\FileUploadController as BaseController;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LivewireUploadController extends BaseController
{
    public function handle()
    {
        $hasValidSignature = request()->hasValidSignature();
        $filesRaw = request('files');
        $filesCount = is_array($filesRaw) ? count($filesRaw) : ($filesRaw ? 1 : 0);
        $contentLength = request()->server('CONTENT_LENGTH') ?? 'N/A';
        $httpHost = request()->server('HTTP_HOST') ?? request()->getHost();
        $clientIp = request()->ip();

        Log::info("[LivewireUpload] Petición de subida recibida", [
            'host' => $httpHost,
            'ip' => $clientIp,
            'has_valid_signature' => $hasValidSignature,
            'files_count' => $filesCount,
            'content_length' => $contentLength,
            'url' => request()->fullUrl(),
        ]);

        if (!$hasValidSignature) {
            Log::warning("[LivewireUpload] 401 Rechazado por firma digital inválida", [
                'expected_root' => config('app.url'),
                'request_url' => request()->fullUrl(),
                'host' => $httpHost,
            ]);
            abort(401);
        }

        $disk = FileUploadConfiguration::disk();

        $filePaths = $this->validateAndStore(request('files'), $disk);

        return ['paths' => $filePaths];
    }

    public function validateAndStore($files, $disk)
    {
        // 1. Detect if request body was dropped due to post_max_size
        $contentLength = (int) (request()->server('CONTENT_LENGTH') ?? 0);
        if (empty($files) && $contentLength > 0) {
            Log::warning("[LivewireUpload] Archivos vacíos pero CONTENT_LENGTH presente ($contentLength bytes). Excede post_max_size.");
            throw ValidationException::withMessages([
                'files' => ['El archivo excede el tamaño máximo total de subida permitido en la configuración del servidor (post_max_size).']
            ]);
        }

        // 2. Validate and log individual PHP uploaded files
        foreach ((array) $files as $index => $file) {
            if (!$file) {
                continue;
            }

            $originalName = is_object($file) && method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : 'desconocido';
            $fileSize = is_object($file) && method_exists($file, 'getSize') ? $file->getSize() : (is_string($file) && file_exists($file) ? filesize($file) : 0);
            $mimeType = is_object($file) && method_exists($file, 'getClientMimeType') ? $file->getClientMimeType() : 'N/A';
            $errorCode = is_object($file) && method_exists($file, 'getError') ? $file->getError() : UPLOAD_ERR_OK;

            Log::info("[LivewireUpload] Procesando archivo [$index]", [
                'nombre_original' => $originalName,
                'tamano_bytes' => $fileSize,
                'mime' => $mimeType,
                'error_php' => $errorCode,
            ]);

            if ($errorCode !== UPLOAD_ERR_OK) {
                $msg = 'El archivo seleccionado no se pudo subir al servidor.';

                if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
                    $msg = "El archivo '$originalName' excede el tamaño máximo permitido de subida en la configuración del servidor.";
                } elseif ($errorCode === UPLOAD_ERR_NO_TMP_DIR || $errorCode === UPLOAD_ERR_CANT_WRITE) {
                    $msg = 'No hay permisos de escritura en la carpeta temporal del servidor para guardar el archivo.';
                } elseif ($errorCode === UPLOAD_ERR_PARTIAL) {
                    $msg = "El archivo '$originalName' solo se subió parcialmente. Por favor reintenta.";
                } elseif ($errorCode === UPLOAD_ERR_NO_FILE) {
                    $msg = 'No se recibió ningún archivo en la petición de subida.';
                }

                Log::error("[LivewireUpload] Error PHP al subir archivo: código $errorCode", [
                    'archivo' => $originalName,
                    'mensaje' => $msg
                ]);

                throw ValidationException::withMessages([
                    'files' => [$msg]
                ]);
            }
        }

        // 3. Validate against Livewire rules (e.g. extension, max size)
        Validator::make(['files' => $files], [
            'files.*' => FileUploadConfiguration::rules()
        ])->validate();

        // 4. Ensure destination temporary directory exists
        $tmpDirectory = FileUploadConfiguration::path();
        try {
            Storage::disk($disk)->makeDirectory($tmpDirectory);
        } catch (\Throwable $e) {
            Log::warning("[LivewireUpload] No se pudo asegurar el directorio temporal vía Storage: " . $e->getMessage());
        }

        // 5. Store files safely
        $fileHashPaths = collect($files)->map(function ($file, $index) use ($disk, $tmpDirectory) {
            $filename = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded($file);
            $targetPath = '/' . $tmpDirectory;

            $storedRelativePath = null;
            try {
                $storedRelativePath = $file->storeAs($targetPath, $filename, [
                    'disk' => $disk
                ]);
            } catch (\Throwable $e) {
                Log::warning("[LivewireUpload] storeAs falló para [$index], intentando copia física directa: " . $e->getMessage());
            }

            // Fallback: direct copy if storeAs didn't produce the file
            $absoluteTargetPath = Storage::disk($disk)->path($tmpDirectory . '/' . $filename);
            if (!$storedRelativePath || !file_exists($absoluteTargetPath) || filesize($absoluteTargetPath) === 0) {
                $sourcePath = is_object($file) ? ($file->getRealPath() ?: (method_exists($file, 'getPathname') ? $file->getPathname() : null)) : null;
                if ($sourcePath && file_exists($sourcePath)) {
                    @copy($sourcePath, $absoluteTargetPath);
                }
            }

            $finalSize = file_exists($absoluteTargetPath) ? filesize($absoluteTargetPath) : 0;

            Log::info("[LivewireUpload] Archivo temporal guardado exitosamente", [
                'indice' => $index,
                'hash_generado' => $filename,
                'ruta_absoluta' => $absoluteTargetPath,
                'tamano_guardado_bytes' => $finalSize,
                'disco' => $disk,
            ]);

            return $tmpDirectory . '/' . $filename;
        });

        // Strip out the temporary upload directory from the paths.
        return $fileHashPaths->map(function ($path) { 
            return str_replace(FileUploadConfiguration::path('/'), '', $path); 
        });
    }
}
