<?php

namespace Modules\Frontend\app\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\FeaturedItem;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Vj;
use Modules\Streaming\app\Models\WatchHistoryItem;

/**
 * Composes the homepage: every rail, the hero, and Continue Watching.
 *
 * This was `SectionDataComposer::build()` until 2026-09-08. It moved out
 * because `GET /api/v1/home` has to answer with the same rails, in the same
 * order, chosen by the same rules — and the plan is explicit that the app's
 * home should BE the website's home by construction rather than by imitation
 * (docs/plans/mobile-offline-app.md §4.2). A second implementation would have
 * drifted the first time an admin reordered a category, which is exactly how
 * the entitlement rules ended up in three places.
 *
 * The composer still owns the per-request memoisation and the per-user
 * watchlist index, because those are about rendering a page. What lives here
 * is the question "what is on the homepage right now", which both callers ask.
 *
 * Some rails are personal (`topPicks`, `upcomingMovies`, `recommendedMovies`,
 * `freshMovies`, `continueWatching` all read auth()->id()), so the result is
 * per-viewer and must never be put in a cross-request cache as a whole. The
 * expensive parts are already cached inside TopPicksRecommender on per-date
 * keys, which is the right granularity.
 */
class HomeRailsService
{
    /**
     * Everything the homepage needs, keyed exactly as the section blades read
     * it. The keys are a contract: HomeRailsPinTest lists them, and a blade
     * somewhere renders each one.
     *
     * @return array<string, mixed>
     */
    public function forWeb(): array
    {
        $movieBase = fn () => Movie::published()->with('genres');
        $showBase = fn () => Show::published()->with('genres');

        // Two windows, on purpose (CHANGELOG 1.8.13):
        //   "Top 10 … To Watch" rails  → distinct viewers over the last 7 days
        //   "#X in … Today" shelves    → distinct viewers over the last 24h
        // Both refresh daily on per-date cache keys. The rails used to be
        // all-time (globalTopPicks), which the same titles hold for months;
        // 1.8.11 briefly made the movie rail daily, which made it twitchy.
        $recommender = app(TopPicksRecommender::class);
        $topMovies = $recommender->topMoviesOfTheWeek(10);

        // Homepage category shelves — ONE pool: every category the admin
        // marked "Visible Home" (with published content), in the exact
        // sort_order set by drag-and-drop on the admin Categories table.
        // First fills the fixed shelf slot, the next three fill the
        // remaining slots — no shuffling, the admin order IS the page
        // order. Nothing outside this pool ever reaches the homepage.
        $categoryShelves = $this->shapeCategoryRails(
            Category::visibleHome()
                ->orderBy('sort_order')
                ->orderBy('name')
        );

        return [
            // Movies
            'latestMovies'   => $movieBase()->orderByDesc('created_at')->take(10)->get(),
            'popularMovies'  => $movieBase()->orderByDesc('views_count')->take(10)->get(),
            'topMovies'      => $topMovies,
            // Upcoming — driven by the STATUS_UPCOMING flag, not a future
            // published_at (the old query was unsatisfiable because the
            // published() scope already forces published_at <= now).
            'upcomingMovies' => app(TopPicksRecommender::class)->upcoming(auth()->id(), 10),
            'recommendedMovies' => app(TopPicksRecommender::class)->smartShuffle(auth()->id(), 10),
            'specialsMovies' => $movieBase()->orderByDesc('created_at')->take(10)->get(),
            'freshMovies'    => app(TopPicksRecommender::class)->freshPicks(auth()->id(), 10),

            // Shows
            'latestShows'    => $showBase()->orderByDesc('created_at')->take(10)->get(),
            'popularShows'   => $showBase()->orderByDesc('views_count')->take(10)->get(),
            'topShows'       => $recommender->topSeriesOfTheWeek(10),
            'recommendedShows' => $showBase()->inRandomOrder()->take(10)->get(),
            'internationalShows' => $showBase()->inRandomOrder()->take(10)->get(),

            // Hero
            'heroMovies'     => $movieBase()->orderByDesc('created_at')->take(3)->get(),
            'heroItems'      => $this->buildHero(),

            // Vertical slider — all ten of the Top 10 Movies of the DAY (24h
            // window, not the weekly rail above) so the "#X in Movies Today"
            // badge on each slide is accurate.
            //
            // The `loadAvg('ratings', 'stars')` that used to hang off this
            // line is gone with the thing it fed. It batched an average for
            // the five-star row, and that row was removed from
            // vertical-banner.blade.php and from the app on 2026-09-10 (ADR-
            // 0006) once it was established that nothing in the product can
            // write a rating. A pre-computation for a display nobody renders
            // is a query per home page for nothing.
            'verticalFeatured' => $recommender->topMoviesOfTheDay(10),

            // Tab slider — Top 10 Series of the Day: ranked by distinct 24h
            // viewers, cached on a per-date key so the shelf is stable within
            // the day and flips at midnight. Falls back to all-time popularity
            // when daily activity is thin. See TopPicksRecommender.
            'tabSeries' => app(TopPicksRecommender::class)->topSeriesOfTheDay(10),

            // Only on Streamit — premium/exclusive (movies with tier_required set)
            'exclusiveMovies' => Movie::published()
                ->with('genres')
                ->whereNotNull('tier_required')
                ->orderByDesc('created_at')
                ->take(8)
                ->get(),

            // Top Picks — personalised per viewer. Warm users get a genre/cast
            // affinity ranking; cold users and guests fall back to the global
            // weighted blend. See docs/plans/top-picks-personalization.md.
            'topPicks' => $this->resolveTopPicks(8),

            // Home Genres rail. Each tile is a piece of artwork borrowed from
            // the genre's most recent published title, and
            // `attachFeaturedImages` resolves the whole rail in two queries —
            // the accessor on its own walks up to four per genre, which is
            // forty on this rail, on every home page, for a decoration.
            'homeGenres' => tap(
                Genre::withCount(['movies', 'shows'])
                    ->orderByDesc('movies_count')
                    ->take(10)
                    ->get(),
                fn ($genres) => Genre::attachFeaturedImages($genres),
            ),

            // Home VJs rail — narrators ranked by combined catalogue
            // size, but filtered to only VJs with at least one
            // *published* movie or show (otherwise the slider would
            // surface VJs whose entire catalogue is still draft).
            // Card thumbnails use Vj::featured_image_url, which falls
            // back through the VJ's most recent published title.
            'homeVjs' => Vj::query()
                ->where(function ($q) {
                    $q->whereHas('movies', fn ($mq) => $mq->published())
                      ->orWhereHas('shows',  fn ($sq) => $sq->published());
                })
                ->withCount([
                    'movies as movies_count' => fn ($q) => $q->published(),
                    'shows as shows_count' => fn ($q) => $q->published(),
                ])
                ->orderByRaw('(movies_count + shows_count) DESC')
                ->orderBy('id')
                ->take(12)
                ->get(),

            // Your Favourite Personality — top cast by appearance count
            'favoritePersonalities' => Person::withCount(['movies', 'shows'])
                ->orderByDesc('movies_count')
                ->orderByDesc('shows_count')
                ->take(12)
                ->get(),

            'continueWatching' => $this->continueWatchingForUser(),

            // EVERY Visible Home category, in admin sort_order. Empty ones are
            // already dropped, so a freshly toggled category never shows a
            // blank rail.
            //
            // This used to arrive split in two — one "fixed" shelf plus three
            // more — because the page had a fixed slot and three rotating
            // ones. It has neither since 1.8.38: the shelves are one movable
            // block on /admin/home-sections. The split outlived its layout and
            // the cap of four with it, and the cap was silently overruling the
            // admin: six categories were marked Visible Home on the dev box
            // and two of them never reached the page.
            //
            // Removing it costs nothing at the database. The cap was applied
            // to the RESULT — the query above already read every Visible Home
            // category either way — so it only ever hid rows an admin had
            // asked to show.
            //
            // 🔴 The number of shelves is now the admin's, set by the Visible
            // Home toggle on the Categories screen and bounded by nothing in
            // code. That is correct for honesty and it is the right place for
            // the decision, but note what one shelf costs before flagging
            // thirty: `shapeCategoryRails` eager-loads every published movie
            // and show in each category to then keep twelve. Bounding that
            // needs a per-parent limit, which Eloquent cannot express in a
            // `with()` closure — a window function or one query per category.
            // Do that before this list grows, not after.
            'homeCategories' => $categoryShelves,
        ];
    }

