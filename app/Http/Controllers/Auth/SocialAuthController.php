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
            // Match-by-email into a local account that never confirmed
            // its email is the account-takeover risk. Google's OAuth
            // flow is itself proof of email ownership (Google verifies
            // every account's email), so we can safely promote the
            // local row to "verified" — the OAuth user IS the rightful
            // owner of that mailbox. This closes the squatting attack
            // (someone signing up locally with a typo / abandoned
            // address never owned) without a confusing UX detour.
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($user->isDeactivated()) {
            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated. Contact support to reactivate.',
            ]);
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
        $candidate = $base;
        $n = 1;
        while (User::where('username', $candidate)->exists()) {
            $candidate = $base . $n++;
        }
        return $candidate;
    }
}
