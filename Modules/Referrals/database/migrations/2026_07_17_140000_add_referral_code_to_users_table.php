<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 50)->nullable()->unique()->after('username');
        });

        // Existing users default to their username as their code, same as
        // new signups do at registration time.
        DB::table('users')
            ->whereNull('referral_code')
            ->whereNotNull('username')
            ->update(['referral_code' => DB::raw('username')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }
};
