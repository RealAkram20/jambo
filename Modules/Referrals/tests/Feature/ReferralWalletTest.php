<?php

namespace Modules\Referrals\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Referrals\app\Listeners\CreditReferralOnPayment;
use Modules\Referrals\app\Models\Referral;
use Modules\Referrals\app\Services\ReferralSettings;
use Modules\Referrals\app\Services\ReferralWalletService;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Models\WithdrawalRequest;
use Modules\Wallet\app\Services\Ledger;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The universal wallet phase: spending the balance on a subscription
 * (full cover only), the withdrawal lifecycle with holds on the ONE
 * queue, partner routing of rewards, and single-ledger idempotency.
 */
class ReferralWalletTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $clerk;
    private SubscriptionTier $tier;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['user', 'admin', 'super-admin', 'finance', 'partner'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['title' => ucfirst($role)]);
        }

        setting(['referrals.active', '1']);
        setting(['referrals.discount_percent', '10']);
        setting(['referrals.reward_percent', '20']);
        setting(['referrals.cookie_days', '15']);
        setting(['referrals.min_withdrawal', '5000']);
        ReferralSettings::flush();

        $this->user = User::factory()->create([
            'username' => 'wallet_' . uniqid(),
            'email' => 'wallet_' . uniqid() . '@test.local',
        ]);
        $this->user->assignRole('user');

        $this->clerk = User::factory()->create([
            'username' => 'clerk_' . uniqid(),
            'email' => 'clerk_' . uniqid() . '@test.local',
        ]);
        $this->clerk->assignRole('finance');

        $this->tier = SubscriptionTier::create([
            'name' => 'Premium Monthly',
            'slug' => 'premium-monthly-' . uniqid(),
            'price' => 10000,
            'currency' => 'UGX',
            'billing_period' => 'monthly',
            'access_level' => 2,
            'is_active' => true,
        ]);
    }

    /** Seed the wallet with a reward entry on the universal ledger. */
    private function fund(User $user, string $amount): void
    {
        DB::transaction(fn () => app(Ledger::class)->append(
            owner: $user,
            type: LedgerEntry::TYPE_REFERRAL_REWARD,
            amount: $amount,
            currency: 'UGX',
            memo: 'seed',
        ));
    }

    private function balance(User $user): string
    {
        return app(Ledger::class)->balanceFor($user, 'UGX');
    }

    /* -------------------------------------------------- spend on subscription */

    public function test_wallet_covers_tier_and_activates_subscription(): void
    {
        $this->fund($this->user, '15000.00');

        $this->actingAs($this->user)
            ->post(route('referrals.wallet.subscribe'), ['tier_slug' => $this->tier->slug])
            ->assertRedirect(route('frontend.pricing-page'))
            ->assertSessionHas('success');

        $order = PaymentOrder::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame(PaymentOrder::STATUS_COMPLETED, $order->status);
        $this->assertSame('referral-wallet', $order->payment_gateway);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->user->id,
            'payment_order_id' => $order->id,
            'status' => UserSubscription::STATUS_ACTIVE,
        ]);

        $this->assertSame(0, bccomp('5000.00', $this->balance($this->user), 2));
    }

    public function test_insufficient_balance_refuses_the_purchase(): void
    {
        $this->fund($this->user, '9999.00');

        $this->actingAs($this->user)
            ->post(route('referrals.wallet.subscribe'), ['tier_slug' => $this->tier->slug])
            ->assertRedirect(route('frontend.pricing-page'))
            ->assertSessionHas('error');

        $this->assertSame(0, PaymentOrder::count());
        $this->assertSame(0, UserSubscription::count());
        $this->assertSame(0, bccomp('9999.00', $this->balance($this->user), 2));
    }

    /* -------------------------------------------------- withdrawal lifecycle */

    public function test_withdrawal_request_holds_the_funds(): void
    {
        $this->fund($this->user, '20000.00');

        $this->actingAs($this->user)
            ->post(route('referrals.wallet.withdraw'), [
                'amount' => '15000',
                'payee_name' => 'Wallet Tester',
                'payee_msisdn' => '0700123456',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $withdrawal = WithdrawalRequest::firstOrFail();
        $this->assertSame(WithdrawalRequest::STATUS_REQUESTED, $withdrawal->status);
        $this->assertSame($this->user->id, (int) $withdrawal->owner_id);
        $this->assertNotNull($withdrawal->hold_entry_id);

        // Balance drops immediately: 20,000 − 15,000 hold.
        $this->assertSame(0, bccomp('5000.00', $this->balance($this->user), 2));
    }

    public function test_below_minimum_and_overdraw_and_double_requests_are_refused(): void
    {
        $this->fund($this->user, '20000.00');

        // Below the 5,000 minimum.
        $this->actingAs($this->user)
            ->post(route('referrals.wallet.withdraw'), [
                'amount' => '1000', 'payee_name' => 'T', 'payee_msisdn' => '0700123456',
            ])->assertSessionHas('error');

        // More than the balance.
        $this->actingAs($this->user)
            ->post(route('referrals.wallet.withdraw'), [
                'amount' => '25000', 'payee_name' => 'T', 'payee_msisdn' => '0700123456',
            ])->assertSessionHas('error');

        $this->assertSame(0, WithdrawalRequest::count());

        // A legitimate request, then a second while it is open.
        app(ReferralWalletService::class)->requestWithdrawal($this->user, '6000', 'T', '0700123456');
        $this->actingAs($this->user)
            ->post(route('referrals.wallet.withdraw'), [
                'amount' => '6000', 'payee_name' => 'T', 'payee_msisdn' => '0700123456',
            ])->assertSessionHas('error');

        $this->assertSame(1, WithdrawalRequest::count());
    }

    public function test_clerk_approves_and_marks_paid(): void
    {
        $this->fund($this->user, '20000.00');
        $withdrawal = app(ReferralWalletService::class)
            ->requestWithdrawal($this->user, '15000', 'Wallet Tester', '0700123456');

        $this->actingAs($this->clerk)
            ->post(route('admin.wallet.withdrawals.approve', $withdrawal))
            ->assertSessionHas('success');
        $this->assertSame(WithdrawalRequest::STATUS_APPROVED, $withdrawal->fresh()->status);

        $this->actingAs($this->clerk)
            ->post(route('admin.wallet.withdrawals.mark-paid', $withdrawal), [
                'transaction_reference' => 'MOMO-12345',
            ])
            ->assertSessionHas('success');

        $fresh = $withdrawal->fresh();
        $this->assertSame(WithdrawalRequest::STATUS_PAID, $fresh->status);
        $this->assertSame('MOMO-12345', $fresh->transaction_reference);

        // The hold remains as the permanent debit.
        $this->assertSame(0, bccomp('5000.00', $this->balance($this->user), 2));
    }

    public function test_rejection_returns_the_held_funds(): void
    {
        $this->fund($this->user, '20000.00');
        $withdrawal = app(ReferralWalletService::class)
            ->requestWithdrawal($this->user, '15000', 'Wallet Tester', '0700123456');

        $this->actingAs($this->clerk)
            ->post(route('admin.wallet.withdrawals.reject', $withdrawal), [
                'rejection_reason' => 'Number does not match a registered account',
            ])
            ->assertSessionHas('success');

        $this->assertSame(WithdrawalRequest::STATUS_REJECTED, $withdrawal->fresh()->status);
        $this->assertSame(0, bccomp('20000.00', $this->balance($this->user), 2));
    }

    public function test_regular_user_cannot_touch_the_payout_queue(): void
    {
        $this->fund($this->user, '20000.00');
        $withdrawal = app(ReferralWalletService::class)
            ->requestWithdrawal($this->user, '15000', 'Wallet Tester', '0700123456');

        $this->actingAs($this->user)
            ->get(route('admin.wallet.withdrawals.index'))
            ->assertForbidden();
        $this->actingAs($this->user)
            ->post(route('admin.wallet.withdrawals.approve', $withdrawal))
            ->assertForbidden();
    }

    /* -------------------------------------------------- partner routing */

    private function completedReferredOrder(User $referrer, User $buyer): PaymentOrder
    {
        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $buyer->id,
            'code_used' => $referrer->referral_code ?? $referrer->username,
            'source' => Referral::SOURCE_COOKIE,
            'status' => Referral::STATUS_PENDING,
        ]);

        return PaymentOrder::create([
            'user_id' => $buyer->id,
            'payable_type' => SubscriptionTier::class,
            'payable_id' => $this->tier->id,
            'merchant_reference' => 'JMB-TEST-' . uniqid(),
            'amount' => 27000,
            'currency' => 'UGX',
            'status' => PaymentOrder::STATUS_COMPLETED,
            'payment_gateway' => 'pesapal',
            'metadata' => ['referral' => [
                'referrer_id' => $referrer->id,
                'reward_percent' => '20',
                'discount_percent' => '10',
                'original_amount' => '30000.00',
                'discount_amount' => '3000.00',
                'final_amount' => '27000.00',
                'currency' => 'UGX',
            ]],
        ]);
    }

    public function test_partner_referrer_is_credited_on_the_partner_profile_wallet(): void
    {
        $partnerUser = User::factory()->create([
            'username' => 'vj_' . uniqid(),
            'email' => 'vj_' . uniqid() . '@test.local',
        ]);
        $partnerUser->assignRole('partner');
        $partner = MonetizationPartner::create([
            'type' => MonetizationPartner::TYPE_VJ,
            'user_id' => $partnerUser->id,
            'display_name' => 'VJ Wallet',
            'status' => MonetizationPartner::STATUS_ENROLLED,
            'multiplier' => 1,
        ]);

        $order = $this->completedReferredOrder($partnerUser, $this->user);
        $listener = app(CreditReferralOnPayment::class);
        $listener->handle($order, 'callback');
        $listener->handle($order, 'ipn'); // replay

        // Exactly one credit, owned by the PARTNER profile, none on the user wallet.
        $this->assertSame(0, LedgerEntry::where('owner_type', $partnerUser->getMorphClass())
            ->where('owner_id', $partnerUser->id)->count());
        $entries = LedgerEntry::where('owner_type', $partner->getMorphClass())
            ->where('owner_id', $partner->id)
            ->where('type', LedgerEntry::TYPE_REFERRAL_REWARD)->get();
        $this->assertCount(1, $entries);
        // 20% of 27,000.
        $this->assertSame(0, bccomp('5400.00', (string) $entries->first()->amount, 2));

        // The referral row still qualifies, and the partner balance sees the money.
        $this->assertSame(Referral::STATUS_QUALIFIED,
            Referral::where('referred_user_id', $this->user->id)->value('status'));
        $this->assertSame(0, bccomp('5400.00', $partner->walletBalance(), 2));
    }

    public function test_replay_after_becoming_partner_does_not_double_credit(): void
    {
        $referrer = User::factory()->create([
            'username' => 'late_' . uniqid(),
            'email' => 'late_' . uniqid() . '@test.local',
        ]);

        $order = $this->completedReferredOrder($referrer, $this->user);
        $listener = app(CreditReferralOnPayment::class);

        // First pass: not a partner — the USER wallet is credited.
        $listener->handle($order, 'callback');
        $this->assertSame(1, LedgerEntry::where('owner_type', $referrer->getMorphClass())
            ->where('owner_id', $referrer->id)->count());

        // They enroll as a partner, then the IPN replays.
        $partner = MonetizationPartner::create([
            'type' => MonetizationPartner::TYPE_VJ,
            'user_id' => $referrer->id,
            'display_name' => 'Late Partner',
            'status' => MonetizationPartner::STATUS_ENROLLED,
            'multiplier' => 1,
        ]);
        $listener->handle($order, 'ipn');

        $this->assertSame(1, LedgerEntry::where('owner_type', $referrer->getMorphClass())
            ->where('owner_id', $referrer->id)->count());
        $this->assertSame(0, LedgerEntry::where('owner_type', $partner->getMorphClass())
            ->where('owner_id', $partner->id)->count());
    }

    /* -------------------------------------------------- wallet page */

    public function test_user_sees_their_wallet_page(): void
    {
        $this->fund($this->user, '15000.00');

        $this->actingAs($this->user)
            ->get(route('profile.wallet', ['username' => $this->user->username]))
            ->assertOk()
            ->assertSee('15,000')
            ->assertSee('Referral reward');
    }

    public function test_partner_is_bounced_to_the_studio_wallet(): void
    {
        $partnerUser = User::factory()->create([
            'username' => 'pw_' . uniqid(),
            'email' => 'pw_' . uniqid() . '@test.local',
        ]);
        $partnerUser->assignRole('partner');
        MonetizationPartner::create([
            'type' => MonetizationPartner::TYPE_VJ,
            'user_id' => $partnerUser->id,
            'display_name' => 'Bounced VJ',
            'status' => MonetizationPartner::STATUS_ENROLLED,
            'multiplier' => 1,
        ]);

        $this->actingAs($partnerUser)
            ->get(route('profile.wallet', ['username' => $partnerUser->username]))
            ->assertRedirect(route('partner.wallet'));
    }

    public function test_wallet_page_open_even_when_program_is_off(): void
    {
        setting(['referrals.active', '0']);
        ReferralSettings::flush();

        $this->actingAs($this->user)
            ->get(route('profile.wallet', ['username' => $this->user->username]))
            ->assertOk();
    }

    /* -------------------------------------------------- program-off access */

    public function test_wallet_stays_reachable_when_program_is_off(): void
    {
        $this->fund($this->user, '20000.00');
        setting(['referrals.active', '0']);
        ReferralSettings::flush();

        // Page still opens for the balance holder…
        $this->actingAs($this->user)
            ->get(route('profile.refer', ['username' => $this->user->username]))
            ->assertOk();

        // …and withdrawing still works.
        $this->actingAs($this->user)
            ->post(route('referrals.wallet.withdraw'), [
                'amount' => '15000',
                'payee_name' => 'Wallet Tester',
                'payee_msisdn' => '0700123456',
            ])->assertSessionHas('status');

        $this->assertSame(1, WithdrawalRequest::count());
    }
}
