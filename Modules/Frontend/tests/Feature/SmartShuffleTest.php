<?php

namespace Modules\Frontend\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Rating;
use Modules\Frontend\app\Services\TopPicksRecommender;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Tests\TestCase;

/**
 * AI Smart Shuffle — the behaviours added when the rail was upgraded from
 * "random sample of two pools" to a scored, collaboratively-filtered,
 * self-explaining shelf.
 *
 * The four original smart_shuffle contracts (excludes completed, mixes
 * affinity + discovery, non-empty for guests, cache invalidated on signal
 * write) stay in TopPicksRecommenderTest. This file covers what's new.
 */
class SmartShuffleTest extends TestCase
{
    use RefreshDatabase;

    private TopPicksRecommender $recommender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recommender = app(TopPicksRecommender::class);
    }

    /**
     * Make $user "warm" (past the cold_threshold) with a thriller taste,
     * built from completions + 5-star ratings on throwaway titles.
     *
     * @return array{0: User, 1: Genre}
     */
    private function warmThrillerFan(int $signals = 3): array
    {
        $user = User::factory()->create();
        $thriller = Genre::factory()->create(['name' => 'Thriller']);

        collect(range(1, $signals))->each(function () use ($thriller, $user) {
            $m = Movie::factory()->create([
                'status' => Movie::STATUS_PUBLISHED,
                'published_at' => now()->subDays(200),
            ]);
            $m->genres()->attach($thriller->id);
            $this->complete($user, $m);
            Rating::create([
                'user_id' => $user->id,
                'ratable_type' => $m->getMorphClass(),
                'ratable_id' => $m->id,
                'stars' => 5,
            ]);
        });

        return [$user, $thriller];
    }

    private function complete(User $user, Movie $movie): WatchHistoryItem
    {
        return WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'position_seconds' => 7200,
            'duration_seconds' => 7200,
            'completed' => true,
            'watched_at' => now()->subDay(),
        ]);
    }

    private function publishedMovie(?Genre $genre = null, int $daysOld = 30): Movie
    {
        $m = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDays($daysOld),
        ]);

        if ($genre) {
            $m->genres()->attach($genre->id);
        }

        return $m;
    }

    // -- Tier 1: correctness gaps the old algorithm had --------------------

    public function test_in_progress_titles_are_excluded(): void
    {
        [$user, $thriller] = $this->warmThrillerFan();

        // Half-watched: this already owns a slot in Continue Watching, so
        // Smart Shuffle showing it too would put the same poster twice on
        // one screen. The old algorithm only excluded *completed* titles.
        $inProgress = $this->publishedMovie($thriller);
        WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $inProgress->getMorphClass(),
            'watchable_id' => $inProgress->id,
            'position_seconds' => 1200,
            'duration_seconds' => 7200,
            'completed' => false,
            'watched_at' => now()->subHours(2),
        ]);

        collect(range(1, 8))->each(fn () => $this->publishedMovie($thriller));

        $shelf = $this->recommender->smartShuffle($user->id, 10);

        $this->assertFalse(
            $shelf->pluck('id')->contains($inProgress->id),
            'A title the user is midway through must not reappear in Smart Shuffle.',
        );
    }

    public function test_abandoned_titles_are_excluded(): void
    {
        [$user, $thriller] = $this->warmThrillerFan();

        // Bailed 4 minutes into a 2-hour movie. Under the old algorithm this
        // stayed eligible forever, so we could keep recommending the exact
        // thing they walked out of.
        $abandoned = $this->publishedMovie($thriller);
        WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $abandoned->getMorphClass(),
            'watchable_id' => $abandoned->id,
            'position_seconds' => 240,
            'duration_seconds' => 7200,
            'completed' => false,
            'watched_at' => now()->subDays(3),
        ]);

        collect(range(1, 8))->each(fn () => $this->publishedMovie($thriller));

        $shelf = $this->recommender->smartShuffle($user->id, 10);

        $this->assertFalse(
            $shelf->pluck('id')->contains($abandoned->id),
            'A title the user abandoned early must not be re-recommended.',
        );
    }

    public function test_negative_affinity_genre_is_never_treated_as_familiar(): void
    {
        // One liked genre, one actively disliked one. `top_genres_count` is
        // 3, so the old `arsort` + `array_slice` promoted the disliked genre
        // into the "familiar" set purely to fill the quota — recommending the
        // very thing the user keeps abandoning.
        $user = User::factory()->create();
        $thriller = Genre::factory()->create(['name' => 'Thriller']);
        $musical = Genre::factory()->create(['name' => 'Musical']);

        collect(range(1, 4))->each(function () use ($thriller, $user) {
            $m = $this->publishedMovie($thriller, 200);
            $this->complete($user, $m);
        });

        // Four musicals, each abandoned inside the first 20% → negative
        // affinity for the genre.
        collect(range(1, 4))->each(function () use ($musical, $user) {
            $m = $this->publishedMovie($musical, 200);
            WatchHistoryItem::create([
                'user_id' => $user->id,
                'watchable_type' => $m->getMorphClass(),
                'watchable_id' => $m->id,
                'position_seconds' => 120,
                'duration_seconds' => 7200,
                'completed' => false,
                'watched_at' => now()->subDays(5),
            ]);
        });

        $genreAffinity = $this->callPrivate('buildGenreAffinity', [$user->id]);

        $this->assertGreaterThan(0, $genreAffinity[$thriller->id], 'Sanity: thriller affinity should be positive.');
        $this->assertLessThan(0, $genreAffinity[$musical->id], 'Sanity: repeated early abandons should make musical negative.');

        $positive = $this->callPrivate('positiveOnly', [$genreAffinity]);

        $this->assertArrayHasKey($thriller->id, $positive);
        $this->assertArrayNotHasKey(
            $musical->id,
            $positive,
            'A genre with negative affinity must never enter the familiar set, even when there are free slots.',
        );
    }

    public function test_cast_affinity_influences_the_shelf(): void
    {
        // Same genre on every candidate, so genre affinity cannot be what
        // separates them — only the shared actor can. Smart Shuffle ignored
        // cast entirely before, despite the vector already being computed.
        [$user, $thriller] = $this->warmThrillerFan();
        $star = Person::factory()->create();

        $starred = $this->publishedMovie($thriller);
        $starred->cast()->attach($star->id, ['role' => 'actor']);

        // Build a strong affinity for that actor via a separate completion.
        $priorWithStar = $this->publishedMovie($thriller, 300);
        $priorWithStar->cast()->attach($star->id, ['role' => 'actor']);
        $this->complete($user, $priorWithStar);

        collect(range(1, 10))->each(fn () => $this->publishedMovie($thriller));

        $castAff = $this->callPrivate('normaliseVector', [
            $this->callPrivate('positiveOnly', [$this->callPrivate('buildCastAffinity', [$user->id])]),
        ]);

        $this->assertNotEmpty($castAff, 'The user should have a cast affinity vector.');

        $withStar = $this->callPrivate('scoreShuffleCandidate', [
            $starred->fresh()->load('genres', 'cast'), [], $castAff, [], [],
        ]);
        $plain = $this->callPrivate('scoreShuffleCandidate', [
            $this->publishedMovie($thriller)->load('genres', 'cast'), [], $castAff, [], [],
        ]);

        $this->assertGreaterThan(
            $plain,
            $withStar,
            'A title featuring an actor the user has watched must outscore an otherwise identical one.',
        );
    }

    public function test_weighted_sample_favours_the_top_of_the_ranking(): void
    {
        // The old code did ->take(20)->shuffle()->take(5), giving rank 0 and
        // rank 19 identical odds. Rank-biased sampling must not.
        $pool = collect(range(0, 19))->map(fn ($i) => (object) ['id' => $i]);

        $topHits = 0;
        $tailHits = 0;

        for ($i = 0; $i < 400; $i++) {
            $picked = $this->callPrivate('weightedSample', [$pool, 5, 0.88])->pluck('id');
            $topHits += $picked->contains(0) ? 1 : 0;
            $tailHits += $picked->contains(19) ? 1 : 0;
        }

        $this->assertGreaterThan(
            $tailHits,
            $topHits,
            'The best-ranked candidate must be sampled more often than the worst.',
        );
        $this->assertGreaterThan(
            0,
            $tailHits,
            'The tail must still surface sometimes — otherwise the shelf never churns.',
        );
    }

    public function test_weighted_sample_never_repeats_a_candidate(): void
    {
        $pool = collect(range(0, 9))->map(fn ($i) => (object) ['id' => $i]);

        $picked = $this->callPrivate('weightedSample', [$pool, 10, 0.88])->pluck('id');

        $this->assertCount(10, $picked);
        $this->assertCount(10, $picked->unique(), 'Sampling is without replacement.');
    }

    public function test_shelf_fills_to_limit_even_on_a_thin_catalog(): void
    {
        [$user, $thriller] = $this->warmThrillerFan();

        // Exactly enough titles to fill, but almost none of them match the
        // user's genre — the affinity/discovery split plus exclusions must
        // still not leave the rail rendering short.
        collect(range(1, 12))->each(fn () => $this->publishedMovie());

        $shelf = $this->recommender->smartShuffle($user->id, 10);

        $this->assertCount(10, $shelf, 'The rail must always fill to the requested limit when the catalog can supply it.');
        $this->assertCount(10, $shelf->pluck('id')->unique(), 'The shelf must never contain the same title twice.');
    }

    // -- Tier 2: the parts that earn the "AI" in the name ------------------

    public function test_collaborative_filtering_surfaces_co_watched_titles(): void
    {
        [$user, $thriller] = $this->warmThrillerFan();

        // The user finished this; so did a crowd of peers.
        $shared = $this->publishedMovie($thriller);
        $this->complete($user, $shared);

        // Those same peers also all finished this — a comedy, so genre
        // affinity would never have surfaced it. Collaborative filtering is
        // the only thing that can put it on the shelf.
        $comedy = Genre::factory()->create(['name' => 'Comedy']);
        $coWatched = $this->publishedMovie($comedy);

        collect(range(1, 6))->each(function () use ($shared, $coWatched) {
            $peer = User::factory()->create();
            $this->complete($peer, $shared);
            $this->complete($peer, $coWatched);
        });

        // Decoys: unrelated comedies nobody co-watched. If the shelf picked
        // the co-watched title by luck of the draw, these would show up
        // just as often.
        collect(range(1, 10))->each(fn () => $this->publishedMovie($comedy));

        $pool = $this->callPrivate('collaborativePool', [$user->id, [], 40]);

        $this->assertTrue(
            $pool->pluck('id')->contains($coWatched->id),
            'A title finished by everyone who finished what the user finished must enter the collaborative pool.',
        );
        $this->assertEqualsWithDelta(
            1.0,
            (float) $pool->firstWhere('id', $coWatched->id)->_collabAffinity,
            0.001,
            'The strongest co-watch link should normalise to 1.0.',
        );
    }

    public function test_collaborative_pool_stays_empty_when_co_watch_data_is_noise(): void
    {
        // A single peer sharing a single title is a coincidence, not a
        // signal. collab_min_peers (2) must reject it rather than dress it
        // up as a recommendation.
        [$user, $thriller] = $this->warmThrillerFan();

        $shared = $this->publishedMovie($thriller);
        $this->complete($user, $shared);

        $lonePeer = User::factory()->create();
        $this->complete($lonePeer, $shared);
        $this->complete($lonePeer, $this->publishedMovie());

        $pool = $this->callPrivate('collaborativePool', [$user->id, [], 40]);

        $this->assertTrue(
            $pool->isEmpty(),
            'A co-watch link seen by only one peer is below the confidence floor and must be discarded.',
        );
    }

    public function test_picks_carry_an_honest_reason(): void
    {
        [$user, $thriller] = $this->warmThrillerFan();
        collect(range(1, 10))->each(fn () => $this->publishedMovie($thriller));

        $shelf = $this->recommender->smartShuffle($user->id, 10);

        $this->assertNotEmpty($shelf);

        foreach ($shelf as $movie) {
            $this->assertNotEmpty(
                $movie->_shuffleReason,
                'Every Smart Shuffle pick must be able to say why it is on the shelf.',
            );
        }

        // Thriller is the user's only affinity genre, so at least one pick
        // must be explained by it rather than by a generic fallback.
        $this->assertTrue(
            $shelf->contains(fn ($m) => $m->_shuffleReason === __('recommendReason.genre_match', ['genre' => 'Thriller'])),
            'A title matched on the user\'s top genre should say so.',
        );
    }

    public function test_because_you_watched_names_the_real_co_watch_link(): void
    {
        [$user, $thriller] = $this->warmThrillerFan();

        $seed = $this->publishedMovie($thriller);
        $seed->update(['title' => 'The Seed Movie']);
        $this->complete($user, $seed);

        $comedy = Genre::factory()->create(['name' => 'Comedy']);
        $coWatched = $this->publishedMovie($comedy);

        collect(range(1, 6))->each(function () use ($seed, $coWatched) {
            $peer = User::factory()->create();
            $this->complete($peer, $seed);
            $this->complete($peer, $coWatched);
        });

        $reasons = $this->callPrivate('coWatchReasons', [$user->id, [$coWatched->id]]);

        $this->assertSame(
            'The Seed Movie',
            $reasons[$coWatched->id] ?? null,
            'The "Because you watched X" caption must name the title actually most co-completed with the pick, not a guess.',
        );
    }

    public function test_recently_shown_titles_are_penalised_on_the_next_window(): void
    {
        [$user, $thriller] = $this->warmThrillerFan();
        $candidate = $this->publishedMovie($thriller);

        $fresh = $this->callPrivate('scoreShuffleCandidate', [
            $candidate->load('genres', 'cast'), [], [], [], [],
        ]);
        $seen = $this->callPrivate('scoreShuffleCandidate', [
            $candidate->load('genres', 'cast'), [], [], [], [$candidate->id => true],
        ]);

        $this->assertLessThan(
            $fresh,
            $seen,
            'A title shown in a recent window must score lower, so the shelf actually turns over.',
        );
    }

    public function test_shuffle_memory_survives_an_admin_publishing_a_title(): void
    {
        [$user, $thriller] = $this->warmThrillerFan();
        collect(range(1, 10))->each(fn () => $this->publishedMovie($thriller));

        $shelf = $this->recommender->smartShuffle($user->id, 10);

        $memoryKey = TopPicksRecommender::SESSION_KEY_SHUFFLE_SEEN . '.' . $user->id;

        $this->assertEqualsCanonicalizing(
            $shelf->pluck('id')->all(),
            array_map('intval', (array) session($memoryKey)),
            'The shelf we just served should be remembered in full.',
        );

        // CatalogCacheObserver calls Cache::flush() on every movie create or
        // publish. If the anti-repeat memory lived in the cache, one admin
        // upload would reset it for every user on the site — so the memory
        // must not live there.
        $this->publishedMovie($thriller);

        $shelfKey = TopPicksRecommender::CACHE_KEY_USER_PREFIX
            . $user->id
            . TopPicksRecommender::CACHE_KEY_SMART_SHUFFLE_USER_SUFFIX;

        $this->assertFalse(Cache::has($shelfKey), 'Sanity: publishing a title does flush the cached shelf.');
        $this->assertNotEmpty(
            session($memoryKey),
            'An admin publishing a title must not wipe every user\'s anti-repeat memory.',
        );
    }

    public function test_shuffle_memory_is_scoped_per_user(): void
    {
        // Two accounts sharing a browser must not inherit each other's
        // memory — the session outlives a logout.
        [$alice, $thriller] = $this->warmThrillerFan();
        collect(range(1, 10))->each(fn () => $this->publishedMovie($thriller));

        $aliceShelf = $this->recommender->smartShuffle($alice->id, 10);

        $bob = User::factory()->create();
        $bobMemory = session(TopPicksRecommender::SESSION_KEY_SHUFFLE_SEEN . '.' . $bob->id, []);

        $this->assertEmpty($bobMemory, 'A second account on the same browser starts with no shuffle memory.');
        $this->assertNotEmpty($aliceShelf, 'Sanity: the first account did get a shelf.');
    }

    public function test_guest_shelf_reacts_to_in_session_browsing(): void
    {
        $horror = Genre::factory()->create(['name' => 'Horror']);
        $romance = Genre::factory()->create(['name' => 'Romance']);

        $horrorFilm = $this->publishedMovie($horror);
        collect(range(1, 6))->each(fn () => $this->publishedMovie($horror));
        collect(range(1, 6))->each(fn () => $this->publishedMovie($romance));

        // Browsing a horror title as a guest should teach the shelf something.
        $this->get(route('frontend.movie_detail', $horrorFilm->slug))->assertOk();

        $this->assertSame(
            [$horror->id],
            array_map('intval', (array) session(TopPicksRecommender::SESSION_KEY_GUEST_GENRES)),
            'A guest opening a movie detail page should leave a genre signal in the session.',
        );

        $shelf = app(TopPicksRecommender::class)->smartShuffle(null, 10);
        $horrorPicks = $shelf->filter(fn ($m) => $m->genres->pluck('id')->contains($horror->id));

        $this->assertGreaterThan(
            0,
            $horrorPicks->count(),
            'A guest who just browsed horror should see horror on their shelf.',
        );
        $this->assertTrue(
            $shelf->contains(fn ($m) => $m->_shuffleReason === __('recommendReason.browsing')),
            'Session-driven picks should be labelled as such.',
        );
    }

    public function test_guest_session_signal_does_not_leak_between_visitors(): void
    {
        // Two guests with different in-session tastes must not share a cache
        // slot. A single global guest key would serve the first visitor's
        // personalised shelf to the second.
        $horror = Genre::factory()->create(['name' => 'Horror']);
        $romance = Genre::factory()->create(['name' => 'Romance']);

        $signalA = $this->callPrivateOn($this->recommender, 'guestGenreSignal', []);
        $this->assertSame([], $signalA, 'No session signal outside a request context.');

        session([TopPicksRecommender::SESSION_KEY_GUEST_GENRES => [$horror->id]]);
        $keyA = $this->guestCacheKeyFor([$horror->id]);

        session([TopPicksRecommender::SESSION_KEY_GUEST_GENRES => [$romance->id]]);
        $keyB = $this->guestCacheKeyFor([$romance->id]);

        $this->assertNotSame($keyA, $keyB, 'Guests with different signals must land on different cache keys.');
    }

    private function guestCacheKeyFor(array $genreIds): string
    {
        return TopPicksRecommender::CACHE_KEY_SMART_SHUFFLE_GUEST
            . ':' . substr(sha1(implode(',', $genreIds)), 0, 12);
    }

    /**
     * The scoring, sampling and pool-building internals are private by
     * design — they're implementation, not API. Testing them directly is
     * still worth it: each one encodes a decision that used to be wrong,
     * and a black-box assertion on a randomised shelf would be flaky.
     */
    private function callPrivate(string $method, array $args): mixed
    {
        return $this->callPrivateOn($this->recommender, $method, $args);
    }

    private function callPrivateOn(object $object, string $method, array $args): mixed
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($object, $args);
    }
}
