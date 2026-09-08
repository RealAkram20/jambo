<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthentication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Streaming\app\Models\Device;
use Modules\Streaming\app\Services\PlaybackAuthorizer;

/**
 * Token sign-in for the mobile and TV app.
 *
 * The rules are the website's, deliberately: look the account up by lowered
 * email, compare with Hash::check, refuse a deactivated account, honour
 * two-factor, and share the same "5 tries per email+ip" throttle bucket as the
 * browser login so an attacker cannot get ten attempts by alternating between
 * the two front doors. AuthenticatedSessionController is the reference.
 *
 * What differs is what a success produces: a Sanctum token bound to a device
 * row rather than a session cookie, so the account's device list can show a
 * phone beside a browser and boot either.
 *
 * Two-factor is two calls here rather than a redirect. Login answers
 * TWO_FACTOR_REQUIRED with a short-lived challenge token that is NOT an access
 * token and can do nothing else; the app posts it back with the code to
 * /auth/2fa/challenge and gets the real token then.
 */
class AuthController extends Controller
{
    /** How long the app has to answer a 2FA prompt before starting over. */
    private const CHALLENGE_TTL_SECONDS = 300;

    private const MAX_LOGIN_ATTEMPTS = 5;

    public function login(Request $request, TwoFactorAuthentication $twoFactor): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            ...self::deviceRules(),
        ]);

        $key = self::throttleKey($data['email'], $request->ip());

        if (RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            return ApiResponse::error(
                ApiErrorCode::RateLimited,
                "Too many sign-in attempts. Try again in {$seconds} seconds.",
                ['email' => ["Try again in {$seconds} seconds."]],
            );
        }

        $user = User::where('email', Str::lower($data['email']))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key);

            // One message for "no such account" and "wrong password", so the
            // endpoint cannot be used to find out which emails have accounts.
            return ApiResponse::error(
                ApiErrorCode::InvalidCredentials,
                'Those details do not match an account.',
            );
        }

        if ($user->isDeactivated()) {
            return ApiResponse::error(
                ApiErrorCode::AccountDeactivated,
                'This account has been deactivated. Contact support to reactivate it.',
            );
        }

        RateLimiter::clear($key);

        if ($user->hasEnabledTwoFactorAuthentication()) {
            // A failure, not a success: no token was issued and the app must
            // not treat it as a sign-in. The challenge token rides alongside
            // the envelope because it is the one thing the client needs to
            // continue, and it is useless for anything else.
            return ApiResponse::error(
                ApiErrorCode::TwoFactorRequired,
                'Enter the code from your authenticator app.',
                extra: ['challenge_token' => $this->issueChallenge($user, $data['device'])],
            );
        }

        return ApiResponse::ok(
            $this->issueToken($user, $data['device']),
            'Signed in.',
        );
    }

    /**
     * Second leg of a two-factor sign-in. Accepts either a TOTP code or one
     * of the account's recovery codes, exactly as the web challenge does.
     */
    public function twoFactorChallenge(Request $request, TwoFactorAuthentication $twoFactor): JsonResponse
    {
        $data = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        if (blank($data['code'] ?? null) && blank($data['recovery_code'] ?? null)) {
            return ApiResponse::error(
                ApiErrorCode::ValidationFailed,
                'Enter your authentication code.',
                ['code' => ['Enter your authentication code.']],
            );
        }

        $cacheKey = self::challengeKey($data['challenge_token']);
        $payload = Cache::get($cacheKey);

        if (! $payload) {
            return ApiResponse::error(
                ApiErrorCode::TwoFactorChallengeExpired,
                'That sign-in attempt expired. Please sign in again.',
            );
        }

        $user = User::find($payload['user_id']);

        if (! $user || $user->isDeactivated()) {
            Cache::forget($cacheKey);

            return ApiResponse::error(
                ApiErrorCode::AccountDeactivated,
                'This account is no longer active.',
            );
        }

        $passed = filled($data['code'] ?? null)
            ? $twoFactor->verifyTotp($user, $data['code'])
            : $twoFactor->verifyRecoveryCode($user, $data['recovery_code']);

        if (! $passed) {
            // The challenge is NOT consumed on a wrong code - a typo should
            // not force the password step again - but it still dies at its
            // TTL, which bounds how long it can be guessed at.
            return ApiResponse::error(
                ApiErrorCode::TwoFactorInvalid,
                'That code is not right. Check your authenticator and try again.',
            );
        }

        Cache::forget($cacheKey);

        return ApiResponse::ok(
            $this->issueToken($user, $payload['device']),
            'Signed in.',
        );
    }

    /**
     * Sign out this device only.
     *
     * Deletes the token that made the request and stamps its device revoked,
     * so the account's device list stops showing it as live. Other devices are
     * untouched: signing out of a phone must not sign out the TV.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($device = Device::forToken($token)) {
            $device->revoke();
        } elseif ($token) {
            $token->delete();
        }

        return ApiResponse::ok(null, 'Signed out.');
    }

    /**
     * Who am I, what can I watch, and on how many devices.
     *
     * The app calls this on launch, so it carries the plan and the caps rather
     * than making the client stitch three requests together on a connection
     * that may not survive them.
     */
    public function me(Request $request, PlaybackAuthorizer $authorizer): JsonResponse
    {
        $user = $request->user();
        $subscription = $authorizer->activeSubscription($user);
        $tier = $subscription?->tier;

        return ApiResponse::ok([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => $user->hasRole('admin'),
            ],
            'subscription' => $subscription ? [
                'tier' => [
                    'name' => $tier?->name,
                    'slug' => $tier?->slug,
                    'access_level' => $tier?->access_level,
                ],
                'starts_at' => optional($subscription->starts_at)->toIso8601String(),
                'ends_at' => optional($subscription->ends_at)->toIso8601String(),
            ] : null,
            'limits' => [
                // null means the tier sets no cap, which is not the same as
                // zero. The app must render "unlimited", not "none".
                'max_concurrent_streams' => $tier?->max_concurrent_streams,
            ],
            // The app anchors its offline clock on this; see ADR-0003.
            'server_time' => now()->toIso8601String(),
        ]);
    }

    // ── internals ────────────────────────────────────────────────────

    /**
     * The device block every sign-in must carry. Without it we would issue a
     * token that no device list can show and no picker can boot.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function deviceRules(): array
    {
        return [
            'device' => ['required', 'array'],
            'device.uuid' => ['required', 'string', 'min:8', 'max:64'],
            'device.platform' => ['required', 'string', 'in:' . implode(',', Device::PLATFORMS)],
            'device.name' => ['nullable', 'string', 'max:120'],
            'device.model' => ['nullable', 'string', 'max:120'],
            'device.app_version' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * Shared with the browser login on purpose. Keying on email+ip rather
     * than ip alone matters here more than on the web: East African carriers
     * put thousands of handsets behind one CGNAT address, so a per-IP bucket
     * would be one bucket per cell tower.
     */
    private static function throttleKey(string $email, ?string $ip): string
    {
        return Str::transliterate(Str::lower($email) . '|' . $ip);
    }

    private static function challengeKey(string $token): string
    {
        return 'api.2fa.' . hash('sha256', $token);
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function issueChallenge(User $user, array $device): string
    {
        $token = Str::random(64);

        Cache::put(
            self::challengeKey($token),
            ['user_id' => $user->id, 'device' => $device],
            self::CHALLENGE_TTL_SECONDS,
        );

        return $token;
    }

    /**
     * Mint the access token and bind it to the device row.
     *
     * @param  array<string, mixed>  $device
     * @return array<string, mixed>
     */
    private function issueToken(User $user, array $device): array
    {
        // Named for the install so `personal_access_tokens` is readable
        // without a join, and abilities kept to `viewer`: this token is for
        // watching. Anything that moves money or changes rates is not
        // reachable with it even if a route forgets its own policy.
        $newToken = $user->createToken('device:' . $device['uuid'], ['viewer']);

        $row = Device::register(
            user: $user,
            uuid: $device['uuid'],
            platform: $device['platform'],
            name: $device['name'] ?? null,
            model: $device['model'] ?? null,
            appVersion: $device['app_version'] ?? null,
            tokenId: $newToken->accessToken->getKey(),
        );

        return [
            'token' => $newToken->plainTextToken,
            'device' => [
                'uuid' => $row->uuid,
                'platform' => $row->platform,
                'name' => $row->name,
            ],
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ],
        ];
    }
}
