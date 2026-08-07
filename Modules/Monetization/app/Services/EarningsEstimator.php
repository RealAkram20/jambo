<?php

namespace Modules\Monetization\app\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Monetization\app\Models\TitleSplit;

/**
 * Live "what have I earned so far" ESTIMATES for the partner
 * dashboard — daily / weekly / month-to-date. Mirrors the month-close
 * pool math (same revenue query, same fee/infra/pool percentages,
 * same split folding via MonthCloseService::aggregateMinutes) on
 * month-TO-DATE numbers, so the estimate converges on the real
 * statement as the month completes.
 *
 * Estimates only: the pool grows with every subscription payment and
 * every partner's minutes keep shifting the shares, so these figures
 * move until the super-admin closes the month — at which point the
 * statement is the truth and the wallet is credited. The UI must say
 * so. Nothing here writes anything.
 */
class EarningsEstimator
{
    protected const SCALE = 8;

    public function __construct(protected MonthCloseService $monthClose)
    {
    }

    /**
     * Estimate for one partner over a window inside the current month.
     *
     * @param  'day'|'week'|'month'  $window
     * @return array{window: string, minutes: float, amount: int, rate_known: bool,
     *               pool: int, currency: string}
     */
    public function estimate(MonetizationPartner $partner, string $window): array
    {
        $month = CarbonImmutable::now()->startOfMonth();
        $currency = setting('payments.currency', config('payments.currency', 'UGX'));

        // ---- Month-to-date pool, exactly like month close ----------
        $gross = $this->monthClose->grossSubscriptionRevenue($month);
        $fee = bcdiv(bcmul($gross, MonetizationSettings::gatewayFeePercent(), self::SCALE), '100', self::SCALE);
        $net = bcsub(bcsub($gross, $fee, self::SCALE), MonetizationSettings::infraCostMonthly(), self::SCALE);
        if (bccomp($net, '0', self::SCALE) < 0) {
            $net = '0';
        }
        $pool = bcdiv(bcmul($net, MonetizationSettings::poolPercent(), self::SCALE), '100', 0);

        // ---- Everyone's weighted minutes (multiplier applied) ------
        [$partnerMinutes, , $platformWeight] = $this->monthClose->aggregateMinutes($month);

        $multipliers = MonetizationPartner::query()
            ->whereIn('id', array_keys($partnerMinutes))
            ->pluck('multiplier', 'id');

        $totalWeight = $platformWeight;
        foreach ($partnerMinutes as $pid => $minutes) {
            $totalWeight = bcadd($totalWeight, bcmul($minutes, (string) ($multipliers[$pid] ?? '1'), self::SCALE), self::SCALE);
        }

        // Estimated shillings per weighted minute at this instant.
        $rateKnown = bccomp($totalWeight, '0', self::SCALE) > 0 && bccomp($pool, '0', 0) > 0;
        $rate = $rateKnown ? bcdiv($pool, $totalWeight, self::SCALE) : '0';

        // ---- The partner's minutes inside the window ---------------
        $windowMinutes = match ($window) {
            'day' => $this->windowMinutes($partner, CarbonImmutable::now()->startOfDay()),
            'week' => $this->windowMinutes($partner, CarbonImmutable::now()->subDays(6)->startOfDay()),
            default => (float) ($partnerMinutes[$partner->id] ?? 0),
        };

        $weighted = bcmul(number_format($windowMinutes, 4, '.', ''), (string) $partner->multiplier, self::SCALE);
        $amount = (int) bcdiv(bcmul($weighted, $rate, self::SCALE), '1', 0);

        return [
            'window' => $window,
            'minutes' => round($windowMinutes, 1),
            'amount' => $amount,
            'rate_known' => $rateKnown,
            'pool' => (int) $pool,
            'currency' => $currency,
        ];
    }

    /**
     * The partner's split-weighted minutes credited since $from,
     * folded from the per-day roll-up (title_minutes_daily).
     */
    protected function windowMinutes(MonetizationPartner $partner, CarbonImmutable $from): float
    {
        $splits = TitleSplit::query()
            ->where('partner_id', $partner->id)
            ->get(['splittable_type', 'splittable_id', 'percent']);

        if ($splits->isEmpty()) {
            return 0.0;
        }

        $showPercents = [];
        $moviePercents = [];
        foreach ($splits as $split) {
            if (str_contains($split->splittable_type, 'Show')) {
                $showPercents[$split->splittable_id] = (float) $split->percent;
            } else {
                $moviePercents[$split->splittable_type . '#' . $split->splittable_id] = (float) $split->percent;
            }
        }

        $total = 0.0;

        if ($showPercents !== []) {
            $rows = DB::table('title_minutes_daily')
                ->where('day', '>=', $from->toDateString())
                ->whereIn('show_id', array_keys($showPercents))
                ->groupBy('show_id')
                ->selectRaw('show_id, SUM(minutes_credited) as minutes')
                ->get();
            foreach ($rows as $r) {
                $total += (float) $r->minutes * ($showPercents[$r->show_id] / 100);
            }
        }

        if ($moviePercents !== []) {
            $rows = DB::table('title_minutes_daily')
                ->where('day', '>=', $from->toDateString())
                ->whereNull('show_id')
                ->groupBy('watchable_type', 'watchable_id')
                ->selectRaw('watchable_type, watchable_id, SUM(minutes_credited) as minutes')
                ->get();
            foreach ($rows as $r) {
                $key = $r->watchable_type . '#' . $r->watchable_id;
                if (isset($moviePercents[$key])) {
                    $total += (float) $r->minutes * ($moviePercents[$key] / 100);
                }
            }
        }

        return $total;
    }
}
