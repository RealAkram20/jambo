<?php

namespace Modules\Monetization\app\Http\Controllers\Partner;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Monetization\app\Services\AuditLogger;
use Modules\Monetization\app\Services\MonetizationSettings;
use Modules\Wallet\app\Models\WithdrawalRequest;
use Modules\Wallet\app\Services\Payouts;

class PartnerWithdrawalController extends PartnerBaseController
{
    public function index()
    {
        $partner = $this->partner();

        return view('monetization::partner.withdrawals', [
            'partner' => $partner,
            'balance' => $partner->walletBalance(),
            'minWithdrawal' => MonetizationSettings::minWithdrawal(),
            'withdrawals' => $partner->withdrawals()->orderByDesc('requested_at')->paginate(15),
            'hasOpen' => $partner->withdrawals()->whereIn('status', WithdrawalRequest::OPEN_STATUSES)->exists(),
        ]);
    }

    /**
     * Partner POLICY checks live here (enrollment, verified payout
     * profile, cooldown, monetization minimum); the money mechanics —
     * owner lock, one-open-request, hold, overdraw — live in the
     * universal Payouts service.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);
        $amount = number_format((float) $data['amount'], 2, '.', '');

        $partner = $this->partner();

        if (!$partner->isEnrolled()) {
            return back()->with('error', 'Your monetization enrollment is suspended — contact support.');
        }
        if (!$partner->payoutVerified()) {
            return back()->with('error', 'Your payout profile must be verified before you can withdraw.');
        }
        if ($partner->payoutLocked()) {
            return back()->with('error', 'Withdrawals are paused until '.$partner->payout_locked_until->format('d M Y H:i').' because your payout details changed.');
        }
        if (bccomp($amount, MonetizationSettings::minWithdrawal(), 2) < 0) {
            return back()->with('error', 'Minimum withdrawal is UGX '.number_format((float) MonetizationSettings::minWithdrawal(), 0).'.');
        }

        try {
            $withdrawal = app(Payouts::class)->request(
                owner: $partner,
                amount: $amount,
                payeeName: (string) $partner->payout_name,
                payeeMsisdn: (string) $partner->payout_msisdn,
                payeeNetwork: $partner->payout_network,
                requestedBy: $request->user()->id,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLogger::log('withdrawal.requested', $withdrawal, ['after' => [
            'amount' => $amount,
            'msisdn' => $partner->payout_msisdn,
        ]]);

        event(new \Modules\Notifications\app\Events\WithdrawalRequested($withdrawal));

        return redirect()
            ->route('partner.withdrawals.index')
            ->with('success', 'Withdrawal requested — you will be notified when it is reviewed.');
    }
}
