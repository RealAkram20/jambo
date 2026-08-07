<?php

namespace Modules\Monetization\app\Http\Controllers\Partner;

use Illuminate\Http\JsonResponse;
use Modules\Monetization\app\Models\MonetizationPeriod;
use Modules\Monetization\app\Models\QualifiedView;
use Modules\Monetization\app\Models\TitleSplit;
use Modules\Wallet\app\Models\WithdrawalRequest;

class PartnerDashboardController extends PartnerBaseController
{
    public function index()
    {
        $partner = $this->partner();

        $monthStart = now()->startOfMonth()->toDateString();

        return view('monetization::partner.dashboard', [
            'partner' => $partner,
            'balance' => $partner->walletBalance(),
            'estimate' => app(\Modules\Monetization\app\Services\EarningsEstimator::class)
                ->estimate($partner, 'month'),
            'monthMinutes' => $this->splitWeightedMinutes($partner->id, $monthStart),
            'lastStatement' => $partner->statements()
                ->whereHas('period', fn ($q) => $q->where('status', MonetizationPeriod::STATUS_CLOSED))
                ->with('period')
                ->latest()
                ->first(),
            'openWithdrawal' => $partner->withdrawals()
                ->whereIn('status', WithdrawalRequest::OPEN_STATUSES)
                ->latest('requested_at')
                ->first(),
            'titleCount' => $partner->splits()->count(),
        ]);
    }

    /**
     * The Earnings graph, filterable like the admin dashboard charts:
     *   Week  — daily estimate bars, last 7 days
     *   Month — daily estimate bars, current month so far
     *   Year  — 12 monthly bars: settled statements, with the still-
     *           open current month shown at its live estimate
     * Always returns real bars (never an empty series), plus a
     * headline total for the window so the card's big number stays in
     * sync with the graph.
     */
    protected function earningsChart(\Illuminate\Http\Request $request, $partner): JsonResponse
    {
        $period = in_array($request->query('period'), ['Year', 'Month', 'Week'], true)
            ? $request->query('period')
            : 'Year';

        $estimator = app(\Modules\Monetization\app\Services\EarningsEstimator::class);
        $ctx = $estimator->context();
        $currency = $ctx['currency'];

        if ($period === 'Week' || $period === 'Month') {
            $from = $period === 'Week'
                ? \Carbon\CarbonImmutable::now()->subDays(6)->startOfDay()
                : \Carbon\CarbonImmutable::now()->startOfMonth();

            $daily = $estimator->dailyMinutes($partner, $from);

            $labels = [];
            $data = [];
            $totalMinutes = 0.0;
            for ($d = $from; $d->lte(\Carbon\CarbonImmutable::now()); $d = $d->addDay()) {
                $key = $d->toDateString();
                $minutes = $daily[$key] ?? 0.0;
                $totalMinutes += $minutes;
                // Week reads as weekdays (Fri 01), Month as dates
                // (01 Aug) — visibly different views even early in the
                // month when the two windows overlap.
                $labels[] = $period === 'Week' ? $d->format('D d') : $d->format('d M');
                $data[] = $estimator->amountFor($partner, $minutes, $ctx);
            }

            return response()->json([
                'labels' => $labels,
                'series' => [['name' => "Estimated earnings ($currency)", 'data' => $data]],
                'headline' => [
                    'amount' => $estimator->amountFor($partner, $totalMinutes, $ctx),
                    'detail' => 'estimated from ' . number_format($totalMinutes) . ' weighted minutes '
                        . ($period === 'Week' ? 'in the last 7 days' : 'this month'),
                ],
            ]);
        }

        // Year: settled statements by month; the open current month
        // rides at its live estimate so the chart is never blank.
        $settled = $partner->statements()
            ->whereHas('period', fn ($q) => $q->where('status', MonetizationPeriod::STATUS_CLOSED))
            ->with('period')
            ->get()
            ->keyBy(fn ($s) => $s->period->period_month->toDateString());

        $currentMonthKey = now()->startOfMonth()->toDateString();
        $monthEstimate = $estimator->amountFor(
            $partner,
            (float) ($ctx['partner_minutes'][$partner->id] ?? 0),
            $ctx,
        );

        $labels = [];
        $data = [];
        $total = 0.0;
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonthsNoOverflow($i)->startOfMonth();
            $key = $month->toDateString();
            $labels[] = $month->format('M Y');
            $value = $key === $currentMonthKey
                ? $monthEstimate
                : (float) ($settled[$key]->amount ?? 0);
            $data[] = round($value);
            $total += $value;
        }

