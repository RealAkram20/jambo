<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\app\Http\Controllers\Admin\NotificationSettingsController;
use Modules\Notifications\app\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Notifications — web routes
|--------------------------------------------------------------------------
|
| Every route is auth-gated. The dropdown endpoint is polled from the
| header bell every 60 seconds; the full index is a normal HTML page.
|
*/

Route::middleware('auth')
    ->prefix('notifications')
    ->name('notifications.')
    ->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/dropdown', [NotificationController::class, 'dropdown'])->name('dropdown');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');

        // Dev helper: manual smoke-test dispatch. Only active in
        // non-production environments.
        Route::post('/test-dispatch', [NotificationController::class, 'testDispatch'])
            ->name('test-dispatch');

        // Browser push subscribe / unsubscribe. Called from the
        // profile notifications tab after the client asks the browser
        // for a push subscription.
        Route::post('/push/subscribe', [NotificationController::class, 'subscribePush'])
            ->name('push.subscribe');
        Route::post('/push/unsubscribe', [NotificationController::class, 'unsubscribePush'])
            ->name('push.unsubscribe');
    });

// Admin-only: bulk update for the global notification switches shown
// inside the Settings tab of /notifications. The GET render stays on
// NotificationController@index so the Inbox + Settings tabs share one
// page and one URL.
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/notifications')
    ->name('admin.notifications.')
    ->group(function () {
        Route::put('/settings', [NotificationSettingsController::class, 'update'])
            ->name('settings.update');

        // Fire a one-off test notification through a single channel so
        // the admin can sanity-check each transport. Channel must be
        // "system" | "push" | "email".
        Route::post('/settings/test/{channel}', [NotificationSettingsController::class, 'testChannel'])
            ->whereIn('channel', ['system', 'push', 'email'])
            ->name('settings.test');

        // Admin broadcast form submission — fans out an
        // AdminBroadcastNotification to the selected audience.
        Route::post('/broadcast', [NotificationSettingsController::class, 'sendBroadcast'])
            ->name('broadcast.send');
    });
