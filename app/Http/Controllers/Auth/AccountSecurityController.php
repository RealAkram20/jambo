<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthentication;
use Illuminate\Http\Request;

/**
 * Single page that surfaces all security controls for the signed-in
 * user: password change, 2FA status + setup, recovery codes, social
 * links, deactivation.
 *
 * Write endpoints for each of those live in their own focused
 * controllers (TwoFactorController, PasswordController,
 * AccountDeactivationController) — this controller only renders.
 */
class AccountSecurityController extends Controller
{
    public function __construct(private TwoFactorAuthentication $twoFactor)
    {
    }

    public function show(Request $request)
    {
        $user = $request->user();

        $hasPendingSetup = !is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at);
        $is2faEnabled    = $user->hasEnabledTwoFactorAuthentication();

        $qrSvg = ($hasPendingSetup || $is2faEnabled)
            ? $this->twoFactor->qrCodeSvg($user)
            : null;

        $manualSecret = ($hasPendingSetup || $is2faEnabled)
            ? $this->twoFactor->secretForManualEntry($user)
            : null;

        // Show the recovery-codes pane when:
        //   - 2FA is enabled AND the user hasn't hidden them in this
        //     session (we surface them as a one-time banner after
        //     generation; returning users see a "View" toggle instead).
        $recoveryCodes = $is2faEnabled
            ? $this->twoFactor->getRecoveryCodes($user)
            : [];

        return view('account.security', [
            'user'            => $user,
            'hasPendingSetup' => $hasPendingSetup,
            'is2faEnabled'    => $is2faEnabled,
            'qrSvg'           => $qrSvg,
            'manualSecret'    => $manualSecret,
            'recoveryCodes'   => $recoveryCodes,
            'googleEnabled'   => (bool) config('services.google.client_id'),
        ]);
    }
}
