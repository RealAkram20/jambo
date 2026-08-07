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
        $ctx = $this->context();

        // ---- The partner's minutes inside the window ---------------
        $windowMinutes = match ($window) {
            'day' => $this->windowMinutes($partner, CarbonImmutable::now()->startOfDay()),
            'week' => $this->windowMinutes($partner, CarbonImmutable::now()->subDays(6)->startOfDay()),
            default => (float) ($ctx['partner_minutes'][$partner->id] ?? 0),
        };

        return [
            'window' => $window,
            'minutes' => round($windowMinutes, 1),
            'amount' => $this->amountFor($partner, $windowMinutes, $ctx),
            'rate_known' => $ctx['rate_known'],
            'pool' => (int) $ctx['pool'],
            'currency' => $ctx['currency'],
        ];
    }

    /**
     * Month-to-date pool + platform-wide weighted minutes + the
     * resulting rate per weighted minute — shared by every estimate.
     *
     * @return array{pool: string, rate: string, rate_known: bool,
     *               currency: string, partner_minutes: array<int, string>}
     */
    public function context(): array
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

        $rateKnown = bccomp($totalWeight, '0', self::SCALE) > 0 && bccomp($pool, '0', 0) > 0;

        return [
            'pool' => $pool,
            'rate' => $rateKnown ? bcdiv($pool, $totalWeight, self::SCALE) : '0',
            'rate_known' => $rateKnown,
            'currency' => $currency,
            'partner_minutes' => $partnerMinutes,
        ];
    }

    /** Estimated whole-currency amount for a partner's minutes at the current rate. */
    public function amountFor(MonetizationPartner $partner, float $minutes, ?array $ctx = null): int
    {
        $ctx ??= $this->context();
        $weighted = bcmul(number_format($minutes, 4, '.', ''), (string) $partner->multiplier, self::SCALE);

        return (int) bcdiv(bcmul($weighted, $ctx['rate'], self::SCALE), '1', 0);
    }

    /**
     * Per-day split-weighted minutes for the partner since $from —
     * feeds the Earnings chart's Month/Week daily bars.
     *
     * @return array<string, float>  day (Y-m-d) => weighted minutes
     */
    public function dailyMinutes(MonetizationPartner $partner, CarbonImmutable $from): array
    {
        $splits = TitleSplit::query()
            ->where('partner_id', $partner->id)
            ->get(['splittable_type', 'splittable_id', 'percent']);

        if ($splits->isEmpty()) {
            return [];
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

        $out = [];

        if ($showPercents !== []) {
            $rows = DB::table('title_minutes_daily')
                ->where('day', '>=', $from->toDateString())
                ->whereIn('show_id', array_keys($showPercents))
                ->groupBy('day', 'show_id')
                ->selectRaw('day, show_id, SUM(minutes_credited) as minutes')
                ->get();
            foreach ($rows as $r) {
                $out[$r->day] = ($out[$r->day] ?? 0.0) + (float) $r->minutes * ($showPercents[$r->show_id] / 100);
            }
        }

        if ($moviePercents !== []) {
            $rows = DB::table('title_minutes_daily')
                ->where('day', '>=', $from->toDateString())
                ->whereNull('show_id')
                ->groupBy('day', 'watchable_type', 'watchable_id')
                ->selectRaw('day, watchable_type, watchable_id, SUM(minutes_credited) as minutes')
                ->get();
            foreach ($rows as $r) {
                $key = $r->watchable_type . '#' . $r->watchable_id;
                if (isset($moviePercents[$key])) {
                    $out[$r->day] = ($out[$r->day] ?? 0.0) + (float) $r->minutes * ($moviePercents[$key] / 100);
                }
            }
        }

        return $out;
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
