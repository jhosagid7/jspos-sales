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
}
