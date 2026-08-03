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
        $serverIp = env('LICENSE_SERVER_IP', session('license_server_ip', '100.74.104.82'));

        if ($serverIp) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(3)->post("http://{$serverIp}/api/clients/check-status", [
                    'client_system_id' => $clientId
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['has_license']) && !empty($data['license_key'])) {
                        if ($this->licenseService->activateLicense($data['license_key'])) {
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
        $serverIp = $request->input('server_ip') ?: env('LICENSE_SERVER_IP', session('license_server_ip', '100.74.104.82'));

        if (!$serverIp) {
            return back()->with('error', 'Por favor ingrese la IP del servidor de licencias.');
        }

        $clientId = $this->licenseService->getClientId();

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->post("http://{$serverIp}/api/clients/check-status", [
                'client_system_id' => $clientId
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['has_license']) && !empty($data['license_key'])) {
                    if ($this->licenseService->activateLicense($data['license_key'])) {
                        session(['license_server_ip' => $serverIp]);
                        return redirect('/')->with('success', '¡Licencia sincronizada y activada exitosamente!');
                    }
                }

                return back()->with('error', 'El servidor respondió pero aún no tiene una licencia activa asignada para este ID.');
            }

            return back()->with('error', 'No se pudo obtener respuesta del servidor de licencias.');

        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo conectar a http://' . $serverIp . ': ' . $e->getMessage());
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
