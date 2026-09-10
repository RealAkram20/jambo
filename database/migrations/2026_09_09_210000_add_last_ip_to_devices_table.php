<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where an app install last spoke from.
 *
 * The devices screen shows each device's location, on Rio's call of
 * 2026-09-09. Browser sessions have carried an IP since Laravel created the
 * `sessions` table; **app installs carried nothing**, so there was no input to
 * resolve a location from at all.
 *
 * **This stores a viewer's IP address, which is personal data**, so two things
 * are deliberate. It is one column holding only the most recent value rather
 * than a history — a device that moves overwrites, and nothing accumulates a
 * trail. And it is nullable with no backfill: every device that existed before
 * this migration shows no location until it next makes a request, which is
 * honest rather than a guess about where it used to be.
 *
 * 45 characters because that is the widest an IPv6 address with an embedded
 * IPv4 suffix can be. `string(45)` is what Laravel's own `ipAddress()` column
 * resolves to, and it is written out here rather than using that helper so the
 * width is visible to whoever reads this next.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('last_ip', 45)->nullable()->after('app_version');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('last_ip');
        });
    }
};
