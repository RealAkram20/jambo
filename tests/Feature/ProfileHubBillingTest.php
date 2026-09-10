<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Tests\TestCase;

/**
 * The profile hub's Billing and Invoice pages, pinned.
 *
 * 🔴 **Both threw a 500 for any account that had ever paid**, and had done
 * since the hub was written. `ProfileHubController::billing()` eager-loaded
 * `payable.tier` and both blades read `$order->payable?->tier?->name`, but
 * `payable` IS the `SubscriptionTier` and that model has no `tier` relation:
 *
 *     RelationNotFoundException: Call to undefined relationship [tier]
 *     on model [Modules\Subscriptions\app\Models\SubscriptionTier]
 *
 * An account with no orders never loads a payable, sees the empty state, and
 * is fine — which is why nothing surfaced it. Every test that touched these
 * pages before this one was written against an empty order list.
 *
 * So the test that matters is not "the page renders". It is **"the page
 * renders for an account that has orders"**, and the plan name it prints is
 * the plan that was bought rather than a dash.
 */
class ProfileHubBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_billing_page_renders_for_an_account_that_has_paid(): void
    {
        $user = $this->viewer();
        $tier = $this->tier();
        $this->order($user, $tier, 'REF-HUB-01', '30000.00', 'completed');

        $this->actingAs($user, 'web')
            ->get(route('profile.billing', ['username' => $user->username]))
            ->assertOk()
            // The plan, not the em dash the broken expression fell back to.
            ->assertSee('Premium Monthly')
            ->assertSee('Completed');
    }

    public function test_the_invoice_page_renders_for_an_account_that_has_paid(): void
    {
        $user = $this->viewer();
        $tier = $this->tier();
        $order = $this->order($user, $tier, 'REF-HUB-02', '30000.00', 'completed');

        $this->actingAs($user, 'web')
            ->get(route('profile.invoice', ['username' => $user->username, 'orderId' => $order->id]))
            ->assertOk()
            ->assertSee('REF-HUB-02')
            ->assertSee('Premium Monthly')
            // ucfirst($billingPeriod) . ' subscription', the line item's subtitle.
            ->assertSee('Monthly subscription');
    }

    /**
     * The empty state was the only state anybody had seen, so it is pinned
     * too — a repair that fixed the populated page by breaking the empty one
     * would otherwise pass.
     */
    public function test_an_account_with_no_orders_still_gets_the_empty_state(): void
    {
        $user = $this->viewer();

        $this->actingAs($user, 'web')
            ->get(route('profile.billing', ['username' => $user->username]))
            ->assertOk()
            ->assertSee('No orders yet.');
    }

    /**
     * `payable` is polymorphic, so an order that points at nothing must still
     * render. The blades fall back to an em dash, which is the honest answer:
     * the page knows there was a charge and does not know what for.
     */
    public function test_an_order_with_no_payable_does_not_break_the_page(): void
    {
        $user = $this->viewer();
        $this->order($user, null, 'REF-HUB-03', '2500.00', 'completed');

        /*
         * The table has no reference column — Date, Plan, Amount, Status,
         * View — so the row is identified by its amount, and the plan cell
         * is the em dash the blade falls back to.
         */
        $this->actingAs($user, 'web')
            ->get(route('profile.billing', ['username' => $user->username]))
            ->assertOk()
            ->assertSee('UGX 2,500.00');
    }

    /** One viewer's orders are not another's, on the web page as on the API. */
    public function test_an_invoice_belonging_to_someone_else_is_not_served(): void
    {
        $mine = $this->viewer();
        $theirs = $this->viewer();
        $order = $this->order($theirs, $this->tier(), 'REF-HUB-04', '30000.00', 'completed');

        $this->actingAs($mine, 'web')
            ->get(route('profile.invoice', ['username' => $mine->username, 'orderId' => $order->id]))
            ->assertNotFound();
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function viewer(): User
    {
        return User::factory()->create();
    }

    private function tier(): SubscriptionTier
    {
        return SubscriptionTier::create([
            'name' => 'Premium Monthly',
            'slug' => 'premium-monthly',
            'price' => 30000,
            'currency' => 'UGX',
            'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
            'access_level' => 2,
            'max_concurrent_streams' => 2,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    private function order(
        User $user,
        ?SubscriptionTier $tier,
        string $ref,
        string $amount,
        string $status,
    ): PaymentOrder {
        return PaymentOrder::create([
            'user_id' => $user->id,
            'payable_type' => $tier?->getMorphClass(),
            'payable_id' => $tier?->getKey(),
            'merchant_reference' => $ref,
            'amount' => $amount,
            'currency' => 'UGX',
            'status' => $status,
        ]);
    }
}
