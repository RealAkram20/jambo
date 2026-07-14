<?php

namespace Modules\Notifications\app\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Models\Guest;
use Throwable;

/**
 * Fans a broadcast out to every verified user, on the worker.
 *
 * Queueing the notifications alone isn't enough: the *iteration* was also
 * happening in-request, so a broadcast still cost one job insert per user
 * before the admin's save could return — fine at 33 users, not at 50,000.
 * Dispatching this single job means the triggering request writes exactly
 * one row and returns, no matter how large the audience grows.
 *
 * The per-user notify() calls are themselves queued (ChannelGatedNotification
 * is ShouldQueue), so one slow push endpoint can't stall the rest of the
 * fan-out and failures retry per-recipient rather than re-broadcasting to
 * everyone.
 */
class BroadcastNotificationToAll implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Notification $notification)
    {
    }

    public function handle(): void
    {
        User::query()
            ->whereNotNull('email_verified_at')
            ->chunkById(500, function ($users) {
                foreach ($users as $user) {
                    $this->safeNotify($user);
                }
            });

        // Anonymous (logged-out) browsers that opted in via the soft-prompt.
        // WebPushChannel iterates every endpoint attached to the singleton,
        // so one notify() covers all guest subscriptions.
        $guest = Guest::singleton();
        if ($guest->pushSubscriptions()->exists()) {
            $this->safeNotify($guest);
        }
    }

    /**
     * One bad recipient must never abort the rest of the broadcast.
     */
    private function safeNotify(object $notifiable): void
    {
        try {
            $notifiable->notify($this->notification);
        } catch (Throwable $e) {
            Log::warning('[notifications] broadcast notify failed', [
                'notifiable_id' => $notifiable->id ?? null,
                'notification' => $this->notification::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
