<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\app\Http\Controllers\Api\V1\NotificationController;

/*
|--------------------------------------------------------------------------
| Notifications API routes
|--------------------------------------------------------------------------
|
| The app's notification list, its preferences, and the FCM token registry.
|
| GET /notifications is not decoration: push is an accelerator, never the
| transport, so everything a push says has to be independently readable here.
| The app must be usable with push switched off entirely.
|
| The scaffold stub that returned $request->user() from /api/v1/notifications
| was removed on 2026-09-08.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:sanctum', 'device.active'])
    ->group(function () {
        Route::get('notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])
            ->name('notifications.read-all');
        Route::delete('notifications', [NotificationController::class, 'destroyAll'])
            ->name('notifications.destroy-all');

        Route::get('notifications/preferences', [NotificationController::class, 'preferences'])
            ->name('notifications.preferences');
        Route::put('notifications/preferences', [NotificationController::class, 'updatePreferences'])
            ->name('notifications.preferences.update');

        // FCM. The token is stored on the device row, so signing out clears
        // it automatically — a shared handset must never keep the previous
        // account's token.
        Route::post('notifications/push-token', [NotificationController::class, 'registerPushToken'])
            ->name('notifications.push.register');
        Route::delete('notifications/push-token', [NotificationController::class, 'unregisterPushToken'])
            ->name('notifications.push.unregister');

        // Wildcards last, so `read-all`, `preferences` and `push-token` are
        // never swallowed as a notification id.
        Route::post('notifications/{id}/read', [NotificationController::class, 'read'])
            ->name('notifications.read');
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])
            ->name('notifications.destroy');
    });
