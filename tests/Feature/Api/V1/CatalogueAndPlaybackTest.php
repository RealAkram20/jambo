<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Streaming\app\Models\ActiveStream;
use Modules\Streaming\app\Models\Device;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Tests\TestCase;

/**
 * Browsing and watching from the app.
 *
 * The load-bearing assertions here are the ones about what does NOT come back:
 * a catalogue response that contains video_url hands out the file the entire
 * offline design exists to protect, and it would do so to anyone, signed in or
 * not. Everything else is a convenience.
 *
 * Playback is deliberately tested against the same scenarios the website's
 * pinning tests cover, because both go through PlaybackAuthorizer and the
 * point is that they answer identically.
 */
class CatalogueAndPlaybackTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';
    private const VIDEO = 'https://origin.example.test/secret-master-file.mp4';

    private SubscriptionTier $basic;
    private SubscriptionTier $premium;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basic = $this->tier('Basic', 'basic', SubscriptionTier::ACCESS_BASIC, 1);
        $this->premium = $this->tier('Premium', 'premium', SubscriptionTier::ACCESS_PREMIUM, 2);
    }

    // ── nothing leaks ────────────────────────────────────────────────

    public function test_a_movie_listing_never_contains_a_video_url(): void
    {
        $this->movie('premium');

        $body = $this->getJson('/api/v1/movies')->assertOk()->content();

        $this->assertStringNotContainsString(self::VIDEO, $body);
        $this->assertStringNotContainsString('video_url', $body);
        $this->assertStringNotContainsString('dropbox_path', $body);
    }

    public function test_a_movie_detail_page_never_contains_a_video_url(): void
    {
        $movie = $this->movie('premium');

        $body = $this->getJson("/api/v1/movies/{$movie->slug}")->assertOk()->content();

        $this->assertStringNotContainsString(self::VIDEO, $body);
        $this->assertStringNotContainsString('video_url', $body);
    }

    public function test_a_series_page_never_contains_an_episode_video_url(): void
    {
        $episode = $this->episode('premium');
        $show = $episode->season->show;

        $body = $this->getJson("/api/v1/series/{$show->slug}")->assertOk()->content();

        $this->assertStringNotContainsString(self::VIDEO, $body);
        $this->assertStringNotContainsString('video_url', $body);
    }

    // ── browsing ─────────────────────────────────────────────────────

    public function test_browsing_is_open_to_guests_like_the_website(): void
    {
        $movie = $this->movie(null);

        $this->getJson('/api/v1/movies')->assertOk()->assertJsonPath('success', true);
        $this->getJson("/api/v1/movies/{$movie->slug}")
            ->assertOk()
            ->assertJsonPath('data.movie.slug', $movie->slug)
            ->assertJsonPath('data.is_released', true);
    }

    public function test_a_draft_movie_is_not_in_the_listing(): void
    {
        $this->movie(null, Movie::STATUS_DRAFT);

        $this->getJson('/api/v1/movies')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    public function test_an_unknown_slug_is_a_clean_not_found(): void
    {
        $this->getJson('/api/v1/movies/no-such-title')
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_listings_are_cursor_paginated(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->movie(null);
        }

        $first = $this->getJson('/api/v1/movies?per_page=2')->assertOk();
        $first->assertJsonCount(2, 'data.items');
        $cursor = $first->json('data.next_cursor');
        $this->assertNotNull($cursor);

        $this->getJson('/api/v1/movies?per_page=2&cursor=' . $cursor)
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_per_page_is_capped_so_a_client_cannot_ask_for_everything(): void
    {
        $this->movie(null);

        $this->getJson('/api/v1/movies?per_page=100000')
            ->assertOk()
            ->assertJsonPath('data.per_page', 60);
    }

    public function test_search_needs_two_characters_and_returns_empty_rather_than_erroring(): void
    {
        $this->movie(null, Movie::STATUS_PUBLISHED, 'Findable Feature');

        $this->getJson('/api/v1/search?q=a')
            ->assertOk()
            ->assertJsonCount(0, 'data.movies');

        $this->getJson('/api/v1/search?q=Findable')
            ->assertOk()
            ->assertJsonCount(1, 'data.movies');
    }

    /**
     * An episode's own tier_required is normally null because the plan lives
     * on the series. A client that read that null as "free" would draw an
     * unlocked badge on premium content — the exact mistake 1.8.22 fixed
     * server-side — so the resolved value is published alongside it.
     */
    public function test_an_episode_publishes_the_tier_it_actually_inherits(): void
    {
        $episode = $this->episode('premium');

        $this->getJson("/api/v1/episodes/{$episode->id}")
            ->assertOk()
            ->assertJsonPath('data.episode.tier_required', null)
            ->assertJsonPath('data.episode.effective_tier_required', 'premium');
    }

    // ── playback ─────────────────────────────────────────────────────

    public function test_a_guest_cannot_open_a_playback_session(): void
    {
        $movie = $this->movie(null);

        $this->postJson('/api/v1/playback/sessions', ['type' => 'movie', 'id' => $movie->id])
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_an_entitled_subscriber_gets_a_playable_url(): void
    {
        $user = $this->subscriber($this->premium);
        $movie = $this->movie('premium');

        $this->withFreshToken($this->signIn($user))
            ->postJson('/api/v1/playback/sessions', ['type' => 'movie', 'id' => $movie->id])
            ->assertOk()
            ->assertJsonPath('data.source', 'file')
            ->assertJsonPath('data.url', self::VIDEO)
            ->assertJsonPath('data.heartbeat_seconds', 15);
    }

    public function test_a_viewer_without_a_plan_is_told_to_subscribe_and_which_plan(): void
    {
        $user = $this->viewer();
        $movie = $this->movie('premium');

        $this->withFreshToken($this->signIn($user))
            ->postJson('/api/v1/playback/sessions', ['type' => 'movie', 'id' => $movie->id])
            ->assertStatus(403)
            ->assertJsonPath('code', 'SUBSCRIPTION_REQUIRED')
            ->assertJsonPath('required_tier.slug', 'premium');
    }

    public function test_a_viewer_on_a_lower_plan_is_told_to_upgrade(): void
    {
        $user = $this->subscriber($this->basic);
        $movie = $this->movie('premium');

        $this->withFreshToken($this->signIn($user))
            ->postJson('/api/v1/playback/sessions', ['type' => 'movie', 'id' => $movie->id])
            ->assertStatus(403)
            ->assertJsonPath('code', 'UPGRADE_REQUIRED');
    }

    public function test_an_unreleased_title_reports_unavailable_and_does_not_leak_its_tier(): void
    {
        $user = $this->subscriber($this->premium);
        $movie = $this->movie('premium', Movie::STATUS_DRAFT);

        $response = $this->withFreshToken($this->signIn($user))
            ->postJson('/api/v1/playback/sessions', ['type' => 'movie', 'id' => $movie->id])
            ->assertStatus(404)
            ->assertJsonPath('code', 'CONTENT_UNAVAILABLE');

        $this->assertNull($response->json('required_tier'));
    }

    /**
     * The whole reason a device uuid doubles as the client session key: an app
     * install occupies one slot in exactly the cap a browser tab does.
     */
    public function test_a_device_at_the_stream_cap_is_refused(): void
    {
        $user = $this->subscriber($this->basic); // cap of 1
        $movie = $this->movie('basic');

        // Another device of the same account is already streaming.
        ActiveStream::create([
            'user_id' => $user->id,
            'session_id' => 'some-other-device-uuid',
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'last_beat_at' => now(),
        ]);

        $this->withFreshToken($this->signIn($user))
            ->postJson('/api/v1/playback/sessions', ['type' => 'movie', 'id' => $movie->id])
            ->assertStatus(409)
            ->assertJsonPath('code', 'STREAM_LIMIT');
    }

    public function test_data_saver_is_refused_rather_than_silently_served_at_full_size(): void
    {
        $user = $this->subscriber($this->premium);
        $movie = $this->movie('premium'); // no video_url_low

        $this->withFreshToken($this->signIn($user))
            ->postJson('/api/v1/playback/sessions', [
                'type' => 'movie', 'id' => $movie->id, 'quality' => 'low',
            ])
            ->assertStatus(404)
            ->assertJsonPath('code', 'CONTENT_UNAVAILABLE');
    }

    public function test_playback_resumes_where_the_website_left_off(): void
    {
        $user = $this->subscriber($this->premium);
        $movie = $this->movie('premium');

        WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'position_seconds' => 754,
            'completed' => false,
            'watched_at' => now()->subHour(),
        ]);

        $this->withFreshToken($this->signIn($user))
            ->postJson('/api/v1/playback/sessions', ['type' => 'movie', 'id' => $movie->id])
            ->assertOk()
            ->assertJsonPath('data.resume_position', 754);
    }

    // ── heartbeat ────────────────────────────────────────────────────

    public function test_a_heartbeat_records_position_under_the_device_key(): void
    {
        $user = $this->subscriber($this->premium);
        $movie = $this->movie('premium');
        $token = $this->signIn($user);

        $this->withFreshToken($token)
            ->postJson('/api/v1/playback/heartbeat', [
                'type' => 'movie', 'id' => $movie->id, 'position' => 120,
            ])
            ->assertOk()
            ->assertJsonPath('data.position', 120);

        // Keyed on the device uuid, so the existing cap, picker and boot flow
        // see an app stream exactly as they see a browser one.
        $this->assertDatabaseHas('active_streams', [
            'user_id' => $user->id,
            'session_id' => 'device-uuid-0001',
        ]);
    }

    public function test_a_booted_device_is_told_to_stop_playing(): void
    {
        $user = $this->subscriber($this->premium);
        $movie = $this->movie('premium');
        $token = $this->signIn($user);

        $this->withFreshToken($token)->postJson('/api/v1/playback/heartbeat', [
            'type' => 'movie', 'id' => $movie->id, 'position' => 30,
        ])->assertOk();

        // The account owner boots this device from somewhere else, which the
        // picker does by flagging the stream.
        ActiveStream::where('session_id', 'device-uuid-0001')->update(['terminated_at' => now()]);

        $this->withFreshToken($token)
            ->postJson('/api/v1/playback/heartbeat', [
                'type' => 'movie', 'id' => $movie->id, 'position' => 45,
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'DEVICE_REVOKED');
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function tier(string $name, string $slug, int $level, int $streams): SubscriptionTier
    {
        return SubscriptionTier::create([
            'name' => $name,
            'slug' => $slug,
            'price' => 10000,
            'currency' => 'UGX',
            'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
            'access_level' => $level,
            'max_concurrent_streams' => $streams,
            'is_active' => true,
            'sort_order' => $level,
        ]);
    }

    /**
     * MovieFactory rolls published_at from its own random published/draft
     * decision, so both are set explicitly or isPubliclyVisible() fails about
     * half the time. video_url_low is deliberately null: the Data Saver case
     * depends on it being absent.
     */
    private function movie(?string $tier, string $status = Movie::STATUS_PUBLISHED, ?string $title = null): Movie
    {
        $attributes = [
            'status' => $status,
            'published_at' => $status === Movie::STATUS_PUBLISHED ? now()->subDay() : null,
            'tier_required' => $tier,
            'video_url' => self::VIDEO,
            'video_url_low' => null,
        ];

        if ($title !== null) {
            $attributes['title'] = $title;
        }

        return Movie::factory()->create($attributes);
    }

    private function episode(?string $showTier): Episode
    {
        $show = Show::create([
            'title' => 'API Show ' . uniqid(),
            'slug' => 'api-show-' . uniqid(),
            'status' => 'published',
            'published_at' => now()->subDay(),
            'tier_required' => $showTier,
        ]);

        $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'Season 1']);

        $episode = Episode::create([
            'season_id' => $season->id,
            'number' => 1,
            'title' => 'Episode 1',
            'published_at' => now()->subDay(),
            'tier_required' => null,
            'video_url' => self::VIDEO,
        ]);

        return $episode->load('season.show');
    }

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function subscriber(SubscriptionTier $tier): User
    {
        $user = $this->viewer();

        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_tier_id' => $tier->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'auto_renew' => true,
        ]);

        return $user;
    }

    private function signIn(User $user, string $uuid = 'device-uuid-0001'): string
    {
        RateLimiter::clear(strtolower($user->email) . '|127.0.0.1');

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => ['uuid' => $uuid, 'platform' => Device::PLATFORM_ANDROID],
        ])->assertOk()->json('data.token');
    }

    /**
     * See AuthAndDevicesTest: Laravel's AuthManager caches the resolved user
     * across requests inside one test, so without this a later request reuses
     * whoever the first one authenticated as.
     */
    private function withFreshToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
