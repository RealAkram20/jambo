<?php

namespace Modules\Monetization\app\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\Monetization\app\Services\MonetizationSettings;
use Modules\Monetization\app\Services\MonthCloseService;

/**
 * Scheduled monthlyOn(1, 02:30): computes the DRAFT statement set for
 * the month that just ended. Credits nothing — a super-admin reviews
 * the draft and clicks "Close & Credit" to release earnings.
 */
class ComputeMonthlyDraftCommand extends Command
{
    protected $signature = 'monetization:compute-draft
        {--month= : Target month as YYYY-MM (defaults to the previous month)}
        {--current : Target the current month (used by the daily schedule so the month resolves at RUN time, not at scheduler boot)}';

    protected $description = 'Compute (or recompute) the draft monetization statement for a month';

    public function handle(MonthCloseService $service): int
    {
        if (!MonetizationSettings::active()) {
            $this->info('Monetization program is inactive — nothing to compute.');

            return self::SUCCESS;
        }

        // '!' resets unparsed fields (day → 1); plain 'Y-m' inherits
        // today's day-of-month and can overflow into the next month.
        $month = match (true) {
            (bool) $this->option('current') => CarbonImmutable::now()->startOfMonth(),
            (bool) $this->option('month') => CarbonImmutable::createFromFormat('!Y-m', $this->option('month')),
            default => CarbonImmutable::now()->subMonthNoOverflow(),
        };

        try {
            $period = $service->computeDraft($month);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%s draft: gross UGX %s, pool UGX %s, partner pool UGX %s, %d statement(s).',
            $period->period_month->format('F Y'),
            number_format((float) $period->gross_revenue, 2),
            number_format((float) $period->pool_amount, 2),
            number_format((float) $period->partner_pool_amount, 2),
            $period->statements()->count(),
        ));

        return self::SUCCESS;
    }
}
