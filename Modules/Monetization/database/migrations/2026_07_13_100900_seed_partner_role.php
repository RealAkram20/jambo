<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * Adds the `partner` role for monetization-program members (VJs,
 * production companies, creators) so the /partner/* console can gate
 * on role:partner.
 *
 * Assignment is NOT console-only like finance/super-admin: the partner
 * admin CRUD (super-admin gated) attaches/detaches this role when a
 * user account is linked to / unlinked from a monetization partner.
 * Holding the role grants access to the partner's OWN data only —
 * every partner controller resolves the partner row from auth()->id().
 */
return new class extends Migration {
    public function up(): void
    {
        Role::firstOrCreate(
            ['name' => 'partner', 'guard_name' => 'web'],
            ['title' => 'accesscontrol.partner', 'is_fixed' => true],
        );
    }

    public function down(): void
    {
        // Don't auto-delete on rollback; would strip the role from
        // every assignment row in the pivot. To remove manually:
        //   php artisan tinker --execute='\Spatie\Permission\Models\Role::where("name","partner")->delete();'
    }
};
