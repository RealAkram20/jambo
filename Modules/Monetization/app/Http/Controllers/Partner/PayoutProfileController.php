<?php

namespace Modules\Monetization\app\Http\Controllers\Partner;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Monetization\app\Services\AuditLogger;
use Modules\Monetization\app\Services\MonetizationSettings;

class PayoutProfileController extends PartnerBaseController
{
    public function edit()
    {
        return view('monetization::partner.payout-profile', [
            'partner' => $this->partner(),
        ]);
    }

    /**
     * Submitting (or changing) payout details always lands in
     * pending_review. If a VERIFIED profile existed before, the
     * account-takeover brake engages: withdrawals freeze for the
     * cooldown window on top of requiring re-verification.
     */
    public function update(Request $request): RedirectResponse
    {
        $partner = $this->partner();

        $data = $request->validate([
            'payout_msisdn' => ['required', 'string', 'regex:/^(\+?256|0)?7\d{8}$/'],
            'payout_name' => 'required|string|max:190',
            'payout_network' => ['required', Rule::in(MonetizationPartner::NETWORKS)],
        ]);

        $unchanged = $partner->payout_msisdn === $data['payout_msisdn']
            && $partner->payout_name === $data['payout_name']
            && $partner->payout_network === $data['payout_network'];

        if ($unchanged) {
            return back()->with('success', 'Payout profile unchanged.');
        }

        $hadVerifiedProfile = $partner->payoutVerified();
        $before = $partner->only(['payout_msisdn', 'payout_name', 'payout_network', 'payout_status']);

        $partner->update($data + [
            'payout_status' => MonetizationPartner::PAYOUT_PENDING_REVIEW,
            'payout_verified_at' => null,
            'payout_verified_by' => null,
            'payout_locked_until' => $hadVerifiedProfile
                ? now()->addDays(MonetizationSettings::payoutChangeCooldownDays())
                : null,
        ]);

        AuditLogger::logDiff(
            $hadVerifiedProfile ? 'payout_profile.changed' : 'payout_profile.submitted',
            $partner,
            $before,
            $partner->only(['payout_msisdn', 'payout_name', 'payout_network', 'payout_status']),
        );

        return back()->with('success', $hadVerifiedProfile
            ? 'Details submitted for re-verification. As a security measure, withdrawals are paused for '.MonetizationSettings::payoutChangeCooldownDays().' day(s).'
            : 'Payout details submitted — an admin will verify them shortly.');
    }
}
