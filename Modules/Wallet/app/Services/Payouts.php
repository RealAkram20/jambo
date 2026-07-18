<?php

namespace Modules\Wallet\app\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Models\WithdrawalRequest;
use RuntimeException;

/**
 * Opens and settles withdrawal requests against the universal ledger.
 * Policy checks that differ per owner type (partner enrollment, payout
 * profile verification, minimums) belong to the CALLER; this service
 * owns the money mechanics: one open request per owner, hold on
 * request, release on rejection.
 */
class Payouts
{
    public function __construct(private Ledger $ledger)
    {
    }

    /**
     * Open a request and hold the funds. Throws RuntimeException with a
     * user-presentable message on refusal.
     */
    public function request(
        Model $owner,
        string $amount,
        string $payeeName,
        string $payeeMsisdn,
        ?string $payeeNetwork = null,
        ?int $requestedBy = null,
        ?string $currency = null,
    ): WithdrawalRequest {
        $amount = number_format((float) $amount, 2, '.', '');
        $currency = $currency ?? config('payments.currency', 'UGX');

        if (bccomp($amount, '0', 2) <= 0) {
            throw new RuntimeException('Enter an amount to withdraw.');
        }

        return DB::transaction(function () use ($owner, $amount, $currency, $payeeName, $payeeMsisdn, $payeeNetwork, $requestedBy) {
            // Serialize on the owner row before the open-request check so
            // two racing submissions can't both pass it.
            $owner->newQuery()->lockForUpdate()->findOrFail($owner->getKey());

            $hasOpen = WithdrawalRequest::query()
                ->where('owner_type', $owner->getMorphClass())
                ->where('owner_id', $owner->getKey())
                ->whereIn('status', WithdrawalRequest::OPEN_STATUSES)
                ->exists();
            if ($hasOpen) {
                throw new RuntimeException('You already have a withdrawal in progress.');
            }

            $withdrawal = WithdrawalRequest::create([
                'owner_type' => $owner->getMorphClass(),
                'owner_id' => $owner->getKey(),
                'requested_by' => $requestedBy,
                'amount' => $amount,
                'currency' => $currency,
                'status' => WithdrawalRequest::STATUS_REQUESTED,
                'payee_name' => $payeeName,
                'payee_msisdn' => $payeeMsisdn,
                'payee_network' => $payeeNetwork,
                'requested_at' => now(),
            ]);

            // Throws "Insufficient wallet balance." on overdraw, rolling
            // the request row back with it.
            $hold = $this->ledger->append(
                owner: $owner,
                type: LedgerEntry::TYPE_WITHDRAWAL_HOLD,
                amount: bcmul($amount, '-1', 2),
                currency: $currency,
                reference: $withdrawal,
                memo: 'Withdrawal hold',
                createdBy: $requestedBy,
            );

            $withdrawal->update(['hold_entry_id' => $hold?->id]);

            return $withdrawal;
        });
    }

    /** Compensating credit when a withdrawal is rejected. Call INSIDE the rejecting transaction. */
    public function releaseHold(WithdrawalRequest $withdrawal, string $reason, ?int $by = null): void
    {
        $owner = $withdrawal->owner()->firstOrFail();

        $this->ledger->append(
            owner: $owner,
            type: LedgerEntry::TYPE_HOLD_RELEASE,
            amount: (string) $withdrawal->amount,
            currency: (string) $withdrawal->currency,
            reference: $withdrawal,
            memo: 'Withdrawal rejected: ' . $reason,
            createdBy: $by,
        );
    }
}
