<?php

use Illuminate\Support\Facades\Route;
use Modules\Streaming\app\Http\Controllers\StreamController;
use Modules\Streaming\app\Http\Controllers\StreamingController;
use Modules\Streaming\app\Http\Controllers\StreamProxyController;

/*
|--------------------------------------------------------------------------
| Streaming web routes
|--------------------------------------------------------------------------
|
| Player routes live under /watch/* and enforce:
|   - auth        : user must be logged in (for watch history + tier)
|   - tier_gate   : user's active subscription covers the content's
|                   tier_required slug
|
| The heartbeat endpoint sits under /api/v1/streaming/heartbeat but is
| intentionally routed here (the `web` group) so it uses session auth
| + CSRF instead of Sanctum tokens — the browser player already has
| both, and Sanctum would just add friction.
*/

Route::middleware(['auth'])->group(function () {
    // Bare fullscreen player. Moved off /watch/* so the rich /watch/{slug}
    // route (in the Frontend module) can live at the short canonical URL.
    Route::get('/player/movie/{movie:slug}', [StreamingController::class, 'watchMovie'])
        ->middleware('tier_gate')
        ->name('streaming.player.movie');

    Route::get('/player/episode/{episode}', [StreamingController::class, 'watchEpisode'])
        ->middleware('tier_gate')
        ->name('streaming.player.episode');

    Route::post('/api/v1/streaming/heartbeat', [StreamingController::class, 'heartbeat'])
        ->name('streaming.heartbeat');

    // Format proxy: converts MKV/HEVC/x265 → H.264 MP4 on the fly via FFmpeg.
    // Must be BEFORE the HLS routes — the /stream/movie/{slug}/{path?} wildcard
    // would otherwise swallow /stream/proxy/... URLs.
    Route::get('/stream/proxy/movie/{movie:slug}', [StreamProxyController::class, 'movie'])
        ->middleware('tier_gate')
        ->name('stream.proxy.movie');

    Route::get('/stream/proxy/episode/{episode}', [StreamProxyController::class, 'episode'])
        ->middleware('tier_gate')
        ->name('stream.proxy.episode');

    // HLS stream endpoints.
    Route::get('/stream/movie/{movie:slug}/{path?}', [StreamController::class, 'movie'])
        ->where('path', '.*')
        ->middleware('tier_gate')
        ->name('stream.movie');

    Route::get('/stream/episode/{episode}/{path?}', [StreamController::class, 'episode'])
        ->where('path', '.*')
        ->middleware('tier_gate')
        ->name('stream.episode');
});
