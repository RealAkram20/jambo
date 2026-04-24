# Top Picks for You — Personalized Recommendations

**Status:** implemented (2026-04-24)
**Owner:** Frontend module
**Shipped behaviour:** signal-driven personal algorithm — `TopPicksRecommender`
at `Modules/Frontend/app/Services/TopPicksRecommender.php`
**Goal:** replace the random draw with a ranking that reflects what each
viewer has watched, rated, and saved

## Why this exists

The section title says "Top Picks for You" but the current query is:

```php
// Modules/Frontend/app/View/Composers/SectionDataComposer.php:134
'topPicks' => Movie::published()
    ->with('genres')
    ->inRandomOrder()
    ->take(8)
    ->get(),
```

Every page load a fresh random 8 — identical treatment for every viewer,
for guests, for brand-new signups, for power users with 200 hours of
watch history. The "for you" label is a lie. This spec replaces it with
a tier-1 personal algorithm that uses signals Jambo already captures.

## Current state audit (what's already in place)

**Signals captured (no new tables needed)**
- `watch_history` — columns: `user_id`, `watchable_type`, `watchable_id`,
  `completed`, `position_seconds`, `watched_at`.
- `ratings` — columns: `user_id`, `ratable_type`, `ratable_id`, `stars`
  (1–5 scale).
- `watchlist_items` — columns: `user_id`, `watchable_type`,
  `watchable_id`, `added_at`.
- `genre_movie`, `genre_show` pivots link titles → genres.
- `movie_person`, `show_person` pivots link titles → cast/crew, with
  a `role` column (actor, director, writer, …).
- `movies.editor_boost`, `shows.editor_boost` admin-pin field.

**Existing wiring**
- `SectionDataComposer::compose()` registered for
  `frontend::Pages.*` and `frontend::components.sections.*` via
  `FrontendServiceProvider::registerViewComposers()` at line 36-47.
- Section blade at
  `Modules/Frontend/resources/views/components/sections/top-pict.blade.php`
  already reads `$topPicks` — no markup changes needed.
- Rendered on `Pages/MainPages/ott-page.blade.php:72`.

**Existing algorithms for comparison**
- `Top 10 Movies` / `Top 10 Series` — see `topPicks(string $modelClass,
  int $limit)` at `SectionDataComposer.php:312`. Cold-start
  (editor_boost → views → published_at) until 20 completions, then a
  weighted blend. Reuse this pattern for the fallback when the user has
  no personal history.
- `Popular Movies` / `Popular Shows` — `orderByDesc('views_count')`,
  no personalisation.

## Algorithm — Tier 1

Four phases. Everything below runs in PHP in-memory after a small
number of SQL queries; no stored procedures, no external services.

### Phase 1 — User affinity profile

Compute two per-user vectors: **genre affinity** and **cast affinity**.

For each genre **G** linked to any title the user has interacted with:

```
affinity_genre[G] =
      3.0 × (# of titles completed that are tagged G)
    + 2.0 × (sum of stars on ratings for titles tagged G)
    + 1.0 × (# of watchlist items tagged G)
    − 1.0 × (# of titles abandoned in first 20% that are tagged G)
```

For each cast member **P** linked to any title the user has interacted
with (role = actor, director, or writer):

```
affinity_cast[P] =
      2.0 × (# of titles completed with P in cast)
    + 1.5 × (sum of stars on ratings for titles with P)
    + 0.8 × (# of watchlist items with P)
```

Notes on the weights:
- Completions beat ratings beat watchlist adds — watched-to-end is the
  strongest signal because it cost the user the most attention.
- Ratings multiplied by star value so a 5★ review contributes more than
  a 1★ one.
- Abandoned-early penalty is intentionally small so a user who bailed
  on one thriller doesn't get all thrillers scrubbed.

### Phase 2 — Candidate scoring

Fetch all published movies and shows the user hasn't **completed** yet
(they can show in-progress items — those surface via Continue Watching
elsewhere, so down-weight rather than exclude). For each candidate C:

```
candidate_score(C) =
      Σ affinity_genre[G] for G in C.genres
    + Σ affinity_cast[P] for P in C.cast
    + 0.2 × log(C.views_count + 1)      // popularity prior,
                                         //   prevents total obscurity
    + 0.5 × recency_boost(C)             // linear 0→1 over last 12 months
    + 1.0 × (C.editor_boost or 0)        // admin pin still wins
    − 2.0 × (1 if user has in-progress history on C else 0)
```

`recency_boost(C)` = `max(0, 1 − months_since_published / 12)`. Clamp
at 0 so ancient titles don't get negative weight.

### Phase 3 — Ranking and diversity

1. Sort candidates by `candidate_score` DESC.
2. Oversample: take top 24.
3. Apply diversity filter: walk the list top-down, drop any candidate
   whose **primary genre** (first genre on the record) has already
   appeared twice in the kept set.
