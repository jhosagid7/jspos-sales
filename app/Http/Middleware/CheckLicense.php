<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LicenseService;

class CheckLicense
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip license check if the app is not installed yet
        if (env('APP_INSTALLED', false) !== true) {
            return $next($request);
        }

        // Allow access to license activation routes to prevent infinite loops
        if ($request->routeIs('license.*')) {
            return $next($request);
        }

        $status = $this->licenseService->checkLicense();

        if ($status['status'] !== 'active') {
            return redirect()->route('license.expired');
        }

        // Share days remaining with views (for the header notification)
        view()->share('license_days_remaining', $status['days_remaining']);

        return $next($request);
    }
}
