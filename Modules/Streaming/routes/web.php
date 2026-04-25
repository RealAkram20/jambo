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

    // Device picker actions.
    //   • boot   — terminate one of your other active sessions
    //              ({sessionId} is the Laravel session ID; scoped to
    //              rows owned by the authed user in the controller)
    //   • reclaim — kicked device clears its own terminated_at and
    //               re-runs the cap check (overlay "Take back here")
    //   • continue — used by the picker's "Continue watching" button;
    //                redirects to the URL TierGate stashed
    Route::post('/streams/boot/{sessionId}', [StreamingController::class, 'bootSession'])
        ->where('sessionId', '[A-Za-z0-9]{20,64}')
        ->name('streams.boot');

    Route::post('/streams/reclaim', [StreamingController::class, 'reclaimStream'])
        ->name('streams.reclaim');

    Route::get('/streams/continue', [StreamingController::class, 'continueStream'])
        ->name('streams.continue');

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

    // Stream-source passthrough. The <video src="..."> attribute points
    // at one of these routes instead of the raw Contabo / Dropbox URL,
    // so inspect-element on the HTML no longer hands out a copyable
    // direct link. Still auth + tier_gate: a leaked /watch/src URL
    // shared with a logged-out viewer bounces to login. These issue a
    // 302 to the real origin after the middleware passes — advanced
    // users watching the Network tab during playback will still see
    // the final URL, but it's no longer sitting in plain HTML.
    // Ordered BEFORE the /stream/movie/{slug}/{path?} HLS wildcard for
    // the same reason as the proxy routes above.
    Route::get('/watch/src/movie/{movie:slug}/low', [StreamProxyController::class, 'passthroughMovieLow'])
        ->name('stream.src.movie.low');
    Route::get('/watch/src/movie/{movie:slug}', [StreamProxyController::class, 'passthroughMovie'])
        ->name('stream.src.movie');
    Route::get('/watch/src/episode/{episode}/low', [StreamProxyController::class, 'passthroughEpisodeLow'])
        ->name('stream.src.episode.low');
    Route::get('/watch/src/episode/{episode}', [StreamProxyController::class, 'passthroughEpisode'])
        ->name('stream.src.episode');

    // HLS stream endpoints.
    Route::get('/stream/movie/{movie:slug}/{path?}', [StreamController::class, 'movie'])
        ->where('path', '.*')
        ->name('stream.movie');

    Route::get('/stream/episode/{episode}/{path?}', [StreamController::class, 'episode'])
        ->where('path', '.*')
        ->name('stream.episode');
});
