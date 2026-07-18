<?php

namespace Modules\Monetization\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\app\Models\LedgerEntry;

/**
 * Weekly integrity sweep over the UNIVERSAL wallet ledger: for every
 * (owner, currency) with entries, assert SUM(amount) equals the newest
 * row's balance_after. Drift means something wrote to the ledger
 * outside Wallet\Services\Ledger (or a partial failure) — loud-log it
 * for investigation, never auto-fix.
 */
class VerifyWalletLedgerCommand extends Command
{
    protected $signature = 'monetization:verify-ledger';

    protected $description = 'Assert wallet ledger sums match running balances for every owner';

    public function handle(): int
    {
        $drifted = 0;

        $owners = LedgerEntry::query()
            ->selectRaw('owner_type, owner_id, currency')
            ->groupBy('owner_type', 'owner_id', 'currency')
            ->get();

        foreach ($owners as $owner) {
            $scope = LedgerEntry::query()
                ->where('owner_type', $owner->owner_type)
                ->where('owner_id', $owner->owner_id)
                ->where('currency', $owner->currency);

            $sum = (string) ((clone $scope)->sum('amount') ?: '0');
            $latest = (clone $scope)->orderByDesc('id')->value('balance_after');

            if (bccomp($sum, (string) $latest, 2) !== 0) {
                $drifted++;
                $message = "Wallet ledger drift for {$owner->owner_type}#{$owner->owner_id} ({$owner->currency}): SUM={$sum}, balance_after={$latest}";
                $this->error($message);
                Log::error('Wallet ledger drift detected', [
                    'owner_type' => $owner->owner_type,
                    'owner_id' => $owner->owner_id,
                    'currency' => $owner->currency,
                    'sum' => $sum,
                    'balance_after' => $latest,
                ]);
            }
        }

        if ($drifted === 0) {
            $this->info('Ledger verified: all wallet balances consistent.');

            return self::SUCCESS;
        }

        return self::FAILURE;
    }
}
