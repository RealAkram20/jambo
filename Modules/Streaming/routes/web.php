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

// Auth-required: user-identity-bound endpoints. Guests have no
// watch history or concurrency state, so heartbeat + limit page only
// make sense when logged in.
Route::middleware(['auth'])->group(function () {
    // Stream concurrency block screen — landed on when tier_gate or
    // FrontendController detects the user is over their tier's
    // max_concurrent_streams. Not gated by tier_gate (that's what
    // sent us here).
    Route::get('/streams/limit', [StreamingController::class, 'streamLimit'])
        ->name('streams.limit');

    Route::post('/api/v1/streaming/heartbeat', [StreamingController::class, 'heartbeat'])
        ->name('streaming.heartbeat');
});

// Tier-gated: `tier_gate` handles the auth + tier decision.
//   - Free content (tier_required null) → open to guests + authed users
//   - Premium content → guest redirected to login, authed user checked
//                       against their subscription + concurrency cap
Route::middleware(['tier_gate'])->group(function () {
    // Bare fullscreen player. Moved off /watch/* so the rich /watch/{slug}
    // route (in the Frontend module) can live at the short canonical URL.
    Route::get('/player/movie/{movie:slug}', [StreamingController::class, 'watchMovie'])
        ->name('streaming.player.movie');

    Route::get('/player/episode/{episode}', [StreamingController::class, 'watchEpisode'])
        ->name('streaming.player.episode');

    // Format proxy: converts MKV/HEVC/x265 → H.264 MP4 on the fly via FFmpeg.
    // Must be BEFORE the HLS routes — the /stream/movie/{slug}/{path?} wildcard
    // would otherwise swallow /stream/proxy/... URLs.
    Route::get('/stream/proxy/movie/{movie:slug}', [StreamProxyController::class, 'movie'])
        ->name('stream.proxy.movie');

    Route::get('/stream/proxy/episode/{episode}', [StreamProxyController::class, 'episode'])
        ->name('stream.proxy.episode');

    // HLS stream endpoints.
    Route::get('/stream/movie/{movie:slug}/{path?}', [StreamController::class, 'movie'])
        ->where('path', '.*')
        ->name('stream.movie');

    Route::get('/stream/episode/{episode}/{path?}', [StreamController::class, 'episode'])
        ->where('path', '.*')
        ->name('stream.episode');
});
