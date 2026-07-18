<?php

namespace Modules\Wallet\app\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\app\Models\LedgerEntry;
use RuntimeException;

/**
 * The ONLY write path into the universal wallet ledger.
 *
 * Every append: (1) takes the owner-row lock — serializing all
 * concurrent balance mutations for that owner, (2) recomputes the
 * authoritative per-currency balance under the lock, (3) refuses
 * debits that would go negative, (4) writes amount + balance_after in
 * one insert. The unique (reference_type, reference_id, type) key
 * additionally makes reference-bearing entries idempotent — replayed
 * webhooks and double-clicked buttons become no-ops, not double money.
 */
class Ledger
{
    /**
     * Append a ledger entry. Call INSIDE a DB::transaction. Returns
     * the created entry, or null when the unique reference key says
     * this exact entry already exists (idempotent replay).
     */
    public function append(
        Model $owner,
        string $type,
        string $amount,
        ?string $currency = null,
        ?Model $reference = null,
        ?string $memo = null,
        ?int $createdBy = null,
        ?array $meta = null,
    ): ?LedgerEntry {
        if (!DB::transactionLevel()) {
            throw new \LogicException('Ledger::append must run inside a transaction.');
        }

        $currency = $currency ?? config('payments.currency', 'UGX');

        // Serialize this owner's wallet.
        $owner->newQuery()->lockForUpdate()->findOrFail($owner->getKey());

        if ($reference !== null) {
            $exists = LedgerEntry::query()
                ->where('reference_type', $reference->getMorphClass())
                ->where('reference_id', $reference->getKey())
                ->where('type', $type)
                ->exists();

            if ($exists) {
                return null; // already applied — replay no-op
            }
        }

        $balance = $this->balanceFor($owner, $currency);
        $newBalance = bcadd($balance, $amount, 2);

        if (bccomp($newBalance, '0', 2) < 0) {
            throw new RuntimeException('Insufficient wallet balance.');
        }

        return LedgerEntry::create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'type' => $type,
            'amount' => bcadd($amount, '0', 2),
            'balance_after' => $newBalance,
            'currency' => $currency,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'memo' => $memo,
            'created_by' => $createdBy,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }

    /** Authoritative per-currency balance: SUM(amount) under whatever lock the caller holds. */
    public function balanceFor(Model $owner, ?string $currency = null): string
    {
        return (string) (LedgerEntry::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->where('currency', $currency ?? config('payments.currency', 'UGX'))
            ->sum('amount') ?: '0');
    }

    /** Lifetime credits of one type (e.g. total referral commissions earned). */
    public function totalOfType(Model $owner, string $type, ?string $currency = null): string
    {
        return (string) (LedgerEntry::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->where('type', $type)
            ->where('currency', $currency ?? config('payments.currency', 'UGX'))
            ->sum('amount') ?: '0');
    }

    public function entriesFor(Model $owner)
    {
        return LedgerEntry::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->orderByDesc('id');
    }
}
