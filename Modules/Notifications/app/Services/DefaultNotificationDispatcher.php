<?php

namespace Modules\Notifications\app\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Contracts\NotificationDispatcher;
use Modules\Notifications\app\Models\Guest;
use Throwable;

/**
 * The only implementation of NotificationDispatcher Jambo ships today.
 *
 * Wraps the standard `$user->notify(...)` call in a try/catch so a
 * single bad recipient never blocks the rest of a broadcast, and
 * resolves admin recipients via spatie/laravel-permission's `role()`
 * scope on the User query.
 */
class DefaultNotificationDispatcher implements NotificationDispatcher
{
    public function toUser(object $user, Notification $notification): void
    {
        $this->safeNotify($user, $notification);
    }

    public function toRole(string $role, Notification $notification): void
    {
        User::role($role)
            ->each(function (User $user) use ($notification) {
                $this->safeNotify($user, $notification);
            });
    }

    public function toAdmins(Notification $notification): void
    {
        $this->toRole('admin', $notification);
    }

    public function broadcastToAll(Notification $notification): void
    {
        User::query()
            ->whereNotNull('email_verified_at')
            ->each(function (User $user) use ($notification) {
                $this->safeNotify($user, $notification);
            });

        // Fan out to anonymous (logged-out) browsers that opted in via
        // the soft-prompt. WebPushChannel iterates every endpoint
        // attached to the singleton, so one notify() call covers all
        // guest subscriptions.
        $guest = Guest::singleton();
        if ($guest->pushSubscriptions()->exists()) {
            $this->safeNotify($guest, $notification);
        }
    }

    private function safeNotify(object $user, Notification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable $e) {
            Log::warning('[notifications] notify failed', [
                'user_id' => $user->id ?? null,
                'notification' => $notification::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
