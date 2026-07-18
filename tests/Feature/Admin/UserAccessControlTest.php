<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Access Control must actually gate user management, not just decorate it.
 *
 * The user-CRUD routes carry `permission:{view,add,edit,delete}_users`, so
 * revoking a permission from the admin role on the Access Control screen has
 * to translate into a 403 on the matching verb — the bug was that these
 * routes were gated by `role:admin` only, so any admin could do everything
 * regardless of what Access Control said.
 *
 * Super-admins bypass every check via the Gate::before in
 * AuthServiceProvider, so stripping the admin role can never lock the owner
 * out of their own dashboard.
 */
class UserAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['view_users', 'add_users', 'edit_users', 'delete_users'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web'], ['title' => 'User']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web'], ['title' => 'Super Administrator']);

        // Admin starts fully granted, like a fresh seed.
        $this->adminRole->syncPermissions(['view_users', 'add_users', 'edit_users', 'delete_users']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeAdmin(): User
    {
        $u = User::factory()->create([
            'username' => 'adm_' . uniqid(),
            'email'    => 'adm_' . uniqid() . '@test.local',
        ]);
        $u->assignRole('admin');
        return $u;
    }

    private function makeRegularUser(): User
    {
        $u = User::factory()->create([
            'username' => 'usr_' . uniqid(),
            'email'    => 'usr_' . uniqid() . '@test.local',
        ]);
        $u->assignRole('user');
        return $u;
    }

    private function revokeFromAdmin(string $permission): void
    {
        $this->adminRole->revokePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_admin_without_view_permission_cannot_open_the_list(): void
    {
        $this->revokeFromAdmin('view_users');

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.user-list'))
            ->assertForbidden();
    }

    public function test_admin_without_add_permission_cannot_create(): void
    {
        $this->revokeFromAdmin('add_users');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('dashboard.user-list.create'))->assertForbidden();
        $this->actingAs($admin)->post(route('dashboard.user-list.store'), [])->assertForbidden();
    }

    public function test_admin_without_edit_permission_cannot_edit(): void
    {
        $this->revokeFromAdmin('edit_users');
        $admin  = $this->makeAdmin();
        $target = $this->makeRegularUser();

        $this->actingAs($admin)
            ->patch(route('dashboard.user-list.update', $target), [])
            ->assertForbidden();
    }

    public function test_admin_without_delete_permission_cannot_delete(): void
    {
        $this->revokeFromAdmin('delete_users');
        $admin  = $this->makeAdmin();
        $target = $this->makeRegularUser();

        $this->actingAs($admin)
            ->delete(route('dashboard.user-list.destroy', $target))
            ->assertForbidden();

        $this->assertNotNull(User::find($target->id), 'A blocked delete must not remove the user.');
    }

    public function test_admin_with_permission_is_allowed_through_to_the_controller(): void
    {
        $admin = $this->makeAdmin(); // still fully granted

        // Add: empty payload passes the permission gate, then fails validation
        // (302 back), proving the middleware let it through rather than 403.
        $this->actingAs($admin)
            ->post(route('dashboard.user-list.store'), [])
            ->assertStatus(302);

        // Delete: a granted admin removes a regular user for real.
        $target = $this->makeRegularUser();
        $this->actingAs($admin)
            ->delete(route('dashboard.user-list.destroy', $target))
            ->assertRedirect();
        $this->assertNull(User::find($target->id), 'A granted admin must be able to delete.');
    }

    public function test_super_admin_bypasses_even_with_the_admin_role_stripped(): void
    {
        // Worst case: the admin role has NO user permissions at all.
        $this->adminRole->syncPermissions([]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $super = User::factory()->create([
            'username' => 'own_' . uniqid(),
            'email'    => 'own_' . uniqid() . '@test.local',
        ]);
        $super->assignRole('super-admin');
        $super->assignRole('admin');

        $this->actingAs($super)->get(route('dashboard.user-list'))->assertOk();

        $target = $this->makeRegularUser();
        $this->actingAs($super)
            ->delete(route('dashboard.user-list.destroy', $target))
            ->assertRedirect();
        $this->assertNull(User::find($target->id),
            'Super-admin must retain full access regardless of the admin role grants.');
    }
}
