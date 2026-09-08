<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Streaming\app\Models\Device;
use Throwable;

/**
 * Forgotten, reset and changed passwords.
 *
 * The reset link the app requests lands on the website, which is deliberate:
 * one reset page, one set of rules, and no deep-link handling to get wrong on
 * a first release. `POST /auth/reset-password` exists so the app CAN complete
 * it in-app later without a server change.
 */
class PasswordController extends Controller
{
    /**
     * Send a reset link.
     *
     * The response is identical whether or not the address has an account.
     * Echoing the broker's real status turns this endpoint into an
     * account-enumeration oracle — an attacker could probe which emails are
     * registered — and the website is careful about exactly this. The mail
     * still only goes out when the account is real.
     */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // The send is wrapped, and the reason is not tidiness. For an address
        // with no account the broker returns without touching mail; for a
        // real one it tries to send. If the transport is down, the real
        // account throws and the fake one does not - and "500 means it
        // exists" is an account-enumeration oracle, the exact thing the
        // neutral message below is meant to prevent. So a mail failure is
        // logged for ops and the viewer sees the same sentence either way.
        //
        // Found 2026-09-08: locally MAIL_HOST points at a Docker hostname,
        // and this endpoint answered a bare TransportException for real
        // accounts only.
        try {
            Password::sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            Log::error('[auth] password reset mail failed', [
                'email_hash' => hash('sha256', Str::lower((string) $request->input('email'))),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
        }

        return ApiResponse::ok(
            null,
            "If an account exists for that email, we've sent a password reset link. Check your inbox and spam folder.",
        );
    }

    /** Complete a reset with the token from the emailed link. */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                // Whoever knew the old password is locked out everywhere. A
                // reset is usually someone recovering an account they think
                // was compromised, so leaving other devices signed in would
                // defeat the point.
                $this->revokeEveryDevice($user->id);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error(
                ApiErrorCode::ValidationFailed,
                __($status),
                ['email' => [__($status)]],
            );
        }

        return ApiResponse::ok(null, 'Your password has been reset. Sign in with the new one.');
    }

    /**
     * Change the password of a signed-in viewer.
     *
     * `current_password` is required, matching the website: knowing the old
     * one is what separates the account owner from someone holding a stolen
     * handset.
     *
     * Every OTHER device is signed out, which is the token equivalent of the
     * website's Auth::logoutOtherDevices. The device that made the change
     * keeps working, so the viewer is not thrown out of the app they are
     * standing in.
     */
    public function change(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $request->user();

        $user->forceFill(['password' => Hash::make($request->input('password'))])->save();

        $this->revokeEveryDevice($user->id, exceptTokenId: $user->currentAccessToken()?->getKey());

        return ApiResponse::ok(null, 'Password changed. Your other devices have been signed out.');
    }

    /**
     * Kill every app install on the account, optionally sparing one.
     *
     * Devices are revoked through the model so their streams stop counting
     * against the concurrency cap, and any token with no device row is deleted
     * separately so nothing survives on a technicality.
     */
    private function revokeEveryDevice(int $userId, ?int $exceptTokenId = null): void
    {
        Device::query()
            ->where('user_id', $userId)
            ->active()
            ->get()
            ->each(function (Device $device) use ($exceptTokenId) {
                if ($exceptTokenId !== null && $device->personal_access_token_id === $exceptTokenId) {
                    return;
                }

                $device->revoke();
            });

        PersonalAccessToken::query()
            ->where('tokenable_type', \App\Models\User::class)
            ->where('tokenable_id', $userId)
            ->when($exceptTokenId !== null, fn ($q) => $q->whereKeyNot($exceptTokenId))
            ->delete();
    }
}
