<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * Adds the `finance` role for admins who manage payments + pricing.
 *
 * Finance-tagged admins see:
 *   - The Pricing page
 *   - All Payments tabs (orders, payment settings)
 *   - Total Subscribers, Total Earnings, Transaction History on the
 *     admin dashboard (which non-finance admins do NOT see — that's
 *     the whole point of this tier).
 *
 * Promotion is console-only, mirrors users:make-super-admin:
 *   php artisan users:grant-finance <email>
 *
 * Super-admins implicitly satisfy any finance-only check via
 * hasAnyRole(['finance', 'super-admin']) so platform owners never
 * lose access to their own money UI.
 */
return new class extends Migration {
    public function up(): void
    {
        Role::firstOrCreate(
            ['name' => 'finance', 'guard_name' => 'web'],
            ['title' => 'accesscontrol.finance', 'is_fixed' => true],
        );
    }

    public function down(): void
    {
        // Don't auto-delete on rollback; would strip the role from
        // every assignment row in the pivot. To remove manually:
        //   php artisan tinker --execute='\Spatie\Permission\Models\Role::where("name","finance")->delete();'
    }
};
