<?php

namespace Modules\Frontend\app\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;

/**
 * Personalised "Top Picks for You" ranking.
 *
 * Warm users (Phase 1-3): per-user genre/cast affinity vectors drive a
 * candidate score, oversampled + diversity-filtered down to the shelf size.
 * Cold users + guests (Phase 4): fall back to the global weighted blend
 * (editor_boost → views → quality) shared with the Top 10 rails.
 *
 * Movies only. The `section-cards` partial routes every item through the
 * movie detail page, so mixing shows in would produce broken links — the
 * spec permits filtering to movies only in fetchCandidates(), so we do.
 */
class TopPicksRecommender
{
    public const CACHE_KEY_USER_PREFIX = 'user:';
    public const CACHE_KEY_USER_SUFFIX = ':top_picks:v1';
    public const CACHE_KEY_GUEST = 'topPicks:guest:v1';
    public const CACHE_KEY_DAILY_SERIES_PREFIX = 'tab_series_of_the_day:';
    public const CACHE_KEY_DAILY_SERIES_SUFFIX = ':v1';
    public const CACHE_KEY_SMART_SHUFFLE_USER_SUFFIX = ':smart_shuffle:v1';
    public const CACHE_KEY_SMART_SHUFFLE_GUEST = 'smart_shuffle:guest:v1';

    /** Catalog-wide "what got finished lately" counts, shared by all users. */
    public const CACHE_KEY_SHUFFLE_TRENDING = 'smart_shuffle:trending:v1';

    /** Genre ids a guest has browsed in the current session. */
    public const SESSION_KEY_GUEST_GENRES = 'jambo.guest_genre_signal';

    /**
     * Anti-repeat memory for Smart Shuffle — the titles we last put on the
     * user's shelf, so the next window can push them down.
     *
     * Session-backed, NOT cache-backed, and that is deliberate.
     * CatalogCacheObserver calls Cache::flush() on every movie/show/episode
     * create or publish, which would wipe this memory site-wide every time
     * an admin adds a title — leaving the anti-repeat penalty empty exactly
     * on the busy catalogs that need it most. The session survives that,
     * needs no schema, and matches the semantics anyway: repetition is
     * something a user perceives across one visit.
     */
    public const SESSION_KEY_SHUFFLE_SEEN = 'jambo.smart_shuffle_seen';
    public const CACHE_KEY_FRESH_PICKS_USER_SUFFIX = ':fresh_picks:v1';
    public const CACHE_KEY_FRESH_PICKS_GUEST = 'fresh_picks:guest:v1';
    public const CACHE_KEY_UPCOMING_USER_SUFFIX = ':upcoming:v1';
    public const CACHE_KEY_UPCOMING_GUEST = 'upcoming:guest:v1';

    /**
     * Global ranking cold-start threshold, mirrors the one used for the
     * Top 10 rails. Below this many completions across all titles of a
     * kind, the weighted blend is noise — use popularity instead.
     */
    private const GLOBAL_COLD_START_MIN = 20;

    public function __construct(private CacheRepository $cache)
    {
    }

    public function forUser(int $userId, int $limit = 8): Collection
    {
        $key = self::CACHE_KEY_USER_PREFIX . $userId . self::CACHE_KEY_USER_SUFFIX;
        $ttl = (int) config('frontend.recommendations.cache_ttl_user', 3600);

        return $this->cache->remember($key, $ttl, fn () => $this->computeForUser($userId, $limit));
    }

    public function forGuest(int $limit = 8): Collection
    {
        $ttl = (int) config('frontend.recommendations.cache_ttl_guest', 1800);

        return $this->cache->remember(
            self::CACHE_KEY_GUEST,
            $ttl,
            fn () => $this->globalTopPicks(Movie::class, $limit),
        );
    }

    /**
     * AI Smart Shuffle — half familiar, half discovery, sampled rather
     * than sorted so the shelf turns over between refresh windows.
     *
     * Warm users:
     *   • Familiar half — candidates scored on *graded* genre + cast
     *     affinity. Binary genre membership (the old behaviour) rated a
     *     title in your #1 genre exactly like one in your #3.
     *   • Discovery half — item-item collaborative filtering: titles
     *     finished by viewers who finished what you finished. Cross-genre
     *     browsing backfills it when co-watch data is too thin to mean
     *     anything.
     *   • Completed and in-progress titles are excluded. In-progress
     *     already owns the Continue Watching rail, and the same poster in
     *     two rails on one screen reads as a bug.
     *   • Titles shown in recent windows are penalised, so a refresh
     *     actually produces a different shelf.
     *   • Every pick carries a `_shuffleReason` the card renders.
     *
     * Guests: personalised from the genres browsed in the current session
     * when there are any; trending + fresh otherwise.
     *
     * Selection is a rank-biased weighted sample, not a uniform shuffle.
     * The old `->take(20)->shuffle()->take(5)` gave the best-matching
     * title and the 20th-best identical odds, which meant popularity —
     * not taste — decided the shelf.
     */
    public function smartShuffle(?int $userId, int $limit = 10): Collection
    {
        $conf = config('frontend.recommendations.smart_shuffle');

        if ($userId !== null) {
            $key = self::CACHE_KEY_USER_PREFIX . $userId . self::CACHE_KEY_SMART_SHUFFLE_USER_SUFFIX;
            $ttl = (int) ($conf['cache_ttl_user'] ?? 1800);
            return $this->cache->remember($key, $ttl, fn () => $this->computeSmartShuffleForUser($userId, $limit));
        }

        // Guests carrying an in-session browsing signal get their own
        // cache slot, keyed on the signal. A single global guest key
        // would serve one visitor's personalised shelf to every other.
        $signal = $this->guestGenreSignal();
        $key = empty($signal)
            ? self::CACHE_KEY_SMART_SHUFFLE_GUEST
            : self::CACHE_KEY_SMART_SHUFFLE_GUEST . ':' . substr(sha1(implode(',', $signal)), 0, 12);

        $ttl = (int) ($conf['cache_ttl_guest'] ?? 900);

        return $this->cache->remember($key, $ttl, fn () => $this->coldSmartShuffle($limit, $signal));
    }

