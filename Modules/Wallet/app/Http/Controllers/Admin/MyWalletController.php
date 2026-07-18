<?php

namespace Modules\Wallet\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Models\WithdrawalRequest;
use Modules\Wallet\app\Services\Ledger;

/**
 * The signed-in staff member's OWN wallet, inside the admin panel —
 * the same universal wallet users see in the profile hub and partners
 * see in the Creator Studio. Both earning streams land here:
 * performance credits (pay-per-upload) and referral rewards.
 */
class MyWalletController extends Controller
{
    public function index(Request $request, Ledger $ledger)
    {
        $user = $request->user();

        return view('wallet::admin.my-wallet', [
            'currency' => config('payments.currency', 'UGX'),
            'balance' => $ledger->balanceFor($user),
            'performanceEarned' => $ledger->totalOfType($user, LedgerEntry::TYPE_PERFORMANCE_CREDIT),
            'referralEarned' => $ledger->totalOfType($user, LedgerEntry::TYPE_REFERRAL_REWARD),
            'entries' => $ledger->entriesFor($user)->paginate(15),
            'minWithdrawal' => \Modules\Referrals\app\Services\ReferralSettings::minWithdrawal(),
            'withdrawals' => WithdrawalRequest::query()
                ->where('owner_type', $user->getMorphClass())
                ->where('owner_id', $user->id)
                ->orderByDesc('requested_at')
                ->limit(10)
                ->get(),
            'hasOpenWithdrawal' => WithdrawalRequest::query()
                ->where('owner_type', $user->getMorphClass())
                ->where('owner_id', $user->id)
                ->whereIn('status', WithdrawalRequest::OPEN_STATUSES)
                ->exists(),
        ]);
    }
}
