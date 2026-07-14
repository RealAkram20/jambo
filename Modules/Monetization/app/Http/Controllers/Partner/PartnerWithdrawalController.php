<?php

namespace Modules\Monetization\app\Http\Controllers\Partner;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Monetization\app\Models\WalletEntry;
use Modules\Monetization\app\Models\WithdrawalRequest;
use Modules\Monetization\app\Services\AuditLogger;
use Modules\Monetization\app\Services\MonetizationSettings;
use Modules\Monetization\app\Services\WalletService;

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
     * Everything money-critical is re-checked INSIDE the transaction,
     * under the partner-row lock WalletService takes: balance, open
     * requests, verified profile, cooldown. Two racing submissions
     * serialize on the lock — the loser sees the hold and fails.
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
            $withdrawal = DB::transaction(function () use ($partner, $amount, $request) {
                // Serialize on the partner row before every check.
                $locked = MonetizationPartner::query()->lockForUpdate()->findOrFail($partner->id);

                if ($locked->withdrawals()->whereIn('status', WithdrawalRequest::OPEN_STATUSES)->exists()) {
                    throw new \RuntimeException('You already have a withdrawal in progress.');
                }

                if (bccomp($locked->walletBalance(), $amount, 2) < 0) {
                    throw new \RuntimeException('That amount exceeds your wallet balance.');
                }

                $withdrawal = WithdrawalRequest::create([
                    'partner_id' => $locked->id,
                    'amount' => $amount,
                    'status' => WithdrawalRequest::STATUS_REQUESTED,
                    'payout_msisdn_snapshot' => $locked->payout_msisdn,
                    'payout_name_snapshot' => $locked->payout_name,
                    'payout_network_snapshot' => $locked->payout_network,
                    'requested_at' => now(),
                ]);

                $hold = app(WalletService::class)->append(
                    partner: $locked,
                    type: WalletEntry::TYPE_WITHDRAWAL_HOLD,
                    amount: bcmul($amount, '-1', 2),
                    reference: $withdrawal,
                    memo: 'Withdrawal hold',
                    createdBy: $request->user()->id,
                );

                $withdrawal->update(['hold_entry_id' => $hold?->id]);

                AuditLogger::log('withdrawal.requested', $withdrawal, ['after' => [
                    'amount' => $amount,
                    'msisdn' => $locked->payout_msisdn,
                ]]);

                return $withdrawal;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        event(new \Modules\Notifications\app\Events\WithdrawalRequested($withdrawal));

        return redirect()
            ->route('partner.withdrawals.index')
            ->with('success', 'Withdrawal requested — you will be notified when it is reviewed.');
    }
}
