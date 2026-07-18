<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The System / owner pages (Settings, SEO & Analytics, System Info, Pages)
 * are delegatable and hidden-by-default: a regular admin can't reach them
 * until a super-admin grants the matching *_access permission. Access
 * Control itself is super-admin ONLY. Super-admins bypass everything via
 * the Gate::before in AuthServiceProvider.
 *
 * The *_access permissions exist because the migration creates them (which
 * also stops spatie's Gate::before throwing on an unknown ability).
 */
class SystemPageAccessTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web'], ['title' => 'User']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web'], ['title' => 'Super Administrator']);
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

    private function makeSuperAdmin(): User
    {
        $u = User::factory()->create([
            'username' => 'own_' . uniqid(),
            'email'    => 'own_' . uniqid() . '@test.local',
        ]);
        $u->assignRole('super-admin');
        $u->assignRole('admin');
        return $u;
    }

    public function test_regular_admin_is_forbidden_from_every_system_page(): void
    {
        $admin = $this->makeAdmin();

        foreach ([
            'admin.settings.index',
            'admin.seo.index',
            'admin.updates.index',
            'admin.diagnostics.status',
            'admin.pages.index',
            'backend.permission-role', // Access Control: super-admin only
        ] as $routeName) {
            if (! Route::has($routeName)) {
                continue;
            }
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertForbidden();
        }
    }

    public function test_granting_one_page_opens_only_that_page(): void
    {
        $this->adminRole->givePermissionTo('settings_access');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = $this->makeAdmin();

        // Granted: the permission gate lets them into Settings (empty POST
        // passes the gate, then fails validation → 302, proving no 403).
        $this->actingAs($admin)
            ->post(route('admin.settings.general'), [])
            ->assertStatus(302);

        // Not granted: Pages is still forbidden — the grant is per-page.
        $this->actingAs($admin)
            ->get(route('admin.pages.index'))
            ->assertForbidden();
    }

    public function test_super_admin_bypasses_all_system_page_gates(): void
    {
        // No *_access permission granted anywhere; Gate::before must let the
        // owner through regardless.
        $super = $this->makeSuperAdmin();

        $this->actingAs($super)
            ->post(route('admin.settings.general'), [])
            ->assertStatus(302);

        // Access Control (super-admin only) must NOT 403 for a super-admin.
        $this->actingAs($super)
            ->get(route('backend.permission-role'))
            ->assertStatus(302); // redirected to password.confirm, not forbidden
    }
}