    /**
     * Runs a category query and attaches `railItems` to each result:
     * published movies + shows merged, newest-added first, capped at
     * 12, tagged `_isShow` (same convention as buildHero) so the rail
     * blade can pick the right detail route per card. Categories that
     * end up with no items are dropped.
     */
    private function shapeCategoryRails($query): Collection
    {
        return $query
            ->with([
                'movies' => fn ($q) => $q->published()->with('genres'),
                'shows'  => fn ($q) => $q->published()->with('genres'),
            ])
            ->get()
            ->each(function (Category $cat) {
                $movies = $cat->movies->each(fn ($m) => $m->_isShow = false);
                $shows  = $cat->shows->each(fn ($s) => $s->_isShow = true);

                $cat->railItems = $movies->concat($shows)
                    ->sortByDesc('created_at')
                    ->take(12)
                    ->values();
            })
            ->filter(fn (Category $cat) => $cat->railItems->isNotEmpty())
            ->values();
    }

    /**
     * The OTT homepage hero: the big banner and the poster rail beside it.
     *
     * Admins own this list at /admin/featured — hand-picked titles in a
     * hand-dragged order (CHANGELOG 1.8.19). When they have not picked
     * anything, or nothing they picked is currently published, this falls
     * back to the original automatic mix of the 3 most-viewed movies and
     * the 3 most-viewed shows, interleaved. So the hero is never empty and
     * the feature can ship before anyone has curated it.
     *
     * Each item is tagged with `_isShow` so the blade can branch without
     * instance checks, and comes with genres/tags/cast already loaded.
     *
     * Homepage only. The banners on /movie, /series and the VJ pages are a
     * different partial fed by FrontendController and are not affected.
     */
    private function buildHero()
    {
        $curated = FeaturedItem::heroItems();

        if ($curated->isNotEmpty()) {
            return $this->withHeroAggregates($curated);
        }

        $relations = [
            'genres',
            'tags',
            'cast' => fn ($q) => $q->wherePivotIn('role', ['actor', 'actress'])->limit(3),
        ];

        $movies = Movie::published()->with($relations)
            ->orderByDesc('views_count')
            ->orderByDesc('created_at')
            ->take(3)
            ->get()
            ->each(fn ($m) => $m->_isShow = false);

        $shows = Show::published()->with(array_merge($relations, ['seasons']))
            ->orderByDesc('views_count')
            ->orderByDesc('created_at')
            ->take(3)
            ->get()
            ->each(fn ($s) => $s->_isShow = true);

        // Interleave so the rail shows movie, show, movie, show, movie, show.
        $items = collect();
        $max = max($movies->count(), $shows->count());
        for ($i = 0; $i < $max; $i++) {
            if (isset($movies[$i])) $items->push($movies[$i]);
            if (isset($shows[$i]))  $items->push($shows[$i]);
        }

        return $this->withHeroAggregates($items);
    }

