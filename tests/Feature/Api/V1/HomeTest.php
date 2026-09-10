<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\FeaturedItem;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Tag;
use Modules\Streaming\app\Models\Device;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Tests\TestCase;

/**
 * The app's home screen.
 *
 * The point of this endpoint is that it is not a second homepage: it calls the
 * same HomeRailsService the website's view composer calls. So the assertions
 * that matter are the ones about the rails matching the site's rules — the
 * admin's category order, drafts staying out, Continue Watching appearing only
 * for a signed-in viewer — plus the usual guarantee that no file URL escapes.
 */
class HomeTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';
    private const VIDEO = 'https://origin.example.test/secret-master-file.mp4';

    public function test_a_guest_gets_a_full_home_screen(): void
    {
        $this->seedCatalogue();

        $response = $this->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['hero', 'rails', 'server_time']]);

        $this->assertNotEmpty($response->json('data.rails'), 'A guest must still get rails, as on the website.');
    }

    public function test_the_home_response_never_contains_a_video_url(): void
    {
        $this->seedCatalogue();

        $body = $this->getJson('/api/v1/home')->assertOk()->content();

        $this->assertStringNotContainsString(self::VIDEO, $body);
        $this->assertStringNotContainsString('video_url', $body);
        $this->assertStringNotContainsString('dropbox_path', $body);
    }

    public function test_every_rail_declares_a_kind_the_app_can_render(): void
    {
        $this->seedCatalogue();

        $rails = $this->getJson('/api/v1/home')->assertOk()->json('data.rails');

        foreach ($rails as $rail) {
            $this->assertArrayHasKey('key', $rail);
            $this->assertArrayHasKey('items', $rail);

            // A heading, for every kind that draws one. The two daily `banner`
            // rails do not: the website's vertical slider and tab slider carry
            // no title row at all, the rank label inside each slide does that
            // job. Asserting a title on them would be asserting a heading the
            // design does not have.
            if ($rail['kind'] !== 'banner') {
                $this->assertArrayHasKey('title', $rail, "Rail '{$rail['key']}' draws a heading and has no title.");
            }

            $this->assertContains(
                $rail['kind'],
                // `banner` joined the list on 2026-09-10 with the two daily
                // Top 10 rails. Keep this in step with `RENDERABLE` in
                // `mobile/src/api/catalogue.ts`: a kind the server sends and
                // the app has no component for renders as nothing at all.
                ['titles', 'progress', 'genres', 'vjs', 'people', 'banner'],
                "Rail '{$rail['key']}' declares an unknown kind; the app has no card component for it."
            );
        }
    }

    /**
     * A heading over nothing is worse than no heading, and on a phone it is a
     * screen of empty space between two real rails.
     */
    public function test_empty_rails_are_dropped_rather_than_sent_empty(): void
    {
        $this->seedCatalogue();

        $rails = $this->getJson('/api/v1/home')->assertOk()->json('data.rails');

        foreach ($rails as $rail) {
            $this->assertNotEmpty($rail['items'], "Rail '{$rail['key']}' was sent with no items.");
        }
    }

    public function test_rails_carry_no_draft_titles(): void
    {
        $this->seedCatalogue();
        $draft = Movie::factory()->create([
            'status' => Movie::STATUS_DRAFT,
            'published_at' => null,
            'title' => 'Draft Must Not Appear',
        ]);

        $body = $this->getJson('/api/v1/home')->assertOk()->content();

        $this->assertStringNotContainsString($draft->title, $body);
    }

    public function test_continue_watching_is_absent_for_a_guest(): void
    {
        $this->seedCatalogue();

        $keys = collect($this->getJson('/api/v1/home')->json('data.rails'))->pluck('key');

        $this->assertNotContains('continue_watching', $keys->all());
    }

    /**
     * The resume target is what makes this rail useful to a native player: it
     * cannot follow the website's watchLink, it needs an id to play.
     */
    public function test_continue_watching_appears_for_a_viewer_and_says_what_to_resume(): void
    {
        $this->seedCatalogue();
        $user = $this->viewer();
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

        $rails = collect(
            $this->withFreshToken($this->signIn($user))->getJson('/api/v1/home')->assertOk()->json('data.rails')
        )->keyBy('key');

        $this->assertArrayHasKey('continue_watching', $rails->all());

        $card = $rails['continue_watching']['items'][0];
        $this->assertSame('movie', $card['resume']['type']);
        $this->assertSame($movie->id, $card['resume']['id']);
        $this->assertSame(300, $card['position_seconds']);
        $this->assertGreaterThan(0, $card['progress_percent']);
    }

    /**
     * For a series the two differ on purpose: resume the episode, remove the
     * show. Removing one episode would let the show re-surface on the next
     * render, because the rail dedupes by show.
     */
    public function test_a_series_card_resumes_the_episode_but_removes_the_show(): void
    {
        $this->seedCatalogue();
        $user = $this->viewer();
        $show = Show::published()->first();
        $season = Season::create(['show_id' => $show->id, 'number' => 2, 'title' => 'S2']);
        $episode = Episode::create([
            'season_id' => $season->id,
            'number' => 1,
            'title' => 'Resumable Episode',
            'published_at' => now()->subDay(),
            'video_url' => self::VIDEO,
        ]);

        WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $episode->getMorphClass(),
            'watchable_id' => $episode->id,
            'position_seconds' => 120,
            'duration_seconds' => 1800,
            'completed' => false,
            'watched_at' => now(),
        ]);

        $rails = collect(
            $this->withFreshToken($this->signIn($user))->getJson('/api/v1/home')->json('data.rails')
        )->keyBy('key');

        $card = $rails['continue_watching']['items'][0];

        $this->assertSame('episode', $card['resume']['type']);
        $this->assertSame($episode->id, $card['resume']['id']);
        $this->assertSame('show', $card['remove']['type']);
        $this->assertSame($show->id, $card['remove']['id']);
    }

    /**
     * The admin's drag order on /admin/categories is the page order on the
     * website, so it must be the rail order in the app too.
     */
    public function test_category_shelves_follow_the_admin_sort_order(): void
    {
        $this->seedCatalogue();

        $second = Category::create([
            'name' => 'Second Shelf',
            'slug' => 'second-shelf',
            'visible_home' => true,
            'sort_order' => 2,
        ]);
        Movie::published()->first()->categories()->attach($second->id);

        $keys = collect($this->getJson('/api/v1/home')->json('data.rails'))
            ->pluck('key')
            ->filter(fn ($key) => str_starts_with($key, 'category:'))
            ->values();

        $this->assertSame(['category:home-shelf', 'category:second-shelf'], $keys->all());
    }

    public function test_the_hero_carries_cards(): void
    {
        $this->seedCatalogue();

        $hero = $this->getJson('/api/v1/home')->assertOk()->json('data.hero');

        $this->assertNotEmpty($hero);
        foreach ($hero as $item) {
            $this->assertContains($item['type'], ['movie', 'series']);
            $this->assertArrayHasKey('slug', $item);
        }
    }

    /**
     * The banner is not a poster card.
     *
     * The app's banner drew a portrait poster with a year under it while the
     * website drew a backdrop, a synopsis and three taxonomy lines, and the
     * cause was this endpoint sending `MovieResource::card()`. These are the
     * fields `components/partials/hero-banner.blade.php` renders; if one stops
     * arriving, that part of the banner silently disappears rather than
     * breaking, which is why it is asserted rather than eyeballed.
     */
    public function test_the_hero_carries_everything_the_websites_banner_draws(): void
    {
        $this->seedHeroTitle();

        $hero = $this->getJson('/api/v1/home')->assertOk()->json('data.hero');

        $movie = collect($hero)->firstWhere('title', 'Banner Movie');
        $this->assertNotNull($movie, 'The seeded title must reach the hero.');

        foreach (['backdrop_url', 'synopsis', 'genres', 'tags', 'cast'] as $field) {
            $this->assertArrayHasKey($field, $movie, "The banner draws {$field}.");
        }

        $this->assertSame('Drama', $movie['genres'][0]['name']);
        $this->assertSame('Kampala', $movie['tags'][0]['name']);
        $this->assertSame('Ada Actor', $movie['cast'][0]['name']);
    }

    /**
     * A series banner needs two things a movie banner does not: the badge says
     * "N season" where a movie's says its certification, and the clock line
     * has no `runtime_minutes` on a show to read.
     */
    public function test_a_series_in_the_hero_carries_its_season_count_and_episode_runtime(): void
    {
        $this->seedHeroTitle();

        $hero = $this->getJson('/api/v1/home')->assertOk()->json('data.hero');
        $series = collect($hero)->firstWhere('type', 'series');

        $this->assertNotNull($series, 'The seeded series must reach the hero.');
        $this->assertSame(1, $series['seasons_count']);
        // 30 and 50 were seeded, so the mean is 40 — not the first episode's
        // 30, which is what the website's blade would have shown.
        $this->assertSame(40, $series['episode_runtime_minutes']);
    }

    /**
     * ⚠️ The star average is gone, and this is the assertion that keeps it gone.
     *
     * `hero-banner.blade.php` read `ratings()->avg('stars') ?? 5`, so on a
     * catalogue with no ratings it drew five filled gold stars on every title.
     * There are no ratings and there is no way to create one — no controller
     * or endpoint on either surface writes to that table, only a seeder — so
     * Rio removed the star row from the app AND from the website on
     * 2026-09-10 (ADR-0006). The IMDb mark stays on both.
     *
     * The field going back into the payload is how this would quietly regress,
     * because a field is much easier to add than a component.
     */
    public function test_the_hero_sends_no_star_average(): void
    {
        $this->seedHeroTitle();

        $hero = $this->getJson('/api/v1/home')->assertOk()->json('data.hero');

        foreach ($hero as $item) {
            $this->assertArrayNotHasKey('stars_avg', $item, 'The star row was removed; the field should have gone with it.');
        }
    }

    /**
     * The banner shape is the banner's alone.
     *
     * Two shapes exist so a shelf of thirty posters does not carry thirty
     * synopses. A later change that widened `card()` to fix the banner would
     * put the home payload's weight up by an order of magnitude and nothing
     * else in the suite would notice.
     */
    public function test_the_rails_below_the_hero_still_carry_plain_poster_cards(): void
    {
        $this->seedHeroTitle();

        $rails = $this->getJson('/api/v1/home')->assertOk()->json('data.rails');

        $titleRails = collect($rails)->where('kind', 'titles');
        $this->assertNotEmpty($titleRails, 'There must be poster rails to check.');

        foreach ($titleRails as $rail) {
            foreach ($rail['items'] as $item) {
                $this->assertArrayNotHasKey('synopsis', $item, "Rail {$rail['key']} is carrying banner fields.");
                $this->assertArrayNotHasKey('backdrop_url', $item);
            }
        }
    }

    // ── fixtures ─────────────────────────────────────────────────────

    /**
     * One movie and one series with everything the banner draws attached.
     *
     * Curated rather than seeded loosely, because `buildHero()` prefers
     * FeaturedItem when any exist and falls back to the most-viewed otherwise
     * — pinning both is what makes "the seeded title must reach the hero" a
     * fact rather than a coin toss on view counts.
     */
    private function seedHeroTitle(): void
    {
        $this->seedCatalogue();

        $genre = Genre::create(['name' => 'Drama', 'slug' => 'banner-drama']);
        $tag = Tag::create(['name' => 'Kampala', 'slug' => 'banner-kampala']);
        $person = Person::create(['first_name' => 'Ada', 'last_name' => 'Actor', 'slug' => 'banner-ada']);

        $movie = Movie::factory()->create([
            'title' => 'Banner Movie',
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'video_url' => self::VIDEO,
        ]);
        $movie->genres()->attach($genre->id);
        $movie->tags()->attach($tag->id);
        $movie->cast()->attach($person->id, ['role' => 'actor']);

        $show = Show::create([
            'title' => 'Banner Series',
            'slug' => 'banner-series',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $show->genres()->attach($genre->id);

        $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'S1']);
        foreach ([30, 50] as $number => $runtime) {
            Episode::create([
                'season_id' => $season->id,
                'number' => $number + 1,
                'title' => 'Episode ' . ($number + 1),
                'runtime_minutes' => $runtime,
                'published_at' => now()->subDay(),
                'video_url' => self::VIDEO,
            ]);
        }

        FeaturedItem::create([
            'featurable_type' => $movie->getMorphClass(),
            'featurable_id' => $movie->id,
            'position' => 1,
        ]);
        FeaturedItem::create([
            'featurable_type' => $show->getMorphClass(),
            'featurable_id' => $show->id,
            'position' => 2,
        ]);
    }

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
                'video_url' => self::VIDEO,
            ]);
            $movie->categories()->attach($category->id);
        }

        // Show::published() needs a published episode, or the series rails are
        // silently empty and half these assertions would pass for nothing.
        for ($i = 0; $i < 2; $i++) {
            $show = Show::create([
                'title' => 'Home Series ' . $i,
                'slug' => 'home-series-' . $i . '-' . uniqid(),
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
                'video_url' => self::VIDEO,
            ]);
        }
    }

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function signIn(User $user): string
    {
        RateLimiter::clear(strtolower($user->email) . '|127.0.0.1');

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => ['uuid' => 'device-uuid-home1', 'platform' => Device::PLATFORM_ANDROID],
        ])->assertOk()->json('data.token');
    }

    /** See AuthAndDevicesTest: the guard caches its user across requests. */
    private function withFreshToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
