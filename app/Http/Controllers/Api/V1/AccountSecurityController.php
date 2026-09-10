<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthentication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Notifications\app\Events\AccountDeactivated;
use Modules\Streaming\app\Models\Device;

/**
 * Two-factor setup and account deactivation.
 *
 * The service doing the work is the website's `TwoFactorAuthentication`, so
 * codes, recovery codes and the confirm step behave identically on both — a
 * recovery code printed from the website works in the app and the other way
 * round.
 *
 * The setup flow is three calls because it has to be: start (which mints a
 * pending secret and returns the QR), confirm (which proves the viewer's
 * authenticator actually has it), and only then is 2FA on. A one-shot enable
 * would let someone lock themselves out of their own account with a
 * mis-scanned code.
 */
class AccountSecurityController extends Controller
{
    public function __construct(private readonly TwoFactorAuthentication $twoFactor)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $pending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;
        $enabled = $user->hasEnabledTwoFactorAuthentication();

        return ApiResponse::ok([
            'two_factor' => [
                'enabled' => $enabled,
                'pending' => $pending,
                // Only while setting up or already on. There is no reason to
                // hand out a secret to a screen that is not showing it.
                'secret' => ($pending || $enabled) ? $this->twoFactor->secretForManualEntry($user) : null,
                'recovery_codes' => $enabled ? $this->twoFactor->getRecoveryCodes($user) : [],
            ],
            'google_enabled' => (bool) config('services.google.client_id'),
            'email_verified' => $user->hasVerifiedEmail(),
        ]);
    }

    /**
     * Start setup: mint a pending secret and recovery codes.
     *
     * The app renders its own QR from `secret` — a server-rendered SVG is a
     * web answer, and a native screen wants the raw value anyway. 2FA is NOT
     * on until confirm() succeeds.
     */
    public function enableTwoFactor(Request $request): JsonResponse
    {
        $this->twoFactor->generatePendingSetup($request->user());

        $user = $request->user()->fresh();

        return ApiResponse::ok([
            'secret' => $this->twoFactor->secretForManualEntry($user),
            'recovery_codes' => $this->twoFactor->getRecoveryCodes($user),
        ], 'Scan or enter the secret in your authenticator, then confirm with a code.');
    }

    /** Finalise setup by proving the authenticator has the secret. */
    public function confirmTwoFactor(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);

        if (! $this->twoFactor->confirm($request->user(), $data['code'])) {
            return ApiResponse::error(
                ApiErrorCode::TwoFactorInvalid,
                'That code did not match. Try the next one your app shows.',
            );
        }

        return ApiResponse::ok(
            ['recovery_codes' => $this->twoFactor->getRecoveryCodes($request->user()->fresh())],
            'Two-factor authentication is on. Store your recovery codes somewhere safe.',
        );
    }

    /**
     * Turn 2FA off.
     *
     * The website puts this behind its password-confirmation middleware, which
     * is a session concept. The token equivalent is asking for the password
     * here — removing a second factor must cost more than holding an unlocked
     * handset.
     */
    public function disableTwoFactor(Request $request): JsonResponse
    {
        if (! $this->passwordConfirmed($request)) {
            return ApiResponse::error(
                ApiErrorCode::ValidationFailed,
                'Enter your current password to turn off two-factor authentication.',
                ['password' => ['Enter your current password.']],
            );
        }

        $this->twoFactor->disable($request->user());

        return ApiResponse::ok(null, 'Two-factor authentication disabled.');
    }

    /** A fresh batch. The old codes stop working immediately. */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        if (! $request->user()->hasEnabledTwoFactorAuthentication()) {
            return ApiResponse::error(
                ApiErrorCode::ValidationFailed,
                'Two-factor authentication is not on for this account.',
            );
        }

        return ApiResponse::ok(
            ['recovery_codes' => $this->twoFactor->regenerateRecoveryCodes($request->user())],
            'New recovery codes generated. Old ones no longer work.',
        );
    }

    /**
     * Deactivate the account.
     *
     * Costs the password and an explicit confirmation, exactly as on the
     * website. Every device is signed out, because the account is now one that
     * `login` refuses — leaving live tokens behind would let the app keep
     * watching on a deactivated account until someone noticed.
     */
    public function deactivate(Request $request): JsonResponse
    {
        /*
         * The admin switch, checked here and not only in the app.
         *
         * Rio can hide the button from `/app/config`, and a build that already
         * shipped still has it. The setting is the authority; the flag the app
         * reads is a courtesy so the row disappears rather than failing when
         * pressed. Refused before the password is even checked, so a disabled
         * feature cannot be used as an oracle for whether a password is right.
         */
        if (! (bool) setting('app.account_deletion_enabled', true)) {
            return ApiResponse::error(
                ApiErrorCode::Forbidden,
                'Closing your account from the app is turned off. Contact support and we will do it for you.',
            );
        }

        $request->validate([
            'password' => ['required', 'string'],
            'confirm' => ['accepted'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            return ApiResponse::error(
                ApiErrorCode::ValidationFailed,
                'That password is not correct.',
                ['password' => ['That password is not correct.']],
            );
        }

        $user->forceFill(['deactivated_at' => now()])->save();

        event(new AccountDeactivated($user, 'user_requested'));

        Device::query()->where('user_id', $user->id)->active()->get()
            ->each(fn (Device $device) => $device->revoke());

        PersonalAccessToken::query()
            ->where('tokenable_type', \App\Models\User::class)
            ->where('tokenable_id', $user->id)
            ->delete();

        return ApiResponse::ok(null, 'Your account has been deactivated. Email support if you change your mind.');
    }

    private function passwordConfirmed(Request $request): bool
    {
        $password = (string) $request->input('password');

        return $password !== '' && Hash::check($password, $request->user()->password);
    }
}
