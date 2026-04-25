<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * Adds the `super-admin` role above `admin`.
 *
 * Super-admins are the platform owners. The admin UI treats them as
 * immutable: regular admins can't delete a super-admin, can't change
 * their roles, and can't promote anyone to super-admin from the UI.
 *
 * Promotion is intentionally console-only: run
 *   php artisan users:make-super-admin <email>
 * once on each environment for the account that should own the box.
 */
return new class extends Migration {
    public function up(): void
    {
        Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            ['title' => 'accesscontrol.super-admin', 'is_fixed' => true],
        );
    }

    public function down(): void
    {
        // Don't ever auto-delete the role on rollback — that would
        // strip the role from every assignment in the pivot table.
        // If the role really needs to come out, do it by hand:
        //   php artisan tinker --execute='\Spatie\Permission\Models\Role::where("name","super-admin")->delete();'
    }
};
