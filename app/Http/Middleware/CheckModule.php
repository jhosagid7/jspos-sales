<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModule
{
    public function handle(Request $request, Closure $next, $module): Response
    {
        $modules = config('tenant.modules', []);
        
        if (!in_array($module, $modules)) {
            // Flash error message via session (can be picked up by sweetalert/noty if implemented on welcome page)
            return redirect()->route('welcome')->with('error', 'Su plan de suscripción actual no incluye el módulo: ' . $module);
        }

        return $next($request);
    }
}