4. Truncate to 8.

Diversity prevents the common recommender failure where someone who
likes one thriller gets served eight thrillers in a row. Two per
primary genre keeps the shelf interesting without discarding the signal.

### Phase 4 — Cold start

A user qualifies as "cold" when:

```
total_signals = completions + ratings + watchlist_items
total_signals < 3
```

For cold users (and all guests), skip Phases 1-3 entirely and reuse
the existing `topPicks(Movie::class, 8)` logic from the Top 10 rails —
editor_boost → views_count → published_at with weighted blend once the
global completion count crosses the 20 threshold.

The section title stays "Top Picks for You"; the content just reflects
popularity until the user has their own signal.

## Caching strategy

Per-user scoring touches several tables and runs on every page load if
not cached. Cache aggressively with invalidation on writes.

| Key shape | TTL | Invalidate on |
|---|---|---|
| `user:{uid}:top_picks:v1` | 1 hour | WatchHistoryItem / Rating / WatchlistItem create/update/delete for this user |
| `topPicks:guest:v1` | 30 minutes | any content create / delete, or a nightly refresh job |

Bump the `v1` suffix whenever the scoring weights or algorithm change —
that forces every user's cache to recompute the next page load without
having to manually flush Redis.

Model observer glue (pseudocode):
```php
// app/Observers/PersonalisationCacheObserver.php
public function saved($model) {
    if ($model->user_id ?? null) {
        Cache::forget("user:{$model->user_id}:top_picks:v1");
    }
}
// Attach to WatchHistoryItem, Rating, WatchlistItem boot() hooks.
```

## Implementation plan — file-by-file

### 1. New service class

**File:** `Modules/Frontend/app/Services/TopPicksRecommender.php`

Public API:
```php
public function forUser(int $userId, int $limit = 8): \Illuminate\Support\Collection
public function forGuest(int $limit = 8): \Illuminate\Support\Collection
```

Private methods to split the phases:
```php
private function buildGenreAffinity(int $userId): array        // [genreId => score]
private function buildCastAffinity(int $userId): array         // [personId => score]
private function fetchCandidates(int $userId): Collection      // Movies + Shows user hasn't completed
private function scoreCandidate(Model $c, array $genreAff, array $castAff): float
private function applyDiversityFilter(Collection $ranked, int $limit): Collection
private function coldFallback(int $limit): Collection          // delegates to existing topPicks()
```

Should accept an injected `CacheRepository` so unit tests can swap it
for `array` driver.

### 2. Composer integration

**File:** `Modules/Frontend/app/View/Composers/SectionDataComposer.php`
**Line:** ~134

Replace:
```php
'topPicks' => Movie::published()
    ->with('genres')
    ->inRandomOrder()
    ->take(8)
    ->get(),
```

With:
```php
'topPicks' => $this->resolveTopPicks(8),
```

And add the helper method:
```php
private function resolveTopPicks(int $limit): Collection
{
    $recommender = app(\Modules\Frontend\app\Services\TopPicksRecommender::class);
    $uid = auth()->id();
    return $uid
        ? $recommender->forUser($uid, $limit)
        : $recommender->forGuest($limit);
}
```

### 3. Cache invalidation observers

**File:** `Modules/Frontend/app/Observers/PersonalisationCacheObserver.php`

Register in `FrontendServiceProvider::boot()`:
```php
WatchHistoryItem::observe(PersonalisationCacheObserver::class);
Rating::observe(PersonalisationCacheObserver::class);
WatchlistItem::observe(PersonalisationCacheObserver::class);
```

The observer only needs the `saved` and `deleted` hooks; both flush
`user:{$model->user_id}:top_picks:v1`.

### 4. Config — tunable weights

**File:** `Modules/Frontend/config/config.php`

```php
return [
    'recommendations' => [
        'cold_threshold' => env('JAMBO_COLD_THRESHOLD', 3),
        'cache_ttl_user' => env('JAMBO_TOP_PICKS_TTL_USER', 3600),
        'cache_ttl_guest' => env('JAMBO_TOP_PICKS_TTL_GUEST', 1800),
        'oversample_factor' => 3,  // take 24 to produce 8
        'diversity_per_primary_genre' => 2,
        'weights' => [
            'completion_genre' => 3.0,
            'rating_genre_per_star' => 2.0,
            'watchlist_genre' => 1.0,
            'abandoned_genre' => -1.0,
            'completion_cast' => 2.0,
            'rating_cast_per_star' => 1.5,
            'watchlist_cast' => 0.8,
            'popularity_log' => 0.2,
            'recency' => 0.5,
            'editor_boost' => 1.0,
            'in_progress_penalty' => -2.0,
        ],
    ],
];
```

Lets you tune without redeploying code — set an override in `.env` or
the DB-backed `settings` table if you want it admin-controllable.

### 5. Blade — no changes needed

