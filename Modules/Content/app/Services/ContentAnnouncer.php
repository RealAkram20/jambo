<?php

namespace Modules\Content\app\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Notifications\app\Events\EpisodeAdded;
use Modules\Notifications\app\Events\MovieAdded;
use Modules\Notifications\app\Events\SeasonAdded;
use Modules\Notifications\app\Events\ShowAdded;

/**
 * The one place content announcements are allowed to fire.
 *
 * Two rules, and every content notification obeys both:
 *
 *   1. NEVER announce something the user cannot open. Public routes resolve
 *      through `published()` / `detailVisible()`, which require
 *      `published_at <= now()`. Announcing on `status = published` alone
 *      (what the admin controllers used to do) sends users to a 404 whenever
 *      the release date is in the future. Every announce* method here gates
 *      on the model's own isPubliclyVisible() — the exact PHP mirror of the
 *      query scope the route will run.
 *
 *   2. Announce at most once. `announced_at` makes that durable, so a
 *      published → draft → published round trip, an admin re-saving a form,
 *      and the scheduled sweep racing a save all converge on one broadcast.
 *
 * Anything with a future release date simply isn't announced yet — the
 * `content:announce-due` command picks it up the minute it goes live.
 */
class ContentAnnouncer
{
    /**
     * Announce a movie if it is live now and hasn't been announced.
     */
    public function announceMovie(Movie $movie): bool
    {
        return $this->announce($movie, fn () => new MovieAdded(
            $movie->id,
            $movie->title,
            $movie->slug,
            $movie->poster_url,
        ));
    }

    /**
     * Announce a show if it is live now and hasn't been announced.
     *
     * Note a show with zero published episodes is deliberately still
     * announceable — its detail page loads and lists what's coming. It's
     * seasons and episodes that need something watchable behind them.
     */
    public function announceShow(Show $show): bool
    {
        return $this->announce($show, fn () => new ShowAdded(
            $show->id,
            $show->title,
            $show->slug,
            $show->poster_url,
        ));
    }

    /**
     * Announce a season once its show is live and it has a live episode.
     *
     * Announcing a season also silently marks its already-live episodes as
     * announced. Without that, dropping a full season fires "Season 2 added"
     * plus one alert per episode in the same minute — the season is the
     * headline, the episodes are its contents.
     */
    public function announceSeason(Season $season): bool
    {
        $show = $season->show;

        if ($show === null) {
            return false;
        }

        $announced = $this->announce($season, fn () => new SeasonAdded(
            $show->title,
            $season->number,
            $show->slug,
            $season->poster_url ?? $show->poster_url,
        ));

        if ($announced) {
            $season->episodes()
                ->published()
                ->whereNull('announced_at')
                ->get()
                ->each(fn (Episode $episode) => $this->markAnnounced($episode));
        }

        return $announced;
    }

    /**
     * Announce an episode once it is live AND its show is publicly reachable
     * — an episode of a draft show has no page to land on.
     */
    public function announceEpisode(Episode $episode): bool
    {
        $season = $episode->season;
        $show = $season?->show;

        if ($season === null || $show === null || ! $show->isPubliclyAvailable()) {
            return false;
        }

        return $this->announce($episode, fn () => new EpisodeAdded(
            $show->title,
            $season->number,
            $episode->number,
            $episode->title,
            $show->slug,
            $episode->still_url ?? $show->poster_url,
        ));
    }

    /**
     * Announce everything whose release moment has now passed.
     *
     * Driven by `content:announce-due` every minute. Ordering matters:
     * shows before seasons before episodes, so a whole series dropping at
     * once announces as "new series" rather than a burst of episode alerts.
     *
     * @return array<string,int> counts keyed by content type
     */
    public function sweepDue(): array
    {
        $counts = ['movies' => 0, 'shows' => 0, 'seasons' => 0, 'episodes' => 0];

        Movie::query()
            ->whereNull('announced_at')
            ->published()
            ->orderBy('published_at')
            ->each(function (Movie $movie) use (&$counts) {
                $counts['movies'] += (int) $this->announceMovie($movie);
            });

        Show::query()
            ->whereNull('announced_at')
            ->where('status', Show::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at')
            ->each(function (Show $show) use (&$counts) {
                $counts['shows'] += (int) $this->announceShow($show);
            });

        Season::query()
            ->whereNull('announced_at')
            ->with('show')
            ->whereHas('show', fn ($s) => $s->published())
            ->whereHas('episodes', fn ($e) => $e->published())
            ->each(function (Season $season) use (&$counts) {
                $counts['seasons'] += (int) $this->announceSeason($season);
            });

        // Re-read after the season sweep: a season announcement stamps its
        // episodes as announced, so those correctly drop out here.
        Episode::query()
            ->whereNull('announced_at')
            ->published()
            ->with('season.show')
            ->orderBy('published_at')
            ->each(function (Episode $episode) use (&$counts) {
                $counts['episodes'] += (int) $this->announceEpisode($episode);
            });

        // A scheduled title goes live because time passed, not because anyone
        // wrote to it — so CatalogCacheObserver (which flushes on a status /
        // published_at change) never fires for it, and the cached home rails
        // would keep hiding the very title we just announced. Flush once, and
        // only when something actually went live.
        if (array_sum($counts) > 0) {
            try {
                Cache::flush();
            } catch (\Throwable $e) {
                Log::warning('Catalog cache flush failed after announcing content', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $counts;
    }

    /**
     * Gate, fire, stamp. `announced_at` is written whether or not the
     * listeners succeed — a broadcast that partially failed is not a reason
     * to re-notify everyone who did receive it on the next tick.
     */
    private function announce(Movie|Show|Season|Episode $model, callable $event): bool
    {
        if ($model->announced_at !== null || ! $model->isPubliclyVisible()) {
            return false;
        }

        $this->markAnnounced($model);

        try {
            event($event());
        } catch (\Throwable $e) {
            Log::error('Content announcement failed to dispatch', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    /**
     * Stamped through Eloquent (never a query-builder update) per the
     * cache-invalidation contract on the model docblocks.
     *
     * Note this write alone does NOT flush the catalog cache —
     * CatalogCacheObserver only reacts to `status` / `published_at` changes,
     * and `announced_at` is neither. sweepDue() flushes explicitly for that
     * reason. See docs/architecture/content-cache-invalidation.md
     */
    private function markAnnounced(Movie|Show|Season|Episode $model): void
    {
        $model->announced_at = now();
        $model->save();
    }
}