    private function computeSmartShuffleForUser(int $userId, int $limit): Collection
    {
        if ($this->isColdUser($userId)) {
            return $this->coldSmartShuffle($limit, []);
        }

        $conf = config('frontend.recommendations.smart_shuffle');
        $poolSize = (int) ($conf['pool_size'] ?? 40);
        $decay = (float) ($conf['rank_decay'] ?? 0.88);

        $excluded = $this->shuffleExclusions($userId);
        $recentlyShown = $this->recentlyShownIds($userId);
        $trending = $this->trendingCounts();

        // Drop non-positive genres BEFORE taking the top N. `arsort` +
        // `array_slice` on the raw vector would happily promote a genre
        // with negative affinity (earned from the abandon penalty) into
        // the "familiar" set whenever the user has fewer than N liked
        // genres — recommending the very thing they keep bailing on.
        $genreAff = $this->normaliseVector($this->positiveOnly($this->buildGenreAffinity($userId)));
        $castAff  = $this->normaliseVector($this->positiveOnly($this->buildCastAffinity($userId)));

        arsort($genreAff);
        $topGenreIds = array_slice(array_keys($genreAff), 0, (int) ($conf['top_genres_count'] ?? 3));

        // Odd shelf sizes give the extra seat to the familiar half.
        $affinityTarget = (int) ceil($limit / 2);

        $score = fn (Collection $pool): Collection => $pool
            ->each(function (Movie $m) use ($genreAff, $castAff, $trending, $recentlyShown) {
                $m->_shuffleScore = $this->scoreShuffleCandidate($m, $genreAff, $castAff, $trending, $recentlyShown);
            })
            ->sortByDesc('_shuffleScore')
            ->values();

        // --- Familiar half ---------------------------------------------
        $affinity = $this->weightedSample(
            $score($this->buildAffinityPool($topGenreIds, $excluded, $poolSize)),
            $affinityTarget,
            $decay,
        );

        $used = array_merge($excluded, $affinity->pluck('id')->all());

        // --- Discovery half --------------------------------------------
        // Take whatever the familiar half couldn't fill, rather than a flat
        // half-shelf: if the user's top genres are thin on unwatched titles,
        // those seats are better spent on scored discovery picks than on the
        // unpersonalised popularity backfill at the bottom of this method.
        $discoveryTarget = $limit - $affinity->count();

        // Collaborative signal leads. Note the CF pool is NOT restricted
        // to genres outside the user's taste: "you wouldn't have found
        // this yourself" is the goal, and a same-genre title surfaced by
        // co-watch data serves that better than a random cross-genre one.
        $discoveryPool = $score($this->collaborativePool($userId, $used, $poolSize));

        if ($discoveryPool->count() < $discoveryTarget) {
            $seen = array_merge($used, $discoveryPool->pluck('id')->all());
            $discoveryPool = $discoveryPool
                ->concat($score($this->buildDiscoveryPool($topGenreIds, $seen, $poolSize)))
                ->sortByDesc('_shuffleScore')
                ->values();
        }

        $discovery = $this->weightedSample($discoveryPool, $discoveryTarget, $decay);

        $shelf = $affinity->concat($discovery);

        // A thin catalog, or aggressive exclusions on a heavy watcher,
        // can leave the shelf short — top it up so the rail never renders
        // as a half-empty row.
        if ($shelf->count() < $limit) {
            $shelf = $shelf->concat($this->shuffleBackfill(
                array_merge($used, $shelf->pluck('id')->all()),
                $limit - $shelf->count(),
            ));
        }

        // Interleave, so the shelf doesn't visibly split into "5 familiar
        // then 5 unfamiliar" — the mix is the point of the feature.
        $shelf = $shelf->shuffle()->values();

        $this->attachReasons($shelf, $userId, $genreAff, $castAff, $trending);
        $this->rememberShown($userId, $shelf->pluck('id')->all());

        return $shelf;
    }

    /**
     * Guests and cold-start users.
     *
     * When the visitor has opened movie detail pages this session we have
     * a genre signal. It's weak, but it's the difference between a shelf
     * that reacts inside one visit and one that stays generic until they
     * sign up. Without it: what people are finishing now, plus what's new.
     *
     * @param array<int, int> $guestGenreIds
     */
    private function coldSmartShuffle(int $limit, array $guestGenreIds = []): Collection
    {
        $conf = config('frontend.recommendations.smart_shuffle');
        $poolSize = (int) ($conf['pool_size'] ?? 40);
        $recencyDays = (int) ($conf['discovery_recency_days'] ?? 60);
        $decay = (float) ($conf['rank_decay'] ?? 0.88);

        $trending = $this->trendingCounts();
        $shelf = collect();

        // --- Session-signal half (guests who've browsed something) ------
        if (!empty($guestGenreIds)) {
            $matched = $this->weightedSample(
                $this->buildAffinityPool($guestGenreIds, [], $poolSize),
                (int) ceil($limit / 2),
                $decay,
            );
            $matched->each(fn (Movie $m) => $m->_shuffleReason = __('recommendReason.browsing'));
            $shelf = $shelf->concat($matched);
        }

        // --- Trending half ----------------------------------------------
        $remaining = $limit - $shelf->count();

        $popular = $this->weightedSample(
            $this->publishedExcept($shelf->pluck('id')->all())
                ->orderByDesc('views_count')
                ->orderByDesc('published_at')
                ->take($poolSize)
                ->get(),
            (int) ceil($remaining / 2),
            $decay,
        );
        $popular->each(fn (Movie $m) => $m->_shuffleReason = ($trending[$m->id] ?? 0) > 0
            ? __('recommendReason.trending')
            : __('recommendReason.popular'));
        $shelf = $shelf->concat($popular);

        // --- Fresh half ---------------------------------------------------
        // `published_at`, not `created_at`: release date is what the user
        // means by "new", and it's what every other shelf here sorts on.
        $fresh = $this->weightedSample(
            $this->publishedExcept($shelf->pluck('id')->all())
                ->where('published_at', '>=', now()->subDays($recencyDays))
                ->orderByDesc('published_at')
                ->take($poolSize)
                ->get(),
            $limit - $shelf->count(),
            $decay,
        );
        $fresh->each(fn (Movie $m) => $m->_shuffleReason = __('recommendReason.just_added'));
        $shelf = $shelf->concat($fresh);

        // Young catalogs have almost nothing inside the recency window.
        if ($shelf->count() < $limit) {
            $backfill = $this->shuffleBackfill($shelf->pluck('id')->all(), $limit - $shelf->count());
            $backfill->each(fn (Movie $m) => $m->_shuffleReason = __('recommendReason.popular'));
            $shelf = $shelf->concat($backfill);
        }

        return $shelf->shuffle()->values();
    }

    /**
     * Fresh Picks — personalised ranking restricted to titles
     * published inside the recency window (default 60 days).
     *
     * Warm users: score the fresh pool with the same genre+cast
     * affinity as Top Picks, apply the diversity cap, take $limit.
     * Cold users / guests: no personal signal, order by recency
     * and return the newest $limit. Pool backfills beyond the
     * window when the catalog is thin so the shelf always fills.
     *
     * Different from Top Picks (all-time, no recency filter) and
     * Smart Shuffle (shuffled, not recency-bounded). All three
     * share affinity helpers so tuning one propagates to the others.
     */
    public function freshPicks(?int $userId, int $limit = 10): Collection
    {
        $conf = config('frontend.recommendations.fresh_picks');

        if ($userId !== null) {
            $key = self::CACHE_KEY_USER_PREFIX . $userId . self::CACHE_KEY_FRESH_PICKS_USER_SUFFIX;
            $ttl = (int) ($conf['cache_ttl_user'] ?? 7200);
            return $this->cache->remember($key, $ttl, fn () => $this->computeFreshPicksForUser($userId, $limit));
        }

        $ttl = (int) ($conf['cache_ttl_guest'] ?? 7200);
        return $this->cache->remember(
            self::CACHE_KEY_FRESH_PICKS_GUEST,
            $ttl,
            fn () => $this->freshPicksGuest($limit),
        );
    }

    private function computeFreshPicksForUser(int $userId, int $limit): Collection
    {
        ['fresh' => $fresh, 'backfill' => $backfill] = $this->buildFreshPool($userId, includePersonalExclusions: true);

        if ($this->isColdUser($userId)) {
            return $fresh->concat($backfill)->take($limit)->values();
        }

        $genreAff = $this->buildGenreAffinity($userId);
        $castAff = $this->buildCastAffinity($userId);
        $inProgressIds = $this->inProgressMovieIds($userId);

        $score = fn (Collection $pool) => $pool->map(function (Movie $m) use ($genreAff, $castAff, $inProgressIds) {
            $m->_freshScore = $this->scoreCandidate($m, $genreAff, $castAff, $inProgressIds);
            return $m;
        })->sortByDesc('_freshScore')->values();

        // Fresh always ranks first — the section title promises "fresh"
        // so even a low-affinity fresh title beats a high-affinity old
        // title. Diversity cap applied within the fresh scored pool.
        $diverseFresh = $this->applyDiversityFilter($score($fresh), $limit);
        if ($diverseFresh->count() >= $limit) {
            return $diverseFresh;
        }

        // Backfill runs only when fresh+diversity can't fill the shelf.
        // Older titles that match the user's taste get the tail slots,
        // in affinity-ranked order.
        $remaining = $limit - $diverseFresh->count();
        $backfillScored = $score($backfill)->take($remaining);

        return $diverseFresh->concat($backfillScored)->values();
    }

