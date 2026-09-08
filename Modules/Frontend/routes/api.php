<?php

use Illuminate\Support\Facades\Route;
use Modules\Frontend\app\Http\Controllers\Api\V1\HomeController;

/*
|--------------------------------------------------------------------------
| Frontend API routes
|--------------------------------------------------------------------------
|
| The app's home screen. It calls HomeRailsService, which is also what the
| website's view composer calls, so the two homepages cannot drift.
|
| Public: a guest gets a full home screen, exactly as on the website. Signing
| in changes what comes back (Continue Watching appears, personalised rails
| become personal), not whether it does.
|
| That is why it runs `api.viewer` rather than `auth:sanctum`. A public route
| never resolves a bearer token on its own, so auth()->id() deep inside the
| rails would read null even for a signed-in app and quietly serve the guest
| home screen. api.viewer resolves the token when one is sent and lets the
| request through when it is not.
|
| The scaffold stub that returned $request->user() from /api/v1/frontend was
| removed on 2026-09-08.
|
*/

Route::prefix('v1')->name('api.v1.')->middleware('api.viewer')->group(function () {
    Route::get('home', [HomeController::class, 'show'])->name('home');
});
