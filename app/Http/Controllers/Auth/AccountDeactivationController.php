<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Soft account deactivation.
 *
 * Sets `users.deactivated_at = now()` and immediately logs the user
 * out. Their row + all relations stay — revenue / watch history /
 * reviews are untouched so the business keeps its analytics and the
 * user can ask support to reactivate later.
 *
 * For full GDPR "erase my data": separate workflow, admin-triggered.
 */
class AccountDeactivationController extends Controller
{
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'confirm'  => 'accepted',
        ]);

        $user = $request->user();

        if (!Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'That password is not correct.',
            ]);
        }

        $user->forceFill(['deactivated_at' => now()])->save();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')
            ->with('status', 'Your account has been deactivated. Email support if you change your mind.');
    }
}
