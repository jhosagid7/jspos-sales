<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\LicenseService;
use Illuminate\Support\Facades\Log;

class LicenseReceiverController extends Controller
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function push(Request $request)
    {
        // Require API Key validation for security against unauthorized pushes
        $validToken = env('LICENSE_SYNC_TOKEN', 'super_secret_token_123'); // Fallback for dev

        $providedToken = $request->header('Authorization') ?? $request->bearerToken() ?? $request->input('api_token');

        if (!$providedToken || $providedToken !== $validToken) {
            Log::warning('Unauthorized license push attempt', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized. Invalid API Token.'], 401);
        }

        $request->validate([
            'license_key' => 'required|string',
        ]);

        try {
            $success = $this->licenseService->activateLicense($request->license_key);

            if ($success) {
                Log::info('License successfully pushed and activated remotely.', ['ip' => $request->ip()]);
                return response()->json(['message' => 'Licencia activada correctamente.'], 200);
            }

            return response()->json(['error' => 'La licencia enviada es inválida, expirada o corresponde a otro cliente.'], 400);
            
        } catch (\Exception $e) {
            Log::error('Error pushing license: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno procesando la licencia.'], 500);
        }
    }

    public function ping()
    {
        $licenseData = $this->licenseService->checkLicense();

        return response()->json([
            'status' => 'online',
            'client_id' => $this->licenseService->getClientId(),
            'has_license' => isset($licenseData['status']) && $licenseData['status'] === 'active',
            'expires_at' => $licenseData['expires_at'] ?? null,
            'installed' => file_exists(storage_path('installed')),
            'wiped' => file_exists(storage_path('wiped')),
        ]);
    }

    public function remoteWipe(Request $request)
    {
        $validToken = env('LICENSE_SYNC_TOKEN', 'super_secret_token_123');
        $providedToken = $request->header('Authorization') ?? $request->bearerToken() ?? $request->input('api_token');

        if (!$providedToken || $providedToken !== $validToken) {
            Log::warning('Unauthorized remote wipe attempt', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized. Invalid API Token.'], 401);
        }

        // Validate that the request target client_system_id matches THIS local installation
        $myClientId = $this->licenseService->getClientId();
        $targetClientId = $request->input('client_system_id');

        if ($targetClientId && $targetClientId !== $myClientId) {
            Log::warning('Remote wipe rejected due to client ID mismatch', [
                'target' => $targetClientId,
                'my_id' => $myClientId
            ]);
            return response()->json(['error' => 'Orden ignorada: El ID de cliente no corresponde a esta instalación.'], 422);
        }

        try {
            $wipeService = new \App\Services\WipeService();

            // 1. Generate Backup Content
            $backupContent = $wipeService->generateBackupStream();

            // 2. Execute local system wipe
            $wipeService->executeLocalWipe();

            Log::alert('REMOTE WIPE EXECUTED VIA LICENSE SERVER', ['ip' => $request->ip()]);

            // 3. Return backup binary to license server
            return response($backupContent, 200)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="backup_remote_wipe.zip"');

        } catch (\Exception $e) {
            Log::error('Error executing remote wipe: ' . $e->getMessage());
            return response()->json(['error' => 'Error durante el borrado remoto: ' . $e->getMessage()], 500);
        }
    }
}
