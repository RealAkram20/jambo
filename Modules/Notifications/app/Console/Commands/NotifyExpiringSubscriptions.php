<?php

namespace Modules\Notifications\app\Console\Commands;

use Illuminate\Console\Command;
use Modules\Notifications\app\Events\SubscriptionExpiring;
use Modules\Subscriptions\app\Models\UserSubscription;

/**
 * Scans active subscriptions whose `ends_at` falls on one of the
 * reminder milestones (7, 3, 1 day out) and fires a SubscriptionExpiring
 * event for each. The event subscriber turns that into a user-facing
 * notification, gated by the `subscription_expiring` switches.
 *
 * Run daily. Add to app/Console/Kernel.php or the module's schedule:
 *
 *   $schedule->command('notifications:subscriptions-expiring')
 *       ->dailyAt('09:00');
 *
 * Idempotency: the notification `type` column already distinguishes
 * this notification class, and users can mark-all-read between runs.
 * For stricter de-duping (only fire once per milestone per sub) we
 * could add a `last_expiry_reminder_at` column — deferred until the
 * feature is actually in use.
 */
class NotifyExpiringSubscriptions extends Command
{
    protected $signature = 'notifications:subscriptions-expiring {--days=7,3,1 : Comma-separated reminder days}';

    protected $description = 'Notify users whose active subscription ends in N days.';

    public function handle(): int
    {
        $milestones = array_filter(array_map('intval', explode(',', (string) $this->option('days'))));
        if (empty($milestones)) $milestones = [7, 3, 1];

        $total = 0;

        foreach ($milestones as $days) {
            $start = now()->startOfDay()->addDays($days);
            $end   = $start->copy()->endOfDay();

            $subs = UserSubscription::with(['user', 'tier'])
                ->where('status', UserSubscription::STATUS_ACTIVE)
                ->whereBetween('ends_at', [$start, $end])
                ->get();

            foreach ($subs as $sub) {
                if (!$sub->user) continue;

                event(new SubscriptionExpiring(
                    user: $sub->user,
                    planName: $sub->tier->name ?? 'Your',
                    daysRemaining: $days,
                ));

                $total++;
            }
        }

        $this->info("Dispatched {$total} expiring-subscription reminder(s).");
        return self::SUCCESS;
    }
}
