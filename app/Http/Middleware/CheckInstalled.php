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
        $installedLockFile = storage_path('installed');

        // 1. Check if the lock file exists (Most robust check)
        if (file_exists($installedLockFile)) {
            return $next($request);
        }

        // 2. Fallback: Check config (in case file was deleted or first run after update)
        // If config says installed but file is missing -> Self Heal (Create file)
        if (config('app.installed') === true) {
            try {
                file_put_contents($installedLockFile, 'JSPOS INSTALLED ON ' . date('Y-m-d H:i:s'));
            } catch (\Throwable $th) {
                // Ignore write errors to avoid crashing, just rely on config this time
            }
            return $next($request);
        }

        // 3. If not installed, allow access to install routes
        if ($request->is('install') || $request->is('install/*')) {
            // Force session driver to 'file' to avoid DB connection errors
            // if the .env has SESSION_DRIVER=database but DB is not configured.
            config(['session.driver' => 'file']);
            
            return $next($request);
        }

        // 4. Otherwise, redirect to installer
        return redirect('/install');
    }
}
