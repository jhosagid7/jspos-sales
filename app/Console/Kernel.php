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

        // Reporte Semanal de Ingresos en PDF por WhatsApp los Sábados a las 10:00 PM
        $schedule->command('app:send-weekly-report')
            ->timezone('America/Caracas')
            ->weeklyOn(6, '22:00') // 6 representa Sábado
            ->runInBackground();

        // Limpieza diaria de respaldos antiguos a las 11:00 PM
        $schedule->command('backup:clean')
            ->timezone('America/Caracas')
            ->dailyAt('23:00')
            ->runInBackground();

        // Respaldo diario de base de datos a las 11:30 PM
        $schedule->command('backup:run --only-db')
            ->timezone('America/Caracas')
            ->dailyAt('23:30')
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
