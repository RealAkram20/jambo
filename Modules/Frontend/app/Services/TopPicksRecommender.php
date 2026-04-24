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
     * Smart Shuffle — Spotify-style half-familiar / half-discovery mix,
     * randomised within pools so the shelf feels fresh on each refresh
     * window. Much lower TTL than Top Picks so it visibly churns.
     *
     * Contract per call:
     *   • Warm users: ~half picks drawn from their top-affinity genres,
     *     ~half drawn from titles outside those genres (discovery).
     *     Completed titles excluded from both sides.
     *   • Cold users + guests: trending (popularity) mixed with fresh
     *     (recent releases).
     * Results are shuffled together so affinity and discovery don't
     * clump at the front/back.
     */
    public function smartShuffle(?int $userId, int $limit = 10): Collection
    {
        $conf = config('frontend.recommendations.smart_shuffle');

        if ($userId !== null) {
            $key = self::CACHE_KEY_USER_PREFIX . $userId . self::CACHE_KEY_SMART_SHUFFLE_USER_SUFFIX;
            $ttl = (int) ($conf['cache_ttl_user'] ?? 1800);
            return $this->cache->remember($key, $ttl, fn () => $this->computeSmartShuffleForUser($userId, $limit));
        }

        $ttl = (int) ($conf['cache_ttl_guest'] ?? 900);
        return $this->cache->remember(
            self::CACHE_KEY_SMART_SHUFFLE_GUEST,
            $ttl,
            fn () => $this->coldSmartShuffle($limit),
        );
    }

    private function computeSmartShuffleForUser(int $userId, int $limit): Collection
    {
        if ($this->isColdUser($userId)) {
            return $this->coldSmartShuffle($limit);
        }

        $conf = config('frontend.recommendations.smart_shuffle');
        $poolSize = (int) ($conf['pool_size'] ?? 20);
        $topGenresCount = (int) ($conf['top_genres_count'] ?? 3);

        $movieMorph = (new Movie)->getMorphClass();
        $completedIds = DB::table('watch_history')
            ->where('user_id', $userId)
            ->where('watchable_type', $movieMorph)
            ->where('completed', true)
            ->pluck('watchable_id')
            ->all();

        // Identify the user's top genres from the same affinity vectors
        // Top Picks uses. Ties broken by genre id for determinism.
        $genreAffinity = $this->buildGenreAffinity($userId);
        arsort($genreAffinity);
        $topGenreIds = array_slice(array_keys($genreAffinity), 0, $topGenresCount);

        // Split the shelf 50/50; odd limits give the extra seat to
        // affinity (we bias slightly toward the user's known taste).
        $affinityTarget = (int) ceil($limit / 2);
        $discoveryTarget = $limit - $affinityTarget;

        $affinity = $this->buildAffinityPool($topGenreIds, $completedIds, $poolSize)
            ->shuffle()
            ->take($affinityTarget);

        // Chain already-used ids so discovery can't duplicate a title
        // already picked from the affinity pool.
        $usedIds = $affinity->pluck('id')->all();
        $discovery = $this->buildDiscoveryPool($topGenreIds, array_merge($completedIds, $usedIds), $poolSize)
            ->shuffle()
            ->take($discoveryTarget);

        // Final shuffle so the shelf doesn't visibly split into
        // "5 familiar then 5 unfamiliar" — the mix is the point.
        return $affinity->concat($discovery)->shuffle()->values();
    }

    /**
     * Shown to guests and cold-start users. Half trending (popularity),
     * half fresh (recency). Randomised the same way as the warm path
     * so the two feel like the same feature.
     */
    private function coldSmartShuffle(int $limit): Collection
    {
        $conf = config('frontend.recommendations.smart_shuffle');
        $poolSize = (int) ($conf['pool_size'] ?? 20);
        $recencyDays = (int) ($conf['discovery_recency_days'] ?? 60);

        $trendingTarget = (int) ceil($limit / 2);
        $freshTarget = $limit - $trendingTarget;

        $trending = Movie::published()
            ->with('genres')
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->take($poolSize)
            ->get()
            ->shuffle()
            ->take($trendingTarget);

        $usedIds = $trending->pluck('id')->all();

        $fresh = Movie::published()
            ->with('genres')
            ->when(!empty($usedIds), fn ($q) => $q->whereNotIn('id', $usedIds))
            ->where('published_at', '>=', now()->subDays($recencyDays))
            ->orderByDesc('published_at')
            ->take($poolSize)
            ->get()
            ->shuffle()
            ->take($freshTarget);

        // Fresh pool may be thin on a young catalog — backfill from
        // popularity so the shelf still fills to $limit.
        $combined = $trending->concat($fresh);
        if ($combined->count() < $limit) {
            $used = $combined->pluck('id')->all();
            $backfill = Movie::published()
                ->with('genres')
                ->when(!empty($used), fn ($q) => $q->whereNotIn('id', $used))
                ->orderByDesc('views_count')
                ->take($limit - $combined->count())
                ->get();
            $combined = $combined->concat($backfill);
        }

        return $combined->shuffle()->values();
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

        $relations = ['genres', 'cast' => fn ($q) => $q->wherePivotIn('role', ['actor', 'director', 'writer'])];

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
            ->with(['genres', 'cast' => fn ($q) => $q->wherePivotIn('role', ['actor', 'director', 'writer'])])
            ->orderByRaw('CASE WHEN published_at IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('published_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Affinity pool — published movies tagged with at least one of the
     * user's top genres, excluding completed titles. Ordered by a
     * light popularity + recency score so the random sample is drawn
     * from a quality-biased shortlist, not the whole catalog.
     *
     * @param array<int, int>  $topGenreIds
     * @param array<int, int>  $excludeIds
     */
    private function buildAffinityPool(array $topGenreIds, array $excludeIds, int $size): Collection
    {
        $q = Movie::published()->with('genres');

        if (!empty($excludeIds)) {
            $q->whereNotIn('id', $excludeIds);
        }

        if (!empty($topGenreIds)) {
            $q->whereHas('genres', fn ($gq) => $gq->whereIn('genres.id', $topGenreIds));
        }

        return $q
            ->orderByDesc('editor_boost')
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->take($size)
            ->get();
    }

    /**
     * Discovery pool — published movies explicitly NOT matching any of
     * the user's top genres. The point of Smart Shuffle: surface what
     * they wouldn't normally click. Ordered editor_boost → recency →
     * popularity so the shortlist has a quality floor before shuffle.
     *
     * @param array<int, int>  $topGenreIds
     * @param array<int, int>  $excludeIds
     */
    private function buildDiscoveryPool(array $topGenreIds, array $excludeIds, int $size): Collection
    {
        $q = Movie::published()->with('genres');

        if (!empty($excludeIds)) {
            $q->whereNotIn('id', $excludeIds);
        }

        if (!empty($topGenreIds)) {
            // "Has no genre in the top-affinity set" — the `NOT EXISTS`
            // form lets titles with no genres at all through, which is
            // intentional: untagged content is still discovery material.
            $q->whereDoesntHave('genres', fn ($gq) => $gq->whereIn('genres.id', $topGenreIds));
        }

        return $q
            ->orderByDesc('editor_boost')
            ->orderByDesc('published_at')
            ->orderByDesc('views_count')
            ->take($size)
            ->get();
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
            $shows = Show::whereIn('id', $dailyIds)
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
        $roles = ['actor', 'director', 'writer'];

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
                'cast' => fn ($q) => $q->wherePivotIn('role', ['actor', 'director', 'writer']),
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
