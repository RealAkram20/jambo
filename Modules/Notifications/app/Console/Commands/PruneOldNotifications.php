<?php

namespace Modules\Notifications\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Prunes read notifications older than N days to keep the
 * `notifications` table bounded. Unread notifications are never
 * touched. Run weekly.
 *
 *   $schedule->command('notifications:prune')->weekly();
 *
 * Default retention: 90 days. Override with --days=30 etc.
 */
class PruneOldNotifications extends Command
{
    protected $signature = 'notifications:prune {--days=90 : Delete read notifications older than this many days}';

    protected $description = 'Prune read notifications older than N days.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 7) {
            $this->error('Refusing to prune with retention < 7 days. Pass a larger --days.');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $count = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('read_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$count} read notification(s) older than {$days} days.");
        return self::SUCCESS;
    }
}
