<?php

namespace Modules\Notifications\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Contracts\NotificationDispatcher;
use Modules\Notifications\app\Models\NotificationSetting;
use Modules\Notifications\app\Notifications\AdminBroadcastNotification;
use Modules\Notifications\app\Notifications\TestNotification;
use NotificationChannels\WebPush\WebPushChannel;
use Throwable;

/**
 * Admin-side bulk update for global notification switches. The GET-side
 * render lives inside the regular NotificationController@index so the
 * admin sees Inbox + Settings as two tabs on a single page.
 *
 * Guarded by the `auth` + `role:admin` middleware in routes/web.php.
 */
class NotificationSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        // Incoming shape:
        //   settings[payment_received][system] = '1'    (checkbox checked)
        //   settings[payment_received][push]            (absent when unchecked)
        //   ...
        // We fall back to false for any missing channel so toggles read
        // as "off" when an admin unchecks them.
        $incoming = $request->input('settings', []);
        $known = NotificationSetting::knownKeys();
        $now = now();
        $rows = [];

        foreach ($known as $key) {
            $channels = (array) ($incoming[$key] ?? []);
            $rows[] = [
                'key'            => $key,
                'system_enabled' => !empty($channels['system']),
                'push_enabled'   => !empty($channels['push']),
                'email_enabled'  => !empty($channels['email']),
                'updated_at'     => $now,
            ];
        }

        // Single upsert is cheaper than 23 individual updates and keeps
        // the admin's save action atomic.
        NotificationSetting::upsert(
            $rows,
            ['key'],
            ['system_enabled', 'push_enabled', 'email_enabled', 'updated_at'],
        );

        NotificationSetting::forgetAllCache();

        return redirect()
            ->route('notifications.index', ['tab' => 'settings'])
            ->with('success', 'Notification settings saved.');
    }

    /**
     * Fire a single test notification through ONE channel (system, push,
     * or email) to the currently-logged-in admin. Lets you verify each
     * transport end-to-end without triggering a real notification.
     *
     * Bypasses both the admin-global and per-user gates — the whole
     * point of this button is to sanity-check the wire, not to obey
     * preferences.
     */
    public function testChannel(Request $request, string $channel): RedirectResponse
    {
        $user = $request->user();

        $channelMap = [
            'system' => 'database',
            'email'  => 'mail',
            'push'   => WebPushChannel::class,
        ];

        if (!isset($channelMap[$channel])) {
            return back()->with('error', 'Unknown channel.');
        }

        // Guardrails for channels that won't work on this user / server.
        if ($channel === 'email' && empty($user->email)) {
            return back()->with('error', 'Your account has no email address set.');
        }
        if ($channel === 'push') {
            if (!config('webpush.vapid.public_key')) {
                return back()->with('error', 'Push is not configured on this server (missing VAPID keys).');
            }
            if (!method_exists($user, 'pushSubscriptions') || !$user->pushSubscriptions()->exists()) {
                return back()->with('error', 'No push subscription registered on this device. Enable push in your profile first.');
            }
        }

        $notification = new TestNotification(
            title: 'Test ' . ucfirst($channel) . ' notification',
            message: 'If you see this, the ' . $channel . ' channel is wired up correctly. Fired at ' . now()->format('H:i:s') . '.',
            channels: [$channelMap[$channel]],
        );

        try {
            $user->notify($notification);
        } catch (Throwable $e) {
            Log::warning('[notifications] test-channel failed', [
                'channel' => $channel,
                'error'   => $e->getMessage(),
            ]);
            return redirect()
                ->route('notifications.index', ['tab' => 'settings'])
                ->with('error', ucfirst($channel) . ' test failed: ' . $e->getMessage());
        }

        $successMessage = match ($channel) {
            'system' => 'System test sent. Check the bell dropdown — you should see a new notification.',
            'email'  => 'Email test queued to ' . $user->email . '. Check your inbox (or the mail log).',
            'push'   => 'Push test dispatched. Your browser should display a notification shortly.',
        };

        return redirect()
            ->route('notifications.index', ['tab' => 'settings'])
            ->with('success', $successMessage);
    }

    /**
     * Dispatch an AdminBroadcastNotification to a selected audience.
     * Gated by NotificationSetting::channelsFor('admin_broadcast') — if
     * admin-global switches for this key are all off, the notifications
     * are silently dropped. The admin sees a flash on the Broadcast tab.
     */
    public function sendBroadcast(Request $request, NotificationDispatcher $dispatcher): RedirectResponse
    {
        $data = $request->validate([
            'subject'    => ['required', 'string', 'max:150'],
            'body'       => ['required', 'string', 'max:2000'],
            'audience'   => ['required', 'in:all,admins,users'],
            'link_url'   => ['nullable', 'url', 'max:500'],
            'link_label' => ['nullable', 'string', 'max:60'],
        ]);

        $notification = new AdminBroadcastNotification(
            subject:   $data['subject'],
            body:      $data['body'],
            linkUrl:   $data['link_url']   ?? null,
            linkLabel: $data['link_label'] ?? null,
        );

        try {
            match ($data['audience']) {
                'admins' => $dispatcher->toAdmins($notification),
                'users'  => $dispatcher->toRole('user', $notification),
                default  => $dispatcher->broadcastToAll($notification),
            };
        } catch (Throwable $e) {
            Log::warning('[notifications] broadcast failed', [
                'audience' => $data['audience'],
                'error'    => $e->getMessage(),
            ]);
            return redirect()
                ->route('notifications.index', ['tab' => 'broadcast'])
                ->with('error', 'Broadcast failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('notifications.index', ['tab' => 'broadcast'])
            ->with('success', 'Broadcast sent to ' . $data['audience'] . '.');
    }
}
