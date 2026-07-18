<?php

namespace App\Console\Commands;

use App\Services\PerformanceCredits;
use Illuminate\Console\Command;

/**
 * Backfill/repair for pay-per-upload: credits every not-yet-credited
 * `created` content activity to its admin's universal wallet at the
 * current rates. Idempotent — the ledger's unique reference key makes
 * re-runs no-ops, so it is safe to run any time (e.g. after changing a
 * rate from zero, or right after enabling the sync).
 */
class SyncPerformanceWallet extends Command
{
    protected $signature = 'performance:sync-wallet';

    protected $description = 'Credit uncredited content uploads to admin wallets at current performance rates';

    public function handle(PerformanceCredits $credits): int
    {
        $n = $credits->sweep();

        $this->info("Credited {$n} upload(s) to admin wallets.");

        return self::SUCCESS;
    }
}
