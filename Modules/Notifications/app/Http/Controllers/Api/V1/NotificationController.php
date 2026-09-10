<?php

namespace Modules\Notifications\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\Notifications\app\Models\NotificationPreference;
use Modules\Notifications\app\Support\NotificationCategories;
use Modules\Streaming\app\Models\Device;

/**
 * Notifications for the app, and the FCM token registry behind push.
 *
 * **Push is an accelerator, never the transport.** Everything a push says has
 * to be independently readable here, because a push may simply not arrive: an
 * OEM battery manager killed the process, the token went stale, the handset
 * was offline for a day. `GET /notifications` is that fallback, and the app
 * must be usable from it with push disabled entirely
 * (`~/.claude/skills/background-push`, rule 3).
 *
 * The token lives on the `devices` row rather than in its own table, because
 * the standard's first rule is that it must be deleted on sign-out — and every
 * path that ends a session already runs through `Device::revoke()`. A shared
 * handset keeping the previous account's token would deliver their
 * notifications, on the lock screen, to whoever is holding the phone.
 *
 * `push_subscriptions` is untouched: that table is web-push shaped and belongs
 * to the browser.
 */
class NotificationController extends Controller
{
    private const PER_PAGE = 30;

    /**
     * The viewer's notifications, newest first.
     *
     * Carries `unread_count` so a badge costs one request rather than two.
     *
     * **`category` filters here rather than in the app**, because the list is
     * cursor-paginated: filtering the thirty rows the app happens to be
     * holding would show a viewer four Movies notifications and hide the fifth
     * until they scrolled far enough to load it. An unknown category is a 422
     * rather than a silently unfiltered list — a filter that quietly does
     * nothing is worse than one that says it cannot.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'category' => ['nullable', 'string', Rule::in(NotificationCategories::all())],
        ]);

        // The relation orders by created_at; id breaks ties so a cursor does
        // not drop notifications sent in the same second.
        $query = $user->notifications()->orderByDesc('id');

        if (($data['category'] ?? null) !== null) {
            $query->whereIn('type', NotificationCategories::typesFor($data['category']));
        }

        $rows = $query->cursorPaginate(self::PER_PAGE);

        return ApiResponse::ok([
            // Deliberately NOT filtered by category. This is the bell's badge,
            // and a badge that changed when a chip was pressed would be
            // reporting the chip rather than the inbox.
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $rows->getCollection()->map(fn ($notification) => $this->card($notification))->values(),
            'next_cursor' => $rows->nextCursor()?->encode(),
        ]);
    }

    /** Mark one read. Idempotent: an already-read one is a success. */
    public function read(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->find($id);

        if (! $notification) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That notification does not exist.');
        }

        $notification->markAsRead();

        return ApiResponse::ok(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return ApiResponse::ok(['unread_count' => 0], 'All caught up.');
    }

    /**
     * Delete one. Idempotent: deleting one already gone satisfies the intent,
     * and a retry over a dropped connection must not fail.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->find($id)?->delete();

        return ApiResponse::ok(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return ApiResponse::ok(['unread_count' => 0], 'Notifications cleared.');
    }

    // ── preferences ──────────────────────────────────────────────────

    /**
     * What this viewer has opted into.
     *
     * Two layers, and they are not the same thing. `channels` is the viewer's
     * global per-channel switch — the three columns on `users` that form layer
     * 4 of the gate in `ChannelGatedNotification`, and the three switches the
     * website's own "Delivery preferences" card renders. `preferences` is the
     * per-notification-type opt-out, which can only narrow what `channels`
     * allows.
     *
     * The channels were added 2026-09-09 for the app's notification settings
     * screen: `/api/v1` exposed the fine-grained layer and not the coarse one,
     * so an app could refuse one kind of email but not switch email off.
     */
    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $rows = NotificationPreference::where('user_id', $user->id)->get();

