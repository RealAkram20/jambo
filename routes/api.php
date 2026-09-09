<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\AccountSecurityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\StreamingPreferencesController;
use App\Http\Controllers\Api\V1\RegistrationController;
use App\Http\Controllers\Api\V1\SocialAuthController;

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

        // Sign-up. The website's honeypot and reCAPTCHA do not port to a
        // native form (no DOM for a bot to fill, no browser token to
        // produce), so this throttle plus the SignupAttempt log carry the
        // weight instead. See RegistrationController.
        Route::post('auth/register', [RegistrationController::class, 'store'])->name('auth.register');

        // Google sign-in: the app sends an ID token from Google's own SDK
        // rather than following the website's OAuth redirect.
        Route::post('auth/google', [SocialAuthController::class, 'google'])->name('auth.google');

        // Recovery. The reset LINK lands on the website; reset-password
        // exists so the app can complete it in-app later with no server
        // change.
        Route::post('auth/forgot-password', [PasswordController::class, 'forgot'])
            ->name('auth.forgot-password');
        Route::post('auth/reset-password', [PasswordController::class, 'reset'])
            ->name('auth.reset-password');
    });

    Route::middleware(['auth:sanctum', 'device.active'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        // Changing a password signs out every OTHER device, which is the
        // token equivalent of the website's Auth::logoutOtherDevices.
        Route::put('auth/password', [PasswordController::class, 'change'])->name('auth.password');

        Route::get('auth/email', [EmailVerificationController::class, 'status'])
            ->name('auth.email.status');

        // Sends mail, so it is throttled below the read endpoints.
        Route::post('auth/email/resend', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('auth.email.resend');

        // The profile screen. /me stays small for launch; this is the
        // editable surface.
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
        Route::delete('profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.destroy');

        // Two-factor setup is three calls on purpose: start, confirm, and
        // only then is it on. A one-shot enable would let someone lock
        // themselves out with a mis-scanned code.
        Route::get('account/security', [AccountSecurityController::class, 'show'])
            ->name('account.security');
        Route::post('account/2fa', [AccountSecurityController::class, 'enableTwoFactor'])
            ->name('account.2fa.enable');
        Route::post('account/2fa/confirm', [AccountSecurityController::class, 'confirmTwoFactor'])
            ->name('account.2fa.confirm');
        Route::delete('account/2fa', [AccountSecurityController::class, 'disableTwoFactor'])
            ->name('account.2fa.disable');
        Route::post('account/2fa/recovery-codes', [AccountSecurityController::class, 'regenerateRecoveryCodes'])
            ->name('account.2fa.recovery-codes');

        Route::delete('account', [AccountSecurityController::class, 'deactivate'])
            ->name('account.deactivate');

        // How this viewer wants their video delivered. On the account rather
        // than on the handset, by Rio's ruling of 2026-09-09: the same choices
        // have to hold on a second phone and on the television in Phase 4.
        //
        // PATCH rather than PUT because a screen of switches sends the one
        // that moved; see the controller for why that distinction protects a
        // setting the client forgot to resend.
        Route::get('account/preferences', [StreamingPreferencesController::class, 'show'])
            ->name('account.preferences.show');
        Route::patch('account/preferences', [StreamingPreferencesController::class, 'update'])
            ->name('account.preferences.update');
    });
});