    /**
     * The one aggregate the hero draws, batched.
     *
     * Both hero paths come through here — the curated list and the automatic
     * fallback — so there is one place that decides what a hero item carries,
     * rather than the curated path quietly lacking a field the fallback has.
     *
     * One query for the whole banner, regardless of how many slides it has.
     * The alternative is what `hero-banner.blade.php` does: it reaches for the
     * first episode's runtime through `seasons->flatMap->episodes`, lazy-
     * loading every episode of every hero series on every page load. The blade
     * still does that; only the API path is fixed here, because changing the
     * blade's query shape is a separate change to a shared file.
     *
     * It batched the star average too until 2026-09-10. The star row is gone
     * from both surfaces (ADR-0006), so that query went with it — a query for
     * a number nothing draws is worse than no query at all.
     *
     * `loadAvg` writes the aggregate onto the model instances themselves, and
     * the partition holds the same instances the caller's collection does, so
     * the collection that comes back is the one that went in.
     *
     * @param  Collection<int, mixed>  $items
     * @return Collection<int, mixed>
     */
    private function withHeroAggregates(Collection $items): Collection
    {
        [$shows] = $items->partition(fn ($item) => (bool) ($item->_isShow ?? false));

        if ($shows->isNotEmpty()) {
            // hasManyThrough seasons, so this is the mean over every episode
            // of the series in one query.
            (new EloquentCollection($shows->all()))->loadAvg('episodes', 'runtime_minutes');
        }

        return $items;
    }

