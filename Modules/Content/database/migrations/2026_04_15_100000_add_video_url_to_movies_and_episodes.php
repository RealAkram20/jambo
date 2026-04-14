<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a generic `video_url` column to movies + episodes. Accepts any
 * playable source we can embed in the browser:
 *
 *   - youtube.com / youtu.be URLs → rendered as an iframe embed
 *   - direct .mp4 / .webm / .m3u8 URLs → rendered as HTML5 <video>
 *
 * We intentionally keep the older `dropbox_path` column in place for
 * backward compat — nothing reads it any more but dropping it would be
 * a destructive change against existing seed data.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $t) {
            $t->string('video_url', 500)->nullable()->after('dropbox_path');
        });

        Schema::table('episodes', function (Blueprint $t) {
            $t->string('video_url', 500)->nullable()->after('dropbox_path');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $t) {
            $t->dropColumn('video_url');
        });

        Schema::table('episodes', function (Blueprint $t) {
            $t->dropColumn('video_url');
        });
    }
};
