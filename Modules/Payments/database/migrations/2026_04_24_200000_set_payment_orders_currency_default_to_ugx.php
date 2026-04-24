<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flip the payment_orders.currency column default from KES → UGX.
 *
 * Jambo serves the Ugandan market; the original migration defaulted
 * to KES because the Streamit template seeder did. PesaPal bills in
 * the merchant's configured currency, so this needs to match.
 *
 * Only the column *default* changes — existing rows keep whatever
 * currency they were created with so we don't retroactively rewrite
 * payment history. Future PaymentOrder inserts that don't explicitly
 * set currency will land as UGX.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $t) {
            $t->string('currency', 8)->default('UGX')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $t) {
            $t->string('currency', 8)->default('KES')->change();
        });
    }
};
