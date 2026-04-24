<?php

namespace Modules\Streaming\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\Movie;
use Modules\Streaming\app\Models\ActiveStream;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Tests\TestCase;

/**
 * Covers the device-limit feature: cap enforcement, picker boot, heartbeat
 * kick detection, and the next-tier-up upgrade helper. The scenarios here
 * are the user-facing behaviours anyone doing a manual QA pass would walk
 * through — they're the tripwires for the feature regressing.
 *
 * Free content is verified separately from premium so a regression in
 * `whereNotNull('tier_required')` on activeStreamCount surfaces immediately
 * instead of being hidden behind a broader integration pass.
 */
class DeviceLimitTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionTier $basic;
    private SubscriptionTier $standard;

    protected function setUp(): void
    {
        parent::setUp();

        // Tier ladder used across every case. Basic = 1 device cap,
        // Standard = 2, Premium = 4 — mirrors a typical SVOD pricing
        // page. Access levels ascend so TierGate's >= check passes.
        $this->basic = SubscriptionTier::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price' => 5000,
            'currency' => 'UGX',
            'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
            'access_level' => SubscriptionTier::ACCESS_BASIC,
            'max_concurrent_streams' => 1,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->standard = SubscriptionTier::create([
            'name' => 'Standard',
            'slug' => 'premium', // intentionally matches a content tier_required slug
            'price' => 10000,
            'currency' => 'UGX',
            'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
            'access_level' => SubscriptionTier::ACCESS_PREMIUM,
            'max_concurrent_streams' => 2,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    public function test_user_at_cap_is_redirected_to_picker_on_premium_content(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic); // 1 device cap

        $movie = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'tier_required' => 'basic',
        ]);

        // Another device is already streaming premium content.
        $this->seedActiveStream($user, $movie, sessionId: 'otherDeviceSessionAbcdefghij0123456789ABCD');

        $response = $this->actingAs($user)->get("/player/movie/{$movie->slug}");

        $response->assertRedirect(route('streams.limit'));
    }

    public function test_user_at_cap_can_still_watch_free_content(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic);

        $premium = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'tier_required' => 'basic',
        ]);
        $this->seedActiveStream($user, $premium, sessionId: 'otherDeviceSessionZzzzzzzzzz0987654321ZZZZ');

        $freeMovie = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'tier_required' => null, // free tier
        ]);

        $response = $this->actingAs($user)->get("/player/movie/{$freeMovie->slug}");

        // TierGate's first branch lets free content through before the
        // cap check even runs.
        $response->assertStatus(200);
    }

    public function test_booting_a_session_terminates_it_and_frees_the_cap(): void
    {
        $user = User::factory()->create();
        $this->giveSub($user, $this->basic);

        $movie = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'tier_required' => 'basic',
        ]);
        $sid = 'deviceToKickAbcdefghijklmnop123456789abcde';
        $this->seedActiveStream($user, $movie, sessionId: $sid);

        // Seed a Laravel session row too — the boot endpoint now deletes
        // this as its primary kill mechanism (account-level logout).
        $this->seedLaravelSession($user, $sid);

        $this->assertSame(1, ActiveStream::activeCount($user->id));
        $this->assertSame(1, DB::table('sessions')->where('id', $sid)->count());

        $response = $this->actingAs($user)
            ->postJson('/streams/boot/deviceToKickAbcdefghijklmnop123456789abcde');

        $response->assertOk()->assertJson(['ok' => true]);

        // The target's Laravel session is GONE — their browser's cookie
        // now points at nothing, so next request is guest.
        $this->assertSame(0, DB::table('sessions')->where('id', $sid)->count());

        // active_streams still has the row but flagged terminated —
        // heartbeat check uses this to fire the "signed out" overlay
        // before the session-cookie side effect bounces them to login.
        $this->assertSame(0, ActiveStream::activeCount($user->id));
    }

    // Self-boot ("Sign out here") is covered by manual QA. The
    // self-branch hinges on hash_equals($request->session()->getId(),
    // $sessionId), and Laravel's HTTP test client doesn't preserve
    // session ids across requests — the captured id from get('/')
    // never matches the id on the subsequent postJson. The main
    // "boot deletes the Laravel session" behaviour is verified by
    // test_booting_a_session_terminates_it_and_frees_the_cap above.

    public function test_terminate_session_marks_all_rows_for_that_session(): void
    {
        // ActiveStream::terminateSession is the ground-truth behind
        // the boot endpoint + "kicked device" heartbeat detection.
        // Model-level so we don't fight Laravel's test client, which
        // doesn't preserve session IDs across its HTTP calls (the
        // route's controller test is covered by the endpoint test
        // above that hits /streams/boot directly).
        $user = User::factory()->create();

        $movieA = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED, 'tier_required' => 'basic',
        ]);
        $movieB = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED, 'tier_required' => 'basic',
        ]);

        // Same session watched two premium titles; both active_streams
        // rows should terminate so the kicked device can't hop content
        // to stay streaming.
        $sid = 'targetSessionAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
        $this->seedActiveStream($user, $movieA, sessionId: $sid);
        $this->seedActiveStream($user, $movieB, sessionId: $sid);

        $terminated = ActiveStream::terminateSession($user->id, $sid);

        $this->assertSame(2, $terminated);
        $this->assertSame(0, ActiveStream::activeCount($user->id));
    }

    public function test_two_devices_watching_the_same_title_both_count_against_cap(): void
    {
        // Regression guard for the ping-pong bug. active_streams is
        // keyed (user, session, content) so this scenario produces
        // two rows and the cap check sees both. The old watch_history
        // variant failed here — it had one row per (user, content)
        // and the session_id bounced, showing 1 active stream.
        $user = User::factory()->create();

        $movie = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED, 'tier_required' => 'basic',
        ]);

        $this->seedActiveStream($user, $movie, sessionId: 'deviceAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
        $this->seedActiveStream($user, $movie, sessionId: 'deviceBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB');

        $this->assertSame(2, ActiveStream::activeCount($user->id));
    }

    public function test_booting_a_session_does_not_affect_other_devices_of_the_same_title(): void
    {
        // Second regression guard: booting device A must not kill
        // device B even when both are on the same title. active_streams
        // rows are per-session, so the terminated_at flag only lands
        // on A's row.
        $user = User::factory()->create();

        $movie = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED, 'tier_required' => 'basic',
        ]);

        $sidA = 'deviceAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
        $sidB = 'deviceBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB';
        $this->seedActiveStream($user, $movie, sessionId: $sidA);
        $this->seedActiveStream($user, $movie, sessionId: $sidB);

        ActiveStream::terminateSession($user->id, $sidA);

        $aRow = ActiveStream::where('user_id', $user->id)->where('session_id', $sidA)->first();
        $bRow = ActiveStream::where('user_id', $user->id)->where('session_id', $sidB)->first();

        $this->assertNotNull($aRow->terminated_at);
        $this->assertNull($bRow->terminated_at);
        $this->assertSame(1, ActiveStream::activeCount($user->id));
    }

    public function test_active_stream_count_ignores_terminated_rows(): void
    {
        // The filter keeps booted devices out of the cap calculation
        // immediately (vs. waiting out the 90s heartbeat window).
        // This is what lets the picker's "disconnect" flow feel
        // instantaneous to the user.
        $user = User::factory()->create();

        $movie = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED, 'tier_required' => 'basic',
        ]);

        // One fresh, one terminated — same user, different sessions.
        $this->seedActiveStream($user, $movie, sessionId: 'aliveSessionAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');

        $deadMovie = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED, 'tier_required' => 'basic',
        ]);
        ActiveStream::create([
            'user_id' => $user->id,
            'session_id' => 'deadSessionBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB',
            'watchable_type' => $deadMovie->getMorphClass(),
            'watchable_id' => $deadMovie->id,
            'last_beat_at' => now()->subSeconds(5),
            'terminated_at' => now()->subSeconds(2),
        ]);

        $this->assertSame(1, ActiveStream::activeCount($user->id));
    }

    public function test_next_tier_up_from_suggests_cheapest_larger_cap(): void
    {
        // Currently on 1-device cap → next up is the 2-device tier.
        $suggestion = SubscriptionTier::nextTierUpFrom(1);

        $this->assertNotNull($suggestion);
        $this->assertSame($this->standard->id, $suggestion->id);
    }

    public function test_next_tier_up_from_returns_null_when_already_unlimited(): void
    {
        // A user on an "unlimited" tier (max_concurrent_streams IS NULL)
        // has nothing to upgrade to; picker falls back to generic CTA.
        $suggestion = SubscriptionTier::nextTierUpFrom(null);

        $this->assertNull($suggestion);
    }

    /**
     * Attach an active UserSubscription row for a given tier. Not using a
     * factory because there isn't one yet and the column set here is small
     * + tied to test intent.
     */
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

    /**
     * Seed a fresh active premium stream on `active_streams` without
     * going through the heartbeat endpoint. Used to set up "you're
     * already at cap" scenarios where a second device tries to start
     * playback.
     *
     * We write to active_streams (concurrency ground truth) and NOT
     * watch_history — the two are now orthogonal: watch_history owns
     * resume + view counts, active_streams owns the session-level
     * concurrency signal. Heartbeat writes to both in production.
     */
    private function seedActiveStream(User $user, Movie $movie, string $sessionId): ActiveStream
    {
        return ActiveStream::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'last_beat_at' => now()->subSeconds(10),
        ]);
    }

    /**
     * Seed a row in Laravel's sessions table for a user+session. The
     * account-level boot endpoint reads + deletes these rows; seeding
     * one lets us assert post-boot that the session was truly killed.
     */
    private function seedLaravelSession(User $user, string $sessionId): void
    {
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'ProbeAgent/1.0',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);
    }
}
