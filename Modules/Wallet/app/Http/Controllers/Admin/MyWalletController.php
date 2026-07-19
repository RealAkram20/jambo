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
 *
 * Earnings figures are sums of CREDIT entries only, so a withdrawal
 * never changes "what I made in March" — the balance goes down, the
 * month's earnings don't. That split is what lets staff work for
 * months without withdrawing and still see both numbers truthfully.
 */
class MyWalletController extends Controller
{
    /** Entry types that count as "earned" (never refunds/hold releases). */
    private const EARNING_TYPES = [
        LedgerEntry::TYPE_PERFORMANCE_CREDIT,
        LedgerEntry::TYPE_REFERRAL_REWARD,
    ];

    public function index(Request $request, Ledger $ledger)
    {
        $user = $request->user();

        $earnings = fn () => LedgerEntry::query()
            ->where('owner_type', $user->getMorphClass())
            ->where('owner_id', $user->id)
            ->whereIn('type', self::EARNING_TYPES);

        // Most recent completed payout. paid_at is stamped on settle;
        // fall back to requested_at for legacy rows that predate it.
        $lastPayout = WithdrawalRequest::query()
            ->where('owner_type', $user->getMorphClass())
            ->where('owner_id', $user->id)
            ->where('status', WithdrawalRequest::STATUS_PAID)
            ->orderByDesc('paid_at')
            ->orderByDesc('requested_at')
            ->first();
        $lastPayoutAt = $lastPayout?->paid_at ?? $lastPayout?->requested_at;

        // Month-by-month earning credits, last 12 months, grouped in PHP
        // so the same code runs on MySQL and the sqlite test database.
        $monthly = [];
        $rows = $earnings()
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->get(['type', 'amount', 'created_at']);
        foreach ($rows as $e) {
            $ym = $e->created_at->format('Y-m');
            $bucket = $e->type === LedgerEntry::TYPE_PERFORMANCE_CREDIT ? 'performance' : 'referral';
            $monthly[$ym][$bucket] = bcadd($monthly[$ym][$bucket] ?? '0', (string) $e->amount, 2);
        }
        krsort($monthly);

        return view('wallet::admin.my-wallet', [
            'currency' => config('payments.currency', 'UGX'),
            'balance' => $ledger->balanceFor($user),
            'performanceEarned' => $ledger->totalOfType($user, LedgerEntry::TYPE_PERFORMANCE_CREDIT),
            'referralEarned' => $ledger->totalOfType($user, LedgerEntry::TYPE_REFERRAL_REWARD),
            'earnedThisMonth' => (string) ($earnings()->where('created_at', '>=', now()->startOfMonth())->sum('amount') ?: '0'),
            'lastPayout' => $lastPayout,
            'earnedSinceLastPayout' => $lastPayoutAt
                ? (string) ($earnings()->where('created_at', '>', $lastPayoutAt)->sum('amount') ?: '0')
                : null,
            'monthlyEarnings' => $monthly,
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
