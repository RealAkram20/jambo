<?php

namespace Modules\Streaming\app\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Streaming\app\Models\WatchHistoryItem;

/**
 * "Play this thing on my watchlist" — what that actually resolves to.
 *
 * The website has always had a real answer to this and it is not the obvious
 * one. `/watchlist/{slug}` plays the movie; `/watchlist/series/{slug}` does
 * NOT start a series at episode one. It looks for the most recent *unfinished*
 * episode of that show in the viewer's history and resumes it, and only falls
 * back to the first episode when there is nothing to resume. That is what
 * makes "pick up where you left off" true rather than decorative.
 *
 * This class exists because the mobile app needed the same answer and the rule
 * lived inline in `FrontendController::watchlistSeriesPlay`. A second copy in
 * the API would have been a fork of a shared mechanism, and the two would have
 * disagreed the first time either changed — the exact fault `PlaybackAuthorizer`
 * was extracted to end in 1.8.22, where the entitlement rules had been written
 * out three times. `watchlistSeriesPlay` now calls `episodeFor()` and the API
 * calls `resolve()`, so there is one implementation of the resume rule.
 *
 * It answers *what to play*, never *whether this viewer may*. Entitlement and
 * publication stay with `PlaybackAuthorizer`, which the playback session
 * endpoint applies afterwards. A card may therefore offer Play on a title the
 * viewer cannot stream; that refusal belongs at the point of streaming, where
 * it can say why, and not on a grid of posters.
 */
class WatchlistPlayResolver
{
    public function __construct(private readonly PlaybackAuthorizer $authorizer) {}

    /**
     * What a watchlist card's Play control should open.
     *
     * Returns null when there is nothing to play — an unreleased title, a
     * series with no episodes, a type that is not playable. **The caller draws
     * no Play control in that case rather than a disabled one**, which is the
     * same rule the site follows when it swaps "Play now" for "View details"
     * on an upcoming card.
     *
     * @return array{type: 'movie'|'episode', id: int}|null
     */
    public function resolve(mixed $watchable, ?Authenticatable $user): ?array
    {
        if ($watchable instanceof Movie) {
            // `isReleased` covers draft, scheduled and upcoming in one place,
            // and lets an admin through — the three cases `watchlistPlay`
            // enumerates by hand, from the service that already owns them.
            return $this->authorizer->isReleased($watchable, $user)
                ? ['type' => 'movie', 'id' => (int) $watchable->id]
                : null;
        }

        if ($watchable instanceof Episode) {
            return $this->authorizer->isReleased($watchable, $user)
                ? ['type' => 'episode', 'id' => (int) $watchable->id]
                : null;
        }

        if ($watchable instanceof Show) {
            $episode = $this->episodeFor($watchable, $user?->getAuthIdentifier());

            return $episode === null ? null : ['type' => 'episode', 'id' => (int) $episode->id];
        }

        return null;
    }

    /**
     * The episode a series should open at: resume first, else the beginning.
     *
     * Lifted verbatim from `FrontendController::watchlistSeriesPlay`, ordering
     * included. **The fallback orders by `season_id` then `number`, not by
     * season *number*** — that is the site's own ordering and it is kept
     * rather than tidied, because changing which episode a viewer lands on is
     * a behaviour change wearing a refactor's clothes. A show whose seasons
     * were created out of order would move under existing viewers.
     *
     * `$userId` may be null for a signed-out caller, which simply means there
     * is no history to resume from.
     */
    public function episodeFor(Show $show, int|string|null $userId): ?Episode
    {
        if ($userId !== null) {
            $resume = WatchHistoryItem::query()
                ->where('user_id', $userId)
                ->where('watchable_type', (new Episode)->getMorphClass())
                ->where('completed', false)
                ->whereIn('watchable_id', function ($query) use ($show) {
                    $query->select('episodes.id')
                        ->from('episodes')
                        ->join('seasons', 'seasons.id', '=', 'episodes.season_id')
                        ->where('seasons.show_id', $show->id);
                })
                ->orderByDesc('watched_at')
                ->first();

            if ($resume) {
                $resumeEpisode = Episode::with('season.show')->find($resume->watchable_id);

                if ($resumeEpisode) {
                    return $resumeEpisode;
                }
            }
        }

        return Episode::query()
            ->whereHas('season', fn ($query) => $query->where('show_id', $show->id))
            ->orderBy('season_id')
            ->orderBy('number')
            ->first();
    }
}
