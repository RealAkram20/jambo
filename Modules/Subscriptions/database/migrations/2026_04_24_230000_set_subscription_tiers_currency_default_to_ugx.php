<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flip the `subscription_tiers.currency` column default from KES → UGX.
 *
 * Mirrors the equivalent fix on payment_orders. The SubscriptionTier
 * admin UI already defaults new tiers to UGX in-memory via
 * `new SubscriptionTier(['currency' => 'UGX'])`, but the DB-level
 * column default still said KES — so any code path that creates a
 * tier row without explicitly setting currency would land as KES, a
 * silent mismatch with config/payments.php and the Uganda merchant
 * account on PesaPal.
 *
 * Existing rows are untouched — they carry whatever currency they
 * were created with (the UGX seeder covers the canonical tiers).
 */
return new class extends Migration {
    public function up(): void
    {
        // ->change() requires doctrine/dbal on SQLite, which we don't
        // ship. The in-memory test DB has no rows to protect, so a
        // no-op on sqlite is safe.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('subscription_tiers', function (Blueprint $t) {
            $t->string('currency', 8)->default('UGX')->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('subscription_tiers', function (Blueprint $t) {
            $t->string('currency', 8)->default('KES')->change();
        });
    }
};
