<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Locks in the four guarantees the super-admin tier provides:
 *
 *   1. A regular admin cannot delete a super-admin via the user-list UI.
 *   2. A regular admin cannot strip the super-admin role from another user.
 *   3. A regular admin cannot promote anyone (themselves or others) to
 *      super-admin via the role-edit form — even if the form is hand-
 *      crafted to include 'super-admin' in the roles array.
 *   4. A regular admin cannot edit any field on a super-admin's row at all.
 *
 * Every super-admin path the UI offers funnels through
 * Admin\UserController::destroy / update / syncRoles. These tests
 * pin those entry points; downstream filesystem changes (the view
 * layer hides the buttons + locks the form) are belt-and-braces but
 * out of scope for the test suite.
 */
class SuperAdminGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $regularAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web'], ['title' => 'User']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web'], ['title' => 'Super Administrator']);

        $this->superAdmin = User::factory()->create([
            'username' => 'owner_' . uniqid(),
            'email'    => 'owner_' . uniqid() . '@test.local',
        ]);
        $this->superAdmin->assignRole('super-admin');
        $this->superAdmin->assignRole('admin');

        $this->regularAdmin = User::factory()->create([
            'username' => 'admin_' . uniqid(),
            'email'    => 'admin_' . uniqid() . '@test.local',
        ]);
        $this->regularAdmin->assignRole('admin');
    }

    public function test_admin_cannot_delete_super_admin(): void
    {
        $response = $this->actingAs($this->regularAdmin)
            ->delete(route('dashboard.user-list.destroy', $this->superAdmin));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotNull(User::find($this->superAdmin->id),
            'Super-admin row must still exist — destroy() must short-circuit.');
    }

    public function test_super_admin_cannot_delete_another_super_admin(): void
    {
        $other = User::factory()->create([
            'username' => 'other_' . uniqid(),
            'email'    => 'other_' . uniqid() . '@test.local',
        ]);
        $other->assignRole('super-admin');
        $other->assignRole('admin');

        // Symmetric guard: a super-admin going rogue still can't take
        // out a peer through the UI. Console-only, deliberately.
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('dashboard.user-list.destroy', $other));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotNull(User::find($other->id),
            'Peer super-admin must still exist after the delete attempt.');
    }

    public function test_admin_cannot_edit_super_admin_via_update_form(): void
    {
        $payload = [
            'first_name' => 'Hacked',
            'last_name'  => 'Name',
            'username'   => 'hijacked_' . uniqid(),
            'email'      => 'hijacked@example.com',
            'phone'      => '',
            'password'   => '',
            'password_confirmation' => '',
            'roles'      => ['user'],
        ];

        $response = $this->actingAs($this->regularAdmin)
            ->patch(route('dashboard.user-list.update', $this->superAdmin), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $fresh = User::find($this->superAdmin->id);
        $this->assertNotSame('Hacked', $fresh->first_name, 'Name must not be overwritten');
        $this->assertTrue($fresh->hasRole('super-admin'), 'Super-admin role must be preserved');
        $this->assertTrue($fresh->hasRole('admin'), 'Admin role must be preserved');
    }

    public function test_admin_cannot_promote_anyone_to_super_admin_via_form(): void
    {
        $target = User::factory()->create([
            'username' => 'target_' . uniqid(),
            'email'    => 'target_' . uniqid() . '@test.local',
        ]);
        $target->assignRole('user');

        $payload = [
            'first_name' => $target->first_name,
            'last_name'  => $target->last_name,
            'username'   => $target->username,
            'email'      => $target->email,
            'phone'      => '',
            'password'   => '',
            'password_confirmation' => '',
            'roles'      => ['user', 'admin', 'super-admin'],
        ];

        $response = $this->actingAs($this->regularAdmin)
            ->patch(route('dashboard.user-list.update', $target), $payload);

        $response->assertRedirect();

        $fresh = User::find($target->id);
        $this->assertFalse(
            $fresh->hasRole('super-admin'),
            'syncRoles must filter super-admin out of the incoming list'
        );
        $this->assertTrue($fresh->hasRole('admin'), 'admin still applied');
    }

    public function test_super_admin_can_be_assigned_via_console_command(): void
    {
        $target = User::factory()->create([
            'username' => 'target_' . uniqid(),
            'email'    => 'console_' . uniqid() . '@test.local',
        ]);

        $this->artisan('users:make-super-admin', ['email' => $target->email])
            ->assertExitCode(0);

        $this->assertTrue($target->fresh()->hasRole('super-admin'));
        $this->assertTrue($target->fresh()->hasRole('admin'),
            'Command should also grant admin so role:admin middleware still recognises the user');
    }

    public function test_console_command_rejects_unknown_email(): void
    {
        $this->artisan('users:make-super-admin', ['email' => 'nobody@example.invalid'])
            ->expectsOutputToContain('No user found')
            ->assertExitCode(1);
    }
}
