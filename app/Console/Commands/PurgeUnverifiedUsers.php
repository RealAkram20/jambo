<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Delete unverified accounts older than N days.
 *
 * Bot signups never click the verification link, so they accumulate
 * forever in the users table even though they can't actually use the
 * platform. This command sweeps them out so the table doesn't bloat
 * and admin user lists / counts stay meaningful.
 *
 * Safety rails:
 *
 *   - Only deletes rows with `email_verified_at IS NULL`. A user who
 *     ever verified is permanently safe from this sweep.
 *   - Skips anyone holding the `admin` role (belt + braces — admins
 *     are normally seeded with verified emails, but a hand-built
 *     admin row that somehow lacks the timestamp will not be purged).
 *   - Honours `--days=N` so the operator can extend the grace window
 *     for a real-user signup wave.
 *   - `--dry-run` lists what would be deleted without touching it.
 *
 * Scheduled nightly at 03:10 by App\Console\Kernel.
 */
class PurgeUnverifiedUsers extends Command
{
    protected $signature = 'jambo:purge-unverified
        {--days=7 : Minimum age in days before an unverified user is purged}
        {--dry-run : List who would be purged without deleting}';

    protected $description = 'Delete unverified accounts older than N days (default 7).';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $query = User::whereNull('email_verified_at')
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'));

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info("No unverified accounts older than {$days} days. Nothing to purge.");
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run: would delete {$count} unverified user(s) older than {$days} days.");
            (clone $query)
                ->orderBy('created_at')
                ->limit(20)
                ->get(['id', 'email', 'created_at'])
                ->each(fn ($u) => $this->line(
                    "  #{$u->id}  {$u->email}  (created {$u->created_at->diffForHumans()})"
                ));
            if ($count > 20) {
                $this->line('  ...and ' . ($count - 20) . ' more.');
            }
            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Purged {$deleted} unverified user(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
