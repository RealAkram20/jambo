<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthentication;
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
 */
class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorAuthentication $twoFactor)
    {
    }

    /** Start setup — generate pending secret + recovery codes. */
    public function enable(Request $request)
    {
        $this->twoFactor->generatePendingSetup($request->user());
        return redirect()->route('account.security')
            ->with('status', 'Scan the QR code with your authenticator, then confirm below.');
    }

    /** Finalise setup — verify one OTP. */
    public function confirm(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|size:6']);

        if (!$this->twoFactor->confirm($request->user(), $data['code'])) {
            return back()->withErrors(['code' => 'That code did not match. Try the next one your app shows.']);
        }

        return redirect()->route('account.security')
            ->with('status', 'Two-factor authentication is now enabled. Store your recovery codes somewhere safe.');
    }

    /** Turn 2FA off — requires password confirmation middleware. */
    public function disable(Request $request)
    {
        $this->twoFactor->disable($request->user());
        return redirect()->route('account.security')
            ->with('status', 'Two-factor authentication disabled.');
    }

    /** Issue a new batch of 8 recovery codes. Old codes become invalid. */
    public function regenerateRecoveryCodes(Request $request)
    {
        $this->twoFactor->regenerateRecoveryCodes($request->user());
        return redirect()->route('account.security')
            ->with('status', 'New recovery codes generated. Save them somewhere safe — old ones no longer work.');
    }
}
