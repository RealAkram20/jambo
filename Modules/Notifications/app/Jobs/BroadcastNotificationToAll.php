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
 * Fans a broadcast out to a chosen audience, on the worker.
 *
 * Queueing the notifications alone isn't enough: the *iteration* was also
 * happening in-request, so a broadcast still cost one job insert per user
 * before the admin's save could return — fine at 33 users, not at 50,000.
 * At that size the in-request loop overran PHP's max_execution_time, and a
 * timeout fatal isn't a catchable Throwable, so the controller's try/catch
 * couldn't stop it 500-ing. Dispatching this single job means the
 * triggering request writes exactly one row and returns, no matter how
 * large the audience grows.
 *
 * `$audience` mirrors the Broadcast form: 'all' (every verified user),
 * 'users' (regular role only) or 'admins' (admin role only). The role
 * scope is resolved here on the worker so a missing/unseeded role fails
 * the job for a retry instead of 500-ing the admin.
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

    public function __construct(
        public readonly Notification $notification,
        public readonly string $audience = 'all',
    ) {
    }

    public function handle(): void
    {
        $query = User::query()->whereNotNull('email_verified_at');

        if ($this->audience === 'admins' || $this->audience === 'users') {
            try {
                $query->role($this->audience === 'admins' ? 'admin' : 'user');
            } catch (Throwable $e) {
                Log::warning('[notifications] broadcast role scope failed', [
                    'audience' => $this->audience,
                    'error' => $e->getMessage(),
                ]);
                return;
            }
        }

        $query->chunkById(500, function ($users) {
            foreach ($users as $user) {
                $this->safeNotify($user);
            }
        });

        // Anonymous (logged-out) browsers that opted in via the soft-prompt.
        // They have no role, so they belong to broadcasts aimed at the
        // general population — never an admins-only one. WebPushChannel
        // iterates every endpoint attached to the singleton, so one notify()
        // covers all guest subscriptions.
        if ($this->audience === 'all' || $this->audience === 'users') {
            $guest = Guest::singleton();
            if ($guest->pushSubscriptions()->exists()) {
                $this->safeNotify($guest);
            }
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
