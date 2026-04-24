<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nullable phone column on users — collected on the profile edit form
 * so mobile-money flows (M-Pesa, MTN MoMo, Airtel Money) can prefill
 * the number on PesaPal's hosted checkout. Optional for the user; a
 * missing phone just means PesaPal will prompt for it on the gateway
 * page.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('phone', 32)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('phone');
        });
    }
};
