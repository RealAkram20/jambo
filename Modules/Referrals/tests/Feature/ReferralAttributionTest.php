<?php

namespace Modules\Referrals\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Referrals\app\Http\Middleware\CaptureReferralCode;
use Modules\Referrals\app\Models\Referral;
use Modules\Referrals\app\Services\ReferralSettings;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cookie capture (?ref=), attribution at registration, and the manual
 * apply-code endpoint.
 */
class ReferralAttributionTest extends TestCase
{
    use RefreshDatabase;

    private User $referrer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web'], ['title' => 'User']);

        setting(['referrals.active', '1']);
        setting(['referrals.discount_percent', '10']);
        setting(['referrals.reward_percent', '10']);
        setting(['referrals.cookie_days', '15']);
        ReferralSettings::flush();

        $this->referrer = User::factory()->create([
            'username' => 'ref_' . uniqid(),
            'email' => 'ref_' . uniqid() . '@test.local',
        ]);
        $this->referrer->referral_code = $this->referrer->username;
        $this->referrer->save();
    }

    public function test_ref_param_sets_the_referral_cookie(): void
    {
        $this->get('/login?ref=' . $this->referrer->referral_code)
            ->assertPlainCookie(CaptureReferralCode::COOKIE_NAME, $this->referrer->referral_code);
    }

    public function test_newer_ref_param_overwrites_the_cookie_last_touch(): void
    {
        $this->withUnencryptedCookie(CaptureReferralCode::COOKIE_NAME, 'oldcode')
            ->get('/login?ref=newcode')
            ->assertPlainCookie(CaptureReferralCode::COOKIE_NAME, 'newcode');
    }

    public function test_no_cookie_when_program_is_off(): void
    {
        setting(['referrals.active', '0']);
        ReferralSettings::flush();

        $this->get('/login?ref=' . $this->referrer->referral_code)
            ->assertCookieMissing(CaptureReferralCode::COOKIE_NAME);
    }

    public function test_malformed_code_is_ignored(): void
    {
        $this->get('/login?ref=' . urlencode('bad code!<'))
            ->assertCookieMissing(CaptureReferralCode::COOKIE_NAME);
    }

    public function test_registration_with_cookie_creates_pending_attribution_and_default_code(): void
    {
        $response = $this->withUnencryptedCookie(CaptureReferralCode::COOKIE_NAME, $this->referrer->referral_code)
            ->post('/register', [
                'first_name' => 'New',
                'last_name' => 'User',
                'username' => 'newbie_' . uniqid(),
                'email' => 'newbie_' . uniqid() . '@test.local',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertRedirect('/');

        $newUser = User::where('email', 'like', 'newbie_%')->firstOrFail();

        $this->assertSame($newUser->username, $newUser->referral_code,
            'A new account must default its referral code to its username.');

        $referral = Referral::where('referred_user_id', $newUser->id)->first();
        $this->assertNotNull($referral, 'Signup with a referral cookie must create an attribution row.');
        $this->assertSame($this->referrer->id, $referral->referrer_id);
        $this->assertSame(Referral::STATUS_PENDING, $referral->status);
        $this->assertSame(Referral::SOURCE_COOKIE, $referral->source);
    }

    public function test_registration_rejects_username_taken_as_someones_referral_code(): void
    {
        // The referrer holds a CUSTOM code that is nobody's username —
        // registering with it as a username would collide when the new
        // account defaults referral_code = username.
        $this->referrer->referral_code = 'custom-code-' . uniqid();
        $this->referrer->save();

        $this->post('/register', [
            'first_name' => 'Squatter',
            'last_name' => 'User',
            'username' => $this->referrer->referral_code,
            'email' => 'squat_' . uniqid() . '@test.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('username');

        $this->assertSame(0, User::where('email', 'like', 'squat_%')->count());
    }

    public function test_registration_with_unknown_code_creates_no_attribution(): void
    {
        $this->withUnencryptedCookie(CaptureReferralCode::COOKIE_NAME, 'no-such-code')
            ->post('/register', [
                'first_name' => 'New',
                'last_name' => 'User',
                'username' => 'lonely_' . uniqid(),
                'email' => 'lonely_' . uniqid() . '@test.local',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])->assertRedirect('/');

        $this->assertSame(0, Referral::count());
    }

    public function test_apply_code_attributes_an_existing_user(): void
    {
        $buyer = $this->makeBuyer();

        $this->actingAs($buyer)
            ->postJson(route('referrals.apply-code'), ['code' => $this->referrer->referral_code])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $referral = Referral::where('referred_user_id', $buyer->id)->firstOrFail();
        $this->assertSame($this->referrer->id, $referral->referrer_id);
        $this->assertSame(Referral::SOURCE_CODE, $referral->source);
    }

    public function test_apply_code_rejects_own_code(): void
    {
        $this->actingAs($this->referrer)
            ->postJson(route('referrals.apply-code'), ['code' => $this->referrer->referral_code])
            ->assertStatus(422);

        $this->assertSame(0, Referral::count());
    }

    public function test_apply_code_rejects_a_user_who_already_paid(): void
    {
        $buyer = $this->makeBuyer();
        PaymentOrder::create([
            'user_id' => $buyer->id,
            'merchant_reference' => 'JMB-TEST-' . uniqid(),
            'amount' => 10000,
            'currency' => 'UGX',
            'status' => PaymentOrder::STATUS_COMPLETED,
            'payment_gateway' => 'pesapal',
        ]);

        $this->actingAs($buyer)
            ->postJson(route('referrals.apply-code'), ['code' => $this->referrer->referral_code])
            ->assertStatus(422);
    }

    public function test_apply_code_rejects_when_program_is_off(): void
    {
        setting(['referrals.active', '0']);
        ReferralSettings::flush();

        $this->actingAs($this->makeBuyer())
            ->postJson(route('referrals.apply-code'), ['code' => $this->referrer->referral_code])
            ->assertStatus(422);
    }

    public function test_qualified_attribution_is_immutable(): void
    {
        $buyer = $this->makeBuyer();
        Referral::create([
            'referrer_id' => $this->referrer->id,
            'referred_user_id' => $buyer->id,
            'code_used' => $this->referrer->referral_code,
            'source' => Referral::SOURCE_COOKIE,
            'status' => Referral::STATUS_QUALIFIED,
        ]);

        $other = User::factory()->create([
            'username' => 'other_' . uniqid(),
            'email' => 'other_' . uniqid() . '@test.local',
        ]);
        $other->referral_code = $other->username;
        $other->save();

        $this->actingAs($buyer)
            ->postJson(route('referrals.apply-code'), ['code' => $other->referral_code])
            ->assertStatus(422);

        $this->assertSame(
            $this->referrer->id,
            Referral::where('referred_user_id', $buyer->id)->value('referrer_id'),
        );
    }

    private function makeBuyer(): User
    {
        $buyer = User::factory()->create([
            'username' => 'buyer_' . uniqid(),
            'email' => 'buyer_' . uniqid() . '@test.local',
        ]);
        $buyer->assignRole('user');

        return $buyer;
    }
}
