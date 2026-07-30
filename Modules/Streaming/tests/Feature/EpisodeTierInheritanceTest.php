<?php

namespace Modules\Streaming\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Tests\TestCase;

/**
 * Episodes must inherit their series' plan when they carry none of their own.
 *
 * The bug this pins: TierGate read only `$episode->tier_required` and let a
 * NULL through as free, while FrontendController::userCanWatch() applied the
 * parent-show fallback. So a Premium series whose episode rows were NULL —
 * the normal state, because the Shows form sets the plan on the series —
 * refused on the rich /watch page but streamed for free via
 * /player/episode/{id} and /watch/src/episode/{id}, which TierGate guards.
 *
 * Guests are the sharpest version of the case: no subscription at all, yet
 * the bare player handed over a premium episode.
 */
class EpisodeTierInheritanceTest extends TestCase
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
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    /**
     * A premium series whose episode rows carry no plan of their own — the
     * shape the admin Shows form produces.
     */
    private function episodeUnderShow(?string $showTier, ?string $episodeTier = null): Episode
    {
        $show = Show::create([
            'title' => 'Gated Show ' . uniqid(),
            'slug' => 'gated-show-' . uniqid(),
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

    public function test_guest_cannot_stream_an_untiered_episode_of_a_premium_series(): void
    {
        $episode = $this->episodeUnderShow('premium');

        $this->get("/player/episode/{$episode->id}")
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_reach_the_stream_source_of_a_premium_series_episode(): void
    {
        $episode = $this->episodeUnderShow('premium');

        $this->get("/watch/src/episode/{$episode->id}")
            ->assertRedirect(route('login'));
    }

    public function test_subscriber_below_the_series_tier_is_refused(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic);

        $episode = $this->episodeUnderShow('premium');

        $this->actingAs($user)
            ->get("/player/episode/{$episode->id}")
            ->assertForbidden();
    }

    public function test_subscriber_at_the_series_tier_can_watch(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->premium);

        $episode = $this->episodeUnderShow('premium');

        $this->actingAs($user)
            ->get("/player/episode/{$episode->id}")
            ->assertOk();
    }

    public function test_an_episode_of_a_free_series_stays_open_to_guests(): void
    {
        // The fallback must not accidentally gate free content.
        $episode = $this->episodeUnderShow(null);

        $this->get("/player/episode/{$episode->id}")->assertOk();
    }

    public function test_an_explicit_episode_plan_still_wins_over_the_series_plan(): void
    {
        // A deliberately free pilot inside a premium run stays free: the
        // fallback only applies when the episode has no plan of its own.
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic);

        $episode = $this->episodeUnderShow('premium', 'basic');

        $this->actingAs($user)
            ->get("/player/episode/{$episode->id}")
            ->assertOk();
    }
}
