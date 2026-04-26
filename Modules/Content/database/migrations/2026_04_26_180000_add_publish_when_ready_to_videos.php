<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `publish_when_ready` flag to movies and episodes. When admin
 * clicks "Publish" on an asset that hasn't finished transcoding yet,
 * we save it as draft + flip this flag — TranscodeVideoJob's success
 * path then auto-flips status to published and fires the *Added event
 * (which dispatches push notifications to the audience).
 *
 * Lets the admin walk away after one click instead of having to
 * remember to come back and publish once the encode finishes.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['movies', 'episodes'] as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'publish_when_ready')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->boolean('publish_when_ready')
                        ->default(false)
                        ->after('transcode_error');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['movies', 'episodes'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'publish_when_ready')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('publish_when_ready');
                });
            }
        }
    }
};
