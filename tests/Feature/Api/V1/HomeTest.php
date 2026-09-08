<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
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
            $this->assertArrayHasKey('title', $rail);
            $this->assertArrayHasKey('items', $rail);
            $this->assertContains(
                $rail['kind'],
                ['titles', 'progress', 'genres', 'vjs', 'people'],
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
