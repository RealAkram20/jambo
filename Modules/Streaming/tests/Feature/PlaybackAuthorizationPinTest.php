<?php

namespace Modules\Streaming\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Behaviour pin for the entitlement decision, written BEFORE the
 * PlaybackAuthorizer extraction (mobile app plan Phase 1) and required to
 * pass unchanged after it.
 *
 * The site is live. The extraction collapses three hand-kept copies of the
 * same rules — TierGate, FrontendController::userCanWatch()/
 * concurrencyExceeded()/contentReleased(), and
 * StreamProxyController::streamable() — into one service the /api/v1
 * mobile endpoints will also call. This file is the evidence that the
 * collapse changed nothing a viewer can see.
 *
 * It pins the decision through the HTTP routes rather than the service,
 * deliberately: the routes are what a viewer experiences, and they are the
 * only thing that must stay identical. Service-level tests are additive and
 * live in PlaybackAuthorizerTest.
 *
 * Two known divergences between the copies are NOT pinned here because they
 * are reconciled by the extraction. They are pinned to their new, unified
 * behaviour in PlaybackAuthorizerTest and named in the worklog:
 *   R1  an unknown tier_required slug + a guest
 *   R2  the concurrency cap on an episode that inherits its show's plan
 */
class PlaybackAuthorizationPinTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionTier $basic;
    private SubscriptionTier $premium;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basic = SubscriptionTier::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price' => 15000,
            'currency' => 'UGX',
            'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
            'access_level' => SubscriptionTier::ACCESS_BASIC,
            'max_concurrent_streams' => 1,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->premium = SubscriptionTier::create([
            'name' => 'Premium',
            'slug' => 'premium',
            'price' => 30000,
            'currency' => 'UGX',
            'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
            'access_level' => SubscriptionTier::ACCESS_PREMIUM,
            'max_concurrent_streams' => 2,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    // ── the bare player, guarded by TierGate ─────────────────────────

    public function test_free_movie_plays_for_a_guest(): void
    {
        $movie = $this->movie(tier: null);

        $this->get("/player/movie/{$movie->slug}")->assertOk();
    }

    public function test_require_signup_to_watch_closes_free_content_to_guests(): void
    {
        setting(['require_signup_to_watch', 1]);
        $movie = $this->movie(tier: null);

        $this->get("/player/movie/{$movie->slug}")->assertRedirect(route('login'));
    }

    public function test_premium_movie_sends_a_guest_to_login(): void
    {
        $movie = $this->movie(tier: 'premium');

        $this->get("/player/movie/{$movie->slug}")->assertRedirect(route('login'));
    }

    public function test_subscriber_below_the_required_level_is_forbidden(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic);
        $movie = $this->movie(tier: 'premium');

        $this->actingAs($user)->get("/player/movie/{$movie->slug}")->assertForbidden();
    }

    public function test_subscriber_at_the_required_level_plays(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->premium);
        $movie = $this->movie(tier: 'premium');

        $this->actingAs($user)->get("/player/movie/{$movie->slug}")->assertOk();
    }

    public function test_admin_plays_premium_content_with_no_subscription(): void
    {
        $movie = $this->movie(tier: 'premium');

        $this->actingAs($this->admin())->get("/player/movie/{$movie->slug}")->assertOk();
    }

    public function test_episode_inherits_the_show_plan_and_refuses_a_guest(): void
    {
        $episode = $this->episode(showTier: 'premium');

        $this->get("/player/episode/{$episode->id}")->assertRedirect(route('login'));
    }

    public function test_an_explicit_episode_plan_outranks_the_show_plan(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic);
        // Show is Premium, the episode itself is only Basic — Basic wins.
        $episode = $this->episode(showTier: 'premium', episodeTier: 'basic');

        $this->actingAs($user)->get("/player/episode/{$episode->id}")->assertOk();
    }

    public function test_a_user_at_their_stream_cap_is_sent_to_the_device_picker(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic); // cap of 1
        $movie = $this->movie(tier: 'basic');

        $this->seedActiveStream($user, $movie, 'anotherDeviceSession0123456789abcdefghijk');

        $this->actingAs($user)->get("/player/movie/{$movie->slug}")
            ->assertRedirect(route('streams.limit'));
    }

    public function test_the_cap_does_not_apply_to_free_content(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic);
        $capped = $this->movie(tier: 'basic');
        $this->seedActiveStream($user, $capped, 'yetAnotherDeviceSession9876543210zyxwvuts');

        $free = $this->movie(tier: null);

        $this->actingAs($user)->get("/player/movie/{$free->slug}")->assertOk();
    }

    // ── the rich watch page, guarded by FrontendController ───────────

    public function test_watch_page_lets_an_entitled_subscriber_in(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->premium);
        $movie = $this->movie(tier: 'premium');

        $this->actingAs($user)->get("/watch/{$movie->slug}")->assertOk();
    }

    public function test_watch_page_sends_an_unentitled_subscriber_to_pricing(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic);
        $movie = $this->movie(tier: 'premium');

        $this->actingAs($user)->get("/watch/{$movie->slug}")
            ->assertRedirect(route('frontend.pricing-page'));
    }

    public function test_watch_page_sends_a_guest_to_login_for_premium_content(): void
    {
        $movie = $this->movie(tier: 'premium');

        $this->get("/watch/{$movie->slug}")->assertRedirect(route('login'));
    }

    // ── the stream source, guarded by TierGate + the release check ───

    public function test_stream_source_redirects_an_entitled_viewer_to_the_origin(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->premium);
        $movie = $this->movie(tier: 'premium');

        $this->actingAs($user)->get("/watch/src/movie/{$movie->slug}")
            ->assertRedirect('https://cdn.example.test/movie.mp4');
    }

    public function test_stream_source_404s_on_an_unpublished_title_even_for_a_subscriber(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->premium);
        $movie = $this->movie(tier: null, status: Movie::STATUS_DRAFT);

        $this->actingAs($user)->get("/watch/src/movie/{$movie->slug}")->assertNotFound();
    }

    public function test_admin_can_stream_an_unpublished_title(): void
    {
        $movie = $this->movie(tier: null, status: Movie::STATUS_DRAFT);

        $this->actingAs($this->admin())->get("/watch/src/movie/{$movie->slug}")
            ->assertRedirect('https://cdn.example.test/movie.mp4');
    }

    // ── fixtures ─────────────────────────────────────────────────────

    /**
     * MovieFactory rolls `published_at` from its own random published/draft
     * decision, so overriding `status` alone leaves the date null half the
     * time and isPubliclyVisible() fails at random. Both are set here.
     */
    private function movie(?string $tier, string $status = Movie::STATUS_PUBLISHED): Movie
    {
        return Movie::factory()->create([
            'status' => $status,
            'published_at' => $status === Movie::STATUS_PUBLISHED ? now()->subDay() : null,
            'tier_required' => $tier,
            'video_url' => 'https://cdn.example.test/movie.mp4',
        ]);
    }

    private function episode(?string $showTier, ?string $episodeTier = null): Episode
    {
        $show = Show::create([
            'title' => 'Pinned Show ' . uniqid(),
            'slug' => 'pinned-show-' . uniqid(),
            'status' => 'published',
            'tier_required' => $showTier,
        ]);

        $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'Season 1']);

        return Episode::create([
            'season_id' => $season->id,
            'number' => 1,
            'title' => 'Episode 1',
            'tier_required' => $episodeTier,
            'video_url' => 'https://cdn.example.test/ep1.mp4',
        ]);
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function giveSub(User $user, SubscriptionTier $tier): UserSubscription
    {
        return UserSubscription::create([
            'user_id' => $user->id,
            'subscription_tier_id' => $tier->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'auto_renew' => true,
        ]);
    }

    private function seedActiveStream(User $user, Movie $movie, string $sessionId): void
    {
        \Modules\Streaming\app\Models\ActiveStream::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'last_beat_at' => now(),
            'terminated_at' => null,
        ]);
    }
}
