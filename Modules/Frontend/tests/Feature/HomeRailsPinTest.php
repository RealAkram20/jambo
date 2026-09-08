<?php

namespace Modules\Frontend\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Frontend\app\View\Composers\SectionDataComposer;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Tests\TestCase;

/**
 * Behaviour pin for the homepage rails, written BEFORE SectionDataComposer's
 * build() was extracted into HomeRailsService, and required to pass unchanged
 * after it.
 *
 * The contract is not build() — that method is what moves. The contract is the
 * set of variables the composer shares with every frontend view, because every
 * section blade on the live site reads them by name. A missing key renders an
 * empty rail; a key whose type changed is a 500 on the homepage.
 *
 * The site is live, so this file is the evidence that pulling ~350 lines out
 * of the composer changed nothing a viewer sees.
 */
class HomeRailsPinTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every variable the composer shares. Extracted from the live build()
     * before the refactor. Nothing may leave this list without a deliberate
     * decision, because a blade somewhere reads each one.
     */
    private const SHARED_KEYS = [
        'latestMovies', 'popularMovies', 'topMovies', 'upcomingMovies',
        'recommendedMovies', 'specialsMovies', 'freshMovies',
        'latestShows', 'popularShows', 'topShows', 'recommendedShows',
        'internationalShows',
        'heroMovies', 'heroItems', 'verticalFeatured', 'tabSeries',
        'exclusiveMovies', 'topPicks', 'homeGenres', 'homeVjs',
        'favoritePersonalities', 'continueWatching',
        'homeCategories', 'randomHomeCategories',
        'userWatchlistIndex',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetComposerCache();
    }

    protected function tearDown(): void
    {
        $this->resetComposerCache();
        parent::tearDown();
    }

    public function test_the_composer_shares_every_variable_the_blades_read(): void
    {
        $this->seedCatalogue();

        $shared = $this->compose();

        foreach (self::SHARED_KEYS as $key) {
            $this->assertArrayHasKey(
                $key,
                $shared,
                "The composer stopped sharing '{$key}'. A section blade reads it by name and will render empty."
            );
        }
    }

    public function test_the_rails_carry_published_content_and_nothing_else(): void
    {
        $this->seedCatalogue();
        $draft = Movie::factory()->create([
            'status' => Movie::STATUS_DRAFT,
            'published_at' => null,
            'title' => 'Draft Should Not Appear',
        ]);

        $shared = $this->compose();

        $this->assertGreaterThan(0, $shared['latestMovies']->count(), 'Published movies must reach the rail.');
        $this->assertFalse(
            $shared['latestMovies']->contains('id', $draft->id),
            'A draft movie must never reach a homepage rail.'
        );
        $this->assertGreaterThan(0, $shared['latestShows']->count());
    }

    public function test_exclusives_are_only_gated_titles(): void
    {
        $this->seedCatalogue();

        $shared = $this->compose();

        foreach ($shared['exclusiveMovies'] as $movie) {
            $this->assertNotNull(
                $movie->tier_required,
                'The Exclusives rail is defined as titles with a plan; a free title in it is a leak of the rail\'s meaning.'
            );
        }
    }

    public function test_a_visible_home_category_becomes_a_shelf_with_rail_items(): void
    {
        $this->seedCatalogue();

        $shared = $this->compose();

        $this->assertGreaterThan(0, $shared['homeCategories']->count(), 'A visible-home category with content must produce a shelf.');

        $shelf = $shared['homeCategories']->first();
        $this->assertTrue(isset($shelf->railItems), 'Each shelf carries railItems for its blade.');
        $this->assertGreaterThan(0, $shelf->railItems->count(), 'An empty shelf must have been dropped, not rendered.');
    }

    public function test_an_empty_category_never_becomes_a_shelf(): void
    {
        $this->seedCatalogue();
        Category::create([
            'name' => 'Nothing In Here',
            'slug' => 'nothing-in-here',
            'visible_home' => true,
            'sort_order' => 99,
        ]);

        $shared = $this->compose();

        $slugs = $shared['homeCategories']->concat($shared['randomHomeCategories'])->pluck('slug');
        $this->assertNotContains('nothing-in-here', $slugs->all());
    }

    public function test_continue_watching_is_empty_for_a_guest(): void
    {
        $this->seedCatalogue();

        $this->assertTrue($this->compose()['continueWatching']->isEmpty());
    }

    public function test_continue_watching_carries_a_card_for_a_signed_in_viewer(): void
    {
        $this->seedCatalogue();
        $user = User::factory()->create();
        $movie = Movie::published()->first();

        WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'position_seconds' => 300,
            'duration_seconds' => 3600,
            'completed' => false,
            'watched_at' => now(),
        ]);

        $this->actingAs($user);
        $cards = $this->compose()['continueWatching'];

        $this->assertCount(1, $cards);
        $card = $cards->first();

        // The shape the card blade reads. Each property is a template line.
        foreach (['imagePath', 'title', 'subtitle', 'progressPercent', 'minutesLeft', 'watchLink', 'removeType', 'removeId'] as $property) {
            $this->assertTrue(
                property_exists($card, $property),
                "The continue-watching card lost '{$property}', which its blade renders."
            );
        }

        $this->assertSame('movie', $card->removeType);
        $this->assertSame($movie->id, $card->removeId);
    }

    /**
     * One card per series, pointing at the episode most recently watched.
     * Without the dedupe a bingewatcher's row is nine cards of one show.
     */
    public function test_continue_watching_shows_one_card_per_series(): void
    {
        $this->seedCatalogue();
        $user = User::factory()->create();
        $show = Show::published()->first();
        $season = Season::create(['show_id' => $show->id, 'number' => 2, 'title' => 'S2']);

        foreach ([1, 2] as $number) {
            $episode = Episode::create([
                'season_id' => $season->id,
                'number' => $number,
                'title' => "Episode {$number}",
                'published_at' => now()->subDay(),
                'video_url' => 'https://cdn.example.test/ep.mp4',
            ]);

            WatchHistoryItem::create([
                'user_id' => $user->id,
                'watchable_type' => $episode->getMorphClass(),
                'watchable_id' => $episode->id,
                'position_seconds' => 120,
                'duration_seconds' => 1800,
                'completed' => false,
                'watched_at' => now()->subMinutes(3 - $number),
            ]);
        }

        $this->actingAs($user);
        $cards = $this->compose()['continueWatching'];

        $this->assertCount(1, $cards, 'Two episodes of one series must collapse to a single card.');
        $this->assertSame('show', $cards->first()->removeType);
        $this->assertSame($show->id, $cards->first()->removeId);
    }

    public function test_the_watchlist_index_is_per_viewer_and_empty_for_guests(): void
    {
        $this->seedCatalogue();

        $this->assertSame([], $this->compose()['userWatchlistIndex']);
    }

    public function test_the_homepage_renders(): void
    {
        $this->seedCatalogue();

        $this->get('/')->assertOk();
    }

    // ── fixtures ─────────────────────────────────────────────────────

    private function seedCatalogue(): void
    {
        $category = Category::create([
            'name' => 'Home Shelf',
            'slug' => 'home-shelf',
            'visible_home' => true,
            'sort_order' => 1,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $movie = Movie::factory()->create([
                'status' => Movie::STATUS_PUBLISHED,
                'published_at' => now()->subDays($i + 1),
                'tier_required' => $i === 0 ? 'premium' : null,
            ]);
            $movie->categories()->attach($category->id);
        }

        // Show::published() requires at least one episode that is itself
        // published - a series with no watchable episode is a dead end, so
        // the scope keeps it off public rails. Seeding a show without one
        // produces empty show rails and assertions that pass for the wrong
        // reason.
        for ($i = 0; $i < 2; $i++) {
            $show = Show::create([
                'title' => 'Pinned Series ' . $i,
                'slug' => 'pinned-series-' . $i . '-' . uniqid(),
                'status' => 'published',
                'published_at' => now()->subDays($i + 1),
            ]);
            $show->categories()->attach($category->id);

            $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'S1']);
            Episode::create([
                'season_id' => $season->id,
                'number' => 1,
                'title' => 'Pilot',
                'published_at' => now()->subDays($i + 1),
                'video_url' => 'https://cdn.example.test/ep.mp4',
            ]);
        }
    }

    /** The variables the composer hands to a view — the actual contract. */
    private function compose(): array
    {
        $this->resetComposerCache();

        // Any view the composer is registered for. It is never rendered -
        // compose() only calls $view->with(), and what is shared is the
        // contract under test.
        $view = view('frontend::Pages.all-geners-page');
        app(SectionDataComposer::class)->compose($view);

        return $view->getData();
    }

    /**
     * The composer memoises into private statics. PHPUnit runs every test in
     * one process, so without this the second test in the file would read the
     * first test's catalogue and the assertions would be meaningless.
     */
    private function resetComposerCache(): void
    {
        foreach (['cache', 'perUserCache'] as $property) {
            $reflected = new \ReflectionProperty(SectionDataComposer::class, $property);
            $reflected->setAccessible(true);
            $reflected->setValue(null, null);
        }
    }
}
