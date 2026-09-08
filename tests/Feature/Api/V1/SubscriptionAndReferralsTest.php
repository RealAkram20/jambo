<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Referrals\app\Services\ReferralSettings;
use Modules\Streaming\app\Models\Device;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Tests\TestCase;

/**
 * Gaps 4 and 8 of docs/api/coverage.md: plans and billing, refer and earn.
 *
 * Both are read-only, and deliberately so. ADR-0004 makes the Play build
 * consumption-only, so it shows a plan and a renewal date and sells nothing;
 * in-app checkout belongs to the direct APK and has not been built, because
 * reusing the website's order creation safely means extracting money code —
 * server-authored price, frozen snapshot, referral discount — into a shared
 * service, and that earns its own slice. Withdrawal is money leaving the
 * business and is out for the same reason.
 */
class SubscriptionAndReferralsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    // ── plans ────────────────────────────────────────────────────────

    /** Public: the Play build must show what a plan costs without selling it. */
    public function test_plans_are_public(): void
    {
        $this->tier('Premium', 'premium', 30000, 2);

        $this->getJson('/api/v1/plans')
            ->assertOk()
            ->assertJsonPath('data.plans.0.slug', 'premium')
            ->assertJsonPath('data.plans.0.max_concurrent_streams', 2);
    }

    public function test_inactive_plans_are_not_offered(): void
    {
        $tier = $this->tier('Retired', 'retired', 5000, 1);
        $tier->forceFill(['is_active' => false])->save();

        $this->getJson('/api/v1/plans')
            ->assertOk()
            ->assertJsonCount(0, 'data.plans');
    }

    /** null means the plan sets no cap. Render "unlimited", never "0". */
    public function test_an_uncapped_plan_reports_null_rather_than_zero(): void
    {
        $tier = $this->tier('Ultra', 'ultra', 50000, 4);
        $tier->forceFill(['max_concurrent_streams' => null])->save();

        $this->getJson('/api/v1/plans')
            ->assertOk()
            ->assertJsonPath('data.plans.0.max_concurrent_streams', null);
    }

    // ── subscription ─────────────────────────────────────────────────

    public function test_the_subscription_screen_needs_a_viewer(): void
    {
        $this->getJson('/api/v1/subscription')->assertStatus(401);
    }

    public function test_a_viewer_with_no_plan_gets_null_and_still_gets_the_plan_list(): void
    {
        $this->tier('Premium', 'premium', 30000, 2);
        $token = $this->signIn($this->viewer());

        $response = $this->withFreshToken($token)->getJson('/api/v1/subscription')->assertOk();

        $this->assertNull($response->json('data.subscription'));
        // So an upgrade prompt can be drawn from one request.
        $this->assertCount(1, $response->json('data.plans'));
    }

    public function test_a_subscriber_sees_their_plan_and_renewal_date(): void
    {
        $tier = $this->tier('Premium', 'premium', 30000, 2);
        $user = $this->viewer();
        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_tier_id' => $tier->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'auto_renew' => true,
        ]);

        $this->withFreshToken($this->signIn($user))->getJson('/api/v1/subscription')
            ->assertOk()
            ->assertJsonPath('data.subscription.tier.slug', 'premium')
            ->assertJsonPath('data.subscription.auto_renew', true)
            ->assertJsonStructure(['data' => ['subscription' => ['ends_at']]]);
    }

    // ── billing history ──────────────────────────────────────────────

    /**
     * An invoice must say what was actually paid, not what the plan costs
     * today, so the amount comes off the order row.
     */
    public function test_billing_history_reports_the_amount_that_was_paid(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);
        $this->order($user, 'REF-0001', '25000.00', 'completed');

        $this->withFreshToken($token)->getJson('/api/v1/subscription/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.reference', 'REF-0001')
            ->assertJsonPath('data.items.0.status', 'completed');

        $this->withFreshToken($token)->getJson('/api/v1/subscription/orders/REF-0001')
            ->assertOk()
            ->assertJsonPath('data.order.amount', '25000.00');
    }

    public function test_one_viewers_orders_are_not_anothers(): void
    {
        $mine = $this->viewer();
        $theirs = $this->viewer();
        $token = $this->signIn($mine, 'device-uuid-mine01');
        $this->order($theirs, 'REF-THEIRS', '10000.00', 'completed');

        $this->withFreshToken($token)->getJson('/api/v1/subscription/orders')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this->withFreshToken($token)->getJson('/api/v1/subscription/orders/REF-THEIRS')
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    // ── refer and earn ───────────────────────────────────────────────

    public function test_refer_and_earn_is_reachable_when_the_programme_is_on(): void
    {
        setting(['referrals.active', '1']);
        ReferralSettings::flush();
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)->getJson('/api/v1/referrals')
            ->assertOk()
            ->assertJsonStructure(['data' => ['code', 'share_url', 'stats', 'terms']]);
    }

    public function test_the_wallet_reports_a_balance_and_a_ledger(): void
    {
        setting(['referrals.active', '1']);
        ReferralSettings::flush();
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonStructure(['data' => ['currency', 'balance', 'min_withdrawal', 'entries']]);
    }

    /**
     * The referral code shares a namespace with usernames, so a code that
     * collides with somebody's username would break their profile URL.
     */
    public function test_a_referral_code_cannot_collide_with_a_username(): void
    {
        setting(['referrals.active', '1']);
        ReferralSettings::flush();
        User::factory()->create(['username' => 'takenname']);
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)->putJson('/api/v1/referrals/code', ['code' => 'takenname'])
            ->assertStatus(422);

        $this->withFreshToken($token)->putJson('/api/v1/referrals/code', ['code' => 'myowncode'])
            ->assertOk()
            ->assertJsonPath('data.code', 'myowncode');
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function tier(string $name, string $slug, int $price, int $level): SubscriptionTier
    {
        return SubscriptionTier::create([
            'name' => $name,
            'slug' => $slug,
            'price' => $price,
            'currency' => 'UGX',
            'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
            'access_level' => $level,
            'max_concurrent_streams' => $level,
            'is_active' => true,
            'sort_order' => $level,
        ]);
    }

    private function order(User $user, string $ref, string $amount, string $status): PaymentOrder
    {
        return PaymentOrder::create([
            'user_id' => $user->id,
            'merchant_reference' => $ref,
            'amount' => $amount,
            'currency' => 'UGX',
            'description' => 'Jambo — Premium',
            'status' => $status,
        ]);
    }

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function signIn(User $user, string $uuid = 'device-uuid-subs01'): string
    {
        RateLimiter::clear(strtolower($user->email) . '|127.0.0.1');

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => ['uuid' => $uuid, 'platform' => Device::PLATFORM_ANDROID],
        ])->assertOk()->json('data.token');
    }

    private function withFreshToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
