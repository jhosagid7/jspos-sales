<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If already installed, continue
        if (config('app.installed') === true) {
            return $next($request);
        }

        // If not installed, allow access to install routes
        if ($request->is('install') || $request->is('install/*')) {
            // Force session driver to 'file' to avoid DB connection errors
            // if the .env has SESSION_DRIVER=database but DB is not configured.
            config(['session.driver' => 'file']);
            
            return $next($request);
        }

        // Otherwise, redirect to installer
        return redirect('/install');
    }
}
