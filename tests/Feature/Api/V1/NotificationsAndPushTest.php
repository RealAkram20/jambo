<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Streaming\app\Models\Device;
use Tests\TestCase;

/**
 * Gap 3 of docs/api/coverage.md: the notification list, and the FCM registry.
 *
 * Two rules from `~/.claude/skills/background-push` carry these tests.
 *
 * Rule 1 — the token is deleted on sign-out. A shared handset that keeps the
 * previous account's token delivers their notifications, on the lock screen,
 * to whoever is holding the phone. That is the assertion that matters most in
 * this file.
 *
 * Rule 3 — push is an accelerator, never the transport. Everything a push says
 * must be readable from GET /notifications, so the app works with push off.
 */
class NotificationsAndPushTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    // ── the polled list (the fallback that must always work) ─────────

    public function test_the_list_needs_a_signed_in_viewer(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }

    public function test_the_list_carries_items_and_an_unread_count(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->seedNotification($user, 'Your plan renews soon');
        $this->seedNotification($user, 'New episode available');

        $this->withFreshToken($token)->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 2)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.read', false);
    }

    public function test_one_viewers_notifications_are_not_anothers(): void
    {
        $mine = $this->viewer();
        $theirs = $this->viewer();
        $token = $this->signIn($mine, 'device-uuid-mine01');

        $this->seedNotification($theirs, 'Not for you');

        $this->withFreshToken($token)->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    public function test_marking_read_one_and_all(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);
        $first = $this->seedNotification($user, 'One');
        $this->seedNotification($user, 'Two');

        $this->withFreshToken($token)->postJson("/api/v1/notifications/{$first}/read")
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $this->withFreshToken($token)->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_deleting_one_and_all_is_idempotent(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);
        $id = $this->seedNotification($user, 'Delete me');

        $this->withFreshToken($token)->deleteJson("/api/v1/notifications/{$id}")->assertOk();
        // A retry over a dropped connection must not fail.
        $this->withFreshToken($token)->deleteJson("/api/v1/notifications/{$id}")->assertOk();

        $this->seedNotification($user, 'And me');
        $this->withFreshToken($token)->deleteJson('/api/v1/notifications')->assertOk();

        $this->withFreshToken($token)->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    public function test_marking_someone_elses_notification_read_is_a_not_found(): void
    {
        $mine = $this->viewer();
        $theirs = $this->viewer();
        $token = $this->signIn($mine, 'device-uuid-mine01');
        $id = $this->seedNotification($theirs, 'Theirs');

        $this->withFreshToken($token)->postJson("/api/v1/notifications/{$id}/read")
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    // ── preferences ──────────────────────────────────────────────────

    public function test_preferences_round_trip(): void
    {
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)->putJson('/api/v1/notifications/preferences', [
            'preferences' => [
                ['key' => 'new_episode', 'in_app' => true, 'email' => false, 'push' => true],
            ],
        ])->assertOk();

        $this->withFreshToken($token)->getJson('/api/v1/notifications/preferences')
            ->assertOk()
            ->assertJsonPath('data.preferences.0.key', 'new_episode')
            ->assertJsonPath('data.preferences.0.email', false)
            ->assertJsonPath('data.preferences.0.push', true);
    }

    // ── the FCM registry ─────────────────────────────────────────────

    public function test_registering_a_push_token_stores_it_against_the_install(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user, 'device-uuid-phone1');

        $this->withFreshToken($token)->postJson('/api/v1/notifications/push-token', [
            'token' => 'fcm-token-for-this-handset',
        ])
            ->assertOk()
            ->assertJsonPath('data.registered', true);

        $this->assertDatabaseHas('devices', [
            'uuid' => 'device-uuid-phone1',
            'fcm_token' => 'fcm-token-for-this-handset',
        ]);
    }

    /**
     * The rule that matters most in this file.
     *
     * A handset gets passed around. If signing out leaves the token behind,
     * the next person holding the phone receives the previous account's
     * notifications, on the lock screen.
     */
    public function test_signing_out_deletes_the_push_token(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user, 'device-uuid-phone1');

        $this->withFreshToken($token)->postJson('/api/v1/notifications/push-token', [
            'token' => 'fcm-token-that-must-not-survive',
        ])->assertOk();

        $this->withFreshToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseMissing('devices', ['fcm_token' => 'fcm-token-that-must-not-survive']);
    }

    /** Booting a device from another one must clear its token too. */
    public function test_booting_a_device_deletes_its_push_token(): void
    {
        $user = $this->viewer();
        $phone = $this->signIn($user, 'device-uuid-phone1');
        $tv = $this->signIn($user, 'device-uuid-tvone1');

        $this->withFreshToken($tv)->postJson('/api/v1/notifications/push-token', [
            'token' => 'fcm-token-on-the-tv',
        ])->assertOk();

        $this->withFreshToken($phone)->deleteJson('/api/v1/devices/device-uuid-tvone1')->assertOk();

        $this->assertDatabaseMissing('devices', ['fcm_token' => 'fcm-token-on-the-tv']);
    }

    /**
     * Android reissues the same token to a reinstall. Two rows holding it
     * would mean one push delivered twice and a receipt that cannot be matched
     * back to an install.
     */
    public function test_a_token_can_only_belong_to_one_install(): void
    {
        $user = $this->viewer();
        $phone = $this->signIn($user, 'device-uuid-phone1');
        $tv = $this->signIn($user, 'device-uuid-tvone1');

        $this->withFreshToken($phone)->postJson('/api/v1/notifications/push-token', ['token' => 'shared-fcm-token'])->assertOk();
        $this->withFreshToken($tv)->postJson('/api/v1/notifications/push-token', ['token' => 'shared-fcm-token'])->assertOk();

        $this->assertSame(1, Device::where('fcm_token', 'shared-fcm-token')->count());
        $this->assertSame('device-uuid-tvone1', Device::where('fcm_token', 'shared-fcm-token')->first()->uuid);
    }

    public function test_a_viewer_can_turn_push_off_for_one_device(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user, 'device-uuid-phone1');

        $this->withFreshToken($token)->postJson('/api/v1/notifications/push-token', [
            'token' => 'fcm-token-abc', 'push_enabled' => false,
        ])->assertOk()->assertJsonPath('data.push_enabled', false);

        $this->assertSame(0, Device::query()->pushable()->count(), 'A device with push off is not pushable.');
    }

    public function test_unregistering_removes_the_token(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user, 'device-uuid-phone1');

        $this->withFreshToken($token)->postJson('/api/v1/notifications/push-token', ['token' => 'fcm-token-xyz'])->assertOk();
        $this->withFreshToken($token)->deleteJson('/api/v1/notifications/push-token')
            ->assertOk()
            ->assertJsonPath('data.registered', false);

        $this->assertDatabaseMissing('devices', ['fcm_token' => 'fcm-token-xyz']);
    }

    /** Only installs with a token, push on, and not revoked can be reached. */
    public function test_the_pushable_scope_is_what_a_sender_would_use(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user, 'device-uuid-phone1');
        $this->signIn($user, 'device-uuid-notok1'); // no token registered

        $this->withFreshToken($token)->postJson('/api/v1/notifications/push-token', ['token' => 'fcm-live'])->assertOk();

        $pushable = Device::query()->pushable()->get();

        $this->assertCount(1, $pushable);
        $this->assertSame('device-uuid-phone1', $pushable->first()->uuid);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function seedNotification(User $user, string $title): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'Modules\\Notifications\\app\\Notifications\\Generic',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => $title, 'message' => 'Body text.']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function signIn(User $user, string $uuid = 'device-uuid-notif1'): string
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
