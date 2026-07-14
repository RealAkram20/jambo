<?php

namespace Modules\Monetization\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Monetization\app\Models\WalletEntry;

/**
 * Weekly integrity sweep: for every partner with ledger entries,
 * assert SUM(amount) equals the newest row's balance_after. Drift
 * means something wrote to the ledger outside WalletService (or a
 * partial failure) — loud-log it for investigation, never auto-fix.
 */
class VerifyWalletLedgerCommand extends Command
{
    protected $signature = 'monetization:verify-ledger';

    protected $description = 'Assert wallet ledger sums match running balances for every partner';

    public function handle(): int
    {
        $drifted = 0;

        MonetizationPartner::query()
            ->whereHas('walletEntries')
            ->each(function (MonetizationPartner $partner) use (&$drifted) {
                $sum = (string) ($partner->walletEntries()->sum('amount') ?: '0');
                $latest = WalletEntry::query()
                    ->where('partner_id', $partner->id)
                    ->orderByDesc('id')
                    ->value('balance_after');

                if (bccomp($sum, (string) $latest, 2) !== 0) {
                    $drifted++;
                    $message = "Wallet ledger drift for partner {$partner->id} ({$partner->display_name}): SUM={$sum}, balance_after={$latest}";
                    $this->error($message);
                    Log::error('Monetization ledger drift detected', [
                        'partner_id' => $partner->id,
                        'sum' => $sum,
                        'balance_after' => $latest,
                    ]);
                }
            });

        if ($drifted === 0) {
            $this->info('Ledger verified: all partner balances consistent.');

            return self::SUCCESS;
        }

        return self::FAILURE;
    }
}
