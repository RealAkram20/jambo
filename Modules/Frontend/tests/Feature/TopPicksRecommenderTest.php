<?php

namespace Modules\Frontend\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Rating;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Frontend\app\Services\TopPicksRecommender;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Streaming\app\Models\WatchlistItem;
use Tests\TestCase;

/**
 * Tests the personalised Top Picks recommender from the Frontend module.
 * Kept in the root tests tree so the stock phpunit testsuite picks them
 * up without extra config; the service under test lives at
 * Modules/Frontend/app/Services/TopPicksRecommender.php.
 */
class TopPicksRecommenderTest extends TestCase
{
    use RefreshDatabase;

    private TopPicksRecommender $recommender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recommender = app(TopPicksRecommender::class);
    }

    public function test_cold_user_falls_back_to_popularity(): void
    {
        $user = User::factory()->create();

        // Seed 10 published movies with varied view counts so the cold
        // fallback's `orderByDesc(views_count)` has something to rank.
        $movies = collect(range(1, 10))->map(fn ($i) => Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDays($i),
            'views_count' => $i * 1000,
            'editor_boost' => 0,
        ]));

        $userPicks = $this->recommender->forUser($user->id, 8);
        $guestPicks = $this->recommender->forGuest(8);

        // Cold user should get the same ordering as a guest — both
        // paths delegate to globalTopPicks() when signals < threshold.
        $this->assertSame(
            $userPicks->pluck('id')->all(),
            $guestPicks->pluck('id')->all(),
            'Cold user and guest should receive the same popularity-based shelf.',
        );

        // Highest-view movie should lead the cold fallback.
        $this->assertSame($movies->sortByDesc('views_count')->first()->id, $userPicks->first()->id);
    }

    public function test_power_user_gets_affinity_ranked_results(): void
    {
        $user = User::factory()->create();

        /** @var Genre $thriller */
        $thriller = Genre::factory()->create(['name' => 'Thriller']);
        /** @var Genre $drama */
        $drama = Genre::factory()->create(['name' => 'Drama']);

        // 5 thrillers the user completed + rated → strong thriller affinity.
        $historyThrillers = collect(range(1, 5))->map(function () use ($thriller, $user) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(100)]);
            $m->genres()->attach($thriller->id);
            WatchHistoryItem::create([
                'user_id' => $user->id,
                'watchable_type' => $m->getMorphClass(),
                'watchable_id' => $m->id,
                'position_seconds' => 7200,
                'duration_seconds' => 7200,
                'completed' => true,
                'watched_at' => now()->subDays(10),
            ]);
            Rating::create([
                'user_id' => $user->id,
                'ratable_type' => $m->getMorphClass(),
                'ratable_id' => $m->id,
                'stars' => 5,
            ]);
            return $m;
        });

        // 2 dramas the user merely added to watchlist → weak drama affinity.
        collect(range(1, 2))->each(function () use ($drama, $user) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(100)]);
            $m->genres()->attach($drama->id);
            WatchlistItem::create([
                'user_id' => $user->id,
                'watchable_type' => $m->getMorphClass(),
                'watchable_id' => $m->id,
                'added_at' => now(),
            ]);
        });

        // Candidate pool: 3 thriller candidates + 3 drama candidates, none completed.
        $thrillerCandidates = collect(range(1, 3))->map(function ($i) use ($thriller) {
            $m = Movie::factory()->create([
                'status' => Movie::STATUS_PUBLISHED,
                'published_at' => now()->subDays(30),
                'views_count' => 100,
            ]);
            $m->genres()->attach($thriller->id);
            return $m;
        });
        $dramaCandidates = collect(range(1, 3))->map(function ($i) use ($drama) {
            $m = Movie::factory()->create([
                'status' => Movie::STATUS_PUBLISHED,
                'published_at' => now()->subDays(30),
                'views_count' => 100,
            ]);
            $m->genres()->attach($drama->id);
            return $m;
        });

        $picks = $this->recommender->forUser($user->id, 6);
        $topPickGenreId = $picks->first()->genres->first()->id;

        $this->assertSame($thriller->id, $topPickGenreId, 'Top result should be a thriller for a thriller-heavy user.');

        // Every thriller candidate should rank above at least one drama candidate.
        $thrillerPositions = $picks->filter(fn ($m) => $thrillerCandidates->contains('id', $m->id))
            ->keys()->all();
        $dramaPositions = $picks->filter(fn ($m) => $dramaCandidates->contains('id', $m->id))
            ->keys()->all();

        if (!empty($thrillerPositions) && !empty($dramaPositions)) {
            $this->assertLessThan(
                max($dramaPositions),
                min($thrillerPositions),
                'The best thriller candidate should outrank at least one drama.',
            );
        }
    }

    public function test_diversity_filter_caps_primary_genre_at_two(): void
    {
        $user = User::factory()->create();

        /** @var Genre $thriller */
        $thriller = Genre::factory()->create(['name' => 'Thriller']);
        /** @var Genre $drama */
        $drama = Genre::factory()->create(['name' => 'Drama']);

        // Strong thriller signal so thriller candidates all top-score.
        collect(range(1, 5))->each(function () use ($thriller, $user) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(200)]);
            $m->genres()->attach($thriller->id);
            WatchHistoryItem::create([
                'user_id' => $user->id,
                'watchable_type' => $m->getMorphClass(),
                'watchable_id' => $m->id,
                'position_seconds' => 7200,
                'duration_seconds' => 7200,
                'completed' => true,
                'watched_at' => now()->subDays(20),
            ]);
        });

        // 10 thriller candidates, 3 drama candidates — all uncompleted.
        collect(range(1, 10))->each(function () use ($thriller) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(15)]);
            $m->genres()->attach($thriller->id);
        });
        collect(range(1, 3))->each(function () use ($drama) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(15)]);
            $m->genres()->attach($drama->id);
        });

        $picks = $this->recommender->forUser($user->id, 8);

        $thrillerInPicks = $picks->filter(fn ($m) => $m->genres->first()?->id === $thriller->id)->count();
        $this->assertLessThanOrEqual(
            2,
            $thrillerInPicks,
            'Diversity filter should cap thrillers at 2 in the final 8.',
        );
    }

    public function test_cache_hit_skips_the_computation(): void
    {
        $user = User::factory()->create();
        Movie::factory()->count(5)->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDays(5),
        ]);

        $this->recommender->forUser($user->id, 8); // warms cache

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->recommender->forUser($user->id, 8);

        $this->assertCount(
            0,
            DB::getQueryLog(),
            'Second call within TTL should hit cache and issue zero queries.',
        );
    }

    public function test_signal_write_invalidates_cache(): void
    {
        $user = User::factory()->create();
        Movie::factory()->count(3)->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDays(3),
        ]);

        $this->recommender->forUser($user->id, 8); // warms cache

        $key = TopPicksRecommender::CACHE_KEY_USER_PREFIX . $user->id . TopPicksRecommender::CACHE_KEY_USER_SUFFIX;
        $this->assertTrue(Cache::has($key), 'Cache entry should exist after first call.');

        $movie = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()]);
        WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'position_seconds' => 100,
            'duration_seconds' => 7200,
            'completed' => true,
            'watched_at' => now(),
        ]);

        $this->assertFalse(
            Cache::has($key),
            'Observer should flush the user\'s Top Picks cache on signal write.',
        );
    }

    public function test_upcoming_listing_merges_movies_and_shows_sorted_by_release_date(): void
    {
        $soonestMovie = Movie::factory()->create(['status' => Movie::STATUS_UPCOMING, 'published_at' => now()->addDays(3)]);
        $laterShow = Show::factory()->create(['status' => Show::STATUS_UPCOMING, 'published_at' => now()->addDays(10)]);
        $soonestShow = Show::factory()->create(['status' => Show::STATUS_UPCOMING, 'published_at' => now()->addDays(5)]);
        $undatedMovie = Movie::factory()->create(['status' => Movie::STATUS_UPCOMING, 'published_at' => null]);
        // Published titles must not appear in the listing.
        Movie::factory()->count(2)->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDay()]);

        $listing = app(TopPicksRecommender::class)->upcomingListing(0, 10);
        $items = $listing['items'];
        $ids = $items->map(fn ($i) => $i->_kind . ':' . $i->id)->all();

        $this->assertSame(4, $listing['total'], 'Listing total must exclude published titles.');
        $this->assertFalse($listing['hasMore'], 'Four items with limit 10 leaves nothing more to load.');

        // Order: soonest movie (D+3) → soonest show (D+5) → later show (D+10) → undated movie.
        $this->assertSame('movie:' . $soonestMovie->id, $ids[0]);
        $this->assertSame('show:' . $soonestShow->id, $ids[1]);
        $this->assertSame('show:' . $laterShow->id, $ids[2]);
        $this->assertSame('movie:' . $undatedMovie->id, $ids[3]);
    }

    public function test_upcoming_listing_paginates_via_offset(): void
    {
        // 5 upcoming movies with staggered release dates.
        $movies = collect(range(1, 5))->map(fn ($i) => Movie::factory()->create([
            'status' => Movie::STATUS_UPCOMING,
            'published_at' => now()->addDays($i),
        ]));

        $firstPage = app(TopPicksRecommender::class)->upcomingListing(0, 2);
        $secondPage = app(TopPicksRecommender::class)->upcomingListing(2, 2);
        $thirdPage = app(TopPicksRecommender::class)->upcomingListing(4, 2);

        $this->assertSame(2, $firstPage['items']->count());
        $this->assertTrue($firstPage['hasMore']);

        $this->assertSame(2, $secondPage['items']->count());
        $this->assertTrue($secondPage['hasMore']);

        // Third page has only one item, and nothing more is available.
        $this->assertSame(1, $thirdPage['items']->count());
        $this->assertFalse($thirdPage['hasMore']);

        // No duplicates across pages.
        $firstIds = $firstPage['items']->pluck('id')->all();
        $secondIds = $secondPage['items']->pluck('id')->all();
        $this->assertEmpty(array_intersect($firstIds, $secondIds), 'Pages should not overlap.');
    }

    public function test_upcoming_listing_is_empty_when_nothing_announced(): void
    {
        Movie::factory()->count(3)->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDay()]);
        Movie::factory()->count(2)->create(['status' => Movie::STATUS_DRAFT, 'published_at' => null]);

        $listing = app(TopPicksRecommender::class)->upcomingListing(0, 10);

        $this->assertSame(0, $listing['total']);
        $this->assertTrue($listing['items']->isEmpty());
        $this->assertFalse($listing['hasMore']);
    }

    public function test_upcoming_returns_only_upcoming_status(): void
    {
        $upcoming = Movie::factory()->create(['status' => Movie::STATUS_UPCOMING, 'published_at' => now()->addDays(10)]);
        $draft = Movie::factory()->create(['status' => Movie::STATUS_DRAFT, 'published_at' => null]);
        $published = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDay()]);

        $shelf = app(TopPicksRecommender::class)->upcoming(null, 10);

        $ids = $shelf->pluck('id')->all();
        $this->assertContains($upcoming->id, $ids);
        $this->assertNotContains($draft->id, $ids, 'Draft titles must not appear in Upcoming.');
        $this->assertNotContains($published->id, $ids, 'Published titles must not appear in Upcoming.');
    }

    public function test_upcoming_guest_orders_by_soonest_release_then_undated_last(): void
    {
        $soonest = Movie::factory()->create(['status' => Movie::STATUS_UPCOMING, 'published_at' => now()->addDays(3)]);
        $later = Movie::factory()->create(['status' => Movie::STATUS_UPCOMING, 'published_at' => now()->addDays(30)]);
        $undated = Movie::factory()->create(['status' => Movie::STATUS_UPCOMING, 'published_at' => null]);

        $shelf = app(TopPicksRecommender::class)->upcoming(null, 10);
        $ids = $shelf->pluck('id')->all();

        $this->assertSame($soonest->id, $shelf->first()->id, 'Soonest release should lead.');
        $this->assertLessThan(
            array_search($later->id, $ids, true),
            array_search($soonest->id, $ids, true),
        );
        // Undated items always come after dated ones.
        $this->assertGreaterThan(
            array_search($later->id, $ids, true),
            array_search($undated->id, $ids, true),
        );
    }

    public function test_upcoming_warm_user_ranks_affinity_matches_first(): void
    {
        $user = User::factory()->create();
        $thriller = Genre::factory()->create(['name' => 'Thriller']);
        $drama = Genre::factory()->create(['name' => 'Drama']);

        // Strong thriller affinity.
        collect(range(1, 3))->each(function () use ($thriller, $user) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(100)]);
            $m->genres()->attach($thriller->id);
            WatchHistoryItem::create([
                'user_id' => $user->id,
                'watchable_type' => $m->getMorphClass(),
                'watchable_id' => $m->id,
                'position_seconds' => 7200,
                'duration_seconds' => 7200,
                'completed' => true,
                'watched_at' => now()->subDays(15),
            ]);
            Rating::create([
                'user_id' => $user->id,
                'ratable_type' => $m->getMorphClass(),
                'ratable_id' => $m->id,
                'stars' => 5,
            ]);
        });

        // Upcoming pool: thriller announcement + drama announcement.
        // Drama has the earlier release date (would lead for a guest),
        // but thriller affinity should push the thriller to the front.
        $upcomingDrama = Movie::factory()->create(['status' => Movie::STATUS_UPCOMING, 'published_at' => now()->addDays(5)]);
        $upcomingDrama->genres()->attach($drama->id);

        $upcomingThriller = Movie::factory()->create(['status' => Movie::STATUS_UPCOMING, 'published_at' => now()->addDays(30)]);
        $upcomingThriller->genres()->attach($thriller->id);

        $shelf = app(TopPicksRecommender::class)->upcoming($user->id, 10);

        $this->assertSame(
            $upcomingThriller->id,
            $shelf->first()->id,
            'Thriller-loving user should see the upcoming thriller before the upcoming drama.',
        );
    }

    public function test_upcoming_cache_invalidated_on_signal_write(): void
    {
        $user = User::factory()->create();
        Movie::factory()->create(['status' => Movie::STATUS_UPCOMING, 'published_at' => now()->addDays(5)]);

        app(TopPicksRecommender::class)->upcoming($user->id, 10); // warms cache

        $key = TopPicksRecommender::CACHE_KEY_USER_PREFIX
            . $user->id
            . TopPicksRecommender::CACHE_KEY_UPCOMING_USER_SUFFIX;
        $this->assertTrue(Cache::has($key), 'Upcoming cache entry should exist after first call.');

        $movie = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()]);
        Rating::create([
            'user_id' => $user->id,
            'ratable_type' => $movie->getMorphClass(),
            'ratable_id' => $movie->id,
            'stars' => 4,
        ]);

        $this->assertFalse(
            Cache::has($key),
            'Observer should flush the Upcoming cache on any signal-table write.',
        );
    }

    public function test_fresh_picks_prefers_recent_affinity_matches_for_warm_users(): void
    {
        $user = User::factory()->create();
        $thriller = Genre::factory()->create(['name' => 'Thriller']);

        // Strong thriller affinity — 3 old completions + 3 star-5 ratings.
        collect(range(1, 3))->each(function () use ($thriller, $user) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(200)]);
            $m->genres()->attach($thriller->id);
            WatchHistoryItem::create([
                'user_id' => $user->id,
                'watchable_type' => $m->getMorphClass(),
                'watchable_id' => $m->id,
                'position_seconds' => 7200,
                'duration_seconds' => 7200,
                'completed' => true,
                'watched_at' => now()->subDays(15),
            ]);
            Rating::create([
                'user_id' => $user->id,
                'ratable_type' => $m->getMorphClass(),
                'ratable_id' => $m->id,
                'stars' => 5,
            ]);
        });

        // 10 fresh movies with distinct genres so diversity cap doesn't
        // starve the shelf; one of them is the affinity match.
        $freshThriller = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(1)]);
        $freshThriller->genres()->attach($thriller->id);

        for ($i = 0; $i < 9; $i++) {
            $g = Genre::factory()->create();
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(2 + $i)]);
            $m->genres()->attach($g->id);
        }

        // Old thriller outside the recency window — should not appear
        // because the fresh pool is already full at limit.
        $oldThriller = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(200)]);
        $oldThriller->genres()->attach($thriller->id);

        $picks = app(TopPicksRecommender::class)->freshPicks($user->id, 10);

        $this->assertTrue(
            $picks->pluck('id')->contains($freshThriller->id),
            'Fresh affinity match should appear in the shelf.',
        );
        $this->assertSame(
            $freshThriller->id,
            $picks->first()->id,
            'Fresh affinity match should lead — affinity wins within the fresh pool.',
        );
        $this->assertFalse(
            $picks->pluck('id')->contains($oldThriller->id),
            'Titles outside the recency window should not appear when the fresh pool is full.',
        );
    }

    public function test_fresh_picks_returns_newest_first_for_guest(): void
    {
        // Three freshly-published movies — newest should lead.
        $oldest = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(30)]);
        $mid = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(10)]);
        $newest = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(1)]);

        $picks = app(TopPicksRecommender::class)->freshPicks(null, 10);

        $this->assertSame($newest->id, $picks->first()->id, 'Guest fresh picks should lead with the newest publication.');
        $this->assertGreaterThanOrEqual(3, $picks->count());

        // Order preserved.
        $ids = $picks->pluck('id')->all();
        $this->assertLessThan(array_search($mid->id, $ids, true), array_search($newest->id, $ids, true));
        $this->assertLessThan(array_search($oldest->id, $ids, true), array_search($mid->id, $ids, true));
    }

    public function test_fresh_picks_backfills_when_recency_window_is_thin(): void
    {
        // One fresh movie, nine older-than-60-day movies. Shelf should
        // still fill because backfill pulls in the next-newest titles.
        Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(5)]);
        Movie::factory()->count(9)->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subYear()]);

        $picks = app(TopPicksRecommender::class)->freshPicks(null, 10);

        $this->assertSame(10, $picks->count(), 'Backfill must fill the shelf even when the recency window is thin.');
    }

    public function test_fresh_picks_excludes_completed_titles(): void
    {
        $user = User::factory()->create();
        $completed = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(3)]);
        Movie::factory()->count(5)->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(3)]);

        // Enough signals to make user warm, and mark $completed as watched-to-end.
        WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $completed->getMorphClass(),
            'watchable_id' => $completed->id,
            'position_seconds' => 7200,
            'duration_seconds' => 7200,
            'completed' => true,
            'watched_at' => now()->subDay(),
        ]);
        Rating::create([
            'user_id' => $user->id,
            'ratable_type' => $completed->getMorphClass(),
            'ratable_id' => $completed->id,
            'stars' => 5,
        ]);
        // A third signal to cross the cold-start threshold.
        $filler = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(100)]);
        \Modules\Streaming\app\Models\WatchlistItem::create([
            'user_id' => $user->id,
            'watchable_type' => $filler->getMorphClass(),
            'watchable_id' => $filler->id,
            'added_at' => now(),
        ]);

        $picks = app(TopPicksRecommender::class)->freshPicks($user->id, 10);

        $this->assertFalse(
            $picks->pluck('id')->contains($completed->id),
            'Fresh Picks should exclude titles the warm user already completed.',
        );
    }

    public function test_fresh_picks_cache_invalidated_on_signal_write(): void
    {
        $user = User::factory()->create();
        Movie::factory()->count(5)->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(3)]);

        app(TopPicksRecommender::class)->freshPicks($user->id, 10); // warms cache

        $key = TopPicksRecommender::CACHE_KEY_USER_PREFIX
            . $user->id
            . TopPicksRecommender::CACHE_KEY_FRESH_PICKS_USER_SUFFIX;
        $this->assertTrue(Cache::has($key), 'Fresh Picks cache entry should exist after first call.');

        $movie = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()]);
        Rating::create([
            'user_id' => $user->id,
            'ratable_type' => $movie->getMorphClass(),
            'ratable_id' => $movie->id,
            'stars' => 4,
        ]);

        $this->assertFalse(
            Cache::has($key),
            'Observer should flush the Fresh Picks cache on any signal-table write.',
        );
    }

    public function test_smart_shuffle_excludes_completed_titles(): void
    {
        $user = User::factory()->create();
        $thriller = Genre::factory()->create(['name' => 'Thriller']);

        // Build a thriller affinity AND mark some thrillers as completed.
        $completed = collect(range(1, 3))->map(function () use ($thriller, $user) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(100)]);
            $m->genres()->attach($thriller->id);
            Rating::create([
                'user_id' => $user->id,
                'ratable_type' => $m->getMorphClass(),
                'ratable_id' => $m->id,
                'stars' => 5,
            ]);
            WatchHistoryItem::create([
                'user_id' => $user->id,
                'watchable_type' => $m->getMorphClass(),
                'watchable_id' => $m->id,
                'position_seconds' => 7200,
                'duration_seconds' => 7200,
                'completed' => true,
                'watched_at' => now()->subDays(5),
            ]);
            return $m;
        });

        // Candidate pool: 5 fresh thrillers the user has NOT completed.
        collect(range(1, 5))->each(function () use ($thriller) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(20)]);
            $m->genres()->attach($thriller->id);
        });

        $shelf = app(TopPicksRecommender::class)->smartShuffle($user->id, 10);
        $completedIds = $completed->pluck('id')->all();

        $this->assertTrue(
            $shelf->pluck('id')->intersect($completedIds)->isEmpty(),
            'Smart Shuffle must not resurface titles the user has already completed.',
        );
    }

    public function test_smart_shuffle_mixes_affinity_and_discovery(): void
    {
        $user = User::factory()->create();
        $thriller = Genre::factory()->create(['name' => 'Thriller']);
        $comedy = Genre::factory()->create(['name' => 'Comedy']);
        $drama = Genre::factory()->create(['name' => 'Drama']);

        // Strong thriller affinity — 3 completions + 3 star-5 ratings.
        collect(range(1, 3))->each(function () use ($thriller, $user) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(200)]);
            $m->genres()->attach($thriller->id);
            WatchHistoryItem::create([
                'user_id' => $user->id,
                'watchable_type' => $m->getMorphClass(),
                'watchable_id' => $m->id,
                'position_seconds' => 7200,
                'duration_seconds' => 7200,
                'completed' => true,
                'watched_at' => now()->subDays(10),
            ]);
            Rating::create([
                'user_id' => $user->id,
                'ratable_type' => $m->getMorphClass(),
                'ratable_id' => $m->id,
                'stars' => 5,
            ]);
        });

        // Candidate pool: thrillers (affinity), comedies + dramas (discovery).
        collect(range(1, 6))->each(function () use ($thriller) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(30)]);
            $m->genres()->attach($thriller->id);
        });
        collect(range(1, 6))->each(function () use ($comedy) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(30)]);
            $m->genres()->attach($comedy->id);
        });
        collect(range(1, 6))->each(function () use ($drama) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(30)]);
            $m->genres()->attach($drama->id);
        });

        $shelf = app(TopPicksRecommender::class)->smartShuffle($user->id, 10);

        $affinityMatches = $shelf->filter(fn ($m) => $m->genres->pluck('id')->contains($thriller->id))->count();
        $discoveryMatches = $shelf->filter(
            fn ($m) => !$m->genres->pluck('id')->contains($thriller->id)
        )->count();

        $this->assertGreaterThan(0, $affinityMatches, 'Shelf should contain at least one affinity (thriller) pick.');
        $this->assertGreaterThan(0, $discoveryMatches, 'Shelf should contain at least one discovery (non-thriller) pick.');
    }

    public function test_smart_shuffle_returns_items_for_guest(): void
    {
        Movie::factory()->count(12)->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDays(10),
        ]);

        $shelf = app(TopPicksRecommender::class)->smartShuffle(null, 10);

        $this->assertGreaterThan(0, $shelf->count(), 'Guest Smart Shuffle should produce a non-empty shelf.');
        $this->assertLessThanOrEqual(10, $shelf->count());
    }

    public function test_smart_shuffle_cache_invalidated_on_signal_write(): void
    {
        $user = User::factory()->create();
        Movie::factory()->count(5)->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDays(5),
        ]);

        app(TopPicksRecommender::class)->smartShuffle($user->id, 10); // warms cache

        $key = TopPicksRecommender::CACHE_KEY_USER_PREFIX
            . $user->id
            . TopPicksRecommender::CACHE_KEY_SMART_SHUFFLE_USER_SUFFIX;
        $this->assertTrue(Cache::has($key), 'Smart Shuffle cache entry should exist after first call.');

        $movie = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()]);
        WatchlistItem::create([
            'user_id' => $user->id,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'added_at' => now(),
        ]);

        $this->assertFalse(
            Cache::has($key),
            'Observer should flush the Smart Shuffle cache on any signal-table write.',
        );
    }

    public function test_top_series_of_the_day_ranks_by_recent_viewers(): void
    {
        // Three shows, each with one episode. $hot gets 3 distinct
        // viewers in the last 24h; $warm gets 1; $cold gets none.
        $hot = $this->makeShowWithEpisode(['title' => 'Hot', 'views_count' => 100]);
        $warm = $this->makeShowWithEpisode(['title' => 'Warm', 'views_count' => 5000]); // higher all-time
        $cold = $this->makeShowWithEpisode(['title' => 'Cold', 'views_count' => 9999]); // highest all-time

        $this->recordEpisodeWatches($hot['episode'], 3, watchedAt: now()->subHours(2));
        $this->recordEpisodeWatches($warm['episode'], 1, watchedAt: now()->subHours(3));
        // $cold: no recent activity.

        $top = app(TopPicksRecommender::class)->topSeriesOfTheDay(10);

        $this->assertGreaterThanOrEqual(3, $top->count());
        // Daily signal beats all-time popularity at the head.
        $this->assertSame($hot['show']->id, $top->first()->id, 'Show with most daily viewers should lead.');
        $this->assertSame($warm['show']->id, $top->get(1)->id, 'Show with fewer daily viewers should come second.');
        // Cold show still appears (padded from all-time popularity).
        $this->assertTrue($top->pluck('id')->contains($cold['show']->id), 'Cold show should be padded into the shelf.');
    }

    public function test_top_series_of_the_day_falls_back_when_no_daily_activity(): void
    {
        // No watch history at all — signal is zero for every show.
        $a = $this->makeShowWithEpisode(['title' => 'Alpha', 'views_count' => 100]);
        $b = $this->makeShowWithEpisode(['title' => 'Beta', 'views_count' => 9999]);

        $top = app(TopPicksRecommender::class)->topSeriesOfTheDay(10);

        // Without any daily signal, ordering is all-time popularity.
        $this->assertSame($b['show']->id, $top->first()->id, 'Falls back to views_count when no daily activity exists.');
    }

    public function test_top_series_of_the_day_is_cached_within_the_day(): void
    {
        $show = $this->makeShowWithEpisode();
        $this->recordEpisodeWatches($show['episode'], 2, watchedAt: now()->subHour());

        $recommender = app(TopPicksRecommender::class);
        $recommender->topSeriesOfTheDay(10); // warms cache

        DB::enableQueryLog();
        DB::flushQueryLog();
        $recommender->topSeriesOfTheDay(10);

        $this->assertCount(
            0,
            DB::getQueryLog(),
            'Second call within the same day should hit cache and issue zero queries.',
        );
    }

    public function test_in_progress_candidate_ranks_below_equivalent_untouched(): void
    {
        $user = User::factory()->create();

        /** @var Genre $action */
        $action = Genre::factory()->create(['name' => 'Action']);

        // Strong action affinity.
        collect(range(1, 4))->each(function () use ($action, $user) {
            $m = Movie::factory()->create(['status' => Movie::STATUS_PUBLISHED, 'published_at' => now()->subDays(100)]);
            $m->genres()->attach($action->id);
            WatchHistoryItem::create([
                'user_id' => $user->id,
                'watchable_type' => $m->getMorphClass(),
                'watchable_id' => $m->id,
                'position_seconds' => 7200,
                'duration_seconds' => 7200,
                'completed' => true,
                'watched_at' => now()->subDays(10),
            ]);
        });

        // Two equivalent action candidates.
        $inProgress = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDays(30),
            'views_count' => 500,
        ]);
        $inProgress->genres()->attach($action->id);

        $untouched = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDays(30),
            'views_count' => 500,
        ]);
        $untouched->genres()->attach($action->id);

        // Mark $inProgress as paused halfway through.
        WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $inProgress->getMorphClass(),
            'watchable_id' => $inProgress->id,
            'position_seconds' => 3600,
            'duration_seconds' => 7200,
            'completed' => false,
            'watched_at' => now()->subHours(2),
        ]);

        $picks = $this->recommender->forUser($user->id, 8);

        $inProgressPos = $picks->search(fn ($m) => $m->id === $inProgress->id);
        $untouchedPos = $picks->search(fn ($m) => $m->id === $untouched->id);

        $this->assertNotFalse($inProgressPos, 'In-progress candidate should still appear in the shelf.');
        $this->assertNotFalse($untouchedPos, 'Untouched candidate should appear in the shelf.');
        $this->assertGreaterThan(
            $untouchedPos,
            $inProgressPos,
            'In-progress candidate should rank below an otherwise-equivalent untouched one.',
        );
    }

    /**
     * @return array{show: Show, season: Season, episode: Episode}
     */
    private function makeShowWithEpisode(array $showAttrs = []): array
    {
        $show = Show::factory()->create(array_merge([
            'status' => Show::STATUS_PUBLISHED,
            'published_at' => now()->subDays(10),
        ], $showAttrs));
        $season = Season::factory()->create(['show_id' => $show->id]);
        $episode = Episode::factory()->create(['season_id' => $season->id]);

        return ['show' => $show, 'season' => $season, 'episode' => $episode];
    }

    /**
     * Drop $count distinct-user WatchHistoryItem rows on the given
     * episode, timestamped in the 24h window so the daily recommender
     * counts them as recent viewers.
     */
    private function recordEpisodeWatches(Episode $episode, int $count, \DateTimeInterface $watchedAt): void
    {
        for ($i = 0; $i < $count; $i++) {
            $viewer = User::factory()->create();
            WatchHistoryItem::create([
                'user_id' => $viewer->id,
                'watchable_type' => $episode->getMorphClass(),
                'watchable_id' => $episode->id,
                'position_seconds' => 600,
                'duration_seconds' => 2400,
                'completed' => false,
                'watched_at' => $watchedAt,
            ]);
        }
    }
}
