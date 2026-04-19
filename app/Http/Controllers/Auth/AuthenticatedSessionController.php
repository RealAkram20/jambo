<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Login happens in two possible shapes depending on the user:
     *
     *   - No 2FA enabled: we log them straight in.
     *   - 2FA enabled:    we stash their id in the session and
     *                     redirect to the OTP challenge screen. The
     *                     session flag `login.id` is the only thing
     *                     the challenge controller trusts — a naked
     *                     POST to the challenge URL 403s without it.
     *
     * Deactivated users are rejected with a specific message so they
     * can ask support to reactivate.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $credentials = $request->only('email', 'password');

        $user = User::where('email', strtolower($credentials['email']))->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($request->throttleKey());
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        if ($user->isDeactivated()) {
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Contact support to reactivate.',
            ]);
        }

        RateLimiter::clear($request->throttleKey());

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $request->boolean('remember'));
            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Admins land on the admin dashboard; regular users land on
        // the public frontend. The two profiles never share a home
        // page — see memory/feedback_admin_vs_user_separation.md.
        $destination = $user->hasRole('admin') ? '/app' : '/';

        return redirect()->intended($destination);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
