<?php

namespace Modules\Notifications\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notifications\app\Models\NotificationAudienceSetting;
use Modules\Notifications\app\Models\NotificationPreference;
use Modules\Notifications\app\Notifications\AdminBroadcastNotification;
use Modules\Notifications\database\seeders\NotificationsDatabaseSeeder;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * End-to-end checks that a notification type behaves the way the admin
 * switched it: enabled types actually deliver on the enabled channels,
 * disabled types deliver nothing, a user's own opt-out can only narrow,
 * and the preference UI reflects the super-admin ceiling exactly.
 *
 * Drives `admin_broadcast` (audience "All" → routed to the user audience
 * via the matrix) as the representative role-audience type, and asserts on
 * the resolved channels from ChannelGatedNotification::via() plus a real
 * dispatch landing (queue is `sync` under phpunit, so notify() runs inline).
 */
class NotificationGateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web'], ['title' => 'User']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web'], ['title' => 'Super Administrator']);

        // Seed the flat + per-audience switches so every type starts from
        // its shipped default, exactly like a fresh install.
        $this->seed(NotificationsDatabaseSeeder::class);

        $this->user = User::factory()->create([
            'username' => 'u_' . uniqid(),
            'email'    => 'u_' . uniqid() . '@test.local',
        ]);
        $this->user->assignRole('user');

        $this->admin = User::factory()->create([
            'username' => 'a_' . uniqid(),
            'email'    => 'a_' . uniqid() . '@test.local',
        ]);
        $this->admin->assignRole('admin');
    }

    /** Set a (key, audience) matrix row and drop the 1-min channel cache. */
    private function setAudience(string $key, string $audience, bool $inApp, bool $email, bool $push): void
    {
        NotificationAudienceSetting::updateOrCreate(
            ['notification_key' => $key, 'audience' => $audience],
            ['in_app_enabled' => $inApp, 'email_enabled' => $email, 'push_enabled' => $push],
        );
        NotificationAudienceSetting::forgetAllCache();
    }

    private function broadcast(): AdminBroadcastNotification
    {
        return new AdminBroadcastNotification('Subject', 'Body');
    }

    public function test_enabled_type_delivers_on_the_enabled_channels(): void
    {
        $this->setAudience('admin_broadcast', 'user', inApp: true, email: true, push: false);

        $channels = $this->broadcast()->via($this->user);

        $this->assertContains('database', $channels, 'in-app was enabled → must resolve the database channel');
        $this->assertContains('mail', $channels, 'email was enabled → must resolve the mail channel');
    }

    public function test_enabled_type_lands_in_the_inbox_on_dispatch(): void
    {
        $this->setAudience('admin_broadcast', 'user', inApp: true, email: false, push: false);

        $this->user->notify($this->broadcast());

        $this->assertSame(1, $this->user->notifications()->count(),
            'An enabled type must actually land in the recipient inbox.');
    }

    public function test_disabled_type_is_blocked_entirely(): void
    {
        $this->setAudience('admin_broadcast', 'user', inApp: false, email: false, push: false);

        $this->assertSame([], $this->broadcast()->via($this->user),
            'A type the super-admin switched off for the audience resolves no channels.');

        $this->user->notify($this->broadcast());

        $this->assertSame(0, $this->user->notifications()->count(),
            'A disabled type must never reach the inbox.');
    }

    public function test_user_opt_out_can_only_narrow_not_widen(): void
    {
        $this->setAudience('admin_broadcast', 'user', inApp: true, email: true, push: false);

        // The user mutes email for this one type (layer 3).
        NotificationPreference::updateOrCreate(
            ['user_id' => $this->user->id, 'notification_key' => 'admin_broadcast'],
            ['in_app_enabled' => true, 'email_enabled' => false, 'push_enabled' => false],
        );
        NotificationPreference::forgetCache($this->user->id);

        $channels = $this->broadcast()->via($this->user->fresh());

        $this->assertContains('database', $channels, 'in-app stays — the user left it on');
        $this->assertNotContains('mail', $channels, 'the user muted email → mail must drop');
    }

    public function test_granted_channels_reflects_the_matrix(): void
    {
        $this->setAudience('admin_broadcast', 'user', inApp: false, email: false, push: false); // off
        $this->setAudience('payment_received', 'user', inApp: true, email: false, push: false);  // on

        $granted = NotificationAudienceSetting::grantedChannelsFor('user');

        $this->assertArrayNotHasKey('admin_broadcast', $granted,
            'A fully-disabled type must not appear in the granted list (no dead toggle).');
        $this->assertArrayHasKey('payment_received', $granted,
            'A type with at least one channel on must be listed.');
    }

    public function test_saving_preferences_ignores_types_the_super_admin_disabled(): void
    {
        // Super-admin disables new_review_posted for the admin audience.
        $this->setAudience('new_review_posted', 'admin', inApp: false, email: false, push: false);

        // The admin saves preferences. new_review_posted isn't rendered in
        // the form, so the payload omits it; admin_broadcast (still enabled)
        // is narrowed to in-app only.
        $response = $this->actingAs($this->admin)->put(
            route('admin.notifications.my-preferences.update'),
            ['prefs' => ['admin_broadcast' => ['system' => '1']]],
        );

        $response->assertRedirect();

        // Regression: the disabled/omitted type must NOT get a deny-override
        // row written for it (which would keep it hidden after a re-enable).
        $this->assertDatabaseMissing('notification_preferences', [
            'user_id'          => $this->admin->id,
            'notification_key' => 'new_review_posted',
        ]);

        // The enabled type the admin actually narrowed IS stored.
        $this->assertDatabaseHas('notification_preferences', [
            'user_id'          => $this->admin->id,
            'notification_key' => 'admin_broadcast',
            'in_app_enabled'   => 1,
            'email_enabled'    => 0,
            'push_enabled'     => 0,
        ]);
    }
}
