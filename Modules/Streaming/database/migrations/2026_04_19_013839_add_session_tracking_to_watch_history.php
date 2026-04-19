<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stamp which browser session each heartbeat came from so the
 * concurrent-stream limit can count distinct active devices per user.
 *
 *   session_id    — matches sessions.id; nullable so pre-existing rows
 *                   (and edge cases where the session didn't exist yet)
 *                   don't fail the insert.
 *   last_beat_at  — separate from watched_at so we can distinguish a
 *                   currently-playing session from one a user left open
 *                   yesterday. Concurrency check uses last_beat_at >
 *                   now() - STREAM_IDLE_SECONDS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watch_history', function (Blueprint $t) {
            $t->string('session_id', 64)->nullable()->after('completed');
            $t->timestamp('last_beat_at')->nullable()->after('session_id');

            $t->index(['user_id', 'last_beat_at'], 'watch_history_user_beat_idx');
        });
    }

    public function down(): void
    {
        Schema::table('watch_history', function (Blueprint $t) {
            $t->dropIndex('watch_history_user_beat_idx');
            $t->dropColumn(['session_id', 'last_beat_at']);
        });
    }
};
