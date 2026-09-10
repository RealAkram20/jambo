<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payments\app\Contracts\PaymentGateway;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Tests\TestCase;

/**
 * Starting a subscription payment from the app.
 *
 * The half of ADR-0004 that was never built until 2026-09-10: the Play build
 * takes no payment at all, and the direct APK gets a hosted checkout. Rio
 * found the gap by trying to subscribe.
 *
 * **The tests that matter here are the ones about money, not about the happy
 * path.** The endpoint's whole security property is that the client names a
 * plan and the server decides what it costs — there is no `amount` in the
 * request and there must never be one. `test_the_price_is_the_servers`
 * is the test that fails the day somebody adds one for convenience.
 */
class SubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    /**
     * A gateway that answers without a network.
     *
     * Records what it was asked for, because the assertion that matters is
     * the amount the SERVER submitted — not the amount the client hoped for.
     */
    private function fakeGateway(): object
    {
        $fake = new class implements PaymentGateway
        {
            public array $submitted = [];

            public function slug(): string
            {
                return 'fake';
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function submitOrder(
                string $merchantReference,
                float $amount,
                string $currency,
                string $description,
                string $callbackUrl,
                array $billingAddress,
                ?string $cancellationUrl = null
            ): array {
                $this->submitted = compact(
                    'merchantReference',
                    'amount',
                    'currency',
                    'description',
                    'callbackUrl',
                    'cancellationUrl',
                );

                return [
                    'redirect_url' => 'https://pay.example.test/' . $merchantReference,
                    'order_tracking_id' => 'track-' . $merchantReference,
                    'raw' => [],
                ];
            }

            public function getTransactionStatus(string $orderTrackingId): array
            {
                return [];
            }

            public function interpretStatus(array $rawStatus): string
            {
                return PaymentOrder::STATUS_PENDING;
            }
        };

        $this->app->instance(PaymentGateway::class, $fake);

        return $fake;
    }

    public function test_a_viewer_can_start_a_payment_for_an_active_plan(): void
    {
        $this->fakeGateway();

        $tier = $this->tier('Premium', 'premium', 30000);
        $token = $this->signIn();

        $response = $this->withToken($token)
            ->postJson('/api/v1/subscription/orders', ['tier_slug' => 'premium'])
            ->assertOk();

        $reference = $response->json('data.reference');

        // A presentation the app obeys, not a gateway it has to recognise.
        $this->assertSame('webview', $response->json('data.checkout.mode'));
        $this->assertNotEmpty($response->json('data.checkout.url'));
        $this->assertNotEmpty($response->json('data.checkout.return_url'));
        $this->assertNotEmpty($response->json('data.checkout.cancel_url'));
        $this->assertNotEmpty($reference);

        $order = PaymentOrder::where('merchant_reference', $reference)->firstOrFail();

        $this->assertSame(PaymentOrder::STATUS_PENDING, $order->status);
        $this->assertSame(SubscriptionTier::class, $order->payable_type);
        $this->assertSame($tier->id, (int) $order->payable_id);
    }

    /**
     * 🔴 **The one that matters.** The request carries a plan and nothing else,
     * so the price is read off the tier row. This asserts what the SERVER sent
     * to the gateway, because that is the number that gets charged — and it
     * fails the day an `amount` field is added to the request for convenience.
     */
    public function test_the_price_is_the_servers_and_a_client_cannot_name_it(): void
    {
        $gateway = $this->fakeGateway();

        $this->tier('Premium', 'premium', 30000);
        $token = $this->signIn();

        $this->withToken($token)->postJson('/api/v1/subscription/orders', [
            'tier_slug' => 'premium',
            // Every shape a hopeful client might try. All ignored.
            'amount' => 1,
            'price' => 1,
            'currency' => 'USD',
        ])->assertOk();

        $this->assertSame(30000.0, $gateway->submitted['amount']);
        $this->assertSame('UGX', $gateway->submitted['currency']);

        $order = PaymentOrder::firstOrFail();
        $this->assertSame('30000.00', number_format((float) $order->amount, 2, '.', ''));
    }

    /**
     * The price is frozen into the order at checkout.
     *
     * The activation backstop validates a paid amount against this snapshot
     * rather than the live tier, so an admin raising the price between
     * checkout and confirmation cannot cause a paid order to be refused.
     */
    public function test_the_order_carries_a_frozen_price_snapshot(): void
    {
        $this->fakeGateway();

        $tier = $this->tier('Premium', 'premium', 30000);
        $token = $this->signIn();

        $this->withToken($token)
            ->postJson('/api/v1/subscription/orders', ['tier_slug' => 'premium'])
            ->assertOk();

        $snapshot = PaymentOrder::firstOrFail()->metadata['tier_snapshot'] ?? null;

        $this->assertSame($tier->id, $snapshot['tier_id']);
        $this->assertSame('30000.00', $snapshot['price']);
        $this->assertSame('UGX', $snapshot['currency']);
    }

    /**
     * A client-supplied referral block must never survive into an order.
     *
     * The activation backstop and the referrer-credit listener trust that
     * block. The endpoint accepts no metadata at all, which is the strongest
     * form of the guard — there is nothing to strip.
     */
    public function test_a_client_cannot_inject_a_referral_block(): void
    {
        $this->fakeGateway();

        $this->tier('Premium', 'premium', 30000);
        $token = $this->signIn();

        $this->withToken($token)->postJson('/api/v1/subscription/orders', [
            'tier_slug' => 'premium',
            'metadata' => ['referral' => ['final_amount' => '1.00', 'discount_percent' => '99']],
        ])->assertOk();

        $metadata = PaymentOrder::firstOrFail()->metadata;

        $this->assertArrayNotHasKey('referral', $metadata);
    }

    public function test_an_inactive_plan_cannot_be_bought(): void
    {
        $this->fakeGateway();

        $tier = $this->tier('Retired', 'retired', 9000);
        $tier->forceFill(['is_active' => false])->save();

        $token = $this->signIn();

        $this->withToken($token)
            ->postJson('/api/v1/subscription/orders', ['tier_slug' => 'retired'])
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');

        $this->assertSame(0, PaymentOrder::count());
    }

    /** A free plan has nothing to pay for, and a gateway asked for zero refuses. */
    public function test_a_free_plan_is_refused_before_the_gateway(): void
    {
        $this->fakeGateway();

        $this->tier('Free', 'free', 0);
        $token = $this->signIn();

        $this->withToken($token)
            ->postJson('/api/v1/subscription/orders', ['tier_slug' => 'free'])
            ->assertStatus(422);

        $this->assertSame(0, PaymentOrder::count());
    }

    public function test_starting_a_payment_requires_signing_in(): void
    {
        $this->fakeGateway();
        $this->tier('Premium', 'premium', 30000);

        $this->postJson('/api/v1/subscription/orders', ['tier_slug' => 'premium'])
            ->assertStatus(401);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function tier(string $name, string $slug, int $price): SubscriptionTier
    {
        return SubscriptionTier::create([
            'name' => $name,
            'slug' => $slug,
            'price' => $price,
            'currency' => 'UGX',
            'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
            'access_level' => 1,
            'max_concurrent_streams' => 2,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function signIn(): string
    {
        $user = User::factory()->create([
            'password' => bcrypt(self::PASSWORD),
            'email_verified_at' => now(),
        ]);

        return $user->createToken('test')->plainTextToken;
    }
}
