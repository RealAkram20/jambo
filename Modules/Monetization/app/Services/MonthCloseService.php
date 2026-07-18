<?php

namespace Modules\Monetization\app\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Monetization\app\Models\MonetizationPeriod;
use Modules\Monetization\app\Models\PartnerStatement;
use Modules\Monetization\app\Models\QualifiedView;
use Modules\Monetization\app\Models\TitleSplit;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Subscriptions\app\Models\SubscriptionTier;

/**
 * The monthly pool engine.
 *
 *   pool = floor( max(0, G − G·fee% − infra) · pool% )
 *
 * where G = completed subscription revenue in the month. Each enrolled
 * partner's weight = (their split-weighted qualified minutes) × their
 * multiplier. Unassigned split percentage and suspended partners'
 * shares accrue to a "platform weight", so the platform keeps that
 * fraction of the pool instead of it silently redistributing.
 *
 * All arithmetic is BCMath (scale 8) — never floats — and the final
 * shilling allocation uses the largest-remainder method so partner
 * amounts sum to partner_pool EXACTLY.
 */
class MonthCloseService
{
    protected const SCALE = 8;

    /**
     * (Re)compute the draft statement set for a month. Idempotent:
     * recomputing a draft deletes and rebuilds its statements. Refuses
     * to touch a closed period.
     */
    public function computeDraft(CarbonImmutable $month): MonetizationPeriod
    {
        $monthStart = $month->startOfMonth();

        $existing = MonetizationPeriod::query()
            ->where('period_month', $monthStart->toDateString())
            ->first();

        if ($existing && $existing->isClosed()) {
            throw new \RuntimeException("Period {$monthStart->format('Y-m')} is closed; refusing to recompute.");
        }

        $gross = $this->grossSubscriptionRevenue($monthStart);
        $feePercent = MonetizationSettings::gatewayFeePercent();
        $infra = MonetizationSettings::infraCostMonthly();
        $poolPercent = MonetizationSettings::poolPercent();

        $fee = bcdiv(bcmul($gross, $feePercent, self::SCALE), '100', self::SCALE);
        $net = bcsub(bcsub($gross, $fee, self::SCALE), $infra, self::SCALE);
        if (bccomp($net, '0', self::SCALE) < 0) {
            $net = '0';
        }
        // Whole shillings: floor via bcdiv scale 0 (net is non-negative).
        $pool = bcdiv(bcmul($net, $poolPercent, self::SCALE), '100', 0);

        [$partnerMinutes, $partnerBreakdown, $platformWeight] = $this->aggregateMinutes($monthStart);

        $partners = MonetizationPartner::query()
            ->whereIn('id', array_keys($partnerMinutes))
            ->get()
            ->keyBy('id');

        // Weighted minutes per partner (multiplier applied).
        $weights = [];
        $sumPartnerWeight = '0';
        foreach ($partnerMinutes as $partnerId => $minutes) {
            $multiplier = (string) ($partners[$partnerId]->multiplier ?? '1');
            $weights[$partnerId] = bcmul($minutes, $multiplier, self::SCALE);
            $sumPartnerWeight = bcadd($sumPartnerWeight, $weights[$partnerId], self::SCALE);
        }
        $totalWeight = bcadd($sumPartnerWeight, $platformWeight, self::SCALE);

        // Partner slice of the pool (platform keeps the rest).
        if (bccomp($totalWeight, '0', self::SCALE) === 0 || bccomp($pool, '0', 0) === 0) {
            $partnerPool = '0';
        } else {
            $partnerPool = bcdiv(
                bcmul($pool, $sumPartnerWeight, self::SCALE),
                $totalWeight,
                0
            );
        }

        $amounts = $this->largestRemainderAllocate($weights, $sumPartnerWeight, $partnerPool);

        $snapshot = MonetizationSettings::snapshotForClose() + [
            'multipliers' => collect($weights)
                ->mapWithKeys(fn ($w, $id) => [$id => (string) ($partners[$id]->multiplier ?? '1')])
                ->all(),
        ];

        return DB::transaction(function () use (
            $existing, $monthStart, $gross, $fee, $infra, $pool, $partnerPool,
            $totalWeight, $platformWeight, $snapshot,
            $partnerMinutes, $weights, $sumPartnerWeight, $amounts, $partners, $partnerBreakdown
        ) {
            $period = $existing ?? new MonetizationPeriod(['period_month' => $monthStart->toDateString()]);
            $period->fill([
                'status' => MonetizationPeriod::STATUS_DRAFT,
                'gross_revenue' => $gross,
                'gateway_fee_amount' => bcadd($fee, '0', 2),
                'infra_cost_amount' => bcadd($infra, '0', 2),
                'pool_amount' => bcadd($pool, '0', 2),
                'partner_pool_amount' => bcadd($partnerPool, '0', 2),
                'total_weighted_minutes' => bcadd($totalWeight, '0', 6),
                'platform_weighted_minutes' => bcadd($platformWeight, '0', 6),
                'settings_snapshot' => $snapshot,
                'computed_at' => now(),
            ]);
            $period->save();

            // Rebuild statements from scratch (draft recompute).
            $period->statements()->delete();

            foreach ($partnerMinutes as $partnerId => $minutes) {
                $partner = $partners[$partnerId] ?? null;
                if (!$partner) {
                    continue;
                }

                $shareRatio = bccomp($sumPartnerWeight, '0', self::SCALE) > 0
                    ? bcdiv($weights[$partnerId], $sumPartnerWeight, 12)
                    : '0';

                PartnerStatement::create([
                    'period_id' => $period->id,
                    'partner_id' => $partnerId,
                    'partner_name' => $partner->display_name,
                    'partner_type' => $partner->type,
                    'multiplier_used' => $partner->multiplier,
                    'qualified_minutes' => bcadd($minutes, '0', 4),
                    'weighted_minutes' => bcadd($weights[$partnerId], '0', 6),
                    'share_ratio' => $shareRatio,
                    'amount' => bcadd($amounts[$partnerId] ?? '0', '0', 2),
                    'breakdown' => $partnerBreakdown[$partnerId] ?? [],
                ]);
            }

            AuditLogger::log('period.computed', $period, ['after' => [
                'month' => $monthStart->format('Y-m'),
                'pool' => (string) $period->pool_amount,
                'partner_pool' => (string) $period->partner_pool_amount,
                'statements' => count($partnerMinutes),
            ]]);

            return $period->fresh();
        });
    }

