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
        // Jalankan `php artisan schedule:run` tiap menit (Task Scheduler Windows / cron)
        $schedule->command('peminjaman:kedaluwarsa')->dailyAt('00:05')->withoutOverlapping();
        $schedule->command('peminjaman:pengingat')->dailyAt('07:00')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
