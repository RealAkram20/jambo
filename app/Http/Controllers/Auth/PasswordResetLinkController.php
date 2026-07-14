<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RecaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Honeypot — see RegisteredUserController for the rationale.
        // Mirror behavior so a bot probing both endpoints sees an
        // identical "successful" response shape.
        if (filled($request->input('website'))) {
            Log::info('[forgot-password] honeypot triggered', ['ip' => $request->ip()]);
            return back()->with('status', __(Password::RESET_LINK_SENT));
        }

        if (!RecaptchaService::verify($request->input('g-recaptcha-response'), 'forgot_password')) {
            throw ValidationException::withMessages([
                'email' => 'reCAPTCHA verification failed. Please try again.',
            ]);
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Attempt the send, but show the SAME response whether or not
        // the email exists (or is throttled). Echoing the broker's
        // real status ("We can't find a user with that email…") turns
        // this form into an account-enumeration oracle — an attacker
        // could probe which emails have accounts. The mail still only
        // goes out when the account is real.
        Password::sendResetLink($request->only('email'));

        return back()->with('status',
            "If an account exists for that email, we've sent a password reset link. Check your inbox and spam folder.");
    }
}
