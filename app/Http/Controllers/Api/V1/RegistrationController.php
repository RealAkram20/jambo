<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\SignupAttempt;
use App\Models\User;
use App\Rules\ReservedUsername;
use App\Services\DeviceTokenIssuer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Sign-up from the app.
 *
 * The validation, the default role, the Registered event and the
 * SignupAttempt logging are the website's — RegisteredUserController is the
 * reference — because an account created here must be indistinguishable from
 * one created in a browser.
 *
 * Two of the website's defences do NOT port, and pretending otherwise would be
 * worse than saying so:
 *
 *   * The honeypot is a hidden form field that naive bots fill in. A native
 *     form has no DOM for a bot to scrape, so the field would be theatre; a
 *     script hitting this endpoint directly would simply omit it.
 *   * reCAPTCHA v3 is a browser token. There is no equivalent an Android app
 *     can produce without adding Play Integrity, which is its own decision.
 *
 * What carries the weight instead: the `auth` throttle on the route (keyed on
 * email plus IP, so carrier NAT does not make one bucket per cell tower), and
 * the same SignupAttempt log the web form writes, so an abusive pattern here
 * is visible in the same place support already looks. **If app signup is ever
 * abused, Play Integrity is the answer, not a honeypot.**
 */
class RegistrationController extends Controller
{
    public function store(Request $request, DeviceTokenIssuer $issuer): JsonResponse
    {
        try {
            $data = $request->validate([
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                // Unique against referral_code too: the username becomes this
                // account's referral code, and colliding with another user's
                // custom code would abort that defaulting.
                'username' => [
                    'required', 'string', 'min:3', 'max:50',
                    'regex:/^[a-zA-Z0-9_.\-]+$/',
                    new ReservedUsername(),
                    'unique:' . User::class,
                    'unique:users,referral_code',
                ],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                ...DeviceTokenIssuer::deviceRules(),
            ]);
        } catch (ValidationException $e) {
            SignupAttempt::log($request, SignupAttempt::OUTCOME_VALIDATION, [
                'errors' => $e->errors(),
                'channel' => 'api',
            ]);
            throw $e;
        }

        try {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
        } catch (\Throwable $e) {
            SignupAttempt::log($request, SignupAttempt::OUTCOME_EXCEPTION, [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'channel' => 'api',
            ]);
            throw $e;
        }

        // Default role so RBAC checks work without an admin touching every new
        // signup. The `admin` role stays hand-assigned.
        if (Role::where('name', 'user')->exists()) {
            $user->assignRole('user');
        }

        // Sends the verification email, and the Referrals listener rides on it
        // to default the referral code and record a pending attribution.
        event(new Registered($user));

        SignupAttempt::log($request, SignupAttempt::OUTCOME_SUCCESS, [
            'user_id' => $user->id,
            'channel' => 'api',
        ]);

        // Signed in immediately, as the website does. The account is usable
        // before the email is verified; the app shows a verify banner rather
        // than a wall, matching the site.
        return ApiResponse::created(
            $issuer->issue($user, $data['device']) + ['email_verified' => false],
            'Welcome to ' . config('app.name') . '. Check your inbox to verify your email.',
        );
    }
}
