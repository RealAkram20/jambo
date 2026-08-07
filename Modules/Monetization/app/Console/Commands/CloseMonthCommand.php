<?php

namespace Modules\Monetization\app\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Monetization\app\Models\MonetizationPeriod;
use Modules\Monetization\app\Models\QualifiedView;
use Modules\Monetization\app\Services\AuditLogger;
use Modules\Monetization\app\Services\MonetizationSettings;
use Modules\Monetization\app\Services\MonthCloseService;

/**
 * Fully automated month settlement. Scheduled daily; on any run it
 * finds every month BEFORE the current one that still needs settling
 * (an open period, or qualified minutes with no period at all),
 * recomputes its statements against the complete month's revenue, and
 * closes it — crediting every partner wallet and firing the partner
 * notifications. Idempotent and self-healing: a failed or missed run
 * is simply caught up by the next day's sweep, and months already
 * closed are never touched.
 *
 * The monetization.auto_close setting (default ON) is the operator's
 * kill switch — turned off, months wait for the manual "Close &
 * Credit" button exactly as before.
 */
class CloseMonthCommand extends Command
{
    /**
     * Days past month-end before the sweep settles it. Mobile-money
     * orders complete asynchronously (IPN callbacks can lag creation
     * by days); settling at 03:00 on the 1st would freeze the pool
     * before those stragglers land, silently under-paying partners.
     * Manual --month runs bypass the grace (the operator decides).
     */
    public const GRACE_DAYS = 3;

    protected $signature = 'monetization:close-month
        {--month= : Settle one specific month (YYYY-MM) instead of sweeping}
        {--force : Allow settling the CURRENT month early (testing / emergencies only)}';

    protected $description = 'Settle open monetization months: finalize statements and credit partner wallets';

    public function handle(MonthCloseService $service): int
    {
        if (!MonetizationSettings::active()) {
            $this->info('Monetization program is inactive — nothing to settle.');

            return self::SUCCESS;
        }

        if (!$this->option('month') && !MonetizationSettings::autoClose()) {
            $this->info('Automatic month close is disabled (monetization.auto_close) — leaving months for manual review.');

            return self::SUCCESS;
        }

        $months = $this->monthsToSettle();
        if ($months === []) {
            $this->info('No open months to settle.');

            return self::SUCCESS;
        }

        $failures = 0;
        foreach ($months as $month) {
            try {
                $existing = MonetizationPeriod::query()
                    ->where('period_month', $month->toDateString())
                    ->first();
                if ($existing?->isClosed()) {
                    $this->info("{$month->format('F Y')} is already settled — nothing to do.");
                    continue;
                }

                // Recompute right before closing so the statements
                // reflect the COMPLETE month (the mid-month draft
                // preview is stale by definition).
                $period = $service->computeDraft($month);
                $period = $service->closeAndCredit($period, null);

                $this->info(sprintf(
                    'Settled %s: partner pool UGX %s across %d statement(s).',
                    $period->period_month->format('F Y'),
                    number_format((float) $period->partner_pool_amount, 0),
                    $period->statements()->count(),
                ));
            } catch (\Throwable $e) {
                $failures++;
                // Loud on every channel we have: the scheduler output,
                // the log, and the monetization audit trail the admins
                // actually read. Tomorrow's sweep retries automatically.
                $this->error("Failed to settle {$month->format('Y-m')}: {$e->getMessage()}");
                Log::error('Monetization auto-close failed', [
                    'month' => $month->format('Y-m'),
                    'error' => $e->getMessage(),
                ]);
                AuditLogger::log('period.auto_close_failed', null, ['after' => [
                    'month' => $month->format('Y-m'),
                    'error' => $e->getMessage(),
                ]]);
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Months that still need settling, oldest first.
     *
     * @return list<CarbonImmutable>
     */
    protected function monthsToSettle(): array
    {
        $currentMonth = CarbonImmutable::now()->startOfMonth();

        if ($this->option('month')) {
            // '!' resets unparsed fields to their epoch defaults (day
            // 1) — plain 'Y-m' inherits TODAY's day, so "2026-04" run
            // on the 31st would overflow to May and settle the wrong
            // month.
            $month = CarbonImmutable::createFromFormat('!Y-m', $this->option('month'))->startOfMonth();
            if ($month->gte($currentMonth) && !$this->option('force')) {
                $this->error("{$month->format('Y-m')} is not over yet — refusing without --force.");

                return [];
            }

            return [$month];
        }

        // Sweep window: from the program's activation month up to (not
        // including) the current month. Bounded so ancient empty months
        // never spawn zero-value periods.
        $activated = MonetizationSettings::activatedAt();
        if (!$activated) {
            return [];
        }
        $cursor = CarbonImmutable::parse($activated)->startOfMonth();

        $open = [];
        while ($cursor->lt($currentMonth)) {
            // Grace period: only settle once the month is comfortably
            // over, so async payment confirmations from the last days
            // of the month are inside the pool before it freezes.
            if (CarbonImmutable::now()->lt($cursor->endOfMonth()->addDays(self::GRACE_DAYS))) {
                break;
            }

            $period = MonetizationPeriod::query()
                ->where('period_month', $cursor->toDateString())
                ->first();

            $needsSettling = $period
                ? !$period->isClosed()
                : QualifiedView::query()->where('period_month', $cursor->toDateString())->exists();

            if ($needsSettling) {
                $open[] = $cursor;
            }

            $cursor = $cursor->addMonthNoOverflow();
        }

        return $open;
    }
}
