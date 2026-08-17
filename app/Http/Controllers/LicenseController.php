<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\LicenseService;

class LicenseController extends Controller
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function expired()
    {
        $clientId = $this->licenseService->getClientId();
        $serverIp = old('server_ip', $this->licenseService->getLicenseServerIp());

        if ($serverIp) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(3)->post("http://{$serverIp}/api/clients/check-status", [
                    'client_system_id' => $clientId
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['has_license']) && !empty($data['license_key'])) {
                        if ($this->licenseService->activateLicense($data['license_key'])) {
                            $this->licenseService->saveLicenseServerIp($serverIp);
                            return redirect('/')->with('success', '¡Licencia sincronizada y activada automáticamente!');
                        }
                    }
                }
            } catch (\Exception $e) {}
        }

        return view('license.expired', compact('clientId', 'serverIp'));
    }

    public function sync(Request $request)
    {
        $rawIp = $request->input('server_ip') ?: $this->licenseService->getLicenseServerIp();

        if (!$rawIp) {
            return back()->withInput()->with('error', 'Por favor ingrese la IP del servidor de licencias.');
        }

        $serverIp = preg_replace('#^https?://#i', '', trim($rawIp));
        $serverIp = rtrim($serverIp, '/');

        $clientId = $this->licenseService->getClientId();

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->post("http://{$serverIp}/api/clients/check-status", [
                'client_system_id' => $clientId
            ]);

            $data = $response->json();

            // Save IP if server responded with valid JSON
            if (is_array($data)) {
                $this->licenseService->saveLicenseServerIp($serverIp);
            }

            if ($response->successful()) {
                if (!empty($data['has_license']) && !empty($data['license_key'])) {
                    if ($this->licenseService->activateLicense($data['license_key'])) {
                        return redirect('/')->with('success', '¡Licencia sincronizada y activada exitosamente!');
                    }
                }

                return back()->withInput()->with('error', $data['message'] ?? 'El servidor respondió pero aún no tiene una licencia activa asignada para este ID.');
            }

            // If client is not registered on the license server, attempt auto-registration
            if ($response->status() === 404 && is_array($data) && str_contains(strtolower($data['message'] ?? ''), 'no registrado')) {
                $regResponse = \Illuminate\Support\Facades\Http::timeout(5)->post("http://{$serverIp}/api/clients/register", [
                    'client_system_id' => $clientId,
                    'name' => config('app.name', 'JSPOS Client') . ' (' . gethostname() . ')',
                    'vpn_ip' => $serverIp,
                ]);

                if ($regResponse->successful()) {
                    $regData = $regResponse->json();
                    $this->licenseService->saveLicenseServerIp($serverIp);
                    return back()->withInput()->with('success', $regData['message'] ?? '¡Equipo registrado exitosamente en el servidor de licencias! El administrador ya lo ve en su panel.');
                }
            }

            $errMsg = is_array($data) ? ($data['message'] ?? $data['error'] ?? null) : null;
            $detail = $errMsg ?: "Respuesta de error del servidor (HTTP {$response->status()}).";

            return back()->withInput()->with('error', "Servidor http://{$serverIp}: {$detail}");

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'No se pudo conectar a http://' . $serverIp . ': ' . $e->getMessage());
        }
    }

    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);

        $success = $this->licenseService->activateLicense($request->license_key);

        if ($success) {
            return redirect('/')->with('success', 'Licencia activada correctamente.');
        }

        return back()->with('error', 'Licencia inválida o corrupta.');
    }
}
