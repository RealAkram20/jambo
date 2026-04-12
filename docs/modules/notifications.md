# Notifications Module — Three-Channel Pattern

A multi-channel notification system for Laravel that delivers the same
message to users via three independent channels:

1. **System (in-app)** — database-backed, shown in a header bell dropdown
   and a dedicated `/notifications` index page
2. **Email** — transactional, via Laravel's default mail renderer or a
   custom Markdown / Blade template
3. **Push** — browser push via the Web Push API, with VAPID keys and a
   service worker

Inspired by the working implementation at
[github.com/RealAkram20/Forever-Loved-updates](https://github.com/RealAkram20/Forever-Loved-updates)
but rebuilt to use Laravel's **built-in** `Notification` facade + channel
system rather than a custom model. The trade-off: more boilerplate per
notification class, but every channel is a one-line addition to `via()`
and every class can be queued (`ShouldQueue`) for free.

---

## Design decisions

### Laravel's built-in Notification system (not a custom model)

Forever used a custom `Notification` Eloquent model with its own table
(`notifications` with `type`, `title`, `message`, `icon`, `action_url`,
`data JSON`). We instead use Laravel's standard approach:

- Each notification is a PHP class under
  `Modules/Notifications/app/Notifications/*`
- The class declares `via($notifiable)` returning an array of channels
- `toDatabase()`, `toMail()`, `toWebPush()` build the payload per channel
- `$user->notify(new FooNotification($payload))` dispatches to all
  channels declared in `via()`
- Laravel's default `notifications` table holds the database-channel
  payloads; queries go through `$user->notifications` / `$user->unreadNotifications`
- `ShouldQueue` on the notification class makes the whole fan-out async

Why go this route:

- Less bespoke code to maintain. Laravel's channel system is battle-tested.
- Adding a new channel is a single line in `via()` plus one `toX()` method
- `Notifiable` trait is already on the `User` model; no extra setup
- Plays nicely with `laravel-echo` / broadcasting if we ever want
  real-time unread-count updates
- Queueable without writing any extra code

What we give up:

- Opaque `data` JSON column — can't filter "all payment notifications"
  without also matching the fully-qualified class name in the `type`
  column. Not a real limitation — the type column is how Laravel
  expects you to filter.
- Each notification class is ~40 lines of boilerplate vs Forever's
  inline `NotificationService::notifyX()` approach. We accept this.

### User preferences live on the `users` table, not a separate table

Three boolean columns added via a migration in this module:

- `in_app_notifications_enabled` (default `true`)
- `email_notifications_enabled` (default `true`)
- `push_notifications_enabled` (default `true`)

Each notification class checks the relevant column inside `via()`:

```php
public function via($notifiable): array
{
    $channels = [];
    if ($notifiable->in_app_notifications_enabled) $channels[] = 'database';
    if ($notifiable->email_notifications_enabled) $channels[] = 'mail';
    if ($notifiable->push_notifications_enabled) $channels[] = WebPushChannel::class;
    return $channels;
}
```

Users toggle these in their account settings. Admins can force-send by
ignoring the flags in an admin broadcast notification class.

### One service class for admin broadcasts + ad-hoc dispatch

A thin `NotificationDispatcher` service in
`Modules/Notifications/app/Services/NotificationDispatcher.php` exposes:

- `toUser(User $user, Notification $notification)`
- `toAdmins(Notification $notification)` — resolves all users with the
  `admin` role via spatie/laravel-permission, loops `notify()` over them
- `toRole(string $role, Notification $notification)`
- `broadcast(Notification $notification)` — every active user

Controllers and listeners call the dispatcher instead of calling
`$user->notify()` directly. This keeps the "who gets this" logic in one
place and lets us add features like "skip users who muted this
category" in one commit instead of sixty.

---

## File layout

```
Modules/Notifications/
├── app/
│   ├── Contracts/
│   │   └── NotificationDispatcher.php          interface
│   ├── Http/
│   │   └── Controllers/
│   │       └── NotificationController.php      index, dropdown, read, read-all, destroy
│   ├── Listeners/
│   │   └── SendPaymentReceivedNotification.php listens on `payment.completed`
│   ├── Notifications/
│   │   ├── PaymentReceivedNotification.php
│   │   ├── SubscriptionActivatedNotification.php
│   │   ├── SubscriptionExpiringNotification.php
│   │   ├── AdminBroadcastNotification.php
│   │   └── TestNotification.php                for smoke tests / settings UI
│   ├── Providers/
│   │   ├── NotificationsServiceProvider.php    registers listener + binds dispatcher
│   │   └── RouteServiceProvider.php
│   └── Services/
│       └── DefaultNotificationDispatcher.php   implements the contract
├── config/config.php                            channel toggles, default from-address
├── database/migrations/
│   ├── xxxx_create_notifications_table.php     Laravel's default shape
│   └── xxxx_add_notification_prefs_to_users.php three bool columns + default true
├── resources/views/
│   ├── admin/
│   │   ├── index.blade.php                     /notifications list
│   │   └── settings.blade.php                  admin preferences + broadcast form (later)
│   ├── emails/
│   │   └── default.blade.php                   shared Markdown / Blade mail template
│   └── partials/
│       └── bell-dropdown.blade.php             header bell dropdown markup, @include()'d from layouts.app
└── routes/web.php
```

---

## Database schema

### `notifications` table (Laravel default)

```php
Schema::create('notifications', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->string('type');                 // fully-qualified notification class
    $t->morphs('notifiable');           // typically User
    $t->text('data');                   // JSON payload
    $t->timestamp('read_at')->nullable();
    $t->timestamps();
});
```

Equivalent to running `php artisan notifications:table` on a fresh
Laravel install. We ship our own migration so the whole thing lives
inside the Notifications module and disappears with
`module:disable Notifications`.

### `users` table — extension

```php
Schema::table('users', function (Blueprint $t) {
    $t->boolean('in_app_notifications_enabled')->default(true)->after('remember_token');
    $t->boolean('email_notifications_enabled')->default(true)->after('in_app_notifications_enabled');
    $t->boolean('push_notifications_enabled')->default(false)->after('email_notifications_enabled');
});
```

Push defaults to `false` — the user must explicitly opt in via the
browser permission prompt before we try to deliver.

### `push_subscriptions` table (later, when channel 3 ships)

```php
Schema::create('push_subscriptions', function (Blueprint $t) {
    $t->id();
    $t->foreignId('user_id')->constrained()->cascadeOnDelete();
    $t->string('endpoint', 500);
    $t->string('p256dh_key');
    $t->string('auth_token');
    $t->string('content_encoding')->default('aes128gcm');
    $t->timestamps();
    $t->unique(['user_id', 'endpoint']);
});
```

---

## Routes

```php
Route::middleware('auth')
    ->prefix('notifications')
    ->name('notifications.')
    ->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/dropdown', [NotificationController::class, 'dropdown'])->name('dropdown');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });

// Admin-only endpoints (broadcast form + test push) come later
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/notifications')
    ->name('admin.notifications.')
    ->group(function () {
        Route::get('/broadcast', [BroadcastController::class, 'create'])->name('broadcast.create');
        Route::post('/broadcast', [BroadcastController::class, 'store'])->name('broadcast.store');
    });
```

---

## Writing a new notification class

Example — payment received:

```php
namespace Modules\Notifications\app\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Payments\app\Models\PaymentOrder;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PaymentOrder $order)
    {
    }

    public function via($notifiable): array
    {
        $channels = [];
        if ($notifiable->in_app_notifications_enabled ?? true) {
            $channels[] = 'database';
        }
        if ($notifiable->email_notifications_enabled ?? true) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Payment received',
            'message' => "Your payment of {$this->order->currency} " .
                number_format($this->order->amount, 2) . ' was successful.',
            'icon' => 'ph-credit-card',
            'colour' => 'success',
            'action_url' => route('payment.complete', ['ref' => $this->order->merchant_reference]),
            'order_id' => $this->order->id,
            'merchant_reference' => $this->order->merchant_reference,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Payment confirmation — Jambo')
            ->greeting("Hi {$notifiable->first_name},")
            ->line('Thanks for your payment. Your receipt is below.')
            ->line("**Reference:** {$this->order->merchant_reference}")
            ->line("**Amount:** {$this->order->currency} " . number_format($this->order->amount, 2))
            ->action('View details', route('payment.complete', ['ref' => $this->order->merchant_reference]))
            ->salutation('— The Jambo team');
    }
}
```

To dispatch:

```php
$user->notify(new PaymentReceivedNotification($order));
```

Or via the dispatcher service:

```php
app(NotificationDispatcher::class)->toUser($user, new PaymentReceivedNotification($order));
app(NotificationDispatcher::class)->toAdmins(new AdminBroadcastNotification(...));
```

---

## How the header bell dropdown works

`resources/views/components/partials/header.blade.php` already has a
bell icon with a placeholder markup block (line ~135). We replace the
static list inside with:

1. An outer `<div x-data="bell()">` (or a small vanilla-JS component) that
   fetches `/notifications/dropdown` every 60 seconds
2. A badge `<span>` bound to the returned `unread_count`
3. A list of 8 most-recent notifications, each with title / message / icon
4. A "Mark all as read" button
5. A "View all" link to `/notifications`

The `/notifications/dropdown` endpoint returns JSON:

```json
{
    "unread_count": 3,
    "recent": [
        {
            "id": "9b5c...",
            "type": "payment_received",
            "title": "Payment received",
            "message": "Your payment of KES 499.00 was successful.",
            "icon": "ph-credit-card",
            "colour": "success",
            "action_url": "/payment/complete?ref=JAM-1-ABC",
            "created_at_human": "5 minutes ago",
            "read_at": null
        }
    ]
}
```

Clicking an item in the dropdown:

1. Sends `POST /notifications/{id}/read` in the background
2. Navigates the browser to `action_url`

"Mark all as read" sends `POST /notifications/mark-all-read` and
refreshes the list.

---

## Event → notification wiring

Jambo's `Payments` module already fires `payment.completed` when an
order transitions to `completed`. The Notifications service provider
registers a listener:

```php
// Modules/Notifications/app/Providers/NotificationsServiceProvider.php

public function boot(): void
{
    // ...existing boot code...

    Event::listen('payment.completed', [
        \Modules\Notifications\app\Listeners\SendPaymentReceivedNotification::class,
        'handle'
    ]);
}
```

The listener calls the dispatcher:

```php
namespace Modules\Notifications\app\Listeners;

use Modules\Notifications\app\Contracts\NotificationDispatcher;
use Modules\Notifications\app\Notifications\PaymentReceivedNotification;

class SendPaymentReceivedNotification
{
    public function __construct(private readonly NotificationDispatcher $dispatcher)
    {
    }

    public function handle($order, string $source): void
    {
        $this->dispatcher->toUser($order->user, new PaymentReceivedNotification($order));
        $this->dispatcher->toAdmins(new PaymentReceivedNotification($order));
    }
}
```

Same pattern for every future event: `subscription.activated`,
`subscription.expiring_soon`, `subscription.cancelled`, etc. Each
listener lives in `Modules/Notifications/app/Listeners/`.

---

## Channels roadmap

| Channel | Status | Notes |
|---|---|---|
| **database** (system / in-app) | v1 | First channel shipped. Powers the bell dropdown and the `/notifications` page. |
| **mail** | v2 | Add `'mail'` to `via()` and `toMail()` to existing notification classes. No new classes needed. Already-queued notifications fan out email at the same time as database writes. |
| **broadcast** | deferred | Needed if we want the bell dropdown to update live without a 60-second poll. Requires Pusher/Soketi + Laravel Echo on the frontend. |
| **webpush** | deferred | `laravel-notification-channels/webpush` package, VAPID keys, service worker, user opt-in flow. |

---

## Gotchas and clever bits

- **Queue driver**: `.env` defaults to `sync`. For production, flip to
  `database` (or `redis`) so notification fan-out doesn't block the
  HTTP response that triggered it. Forever ran synchronous and hit
  occasional request timeouts on bulk broadcasts.
- **Default `via()` for users who haven't saved preferences**: The
  three user preference columns default to `true` on migration, but we
  still defensively read `?? true` in `via()` so a user with NULL
  preferences (from an older test DB) still gets notifications.
- **Read-at timestamp**: Laravel's `$user->unreadNotifications`
  collection filters on `read_at IS NULL`. Marking as read calls
  `$notification->markAsRead()`. Don't touch the `data` column by hand.
- **`type` column filtering**: To find all payment notifications,
  query `where('type', PaymentReceivedNotification::class)`. The type
  is the fully-qualified class name.
- **Notification deletion**: Don't actually delete rows. Future
  analytics will want the history. Provide a "dismiss" that flips
  `read_at` but keeps the row. If you really must delete, do it in a
  scheduled cleanup (`prune` command) that removes read notifications
  older than 90 days.
- **Polymorphic notifiable**: Laravel's `morphs('notifiable')` lets us
  notify models other than User (e.g. `Admin` if it becomes its own
  model). We default to User for now, but the schema supports the
  change without a migration.
- **Bell dropdown 60s polling**: Real-time updates come in channel 3
  (broadcast). Until then, 60 seconds is fine — anything tighter hits
  the DB for every admin tab in every browser.

---

## How to rebuild in a new project

1. `php artisan module:make Notifications` (if using nwidart modules)
2. Copy the file layout above
3. Run the two migrations: `notifications` table + `users` prefs columns
4. Register the service provider in `config/app.php` (nwidart does this
   automatically via `module.json`)
5. In `routes/web.php`, include the module's web routes (nwidart does
   this automatically via the module's `RouteServiceProvider`)
6. Write one example notification class (`TestNotification`) to
   verify the pipeline
7. Dispatch from `tinker`:
   `\App\Models\User::first()->notify(new \Modules\Notifications\app\Notifications\TestNotification());`
8. Check the `notifications` table for the row
9. Wire the bell dropdown markup in the admin layout
10. Add the listener for `payment.completed` (or whatever event your
    app fires first)
11. Ship channel 1 (`database`), then layer channel 2 (`mail`), then
    channel 3 (`webpush`) in separate commits
