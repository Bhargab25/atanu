<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Services\BackupService;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Daily backup at 2 AM
        $schedule->command('backup:auto')->dailyAt('08:53');

        // Weekly cleanup of old backups
        $schedule->call(function () {
            $backupService = new BackupService();
            $backupService->cleanupOldBackups(30);
        })->weekly();;
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