        return response()->json([
            'labels' => $labels,
            'series' => [['name' => "Earnings ($currency)", 'data' => $data]],
            'headline' => [
                'amount' => (int) round($total),
                'detail' => 'last 12 months — settled statements, current month estimated',
            ],
        ]);
    }

    /**
     * Live earnings estimate for the dashboard's Daily/Weekly/Monthly
     * filter. Estimates only — statements at month close are the truth.
     */
    public function estimate(\Illuminate\Http\Request $request): JsonResponse
    {
        $window = in_array($request->query('window'), ['day', 'week', 'month'], true)
            ? $request->query('window')
            : 'month';

        return response()->json(
            app(\Modules\Monetization\app\Services\EarningsEstimator::class)
                ->estimate($this->partner(), $window)
        );
    }

    /**
     * ApexCharts JSON feeds (same pattern as the admin dashboard's
     * chartData endpoint). Scoped to the authenticated partner.
     */
    public function chartData(\Illuminate\Http\Request $request, string $chart): JsonResponse
    {
        $partner = $this->partner();

        if ($chart === 'earnings') {
            return $this->earningsChart($request, $partner);
        }

        // 'minutes': split-weighted qualified minutes for the last 6
        // months — one batched pass, not one query per split per month.
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = now()->subMonthsNoOverflow($i)->startOfMonth();
        }
        $byMonth = $this->splitWeightedMinutesByMonth(
            $partner->id,
            array_map(fn ($m) => $m->toDateString(), $months),
        );
        $labels = [];
        $data = [];
        foreach ($months as $month) {
            $labels[] = $month->format('M Y');
            $data[] = round($byMonth[$month->toDateString()] ?? 0.0, 1);
        }

        return response()->json([
            'labels' => $labels,
            'series' => [['name' => 'Qualified minutes', 'data' => $data]],
        ]);
    }

    /**
     * The partner's share of a month's qualified minutes, weighted by
     * their per-title split percentages (matches month-close math,
     * minus the multiplier — this is a volume stat, not money).
     */
    protected function splitWeightedMinutes(int $partnerId, string $periodMonth): float
    {
        return $this->splitWeightedMinutesByMonth($partnerId, [$periodMonth])[$periodMonth] ?? 0.0;
    }

    /**
     * Same math for a batch of months in a FIXED number of queries.
     * The naive shape (one sum per split per month) was fine for a
     * pilot but explodes on a real VJ catalogue: 863 splits made the
     * dashboard ~6,000 queries per load.
     *
     * @param  list<string>  $months  period_month date strings
     * @return array<string, float>  month => weighted minutes
     */
    protected function splitWeightedMinutesByMonth(int $partnerId, array $months): array
    {
        $b = $this->splitWeightedMinutesBreakdown($partnerId, $months);

        $out = [];
        foreach ($months as $m) {
            $out[$m] = ($b['movies'][$m] ?? 0.0) + ($b['shows'][$m] ?? 0.0);
        }

        return $out;
    }

    /**
     * Movies/Series breakdown of the same weighted-minutes math —
     * feeds the dashboard's stacked bar (Movies vs Series per month,
     * matching the admin Most Watched card's shape).
     *
     * @param  list<string>  $months
     * @return array{movies: array<string, float>, shows: array<string, float>}
     */
    protected function splitWeightedMinutesBreakdown(int $partnerId, array $months): array
    {
        $movies = array_fill_keys($months, 0.0);
        $shows = array_fill_keys($months, 0.0);
        $out = ['movies' => $movies, 'shows' => $shows];

        $splits = TitleSplit::query()
            ->where('partner_id', $partnerId)
            ->get(['splittable_type', 'splittable_id', 'percent']);

        if ($splits->isEmpty()) {
            return $out;
        }

        $showPercents = [];   // show_id => percent
        $moviePercents = [];  // "type#id" => percent
        foreach ($splits as $split) {
            if (str_contains($split->splittable_type, 'Show')) {
                $showPercents[$split->splittable_id] = (float) $split->percent;
            } else {
                $moviePercents[$split->splittable_type . '#' . $split->splittable_id] = (float) $split->percent;
            }
        }

        if ($showPercents !== []) {
            $rows = QualifiedView::query()
                ->whereIn('period_month', $months)
                ->whereIn('show_id', array_keys($showPercents))
                ->groupBy('period_month', 'show_id')
                ->selectRaw('period_month, show_id, SUM(minutes_credited) as minutes')
                ->get();
            foreach ($rows as $r) {
                $month = $r->period_month->toDateString();
                $out['shows'][$month] = ($out['shows'][$month] ?? 0.0)
                    + (float) $r->minutes * ($showPercents[$r->show_id] / 100);
            }
        }

        if ($moviePercents !== []) {
            // Grouped over every watched movie for the months, matched
            // to this partner's splits in PHP — the distinct watched
            // titles per month bound this far tighter than a giant
            // composite whereIn would.
            $rows = QualifiedView::query()
                ->whereIn('period_month', $months)
                ->whereNull('show_id')
                ->groupBy('period_month', 'watchable_type', 'watchable_id')
                ->selectRaw('period_month, watchable_type, watchable_id, SUM(minutes_credited) as minutes')
                ->get();
            foreach ($rows as $r) {
                $key = $r->watchable_type . '#' . $r->watchable_id;
                if (!isset($moviePercents[$key])) {
                    continue;
                }
                $month = $r->period_month->toDateString();
                $out['movies'][$month] = ($out['movies'][$month] ?? 0.0)
                    + (float) $r->minutes * ($moviePercents[$key] / 100);
            }
        }

        return $out;
    }
}
