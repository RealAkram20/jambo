<?php

namespace Modules\Monetization\app\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Wallet\app\Models\LedgerEntry;

/**
 * Thin adapter kept for the Monetization call sites (statement Close &
 * Credit, adjustments). All money mechanics — owner lock, per-currency
 * balance recompute, overdraw refusal, reference idempotency — live in
 * the universal Wallet module now; this just fixes the owner to a
 * partner profile.
 */
class WalletService
{
    /**
     * Append a ledger entry for a partner. Call INSIDE a
     * DB::transaction. Returns the created entry, or null when the
     * unique reference key says this exact entry already exists.
     */
    public function append(
        MonetizationPartner $partner,
        string $type,
        string $amount,
        ?Model $reference = null,
        ?string $memo = null,
        ?int $createdBy = null,
    ): ?LedgerEntry {
        return app(\Modules\Wallet\app\Services\Ledger::class)->append(
            owner: $partner,
            type: $type,
            amount: $amount,
            reference: $reference,
            memo: $memo,
            createdBy: $createdBy,
        );
    }
}
