<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The short-lived handle between "your password was right" and "here is your
 * token" when an account has two-factor enabled.
 *
 * A challenge token is NOT an access token. It authorises exactly one thing —
 * presenting a TOTP or recovery code — and it expires on its own, which bounds
 * how long it can be guessed at.
 *
 * Shared by password sign-in and Google sign-in, because Google proves the
 * mailbox and not possession of the viewer's authenticator: both must land on
 * the same challenge.
 *
 * Cache-backed rather than a table. These live for five minutes and their loss
 * costs a viewer one retry, which does not justify a migration; the file cache
 * this application runs on is per-server, and there is one server.
 */
class TwoFactorChallengeStore
{
    /** How long the app has to answer before starting over. */
    private const TTL_SECONDS = 300;

    /**
     * @param  array<string, mixed>  $device  Remembered so the second leg does
     *                                        not have to be told again.
     */
    public function issue(User $user, array $device): string
    {
        $token = Str::random(64);

        Cache::put(
            self::key($token),
            ['user_id' => $user->id, 'device' => $device],
            self::TTL_SECONDS,
        );

        return $token;
    }

    /**
     * Read a challenge without spending it.
     *
     * Deliberately not consumed on read: a wrong code must not send the viewer
     * back to the password screen, so the caller decides when to forget().
     *
     * @return array<string, mixed>|null
     */
    public function peek(string $token): ?array
    {
        return Cache::get(self::key($token));
    }

    public function forget(string $token): void
    {
        Cache::forget(self::key($token));
    }

    /**
     * Hashed, so a cache dump does not hand out usable challenge tokens.
     */
    private static function key(string $token): string
    {
        return 'api.2fa.' . hash('sha256', $token);
    }
}
