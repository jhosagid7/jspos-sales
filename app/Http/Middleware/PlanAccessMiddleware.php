<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlanAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string $type  'plan' or 'addon'
     * @param  string $requirement The required plan (e.g. 'pro') or addon (e.g. 'soplados')
     */
    public function handle(Request $request, Closure $next, $type, $requirement): Response
    {
        try {
            $config = \App\Services\ConfigurationService::getConfig();
            
            if ($config) {
                if ($type === 'plan' && method_exists($config, 'hasPlan')) {
                    if (!$config->hasPlan($requirement)) {
                        return redirect()->route('dashboard')->with('error', "Acceso Denegado. Esta función requiere el plan " . ucfirst($requirement) . " o superior.");
                    }
                } elseif ($type === 'addon' && method_exists($config, 'hasAddon')) {
                    if (!$config->hasAddon($requirement)) {
                        return redirect()->route('dashboard')->with('error', "Acceso Denegado. Esta función requiere el módulo adicional: " . ucfirst($requirement) . ".");
                    }
                }
            }
        } catch (\Throwable $th) {
            // Fail open or closed depending on security. 
            // In this case, we fail safe by letting it pass if config fails to load so the whole app doesn't break.
        }

        return $next($request);
    }
}
