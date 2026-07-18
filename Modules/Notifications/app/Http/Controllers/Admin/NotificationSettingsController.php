<?php

namespace Modules\Notifications\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Notifications\app\Jobs\BroadcastNotificationToAll;
use Modules\Notifications\app\Models\NotificationAudienceSetting;
use Modules\Notifications\app\Models\NotificationPreference;
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

        // Single upsert is cheaper than N individual updates and keeps
        // the admin's save action atomic.
        NotificationSetting::upsert(
            $rows,
            ['key'],
            ['system_enabled', 'push_enabled', 'email_enabled', 'updated_at'],
        );

        NotificationSetting::forgetAllCache();

        // Per-audience matrix for role-targeted types. Shape:
        //   settings_audience[user_signup][admin][system] = '1'
        // Only rows whose (key, audience) are legitimate per definitions()
        // are written, so a tampered payload can't invent combinations.
        $incomingAudience = (array) $request->input('settings_audience', []);
        $audienceRows = [];
        foreach (NotificationSetting::definitions() as $group) {
            foreach ($group['items'] as $item) {
                $audiences = NotificationAudienceSetting::audiencesForTag($item['audience']);
                foreach ($audiences as $aud) {
                    $ch = (array) ($incomingAudience[$item['key']][$aud] ?? []);
                    $audienceRows[] = [
                        'notification_key' => $item['key'],
                        'audience'         => $aud,
                        'in_app_enabled'   => !empty($ch['system']),
                        'push_enabled'     => !empty($ch['push']),
                        'email_enabled'    => !empty($ch['email']),
                        'updated_at'       => $now,
                    ];
                }
            }
        }

        if ($audienceRows !== []) {
            NotificationAudienceSetting::upsert(
                $audienceRows,
                ['notification_key', 'audience'],
                ['in_app_enabled', 'push_enabled', 'email_enabled', 'updated_at'],
            );
            NotificationAudienceSetting::forgetAllCache();
        }

        return redirect()
            ->route('notifications.index', ['tab' => 'settings'])
            ->with('success', 'Notification settings saved.');
    }

    /**
     * Save the current admin's OWN per-type opt-outs (the "My preferences"
     * tab). Sparse: a type left fully on is deleted from the table so it
     * inherits the platform default; any channel switched off is stored.
     *
     * Only types that reach the admin audience can be tuned here — the
     * incoming payload is intersected with that allow-list so a hand-
     * crafted request can't create preference rows for arbitrary keys.
     * This never touches the global/audience matrix, so an admin can only
     * narrow what THEY receive, never what other admins or users receive.
     */
    public function updateMyPreferences(Request $request): RedirectResponse
    {
        $user = $request->user();
        $incoming = (array) $request->input('prefs', []);

        // Only the types this admin's audience is actually granted (routed to
        // it AND left on in the super-admin matrix). Iterating the full tag
        // list instead would let a type the super-admin switched off — and
        // which is therefore NOT rendered in the form — read back as "all
        // channels unchecked" and get saved as a deny-override, keeping it
        // hidden even after the super-admin re-enables it. A channel the
        // super-admin disabled is likewise ignored here (clamped to the
        // ceiling), so the admin can only ever narrow, never widen.
        $audience = NotificationAudienceSetting::audienceFor($user);
        $ceiling  = NotificationAudienceSetting::grantedChannelsFor($audience);

        foreach ($ceiling as $key => $allow) {
            $ch = (array) ($incoming[$key] ?? []);
            $inApp = !empty($ch['system']) && $allow['in_app'];
            $push  = !empty($ch['push'])   && $allow['push'];
            $email = !empty($ch['email'])  && $allow['email'];

            // "Everything the super-admin allows is on" = no personal
            // override → drop the row so this type inherits the ceiling.
            if ($inApp === $allow['in_app'] && $email === $allow['email'] && $push === $allow['push']) {
                NotificationPreference::where('user_id', $user->id)
                    ->where('notification_key', $key)
                    ->delete();
                continue;
            }

            NotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'notification_key' => $key],
                ['in_app_enabled' => $inApp, 'email_enabled' => $email, 'push_enabled' => $push],
            );
        }

        NotificationPreference::forgetCache($user->id);

        return redirect()
            ->route('notifications.index', ['tab' => 'my-preferences'])
            ->with('success', 'Your notification preferences were saved.');
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
     * Queue an AdminBroadcastNotification to a selected audience.
     *
     * The whole fan-out is handed to BroadcastNotificationToAll so the
     * request writes one job row and returns — the per-recipient walk
     * (which used to run in-request for the users/admins audiences and
     * time out on large sites) happens on the worker.
     *
     * Delivery still passes the four-layer gate (the admin_broadcast site
     * switches + each recipient's own preferences). The `channels` picker
     * narrows that further to just the transports ticked on the form, and
     * an optional image rides along on the in-app and push renders.
     */
    public function sendBroadcast(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject'    => ['required', 'string', 'max:150'],
            'body'       => ['required', 'string', 'max:2000'],
            'audience'   => ['required', 'in:all,admins,users'],
            'channels'   => ['required', 'array', 'min:1'],
            'channels.*' => ['in:system,email,push'],
            'link_url'   => ['nullable', 'url', 'max:500'],
            'link_label' => ['nullable', 'string', 'max:60'],
            'image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:3072'],
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('broadcasts', 'public');
            $imageUrl = Storage::disk('public')->url($path);
        }

        $notification = new AdminBroadcastNotification(
            subject:   $data['subject'],
            body:      $data['body'],
            linkUrl:   $data['link_url']   ?? null,
            linkLabel: $data['link_label'] ?? null,
            imageUrl:  $imageUrl,
            channels:  $data['channels'],
        );

        try {
            BroadcastNotificationToAll::dispatch($notification, $data['audience']);
        } catch (Throwable $e) {
            Log::warning('[notifications] broadcast dispatch failed', [
                'audience' => $data['audience'],
                'error'    => $e->getMessage(),
            ]);
            return redirect()
                ->route('notifications.index', ['tab' => 'broadcast'])
                ->with('error', 'Broadcast failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('notifications.index', ['tab' => 'broadcast'])
            ->with('success', 'Broadcast queued for ' . $data['audience'] . ' — delivery runs in the background.');
    }
}
