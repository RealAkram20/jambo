<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Services\TwoFactorAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Modules\Streaming\app\Models\Device;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The profile screen and the security screen — gap 2 in docs/api/coverage.md.
 *
 * The load-bearing cases are the ones protecting the account rather than the
 * ones editing it: email is the recovery anchor, so changing it costs the
 * current password; turning a second factor off costs it too; and a
 * deactivated account must not keep watching on a live token.
 */
class ProfileAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    // ── profile ──────────────────────────────────────────────────────

    public function test_the_profile_screen_needs_a_signed_in_viewer(): void
    {
        $this->getJson('/api/v1/profile')->assertStatus(401);
    }

    public function test_reading_and_editing_a_profile(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.profile.username', $user->username);

        $this->withFreshToken($token)->patchJson('/api/v1/profile', [
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'username' => 'gracehopper',
            'email' => $user->email,
            'phone' => '+256 700 000 000',
        ])
            ->assertOk()
            ->assertJsonPath('data.profile.first_name', 'Grace')
            ->assertJsonPath('data.profile.username', 'gracehopper')
            /*
             * Normalised, not echoed. Updated 2026-09-10 when phone numbers
             * started being stored as E.164 — Rio's request, because the
             * column held the same number in fifteen shapes. Sending
             * `+256 700 000 000` and reading back `+256700000000` is the
             * change working, not a regression. See `App\Support\PhoneNumber`.
             */
            ->assertJsonPath('data.profile.phone', '+256700000000');
    }

    /**
     * The one that matters. With a stolen token an attacker could swap the
     * email and then "forgot password" their way into a full takeover, so
     * changing it costs the current password.
     */
    public function test_changing_the_email_costs_the_current_password(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $payload = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => 'attacker-controlled@example.test',
        ];

        $this->withFreshToken($token)->patchJson('/api/v1/profile', $payload)
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED');

        $this->assertNotSame('attacker-controlled@example.test', $user->fresh()->email);

        $this->withFreshToken($token)->patchJson('/api/v1/profile', $payload + [
            'current_password' => self::PASSWORD,
        ])->assertOk();

        $this->assertSame('attacker-controlled@example.test', $user->fresh()->email);
    }

    public function test_a_changed_email_must_be_verified_again(): void
    {
        $user = $this->viewer();
        $user->forceFill(['email_verified_at' => now()])->save();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->patchJson('/api/v1/profile', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => 'moved@example.test',
            'current_password' => self::PASSWORD,
        ])->assertOk()->assertJsonPath('data.profile.email_verified', false);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    /**
     * The link the response promises is actually sent.
     *
     * 🔴 **It was not.** `update()` cleared `email_verified_at` and
     * answered "Check your new address for a verification link" while sending
     * nothing, so the one moment somebody is watching their inbox for that
     * mail was the moment it did not come. The test above passed throughout,
     * because it asserted the flag and not the consequence — the same shape
     * jambo-49 and I traded findings about on 2026-09-10: assert what the code
     * is FOR, not only what it sets.
     *
     * Sent to the NEW address, which is what the signed URL has to match.
     *
     * The class asserted is the app's own queued notification, not the
     * framework's: `User::sendEmailVerificationNotification()` overrides the
     * default so the mail leaves on a queue rather than in the request.
     */
    public function test_changing_the_email_sends_a_verification_link_to_the_new_address(): void
    {
        Notification::fake();

        $user = $this->viewer();
        $user->forceFill(['email_verified_at' => now()])->save();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->patchJson('/api/v1/profile', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => 'moved@example.test',
            'current_password' => self::PASSWORD,
        ])->assertOk();

        Notification::assertSentTo(
            $user->fresh(),
            QueuedVerifyEmail::class,
            fn ($notification, $channels, $notifiable) => $notifiable->email === 'moved@example.test',
        );
    }

    /** A profile saved without touching the email sends nothing. */
    public function test_saving_a_profile_without_changing_the_email_sends_nothing(): void
    {
        Notification::fake();

        $user = $this->viewer();
        $user->forceFill(['email_verified_at' => now()])->save();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->patchJson('/api/v1/profile', [
            'first_name' => 'Renamed',
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => $user->email,
            'current_password' => self::PASSWORD,
        ])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_a_username_cannot_be_taken_or_reserved(): void
    {
        User::factory()->create(['username' => 'alreadytaken']);
        $user = $this->viewer();
        $token = $this->signIn($user);

        foreach (['alreadytaken', 'admin'] as $username) {
            $this->withFreshToken($token)->patchJson('/api/v1/profile', [
                'first_name' => 'A', 'last_name' => 'B',
                'username' => $username,
                'email' => $user->email,
            ])->assertStatus(422);
        }
    }

    public function test_uploading_and_removing_an_avatar(): void
    {
        Storage::fake('public');
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)
            ->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg')], ['Accept' => 'application/json'])
            ->assertOk();

        $this->withFreshToken($token)->deleteJson('/api/v1/profile/avatar')
            ->assertOk()
            ->assertJsonPath('data.profile.avatar_url', null);
    }

    public function test_an_avatar_must_be_an_image_within_the_size_bound(): void
    {
        Storage::fake('public');
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)
            ->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->create('virus.pdf', 40)], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    // ── two-factor ───────────────────────────────────────────────────

    public function test_the_security_screen_reports_the_two_factor_state(): void
    {
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)->getJson('/api/v1/account/security')
            ->assertOk()
            ->assertJsonPath('data.two_factor.enabled', false)
            ->assertJsonPath('data.two_factor.pending', false);
    }

    /**
     * Three calls on purpose. A one-shot enable would let someone lock
     * themselves out of their own account with a mis-scanned code.
     */
    public function test_two_factor_is_not_on_until_a_code_is_confirmed(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->postJson('/api/v1/account/2fa')
            ->assertOk()
            ->assertJsonStructure(['data' => ['secret', 'recovery_codes']]);

        $this->assertFalse($user->fresh()->hasEnabledTwoFactorAuthentication(), 'Starting setup must not switch it on.');

        $this->withFreshToken($token)->getJson('/api/v1/account/security')
            ->assertOk()
            ->assertJsonPath('data.two_factor.pending', true)
            ->assertJsonPath('data.two_factor.enabled', false);

        $this->withFreshToken($token)->postJson('/api/v1/account/2fa/confirm', ['code' => '000000'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'TWO_FACTOR_INVALID');

        $this->withFreshToken($token)->postJson('/api/v1/account/2fa/confirm', [
            'code' => $this->currentTotpFor($user->fresh()),
        ])->assertOk();

        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    /** Removing a second factor must cost more than holding an unlocked phone. */
    public function test_turning_two_factor_off_costs_the_password(): void
    {
        [$user, $token] = $this->signedInWithTwoFactor();

        $this->withFreshToken($token)->deleteJson('/api/v1/account/2fa')
            ->assertStatus(422);
        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());

        $this->withFreshToken($token)->deleteJson('/api/v1/account/2fa', ['password' => self::PASSWORD])
            ->assertOk();
        $this->assertFalse($user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_recovery_codes_can_be_regenerated_and_the_old_batch_dies(): void
    {
        [$user, $token] = $this->signedInWithTwoFactor();

        $before = $this->withFreshToken($token)->getJson('/api/v1/account/security')
            ->json('data.two_factor.recovery_codes');

        $after = $this->withFreshToken($token)->postJson('/api/v1/account/2fa/recovery-codes')
            ->assertOk()
            ->json('data.recovery_codes');

        $this->assertNotEmpty($before);
        $this->assertNotEmpty($after);
        $this->assertEmpty(array_intersect($before, $after), 'A regenerated batch must share nothing with the old one.');
    }

    public function test_recovery_codes_cannot_be_regenerated_when_two_factor_is_off(): void
    {
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)->postJson('/api/v1/account/2fa/recovery-codes')->assertStatus(422);
    }

    // ── deactivation ─────────────────────────────────────────────────

    public function test_deactivation_costs_the_password_and_an_explicit_confirmation(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->deleteJson('/api/v1/account', [
            'password' => self::PASSWORD,
        ])->assertStatus(422); // confirm missing

        $this->withFreshToken($token)->deleteJson('/api/v1/account', [
            'password' => 'wrong', 'confirm' => true,
        ])->assertStatus(422);

        $this->assertNull($user->fresh()->deactivated_at);
    }

    /**
     * A deactivated account is one `login` refuses. Leaving live tokens behind
     * would let the app keep watching on it until somebody noticed.
     */
    public function test_deactivating_signs_out_every_device(): void
    {
        $user = $this->viewer();
        $phone = $this->signIn($user, 'device-uuid-phone9');
        $tv = $this->signIn($user, 'device-uuid-tvnine');

        $this->withFreshToken($phone)->deleteJson('/api/v1/account', [
            'password' => self::PASSWORD, 'confirm' => true,
        ])->assertOk();

        $this->assertNotNull($user->fresh()->deactivated_at);
        $this->withFreshToken($phone)->getJson('/api/v1/me')->assertStatus(401);
        $this->withFreshToken($tv)->getJson('/api/v1/me')->assertStatus(401);
    }

    /**
     * Rio's switch, enforced on the server rather than only in the app.
     *
     * He asked for a delete button he can withdraw at any time
     * (2026-09-10). `/app/config` carries the flag so the row disappears, but
     * a build already on somebody's phone still draws it — so the endpoint
     * refuses on the same setting. Without this test the feature is a
     * client-side courtesy dressed as a control.
     */
    public function test_account_deletion_is_refused_when_the_admin_has_turned_it_off(): void
    {
        setting(['app.account_deletion_enabled', '0']);

        $user = $this->viewer();
        $token = $this->signIn($user, 'device-uuid-offswit');

        $this->withFreshToken($token)->deleteJson('/api/v1/account', [
            'password' => self::PASSWORD, 'confirm' => true,
        ])->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');

        $this->assertNull($user->fresh()->deactivated_at, 'The account must survive a refused request.');
    }

    /**
     * A missing settings row means ON.
     *
     * The two flags beside this one in `/app/config` default to false because
     * they gate unfinished features. This gates a finished one, and defaulting
     * it off would take away somebody's ability to close their own account on
     * any server where nobody had ever visited the settings page.
     */
    public function test_account_deletion_is_offered_by_default(): void
    {
        $this->assertTrue(
            (bool) $this->getJson('/api/v1/app/config')->assertOk()->json('data.features.account_deletion')
        );

        $user = $this->viewer();
        $token = $this->signIn($user, 'device-uuid-default');

        $this->withFreshToken($token)->deleteJson('/api/v1/account', [
            'password' => self::PASSWORD, 'confirm' => true,
        ])->assertOk();

        $this->assertNotNull($user->fresh()->deactivated_at);
    }

    /** The flag the app hides the row on follows the same setting. */
    public function test_app_config_reports_the_switch(): void
    {
        setting(['app.account_deletion_enabled', '0']);

        $this->getJson('/api/v1/app/config')
            ->assertOk()
            ->assertJsonPath('data.features.account_deletion', false);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    /**
     * Sign in FIRST, then switch two-factor on.
     *
     * Signing in afterwards is impossible by design: login correctly answers
     * TWO_FACTOR_REQUIRED for an account with a second factor, so a helper
     * that enabled it first could never obtain a token.
     *
     * @return array{0: User, 1: string}
     */
    private function signedInWithTwoFactor(): array
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        app(TwoFactorAuthentication::class)->generatePendingSetup($user);
        $user->refresh()->forceFill(['two_factor_confirmed_at' => now()])->save();

        return [$user->refresh(), $token];
    }

    private function currentTotpFor(User $user): string
    {
        return app(\PragmaRX\Google2FA\Google2FA::class)
            ->getCurrentOtp(Crypt::decryptString($user->two_factor_secret));
    }

    private function signIn(User $user, string $uuid = 'device-uuid-prof01'): string
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
