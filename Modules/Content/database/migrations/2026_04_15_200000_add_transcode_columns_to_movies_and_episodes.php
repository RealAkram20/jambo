<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['movies', 'episodes'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                // Original uploaded file path (private disk, never exposed).
                $t->string('source_path', 500)->nullable()->after('video_url');
                // Path to master.m3u8 on the private hls disk, once ready.
                $t->string('hls_master_path', 500)->nullable()->after('source_path');
                // State machine: null (URL-only), queued, transcoding, ready, failed.
                $t->string('transcode_status', 20)->nullable()->after('hls_master_path');
                // Last error message from a failed transcode — surfaced in admin.
                $t->text('transcode_error')->nullable()->after('transcode_status');
            });
        }
    }

    public function down(): void
    {
        foreach (['movies', 'episodes'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['source_path', 'hls_master_path', 'transcode_status', 'transcode_error']);
            });
        }
    }
};
