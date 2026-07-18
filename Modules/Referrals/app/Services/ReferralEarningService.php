<?php

namespace Modules\Referrals\app\Services;

use App\Models\User;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Services\Ledger;

/**
 * Thin adapter over the universal Wallet ledger for referral money —
 * fixes the owner to a User and the credit type to referral_reward.
 * All money mechanics (owner lock, per-currency balance, overdraw,
 * reference idempotency) live in Wallet\Services\Ledger.
 */
class ReferralEarningService
{
    public function __construct(private Ledger $ledger)
    {
    }

    /**
     * Credit a reward onto a user's wallet. Call INSIDE a transaction.
     * Returns null when this reference was already credited (replay).
     */
    public function credit(
        int $referrerId,
        string $amount,
        string $currency,
        PaymentOrder $order,
        ?int $referralId,
        string $memo,
    ): ?LedgerEntry {
        if (bccomp($amount, '0', 2) <= 0) {
            return null;
        }

        return $this->ledger->append(
            owner: User::findOrFail($referrerId),
            type: LedgerEntry::TYPE_REFERRAL_REWARD,
            amount: $amount,
            currency: $currency,
            reference: $order,
            memo: $memo,
            meta: $referralId ? ['referral_id' => $referralId] : null,
        );
    }

    public function balanceFor(User $user, ?string $currency = null): string
    {
        return $this->ledger->balanceFor($user, $currency);
    }

    /** Lifetime referral commissions earned on the user's own wallet. */
    public function totalEarnedFor(User $user, ?string $currency = null): string
    {
        return $this->ledger->totalOfType($user, LedgerEntry::TYPE_REFERRAL_REWARD, $currency);
    }
}
