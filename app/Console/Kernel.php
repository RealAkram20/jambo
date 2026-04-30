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
        // Sweep out bot signups that never confirmed their email.
        // 7 days is plenty of grace for a real user; bot accounts
        // never come back to verify so they pile up indefinitely
        // without this. Runs at 03:10 UTC — off-peak.
        $schedule->command('jambo:purge-unverified --days=7')
            ->dailyAt('03:10')
            ->withoutOverlapping()
            ->onOneServer();

        // Drain the queue every minute. The system relies on a queue
        // for QueuedVerifyEmail (so a flaky SMTP doesn't 500 the
        // signup request), and any other ShouldQueue job we add
        // later. Without a worker, queued jobs sit in the `jobs`
        // table forever — the visible bug is "I registered but never
        // got the verification email". This keeps a worker alive for
        // up to 55 seconds per minute, exiting before the next tick
        // so the next run can take over without overlap.
        //
        //   --stop-when-empty: idle path exits in ms, doesn't burn CPU
        //   --max-time=55:     hard ceiling so the next tick is fresh
        //   --sleep=2:         poll interval when waiting on a job
        //   --tries=3:         retry transient failures before failing
        $schedule->command('queue:work --stop-when-empty --max-time=55 --sleep=2 --tries=3')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();
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
