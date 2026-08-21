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
        // Auto-synchronize APP_URL & URL root with request host to prevent Livewire 401 signature mismatch
        try {
            if (!app()->runningInConsole() && isset($_SERVER['HTTP_HOST'])) {
                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $currentUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
                config(['app.url' => $currentUrl]);
                \Illuminate\Support\Facades\URL::forceRootUrl($currentUrl);
            }
        } catch (\Throwable $e) {}

        // Auto-ensure public storage directories & symlink exist
        try {
            @ini_set('upload_max_filesize', '64M');
            @ini_set('post_max_size', '64M');
            @ini_set('memory_limit', '512M');

            $publicStoragePath = storage_path('app/public');
            $productsPath = storage_path('app/public/products');
            $categoriesPath = storage_path('app/public/categories');
            $livewireTmpPath = storage_path('app/livewire-tmp');

            if (!file_exists($productsPath)) {
                @mkdir($productsPath, 0777, true);
            }
            if (!file_exists($categoriesPath)) {
                @mkdir($categoriesPath, 0777, true);
            }
            if (!file_exists($livewireTmpPath)) {
                @mkdir($livewireTmpPath, 0777, true);
            }

            $symlink = public_path('storage');
            if (!file_exists($symlink) && !is_link($symlink)) {
                @app('files')->link($publicStoragePath, $symlink);
            }
        } catch (\Throwable $e) {}

        \Illuminate\Support\Facades\View::composer('layouts.theme.header', \App\View\Composers\HeaderComposer::class);

        // Registro de directiva Blade para Módulos SaaS
        \Illuminate\Support\Facades\Blade::if('module', function ($moduleName) {
            // Super Admin bypass: ve todos los módulos sin importar el plan
            try {
                if (auth()->check() && auth()->user()->hasRole('Super Admin')) {
                    return true;
                }
            } catch (\Throwable $e) {}

            try {
                $config = \App\Services\ConfigurationService::getConfig();
                if ($config && method_exists($config, 'hasAddon')) {
                    return $config->hasAddon($moduleName);
                }
            } catch (\Throwable $th) {}

            // Fallback en caso de que no haya DB (ej. en migraciones)
            $modules = config('tenant.modules', []);
            return in_array($moduleName, $modules);
        });

        // Registro de directiva Blade para Planes de Suscripción
        \Illuminate\Support\Facades\Blade::if('plan', function ($planName) {
            // Super Admin bypass: cumple cualquier plan requerido
            try {
                if (auth()->check() && auth()->user()->hasRole('Super Admin')) {
                    return true;
                }
            } catch (\Throwable $e) {}

            try {
                $config = \App\Services\ConfigurationService::getConfig();
                if ($config && method_exists($config, 'hasPlan')) {
                    return $config->hasPlan($planName);
                }
            } catch (\Throwable $th) {}
            return false; // Default false to enforce security
        });

        // Registro de directiva Blade para Add-ons a la carta
        \Illuminate\Support\Facades\Blade::if('addon', function ($addonName) {
            // Super Admin bypass: ve todos los add-ons
            try {
                if (auth()->check() && auth()->user()->hasRole('Super Admin')) {
                    return true;
                }
            } catch (\Throwable $e) {}

            try {
                $config = \App\Services\ConfigurationService::getConfig();
                if ($config && method_exists($config, 'hasAddon')) {
                    return $config->hasAddon($addonName);
                }
            } catch (\Throwable $th) {}
            return false;
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

        // Auto-enable new treasury module in local environment for testing
        if (app()->environment('local')) {
            $modules = config('tenant.modules', []);
            if (!is_array($modules)) {
                $modules = [];
            }
            if (!in_array('module_treasury', $modules)) {
                $modules[] = 'module_treasury';
                config(['tenant.modules' => $modules]);
            }
        }
    }
}