    /**
     * Release a reviewed draft: mark closed and credit every statement
     * to its partner's wallet. Idempotent — a second click no-ops on
     * the status check, and the ledger's unique reference key would
     * refuse duplicate credits even if it didn't.
     */
    public function closeAndCredit(MonetizationPeriod $period, int $adminId): MonetizationPeriod
    {
        return DB::transaction(function () use ($period, $adminId) {
            $fresh = MonetizationPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if ($fresh->isClosed()) {
                return $fresh;
            }

            $fresh->forceFill([
                'status' => MonetizationPeriod::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by' => $adminId,
            ])->save();

            $wallet = app(WalletService::class);
            foreach ($fresh->statements()->with('partner')->get() as $statement) {
                if (bccomp((string) $statement->amount, '0', 2) <= 0) {
                    continue;
                }

                $wallet->append(
                    partner: $statement->partner,
                    type: LedgerEntry::TYPE_STATEMENT_CREDIT,
                    amount: (string) $statement->amount,
                    reference: $statement,
                    memo: 'Earnings for '.$fresh->period_month->format('F Y'),
                );

                event(new \Modules\Notifications\app\Events\EarningsCredited($statement));
            }

            AuditLogger::log('period.closed', $fresh, ['after' => [
                'month' => $fresh->period_month->format('Y-m'),
                'partner_pool' => (string) $fresh->partner_pool_amount,
            ]]);

            return $fresh;
        });
    }

    /** Completed subscription-tier order revenue inside the month. */
    protected function grossSubscriptionRevenue(CarbonImmutable $monthStart): string
    {
        $sum = PaymentOrder::query()
            ->where('status', PaymentOrder::STATUS_COMPLETED)
            ->where('payable_type', (new SubscriptionTier())->getMorphClass())
            ->whereBetween('created_at', [$monthStart, $monthStart->endOfMonth()])
            ->sum('amount');

        return (string) $sum;
    }

