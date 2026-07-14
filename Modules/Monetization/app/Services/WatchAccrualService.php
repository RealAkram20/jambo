<?php

namespace Modules\Monetization\app\Services;

use Illuminate\Support\Facades\Log;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Show;
use Modules\Monetization\app\Models\QualifiedView;
use Modules\Monetization\app\Models\TitleSplit;
use Modules\Monetization\app\Models\WatchProgressMonthly;
use Modules\Streaming\app\Events\PlaybackBeat;
use Modules\Subscriptions\app\Models\UserSubscription;

/**
 * Turns raw player heartbeats into auditable earning facts.
 *
 * Fraud posture (why each rule exists):
 *  - Runtime comes from the CONTENT ROW (`runtime_minutes`), never the
 *    client-reported duration — a scripted player can't shrink a movie
 *    to qualify in seconds.
 *  - Credit per beat is bounded by wall-clock elapsed since OUR last
 *    beat (max 45s ≈ 3 heartbeats) — replaying beats or seeking to the
 *    end can't mint watch-time faster than real time passes.
 *  - Paused players credit nothing (position must advance).
 *  - Qualification requires an ACTIVE PAID subscription at that moment,
 *    is denied to the title's own split-partners (self-farming), and is
 *    capped per user per day (a looping account plateaus).
 *  - The unique key on qualified_views makes the whole thing
 *    replay/concurrency idempotent: one payable fact per
 *    (user, title, month), ever.
 */
class WatchAccrualService
{
    /**
     * Max seconds credited per beat: 3× the 15s player cadence, so a
     * couple of dropped heartbeats don't cost the viewer credit but a
     * beat-replay script gains nothing.
     */
    public const MAX_CREDIT_PER_BEAT_SECONDS = 45;

    /**
     * Forgiveness margin when position advances less than wall time
     * (buffering, minor clock skew).
     */
    public const POSITION_SLACK_SECONDS = 10;

    public function ingest(PlaybackBeat $beat): void
    {
        if (!MonetizationSettings::accruing()) {
            return;
        }

        // Server-side truth for duration. Titles without a runtime can't
        // be measured against a threshold — they simply don't accrue
        // (ops: set runtime_minutes on monetized titles).
        $runtimeMinutes = (int) ($beat->item->runtime_minutes ?? 0);
        if ($runtimeMinutes <= 0) {
            return;
        }
        $runtimeSeconds = $runtimeMinutes * 60;

        $now = now();
        $row = WatchProgressMonthly::query()->firstOrNew([
            'user_id' => $beat->userId,
            'watchable_type' => $beat->item->getMorphClass(),
            'watchable_id' => $beat->item->getKey(),
            'period_month' => $now->copy()->startOfMonth()->toDateString(),
        ]);

        if ($row->exists && $row->qualified) {
            // Already earned this month — keep the row fresh but skip
            // all further math.
            $row->forceFill([
                'last_position_seconds' => $beat->position,
                'last_beat_at' => $now,
            ])->save();

            return;
        }

        $credit = 0;
        if ($row->exists && $row->last_beat_at !== null) {
            $wallDelta = min(
                max(0, $now->diffInSeconds($row->last_beat_at)),
                self::MAX_CREDIT_PER_BEAT_SECONDS,
            );
            $posDelta = $beat->position - $row->last_position_seconds;

            if ($posDelta > 0) {
                $credit = min($wallDelta, $posDelta + self::POSITION_SLACK_SECONDS);
            }
        }
        // First beat of the month/session establishes the baseline only.

        $row->forceFill([
            'seconds_watched' => min(($row->seconds_watched ?? 0) + $credit, $runtimeSeconds),
            'last_position_seconds' => $beat->position,
            'last_beat_at' => $now,
            'session_id' => $beat->sessionId,
            'ip' => $beat->ip,
        ])->save();

        $thresholdSeconds = (int) ceil($runtimeSeconds * MonetizationSettings::qualifyThresholdPercent() / 100);
        if ($row->seconds_watched < $thresholdSeconds) {
            return;
        }

        $this->qualify($beat, $row, $runtimeMinutes);
    }

    /**
     * Threshold crossed this month — decide whether it becomes a
     * payable fact. Runs at most once per (user, title, month).
     */
    protected function qualify(PlaybackBeat $beat, WatchProgressMonthly $row, int $runtimeMinutes): void
    {
        // 1. Active PAID subscription right now (access_level >= 1;
        //    the free tier is level 0 and free users never pay out).
        $subscription = UserSubscription::query()
            ->current()
            ->where('user_id', $beat->userId)
            ->whereHas('tier', fn ($q) => $q->where('access_level', '>=', 1))
            ->orderByDesc('ends_at')
            ->first();

        if (!$subscription) {
            return;
        }

        // 2. Self-view exclusion: the watcher must not be a split
        //    partner on this title (movies split directly; episodes
        //    resolve to their parent show's split set).
        $showId = $this->resolveShowId($beat->item);
        if ($this->isSplitPartnerOnTitle($beat->userId, $beat->item, $showId)) {
            return;
        }

        // 3. Daily sanity cap: even a genuine binge plateaus, and a
        //    24/7 looping account stops minting minutes.
        $creditedToday = (int) QualifiedView::query()
            ->where('user_id', $beat->userId)
            ->where('qualified_at', '>=', now()->startOfDay())
            ->sum('minutes_credited');

        if ($creditedToday + $runtimeMinutes > MonetizationSettings::dailyMinutesCap()) {
            Log::notice('Monetization: daily minutes cap hit, view not credited', [
                'user_id' => $beat->userId,
                'watchable' => $beat->item->getMorphClass().'#'.$beat->item->getKey(),
                'credited_today' => $creditedToday,
            ]);

            return;
        }

        // insertOrIgnore: the unique (user, title, month) key absorbs
        // concurrent double-beats and replays as silent no-ops.
        QualifiedView::query()->insertOrIgnore([
            'user_id' => $beat->userId,
            'watchable_type' => $beat->item->getMorphClass(),
            'watchable_id' => $beat->item->getKey(),
            'show_id' => $showId,
            'period_month' => $row->period_month->toDateString(),
            'minutes_credited' => $runtimeMinutes,
            'runtime_minutes_snapshot' => $runtimeMinutes,
            'user_subscription_id' => $subscription->id,
            'session_id' => $beat->sessionId,
            'ip' => $beat->ip,
            'qualified_at' => now(),
            'created_at' => now(),
        ]);

        $row->forceFill(['qualified' => true])->save();
    }

    /** Episodes earn against their parent Show's split set. */
    protected function resolveShowId($item): ?int
    {
        if ($item instanceof Episode) {
            return $item->season?->show_id;
        }

        return null;
    }

    protected function isSplitPartnerOnTitle(int $userId, $item, ?int $showId): bool
    {
        $query = TitleSplit::query()
            ->whereHas('partner', fn ($q) => $q->where('user_id', $userId));

        if ($showId !== null) {
            $query->where('splittable_type', (new Show())->getMorphClass())
                ->where('splittable_id', $showId);
        } else {
            $query->where('splittable_type', $item->getMorphClass())
                ->where('splittable_id', $item->getKey());
        }

        return $query->exists();
    }
}
