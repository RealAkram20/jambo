<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Two-factor auth columns on the User record.
     *
     * - `two_factor_secret`          encrypted Base32 TOTP secret
     * - `two_factor_recovery_codes`  encrypted JSON array of single-use
     *                                codes (~8 codes of 10 alnum chars)
     * - `two_factor_confirmed_at`    set once the user successfully
     *                                verified the first OTP; until
     *                                then the feature is in "pending"
     *                                state and the middleware doesn't
     *                                challenge on login
     *
     * Secret + recovery codes are written via Laravel's encrypter so a
     * DB leak doesn't hand the attacker the seed.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->text('two_factor_secret')->nullable()->after('password');
            $t->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $t->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
