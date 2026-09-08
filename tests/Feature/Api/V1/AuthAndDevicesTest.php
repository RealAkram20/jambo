<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Services\TwoFactorAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Streaming\app\Models\ActiveStream;
use Modules\Streaming\app\Models\Device;
use Tests\TestCase;

/**
 * Token sign-in and the device list — the app's front door.
 *
 * These are the endpoints everything else in the app depends on, so the cases
 * here are the ones that would strand a real viewer: a wrong password, a
 * deactivated account, a phone that was signed out from another device, and
 * the throttle that must not lock out a whole cell tower.
 */
class AuthAndDevicesTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear($this->throttleKey());
    }

    // ── sign-in ──────────────────────────────────────────────────────

    public function test_a_viewer_signs_in_and_gets_a_token_bound_to_their_device(): void
    {
        $user = $this->viewer();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => $this->device(),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.device.uuid', 'device-uuid-0001')
            ->assertJsonStructure(['success', 'message', 'data' => ['token', 'device', 'user']]);

        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'uuid' => 'device-uuid-0001',
            'platform' => Device::PLATFORM_ANDROID,
            'revoked_at' => null,
        ]);

        // The token must actually work.
        $this->withFreshToken($response->json('data.token'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_the_email_is_matched_case_insensitively_like_the_website(): void
    {
        $user = $this->viewer();

        $this->postJson('/api/v1/auth/login', [
            'email' => strtoupper($user->email),
            'password' => self::PASSWORD,
            'device' => $this->device(),
        ])->assertOk();
    }

    public function test_a_wrong_password_does_not_reveal_whether_the_account_exists(): void
    {
        $user = $this->viewer();

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'not-the-password',
            'device' => $this->device(),
        ]);

        $noSuchAccount = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.test',
            'password' => 'not-the-password',
            'device' => $this->device(),
        ]);

        $wrongPassword->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $noSuchAccount->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $this->assertSame($wrongPassword->json('message'), $noSuchAccount->json('message'));
    }

    public function test_a_deactivated_account_is_refused_with_its_own_code(): void
    {
        $user = $this->viewer(['deactivated_at' => now()]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => $this->device(),
        ])->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_DEACTIVATED');
    }

    public function test_sign_in_requires_a_device_block(): void
    {
        $user = $this->viewer();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_an_unknown_platform_is_refused(): void
    {
        $user = $this->viewer();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => ['uuid' => 'device-uuid-0001', 'platform' => 'playstation'],
        ])->assertStatus(422);
    }

    public function test_repeated_wrong_passwords_are_throttled_on_email_plus_ip(): void
    {
        $user = $this->viewer();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong',
                'device' => $this->device(),
            ])->assertStatus(401);
        }

        // The sixth is refused before the password is even checked, and the
        // right password does not get through either.
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => $this->device(),
        ])->assertStatus(429)->assertJsonPath('code', 'RATE_LIMITED');
    }

    /**
     * The bucket is keyed on email+ip, so one person failing repeatedly must
     * not lock out everyone else behind the same carrier NAT address.
     */
    public function test_one_throttled_account_does_not_lock_out_another_on_the_same_ip(): void
    {
        $victim = $this->viewer();
        $other = $this->viewer();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $victim->email,
                'password' => 'wrong',
                'device' => $this->device(),
            ]);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $other->email,
            'password' => self::PASSWORD,
            'device' => $this->device('device-uuid-0002'),
        ])->assertOk();
    }

    // ── two-factor ───────────────────────────────────────────────────

    public function test_two_factor_login_returns_a_challenge_instead_of_a_token(): void
    {
        $user = $this->viewerWithTwoFactor();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => $this->device(),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'TWO_FACTOR_REQUIRED')
            ->assertJsonStructure(['challenge_token']);

        $this->assertNull($response->json('data.token'));
        $this->assertDatabaseCount('devices', 0);
    }

    public function test_a_valid_totp_code_completes_the_challenge_and_issues_the_token(): void
    {
        $user = $this->viewerWithTwoFactor();

        $challenge = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => $this->device(),
        ])->json('challenge_token');

        $response = $this->postJson('/api/v1/auth/2fa/challenge', [
            'challenge_token' => $challenge,
            'code' => $this->currentTotpFor($user),
        ]);

        $response->assertOk()->assertJsonPath('data.device.uuid', 'device-uuid-0001');
        $this->assertDatabaseHas('devices', ['user_id' => $user->id, 'uuid' => 'device-uuid-0001']);
    }

    public function test_a_wrong_code_is_refused_but_does_not_burn_the_challenge(): void
    {
        $user = $this->viewerWithTwoFactor();

        $challenge = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => $this->device(),
        ])->json('challenge_token');

        $this->postJson('/api/v1/auth/2fa/challenge', [
            'challenge_token' => $challenge,
            'code' => '000000',
        ])->assertStatus(422)->assertJsonPath('code', 'TWO_FACTOR_INVALID');

        // A typo must not send the viewer back to the password screen.
        $this->postJson('/api/v1/auth/2fa/challenge', [
            'challenge_token' => $challenge,
            'code' => $this->currentTotpFor($user),
        ])->assertOk();
    }

    public function test_an_unknown_challenge_token_is_refused(): void
    {
        $this->postJson('/api/v1/auth/2fa/challenge', [
            'challenge_token' => 'never-issued',
            'code' => '123456',
        ])->assertStatus(401)->assertJsonPath('code', 'TWO_FACTOR_CHALLENGE_EXPIRED');
    }

    // ── devices ──────────────────────────────────────────────────────

    public function test_the_device_list_marks_the_calling_device(): void
    {
        $user = $this->viewer();
        $phone = $this->signIn($user, 'device-uuid-phone');
        $this->signIn($user, 'device-uuid-tv', Device::PLATFORM_ANDROID_TV);

        $response = $this->withFreshToken($phone)->getJson('/api/v1/devices')->assertOk();

        $devices = collect($response->json('data.devices'))->keyBy('uuid');
        $this->assertCount(2, $devices);
        $this->assertTrue($devices['device-uuid-phone']['is_current']);
        $this->assertFalse($devices['device-uuid-tv']['is_current']);
    }

    public function test_booting_a_device_kills_its_token_and_stops_its_streams(): void
    {
        $user = $this->viewer();
        $phone = $this->signIn($user, 'device-uuid-phone');
        $tv = $this->signIn($user, 'device-uuid-tv', Device::PLATFORM_ANDROID_TV);

        // The TV is mid-stream, keyed on its device uuid exactly as the
        // heartbeat will write it.
        ActiveStream::create([
            'user_id' => $user->id,
            'session_id' => 'device-uuid-tv',
            'watchable_type' => 'movie',
            'watchable_id' => 1,
            'last_beat_at' => now(),
        ]);

        $this->withFreshToken($phone)
            ->deleteJson('/api/v1/devices/device-uuid-tv')
            ->assertOk();

        // The TV's token is gone, so its next request is simply not signed in.
        $this->withFreshToken($tv)->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        $this->assertDatabaseHas('devices', ['uuid' => 'device-uuid-tv', 'personal_access_token_id' => null]);
        $this->assertNotNull(
            ActiveStream::where('session_id', 'device-uuid-tv')->first()->terminated_at,
            'Booting a device must stop what it was playing counting against the cap.'
        );

        // The phone that did the booting is untouched.
        $this->withFreshToken($phone)->getJson('/api/v1/me')->assertOk();
    }

    public function test_a_device_belonging_to_someone_else_reads_as_not_found(): void
    {
        $mine = $this->signIn($this->viewer(), 'device-uuid-mine');
        $this->signIn($this->viewer(), 'device-uuid-theirs');

        $this->withFreshToken($mine)
            ->deleteJson('/api/v1/devices/device-uuid-theirs')
            ->assertStatus(404)
            ->assertJsonPath('code', 'DEVICE_NOT_FOUND');

        // And it is genuinely still signed in.
        $this->assertDatabaseHas('devices', ['uuid' => 'device-uuid-theirs', 'revoked_at' => null]);
    }

    public function test_signing_in_again_on_the_same_device_replaces_its_token(): void
    {
        $user = $this->viewer();
        $first = $this->signIn($user, 'device-uuid-0001');
        $second = $this->signIn($user, 'device-uuid-0001');

        $this->assertSame(1, Device::where('user_id', $user->id)->count());

        // The superseded token must not still work: tokens never expire here,
        // so anything left behind works forever.
        $this->withFreshToken($first)->getJson('/api/v1/me')->assertStatus(401);
        $this->withFreshToken($second)->getJson('/api/v1/me')->assertOk();
    }

    public function test_logging_out_signs_out_only_the_calling_device(): void
    {
        $user = $this->viewer();
        $phone = $this->signIn($user, 'device-uuid-phone');
        $tv = $this->signIn($user, 'device-uuid-tv', Device::PLATFORM_ANDROID_TV);

        $this->withFreshToken($phone)->postJson('/api/v1/auth/logout')->assertOk();

        $this->withFreshToken($phone)->getJson('/api/v1/me')->assertStatus(401);
        $this->withFreshToken($tv)->getJson('/api/v1/me')->assertOk();
    }

    public function test_a_revoked_device_whose_token_survived_is_still_refused(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user, 'device-uuid-0001');

        // Simulate a half-completed revoke: the row is flagged but the token
        // row was not deleted. The middleware is the second line of defence.
        Device::where('uuid', 'device-uuid-0001')->update(['revoked_at' => now()]);

        $this->withFreshToken($token)->getJson('/api/v1/me')
            ->assertStatus(403)
            ->assertJsonPath('code', 'DEVICE_REVOKED');
    }

    // ── envelope and config ──────────────────────────────────────────

    public function test_an_unauthenticated_request_answers_json_not_a_redirect(): void
    {
        $this->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_app_config_is_public_and_carries_the_server_clock(): void
    {
        $this->getJson('/api/v1/app/config')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['min_app_version', 'server_time', 'features' => ['downloads', 'in_app_subscribe']],
            ]);
    }

    // ── helpers ──────────────────────────────────────────────────────

    /** @param array<string, mixed> $attributes */
    private function viewer(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'password' => Hash::make(self::PASSWORD),
        ]);
    }

    private function viewerWithTwoFactor(): User
    {
        $user = $this->viewer();
        app(TwoFactorAuthentication::class)->generatePendingSetup($user);
        $user->refresh();
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $user->refresh();
    }

    private function currentTotpFor(User $user): string
    {
        // TwoFactorAuthentication stores the secret with Crypt::encryptString,
        // so it must come back with decryptString. decrypt() unserializes and
        // dies on a plain string.
        $secret = Crypt::decryptString($user->two_factor_secret);

        return app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($secret);
    }

    /**
     * Make the next request as a genuinely fresh client.
     *
     * Laravel's AuthManager is a container singleton and RequestGuard caches
     * the user it resolved, so within one test every later request reuses the
     * first resolution. That makes a deleted token look like it still works.
     * In production each request is its own process and no such cache exists,
     * so forgetting the guards here is what makes the test honest rather than
     * what makes it pass. Without this line, every revocation assertion below
     * would succeed no matter how broken revocation was.
     */
    private function withFreshToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    /** @return array<string, string> */
    private function device(string $uuid = 'device-uuid-0001', string $platform = Device::PLATFORM_ANDROID): array
    {
        return [
            'uuid' => $uuid,
            'platform' => $platform,
            'name' => 'Test Handset',
            'model' => 'Tecno Spark 10',
            'app_version' => '1.0.0',
        ];
    }

    /** Signs in and returns the plain-text token. */
    private function signIn(User $user, string $uuid, string $platform = Device::PLATFORM_ANDROID): string
    {
        RateLimiter::clear($this->throttleKey($user->email));

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => $this->device($uuid, $platform),
        ])->assertOk()->json('data.token');
    }

    private function throttleKey(string $email = ''): string
    {
        return strtolower($email) . '|127.0.0.1';
    }
}
