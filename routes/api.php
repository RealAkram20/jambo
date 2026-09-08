<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| API v1 — the mobile and TV app
|--------------------------------------------------------------------------
|
| Added 2026-09-08 for the app (docs/plans/mobile-offline-app.md). Additive
| only: nothing here changes a web route, and the website does not call any
| of it. Per-module endpoints live in each module's own routes/api.php;
| what sits here is what belongs to no single module.
|
| Sign-in is throttled on `auth`, which is keyed on email+ip rather than ip
| alone, because a per-ip bucket behind East African carrier NAT is one
| bucket for a whole cell tower.
|
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('app/config', [AppConfigController::class, 'show'])->name('app.config');

    Route::middleware('throttle:auth')->group(function () {
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('auth/2fa/challenge', [AuthController::class, 'twoFactorChallenge'])
            ->name('auth.2fa.challenge');
    });

    Route::middleware(['auth:sanctum', 'device.active'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
    });
});
