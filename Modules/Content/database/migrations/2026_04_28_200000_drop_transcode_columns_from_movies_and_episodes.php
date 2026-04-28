<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the on-server transcoding columns now that playback is direct
 * passthrough to the original Dropbox URL. The earlier 2026_04_15 +
 * 2026_04_26 migrations added these to support an HLS pipeline that
 * was retired in 1.4.0:
 *
 *   * source_path        — path on the (now-gone) `source` disk
 *   * hls_master_path    — path on the (now-gone) `hls` disk
 *   * transcode_status   — queued/downloading/transcoding/ready/failed
 *   * transcode_error    — last ffmpeg error message
 *   * publish_when_ready — auto-publish hook for the deferral path
 *
 * Each drop is guarded by `Schema::hasColumn` so the migration can run
 * cleanly on environments that already shed columns by hand or that
 * never had them in the first place. The down() recreates the same
 * columns the ADD migrations did, in the same shapes — rolling back
 * lands the schema in the pre-1.4.0 state.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['movies', 'episodes'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['source_path', 'hls_master_path', 'transcode_status', 'transcode_error', 'publish_when_ready'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['movies', 'episodes'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'source_path')) {
                    $t->string('source_path', 500)->nullable()->after('video_url');
                }
                if (!Schema::hasColumn($table, 'hls_master_path')) {
                    $t->string('hls_master_path', 500)->nullable()->after('source_path');
                }
                if (!Schema::hasColumn($table, 'transcode_status')) {
                    $t->string('transcode_status', 20)->nullable()->after('hls_master_path');
                }
                if (!Schema::hasColumn($table, 'transcode_error')) {
                    $t->text('transcode_error')->nullable()->after('transcode_status');
                }
                if (!Schema::hasColumn($table, 'publish_when_ready')) {
                    $t->boolean('publish_when_ready')->default(false)->after('transcode_error');
                }
            });
        }
    }
};
