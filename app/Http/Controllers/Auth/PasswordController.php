<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Kick every other device: rotates the remember token and
        // refreshes THIS session's password hash so AuthenticateSession
        // (web group) logs the other sessions out on their next request.
        // Passing the new password (it's current as of the update above)
        // keeps the session the user changed it from alive.
        Auth::logoutOtherDevices($validated['password']);

        return back()->with('status', 'password-updated');
    }
}
