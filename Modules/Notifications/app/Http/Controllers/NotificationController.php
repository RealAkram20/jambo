<?php

namespace Modules\Notifications\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

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
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20)
            ->withQueryString();

        return view('notifications::index', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
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
}
