<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How many premium streams the tier allows at the same time.
 *
 * NULL = no limit (free tier default; free content is always unlimited
 * regardless of this value). Any positive integer means "at most N
 * concurrent active streams on premium-gated content per subscriber".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_tiers', function (Blueprint $t) {
            $t->unsignedSmallInteger('max_concurrent_streams')->nullable()->after('access_level');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_tiers', function (Blueprint $t) {
            $t->dropColumn('max_concurrent_streams');
        });
    }
};
