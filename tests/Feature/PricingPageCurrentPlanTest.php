<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The free-tier card's CTA: "Current Plan" for signed-in viewers with
 * no paid subscription, the normal Get Started / checkout CTAs for
 * everyone else.
 */
class PricingPageCurrentPlanTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionTier $freeTier;
    private SubscriptionTier $paidTier;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web'], ['title' => 'User']);

        $this->freeTier = SubscriptionTier::create([
            'name' => 'Free',
            'slug' => 'free-' . uniqid(),
            'price' => 0,
            'currency' => 'UGX',
            'billing_period' => 'monthly',
            'access_level' => 0,
            'is_active' => true,
        ]);

        $this->paidTier = SubscriptionTier::create([
            'name' => 'Premium',
            'slug' => 'premium-' . uniqid(),
            'price' => 30000,
            'currency' => 'UGX',
            'billing_period' => 'monthly',
            'access_level' => 2,
            'is_active' => true,
        ]);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create([
            'username' => 'viewer_' . uniqid(),
            'email' => 'viewer_' . uniqid() . '@test.local',
        ]);
        $user->assignRole('user');

        return $user;
    }

    public function test_free_user_sees_current_plan_on_the_free_tier(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('frontend.pricing-page'))
            ->assertOk()
            ->assertSee('Current Plan');
    }

    public function test_subscribed_user_does_not_see_current_plan_on_the_free_tier(): void
    {
        $user = $this->makeUser();
        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_tier_id' => $this->paidTier->id,
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addMonth(),
            'status' => UserSubscription::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->get(route('frontend.pricing-page'))
            ->assertOk()
            ->assertDontSee('Current Plan');
    }

    public function test_guest_sees_get_started_not_current_plan(): void
    {
        $this->get(route('frontend.pricing-page'))
            ->assertOk()
            ->assertDontSee('Current Plan');
    }
}
