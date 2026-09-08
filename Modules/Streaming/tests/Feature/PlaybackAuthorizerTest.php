<?php

namespace Modules\Streaming\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Streaming\app\Models\ActiveStream;
use Modules\Streaming\app\Playback\PlaybackDenial;
use Modules\Streaming\app\Services\PlaybackAuthorizer;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The shared entitlement service, tested directly.
 *
 * PlaybackAuthorizationPinTest proves the HTTP routes behave as they did
 * before the extraction. This file covers what the routes cannot express:
 * the denial reasons the /api/v1 mobile endpoints branch on, and the two
 * places where the old copies disagreed with each other and the extraction
 * had to pick a winner.
 */
class PlaybackAuthorizerTest extends TestCase
{
    use RefreshDatabase;

    private PlaybackAuthorizer $authorizer;
    private SubscriptionTier $basic;
    private SubscriptionTier $premium;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizer = app(PlaybackAuthorizer::class);

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

    // ── the codes the app branches on ────────────────────────────────

    public function test_a_guest_on_gated_content_is_told_to_log_in(): void
    {
        $decision = $this->authorizer->authorize($this->movie('premium'), null);

        $this->assertTrue($decision->denied());
        $this->assertSame('LOGIN_REQUIRED', $decision->code());
    }

    public function test_a_viewer_with_no_plan_is_told_to_subscribe(): void
    {
        $decision = $this->authorizer->authorize($this->movie('premium'), User::factory()->create());

        $this->assertSame('SUBSCRIPTION_REQUIRED', $decision->code());
        $this->assertSame('Premium', $decision->requiredTier?->name);
    }

    public function test_a_viewer_on_a_lower_plan_is_told_to_upgrade(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic);

        $decision = $this->authorizer->authorize($this->movie('premium'), $user);

        $this->assertSame('UPGRADE_REQUIRED', $decision->code());
    }

    /**
     * Both plan denials render as one 403 with one sentence on the web,
     * which is what the site did before the codes were split apart.
     */
    public function test_both_plan_denials_share_the_web_message(): void
    {
        $noPlan = User::factory()->create();
        $lowPlan = User::factory()->create();
        $this->giveSub($lowPlan, $this->basic);
        $movie = $this->movie('premium');

        $a = $this->authorizer->authorize($movie, $noPlan);
        $b = $this->authorizer->authorize($movie, $lowPlan);

        $this->assertTrue($a->needsBetterPlan());
        $this->assertTrue($b->needsBetterPlan());
        $this->assertSame('This requires a Premium subscription.', $a->message());
        $this->assertSame($a->message(), $b->message());
    }

    public function test_an_entitled_viewer_at_the_cap_gets_the_stream_limit_code(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic); // cap of 1
        $movie = $this->movie('basic');
        $this->seedStream($user, $movie, 'someOtherDeviceSessionKey0123456789abcdef');

        $decision = $this->authorizer->authorize($movie, $user, 'thisDeviceSessionKey');

        $this->assertSame('STREAM_LIMIT', $decision->code());
    }

    /**
     * A page asking "should the Play button be enabled" is not starting a
     * stream, so it passes no session key and must not be refused for a cap
     * the viewer has not hit yet.
     */
    public function test_omitting_the_session_key_skips_the_cap(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic);
        $movie = $this->movie('basic');
        $this->seedStream($user, $movie, 'someOtherDeviceSessionKey0123456789abcdef');

        $this->assertTrue($this->authorizer->authorize($movie, $user)->allowed);
    }

    // ── R1: an unknown tier slug ─────────────────────────────────────

    /**
     * A tier_required slug matching no tier row is a data error. TierGate
     * treated it as free and streamed the bytes; userCanWatch() refused
     * guests, so the page and the player disagreed. The service follows
     * TierGate, which is the security boundary and was already serving the
     * file — so this closes a contradiction rather than opening anything.
     *
     * Reverse this and the pricing/preview tests that lean on it if the
     * ruling should instead be "an unresolvable slug fails closed".
     */
    public function test_an_unknown_tier_slug_is_treated_as_free_including_for_guests(): void
    {
        $movie = $this->movie('a-slug-no-tier-has');

        $this->assertTrue($this->authorizer->authorize($movie, null)->allowed);
    }

    public function test_the_signup_switch_still_beats_an_unknown_tier_slug(): void
    {
        setting(['require_signup_to_watch', 1]);
        $movie = $this->movie('a-slug-no-tier-has');

        $decision = $this->authorizer->authorize($movie, null);

        $this->assertSame('LOGIN_REQUIRED', $decision->code());
    }

    // ── R2: the cap on an inherited episode plan ─────────────────────

    /**
     * The shape the admin Shows form produces: the plan sits on the series
     * and every episode row leaves tier_required null.
     * FrontendController::concurrencyExceeded() read the episode's own column
     * and so skipped the cap entirely for these — the normal case. TierGate
     * applied it to the stream URL, so the viewer got a player that refused
     * to load with no explanation. The service caps them and says why.
     */
    public function test_an_episode_inheriting_its_show_plan_is_capped(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic); // cap of 1
        $episode = $this->episode(showTier: 'basic');

        $capped = $this->movie('basic');
        $this->seedStream($user, $capped, 'anotherDeviceSessionKey9876543210zyxwvut');

        $decision = $this->authorizer->authorize($episode, $user, 'thisDeviceSessionKey');

        $this->assertSame('STREAM_LIMIT', $decision->code());
    }

    public function test_the_governing_slug_falls_back_to_the_show(): void
    {
        $this->assertSame('premium', $this->authorizer->requiredTierSlug($this->episode('premium')));
        $this->assertSame('basic', $this->authorizer->requiredTierSlug($this->episode('premium', 'basic')));
        $this->assertNull($this->authorizer->requiredTierSlug($this->episode(null)));
    }

    // ── release state ────────────────────────────────────────────────

    public function test_a_draft_title_is_not_released(): void
    {
        $this->assertFalse(
            $this->authorizer->isReleased($this->movie(null, Movie::STATUS_DRAFT), User::factory()->create())
        );
    }

    public function test_an_admin_sees_a_draft_as_released(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue(
            $this->authorizer->isReleased($this->movie(null, Movie::STATUS_DRAFT), $admin)
        );
    }

    /**
     * An unreleased title must answer "unavailable", never "you need
     * Premium" — the tier of something nobody can see yet is not the
     * viewer's business.
     */
    public function test_authorize_stream_reports_an_unreleased_title_as_unavailable_not_as_gated(): void
    {
        $movie = $this->movie('premium', Movie::STATUS_DRAFT);

        $decision = $this->authorizer->authorizeStream($movie, null);

        $this->assertTrue($decision->is(PlaybackDenial::ContentUnavailable));
        $this->assertSame('CONTENT_UNAVAILABLE', $decision->code());
    }

    // ── fixtures ─────────────────────────────────────────────────────

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
            'title' => 'Service Show ' . uniqid(),
            'slug' => 'service-show-' . uniqid(),
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

    private function seedStream(User $user, Movie $movie, string $sessionKey): void
    {
        ActiveStream::create([
            'user_id' => $user->id,
            'session_id' => $sessionKey,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'last_beat_at' => now(),
            'terminated_at' => null,
        ]);
    }
}
