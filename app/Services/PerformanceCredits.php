<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\ContentActivity;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Services\Ledger;

/**
 * Pays staff per upload: every `created` row in the append-only
 * content_activity_log for a paid content type credits the acting
 * admin's universal wallet at the super-admin-configured rate — the
 * same wallet that collects their referral commissions.
 *
 * The ledger's unique (reference = activity row, type) key makes each
 * activity creditable exactly once, so the live hook and the backfill
 * sweep can never double-pay. Rates are snapshotted at credit time;
 * deleting content later never claws a credit back (same doctrine as
 * the activity log itself).
 */
class PerformanceCredits
{
    /** Content types that earn; seasons are tracked but never paid. */
    public const PAID_TYPES = ['movie', 'show', 'episode'];

    public function creditActivity(ContentActivity $activity): ?LedgerEntry
    {
        if ($activity->action !== ContentActivity::ACTION_CREATED
            || !in_array($activity->content_type, self::PAID_TYPES, true)
            || !$activity->actor_id
        ) {
            return null;
        }

        // Staff-contribution pay only — partners/VJs creating content
        // earn via the Monetization revenue share, not per-upload rates.
        $actor = User::find($activity->actor_id);
        if (!$actor || !$actor->hasAnyRole(['admin', 'super-admin'])) {
            return null;
        }

        $rate = (string) setting('performance.price_per_' . $activity->content_type, '0');
        if (!is_numeric($rate) || bccomp($rate, '0', 2) <= 0) {
            return null;
        }

        return DB::transaction(fn () => app(Ledger::class)->append(
            owner: $actor,
            type: LedgerEntry::TYPE_PERFORMANCE_CREDIT,
            amount: bcadd($rate, '0', 2),
            reference: $activity,
            memo: ucfirst($activity->content_type) . ' added: ' . ($activity->content_title ?: '#' . $activity->content_id),
        ));
    }

    /**
     * Backfill/repair: credit every not-yet-credited paid activity at
     * CURRENT rates, stamping each entry with the activity's original
     * timestamp so period views on the Performance page stay truthful.
     *
     * @return int number of activities credited
     */
    public function sweep(): int
    {
        $credited = 0;

        ContentActivity::query()
            ->where('action', ContentActivity::ACTION_CREATED)
            ->whereIn('content_type', self::PAID_TYPES)
            ->whereNotNull('actor_id')
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use (&$credited) {
                foreach ($chunk as $activity) {
                    $entry = $this->creditActivity($activity);
                    if ($entry) {
                        // Backdate to the upload moment (query builder on
                        // purpose — the model itself is append-only).
                        DB::table('wallet_ledger_entries')
                            ->where('id', $entry->id)
                            ->update(['created_at' => $activity->created_at]);
                        $credited++;
                    }
                }
            });

        return $credited;
    }

    /** Wallet-credited performance earnings for one admin since a moment. */
    public function earnedSince(User $user, \DateTimeInterface $since): string
    {
        return (string) (LedgerEntry::query()
            ->where('owner_type', $user->getMorphClass())
            ->where('owner_id', $user->id)
            ->where('type', LedgerEntry::TYPE_PERFORMANCE_CREDIT)
            ->where('created_at', '>=', $since)
            ->sum('amount') ?: '0');
    }
}
