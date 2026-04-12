<?php

namespace Modules\Notifications\app\Contracts;

use Illuminate\Notifications\Notification;

/**
 * Single entry point for fan-out. Controllers and event listeners call
 * methods on this contract instead of `$user->notify(...)` directly so
 * "who gets this notification" logic lives in one place.
 */
interface NotificationDispatcher
{
    /** Send to a single user. */
    public function toUser(object $user, Notification $notification): void;

    /** Send to every user with the given spatie role (e.g. `admin`). */
    public function toRole(string $role, Notification $notification): void;

    /** Send to every admin (convenience for `toRole('admin', ...)`). */
    public function toAdmins(Notification $notification): void;

    /** Send to every active user in the system. Use sparingly. */
    public function broadcastToAll(Notification $notification): void;
}
