<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Streaming\app\Models\Device;
use Modules\Streaming\app\Services\AccountDeviceRegistry;
use Tests\TestCase;

/**
 * Gap 5 of docs/api/coverage.md: one account, one device list.
 *
 * The problem this closes is concrete. A viewer hits their stream cap because
 * a laptop at home is still signed in. They are holding their phone. Before
 * this, the app could list and boot app installs only, so there was nothing
 * they could do from where they were standing.
 */
class AccountDevicesTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    public function test_the_list_carries_browser_sessions_beside_app_installs(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user, 'device-uuid-phone1');
        $this->seedBrowserSession($user, 'browser-session-id-0001');

        $devices = collect(
            $this->withFreshToken($token)->getJson('/api/v1/devices')->assertOk()->json('data.devices')
        )->keyBy('id');

        $this->assertCount(2, $devices);
        $this->assertSame('app', $devices['device-uuid-phone1']['kind']);
        $this->assertSame('browser', $devices['browser-session-id-0001']['kind']);
    }

    /** The whole point: free a slot held by a laptop, from a phone. */
    public function test_a_phone_can_sign_out_a_browser(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user, 'device-uuid-phone1');
        $this->seedBrowserSession($user, 'browser-session-id-0001');

        $this->withFreshToken($token)
            ->deleteJson('/api/v1/devices/browser-session-id-0001')
            ->assertOk();

        $this->assertDatabaseMissing('sessions', ['id' => 'browser-session-id-0001']);
    }

    public function test_another_accounts_browser_session_cannot_be_booted(): void
    {
        $mine = $this->viewer();
        $theirs = $this->viewer();
        $token = $this->signIn($mine, 'device-uuid-mine01');
        $this->seedBrowserSession($theirs, 'their-session-id-0001');

        $this->withFreshToken($token)
            ->deleteJson('/api/v1/devices/their-session-id-0001')
            ->assertStatus(404)
            ->assertJsonPath('code', 'DEVICE_NOT_FOUND');

        $this->assertDatabaseHas('sessions', ['id' => 'their-session-id-0001']);
    }

    // ── the cap setting ──────────────────────────────────────────────

    /**
     * Ships OFF. Switching it on tightens the live site for anyone who has
     * both a browser and the app — they would start meeting the device picker
     * where they did not before. That is a product decision, not a deploy.
     */
    public function test_app_devices_do_not_count_against_the_cap_by_default(): void
    {
        $user = $this->viewer();
        $this->signIn($user, 'device-uuid-phone1');
        $this->seedBrowserSession($user, 'browser-session-id-0001');

        $this->assertFalse(AccountDeviceRegistry::appDevicesCount());
        $this->assertSame(
            1,
            app(AccountDeviceRegistry::class)->countAgainstCap($user->id),
            'Only the browser session counts while the setting is off.'
        );
    }

    public function test_the_setting_makes_app_devices_count(): void
    {
        setting(['streams.count_app_devices', 1]);

        $user = $this->viewer();
        $this->signIn($user, 'device-uuid-phone1');
        $this->seedBrowserSession($user, 'browser-session-id-0001');

        $this->assertTrue(AccountDeviceRegistry::appDevicesCount());
        $this->assertSame(2, app(AccountDeviceRegistry::class)->countAgainstCap($user->id));
    }

    public function test_the_list_reports_which_mode_the_cap_is_in(): void
    {
        $token = $this->signIn($this->viewer(), 'device-uuid-phone1');

        $this->withFreshToken($token)->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonPath('data.counts_app_devices', false);
    }

    /** A session idle past the session lifetime is not signed in any more. */
    public function test_a_stale_browser_session_is_not_listed(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user, 'device-uuid-phone1');
        $this->seedBrowserSession($user, 'stale-session-id-00001', lastActivity: now()->subDays(30));

        $ids = collect(
            $this->withFreshToken($token)->getJson('/api/v1/devices')->json('data.devices')
        )->pluck('id');

        $this->assertNotContains('stale-session-id-00001', $ids->all());
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function seedBrowserSession(User $user, string $id, $lastActivity = null): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '41.210.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0 Safari/537.36',
            'payload' => base64_encode('test'),
            'last_activity' => ($lastActivity ?? now())->timestamp,
        ]);
    }

    private function signIn(User $user, string $uuid): string
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
