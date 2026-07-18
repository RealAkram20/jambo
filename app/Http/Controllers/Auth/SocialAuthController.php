<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

/**
 * OAuth login via Socialite. Currently Google only; add providers by
 * extending $allowed and filling config/services.php.
 *
 * Match logic: if a user with the same email already exists we log
 * them into that account (same-email merge). Otherwise we create a
 * new User with email_verified_at set — Google/Apple already verified
 * the email, no need to re-send a confirmation.
 */
class SocialAuthController extends Controller
{
    private array $allowed = ['google'];

    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, $this->allowed, true), 404);
        abort_unless(config("services.{$provider}.client_id"), 503,
            'Social login is not configured for this provider.');

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        abort_unless(in_array($provider, $this->allowed, true), 404);
        abort_unless(config("services.{$provider}.client_id"), 503);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'We could not sign you in with ' . ucfirst($provider) . '. Please try again.']);
        }

        $email = strtolower((string) $social->getEmail());
        if (!$email) {
            return redirect()->route('login')
                ->withErrors(['email' => ucfirst($provider) . ' did not share an email. Please use email sign-in instead.']);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = $this->createFromSocial($email, $social);
        } elseif ($user->email_verified_at === null) {
            // Match-by-email into a local account that never confirmed its
            // email is the account-takeover risk. A squatter can register
            // victim@gmail.com locally with a password THEY chose (email
            // never verified, so the row just sits there); when the real
            // owner later signs in with Google, we must not leave that
            // attacker-set password working on the now-adopted account.
            //
            // Google's OAuth flow proves the mailbox belongs to whoever
            // just authenticated, so we promote the row to verified — but
            // we also overwrite the password with an unusable random value
            // (exactly what createFromSocial() seeds) and rotate the
            // remember token, severing any access the previous password
            // holder had. The rightful owner uses Google from here, or the
            // password-reset flow to set a new one they actually control.
            $user->forceFill([
                'email_verified_at' => now(),
                'password'          => bcrypt(Str::random(40)),
                'remember_token'    => Str::random(60),
            ])->save();
        }

        if ($user->isDeactivated()) {
            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated. Contact support to reactivate.',
            ]);
        }

        // A user who enabled 2FA gets the same OTP challenge here as
        // on password login — OAuth proves the Google account, not
        // possession of the authenticator device. Same session
        // handshake AuthenticatedSessionController uses.
        if ($user->hasEnabledTwoFactorAuthentication()) {
            request()->session()->put('login.id', $user->id);
            request()->session()->put('login.remember', true);
            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended('/');
    }

    private function createFromSocial(string $email, $social): User
    {
        // Split the provider's display name into first/last where we
        // can. Falls back to "Jambo user" if the provider only
        // shipped an email.
        [$first, $last] = $this->splitName((string) $social->getName());

        $username = $this->uniqueUsername(
            Str::slug(Str::before($email, '@'), '') ?: 'user'
        );

        $user = User::create([
            'first_name'        => $first ?: 'Jambo',
            'last_name'         => $last ?: 'Viewer',
            'username'          => $username,
            'email'             => $email,
            'password'          => bcrypt(Str::random(40)),  // unusable password
            'email_verified_at' => now(),
        ]);

        if (Role::where('name', 'user')->exists()) {
            $user->assignRole('user');
        }

        // Same signal the form-registration path sends. The Referrals
        // listener rides on it to default the referral code and record
        // a pending attribution from the ?ref= cookie; the stock
        // verification-email listener no-ops because the account is
        // created already verified.
        event(new \Illuminate\Auth\Events\Registered($user));

        return $user;
    }

    private function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full), 2);
        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    private function uniqueUsername(string $base): string
    {
        $base = substr($base, 0, 40) ?: 'user';

        // The manual signup form runs the ReservedUsername rule; this
        // auto-generated path must too, or admin@gmail.com signing in
        // with Google would mint the username "admin" — a route
        // collision (/{username} profile URLs) and an impersonation
        // handle. Reserved bases get a numeric suffix immediately.
        $reserved = in_array(strtolower($base), \App\Rules\ReservedUsername::RESERVED, true);

        $candidate = $reserved ? $base . '1' : $base;
        $n = $reserved ? 2 : 1;
        // Free against BOTH columns — the username becomes this account's
        // referral code, so squatting on someone's custom code would leave
        // the new user without one.
        while (User::where('username', $candidate)->orWhere('referral_code', $candidate)->exists()) {
            $candidate = $base . $n++;
        }
        return $candidate;
    }
}
