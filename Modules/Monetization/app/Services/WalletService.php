<?php

namespace Modules\Monetization\app\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Monetization\app\Models\WalletEntry;

/**
 * The ONLY write path into the wallet ledger.
 *
 * Every append: (1) takes the partner row lock — serializing all
 * concurrent balance mutations for that partner, (2) recomputes the
 * authoritative balance under the lock, (3) refuses debits that would
 * go negative, (4) writes amount + balance_after in one insert. The
 * unique (reference_type, reference_id, type) key additionally makes
 * reference-bearing entries idempotent at the constraint level.
 */
class WalletService
{
    /**
     * Append a ledger entry. Call INSIDE a DB::transaction. Returns
     * the created entry, or null when the unique reference key says
     * this exact entry already exists (idempotent replay).
     */
    public function append(
        MonetizationPartner $partner,
        string $type,
        string $amount,
        ?Model $reference = null,
        ?string $memo = null,
        ?int $createdBy = null,
    ): ?WalletEntry {
        if (!DB::transactionLevel()) {
            throw new \LogicException('WalletService::append must run inside a transaction.');
        }

        // Serialize this partner's wallet.
        $locked = MonetizationPartner::query()->lockForUpdate()->findOrFail($partner->id);

        if ($reference !== null) {
            $exists = WalletEntry::query()
                ->where('reference_type', $reference->getMorphClass())
                ->where('reference_id', $reference->getKey())
                ->where('type', $type)
                ->exists();

            if ($exists) {
                return null; // already applied — replay no-op
            }
        }

        $balance = (string) ($locked->walletEntries()->sum('amount') ?: '0');
        $newBalance = bcadd($balance, $amount, 2);

        if (bccomp($newBalance, '0', 2) < 0) {
            throw new \RuntimeException(
                "Wallet debit would overdraw partner {$locked->id}: balance {$balance}, amount {$amount}."
            );
        }

        return WalletEntry::create([
            'partner_id' => $locked->id,
            'type' => $type,
            'amount' => bcadd($amount, '0', 2),
            'balance_after' => $newBalance,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'memo' => $memo,
            'created_by' => $createdBy,
            'created_at' => now(),
        ]);
    }
}
