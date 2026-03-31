<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class AutoMigrate
{
    /**
     * Handle an incoming request and run migrations if version mismatch.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only run on GET requests to avoid interrupting POST/PUT
        if ($request->isMethod('get')) {
            try {
                $versionFile = base_path('version.txt');
                if (File::exists($versionFile)) {
                    $currentVersion = trim(File::get($versionFile));
                    
                    // Use file-based cache key to track migration status per version
                    $cacheKey = 'migrated_version_' . str_replace('.', '_', $currentVersion);
                    
                    if (!Cache::store('file')->has($cacheKey)) {
                        // Run migrations automatically
                        Artisan::call('migrate', ['--force' => true]);
                        
                        // Mark as migrated for this version
                        Cache::store('file')->put($cacheKey, true, now()->addYears(5));
                        
                        // Optional: Clear view/config cache after migration
                        Artisan::call('optimize:clear');
                    }
                }
            } catch (\Throwable $th) {
                // Silently fail to not block the user, log it if possible
                \Illuminate\Support\Facades\Log::error("AutoMigrate failed: " . $th->getMessage());
            }
        }

        return $next($request);
    }
}
