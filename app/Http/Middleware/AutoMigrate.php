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
                    
                    // Use a simple flat file in storage to track migration status per version
                    $flagFile = storage_path('framework/migrated_' . str_replace('.', '_', $currentVersion) . '.log');
                    
                    if (!File::exists($flagFile)) {
                        \Illuminate\Support\Facades\Log::info("AutoMigrate - Start for version: " . $currentVersion);
                        
                        // Auto-install new composer dependencies ONLY if composer.lock has changed
                        if (function_exists('exec')) {
                            $lockFile = base_path('composer.lock');
                            $lockHash = File::exists($lockFile) ? md5_file($lockFile) : 'no_lock';
                            
                            $hashFile = storage_path('framework/composer_lock.hash');
                            $savedHash = File::exists($hashFile) ? trim(File::get($hashFile)) : '';
                            
                            if ($lockHash !== $savedHash) {
                                \Illuminate\Support\Facades\Log::info("AutoMigrate - composer.lock changed. Running composer install...");
                                $base = base_path();
                                exec("cd {$base} && composer install --no-interaction --optimize-autoloader 2>&1", $out, $ret);
                                \Illuminate\Support\Facades\Log::info("AutoMigrate - Composer: " . implode("\n", $out));
                                
                                // Only save the hash if composer install completed successfully
                                if (isset($ret) && $ret === 0) {
                                    File::put($hashFile, $lockHash);
                                }
                            } else {
                                \Illuminate\Support\Facades\Log::info("AutoMigrate - composer.lock has not changed. Skipping composer install.");
                            }
                        }
                        
                        // Run migrations automatically via UpdateService
                        app(\App\Services\UpdateService::class)->runMigrations();
                        
                        \Illuminate\Support\Facades\Log::info("AutoMigrate - Migration complete");
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
