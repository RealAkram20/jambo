<?php

namespace Database\Seeders\Auth;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Class PermissionRoleTableSeeder.
 */
class PermissionRoleTableSeeder extends Seeder
{
    /**
     * Run the database seed.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        $admin = Role::firstOrCreate(['name' => 'admin', 'title' => 'accesscontrol.admin', 'is_fixed' => true]);
        $user = Role::firstOrCreate(['name' => 'user', 'title' => 'accesscontrol.user', 'is_fixed' => true]);

        $modules = config('constant.MODULES');

        foreach ($modules as $key => $module) {
            $permissions = ['view', 'add', 'edit', 'delete'];
            $module_name = strtolower(str_replace(' ', '_', $module['module_name']));
            foreach ($permissions as $key => $value) {
                $permission_name = $value.'_'.$module_name;
                Permission::firstOrCreate(['name' => $permission_name, 'is_fixed' => true]);
            }
            if (isset($module['more_permission']) && is_array($module['more_permission'])) {
                foreach ($module['more_permission'] as $key => $value) {
                    $permission_name = $module_name.'_'.$value;
                    Permission::firstOrCreate(['name' => $permission_name, 'is_fixed' => true]);
                }
            }
        }
        // Assign Permissions to Roles.
        //
        // Admin gets everything — the admin area is gated by the
        // role:admin middleware first and then by fine-grained
        // @can() checks inside views (e.g. @can('view_users') on
        // the sidebar Users link).
        //
        // The `user` role is the default signup role for regular
        // viewers. Admin-area permissions (add_movies, delete_users,
        // etc.) aren't meaningful for them — the admin area is
        // fully gated by role:admin. Giving every signed-up user
        // all 28 admin permissions was a seeding bug that made the
        // RBAC checks meaningless for any hand-rolled "Moderator"
        // role an admin might create later.
        //
        // Re-syncing on every seed run so old installs with the
        // previous too-permissive `user` grants get cleaned up.
        $admin->syncPermissions(Permission::get());
        $user->syncPermissions([]);

        Schema::enableForeignKeyConstraints();
    }
}
