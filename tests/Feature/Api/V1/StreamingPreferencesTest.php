<?php

namespace Tests\Feature\Api\V1;

use App\Http\Controllers\Api\V1\StreamingPreferencesController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Streaming\app\Models\Device;
use Tests\TestCase;

/**
 * Streaming preferences: how a viewer wants their video delivered.
 *
 * Added 2026-09-09 for the mobile app's profile drawer, after Rio's ruling
 * that the app is a pure streaming experience and that these settings follow
 * the account rather than the handset.
 *
 * The load-bearing cases are not the ones that save a value. They are the
 * three that stop a preference from silently becoming the wrong thing: a
 * partial PATCH must not reset what it did not send, a quality this build does
 * not recognise must fall back rather than reach the player, and one viewer's
 * choices must never be visible to another.
 */
class StreamingPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    public function test_preferences_need_a_signed_in_viewer(): void
    {
        $this->getJson('/api/v1/account/preferences')->assertStatus(401);
        $this->patchJson('/api/v1/account/preferences', ['autoplay_next' => false])
            ->assertStatus(401);
    }

    /**
     * A viewer who has never opened the screen gets a complete set.
     *
     * Nulls would make every client branch on a missing key, which is how a
     * boolean setting acquires a third state.
     */
    public function test_an_untouched_account_reads_the_defaults(): void
    {
        $user = $this->viewer();
        $this->assertNull($user->streaming_preferences);

        $this->withFreshToken($this->signIn($user))
            ->getJson('/api/v1/account/preferences')
            ->assertOk()
            ->assertJsonPath('data.preferences.video_quality', 'auto')
            ->assertJsonPath('data.preferences.autoplay_next', true)
            ->assertJsonPath('data.preferences.wifi_only_downloads', true);
    }

    /**
     * `auto` and Wi-Fi-only downloads are the defaults on purpose, and the
     * reason is the network rather than taste: somebody on MTN data who has
     * never opened this screen should not be spending their bundle at the
     * highest rendition, or downloading on it at all.
     */
    public function test_the_defaults_are_the_data_careful_ones(): void
    {
        $this->assertSame('auto', StreamingPreferencesController::DEFAULTS['video_quality']);
        $this->assertTrue(StreamingPreferencesController::DEFAULTS['wifi_only_downloads']);
    }

    public function test_saving_a_preference_and_reading_it_back(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)
            ->patchJson('/api/v1/account/preferences', ['video_quality' => 'data_saver'])
            ->assertOk()
            ->assertJsonPath('data.preferences.video_quality', 'data_saver');

        // Read back through a second request, not from the write's own
        // response: the point of the column is that it survives the request.
        $this->withFreshToken($token)
            ->getJson('/api/v1/account/preferences')
            ->assertOk()
            ->assertJsonPath('data.preferences.video_quality', 'data_saver');
    }

    /**
     * The one that protects a setting nobody touched.
     *
     * A screen of switches sends the one that moved. If a partial PATCH reset
     * the others to their defaults, a viewer turning autoplay off would also
     * turn their data saver off — and they would have no way to know it, or to
     * connect the two.
     */
    public function test_a_partial_patch_leaves_the_other_settings_alone(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->patchJson('/api/v1/account/preferences', [
            'video_quality' => 'data_saver',
            'wifi_only_downloads' => false,
        ])->assertOk();

        $this->withFreshToken($token)
            ->patchJson('/api/v1/account/preferences', ['autoplay_next' => false])
            ->assertOk()
            ->assertJsonPath('data.preferences.autoplay_next', false)
            ->assertJsonPath('data.preferences.video_quality', 'data_saver')
            ->assertJsonPath('data.preferences.wifi_only_downloads', false);
    }

    /** A write answers with the whole set, so a client never has to merge. */
    public function test_a_patch_answers_with_the_complete_set(): void
    {
        $user = $this->viewer();

        $this->withFreshToken($this->signIn($user))
            ->patchJson('/api/v1/account/preferences', ['autoplay_next' => false])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'preferences' => ['video_quality', 'autoplay_next', 'wifi_only_downloads'],
                ],
            ]);
    }

    /**
     * Only the three renditions policies the platform can honour.
     *
     * Jambo has two renditions. "1080p" is not one of them, and a value that
     * reached the player would ask Bunny for a file that does not exist — a
     * spinner with no error rather than a rejected request.
     */
    public function test_an_unknown_quality_is_refused(): void
    {
        $user = $this->viewer();

        $this->withFreshToken($this->signIn($user))
            ->patchJson('/api/v1/account/preferences', ['video_quality' => '1080p'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * A value written straight to the column by an older or newer build still
     * comes back as something the player can use.
     *
     * Validation guards the endpoint, not the column. This is the case where
     * the row is already wrong — a rolled-back deploy, a console edit — and
     * the read has to cope rather than hand the mistake onward.
     */
    public function test_a_stored_quality_this_build_does_not_know_falls_back(): void
    {
        $user = $this->viewer();
        $user->forceFill(['streaming_preferences' => ['video_quality' => 'uhd']])->save();

        $this->withFreshToken($this->signIn($user))
            ->getJson('/api/v1/account/preferences')
            ->assertOk()
            ->assertJsonPath('data.preferences.video_quality', 'auto');
    }

    /**
     * A key from a newer build does not travel back to an older one.
     *
     * The response shape is the shape this build knows. Passing an unknown key
     * through would put a value on screen that no code here can interpret.
     */
    public function test_unknown_stored_keys_are_not_returned(): void
    {
        $user = $this->viewer();
        $user->forceFill([
            'streaming_preferences' => ['video_quality' => 'high', 'subtitle_language' => 'lg'],
        ])->save();

        $this->withFreshToken($this->signIn($user))
            ->getJson('/api/v1/account/preferences')
            ->assertOk()
            ->assertJsonPath('data.preferences.video_quality', 'high')
            ->assertJsonMissingPath('data.preferences.subtitle_language');
    }

    /** Preferences are per account. The obvious guard, and the cheapest. */
    public function test_one_viewer_cannot_see_anothers_preferences(): void
    {
        $other = $this->viewer();
        $other->forceFill(['streaming_preferences' => ['video_quality' => 'data_saver']])->save();

        $mine = $this->viewer();

        $this->withFreshToken($this->signIn($mine, 'device-uuid-pref02'))
            ->getJson('/api/v1/account/preferences')
            ->assertOk()
            ->assertJsonPath('data.preferences.video_quality', 'auto');

        $this->assertSame(
            'data_saver',
            $other->refresh()->streaming_preferences['video_quality'],
        );
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function signIn(User $user, string $uuid = 'device-uuid-pref01'): string
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
