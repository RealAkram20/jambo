<?php

use Illuminate\Support\Facades\Route;
use Modules\Streaming\app\Http\Controllers\StreamingController;

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
});
