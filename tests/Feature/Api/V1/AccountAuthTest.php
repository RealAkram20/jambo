<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Streaming\app\Models\Device;
use Tests\TestCase;

/**
 * Account creation and recovery from the app — gap 1 in docs/api/coverage.md.
 *
 * Until these existed the app could only be used by someone who already had an
 * account and remembered the password, which on the consumption-only Play
 * build meant a new viewer could not start at all.
 */
class AccountAuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    private function device(string $uuid = 'device-uuid-acct01'): array
    {
        return ['uuid' => $uuid, 'platform' => Device::PLATFORM_ANDROID];
    }

    private function withFreshToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    // ── registration ─────────────────────────────────────────────────

    public function test_registering_creates_a_signed_in_account(): void
    {
        Event::fake([Registered::class]);

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'username' => 'adalovelace',
            'email' => 'ada@example.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'device' => $this->device(),
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'ada@example.test', 'username' => 'adalovelace']);
        Event::assertDispatched(Registered::class);

        // Signed in immediately, as the website does.
        $this->withFreshToken($response->json('data.token'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.username', 'adalovelace');

        // A device row too, so the account can see and boot this install.
        $this->assertDatabaseHas('devices', ['uuid' => 'device-uuid-acct01']);
    }

    public function test_registration_rejects_a_duplicate_email_or_username(): void
    {
        User::factory()->create(['email' => 'taken@example.test', 'username' => 'taken']);

        $base = [
            'first_name' => 'A', 'last_name' => 'B',
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD,
            'device' => $this->device(),
        ];

        $this->postJson('/api/v1/auth/register', $base + [
            'username' => 'someoneelse', 'email' => 'taken@example.test',
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_FAILED');

        $this->postJson('/api/v1/auth/register', $base + [
            'username' => 'taken', 'email' => 'fresh@example.test',
        ])->assertStatus(422);
    }

    /**
     * The username becomes the account's referral code and sits at
     * /{username}, so a reserved word would be a route collision and an
     * impersonation handle.
     */
    public function test_registration_refuses_a_reserved_username(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'A', 'last_name' => 'B',
            'username' => 'admin',
            'email' => 'admin-wannabe@example.test',
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD,
            'device' => $this->device(),
        ])->assertStatus(422);
    }

    public function test_registration_requires_a_confirmed_password_and_a_device(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'A', 'last_name' => 'B',
            'username' => 'mismatch', 'email' => 'mismatch@example.test',
            'password' => self::PASSWORD, 'password_confirmation' => 'something-else',
            'device' => $this->device(),
        ])->assertStatus(422);

        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'A', 'last_name' => 'B',
            'username' => 'nodevice', 'email' => 'nodevice@example.test',
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD,
        ])->assertStatus(422);
    }

    public function test_a_failed_registration_is_logged_for_support(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'A', 'last_name' => 'B',
            'username' => 'x', // too short
            'email' => 'not-an-email',
            'password' => 'short', 'password_confirmation' => 'short',
            'device' => $this->device(),
        ])->assertStatus(422);

        $this->assertDatabaseHas('signup_attempts', ['outcome' => 'validation_error']);
    }

    // ── password recovery ────────────────────────────────────────────

    /**
     * The response must be identical whether or not the address has an
     * account, or the endpoint becomes an account-enumeration oracle.
     */
    public function test_forgot_password_does_not_reveal_whether_an_account_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'real@example.test']);

        $real = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'real@example.test'])->assertOk();
        $fake = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.test'])->assertOk();

        $this->assertSame($real->json('message'), $fake->json('message'));

        // The mail still only goes to a real account.
        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertCount(1);
    }

    public function test_a_reset_token_sets_a_new_password_and_signs_out_every_device(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.test',
            'password' => Hash::make('the-old-password'),
        ]);

        // A phone is already signed in; a reset must not leave it working.
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'reset@example.test', 'password' => 'the-old-password',
            'device' => $this->device('device-uuid-oldone'),
        ])->assertOk()->json('data.token');

        $reset = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $reset,
            'email' => 'reset@example.test',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));

        // Whoever knew the old password is out everywhere.
        $this->withFreshToken($token)->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_a_bad_reset_token_is_refused(): void
    {
        User::factory()->create(['email' => 'reset2@example.test']);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'reset2@example.test',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    // ── changing a password ──────────────────────────────────────────

    public function test_changing_a_password_needs_the_current_one(): void
    {
        $token = $this->signIn($this->viewer('change@example.test'));

        $this->withFreshToken($token)->putJson('/api/v1/auth/password', [
            'current_password' => 'not-the-current-one',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertStatus(422);
    }

    /**
     * The device that made the change keeps working — a viewer must not be
     * thrown out of the app they are standing in — but every other one is
     * signed out, matching the website's logoutOtherDevices.
     */
    public function test_changing_a_password_signs_out_other_devices_but_not_this_one(): void
    {
        $user = $this->viewer('change2@example.test');
        $phone = $this->signIn($user, 'device-uuid-phone1');
        $tv = $this->signIn($user, 'device-uuid-tvone1');

        $this->withFreshToken($phone)->putJson('/api/v1/auth/password', [
            'current_password' => self::PASSWORD,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertOk();

        $this->withFreshToken($tv)->getJson('/api/v1/me')->assertStatus(401);
        $this->withFreshToken($phone)->getJson('/api/v1/me')->assertOk();
    }

    // ── email verification ───────────────────────────────────────────

    public function test_verification_status_and_resend(): void
    {
        Notification::fake();
        $user = $this->viewer('verify@example.test');
        $user->forceFill(['email_verified_at' => null])->save();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->getJson('/api/v1/auth/email')
            ->assertOk()
            ->assertJsonPath('data.verified', false);

        $this->withFreshToken($token)->postJson('/api/v1/auth/email/resend')
            ->assertOk()
            ->assertJsonPath('data.verified', false);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->withFreshToken($token)->postJson('/api/v1/auth/email/resend')
            ->assertOk()
            ->assertJsonPath('data.verified', true);
    }

    // ── Google sign-in ───────────────────────────────────────────────

    private function googleReturns(array $payload): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response($payload, 200),
        ]);
    }

    public function test_google_sign_in_creates_an_account_and_issues_a_token(): void
    {
        config(['services.google.client_id' => 'jambo-app-client-id']);
        $this->googleReturns([
            'aud' => 'jambo-app-client-id',
            'email' => 'gada@example.test',
            'email_verified' => 'true',
            'name' => 'Grace Hopper',
        ]);

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'a-google-id-token',
            'device' => $this->device(),
        ])->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'gada@example.test']);

        $this->withFreshToken($response->json('data.token'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.first_name', 'Grace');
    }

    /**
     * A valid Google token issued to somebody else's app is still a valid
     * Google token. Without the audience check anyone with any Google client
     * could mint sign-ins here.
     */
    public function test_a_token_issued_for_another_app_is_refused(): void
    {
        config(['services.google.client_id' => 'jambo-app-client-id']);
        $this->googleReturns([
            'aud' => 'some-other-apps-client-id',
            'email' => 'attacker@example.test',
            'email_verified' => 'true',
        ]);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'a-token-for-another-app',
            'device' => $this->device(),
        ])->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');

        $this->assertDatabaseMissing('users', ['email' => 'attacker@example.test']);
    }

    /**
     * The Android app signs in with its OWN OAuth client.
     *
     * Google keys an Android client to the package name and the signing
     * certificate, and puts that client's id in the token's `aud` — so it is
     * legitimately a different string from the Web client the website uses.
     * One hardcoded audience cannot serve both surfaces, which is why the
     * check reads a list.
     */
    public function test_a_token_issued_for_the_android_app_is_accepted(): void
    {
        config([
            'services.google.client_id' => 'jambo-web-client-id',
            'services.google.client_ids' => ['jambo-web-client-id', 'jambo-android-client-id'],
        ]);
        $this->googleReturns([
            'aud' => 'jambo-android-client-id',
            'email' => 'onthephone@example.test',
            'email_verified' => 'true',
            'name' => 'Ada Lovelace',
        ]);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'a-token-from-the-android-app',
            'device' => $this->device(),
        ])->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'onthephone@example.test']);
    }

    /**
     * 🔴 Widening the audience check to a list must not widen it to
     * everything.
     *
     * This is the same attack the single-audience test covers, re-run against
     * the list, because "accepts more than one of ours" and "accepts anyone's"
     * are one careless `in_array` apart — a loose comparison, an empty list
     * treated as a wildcard, or a fallback that passes when nothing matches.
     */
    public function test_a_third_party_token_is_still_refused_when_several_clients_are_configured(): void
    {
        config([
            'services.google.client_id' => 'jambo-web-client-id',
            'services.google.client_ids' => ['jambo-web-client-id', 'jambo-android-client-id'],
        ]);
        $this->googleReturns([
            'aud' => 'some-other-apps-client-id',
            'email' => 'attacker@example.test',
            'email_verified' => 'true',
        ]);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'a-token-for-another-app',
            'device' => $this->device(),
        ])->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');

        $this->assertDatabaseMissing('users', ['email' => 'attacker@example.test']);
    }

    /**
     * 🔴 A non-string audience must not authenticate as every client at once.
     *
     * This test exists because deleting the `true` from `in_array(..., true)`
     * changed nothing: every audience in the other tests is a plain
     * non-numeric string, where loose and strict comparison agree, so the
     * strict flag was correct and unobserved.
     *
     * The state it actually defends against is a boolean. In PHP,
     * `true == 'jambo-web-client-id'` is TRUE, because any non-empty string is
     * truthy — so under a loose comparison an `aud` of `true` matches the
     * FIRST configured client and mints a session for whatever email came with
     * it. Google will not send that over HTTPS today; the check costs one
     * keyword and the failure it prevents is silent account takeover.
     */
    public function test_a_non_string_audience_is_refused_rather_than_matching_everything(): void
    {
        config([
            'services.google.client_id' => 'jambo-web-client-id',
            'services.google.client_ids' => ['jambo-web-client-id', 'jambo-android-client-id'],
        ]);
        $this->googleReturns([
            // Not a string, and truthy. The state the strict comparison exists for.
            'aud' => true,
            'email' => 'juggled@example.test',
            'email_verified' => 'true',
        ]);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'a-token-with-a-boolean-audience',
            'device' => $this->device(),
        ])->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');

        $this->assertDatabaseMissing('users', ['email' => 'juggled@example.test']);
    }

    /**
     * A server with no Google client configured refuses rather than accepting
     * whatever arrives. An empty allow-list is not a wildcard.
     */
    public function test_google_sign_in_is_refused_when_no_client_is_configured(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_ids' => []]);
        $this->googleReturns([
            'aud' => 'anything-at-all',
            'email' => 'nobody@example.test',
            'email_verified' => 'true',
        ]);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'a-token',
            'device' => $this->device(),
        ])->assertStatus(500);

        $this->assertDatabaseMissing('users', ['email' => 'nobody@example.test']);
    }

    public function test_an_unverified_google_email_cannot_adopt_an_account(): void
    {
        config(['services.google.client_id' => 'jambo-app-client-id']);
        $this->googleReturns([
            'aud' => 'jambo-app-client-id',
            'email' => 'unproven@example.test',
            'email_verified' => 'false',
        ]);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'a-token-without-a-proven-email',
            'device' => $this->device(),
        ])->assertStatus(401);

        $this->assertDatabaseMissing('users', ['email' => 'unproven@example.test']);
    }

    /**
     * Google being unreachable must read as "could not verify", never as a
     * pass. The destructive branch here is granting access.
     */
    public function test_google_being_unreachable_refuses_rather_than_allows(): void
    {
        config(['services.google.client_id' => 'jambo-app-client-id']);
        Http::fake(['oauth2.googleapis.com/*' => Http::response('', 500)]);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'a-token',
            'device' => $this->device(),
        ])->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    /**
     * Google proves the mailbox, not possession of the viewer's authenticator,
     * so an account with 2FA still gets the challenge.
     */
    public function test_google_sign_in_still_honours_two_factor(): void
    {
        config(['services.google.client_id' => 'jambo-app-client-id']);

        $user = $this->viewer('twofa@example.test');
        app(\App\Services\TwoFactorAuthentication::class)->generatePendingSetup($user);
        $user->refresh()->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->googleReturns([
            'aud' => 'jambo-app-client-id',
            'email' => 'twofa@example.test',
            'email_verified' => 'true',
        ]);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'a-google-id-token',
            'device' => $this->device(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'TWO_FACTOR_REQUIRED')
            ->assertJsonStructure(['challenge_token']);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function viewer(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
        ]);
    }

    private function signIn(User $user, string $uuid = 'device-uuid-acct01'): string
    {
        RateLimiter::clear(strtolower($user->email) . '|127.0.0.1');

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => $this->device($uuid),
        ])->assertOk()->json('data.token');
    }
}
