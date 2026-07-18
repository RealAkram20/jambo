<?php

namespace Modules\Referrals\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Referrals\app\Services\ReferralSettings;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Access rules: the whole back office (overview + settings) is
 * super-admin only, everyone participates via the user-facing hub tab,
 * and referral-code editing guards.
 */
class ReferralAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web'], ['title' => 'Super Administrator']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web'], ['title' => 'User']);

        setting(['referrals.active', '1']);
        setting(['referrals.discount_percent', '10']);
        setting(['referrals.reward_percent', '10']);
        setting(['referrals.cookie_days', '15']);
        ReferralSettings::flush();

        $this->superAdmin = User::factory()->create([
            'username' => 'owner_' . uniqid(),
            'email' => 'owner_' . uniqid() . '@test.local',
        ]);
        $this->superAdmin->assignRole('super-admin');
        $this->superAdmin->assignRole('admin');

        $this->admin = User::factory()->create([
            'username' => 'admin_' . uniqid(),
            'email' => 'admin_' . uniqid() . '@test.local',
        ]);
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create([
            'username' => 'user_' . uniqid(),
            'email' => 'user_' . uniqid() . '@test.local',
        ]);
        $this->user->assignRole('user');
        $this->user->referral_code = $this->user->username;
        $this->user->save();
    }

    /* -------------------------------------------------- admin settings */

    public function test_plain_admin_cannot_open_referral_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.referrals.settings'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_and_save_settings(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.referrals.settings'))
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->put(route('admin.referrals.settings.update'), [
                'active' => '1',
                'reward_percent' => '15',
                'discount_percent' => '20',
                'cookie_days' => '30',
                'min_withdrawal' => '8000',
            ])
            ->assertRedirect(route('admin.referrals.settings'));

        ReferralSettings::flush();
        $this->assertSame('15', ReferralSettings::rewardPercent());
        $this->assertSame('20', ReferralSettings::discountPercent());
        $this->assertSame(30, ReferralSettings::cookieDays());
        $this->assertSame('8000', ReferralSettings::minWithdrawal());
    }

    public function test_full_discount_is_rejected(): void
    {
        // 100% off produces a zero-amount order the gateway can't charge,
        // which would block every referred buyer at checkout.
        $this->actingAs($this->superAdmin)
            ->from(route('admin.referrals.settings'))
            ->put(route('admin.referrals.settings.update'), [
                'active' => '1',
                'reward_percent' => '10',
                'discount_percent' => '100',
                'cookie_days' => '15',
                'min_withdrawal' => '10000',
            ])
            ->assertSessionHasErrors('discount_percent');
    }

    public function test_regular_user_cannot_open_the_admin_overview(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.referrals.index'))
            ->assertForbidden();
    }

    public function test_plain_admin_opens_the_hub_without_the_overview_tab(): void
    {
        // The hub page itself is open to every panel user (their own
        // Refer & Earn tab lives there); the program-wide Overview tab
        // and the Payouts tab stay hidden for a plain admin.
        $this->actingAs($this->admin)
            ->get(route('admin.referrals.index'))
            ->assertOk()
            ->assertDontSee('overview-pane')
            ->assertDontSee('payouts-pane');
    }

    public function test_super_admin_sees_all_hub_tabs(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.referrals.index'))
            ->assertOk()
            ->assertSee('overview-pane')
            ->assertSee('payouts-pane');
    }

    /* -------------------------------------------------- hub page */

    public function test_user_sees_their_refer_and_earn_page(): void
    {
        $this->actingAs($this->user)
            ->get(route('profile.refer', ['username' => $this->user->username]))
            ->assertOk()
            ->assertSee($this->user->referral_code);
    }

    public function test_refer_page_is_404_when_program_is_off(): void
    {
        setting(['referrals.active', '0']);
        ReferralSettings::flush();

        $this->actingAs($this->user)
            ->get(route('profile.refer', ['username' => $this->user->username]))
            ->assertNotFound();
    }

    public function test_admin_can_view_refer_page_and_edit_their_code(): void
    {
        $this->admin->referral_code = $this->admin->username;
        $this->admin->save();

        $this->actingAs($this->admin)
            ->get(route('profile.refer', ['username' => $this->admin->username]))
            ->assertOk()
            ->assertSee($this->admin->referral_code);

        $this->actingAs($this->admin)
            ->put(route('profile.refer.code', ['username' => $this->admin->username]), [
                'referral_code' => 'admin-refers',
            ])
            ->assertRedirect(route('profile.refer', ['username' => $this->admin->username]));

        $this->assertSame('admin-refers', $this->admin->fresh()->referral_code);
    }

    public function test_admin_still_bounces_off_other_hub_tabs(): void
    {
        $this->actingAs($this->admin)
            ->get(route('profile.show', ['username' => $this->admin->username]))
            ->assertRedirect('/app');
    }

    /* -------------------------------------------------- live availability check */

    public function test_check_code_reports_available(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('referrals.check-code'), ['code' => 'totally-free-code'])
            ->assertOk()
            ->assertJson(['ok' => true, 'available' => true]);
    }

    public function test_check_code_reports_taken_code_username_and_reserved(): void
    {
        $other = User::factory()->create([
            'username' => 'occupied_' . uniqid(),
            'email' => 'occupied_' . uniqid() . '@test.local',
        ]);
        $other->referral_code = 'occupied-code';
        $other->save();

        foreach (['occupied-code', $other->username, 'admin'] as $code) {
            $this->actingAs($this->user)
                ->postJson(route('referrals.check-code'), ['code' => $code])
                ->assertOk()
                ->assertJson(['available' => false]);
        }
    }

    public function test_check_code_allows_your_own_current_values(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('referrals.check-code'), ['code' => $this->user->username])
            ->assertOk()
            ->assertJson(['available' => true]);
    }

    public function test_check_code_rejects_bad_format(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('referrals.check-code'), ['code' => 'a b!'])
            ->assertOk()
            ->assertJson(['available' => false]);
    }

    /* -------------------------------------------------- code editing */

    public function test_user_can_customise_their_code(): void
    {
        $this->actingAs($this->user)
            ->put(route('profile.refer.code', ['username' => $this->user->username]), [
                'referral_code' => 'my-cool-code',
            ])
            ->assertRedirect(route('profile.refer', ['username' => $this->user->username]));

        $this->assertSame('my-cool-code', $this->user->fresh()->referral_code);
    }

    public function test_reserved_names_are_rejected_as_codes(): void
    {
        $this->actingAs($this->user)
            ->from(route('profile.refer', ['username' => $this->user->username]))
            ->put(route('profile.refer.code', ['username' => $this->user->username]), [
                'referral_code' => 'admin',
            ])
            ->assertSessionHasErrors('referral_code');

        $this->assertSame($this->user->username, $this->user->fresh()->referral_code);
    }

    public function test_another_users_username_cannot_be_claimed_as_a_code(): void
    {
        $victim = User::factory()->create([
            'username' => 'victim_' . uniqid(),
            'email' => 'victim_' . uniqid() . '@test.local',
        ]);

        $this->actingAs($this->user)
            ->from(route('profile.refer', ['username' => $this->user->username]))
            ->put(route('profile.refer.code', ['username' => $this->user->username]), [
                'referral_code' => $victim->username,
            ])
            ->assertSessionHasErrors('referral_code');
    }

    public function test_another_users_code_cannot_be_duplicated(): void
    {
        $other = User::factory()->create([
            'username' => 'other_' . uniqid(),
            'email' => 'other_' . uniqid() . '@test.local',
        ]);
        $other->referral_code = 'taken-code';
        $other->save();

        $this->actingAs($this->user)
            ->from(route('profile.refer', ['username' => $this->user->username]))
            ->put(route('profile.refer.code', ['username' => $this->user->username]), [
                'referral_code' => 'taken-code',
            ])
            ->assertSessionHasErrors('referral_code');
    }
}
