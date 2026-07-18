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

        // 'minutes': split-weighted qualified minutes for the last 6 months.
        $labels = [];
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonthsNoOverflow($i)->startOfMonth();
            $labels[] = $month->format('M Y');
            $data[] = round($this->splitWeightedMinutes($partner->id, $month->toDateString()), 1);
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
        $splits = TitleSplit::query()
            ->where('partner_id', $partnerId)
            ->get(['splittable_type', 'splittable_id', 'percent']);

        if ($splits->isEmpty()) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($splits as $split) {
            $isShow = str_contains($split->splittable_type, 'Show');

            $minutes = (float) QualifiedView::query()
                ->where('period_month', $periodMonth)
                ->when($isShow,
                    fn ($q) => $q->where('show_id', $split->splittable_id),
                    fn ($q) => $q->where('watchable_type', $split->splittable_type)
                        ->where('watchable_id', $split->splittable_id))
                ->sum('minutes_credited');

            $total += $minutes * ((float) $split->percent / 100);
        }

        return $total;
    }
}
