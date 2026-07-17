<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{


    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Cierre Diario de Ventas por WhatsApp a las 10:00 PM
        $schedule->command('app:send-daily-closure')
            ->timezone('America/Caracas')
            ->dailyAt('22:00')
            ->runInBackground();

        // Dynamically schedule weekly reports based on DB configuration
        $weeklyReportDay = 6;
        $weeklyReportHour = '22:00';
        try {
            $config = \App\Models\Configuration::first();
            if ($config) {
                $weeklyReportDay = $config->weekly_report_send_day !== null ? (int) $config->weekly_report_send_day : 6;
                $weeklyReportHour = $config->weekly_report_send_hour ?: '22:00';
            }
        } catch (\Exception $e) {
            // Fallback to default in case DB is not initialized or migrated yet
        }

        // Reporte Semanal de Ingresos en PDF por WhatsApp
        $schedule->command('app:send-weekly-report')
            ->timezone('America/Caracas')
            ->weeklyOn($weeklyReportDay, $weeklyReportHour)
            ->runInBackground();

        // Reporte Consolidado Semanal de Soplados en PDF por WhatsApp y Email
        $schedule->command('app:send-soplados-weekly-report')
            ->timezone('America/Caracas')
            ->weeklyOn($weeklyReportDay, $weeklyReportHour)
            ->runInBackground();

        // Limpieza diaria de respaldos antiguos a las 11:00 PM
        $schedule->command('backup:clean')
            ->timezone('America/Caracas')
            ->dailyAt('23:00')
            ->runInBackground();

        // Respaldo de base de datos cada 2 horas entre las 6:00 y las 22:00 (6 AM - 10 PM)
        $schedule->command('backup:run --only-db')
            ->timezone('America/Caracas')
            ->everyTwoHours()
            ->between('6:00', '22:00')
            ->runInBackground();

        // Cierre diario de bancos (tesorería)
        $treasuryCutoffHour = '17:00';
        try {
            $config = \App\Models\Configuration::first();
            if ($config) {
                $treasuryCutoffHour = $config->treasury_cutoff_hour ?: '17:00';
            }
        } catch (\Exception $e) {
        }

        $schedule->command('treasury:bank-cutoff')
            ->timezone('America/Caracas')
            ->dailyAt($treasuryCutoffHour)
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
