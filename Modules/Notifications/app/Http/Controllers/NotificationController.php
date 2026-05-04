<?php

namespace Modules\Notifications\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Notifications\app\Models\Guest;
use Modules\Notifications\app\Models\NotificationSetting;

/**
 * User-facing notification endpoints:
 *
 *   GET    /notifications             full list, paginated
 *   GET    /notifications/dropdown    JSON: unread count + 8 most recent
 *   POST   /notifications/{id}/read   mark one as read
 *   POST   /notifications/mark-all-read
 *   DELETE /notifications/{id}        drop one from the list
 *
 * All behind the `auth` middleware. Works equally well for admins and
 * regular users — the same bell dropdown pattern in the header polls
 * the same endpoint.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Admins land on the dashboard-styled index. Regular users get
        // their inbox inside the profile hub so the chrome matches the
        // rest of the site (see feedback_admin_vs_user_separation).
        if (!$user->hasRole('admin')) {
            return redirect()->route('profile.notifications', [
                'username' => $user->username,
            ]);
        }

        $notifications = $user->notifications()
            ->paginate(20)
            ->withQueryString();

        // Settings tab: load the global switches keyed by notification
        // key so the blade can look up current state per-row without a
        // query per row.
        $settingRows = NotificationSetting::all()->keyBy('key');
        $definitions = NotificationSetting::definitions();

        $validTabs = ['inbox', 'settings', 'broadcast'];
        $activeTab = in_array($request->query('tab'), $validTabs, true)
            ? $request->query('tab')
            : 'inbox';

        return view('notifications::index', [
            'notifications' => $notifications,
            'unreadCount'   => $user->unreadNotifications()->count(),
            'definitions'   => $definitions,
            'settingRows'   => $settingRows,
            'activeTab'     => $activeTab,
        ]);
    }

    public function dropdown(Request $request): JsonResponse
    {
        $user = $request->user();

        $recent = $user->notifications()
            ->take(8)
            ->get()
            ->map(function ($notification) {
                $data = (array) $notification->data;
                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? 'Notification',
                    'message' => $data['message'] ?? '',
                    'icon' => $data['icon'] ?? 'ph-bell',
                    'image' => $data['image'] ?? null,
                    'colour' => $data['colour'] ?? 'primary',
                    'action_url' => $data['action_url'] ?? null,
                    'created_at_human' => $notification->created_at?->diffForHumans(),
                    'read_at' => $notification->read_at,
                ];
            });

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'recent' => $recent,
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        // Frontend plain-form submits get a redirect back (or to the
        // notification's action URL if one exists, for one-click
        // inbox-to-destination flow). AJAX consumers still get JSON.
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        $target = $notification->data['action_url'] ?? null;
        if ($target) {
            return redirect()->away($target);
        }
        return back();
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'unread_count' => 0]);
        }
        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'ok' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Bulk-delete every notification belonging to the current user.
     * Symmetric to markAllAsRead() — for users who let months of
     * notifications pile up and don't want to click 200 trash icons.
     * Hard delete; there's no soft-deletes column on the
     * notifications table, so this is irreversible (matching the
     * single-row destroy() behaviour above).
     */
    public function destroyAll(Request $request)
    {
        $request->user()->notifications()->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'unread_count' => 0]);
        }
        return back()->with('success', 'All notifications deleted.');
    }

    /**
     * Dev-only endpoint: POST /notifications/test-dispatch creates a
     * TestNotification for the current user so we can verify the
     * pipeline end to end. Gated by local-env + auth middleware in
     * the route file; will be removed once real flows exist.
     */
    public function testDispatch(Request $request): RedirectResponse
    {
        $request->user()->notify(new \Modules\Notifications\app\Notifications\TestNotification(
            title: 'Test notification',
            message: 'Dispatched at ' . now()->toDateTimeString() . '. The system channel is working.',
        ));

        return back()->with('success', 'Test notification dispatched.');
    }

    /**
     * Register a browser push subscription. Works for both authenticated
     * users and anonymous guests — guests are anchored to a singleton
     * Guest model so the same morph relation can fan out broadcasts.
     */
    public function subscribePush(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'    => ['required', 'string'],
            'keys.auth'   => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'expiration_time' => ['nullable'],
        ]);

        $notifiable = $request->user() ?? Guest::singleton();

        $notifiable->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            null,
        );

        // Auto-enable the user-level opt-in on first subscribe so the
        // switch in the profile tab reflects reality immediately.
        // Guests don't have this column — the accessor on Guest is
        // hard-coded true, so nothing to write.
        if ($request->user() && !$request->user()->push_notifications_enabled) {
            $request->user()->forceFill(['push_notifications_enabled' => true])->save();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Drop a push subscription by endpoint. The client sends the
     * endpoint from the PushSubscription it just unsubscribed.
     */
    public function unsubscribePush(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $notifiable = $request->user() ?? Guest::singleton();
        $notifiable->deletePushSubscription($data['endpoint']);

        if ($request->user() && !$request->user()->pushSubscriptions()->exists()) {
            $request->user()->forceFill(['push_notifications_enabled' => false])->save();
        }

        return response()->json(['ok' => true]);
    }
}