    /**
     * Real Continue Watching row, per authenticated user.
     *
     * Contract:
     *   • Anonymous viewers get an empty collection — the section blade
     *     hides itself entirely in that case.
     *   • For signed-in users: up to WatchHistoryItem::CONTINUE_WATCHING_LIMIT
     *     cards, ordered by most recent heartbeat. Completed rows are
     *     excluded. The write side (WatchHistoryItem::pruneContinueWatching)
     *     enforces the same cap in storage, so the row can't grow unbounded.
     *   • Series are deduplicated: one card per show, and the card
     *     points at the latest episode the user was watching (most
     *     recent heartbeat wins).
     *
     * Each returned element is a normalised stdClass so the card
     * template doesn't need to branch on movie-vs-episode.
     */
    public function continueWatchingForUser(): Collection
    {
        $userId = auth()->id();
        if (!$userId) {
            return collect();
        }

        // Pull enough rows to dedupe across shows. 24 is generous —
        // unlikely a real user has more in-progress items than that
        // between heartbeats, but it gives room for a bingewatcher
        // with multiple episodes of several shows still open.
        $rows = WatchHistoryItem::where('user_id', $userId)
            ->where('completed', false)
            ->orderByDesc('watched_at')
            ->with(['watchable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Movie::class   => ['genres'],
                    Episode::class => ['season.show'],
                ]);
            }])
            ->take(24)
            ->get();

        $cards = collect();
        $seenShows = [];

        foreach ($rows as $row) {
            $w = $row->watchable;
            if (!$w) continue;

            if ($w instanceof Movie) {
                $cards->push($this->buildMovieCard($row, $w));
            } elseif ($w instanceof Episode) {
                $showId = $w->season?->show_id;
                if (!$showId || in_array($showId, $seenShows, true)) {
                    continue;
                }
                $seenShows[] = $showId;
                $cards->push($this->buildEpisodeCard($row, $w));
            }

            if ($cards->count() >= WatchHistoryItem::CONTINUE_WATCHING_LIMIT) break;
        }

        return $cards;
    }

    /**
     * Minutes remaining. Prefers the heartbeat-reported duration
     * (real), falls back to `runtime_minutes` (admin-set). The fallback
     * assumes the user's position splits evenly — inaccurate, but
     * better than showing nothing.
     */
    private function minutesLeft(WatchHistoryItem $h, ?int $runtimeMinutes): int
    {
        if ($h->duration_seconds && $h->duration_seconds > $h->position_seconds) {
            return max(1, (int) ceil(($h->duration_seconds - $h->position_seconds) / 60));
        }
        if ($runtimeMinutes) {
            $pct = max(0, 100 - $h->progressPercent());
            return max(1, (int) ceil($runtimeMinutes * $pct / 100));
        }
        return 10;
    }

    private function buildMovieCard(WatchHistoryItem $h, Movie $m): object
    {
        return (object) [
            'imagePath'       => $m->backdrop_url ?: $m->poster_url ?: 'gameofhero.webp',
            'title'           => $m->title,
            'subtitle'        => $m->published_at?->format('M Y') ?? ($m->year ? (string) $m->year : ''),
            'progressPercent' => $h->progressPercent(),
            'minutesLeft'     => $this->minutesLeft($h, $m->runtime_minutes),
            'watchLink'       => route('frontend.watch', $m->slug),
            'removeType'      => 'movie',
            'removeId'        => $m->id,

            // What to actually resume, for a client that cannot follow a web
            // route. Added 2026-09-08 for /api/v1/home. `removeType` is not
            // the same thing: removing is by show, resuming is by episode.
            'resumeType'      => 'movie',
            'resumeId'        => $m->id,
            'positionSeconds' => (int) $h->position_seconds,
        ];
    }

    /**
     * Route the Top Picks shelf through the personal recommender.
     * Rollout flag lets us fall back to the old random draw live if
     * the algorithm ships with a bug — flip without a redeploy.
     */
    private function resolveTopPicks(int $limit): Collection
    {
        if (!config('frontend.recommendations.enabled', true)) {
            return Movie::published()->with('genres')->inRandomOrder()->take($limit)->get();
        }

        $recommender = app(TopPicksRecommender::class);
        $uid = auth()->id();

        return $uid
            ? $recommender->forUser($uid, $limit)
            : $recommender->forGuest($limit);
    }

    private function buildEpisodeCard(WatchHistoryItem $h, Episode $e): object
    {
        $show = $e->season->show;
        $ep = 'S' . str_pad($e->season->number, 2, '0', STR_PAD_LEFT)
            . 'E' . str_pad($e->number, 2, '0', STR_PAD_LEFT);
        return (object) [
            'imagePath'       => $e->still_url ?: ($show->backdrop_url ?: $show->poster_url ?: 'vikings-portrait.webp'),
            'title'           => $show->title,
            'subtitle'        => $ep . ' · ' . $e->title,
            'progressPercent' => $h->progressPercent(),
            'minutesLeft'     => $this->minutesLeft($h, $e->runtime_minutes),
            'watchLink'       => $e->frontendUrl($show),
            // For shows, remove-by-show_id wipes every episode's history,
            // otherwise a kept row would re-surface the show card on the
            // next render (composer dedupes by show).
            'removeType'      => 'show',
            'removeId'        => $show->id,

            // Resume is the EPISODE, not the show. The web card encodes that
            // in watchLink; a native player needs the id.
            'resumeType'      => 'episode',
            'resumeId'        => $e->id,
            'positionSeconds' => (int) $h->position_seconds,
        ];
    }
}
