<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\AccountDeactivationController;
use App\Http\Controllers\Auth\AccountSecurityController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->name('password.store');

    // Social sign-in (Socialite). Provider is whitelisted in the
    // controller; the button on the login/register views only shows
    // when the corresponding config('services.{provider}.client_id')
    // is set.
    Route::get('auth/{provider}', [SocialAuthController::class, 'redirect'])
                ->whereIn('provider', ['google'])
                ->name('auth.social');
    Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])
                ->whereIn('provider', ['google'])
                ->name('auth.social.callback');

    // Two-factor challenge — the login controller parks `login.id`
    // on the session when 2FA is enabled and redirects here. The
    // challenge controller 403s if that session flag isn't present.
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'show'])
                ->name('two-factor.challenge');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'verify'])
                ->middleware('throttle:6,1')
                ->name('two-factor.verify');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    // Throttle so a stolen session cookie cannot be used to brute-force
    // the user's password against this gate. 6/min matches the rest of
    // the auth flow (2FA verify, email verification resend).
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])
                ->middleware('throttle:6,1');

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Legacy /account/security redirects into the profile hub's
    // security tab. Kept so any bookmarks or old emails still land.
    Route::get('account/security', function () {
        return redirect()->route('profile.security', [
            'username' => auth()->user()->username,
        ]);
    })->name('account.security');

    // Account deactivation — soft. Password + explicit checkbox
    // confirmation gate it. The form in the security tab posts here.
    Route::delete('account/deactivate', [AccountDeactivationController::class, 'destroy'])
                ->name('account.deactivate');

    // Two-factor enable / confirm / disable / recovery codes.
    // `password.confirm` makes the user re-enter their password so a
    // stolen session cookie can't silently disable 2FA.
    Route::middleware('password.confirm')->group(function () {
        Route::post('account/two-factor/enable',
            [TwoFactorController::class, 'enable'])->name('two-factor.enable');
        Route::post('account/two-factor/confirm',
            [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
        Route::delete('account/two-factor',
            [TwoFactorController::class, 'disable'])->name('two-factor.disable');
        Route::post('account/two-factor/recovery-codes',
            [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes');
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
