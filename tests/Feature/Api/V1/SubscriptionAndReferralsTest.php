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

    /**
     * The bullet list under the price, which the endpoint withheld until
     * 2026-09-09.
     *
     * The column has backed the website's pricing cards all along. Without it
     * the app's membership screen is a price ladder with no stated reason to
     * climb it, and the only alternatives are showing less than the website
     * or inventing bullets the admin never wrote.
     */
    public function test_a_plan_carries_the_features_the_website_renders(): void
    {
        $tier = $this->tier('Premium', 'premium', 30000, 2);
        $tier->forceFill(['features' => ['Everything in Basic', '4K Ultra HD', '4 devices']])->save();

        $this->getJson('/api/v1/plans')
            ->assertOk()
            ->assertJsonPath('data.plans.0.features', ['Everything in Basic', '4K Ultra HD', '4 devices'])
            ->assertJsonPath('data.plans.0.period_label', 'per month');
    }

    /**
     * An admin who has written no features gets an empty list, not a null.
     *
     * A client guarding a nullable array is a client that will one day render
     * "null" into a card, and the distinction between "no features" and "this
     * field was not sent" is one no screen needs to draw.
     */
    public function test_a_plan_with_no_features_reports_an_empty_list(): void
    {
        $this->tier('Bare', 'bare', 1000, 1);

        $this->getJson('/api/v1/plans')
            ->assertOk()
            ->assertJsonPath('data.plans.0.features', []);
    }

    /**
     * A features column with gaps in its keys still leaves as a JSON array.
     *
     * **Written because the mutation did not fail.** Removing `array_values`
     * and the `is_array` check left both feature tests green — the null case
     * was covered by `?? []` and nothing was asking the other half anything.
     * That is jambo-49's finding of 2026-09-10 in another module: when several
     * guards share one fallback, a test can pass without being able to say
     * which guard answered.
     *
     * The case it protects is real rather than theoretical. An admin editing
     * this JSON by hand, or any write that deletes one entry from the middle,
     * leaves `[0 => 'A', 2 => 'B']` — which `json_encode` emits as an OBJECT.
     * The client's type says `string[]`, so the app would receive
     * `{"0":"A","2":"B"}` where it expects a list, and the card would render
     * no bullets at all while the endpoint looked entirely healthy.
     */
    public function test_features_with_gapped_keys_are_reindexed_into_a_list(): void
    {
        $tier = $this->tier('Patched', 'patched', 20000, 1);
        $tier->forceFill(['features' => [0 => 'First', 2 => 'Third']])->save();

        $features = $this->getJson('/api/v1/plans')->assertOk()->json('data.plans.0.features');

        $this->assertSame(['First', 'Third'], $features);
        $this->assertSame([0, 1], array_keys($features), 'A list, not an object with numeric keys.');
    }

    /**
     * The pill lands on exactly one plan, and on the same plan the website
     * marks: the highest access level among monthly paid tiers.
     *
     * Both surfaces call `SubscriptionTier::popularFrom`, which is the point
     * of the test — it fails the day somebody re-implements the rule in one
     * of them.
     */
    public function test_one_plan_is_marked_popular_and_free_is_never_it(): void
    {
        $free = $this->tier('Free', 'free', 0, 0);
        $free->forceFill(['access_level' => 0])->save();
        $this->tier('Basic', 'basic', 15000, 1);
        $this->tier('Premium', 'premium', 30000, 2);

        $response = $this->getJson('/api/v1/plans')->assertOk();

        $popular = collect($response->json('data.plans'))->where('is_popular', true);

        $this->assertCount(1, $popular, 'Exactly one plan wears the pill.');
        $this->assertSame('premium', $popular->first()['slug']);
    }

    /**
     * A catalogue with no monthly plan still marks a winner.
     *
     * **Written because deleting the fallback failed nothing.** Every other
     * fixture in this file builds monthly tiers, so the `?? highest paid tier`
     * branch could not run and `return $monthly;` alone passed all 23 cases.
     * jambo-49's addition to the rule on 2026-09-10: when a mutation does not
     * fail, suspect the fixture as much as the assertion — mine asserted the
     * right thing about data that could never have shown the fault.
     *
     * The branch is not decorative. The website's own comment says an
     * all-yearly catalogue should still get a visual winner, and an admin who
     * sells only weekly and yearly plans is an admin this rule has to answer
     * for.
     */
    public function test_a_catalogue_with_no_monthly_plan_still_marks_one_popular(): void
    {
        $weekly = $this->tier('Weekly', 'weekly', 6000, 1);
        $weekly->forceFill(['billing_period' => SubscriptionTier::PERIOD_WEEKLY])->save();

        $yearly = $this->tier('Yearly Premium', 'yearly-premium', 300000, 2);
        $yearly->forceFill(['billing_period' => SubscriptionTier::PERIOD_YEARLY])->save();

        $popular = collect($this->getJson('/api/v1/plans')->assertOk()->json('data.plans'))
            ->where('is_popular', true);

        $this->assertCount(1, $popular, 'A catalogue with no monthly plan still has a winner.');
        $this->assertSame('yearly-premium', $popular->first()['slug']);
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

    /**
     * An order has to say what was bought.
     *
     * The plan name lives on `payable`, and **`payable` IS the tier** rather
     * than a row that has one. The website's own billing and invoice blades
     * read `payable->tier->name` and its controller eager-loaded
     * `payable.tier`, which threw for every account that had ever paid; this
     * is the API half of that repair.
     *
     * `payment_method` and `tracking_id` are asserted alongside it because
     * the website's invoice prints both, and an invoice a viewer cannot
     * reconcile against a bank statement is not an invoice.
     */
    public function test_an_order_names_the_plan_that_was_bought(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);
        $tier = $this->tier('Premium', 'premium', 30000, 2);

        $order = $this->order($user, 'REF-PLAN01', '30000.00', 'completed');
        $order->forceFill([
            'payable_type' => $tier->getMorphClass(),
            'payable_id' => $tier->getKey(),
            'payment_method' => 'mpesa',
            'order_tracking_id' => 'PSP-1234',
        ])->save();

        $this->withFreshToken($token)->getJson('/api/v1/subscription/orders')
            ->assertOk()
            ->assertJsonPath('data.items.0.plan.name', 'Premium')
            ->assertJsonPath('data.items.0.plan.billing_period', SubscriptionTier::PERIOD_MONTHLY);

        $this->withFreshToken($token)->getJson('/api/v1/subscription/orders/REF-PLAN01')
            ->assertOk()
            ->assertJsonPath('data.order.plan.name', 'Premium')
            ->assertJsonPath('data.order.payment_method', 'mpesa')
            ->assertJsonPath('data.order.tracking_id', 'PSP-1234');
    }

    /**
     * `payable` is polymorphic and is meant to carry rentals and merchandise
     * later, so an order that is not a plan purchase has no `plan` block at
     * all. Null, not an empty object and not the string "—": the client
     * decides what absence looks like.
     *
     * **Two cases, and the second is the one that matters.** An order with NO
     * payable proves nothing about the `instanceof` guard — the property is
     * null either way, so removing the guard leaves this test green. Written
     * that way first, and the mutation harness caught it. The second case
     * gives the order a payable that is a real model and is not a tier, which
     * is the only shape that fails when the guard goes.
     */
    public function test_an_order_that_is_not_a_plan_has_no_plan_block(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);
        $this->order($user, 'REF-NOPLAN', '2500.00', 'completed');

        $this->withFreshToken($token)->getJson('/api/v1/subscription/orders/REF-NOPLAN')
            ->assertOk()
            ->assertJsonPath('data.order.plan', null)
            ->assertJsonPath('data.order.payment_method', null)
            ->assertJsonPath('data.order.tracking_id', null);

        // A payable that exists and is not a plan. Without the instanceof
        // guard this emits whatever `name` that model happens to have, and
        // the app prints a stranger's account as the plan that was bought.
        $notATier = $this->order($user, 'REF-NOTTIER', '400.00', 'completed');
        $notATier->forceFill([
            'payable_type' => $user->getMorphClass(),
            'payable_id' => $user->getKey(),
        ])->save();

        $this->withFreshToken($token)->getJson('/api/v1/subscription/orders/REF-NOTTIER')
            ->assertOk()
            ->assertJsonPath('data.order.plan', null);
    }

    /**
     * `description` was published in the contract and no column ever backed
     * it, so it was null on every order in the table's life. It is gone, and
     * this pins that it stays gone rather than being reintroduced by somebody
     * reading the old spec.
     */
    public function test_the_dead_description_field_is_not_published(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);
        $this->order($user, 'REF-NODESC', '1000.00', 'completed');

        $card = $this->withFreshToken($token)
            ->getJson('/api/v1/subscription/orders/REF-NODESC')
            ->assertOk()
            ->json('data.order');

        $this->assertArrayNotHasKey('description', $card);
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
    /**
     * 🔴 The defect this slice found.
     *
     * The profile hub lives at `/{username}`, so a referral code is also a URL
     * segment. `ProfileHubController::updateReferralCode` has always applied
     * `ReservedUsername`; the API's `updateCode` did not, so the mobile app
     * could take a code the router already owns and the resulting referral
     * link would resolve to a real page instead of the referrer.
     *
     * Both now go through `ReferralCodeRules`.
     */
    public function test_the_api_refuses_a_referral_code_the_router_owns(): void
    {
        setting(['referrals.active', '1']);
        ReferralSettings::flush();
        $user = $this->viewer();
        $token = $this->signIn($user);

        foreach (['login', 'movies', 'watch', 'search'] as $reserved) {
            $this->withFreshToken($token)
                ->putJson('/api/v1/referrals/code', ['code' => $reserved])
                ->assertStatus(422);
        }

        $this->assertNotSame('login', $user->fresh()->referral_code);
    }

    public function test_the_api_accepts_a_free_referral_code(): void
    {
        setting(['referrals.active', '1']);
        ReferralSettings::flush();
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)
            ->putJson('/api/v1/referrals/code', ['code' => 'jambo-rio-207'])
            ->assertOk()
            ->assertJsonPath('data.code', 'jambo-rio-207');

        $this->assertSame('jambo-rio-207', $user->fresh()->referral_code);
    }

    /**
     * Availability must never say yes to something the save would reject.
     *
     * That promise is why the rules and the check live in one class. The
     * website's own check carried it as a comment while the API had no check
     * at all and a weaker save.
     */
    public function test_availability_agrees_with_what_the_save_would_do(): void
    {
        setting(['referrals.active', '1']);
        ReferralSettings::flush();
        $user = $this->viewer();
        $token = $this->signIn($user);

        $cases = [
            'login' => false,       // reserved route
            'ab' => false,          // too short
            'has spaces' => false,  // bad shape
            'jambo-rio-207' => true,
        ];

        foreach ($cases as $code => $expected) {
            $this->withFreshToken($token)
                ->postJson('/api/v1/referrals/code/check', ['code' => $code])
                ->assertOk()
                ->assertJsonPath('data.available', $expected);

            // And the save agrees, which is the whole point.
            $save = $this->withFreshToken($token)
                ->putJson('/api/v1/referrals/code', ['code' => $code]);

            $expected ? $save->assertOk() : $save->assertStatus(422);
        }
    }

    public function test_a_taken_code_is_reported_unavailable(): void
    {
        setting(['referrals.active', '1']);
        ReferralSettings::flush();
        $other = $this->viewer();
        $other->forceFill(['referral_code' => 'takenalready'])->save();

        $token = $this->signIn($this->viewer(), 'device-uuid-ref02');

        $this->withFreshToken($token)
            ->postJson('/api/v1/referrals/code/check', ['code' => 'takenalready'])
            ->assertOk()
            ->assertJsonPath('data.available', false);
    }

    /**
     * Re-checking the code you already hold is available, not taken.
     *
     * Without this the Save button disables itself the moment somebody edits
     * their code and types it back — the website's own JS works around it on
     * the client, and the server should simply be right.
     */
    public function test_your_own_current_code_is_available_to_you(): void
    {
        setting(['referrals.active', '1']);
        ReferralSettings::flush();
        $user = $this->viewer();
        $user->forceFill(['referral_code' => 'minealready'])->save();
        $token = $this->signIn($user);

        $this->withFreshToken($token)
            ->postJson('/api/v1/referrals/code/check', ['code' => 'minealready'])
            ->assertOk()
            ->assertJsonPath('data.available', true);
    }

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

    /*
     * This used to pass a `description`. `payment_orders` has no such column
     * and `description` is not fillable, so the value was silently discarded
     * on every call — which is exactly why the dead field in the resource
     * survived a test suite that appeared to cover it.
     */
    private function order(User $user, string $ref, string $amount, string $status): PaymentOrder
    {
        return PaymentOrder::create([
            'user_id' => $user->id,
            'merchant_reference' => $ref,
            'amount' => $amount,
            'currency' => 'UGX',
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
