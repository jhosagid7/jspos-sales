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
        return view('license.expired', compact('clientId'));
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
