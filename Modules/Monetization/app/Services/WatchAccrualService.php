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
 *  - HYBRID ACCRUAL: actual watched minutes credit continuously for
 *    eligible viewers; crossing the completion threshold (default 70%,
 *    i.e. "reached the credit words") tops the credit up to the full
 *    runtime. Partial watching earns its real minutes; completion
 *    earns the whole film.
 *  - Crediting requires an ACTIVE PAID subscription at that moment,
 *    is denied to the title's own split-partners (self-farming), and is
 *    capped per user per day (a looping account plateaus).
 *  - Free titles mint minutes only when `free_content_earns` is on, and
 *    when it is, ActiveStream counts them against the device cap too —
 *    earning outside the cap is exactly the tab-farming hole.
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

        if ($row->exists && $row->qualified && $row->completed_at !== null) {
            // Fully settled this month — completed AND topped up to the
            // full runtime. Keep the row fresh but skip all further math.
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

        // Completion marker: crossing the qualify threshold means the
        // viewer effectively finished the film (the remaining tail is
        // credit words — which is exactly why the threshold exists).
        // Stamped for stats regardless of payment eligibility: a free
        // viewer's completion is still a completion.
        $thresholdSeconds = (int) ceil($runtimeSeconds * MonetizationSettings::qualifyThresholdPercent() / 100);
        $completedNow = $row->seconds_watched >= $thresholdSeconds;
        if ($completedNow && $row->completed_at === null) {
            $row->forceFill(['completed_at' => $now])->save();
        }

        // Free titles only mint payable minutes when the program says
        // they do. Checked BEFORE credit() so a free title under a
        // "premium catalogue only" deal never touches the daily cap —
        // otherwise free viewing would eat the budget that a partner's
        // premium titles are supposed to spend.
        //
        // Note watch_progress_monthly above was still written: the
        // watch-time is recorded either way, so you can always answer
        // "what would my free catalogue have earned?" without having
        // to flip the switch on to find out.
        if (self::isFreeContent($beat->item) && !MonetizationSettings::freeContentEarns()) {
            return;
        }

        $this->credit($beat, $row, $runtimeMinutes, $completedNow);
    }

    /** A title with no tier_required sits on the free shelf. */
    public static function isFreeContent($item): bool
    {
        return blank($item->tier_required ?? null);
    }

    /**
     * Hybrid accrual: actual watched minutes credit continuously for
     * eligible viewers; crossing the completion threshold tops the
     * credit up to the FULL runtime (closing during the credit words
     * still pays the whole film, and a completed view stays worth
     * more than an abandoned one).
     *
     * The eligibility queries below run only when there is at least
     * one new whole minute to credit — i.e. at most ~once per minute
     * per viewer, not on every 15s heartbeat.
     */
    protected function credit(PlaybackBeat $beat, WatchProgressMonthly $row, int $runtimeMinutes, bool $completedNow): void
    {
        // Payable target so far: watched whole minutes, topped up to
        // the full runtime at completion. Never exceeds the runtime —
        // the runtime bound plus the (user, title, month) unique key
        // is the double-pay protection.
        $targetMinutes = $completedNow
            ? $runtimeMinutes
            : min(intdiv((int) $row->seconds_watched, 60), $runtimeMinutes);

        if ($targetMinutes <= 0) {
            return;
        }

        $view = QualifiedView::query()
            ->where('user_id', $beat->userId)
            ->where('watchable_type', $beat->item->getMorphClass())
            ->where('watchable_id', $beat->item->getKey())
            ->where('period_month', $row->period_month->toDateString())
            ->first();

        $already = $view ? (int) $view->minutes_credited : 0;
        $delta = $targetMinutes - $already;
        $needsCompletionStamp = $completedNow && (!$view || $view->completed_at === null);

        if ($delta <= 0 && !$needsCompletionStamp) {
            return; // nothing new this beat — the common hot-path exit
        }

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
        //    24/7 looping account stops minting minutes. Conservative
        //    on purpose: rows touched today count with their WHOLE
        //    month-to-date minutes, so the cap can only trip early,
        //    never late. A capped viewer resumes crediting tomorrow.
        if ($delta > 0) {
            $creditedToday = (int) QualifiedView::query()
                ->where('user_id', $beat->userId)
                ->where('last_credited_at', '>=', now()->startOfDay())
                ->sum('minutes_credited');

            if ($creditedToday + $delta > MonetizationSettings::dailyMinutesCap()) {
                Log::notice('Monetization: daily minutes cap hit, credit deferred', [
                    'user_id' => $beat->userId,
                    'watchable' => $beat->item->getMorphClass().'#'.$beat->item->getKey(),
                    'credited_today' => $creditedToday,
                ]);

                return;
            }
        }

        $now = now();
        $creditedDelta = 0;
        if (!$view) {
            // insertOrIgnore: the unique (user, title, month) key
            // absorbs concurrent double-beats and replays. Its return
            // value says whether WE inserted — if we lost the race,
            // re-read and fall through to the update path so the
            // winner's row still reaches the right minute count.
            $inserted = QualifiedView::query()->insertOrIgnore([
                'user_id' => $beat->userId,
                'watchable_type' => $beat->item->getMorphClass(),
                'watchable_id' => $beat->item->getKey(),
                'show_id' => $showId,
                'period_month' => $row->period_month->toDateString(),
                'minutes_credited' => $targetMinutes,
                'runtime_minutes_snapshot' => $runtimeMinutes,
                'content_was_free' => self::isFreeContent($beat->item),
                'user_subscription_id' => $subscription->id,
                'session_id' => $beat->sessionId,
                'ip' => $beat->ip,
                'qualified_at' => $now,
                'completed_at' => $completedNow ? $now : null,
                'last_credited_at' => $now,
                'created_at' => $now,
            ]);
            if ($inserted > 0) {
                $creditedDelta = $targetMinutes;
            }
            $view = QualifiedView::query()
                ->where('user_id', $beat->userId)
                ->where('watchable_type', $beat->item->getMorphClass())
                ->where('watchable_id', $beat->item->getKey())
                ->where('period_month', $row->period_month->toDateString())
                ->first();
        }

        if ($creditedDelta === 0 && $view
            && ((int) $view->minutes_credited < $targetMinutes || ($completedNow && $view->completed_at === null))) {
            $creditedDelta = max(0, $targetMinutes - (int) $view->minutes_credited);
            $view->forceFill([
                'minutes_credited' => max((int) $view->minutes_credited, $targetMinutes),
                'completed_at' => $view->completed_at ?? ($completedNow ? $now : null),
                'last_credited_at' => $now,
            ])->save();
        }

        // Daily roll-up behind the partner dashboard's Daily/Weekly
        // earnings filter. Atomic increment on the (day, title) grain
        // so concurrent viewers of the same title can't lose updates.
        if ($creditedDelta > 0) {
            \Illuminate\Support\Facades\DB::statement(
                'INSERT INTO title_minutes_daily (day, watchable_type, watchable_id, show_id, minutes_credited, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE minutes_credited = minutes_credited + VALUES(minutes_credited), updated_at = VALUES(updated_at)',
                [
                    $now->toDateString(),
                    $beat->item->getMorphClass(),
                    $beat->item->getKey(),
                    $showId,
                    $creditedDelta,
                    $now->toDateTimeString(),
                    $now->toDateTimeString(),
                ],
            );
        }

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
