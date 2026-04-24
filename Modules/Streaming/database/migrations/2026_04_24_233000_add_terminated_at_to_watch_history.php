<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mark a streaming row as forcibly terminated by the device-limit
 * picker. When a user at their concurrent-stream cap opens premium
 * content on a new device and boots one of the existing sessions,
 * that row's `terminated_at` is stamped.
 *
 *   • `activeStreamCount` excludes rows with `terminated_at` set so
 *     the cap frees up immediately (no 90s wait for the heartbeat
 *     window to elapse).
 *   • The booted device's next heartbeat reads `terminated_at` on
 *     its own row and the server responds `{terminated: true}`,
 *     at which point the player overlays a "signed out here" message.
 *
 * Kept separate from `completed` — completed means the user finished
 * watching; terminated means another device took over. Different
 * semantics, different downstream behavior.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watch_history', function (Blueprint $t) {
            $t->timestamp('terminated_at')->nullable()->after('last_beat_at');
        });
    }

    public function down(): void
    {
        Schema::table('watch_history', function (Blueprint $t) {
            $t->dropColumn('terminated_at');
        });
    }
};