        return ApiResponse::ok([
            'channels' => [
                'in_app' => (bool) $user->in_app_notifications_enabled,
                'email' => (bool) $user->email_notifications_enabled,
                'push' => (bool) $user->push_notifications_enabled,
            ],
            // Whether the address the emails would go to is confirmed. The
            // website says this beside its Email switch, and it is a fact the
            // switch itself cannot show.
            'email_verified' => $user->email_verified_at !== null,
            'preferences' => $rows->map(fn (NotificationPreference $p) => [
                'key' => $p->notification_key,
                'in_app' => (bool) $p->in_app_enabled,
                'email' => (bool) $p->email_enabled,
                'push' => (bool) $p->push_enabled,
            ])->values(),
        ]);
    }

    /**
     * Change either layer, or both.
     *
     * Both keys are optional and a request carrying neither is refused: a PUT
     * that validates and changes nothing reads as success to the app, and the
     * switch stays where the viewer put it while the server disagrees.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channels' => ['nullable', 'array'],
            'channels.in_app' => ['nullable', 'boolean'],
            'channels.email' => ['nullable', 'boolean'],
            'channels.push' => ['nullable', 'boolean'],
            'preferences' => ['nullable', 'array'],
            'preferences.*.key' => ['required', 'string', 'max:100'],
            'preferences.*.in_app' => ['nullable', 'boolean'],
            'preferences.*.email' => ['nullable', 'boolean'],
            'preferences.*.push' => ['nullable', 'boolean'],
        ]);

        if (($data['channels'] ?? null) === null && ($data['preferences'] ?? null) === null) {
            return ApiResponse::error(
                ApiErrorCode::ValidationFailed,
                'Send channels, preferences, or both.',
            );
        }

        $user = $request->user();

        if (($data['channels'] ?? null) !== null) {
            $columns = [
                'in_app' => 'in_app_notifications_enabled',
                'email' => 'email_notifications_enabled',
                'push' => 'push_notifications_enabled',
            ];

            $changes = [];
            foreach ($columns as $key => $column) {
                // array_key_exists, not ??: a switch turned OFF sends false,
                // and false would fall through a null-coalesce as "not sent".
                if (array_key_exists($key, $data['channels'])) {
                    $changes[$column] = (bool) $data['channels'][$key];
                }
            }

            if ($changes !== []) {
                $user->forceFill($changes)->save();
            }
        }

        foreach ($data['preferences'] ?? [] as $preference) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'notification_key' => $preference['key']],
                [
                    'in_app_enabled' => (bool) ($preference['in_app'] ?? true),
                    'email_enabled' => (bool) ($preference['email'] ?? true),
                    'push_enabled' => (bool) ($preference['push'] ?? true),
                ],
            );
        }

        return $this->preferences($request);
    }

    // ── push registration ────────────────────────────────────────────

    /**
     * Register this install's FCM token.
     *
     * Logged unconditionally, BEFORE any guard. Otherwise "the app never
     * registered" and "it registered and there was no device row" leave
     * identical evidence — nothing — and they need completely different fixes.
     * That is the most expensive lesson in the push standard: KangaruRide
     * dispatched thirty-eight job offers to nobody because a token table was
     * empty and no code was documented as having anything to say about it.
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'push_enabled' => ['nullable', 'boolean'],
        ]);

        $device = Device::forToken($request->user()->currentAccessToken());

        Log::info('[push] token registration', [
            'user_id' => $request->user()->id,
            'device_uuid' => $device?->uuid,
            'has_device_row' => $device !== null,
            // Never the whole token: it is a credential for reaching a handset.
            'token_tail' => substr($data['token'], -8),
        ]);

        if (! $device) {
            // An access token with no device row is not something the app
            // produces. Refusing is honest: there is nowhere to put the token,
            // so quietly succeeding would create exactly the empty registry
            // the standard warns about.
            return ApiResponse::error(
                ApiErrorCode::DeviceNotFound,
                'This session is not registered as a device, so push cannot be enabled for it.',
            );
        }

        $device->registerPushToken($data['token']);

        if (array_key_exists('push_enabled', $data) && $data['push_enabled'] !== null) {
            $device->forceFill(['push_enabled' => (bool) $data['push_enabled']])->save();
        }

        return ApiResponse::ok([
            'registered' => true,
            'push_enabled' => (bool) $device->fresh()->push_enabled,
        ], 'Push notifications are on for this device.');
    }

    /**
     * Stop pushing to this install.
     *
     * Signing out does this too, through Device::revoke(). This is for the
     * viewer who wants the app but not the notifications.
     */
    public function unregisterPushToken(Request $request): JsonResponse
    {
        $device = Device::forToken($request->user()->currentAccessToken());

        Log::info('[push] token removal', [
            'user_id' => $request->user()->id,
            'device_uuid' => $device?->uuid,
            'has_device_row' => $device !== null,
        ]);

        $device?->forgetPushToken();

        return ApiResponse::ok(['registered' => false], 'Push notifications are off for this device.');
    }

    /** @return array<string, mixed> */
    private function card($notification): array
    {
        $data = (array) $notification->data;

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'icon' => $data['icon'] ?? 'ph-bell',
            // The site paints the icon tile from this — bg-{colour}-subtle on
            // text-{colour}-emphasis — so the app has to receive it to wear
            // the same tile rather than colouring every icon the same.
            'colour' => $data['colour'] ?? 'primary',
            'image_url' => $data['image'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            // Which filter chip this row sits under, or null for the rows that
            // belong under All alone. Derived from the stored class name, so
            // notifications sent before the chips existed still answer it.
            'category' => NotificationCategories::forType($notification->type),
            'read' => $notification->read_at !== null,
            'created_at' => optional($notification->created_at)->toIso8601String(),
        ];
    }
}
