<?php

namespace App\Services;

use App\Models\User;
use App\Rules\ReservedUsername;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Turns a verified social identity into a Jambo account.
 *
 * Lifted out of SocialAuthController on 2026-09-08 so `POST /api/v1/auth/google`
 * can reuse it. The rules here decide who owns an account, and a second copy of
 * that in the API would be the worst possible place for the drift this phase
 * has been removing — SocialAuthPinTest was written and green against the old
 * controller before the move, and is green after.
 *
 * The caller decides what to do with the returned user: the website logs in and
 * redirects, the API issues a device token. Deactivation and two-factor are
 * deliberately NOT handled here, because the two clients answer them
 * differently — a redirect versus an error code.
 *
 * **This service assumes the email is already proven.** It is only ever called
 * after a provider has authenticated the mailbox, and it grants verified status
 * on that basis. Never call it with an address a user simply typed.
 */
class SocialAccountResolver
{
    /**
     * Find or create the account behind a provider-verified email.
     *
     * @param  string  $email  Lower-cased, and proven by the provider.
     * @param  string|null  $name  The provider's display name, if it shared one.
     */
    public function resolve(string $email, ?string $name): User
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return $this->createFromSocial($email, (string) $name);
        }

        if ($user->email_verified_at === null) {
            $this->adoptUnverifiedAccount($user);
        }

        return $user;
    }

    /**
     * Match-by-email into a local account that never confirmed its email is
     * the account-takeover risk.
     *
     * A squatter can register victim@gmail.com locally with a password THEY
     * chose — the email is never verified, so the row just sits there. When
     * the real owner later signs in with Google, we must not leave that
     * attacker-set password working on the now-adopted account.
     *
     * The provider's flow proves the mailbox belongs to whoever just
     * authenticated, so the row is promoted to verified — and the password is
     * overwritten with an unusable random value (exactly what a fresh social
     * account gets) and the remember token rotated, severing any access the
     * previous password holder had. The rightful owner continues with the
     * provider, or uses password reset to set one they actually control.
     */
    private function adoptUnverifiedAccount(User $user): void
    {
        $user->forceFill([
            'email_verified_at' => now(),
            'password' => bcrypt(Str::random(40)),
            'remember_token' => Str::random(60),
        ])->save();
    }

    private function createFromSocial(string $email, string $name): User
    {
        [$first, $last] = $this->splitName($name);

        $username = $this->uniqueUsername(
            Str::slug(Str::before($email, '@'), '') ?: 'user'
        );

        $user = User::create([
            'first_name' => $first ?: 'Jambo',
            'last_name' => $last ?: 'Viewer',
            'username' => $username,
            'email' => $email,
            'password' => bcrypt(Str::random(40)),  // unusable password
        ]);

        // forceFill, not create(): email_verified_at is deliberately absent
        // from $fillable (mass-assignable self-verification would be a hole),
        // so inside the create() payload it was silently dropped and every
        // Google signup landed as "Pending".
        $user->forceFill(['email_verified_at' => now()])->save();

        if (Role::where('name', 'user')->exists()) {
            $user->assignRole('user');
        }

        // Same signal the form-registration path sends. The Referrals listener
        // rides on it to default the referral code and record a pending
        // attribution from the ?ref= cookie; the stock verification-email
        // listener no-ops because the account is created already verified.
        event(new Registered($user));

        return $user;
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full), 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    /**
     * A free username, derived from the email's local part.
     *
     * The manual signup form runs the ReservedUsername rule; this
     * auto-generated path must too, or admin@gmail.com signing in with Google
     * would mint the username "admin" — a route collision (/{username} profile
     * URLs) and an impersonation handle. Reserved bases get a numeric suffix
     * immediately.
     */
    private function uniqueUsername(string $base): string
    {
        $base = substr($base, 0, 40) ?: 'user';

        $reserved = in_array(strtolower($base), ReservedUsername::RESERVED, true);

        $candidate = $reserved ? $base . '1' : $base;
        $n = $reserved ? 2 : 1;

        // Free against BOTH columns — the username becomes this account's
        // referral code, so squatting on someone's custom code would leave the
        // new user without one.
        while (User::where('username', $candidate)->orWhere('referral_code', $candidate)->exists()) {
            $candidate = $base . $n++;
        }

        return $candidate;
    }
}
