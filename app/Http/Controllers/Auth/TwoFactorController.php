<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Enable / confirm / disable / recovery-codes management for 2FA.
 *
 * Flow:
 *   1. User hits "Enable 2FA" (POST /account/two-factor/enable)
 *   2. Service generates a pending secret + recovery codes
 *   3. User scans QR in their authenticator app, enters the 6-digit
 *      code, hits Confirm (POST /account/two-factor/confirm)
 *   4. On successful verify, `two_factor_confirmed_at` is set and the
 *      feature goes live — next login will require an OTP.
 *
 * All endpoints behind auth + password.confirm so a stolen session
 * cookie can't silently disable 2FA.
 *
 * Post-action redirect follows the acting user: admins land back on
 * `dashboard.profile` (admins can't reach the profile hub at all
 * because `ProfileHubController::resolveOwn()` bounces them to /app),
 * everyone else lands on the user-side security page.
 */
class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorAuthentication $twoFactor)
    {
    }

    /** Start setup — generate pending secret + recovery codes. */
    public function enable(Request $request): RedirectResponse
    {
        $this->twoFactor->generatePendingSetup($request->user());
        return $this->redirectToSecurity($request)
            ->with('status-2fa', 'Scan the QR code with your authenticator, then confirm below.');
    }

    /** Finalise setup — verify one OTP. */
    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string|size:6']);

        if (!$this->twoFactor->confirm($request->user(), $data['code'])) {
            return back()->withErrors(['code' => 'That code did not match. Try the next one your app shows.']);
        }

        return $this->redirectToSecurity($request)
            ->with('status-2fa', 'Two-factor authentication is now enabled. Store your recovery codes somewhere safe.');
    }

    /** Turn 2FA off — requires password confirmation middleware. */
    public function disable(Request $request): RedirectResponse
    {
        $this->twoFactor->disable($request->user());
        return $this->redirectToSecurity($request)
            ->with('status-2fa', 'Two-factor authentication disabled.');
    }

    /** Issue a new batch of 8 recovery codes. Old codes become invalid. */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $this->twoFactor->regenerateRecoveryCodes($request->user());
        return $this->redirectToSecurity($request)
            ->with('status-2fa', 'New recovery codes generated. Save them somewhere safe — old ones no longer work.');
    }

    /**
     * Admins return to the admin profile page; everyone else goes to
     * the user-side security tab. This avoids `account.security` →
     * `profile.security` → resolveOwn() bouncing admins to /app,
     * which made the flow look broken from the admin side.
     */
    private function redirectToSecurity(Request $request): RedirectResponse
    {
        return $request->user()->hasRole('admin')
            ? redirect()->route('dashboard.profile')
            : redirect()->route('account.security');
    }
}
