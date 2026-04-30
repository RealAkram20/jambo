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

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                            ->withErrors(['email' => __($status)]);
    }
}