    /**
     * Fold the month's qualified_views through title_splits.
     *
     * @return array{0: array<int,string>, 1: array<int,array>, 2: string}
     *         [partnerId => minutes], [partnerId => breakdown[]], platformWeight
     */
    protected function aggregateMinutes(CarbonImmutable $monthStart): array
    {
        // Minutes per title: movies keyed by their own id, episodes
        // roll up to their parent show.
        $movieMorph = (new Movie())->getMorphClass();
        $episodeMorph = (new Episode())->getMorphClass();
        $showMorph = (new Show())->getMorphClass();

        $rows = QualifiedView::query()
            ->where('period_month', $monthStart->toDateString())
            ->selectRaw('watchable_type, watchable_id, show_id, SUM(minutes_credited) as minutes')
            ->groupBy('watchable_type', 'watchable_id', 'show_id')
            ->get();

        /** @var array<string, string> $titleMinutes "type#id" => minutes */
        $titleMinutes = [];
        foreach ($rows as $row) {
            if ($row->watchable_type === $episodeMorph) {
                if (!$row->show_id) {
                    continue; // orphan episode fact — unattributable
                }
                $key = $showMorph.'#'.$row->show_id;
            } else {
                $key = $movieMorph.'#'.$row->watchable_id;
            }
            $titleMinutes[$key] = bcadd($titleMinutes[$key] ?? '0', (string) $row->minutes, self::SCALE);
        }

        if ($titleMinutes === []) {
            return [[], [], '0'];
        }

        // Split sets per title (only enrolled partners earn; suspended
        // partners' percentages fall to the platform).
        $splits = TitleSplit::query()
            ->with('partner:id,status,display_name')
            ->get()
            ->groupBy(fn ($s) => $s->splittable_type.'#'.$s->splittable_id);

        $titleLabels = $this->titleLabels(array_keys($titleMinutes), $movieMorph, $showMorph);

        $partnerMinutes = [];
        $partnerBreakdown = [];
        $platformWeight = '0';

        foreach ($titleMinutes as $key => $minutes) {
            $assignedPercent = '0';

            foreach ($splits->get($key, collect()) as $split) {
                if (!$split->partner || !$split->partner->isEnrolled()) {
                    continue;
                }

                $percent = (string) $split->percent;
                $assignedPercent = bcadd($assignedPercent, $percent, self::SCALE);

                $share = bcdiv(bcmul($minutes, $percent, self::SCALE), '100', self::SCALE);
                $partnerMinutes[$split->partner_id] = bcadd($partnerMinutes[$split->partner_id] ?? '0', $share, self::SCALE);

                [$type, $id] = explode('#', $key);
                $partnerBreakdown[$split->partner_id][] = [
                    'type' => $type === $movieMorph ? 'movie' : 'show',
                    'id' => (int) $id,
                    'title' => $titleLabels[$key] ?? '#'.$id,
                    'minutes' => (float) bcadd($minutes, '0', 2),
                    'split_percent' => (float) $percent,
                    'credited_minutes' => (float) bcadd($share, '0', 2),
                ];
            }

            // Whatever isn't assigned to an enrolled partner weighs in
            // for the platform (multiplier 1.0).
            $unassigned = bcsub('100', $assignedPercent, self::SCALE);
            if (bccomp($unassigned, '0', self::SCALE) > 0) {
                $platformWeight = bcadd(
                    $platformWeight,
                    bcdiv(bcmul($minutes, $unassigned, self::SCALE), '100', self::SCALE),
                    self::SCALE
                );
            }
        }

        return [$partnerMinutes, $partnerBreakdown, $platformWeight];
    }

    /**
     * Whole-shilling allocation summing to partnerPool exactly:
     * floor everything, then hand the leftover shillings to the
     * largest fractional parts (ties broken by partner id).
     *
     * @param array<int,string> $weights
     * @return array<int,string>
     */
    protected function largestRemainderAllocate(array $weights, string $sumWeight, string $partnerPool): array
    {
        if ($weights === [] || bccomp($partnerPool, '0', 0) <= 0 || bccomp($sumWeight, '0', self::SCALE) <= 0) {
            return array_map(fn () => '0', $weights);
        }

        $floors = [];
        $fractions = [];
        $allocated = '0';

        foreach ($weights as $partnerId => $weight) {
            $raw = bcdiv(bcmul($partnerPool, $weight, self::SCALE), $sumWeight, self::SCALE);
            $floor = bcdiv($raw, '1', 0);
            $floors[$partnerId] = $floor;
            $fractions[$partnerId] = bcsub($raw, $floor, self::SCALE);
            $allocated = bcadd($allocated, $floor, 0);
        }

        $leftover = (int) bcsub($partnerPool, $allocated, 0);

        // Sort by fraction DESC, partner id ASC.
        uksort($fractions, function ($a, $b) use ($fractions) {
            $cmp = bccomp($fractions[$b], $fractions[$a], self::SCALE);

            return $cmp !== 0 ? $cmp : ($a <=> $b);
        });

        foreach (array_keys($fractions) as $partnerId) {
            if ($leftover <= 0) {
                break;
            }
            $floors[$partnerId] = bcadd($floors[$partnerId], '1', 0);
            $leftover--;
        }

        return $floors;
    }

    /** @param string[] $keys */
    protected function titleLabels(array $keys, string $movieMorph, string $showMorph): array
    {
        $movieIds = [];
        $showIds = [];
        foreach ($keys as $key) {
            [$type, $id] = explode('#', $key);
            $type === $movieMorph ? $movieIds[] = (int) $id : $showIds[] = (int) $id;
        }

        $labels = [];
        foreach (Movie::query()->whereIn('id', $movieIds)->pluck('title', 'id') as $id => $title) {
            $labels[$movieMorph.'#'.$id] = $title;
        }
        foreach (Show::query()->whereIn('id', $showIds)->pluck('title', 'id') as $id => $title) {
            $labels[$showMorph.'#'.$id] = $title;
        }

        return $labels;
    }
}
