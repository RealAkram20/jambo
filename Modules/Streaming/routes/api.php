<?php

use Illuminate\Support\Facades\Route;
use Modules\Streaming\app\Http\Controllers\Api\V1\DeviceController;
use Modules\Streaming\app\Http\Controllers\Api\V1\PlaybackController;
use Modules\Streaming\app\Http\Controllers\Api\V1\WatchlistController;

/*
|--------------------------------------------------------------------------
| Streaming API routes
|--------------------------------------------------------------------------
|
| Loaded by the module's RouteServiceProvider under the `api` prefix and
| middleware group, so these resolve at /api/v1/....
|
| The scaffold stub that returned $request->user() from /api/v1/streaming was
| removed on 2026-09-08: /api/v1/me answers that properly, with the plan and
| the caps the app actually needs.
|
| Download and offline-session endpoints land here in Phase 3.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:sanctum', 'device.active'])
    ->group(function () {
        // The app-side twin of the website's device picker.
        Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::delete('devices/{uuid}', [DeviceController::class, 'destroy'])
            ->where('uuid', '[A-Za-z0-9\-_]{8,64}')
            ->name('devices.destroy');

        // Playback. Neither endpoint holds a rule of its own: `sessions` asks
        // PlaybackAuthorizer and StreamSourceResolver, `heartbeat` calls
        // PlaybackBeatRecorder — the same three the website uses, so an app
        // viewer and a browser viewer cannot be gated differently.
        Route::post('playback/sessions', [PlaybackController::class, 'store'])
            ->name('playback.sessions');
        Route::post('playback/heartbeat', [PlaybackController::class, 'heartbeat'])
            ->name('playback.heartbeat');

        // The viewer's own lists. POST adds and DELETE removes rather than
        // one toggle endpoint: a toggle retried over a flaky mobile
        // connection silently undoes itself, while these are safe to repeat.
        Route::get('watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
        Route::post('watchlist', [WatchlistController::class, 'store'])->name('watchlist.store');
        Route::delete('watchlist/{type}/{id}', [WatchlistController::class, 'destroy'])
            ->where('type', 'movie|show|episode')
            ->where('id', '[0-9]+')
            ->name('watchlist.destroy');

        Route::get('continue-watching', [WatchlistController::class, 'continueWatching'])
            ->name('continue-watching');
        Route::get('history', [WatchlistController::class, 'history'])->name('history');
    });