    private function freshPicksGuest(int $limit): Collection
    {
        ['fresh' => $fresh, 'backfill' => $backfill] = $this->buildFreshPool(userId: null, includePersonalExclusions: false);
        return $fresh->concat($backfill)->take($limit)->values();
    }

    /**
     * Recency-windowed pool split into two buckets:
     *   • `fresh`: titles published inside the recency window,
     *     ordered newest-first.
     *   • `backfill`: the next newest titles outside the window,
     *     only filled when the fresh bucket is below pool_size.
     *
     * Callers can rank the two buckets separately so fresh is
     * guaranteed to dominate the shelf — the section title promises
     * fresh content, so older material must never push fresh out.
     *
     * When $includePersonalExclusions is true, completed titles are
     * removed up-front (warm path — no point ranking movies the
     * user already finished).
     *
     * @return array{fresh: Collection, backfill: Collection}
     */
    private function buildFreshPool(?int $userId, bool $includePersonalExclusions): array
    {
        $conf = config('frontend.recommendations.fresh_picks');
        $poolSize = (int) ($conf['pool_size'] ?? 40);
        $recencyDays = (int) ($conf['recency_days'] ?? 60);

        $excludeIds = [];
        if ($includePersonalExclusions && $userId !== null) {
            $movieMorph = (new Movie)->getMorphClass();
            $excludeIds = DB::table('watch_history')
                ->where('user_id', $userId)
                ->where('watchable_type', $movieMorph)
                ->where('completed', true)
                ->pluck('watchable_id')
                ->all();
        }

        $relations = ['genres', 'cast' => fn ($q) => $q->wherePivotIn('role', ['actor', 'actress', 'director', 'writer'])];

        // `published_at`, not `created_at`. created_at is when the *row* was
        // inserted, so importing a 1990 film yesterday would have made it
        // "fresh" — and on a seeded catalog, where every row shares an
        // insert timestamp, ordering by it collapses to insertion order and
        // the rail stops being sorted at all.
        $fresh = Movie::published()
            ->with($relations)
            ->when(!empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->where('published_at', '>=', now()->subDays($recencyDays))
            ->orderByDesc('published_at')
            ->take($poolSize)
            ->get();

        if ($fresh->count() >= $poolSize) {
            return ['fresh' => $fresh, 'backfill' => collect()];
        }

        $usedIds = array_merge($excludeIds, $fresh->pluck('id')->all());
        $deficit = $poolSize - $fresh->count();

        $backfill = Movie::published()
            ->with($relations)
            ->when(!empty($usedIds), fn ($q) => $q->whereNotIn('id', $usedIds))
            ->orderByDesc('published_at')
            ->take($deficit)
            ->get();

        return ['fresh' => $fresh, 'backfill' => $backfill];
    }

    /**
     * Upcoming — announced / scheduled titles (status = upcoming).
     *
     * Warm users: affinity-ranked so announcements that match their
     * taste surface first. Cold users / guests: soonest release first,
     * nulls last (titles without a set release date go after dated ones).
     *
     * Unlike published shelves, there's no completion-based exclusion
     * to apply (you can't have watched an upcoming title) — the pool
     * is simply everything the admin has flagged as upcoming.
     */
    public function upcoming(?int $userId, int $limit = 10): Collection
    {
        $conf = config('frontend.recommendations.upcoming');

        if ($userId !== null) {
            $key = self::CACHE_KEY_USER_PREFIX . $userId . self::CACHE_KEY_UPCOMING_USER_SUFFIX;
            $ttl = (int) ($conf['cache_ttl_user'] ?? 7200);
            return $this->cache->remember($key, $ttl, fn () => $this->computeUpcomingForUser($userId, $limit));
        }

        $ttl = (int) ($conf['cache_ttl_guest'] ?? 7200);
        return $this->cache->remember(
            self::CACHE_KEY_UPCOMING_GUEST,
            $ttl,
            fn () => $this->upcomingGuest($limit),
        );
    }

    /**
     * Paginated listing of all upcoming titles (movies + shows) for
     * the /upcoming page. Sorted by release date ASC, nulls last,
     * with created_at DESC as the final tiebreak. Each returned item
     * is tagged with a `_kind` attribute ('movie' | 'show') so the
     * view can pick the right detail-page route.
     *
     * No server cache: the /upcoming page is a browse destination,
     * not a hot home-shelf. DB hit per page load is acceptable and
     * keeps admin-toggled status changes visible immediately.
     *
     * @return array{items: Collection, hasMore: bool, total: int}
     */
    public function upcomingListing(int $offset = 0, int $limit = 20): array
    {
        $movies = Movie::upcoming()->with('genres')->get()
            ->each(fn ($m) => $m->_kind = 'movie');
        $shows = Show::upcoming()->with('genres')->get()
            ->each(fn ($s) => $s->_kind = 'show');

        $sorted = $movies->concat($shows)
            ->sort(fn ($a, $b) => $this->compareUpcoming($a, $b))
            ->values();

        $total = $sorted->count();
        $offset = max(0, $offset);
        $limit = max(1, $limit);
        $slice = $sorted->slice($offset, $limit)->values();
        $hasMore = ($offset + $slice->count()) < $total;

        return ['items' => $slice, 'hasMore' => $hasMore, 'total' => $total];
    }

    /**
     * Ordering rule for the Upcoming listing:
     *   1. Dated titles before undated ones (null release = last).
     *   2. Earlier release date first (soonest on top).
     *   3. Newer record first when dates match.
     */
    private function compareUpcoming($a, $b): int
    {
        $aDate = $a->published_at;
        $bDate = $b->published_at;

        if ($aDate === null && $bDate === null) {
            return ($b->created_at <=> $a->created_at);
        }
        if ($aDate === null) {
            return 1;
        }
        if ($bDate === null) {
            return -1;
        }
        $cmp = $aDate <=> $bDate;
        return $cmp !== 0 ? $cmp : ($b->created_at <=> $a->created_at);
    }

    private function computeUpcomingForUser(int $userId, int $limit): Collection
    {
        $pool = $this->fetchUpcomingPool();

        if ($pool->isEmpty() || $this->isColdUser($userId)) {
            return $pool->take($limit)->values();
        }

        $genreAff = $this->buildGenreAffinity($userId);
        $castAff = $this->buildCastAffinity($userId);

        $scored = $pool->map(function (Movie $m) use ($genreAff, $castAff) {
            $score = 0.0;
            foreach ($m->genres as $g) {
                $score += $genreAff[$g->id] ?? 0;
            }
            foreach ($m->cast as $p) {
                $score += $castAff[$p->id] ?? 0;
            }
            // Editor pin still matters — a hyped announcement should
            // win the front regardless of the user's affinity vector.
            $weights = config('frontend.recommendations.weights');
            $score += ($weights['editor_boost'] ?? 1.0) * ($m->editor_boost ?? 0);
            $m->_upcomingScore = $score;
            return $m;
        })->sortByDesc('_upcomingScore')->values();

        return $scored->take($limit)->values();
    }

    private function upcomingGuest(int $limit): Collection
    {
        // fetchUpcomingPool() already orders by release date (nulls
        // last) then created_at DESC — that's the right shelf order
        // for guests, so just take the first $limit.
        return $this->fetchUpcomingPool()->take($limit)->values();
    }

    /**
     * Pool of all upcoming movies, ordered "soonest release first,
     * undated items after". Uses a CASE expression so the null-last
     * behaviour is portable across MySQL and SQLite (MySQL's default
     * NULL ordering differs and SQLite has no `NULLS LAST` clause).
     */
    private function fetchUpcomingPool(): Collection
    {
        return Movie::upcoming()
            ->with(['genres', 'cast' => fn ($q) => $q->wherePivotIn('role', ['actor', 'actress', 'director', 'writer'])])
            ->orderByRaw('CASE WHEN published_at IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('published_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Genres + the cast roles that actually drive a viewer's choice.
     * Every Smart Shuffle pool eager-loads both, so scoreShuffleCandidate()
     * and the reason captions never trigger an N+1.
     *
     * @return array<int|string, mixed>
     */
    private function shuffleRelations(): array
    {
        return [
            'genres',
            'cast' => fn ($q) => $q->wherePivotIn('role', ['actor', 'actress', 'director', 'writer']),
        ];
    }

    /**
     * @param array<int, int> $excludeIds
     */
    private function publishedExcept(array $excludeIds): \Illuminate\Database\Eloquent\Builder
    {
        return Movie::published()
            ->with($this->shuffleRelations())
            ->when(!empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds));
    }

    /**
     * Ids Smart Shuffle must never surface: titles the user completed, and
     * titles they have in progress. In-progress already owns the Continue
     * Watching rail, so re-recommending it puts the same poster twice on
     * one screen. Abandoned titles are a subset of in-progress (position
     * advanced, never completed), which means quitting a movie early now
     * also takes it out of the shuffle — previously it stayed eligible
     * forever.
     *
     * @return array<int, int>
     */
    private function shuffleExclusions(int $userId): array
    {
        return DB::table('watch_history')
            ->where('user_id', $userId)
            ->where('watchable_type', (new Movie)->getMorphClass())
            ->where(fn ($q) => $q
                ->where('completed', true)
                ->orWhere(fn ($p) => $p->where('completed', false)->where('position_seconds', '>', 0)))
            ->pluck('watchable_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Affinity pool — published movies tagged with at least one of the
     * user's top genres. The SQL ordering is only a quality floor for the
     * shortlist; the real ranking happens in scoreShuffleCandidate().
     *
     * @param array<int, int>  $topGenreIds
     * @param array<int, int>  $excludeIds
     */
    private function buildAffinityPool(array $topGenreIds, array $excludeIds, int $size): Collection
    {
        return $this->publishedExcept($excludeIds)
            ->when(!empty($topGenreIds), fn ($q) => $q->whereHas(
                'genres',
                fn ($gq) => $gq->whereIn('genres.id', $topGenreIds),
            ))
            ->orderByDesc('editor_boost')
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->take($size)
            ->get();
    }

    /**
     * Cross-genre pool — published movies matching none of the user's top
     * genres. This is the *fallback* discovery source now, used only when
     * collaborative filtering comes back thin (young catalog, few
     * co-watchers). On its own it's a weak signal: "outside your usual
     * genres" is no reason to think you'd like something.
     *
     * @param array<int, int>  $topGenreIds
     * @param array<int, int>  $excludeIds
     */
    private function buildDiscoveryPool(array $topGenreIds, array $excludeIds, int $size): Collection
    {
        return $this->publishedExcept($excludeIds)
            // The `NOT EXISTS` form lets untagged titles through, which is
            // intentional: content with no genres is still discovery material.
            ->when(!empty($topGenreIds), fn ($q) => $q->whereDoesntHave(
                'genres',
                fn ($gq) => $gq->whereIn('genres.id', $topGenreIds),
            ))
            ->orderByDesc('editor_boost')
            ->orderByDesc('published_at')
            ->orderByDesc('views_count')
            ->take($size)
            ->get();
    }

    /**
     * Last-resort top-up so the rail never renders short.
     *
     * @param array<int, int> $excludeIds
     */
    private function shuffleBackfill(array $excludeIds, int $need): Collection
    {
        if ($need <= 0) {
            return collect();
        }

        return $this->publishedExcept($excludeIds)
            ->orderByDesc('editor_boost')
            ->orderByDesc('views_count')
            ->take($need)
            ->get();
    }

    /**
     * Collaborative filtering — "viewers who finished what you finished
     * also finished these".
     *
     * Two indexed queries rather than a similarity matrix: find the peers
     * who completed any of your recent completions, then count what else
     * those peers completed. Cheap, and it needs no model, no training
     * step, and no new infrastructure.
     *
     * This is what makes the discovery half worth looking at. Returns an
     * empty collection when the co-watch data is too thin to be anything
     * but noise — the caller backfills with cross-genre browsing rather
     * than dressing up a coincidence as a recommendation.
     *
     * @param array<int, int> $excludeIds
     */
    private function collaborativePool(int $userId, array $excludeIds, int $size): Collection
    {
        $conf = config('frontend.recommendations.smart_shuffle');

        if (! ($conf['collab_enabled'] ?? true)) {
            return collect();
        }

        $seedIds = $this->collabSeedIds($userId);

        if (empty($seedIds)) {
            return collect();
        }

        $peerIds = DB::table('watch_history')
            ->where('watchable_type', (new Movie)->getMorphClass())
            ->where('completed', true)
            ->whereIn('watchable_id', $seedIds)
            ->where('user_id', '!=', $userId)
            ->distinct()
            ->limit((int) ($conf['collab_peer_limit'] ?? 400))
            ->pluck('user_id')
            ->all();

        if (empty($peerIds)) {
            return collect();
        }

        $skip = array_values(array_unique(array_merge($seedIds, $excludeIds)));

        $counts = DB::table('watch_history')
            ->where('watchable_type', (new Movie)->getMorphClass())
            ->where('completed', true)
            ->whereIn('user_id', $peerIds)
            ->when(!empty($skip), fn ($q) => $q->whereNotIn('watchable_id', $skip))
            ->select('watchable_id', DB::raw('COUNT(DISTINCT user_id) as peers'))
            ->groupBy('watchable_id')
            ->havingRaw('COUNT(DISTINCT user_id) >= ?', [(int) ($conf['collab_min_peers'] ?? 2)])
            ->orderByDesc('peers')
            ->limit($size)
            ->pluck('peers', 'watchable_id');

        if ($counts->isEmpty()) {
            return collect();
        }

        $max = (float) max($counts->map(fn ($c) => (int) $c)->all());

        return Movie::published()
            ->whereIn('id', $counts->keys()->all())
            ->with($this->shuffleRelations())
            ->get()
            ->each(function (Movie $m) use ($counts, $max) {
                // Normalised to 0..1 so the `collab` weight in config means
                // the same thing on a quiet catalog as on a busy one.
                $m->_collabAffinity = $max > 0 ? ((int) $counts->get($m->id, 0)) / $max : 0.0;
            });
    }

    /**
     * The user's most recent completions — the seeds every co-watch query
     * pivots on.
     *
     * @return array<int, int>
     */
    private function collabSeedIds(int $userId): array
    {
        return DB::table('watch_history')
            ->where('user_id', $userId)
            ->where('watchable_type', (new Movie)->getMorphClass())
            ->where('completed', true)
            ->orderByDesc('watched_at')
            ->limit((int) config('frontend.recommendations.smart_shuffle.collab_seed_titles', 20))
            ->pluck('watchable_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Completions per movie inside the trending window — what people are
     * actually finishing right now, as opposed to `views_count`, which is
     * all-time and lets a years-old hit sit at the top of the pool forever.
     *
     * Catalog-wide, so it's cached once and shared by every user.
     *
     * @return array<int, int>  [movieId => distinct completers]
     */
    private function trendingCounts(): array
    {
        $conf = config('frontend.recommendations.smart_shuffle');
        $days = (int) ($conf['trending_days'] ?? 14);
        $ttl = (int) ($conf['trending_cache_ttl'] ?? 900);

        return $this->cache->remember(
            self::CACHE_KEY_SHUFFLE_TRENDING,
            $ttl,
            fn () => DB::table('watch_history')
                ->where('watchable_type', (new Movie)->getMorphClass())
                ->where('completed', true)
                ->where('watched_at', '>=', now()->subDays($days))
                ->select('watchable_id', DB::raw('COUNT(DISTINCT user_id) as c'))
                ->groupBy('watchable_id')
                ->pluck('c', 'watchable_id')
                ->map(fn ($c) => (int) $c)
                ->all(),
        );
    }

    /**
     * Blended score for one Smart Shuffle candidate. Genre, cast and
     * collab components all arrive normalised to 0..1, so the config
     * weights are directly comparable to one another.
     *
     * @param array<int, float> $genreAff
     * @param array<int, float> $castAff
     * @param array<int, int>   $trending
     * @param array<int, true>  $recentlyShown
     */
    private function scoreShuffleCandidate(
        Movie $m,
        array $genreAff,
        array $castAff,
        array $trending,
        array $recentlyShown,
    ): float {
        $w = config('frontend.recommendations.smart_shuffle.weights');
        $score = 0.0;

        $genreScore = 0.0;
        foreach ($m->genres as $genre) {
            $genreScore += $genreAff[$genre->id] ?? 0.0;
        }
        $score += ($w['genre_affinity'] ?? 1.0) * $genreScore;

        if ($m->relationLoaded('cast')) {
            $castScore = 0.0;
            foreach ($m->cast as $person) {
                $castScore += $castAff[$person->id] ?? 0.0;
            }
            $score += ($w['cast_affinity'] ?? 0.6) * $castScore;
        }

        $score += ($w['collab'] ?? 1.4) * (float) ($m->_collabAffinity ?? 0.0);
        $score += ($w['trending'] ?? 0.5) * log(1 + ($trending[$m->id] ?? 0));
        $score += ($w['editor_boost'] ?? 0.8) * (float) ($m->editor_boost ?? 0);

        if (isset($recentlyShown[$m->id])) {
            $score += (float) ($w['recently_shown'] ?? -1.2);
        }

        return $score;
    }

    /**
     * Rank-biased sample without replacement: the candidate at rank r is
     * drawn with weight $decay^r. The top of the list is favoured but
     * never guaranteed, which is what lets the shelf churn between windows
     * while still respecting the ranking.
     *
     * $decay = 1.0 degenerates to a uniform shuffle (throws the ranking
     * away); $decay → 0 to a strict top-N (never churns).
     *
     * Expects $ranked to already be sorted best-first.
     */
    private function weightedSample(Collection $ranked, int $n, float $decay): Collection
    {
        if ($n <= 0 || $ranked->isEmpty()) {
            return collect();
        }

        $decay = min(1.0, max(0.01, $decay));
        $pool = $ranked->values()->all();

        $weights = [];
        foreach (array_keys($pool) as $rank) {
            $weights[$rank] = $decay ** $rank;
        }

        $picked = collect();

        while ($picked->count() < $n && !empty($weights)) {
            $total = array_sum($weights);
            if ($total <= 0.0) {
                break;
            }

            $roll = (mt_rand() / mt_getrandmax()) * $total;
            $chosen = array_key_first($weights);
            $acc = 0.0;

            foreach ($weights as $rank => $weight) {
                $acc += $weight;
                if ($roll <= $acc) {
                    $chosen = $rank;
                    break;
                }
            }

            $picked->push($pool[$chosen]);
            unset($weights[$chosen]);
        }

        return $picked;
    }

    /**
     * Ids surfaced in previous windows, as a flipped map for O(1) lookup.
     * See SESSION_KEY_SHUFFLE_SEEN for why this lives in the session.
     *
     * @return array<int, true>
     */
    private function recentlyShownIds(int $userId): array
    {
        $session = $this->sessionStore();

        if ($session === null) {
            return [];
        }

        return array_flip(array_map(
            'intval',
            (array) $session->get(self::shuffleSeenKey($userId), []),
        ));
    }

    /**
     * Remember what we just showed so the next window can push it down.
     *
     * @param array<int, int> $ids
     */
    private function rememberShown(int $userId, array $ids): void
    {
        $session = $this->sessionStore();

        if ($session === null) {
            return;
        }

        $key = self::shuffleSeenKey($userId);
        $size = (int) config('frontend.recommendations.smart_shuffle.recent_memory_size', 30);

        // Newest first, so the cap evicts the oldest memories.
        $merged = array_values(array_unique(array_map(
            'intval',
            array_merge($ids, (array) $session->get($key, [])),
        )));

        $session->put($key, array_slice($merged, 0, $size));
    }

    /**
     * Scoped by user id so logging out and back in as somebody else on the
     * same browser doesn't inherit the previous account's shuffle memory.
     */
    private static function shuffleSeenKey(int $userId): string
    {
        return self::SESSION_KEY_SHUFFLE_SEEN . '.' . $userId;
    }

    /**
     * Tag each pick with the reason it earned its slot, strongest first:
     * a real co-watch link, then cast, then genre, then trending.
     *
     * Nothing here is invented. "Because you watched X" is only ever
     * emitted when X is genuinely the title most co-completed with this
     * pick — a plausible-sounding but fabricated reason would do more
     * damage to trust than showing no reason at all, so when we can't
     * name something we computed, the card gets the neutral caption.
     *
     * @param array<int, float> $genreAff
     * @param array<int, float> $castAff
     * @param array<int, int>   $trending
     */
    private function attachReasons(
        Collection $shelf,
        int $userId,
        array $genreAff,
        array $castAff,
        array $trending,
    ): void {
        if ($shelf->isEmpty() || ! config('frontend.recommendations.smart_shuffle.reasons_enabled', true)) {
            return;
        }

        $coWatch = $this->coWatchReasons($userId, $shelf->pluck('id')->all());

        foreach ($shelf as $m) {
            if (isset($coWatch[$m->id])) {
                $m->_shuffleReason = __('recommendReason.because_you_watched', ['title' => $coWatch[$m->id]]);
                continue;
            }

            $person = $this->topMatch($m->relationLoaded('cast') ? $m->cast : collect(), $castAff);
            if ($person !== null) {
                $m->_shuffleReason = __('recommendReason.cast_match', ['name' => $person->full_name]);
                continue;
            }

            $genre = $this->topMatch($m->genres, $genreAff);
            if ($genre !== null) {
                $m->_shuffleReason = __('recommendReason.genre_match', ['genre' => $genre->name]);
                continue;
            }

            $m->_shuffleReason = ($trending[$m->id] ?? 0) > 0
                ? __('recommendReason.trending')
                : __('recommendReason.new_to_you');
        }
    }

    /**
     * For the chosen shelf only, the user's own completed title most often
     * finished alongside each pick.
     *
     * One grouped self-join over at most $limit ids — cheap enough to run
     * after selection, and it's what lets a card say "Because you watched
     * Sinners" and be telling the truth.
     *
     * @param  array<int, int> $candidateIds
     * @return array<int, string>  [movieId => seed title]
     */
    private function coWatchReasons(int $userId, array $candidateIds): array
    {
        if (empty($candidateIds) || ! config('frontend.recommendations.smart_shuffle.collab_enabled', true)) {
            return [];
        }

        $seedIds = $this->collabSeedIds($userId);

        if (empty($seedIds)) {
            return [];
        }

        $movieMorph = (new Movie)->getMorphClass();

        $rows = DB::table('watch_history as seed')
            ->join('watch_history as also', fn ($join) => $join
                ->on('also.user_id', '=', 'seed.user_id')
                ->where('also.watchable_type', '=', $movieMorph)
                ->where('also.completed', '=', true))
            ->where('seed.watchable_type', $movieMorph)
            ->where('seed.completed', true)
            ->where('seed.user_id', '!=', $userId)
            ->whereIn('seed.watchable_id', $seedIds)
            ->whereIn('also.watchable_id', $candidateIds)
            ->select(
                'seed.watchable_id as seed_id',
                'also.watchable_id as cand_id',
                DB::raw('COUNT(DISTINCT seed.user_id) as peers'),
            )
            ->groupBy('seed.watchable_id', 'also.watchable_id')
            ->orderByDesc('peers')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Rows arrive strongest-first, so the first one seen for a
        // candidate is its best link.
        $bestSeed = [];
        foreach ($rows as $row) {
            $bestSeed[(int) $row->cand_id] ??= (int) $row->seed_id;
        }

        $titles = Movie::whereIn('id', array_values(array_unique($bestSeed)))->pluck('title', 'id');

        $reasons = [];
        foreach ($bestSeed as $candidateId => $seedId) {
            if (isset($titles[$seedId])) {
                $reasons[$candidateId] = $titles[$seedId];
            }
        }

        return $reasons;
    }

    /**
     * Highest-affinity related record on a candidate (genre or cast
     * member), or null when the user has no signal on any of them.
     *
     * @param array<int, float> $affinity
     */
    private function topMatch(Collection $related, array $affinity): ?Model
    {
        $best = null;
        $bestScore = 0.0;

        foreach ($related as $record) {
            $score = $affinity[$record->id] ?? 0.0;
            if ($score > $bestScore) {
                $best = $record;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * @param  array<int, float> $vector
     * @return array<int, float>
     */
    private function positiveOnly(array $vector): array
    {
        return array_filter($vector, fn ($v) => $v > 0);
    }

    /**
     * Scale a vector so its largest value is 1.0. Without this, genre
     * affinity (built from raw counts × weights) and cast affinity live on
     * different scales and their config weights would mean nothing.
     *
     * @param  array<int, float> $vector
     * @return array<int, float>
     */
    private function normaliseVector(array $vector): array
    {
        if (empty($vector)) {
            return [];
        }

        $max = max(array_map('abs', $vector));

        if ($max <= 0.0) {
            return [];
        }

        return array_map(fn ($v) => $v / $max, $vector);
    }

    /**
     * Genre ids the current guest has browsed this session, most recent
     * first. Empty for authenticated users (they have real signals) and in
     * any context with no started session — queue workers, console
     * commands, and the like.
     *
     * @return array<int, int>
     */
    private function guestGenreSignal(): array
    {
        $session = $this->guestSignalEnabled() ? $this->sessionStore() : null;

        if ($session === null) {
            return [];
        }

        $ids = array_map('intval', (array) $session->get(self::SESSION_KEY_GUEST_GENRES, []));

        return array_slice(array_values(array_unique($ids)), 0, 3);
    }

    private function guestSignalEnabled(): bool
    {
        return (bool) config('frontend.recommendations.smart_shuffle.guest_session_signal', true);
    }

    /**
     * The session store, or null when there's no session container at all
     * (queue worker, console command, stateless API route).
     *
     * Deliberately does NOT gate on `isStarted()`. The store reports itself
     * as not-started once the response has been sent and the session saved,
     * even though its data is still perfectly readable — gating on it made
     * the signal silently invisible to every caller that ran after the
     * request had been handled, which is most of them. Reading a
     * never-started store is harmless: it just returns the default.
     */
    private function sessionStore(): ?\Illuminate\Contracts\Session\Session
    {
        return app()->bound('session.store') ? app('session.store') : null;
    }

    /**
     * Record a browsed title's genres as guest session signal, newest
     * first. No-op for authenticated users, whose real watch/rating/
     * watchlist signals are strictly better. Called from the movie detail
     * page — the one place we know a visitor showed interest in something
     * specific before they've watched anything.
     */
    public function recordGuestSignal(Movie $movie): void
    {
        $session = (auth()->check() || ! $this->guestSignalEnabled())
            ? null
            : $this->sessionStore();

        if ($session === null) {
            return;
        }

        $genreIds = $movie->relationLoaded('genres')
            ? $movie->genres->pluck('id')->all()
            : $movie->genres()->pluck('genres.id')->all();

        if (empty($genreIds)) {
            return;
        }

        $existing = (array) $session->get(self::SESSION_KEY_GUEST_GENRES, []);

        // New genres go to the front: what they're looking at now should
        // outweigh what they glanced at ten pages ago.
        $merged = array_values(array_unique(array_map(
            'intval',
            array_merge($genreIds, $existing),
        )));

        $session->put(self::SESSION_KEY_GUEST_GENRES, array_slice($merged, 0, 8));
    }

    /**
     * Shared entry point for the cold fallback and the Top 10 rails.
     * Weighted blend of popularity, quality, sentiment, buzz, recency,
     * and admin boost — or a pure popularity sort when the catalog has
     * too few completions to rank meaningfully.
     *
     * Moved here from SectionDataComposer so the personalised and
     * global paths share one implementation (no duplication per DoD).
     *
     * @param class-string<Movie|Show> $modelClass
     */
    public function globalTopPicks(string $modelClass, int $limit = 10): Collection
    {
        $completionsTotal = DB::table('watch_history')
            ->where('watchable_type', $modelClass)
            ->where('completed', true)
            ->count();

        $base = $modelClass::published()->with('genres');

        if ($completionsTotal < self::GLOBAL_COLD_START_MIN) {
            return $base
                ->orderByDesc('editor_boost')
                ->orderByDesc('views_count')
                ->orderByDesc('published_at')
                ->take($limit)
                ->get()
                ->values();
        }

        $table = (new $modelClass)->getTable();

        return $base
            ->select($table . '.*')
            ->selectRaw("(
                1.0 * {$table}.views_count
                + 3.0 * COALESCE((SELECT COUNT(*) FROM watch_history
                        WHERE watchable_type = ? AND watchable_id = {$table}.id AND completed = 1), 0)
                + 0.5 * COALESCE((SELECT COUNT(*) FROM watchlist_items
                        WHERE watchable_type = ? AND watchable_id = {$table}.id), 0)
                + 2.0 * COALESCE((SELECT AVG(stars) FROM ratings
                        WHERE ratable_type = ? AND ratable_id = {$table}.id), 0)
                + 0.5 * COALESCE((SELECT COUNT(*) FROM reviews
                        WHERE reviewable_type = ? AND reviewable_id = {$table}.id AND is_published = 1), 0)
                - 0.01 * COALESCE(DATEDIFF(NOW(), {$table}.published_at), 0)
                + 100 * COALESCE({$table}.editor_boost, 0)
            ) AS top_pick_score", [$modelClass, $modelClass, $modelClass, $modelClass])
            ->orderByDesc('top_pick_score')
            ->orderByDesc('views_count')
            ->take($limit)
            ->get()
            ->values();
    }

    /**
     * "Top 10 Series of the Day" — shows ranked by distinct users who
     * watched any of their episodes in the last 24 hours. Result is
     * keyed on today's calendar date so the ranking is stable within
     * the day and rolls over at local midnight; the stale key expires
     * on its own and a fresh compute runs on the next page load.
     *
     * If daily activity is thin (brand-new catalog, overnight quiet
     * window, …) the shelf backfills from all-time popularity so the
     * user never sees a nearly-empty rail.
     */
    public function topSeriesOfTheDay(int $limit = 10): Collection
    {
        $cacheKey = self::CACHE_KEY_DAILY_SERIES_PREFIX
            . now()->toDateString()
            . self::CACHE_KEY_DAILY_SERIES_SUFFIX;

        return $this->cache->remember($cacheKey, 86400, fn () => $this->computeTopSeriesOfTheDay($limit));
    }

    /**
     * Two-pass build:
     *   1. Rank shows by distinct 24h viewers (join watch_history on
     *      the episode morph, aggregate up to the show).
     *   2. If fewer than $limit shows have any daily activity, pad
     *      the tail with top all-time shows not already in the list.
     *
     * Preserves the daily-signal ordering at the head of the list —
     * the padded tail is just "safety net" so the rail always fills.
     */
    private function computeTopSeriesOfTheDay(int $limit): Collection
    {
        $since = now()->subDay();
        $episodeMorph = (new Episode)->getMorphClass();

        $dailyIds = DB::table('shows')
            ->select('shows.id')
            ->selectRaw('COUNT(DISTINCT wh.user_id) as daily_viewers')
            ->join('seasons', 'seasons.show_id', '=', 'shows.id')
            ->join('episodes', 'episodes.season_id', '=', 'seasons.id')
            ->join('watch_history as wh', function ($join) use ($episodeMorph) {
                $join->on('wh.watchable_id', '=', 'episodes.id')
                    ->where('wh.watchable_type', '=', $episodeMorph);
            })
            ->where('wh.watched_at', '>=', $since)
            ->where('shows.status', Show::STATUS_PUBLISHED)
            ->whereNotNull('shows.published_at')
            ->where('shows.published_at', '<=', now())
            ->groupBy('shows.id')
            ->orderByDesc('daily_viewers')
            ->orderByDesc('shows.views_count')
            ->orderByDesc('shows.published_at')
            ->limit($limit)
            ->pluck('shows.id')
            ->all();

        $result = collect();

        if (!empty($dailyIds)) {
            // Re-filter the daily IDs through Show::scopePublished so a
            // series that's flagged published but has no playable
            // (HLS-encoded) episode never lands in the rail. The raw
            // SQL above can't apply the model scope, so we apply it
            // here on the result fetch.
            $shows = Show::published()
                ->whereIn('id', $dailyIds)
                ->with(['seasons.episodes'])
                ->get()
                ->keyBy('id');

            foreach ($dailyIds as $id) {
                if (isset($shows[$id])) {
                    $result->push($shows[$id]);
                }
            }
        }

        if ($result->count() < $limit) {
            $extras = Show::published()
                ->with(['seasons.episodes'])
                ->when(!empty($dailyIds), fn ($q) => $q->whereNotIn('id', $dailyIds))
                ->orderByDesc('views_count')
                ->orderByDesc('published_at')
                ->take($limit - $result->count())
                ->get();

            $result = $result->concat($extras);
        }

        return $result->values();
    }

    private function computeForUser(int $userId, int $limit): Collection
    {
        if ($this->isColdUser($userId)) {
            return $this->globalTopPicks(Movie::class, $limit);
        }

        $genreAffinity = $this->buildGenreAffinity($userId);
        $castAffinity = $this->buildCastAffinity($userId);
        $inProgressIds = $this->inProgressMovieIds($userId);

        $candidates = $this->fetchCandidates($userId);

        // Empty vectors (e.g. user has only watchlist adds on
        // genre-less/cast-less titles) still yield candidates; the
        // popularity + recency priors keep the shelf from being empty.
        $scored = $candidates->map(function (Movie $m) use ($genreAffinity, $castAffinity, $inProgressIds) {
            $m->_topPickScore = $this->scoreCandidate($m, $genreAffinity, $castAffinity, $inProgressIds);
            return $m;
        })->sortByDesc('_topPickScore')->values();

        $oversampleFactor = (int) config('frontend.recommendations.oversample_factor', 3);
        $pool = $scored->take($limit * $oversampleFactor);

        return $this->applyDiversityFilter($pool, $limit);
    }

    private function isColdUser(int $userId): bool
    {
        $threshold = (int) config('frontend.recommendations.cold_threshold', 3);

        $signals = DB::table('watch_history')->where('user_id', $userId)->where('completed', true)->count()
            + DB::table('ratings')->where('user_id', $userId)->count()
            + DB::table('watchlist_items')->where('user_id', $userId)->count();

        return $signals < $threshold;
    }

    /**
     * Build `[genreId => affinity]` from the user's signal tables.
     *
     * Pulls three raw pivots (completions, ratings, watchlist) and
     * one "abandoned in first 20%" slice, aggregates each by genre,
     * applies the configured weights.
     *
     * @return array<int, float>
     */
    private function buildGenreAffinity(int $userId): array
    {
        $weights = config('frontend.recommendations.weights');
        $affinity = [];

        $movieMorph = (new Movie)->getMorphClass();

        // Completions by genre.
        $completions = DB::table('watch_history as wh')
            ->join('genre_movie as gm', function ($join) use ($movieMorph) {
                $join->on('gm.movie_id', '=', 'wh.watchable_id')
                    ->where('wh.watchable_type', '=', $movieMorph);
            })
            ->where('wh.user_id', $userId)
            ->where('wh.completed', true)
            ->select('gm.genre_id', DB::raw('COUNT(*) as c'))
            ->groupBy('gm.genre_id')
            ->pluck('c', 'gm.genre_id');
        foreach ($completions as $gid => $c) {
            $affinity[$gid] = ($affinity[$gid] ?? 0) + $weights['completion_genre'] * (int) $c;
        }

        // Ratings by genre (star-sum, so 5★ weighs more than 1★).
        $ratings = DB::table('ratings as r')
            ->join('genre_movie as gm', function ($join) use ($movieMorph) {
                $join->on('gm.movie_id', '=', 'r.ratable_id')
                    ->where('r.ratable_type', '=', $movieMorph);
            })
            ->where('r.user_id', $userId)
            ->select('gm.genre_id', DB::raw('SUM(r.stars) as s'))
            ->groupBy('gm.genre_id')
            ->pluck('s', 'gm.genre_id');
        foreach ($ratings as $gid => $s) {
            $affinity[$gid] = ($affinity[$gid] ?? 0) + $weights['rating_genre_per_star'] * (float) $s;
        }

        // Watchlist by genre.
        $watchlist = DB::table('watchlist_items as wl')
            ->join('genre_movie as gm', function ($join) use ($movieMorph) {
                $join->on('gm.movie_id', '=', 'wl.watchable_id')
                    ->where('wl.watchable_type', '=', $movieMorph);
            })
            ->where('wl.user_id', $userId)
            ->select('gm.genre_id', DB::raw('COUNT(*) as c'))
            ->groupBy('gm.genre_id')
            ->pluck('c', 'gm.genre_id');
        foreach ($watchlist as $gid => $c) {
            $affinity[$gid] = ($affinity[$gid] ?? 0) + $weights['watchlist_genre'] * (int) $c;
        }

        // Abandoned in first 20% — requires a known duration, otherwise
        // we can't tell where the user bailed. Intentionally small penalty
        // (weight is negative in config).
        $abandoned = DB::table('watch_history as wh')
            ->join('genre_movie as gm', function ($join) use ($movieMorph) {
                $join->on('gm.movie_id', '=', 'wh.watchable_id')
                    ->where('wh.watchable_type', '=', $movieMorph);
            })
            ->where('wh.user_id', $userId)
            ->where('wh.completed', false)
            ->whereNotNull('wh.duration_seconds')
            ->where('wh.duration_seconds', '>', 0)
            ->whereRaw('(wh.position_seconds / wh.duration_seconds) < 0.2')
            ->select('gm.genre_id', DB::raw('COUNT(*) as c'))
            ->groupBy('gm.genre_id')
            ->pluck('c', 'gm.genre_id');
        foreach ($abandoned as $gid => $c) {
            $affinity[$gid] = ($affinity[$gid] ?? 0) + $weights['abandoned_genre'] * (int) $c;
        }

        return $affinity;
    }

    /**
     * Build `[personId => affinity]` restricted to actor/director/writer
     * roles — a cinematographer or makeup artist probably doesn't drive
     * recommendations for a typical viewer.
     *
     * @return array<int, float>
     */
    private function buildCastAffinity(int $userId): array
    {
        $weights = config('frontend.recommendations.weights');
        $affinity = [];

        $movieMorph = (new Movie)->getMorphClass();
        $roles = ['actor', 'actress', 'director', 'writer'];

        $completions = DB::table('watch_history as wh')
            ->join('movie_person as mp', function ($join) use ($movieMorph) {
                $join->on('mp.movie_id', '=', 'wh.watchable_id')
                    ->where('wh.watchable_type', '=', $movieMorph);
            })
            ->where('wh.user_id', $userId)
            ->where('wh.completed', true)
            ->whereIn('mp.role', $roles)
            ->select('mp.person_id', DB::raw('COUNT(*) as c'))
            ->groupBy('mp.person_id')
            ->pluck('c', 'mp.person_id');
        foreach ($completions as $pid => $c) {
            $affinity[$pid] = ($affinity[$pid] ?? 0) + $weights['completion_cast'] * (int) $c;
        }

        $ratings = DB::table('ratings as r')
            ->join('movie_person as mp', function ($join) use ($movieMorph) {
                $join->on('mp.movie_id', '=', 'r.ratable_id')
                    ->where('r.ratable_type', '=', $movieMorph);
            })
            ->where('r.user_id', $userId)
            ->whereIn('mp.role', $roles)
            ->select('mp.person_id', DB::raw('SUM(r.stars) as s'))
            ->groupBy('mp.person_id')
            ->pluck('s', 'mp.person_id');
        foreach ($ratings as $pid => $s) {
            $affinity[$pid] = ($affinity[$pid] ?? 0) + $weights['rating_cast_per_star'] * (float) $s;
        }

        $watchlist = DB::table('watchlist_items as wl')
            ->join('movie_person as mp', function ($join) use ($movieMorph) {
                $join->on('mp.movie_id', '=', 'wl.watchable_id')
                    ->where('wl.watchable_type', '=', $movieMorph);
            })
            ->where('wl.user_id', $userId)
            ->whereIn('mp.role', $roles)
            ->select('mp.person_id', DB::raw('COUNT(*) as c'))
            ->groupBy('mp.person_id')
            ->pluck('c', 'mp.person_id');
        foreach ($watchlist as $pid => $c) {
            $affinity[$pid] = ($affinity[$pid] ?? 0) + $weights['watchlist_cast'] * (int) $c;
        }

        return $affinity;
    }

    /**
     * Published movies the user hasn't completed. Eager-load genres +
     * cast (actor/director/writer only) so scoreCandidate() is N-free
     * and the blade's `$item->genres` access doesn't re-query.
     */
    private function fetchCandidates(int $userId): Collection
    {
        $movieMorph = (new Movie)->getMorphClass();

        $completedIds = DB::table('watch_history')
            ->where('user_id', $userId)
            ->where('watchable_type', $movieMorph)
            ->where('completed', true)
            ->pluck('watchable_id')
            ->all();

        return Movie::published()
            ->when($completedIds, fn ($q) => $q->whereNotIn('id', $completedIds))
            ->with([
                'genres',
                'cast' => fn ($q) => $q->wherePivotIn('role', ['actor', 'actress', 'director', 'writer']),
            ])
            ->get();
    }

    /**
     * @return array<int, true> flipped map for O(1) isset() lookups
     */
    private function inProgressMovieIds(int $userId): array
    {
        $movieMorph = (new Movie)->getMorphClass();

        return DB::table('watch_history')
            ->where('user_id', $userId)
            ->where('watchable_type', $movieMorph)
            ->where('completed', false)
            ->where('position_seconds', '>', 0)
            ->pluck('watchable_id')
            ->flip()
            ->all();
    }

    /**
     * @param array<int, float> $genreAff
     * @param array<int, float> $castAff
     * @param array<int, true>  $inProgressIds
     */
    private function scoreCandidate(Movie $c, array $genreAff, array $castAff, array $inProgressIds): float
    {
        $weights = config('frontend.recommendations.weights');
        $score = 0.0;

        foreach ($c->genres as $g) {
            $score += $genreAff[$g->id] ?? 0;
        }

        foreach ($c->cast as $p) {
            $score += $castAff[$p->id] ?? 0;
        }

        $score += $weights['popularity_log'] * log(($c->views_count ?? 0) + 1);
        $score += $weights['recency'] * $this->recencyBoost($c);
        $score += $weights['editor_boost'] * ($c->editor_boost ?? 0);

        if (isset($inProgressIds[$c->id])) {
            $score += $weights['in_progress_penalty'];
        }

        return $score;
    }

    /**
     * Linear 0→1 over the last 12 months, clamped at 0 so ancient
     * titles get no recency contribution (not a negative one).
     */
    private function recencyBoost(Movie $c): float
    {
        if (!$c->published_at) {
            return 0.0;
        }
        $months = $c->published_at->diffInMonths(now());
        return max(0.0, 1.0 - ($months / 12.0));
    }

    /**
     * Walk top-down, keep each candidate unless its primary genre
     * (first genre on the record) has already appeared twice. Stops
     * when we've collected $limit. Candidates without any genre fall
     * through as "ungenre" — capped the same way.
     *
     * If diversity starves the shelf (thin catalog, few cross-genre
     * candidates), the shelf ships short rather than break the cap.
     * The spec's intent is "at most 2 per primary genre, full stop" —
     * the oversampling plus a healthy catalog are expected to keep
     * the output full in practice.
     */
    private function applyDiversityFilter(Collection $ranked, int $limit): Collection
    {
        $perGenreCap = (int) config('frontend.recommendations.diversity_per_primary_genre', 2);
        $genreCounts = [];
        $kept = collect();

        foreach ($ranked as $c) {
            $primary = $c->genres->first()?->id ?? '__ungenre__';
            if (($genreCounts[$primary] ?? 0) >= $perGenreCap) {
                continue;
            }
            $kept->push($c);
            $genreCounts[$primary] = ($genreCounts[$primary] ?? 0) + 1;
            if ($kept->count() >= $limit) {
                break;
            }
        }

        return $kept->values();
    }
}
