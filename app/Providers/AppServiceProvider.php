<?php

namespace App\Providers;

use App\Helpers\Helper;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registro de helpers
        $this->app->singleton('fun', function () {
            return new Helper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('layouts.theme.header', \App\View\Composers\HeaderComposer::class);

        // Registro de directiva Blade para Módulos SaaS
        \Illuminate\Support\Facades\Blade::if('module', function ($moduleName) {
            $modules = config('tenant.modules');
            
            if ($modules === null) {
                // If config is null, the middleware hasn't run or is not persistent
                // Check if we can get it from the license service directly as fallback
                try {
                    $licenseService = app(\App\Services\LicenseService::class);
                    $status = $licenseService->checkLicense();
                    $modules = $status['modules'] ?? [];
                    // Cache it for the rest of this request
                    config(['tenant.modules' => $modules]);
                } catch (\Exception $e) {
                    $modules = [];
                }
            }

            return in_array($moduleName, $modules);
        });

        try {
            $config = \App\Services\ConfigurationService::getConfig();
            if ($config) {
                if (!empty($config->backup_emails)) {
                    config(['backup.notifications.mail.to' => $config->backup_emails]);
                }
                
                $businessName = $config->business_name ?? 'Sistema';
                // Sanitize business name
                $businessName = iconv('UTF-8', 'UTF-8//IGNORE', $businessName);
                $businessName = preg_replace('/[\x00-\x1F\x7F]/u', '', $businessName) ?? $businessName;

                $appName = "JSPOS(" . $businessName . ")";
                config([
                    'backup.backup.name' => $appName,
                    'mail.from.name' => $appName,
                    'app.name' => $appName
                ]);
            }
        } catch (\Throwable $th) {
            // Fails silently
        }
    }
}
