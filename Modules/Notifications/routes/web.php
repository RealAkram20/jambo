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

        // Bulk delete must come BEFORE the wildcard /{id} below or
        // Laravel matches DELETE /notifications/all against the
        // single-row route with id="all" and 404s on findOrFail().
        Route::delete('/all', [NotificationController::class, 'destroyAll'])->name('destroy-all');

        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');

        // Dev helper: manual smoke-test dispatch. Gated at the route
        // level so production never registers it — matches the
        // @if (app()->environment('local')) check on the admin UI.
        if (! app()->environment('production')) {
            Route::post('/test-dispatch', [NotificationController::class, 'testDispatch'])
                ->name('test-dispatch');
        }
    });

// Push subscribe / unsubscribe are deliberately auth-OPTIONAL: guests
// can opt in too, anchored to the Guest singleton so broadcasts fan
// out to logged-out browsers as well. The controller branches on
// $request->user() to pick the right notifiable.
Route::prefix('notifications')
    ->name('notifications.')
    ->group(function () {
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
