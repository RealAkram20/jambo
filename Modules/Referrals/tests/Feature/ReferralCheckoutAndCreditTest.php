<?php

namespace Modules\Referrals\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Referrals\app\Listeners\CreditReferralOnPayment;
use Modules\Referrals\app\Models\Referral;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Referrals\app\Services\ReferralCheckoutService;
use Modules\Referrals\app\Services\ReferralSettings;
use Modules\Subscriptions\app\Listeners\ActivateSubscriptionFromPayment;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The money paths: server-side discount math, the activation price
 * backstop with and without a legitimate referral block, and the
 * idempotent referrer credit on payment completion.
 */
class ReferralCheckoutAndCreditTest extends TestCase
{
    use RefreshDatabase;

    private User $referrer;
    private User $buyer;
    private SubscriptionTier $tier;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web'], ['title' => 'User']);

        setting(['referrals.active', '1']);
        setting(['referrals.discount_percent', '10']);
        setting(['referrals.reward_percent', '20']);
        setting(['referrals.cookie_days', '15']);
        ReferralSettings::flush();

        $this->referrer = User::factory()->create([
            'username' => 'ref_' . uniqid(),
            'email' => 'ref_' . uniqid() . '@test.local',
        ]);
        $this->referrer->referral_code = $this->referrer->username;
        $this->referrer->save();

        $this->buyer = User::factory()->create([
            'username' => 'buyer_' . uniqid(),
            'email' => 'buyer_' . uniqid() . '@test.local',
        ]);

