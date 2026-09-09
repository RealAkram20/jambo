<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a viewer wants their video delivered, kept with the account.
 *
 * Rio's ruling on 2026-09-09 is that the mobile app is a pure streaming
 * experience — "it should be watching not editing so all can edit and update
 * their streaming preferences" — and that these follow the account rather than
 * the handset. That last part is the reason this is a column and not
 * AsyncStorage: a household signs in on a phone, a tablet and, in Phase 4, a
 * television, and a data-saver choice made on the phone is meaningless if the
 * TV has never heard of it.
 *
 * **One nullable JSON column rather than three typed ones, and rather than a
 * table.** The set is small, it is read whole and written whole, it is never
 * queried across users, and it will grow — subtitles when the platform gets
 * them, a download quality when Phase 3 lands. Three columns today would be
 * six by Christmas and a migration for each. A table would be a join on every
 * playback authorisation for a row that is always exactly one.
 *
 * Null means "never chosen", and the API answers it with the defaults rather
 * than with nulls, so a client never has to know this column can be empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->json('streaming_preferences')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('streaming_preferences');
        });
    }
};