`top-pict.blade.php` already reads `$topPicks` unchanged. The service
returns an `Illuminate\Support\Collection` that quacks like the
`EloquentCollection` the blade expects.

Make sure the service eager-loads `genres` on each item so the blade's
card partial (which reads `$item->genres`) doesn't trigger N+1.

### 6. Tests

**File:** `Modules/Frontend/tests/Feature/TopPicksRecommenderTest.php`

Minimum cases:
- Cold user (no signals) → returns popularity fallback, same as guest.
- Power user (history across 5 thrillers, 2 dramas) → top result is a
  thriller; drama scores are ranked below.
- Diversity filter — seed 10 thrillers all at the top of the score
  table → output has at most 2 thrillers in the top 8.
- Cache hit — second call within TTL returns identical collection
  without running the SQL queries (spy on DB::connection()->getQueryLog()).
- Cache invalidation — create a WatchHistoryItem → observer clears
  the key → next call re-queries.
- In-progress penalty — candidate user paused halfway through a movie
  ranks lower than an equivalent untouched candidate.

### 7. Docs update

After landing, append a short paragraph to this file's header
("**Status:** implemented") plus a link from `docs/modules.md` or a new
`docs/recommendations.md` index pointing at this file.

## Edge cases & gotchas

- **Empty affinity vectors.** A user with exactly one completion has a
  very sparse vector. The popularity prior (`0.2 × log(views + 1)`)
  keeps them from seeing nothing at all.
- **User completes everything in a genre.** Candidate list for that
  genre runs out. Oversampling to 24 + cross-genre scoring means the
  shelf still fills with the next-best non-completed titles.
- **Editor-boosted low-affinity title.** Editor boost is additive, not
  multiplicative, so it lifts the title but doesn't crush the user's
  actual preferences. A pinned movie with zero genre overlap scores
  `1.0 + 0.2 log(views) + recency_boost` — competitive with a middle-
  pack personal match, which is the intended behaviour for a pin.
- **Series vs movies mix.** Score movies and shows with the same
  formula; return them in one collection. The section card supports
  both. If you want movies-only, filter in `fetchCandidates()`.
- **Scaling.** At ~5k published titles and ~10 signals per user this
  runs well under 100ms uncached. Beyond 100k titles or real
  concurrency, push to a materialised view or precompute nightly.
- **Privacy.** Scoring reads only the current user's rows from the
  signal tables. There's no per-user leakage via the cache because the
  cache key includes the user id.
- **GDPR / right to delete.** When a user deletes their account, their
  rows in watch_history / ratings / watchlist_items are removed by
  cascade. Cache key naturally expires on TTL; no explicit flush needed.

## Rollout

1. Implement behind a config flag
   `config('frontend.recommendations.enabled', true)` — one line in the
   composer guards the call; if false, falls back to the current random
   draw. Lets you toggle live in case the algorithm ships with a bug.
2. Deploy. Observe logs for unexpected exceptions.
3. Monitor two numbers for a week:
   - Average time on the home page
   - Click-through rate on the Top Picks shelf
4. If click-through rises and nothing breaks, remove the fallback and
   the flag.
5. If results feel off for specific users, first tune weights in the
   config without touching code. Only rewrite the algorithm if weight
   tuning doesn't help.

## Tier 2 upgrades (not in scope here)

- Collaborative filtering — "people who finished X also finished Y".
  Needs a nightly item-item co-occurrence matrix.
- Session context — time of day, device class, last-watched genre.
- Exploration mix — 10-20% of the shelf drawn outside the user's top
  affinity to prevent filter bubble.
- Negative implicit signals — skipping recommended titles repeatedly
  should down-weight their genre/cast for that user.
- A/B framework — serve 10% of users the random fallback, compare KPIs
  to the algorithmic group, iterate weights from real data.

## Quick prompts for the implementing chat

Drop these into a fresh Claude chat when you're ready to build:

- "Read `docs/plans/top-picks-personalization.md` and implement the
  tier-1 algorithm exactly as specified."
- "Run tests after implementation and show me the pass/fail output."
- "Don't touch the blade — the section template is already correct."
- "Use the existing `SectionDataComposer` caching shape for the cache
  keys — per-request static cache inside the composer, Laravel
  Cache::remember on top for cross-request persistence."

## Definition of done

- [ ] `TopPicksRecommender` service implemented per the phases above
- [ ] Composer updated to call it, with cold-start + guest fallback
- [ ] Observers registered to invalidate the per-user cache on write
- [ ] Config file with tunable weights
- [ ] Feature tests covering cold user, power user, diversity,
  cache hit, cache invalidation, in-progress penalty
- [ ] No N+1 on the home page (`laravel-debugbar` or equivalent clean)
- [ ] No blade template touched
- [ ] Existing `topPicks(Movie::class, 8)` cold-start path reused (no
  duplication)
- [ ] This doc updated with status: implemented + date
