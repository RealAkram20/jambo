<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SignupAttempt;
use App\Models\User;
use App\Rules\ReservedUsername;
use App\Services\RecaptchaService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * Every branch of this method writes a SignupAttempt row before
     * returning so support / admins can triage failures via the
     * /admin/diagnostics/signups page. CSRF (419) and throttle (429)
     * paths are logged at the exception-handler level since they
     * never reach this controller — see App\Exceptions\Handler.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Honeypot. The hidden `website` field is invisible to humans
        // (CSS + tabindex=-1 + autocomplete=off + aria-hidden) but
        // visible in the DOM, so naive bots happily fill it. A
        // non-empty value here is a bot signature: we silently
        // succeed (so the bot scoring engine moves on) without
        // creating any row. Real users never see this field — UNLESS
        // their password manager auto-filled it. The signup_attempts
        // log lets us spot the second case without changing this
        // behaviour: an honest IP repeatedly hitting the honeypot
        // means we should drop the field rather than the user.
        if (filled($request->input('website'))) {
            Log::info('[register] honeypot triggered', ['ip' => $request->ip()]);
            SignupAttempt::log($request, SignupAttempt::OUTCOME_HONEYPOT, [
                'website_value' => substr((string) $request->input('website'), 0, 100),
            ]);
            return redirect('/')->with('status', 'Welcome to ' . config('app.name') . '!');
        }

        // Optional reCAPTCHA — only enforced when the admin has wired
        // a key pair via /admin/settings. With keys absent, this is a
        // pass-through and the honeypot + throttle do the heavy
        // lifting alone.
        if (!RecaptchaService::verify($request->input('g-recaptcha-response'), 'register')) {
            SignupAttempt::log($request, SignupAttempt::OUTCOME_RECAPTCHA_FAIL, [
                'has_token' => filled($request->input('g-recaptcha-response')),
            ]);
            throw ValidationException::withMessages([
                'email' => 'reCAPTCHA verification failed. Please try again.',
            ]);
        }

        try {
            $data = $request->validate([
                'first_name' => ['required', 'string', 'max:100'],
                'last_name'  => ['required', 'string', 'max:100'],
                'username'   => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_.\-]+$/', new ReservedUsername(), 'unique:'.User::class],
                'email'      => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
                'password'   => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
        } catch (ValidationException $e) {
            SignupAttempt::log($request, SignupAttempt::OUTCOME_VALIDATION, [
                'errors' => $e->errors(),
            ]);
            throw $e;
        }

        try {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'username'   => $data['username'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
            ]);
        } catch (\Throwable $e) {
            // DB outage, unique-constraint race that slipped past the
            // validator, etc. Log and rethrow so the user sees the
            // standard 500 page (they already know something failed,
            // and we now have a record of why).
            SignupAttempt::log($request, SignupAttempt::OUTCOME_EXCEPTION, [
                'class'   => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        // Default role so RBAC checks work without an admin having to
        // touch every new signup. The `admin` role stays hand-assigned.
        if (method_exists($user, 'assignRole') && \Spatie\Permission\Models\Role::where('name', 'user')->exists()) {
            $user->assignRole('user');
        }

        event(new Registered($user));

        Auth::login($user);

        SignupAttempt::log($request, SignupAttempt::OUTCOME_SUCCESS, [
            'user_id' => $user->id,
        ]);

        // New signups are always regular users (the 'admin' role is
        // hand-assigned only), so we send them to the public frontend,
        // never to the admin dashboard. The status flash mentions the
        // verification email AND reminds them to check spam — during
        // our launch phase Gmail / Outlook frequently fold our mail
        // there until our sender reputation builds. The verify-email
        // page itself carries the same notice for users who hit it.
        return redirect('/')
            ->with('status', "Welcome to " . config('app.name') . '! '
                . "We've sent a verification link to {$user->email} — "
                . "if you don't see it in a couple of minutes, check your Spam folder.");
    }
}
