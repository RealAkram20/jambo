<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\SocialAccountResolver;
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

        // Who owns this address - including the account-takeover mitigation
        // for a local row that never verified its email - is decided by
        // SocialAccountResolver, which /api/v1/auth/google calls too. Security
        // logic in two places is how it drifts. See SocialAuthPinTest.
        $user = app(SocialAccountResolver::class)->resolve($email, $social->getName());

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

}
