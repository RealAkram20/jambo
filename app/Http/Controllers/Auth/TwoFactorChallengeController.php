<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthentication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Post-password-check OTP prompt.
 *
 * AuthenticatedSessionController validates email+password and — if
 * the user has 2FA enabled — parks their id in the session under
 * `login.id` and redirects here *without* calling Auth::login().
 * Only once the TOTP or recovery code validates do we actually
 * promote them to an authenticated session.
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(private TwoFactorAuthentication $twoFactor)
    {
    }

    public function show(Request $request)
    {
        abort_unless($request->session()->has('login.id'), 403);
        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code'          => 'nullable|string|size:6',
            'recovery_code' => 'nullable|string|min:8|max:20',
        ]);

        // At least one of the two has to be present.
        if (!$request->filled('code') && !$request->filled('recovery_code')) {
            return back()->withErrors(['code' => 'Enter a code from your authenticator app or a recovery code.']);
        }

        $userId = $request->session()->get('login.id');
        abort_unless($userId, 403);

        $user = User::findOrFail($userId);

        $ok = $request->filled('code')
            ? $this->twoFactor->verifyTotp($user, $request->input('code'))
            : $this->twoFactor->verifyRecoveryCode($user, $request->input('recovery_code'));

        if (!$ok) {
            return back()->withErrors(['code' => 'That code did not match.']);
        }

        Auth::login($user, $request->session()->pull('login.remember', false));
        $request->session()->forget('login.id');
        $request->session()->regenerate();

        return redirect()->intended(config('auth.home', '/'));
    }
}
