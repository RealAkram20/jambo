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
    public function chartData(string $chart): JsonResponse
    {
        $partner = $this->partner();

        if ($chart === 'earnings') {
            $statements = $partner->statements()
                ->whereHas('period', fn ($q) => $q->where('status', MonetizationPeriod::STATUS_CLOSED))
                ->with('period')
                ->get()
                ->sortBy(fn ($s) => $s->period->period_month)
                ->take(-12);

            return response()->json([
                'labels' => $statements->map(fn ($s) => $s->period->period_month->format('M Y'))->values(),
                'series' => [[
                    'name' => 'Earnings (UGX)',
                    'data' => $statements->map(fn ($s) => (float) $s->amount)->values(),
                ]],
            ]);
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
        $out = array_fill_keys($months, 0.0);

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
                $out[$month] = ($out[$month] ?? 0.0)
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
                $out[$month] = ($out[$month] ?? 0.0)
                    + (float) $r->minutes * ($moviePercents[$key] / 100);
            }
        }

        return $out;
    }
}
