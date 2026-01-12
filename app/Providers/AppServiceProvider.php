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
                
                $appName = "JSPOS(" . ($config->business_name ?? 'Sistema') . ")";
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
