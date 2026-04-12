<?php

use Illuminate\Support\Facades\Route;
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
    });
