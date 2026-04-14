<?php

namespace Modules\Subscriptions\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Subscriptions\app\Models\UserSubscription;

/**
 * Flip any UserSubscription past its ends_at from `active` to `expired`
 * and fire a `subscription.expired` event per row. Safe to run
 * repeatedly — only touches rows that are still `active` AND past
 * their ends_at, so re-runs are no-ops.
 *
 * Scheduled hourly from SubscriptionsServiceProvider. Can also be run
 * ad-hoc: `php artisan subscriptions:expire`.
 */
class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire
                            {--dry-run : Report what would expire without writing}';

    protected $description = 'Expire user subscriptions whose ends_at has passed.';

    public function handle(): int
    {
        $now = Carbon::now();
        $dryRun = (bool) $this->option('dry-run');

        $query = UserSubscription::query()
            ->where('status', UserSubscription::STATUS_ACTIVE)
            ->where('ends_at', '<=', $now);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No subscriptions to expire.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("[dry-run] Would expire {$count} subscription(s).");
            $query->with('tier:id,name')->chunk(100, function ($rows) {
                foreach ($rows as $sub) {
                    $this->line(sprintf(
                        '  #%d user=%d tier=%s ends_at=%s',
                        $sub->id,
                        $sub->user_id,
                        $sub->tier?->name ?? '?',
                        $sub->ends_at?->toDateTimeString() ?? '-',
                    ));
                }
            });
            return self::SUCCESS;
        }

        $expired = 0;
        $query->chunkById(100, function ($rows) use (&$expired, $now) {
            foreach ($rows as $sub) {
                DB::transaction(function () use ($sub, $now, &$expired) {
                    $fresh = UserSubscription::lockForUpdate()->find($sub->id);
                    if (!$fresh || $fresh->status !== UserSubscription::STATUS_ACTIVE) {
                        return;
                    }
                    if ($fresh->ends_at === null || $fresh->ends_at->greaterThan($now)) {
                        return;
                    }

                    $fresh->update(['status' => UserSubscription::STATUS_EXPIRED]);
                    event('subscription.expired', [$fresh]);
                    $expired++;
                });
            }
        });

        Log::info('[subscriptions] expiry sweep complete', ['expired' => $expired]);
        $this->info("Expired {$expired} subscription(s).");
        return self::SUCCESS;
    }
}
