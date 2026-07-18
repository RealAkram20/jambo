<?php

namespace Modules\Referrals\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Referrals\app\Services\ReferralWalletService;
use Modules\Subscriptions\app\Models\SubscriptionTier;

/**
 * Wallet actions for the signed-in referrer: pay for a subscription
 * with the balance (full cover only) and request a cash withdrawal.
 * Deliberately NOT gated on ReferralSettings::active() — earned money
 * stays usable when the program is off.
 */
class ReferralWalletController extends Controller
{
    public function __construct(private ReferralWalletService $wallet)
    {
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tier_slug' => 'required|string|exists:subscription_tiers,slug',
        ]);

        $tier = SubscriptionTier::where('slug', $data['tier_slug'])->firstOrFail();

        try {
            $this->wallet->spendOnTier($request->user(), $tier);
        } catch (\RuntimeException $e) {
            return redirect()->route('frontend.pricing-page')->with('error', $e->getMessage());
        }

        return redirect()
            ->route('frontend.pricing-page')
            ->with('success', __('Paid with your wallet — :tier is now active.', ['tier' => $tier->name]));
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payee_name' => 'required|string|max:100',
            'payee_msisdn' => 'required|string|max:30|regex:/^[0-9+ ]{7,30}$/',
        ]);

        $user = $request->user();

        try {
            $withdrawal = $this->wallet->requestWithdrawal(
                $user,
                (string) $data['amount'],
                trim($data['payee_name']),
                trim($data['payee_msisdn']),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Same event the partner flow fires — the notification
        // subscriber fans it out to the clerks.
        event(new \Modules\Notifications\app\Events\WithdrawalRequested($withdrawal));

        // back(): the form lives on the profile hub AND the admin
        // Referrals hub — land wherever it was submitted from.
        return back()->with('status', 'Withdrawal requested — you will be notified when it is reviewed.');
    }
}
