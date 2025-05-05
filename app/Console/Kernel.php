<?php

namespace App\Console;

use App\Console\Commands\Instagram;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('kta:update-status')->daily();
        $schedule->command('sbun:update-status')->daily();
        $schedule->command('sbus:update-status')->daily();
    }
}
