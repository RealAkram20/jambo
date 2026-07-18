<?php

namespace Modules\Referrals\app\Services;

use App\Models\User;
use Modules\Referrals\app\Models\Referral;

/**
 * View data for the user-facing "Refer & Earn" hub tab. Identity of
 * referred users is masked here, in one place — the page never sees a
 * full name or email.
 */
class ReferralDashboardService
{
    public function __construct(private ReferralEarningService $earnings)
    {
    }

    public function forUser(User $user): array
    {
        $code = $user->referral_code ?: $user->username;

        $referrals = Referral::where('referrer_id', $user->id)
            ->with('referredUser:id,username,first_name,last_name,email')
            ->latest()
            ->paginate(10);

        $referrals->getCollection()->transform(function (Referral $referral) {
            $referral->setAttribute('masked_name', $this->maskName($referral->referredUser));
            $referral->setAttribute('masked_email', $this->maskEmail($referral->referredUser?->email));
            return $referral;
        });

        // Enrolled partners earn NEW rewards into their Creator Studio
        // wallet; their referral-wallet balance here only holds money
        // earned before enrollment (still withdrawable below).
        $isPartner = $this->enrolledPartnerId($user) !== null;
        $partnerEarned = $isPartner ? $this->partnerReferralEarned($user) : '0';

        return [
            'code' => $code,
            'link' => $code ? url('/') . '?ref=' . $code : url('/'),
            'balance' => $this->earnings->balanceFor($user),
            'totalEarned' => $this->earnings->totalEarnedFor($user),
            'totalReferrals' => Referral::where('referrer_id', $user->id)->count(),
            'qualifiedCount' => Referral::where('referrer_id', $user->id)
                ->where('status', Referral::STATUS_QUALIFIED)
                ->count(),
            'referrals' => $referrals,
            'discountPercent' => ReferralSettings::discountPercent(),
            'rewardPercent' => ReferralSettings::rewardPercent(),
            'currency' => config('payments.currency', 'UGX'),
            'minWithdrawal' => ReferralSettings::minWithdrawal(),
            'withdrawals' => \Modules\Wallet\app\Models\WithdrawalRequest::query()
                ->where('owner_type', $user->getMorphClass())
                ->where('owner_id', $user->id)
                ->orderByDesc('requested_at')
                ->limit(10)
                ->get(),
            'hasOpenWithdrawal' => \Modules\Wallet\app\Models\WithdrawalRequest::query()
                ->where('owner_type', $user->getMorphClass())
                ->where('owner_id', $user->id)
                ->whereIn('status', \Modules\Wallet\app\Models\WithdrawalRequest::OPEN_STATUSES)
                ->exists(),
            'isPartner' => $isPartner,
            'partnerEarned' => $partnerEarned,
        ];
    }

    private function enrolledPartnerId(User $user): ?int
    {
        if (!class_exists(\Modules\Monetization\app\Models\MonetizationPartner::class)) {
            return null;
        }

        return \Modules\Monetization\app\Models\MonetizationPartner::query()
            ->where('user_id', $user->id)
            ->where('status', \Modules\Monetization\app\Models\MonetizationPartner::STATUS_ENROLLED)
            ->value('id');
    }

    /** Referral rewards that landed on the partner-profile wallet. */
    private function partnerReferralEarned(User $user): string
    {
        $partnerId = $this->enrolledPartnerId($user);
        if (!$partnerId) {
            return '0';
        }

        $partner = \Modules\Monetization\app\Models\MonetizationPartner::find($partnerId);

        return $partner
            ? app(\Modules\Wallet\app\Services\Ledger::class)
                ->totalOfType($partner, \Modules\Wallet\app\Models\LedgerEntry::TYPE_REFERRAL_REWARD)
            : '0';
    }

    private function maskName(?User $user): string
    {
        if (!$user) {
            return 'Deleted account';
        }

        $first = trim((string) $user->first_name);
        if ($first !== '') {
            $lastInitial = strtoupper(substr(trim((string) $user->last_name), 0, 1));
            return $lastInitial !== '' ? "{$first} {$lastInitial}." : $first;
        }

        return (string) ($user->username ?: 'Member');
    }

    private function maskEmail(?string $email): string
    {
        if (!$email || !str_contains($email, '@')) {
            return '—';
        }

        [$local, $domain] = explode('@', $email, 2);

        return substr($local, 0, 1) . '•••@' . $domain;
    }
}