        $this->tier = SubscriptionTier::create([
            'name' => 'Premium Monthly',
            'slug' => 'premium-monthly-' . uniqid(),
            'price' => 30000,
            'currency' => 'UGX',
            'billing_period' => 'monthly',
            'access_level' => 2,
            'is_active' => true,
        ]);
    }

    private function attributeBuyer(): Referral
    {
        return Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_user_id' => $this->buyer->id,
            'code_used' => $this->referrer->referral_code,
            'source' => Referral::SOURCE_COOKIE,
            'status' => Referral::STATUS_PENDING,
        ]);
    }

    private function makeOrder(float $amount, ?array $referralBlock): PaymentOrder
    {
        $metadata = ['tier_slug' => $this->tier->slug];
        if ($referralBlock !== null) {
            $metadata['referral'] = $referralBlock;
        }

        return PaymentOrder::create([
            'user_id' => $this->buyer->id,
            'payable_type' => SubscriptionTier::class,
            'payable_id' => $this->tier->id,
            'merchant_reference' => 'JMB-TEST-' . uniqid(),
            'amount' => $amount,
            'currency' => 'UGX',
            'status' => PaymentOrder::STATUS_COMPLETED,
            'payment_gateway' => 'pesapal',
            'metadata' => $metadata,
        ]);
    }

    /* -------------------------------------------------- checkout math */

    public function test_attributed_first_time_buyer_gets_the_discount_block(): void
    {
        $this->attributeBuyer();

        $block = app(ReferralCheckoutService::class)->apply($this->buyer, '30000.00', 'UGX', null);

        $this->assertNotNull($block);
        $this->assertSame('3000.00', $block['discount_amount']);
        $this->assertSame('27000.00', $block['final_amount']);
        $this->assertSame('10', $block['discount_percent']);
        $this->assertSame('20', $block['reward_percent']);
        $this->assertSame($this->referrer->id, $block['referrer_id']);
    }

    public function test_no_discount_after_a_completed_payment(): void
    {
        $this->attributeBuyer();
        $this->makeOrder(30000, null);

        $this->assertNull(app(ReferralCheckoutService::class)->apply($this->buyer, '30000.00', 'UGX', null));
    }

    public function test_no_discount_when_program_is_off(): void
    {
        $this->attributeBuyer();
        setting(['referrals.active', '0']);
        ReferralSettings::flush();

        $this->assertNull(app(ReferralCheckoutService::class)->apply($this->buyer, '30000.00', 'UGX', null));
    }

    public function test_cookie_creates_attribution_lazily_at_checkout(): void
    {
        $block = app(ReferralCheckoutService::class)->apply(
            $this->buyer, '30000.00', 'UGX', $this->referrer->referral_code,
        );

        $this->assertNotNull($block);
        $this->assertDatabaseHas('referrals', [
            'referred_user_id' => $this->buyer->id,
            'referrer_id' => $this->referrer->id,
            'status' => Referral::STATUS_PENDING,
        ]);
    }

    public function test_own_code_earns_no_discount(): void
    {
        $this->assertNull(app(ReferralCheckoutService::class)->apply(
            $this->referrer, '30000.00', 'UGX', $this->referrer->referral_code,
        ));
    }

    public function test_legacy_full_discount_setting_yields_no_block_instead_of_a_zero_order(): void
    {
        // A '100' saved before the max:99 validation existed must not
        // produce a zero-amount order the gateway would reject.
        $this->attributeBuyer();
        setting(['referrals.discount_percent', '100']);
        ReferralSettings::flush();

        $this->assertNull(app(ReferralCheckoutService::class)->apply($this->buyer, '30000.00', 'UGX', null));
    }

    public function test_applying_a_discount_cancels_stale_pending_discounted_orders(): void
    {
        $this->attributeBuyer();

        $stale = PaymentOrder::create([
            'user_id' => $this->buyer->id,
            'payable_type' => SubscriptionTier::class,
            'payable_id' => $this->tier->id,
            'merchant_reference' => 'JMB-STALE-' . uniqid(),
            'amount' => 27000,
            'currency' => 'UGX',
            'status' => PaymentOrder::STATUS_PENDING,
            'payment_gateway' => 'pesapal',
            'metadata' => ['referral' => ['referrer_id' => $this->referrer->id]],
        ]);
        $plain = PaymentOrder::create([
            'user_id' => $this->buyer->id,
            'merchant_reference' => 'JMB-PLAIN-' . uniqid(),
            'amount' => 5000,
            'currency' => 'UGX',
            'status' => PaymentOrder::STATUS_PENDING,
            'payment_gateway' => 'pesapal',
            'metadata' => ['note' => 'no referral'],
        ]);

        $block = app(ReferralCheckoutService::class)->apply($this->buyer, '30000.00', 'UGX', null);

        $this->assertNotNull($block);
        $this->assertSame(PaymentOrder::STATUS_CANCELLED, $stale->fresh()->status,
            'The abandoned discounted order must be cancelled so both cannot be paid at the discount.');
        $this->assertSame(PaymentOrder::STATUS_PENDING, $plain->fresh()->status,
            'Pending orders without a referral block are untouched.');
    }

    /* -------------------------------------------------- activation backstop */

    public function test_discounted_order_with_consistent_block_activates(): void
    {
        $this->attributeBuyer();
        $order = $this->makeOrder(27000, [
            'referrer_id' => $this->referrer->id,
            'discount_percent' => '10',
            'reward_percent' => '20',
            'original_amount' => '30000.00',
            'discount_amount' => '3000.00',
            'final_amount' => '27000.00',
            'currency' => 'UGX',
        ]);

        app(ActivateSubscriptionFromPayment::class)->handle($order, 'test');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->buyer->id,
            'payment_order_id' => $order->id,
            'status' => UserSubscription::STATUS_ACTIVE,
        ]);
    }

    public function test_tampered_referral_block_is_refused(): void
    {
        $order = $this->makeOrder(1000, [
            'referrer_id' => $this->referrer->id,
            'discount_percent' => '10',
            'reward_percent' => '20',
            'original_amount' => '30000.00',
            'discount_amount' => '29000.00', // does not match 10% of 30000
            'final_amount' => '1000.00',
            'currency' => 'UGX',
        ]);

        app(ActivateSubscriptionFromPayment::class)->handle($order, 'test');

        $this->assertSame(0, UserSubscription::count(),
            'An inconsistent referral block must not lower the price floor.');
    }

    public function test_plain_underpaid_order_is_still_refused(): void
    {
        $order = $this->makeOrder(1000, null);

        app(ActivateSubscriptionFromPayment::class)->handle($order, 'test');

        $this->assertSame(0, UserSubscription::count());
    }

    /* -------------------------------------------------- referrer credit */

    private function completedReferralOrder(): PaymentOrder
    {
        $this->attributeBuyer();

        return $this->makeOrder(27000, [
            'referrer_id' => $this->referrer->id,
            'referral_id' => Referral::where('referred_user_id', $this->buyer->id)->value('id'),
            'code' => $this->referrer->referral_code,
            'source' => Referral::SOURCE_COOKIE,
            'discount_percent' => '10',
            'reward_percent' => '20',
            'original_amount' => '30000.00',
            'discount_amount' => '3000.00',
            'final_amount' => '27000.00',
            'currency' => 'UGX',
        ]);
    }

    public function test_completion_credits_the_referrer_once_even_on_replay(): void
    {
        $order = $this->completedReferralOrder();

        $listener = app(CreditReferralOnPayment::class);
        $listener->handle($order, 'callback');
        $listener->handle($order, 'ipn'); // replay

        $this->assertSame(1, LedgerEntry::count(), 'Callback + IPN must credit exactly once.');

        $entry = LedgerEntry::firstOrFail();
        $this->assertSame($this->referrer->id, (int) $entry->owner_id);
        $this->assertSame($this->referrer->getMorphClass(), $entry->owner_type);
        // 20% of the 27,000 actually paid.
        $this->assertSame('5400.00', (string) $entry->amount);
        $this->assertSame('5400.00', (string) $entry->balance_after);

        $referral = Referral::where('referred_user_id', $this->buyer->id)->firstOrFail();
        $this->assertSame(Referral::STATUS_QUALIFIED, $referral->status);
        $this->assertSame($order->id, $referral->qualified_payment_order_id);
        $this->assertSame('5400.00', (string) $referral->reward_amount);
    }

    public function test_second_order_never_credits_again(): void
    {
        $order = $this->completedReferralOrder();
        $listener = app(CreditReferralOnPayment::class);
        $listener->handle($order, 'callback');

        // A second in-flight order that also carried the block (created
        // before the first completed).
        $second = $this->makeOrder(27000, [
            'referrer_id' => $this->referrer->id,
            'discount_percent' => '10',
            'reward_percent' => '20',
            'original_amount' => '30000.00',
            'discount_amount' => '3000.00',
            'final_amount' => '27000.00',
            'currency' => 'UGX',
        ]);
        $listener->handle($second, 'ipn');

        $this->assertSame(1, LedgerEntry::count(), 'First payment only — a second order must not credit.');
    }

    public function test_snapshot_percent_survives_settings_change(): void
    {
        $order = $this->completedReferralOrder();

        // Program terms change (and even switch off) while the order is in flight.
        setting(['referrals.reward_percent', '50']);
        setting(['referrals.active', '0']);
        ReferralSettings::flush();

        app(CreditReferralOnPayment::class)->handle($order, 'ipn');

        $this->assertSame('5400.00', (string) LedgerEntry::firstOrFail()->amount,
            'Reward must use the 20% snapshot from order time, not the changed setting.');
    }

    public function test_qualification_repoints_a_repointed_row_to_the_paid_referrer(): void
    {
        // Order created under referrer A's terms; before it completes,
        // last-touch re-points the pending row to referrer B. A gets the
        // money (order-time snapshot), so the qualified row must show A —
        // never earnings on B's dashboard that B was not paid.
        $order = $this->completedReferralOrder();

        $other = User::factory()->create([
            'username' => 'other_' . uniqid(),
            'email' => 'other_' . uniqid() . '@test.local',
        ]);
        Referral::where('referred_user_id', $this->buyer->id)->update([
            'referrer_id' => $other->id,
            'code_used' => 'other-code',
        ]);

        app(CreditReferralOnPayment::class)->handle($order, 'callback');

        $referral = Referral::where('referred_user_id', $this->buyer->id)->firstOrFail();
        $this->assertSame($this->referrer->id, $referral->referrer_id);
        $this->assertSame(Referral::STATUS_QUALIFIED, $referral->status);
        $this->assertSame($this->referrer->id, (int) LedgerEntry::firstOrFail()->owner_id);
    }

    public function test_balances_are_scoped_per_currency(): void
    {
        $ledger = app(\Modules\Wallet\app\Services\Ledger::class);

        \Illuminate\Support\Facades\DB::transaction(fn () => $ledger->append(
            owner: $this->referrer, type: LedgerEntry::TYPE_REFERRAL_REWARD,
            amount: '1000.00', currency: 'UGX', memo: 'UGX credit',
        ));
        \Illuminate\Support\Facades\DB::transaction(fn () => $ledger->append(
            owner: $this->referrer, type: LedgerEntry::TYPE_REFERRAL_REWARD,
            amount: '50.00', currency: 'USD', memo: 'USD credit',
        ));

        // The default (platform-currency) balance must not absorb the USD row.
        $this->assertSame(0, bccomp('1000.00', $ledger->balanceFor($this->referrer, 'UGX'), 2));
        $this->assertSame(0, bccomp('50.00', $ledger->balanceFor($this->referrer, 'USD'), 2));
    }

    public function test_event_wiring_reaches_the_credit_listener(): void
    {
        $order = $this->completedReferralOrder();

        event('payment.completed', [$order, 'test']);

        $this->assertSame(1, LedgerEntry::count());
    }
}
