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

        try {
            $config = \App\Models\Configuration::first();
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
