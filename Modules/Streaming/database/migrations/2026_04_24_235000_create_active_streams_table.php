<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated live-session tracking table for the device-limit feature.
 *
 * Why a new table instead of reusing watch_history:
 *   • watch_history is unique on `(user, watchable)` — one row per
 *     title-the-user-has-ever-watched, with session_id updated on
 *     each heartbeat. That's correct for resume position and view
 *     counts, but it breaks concurrency tracking: two devices on the
 *     same title ping-pong one row's session_id, so the booted
 *     device's next heartbeat "steals" the row back and the kick
 *     signal is lost.
 *   • active_streams is unique on `(user, session, watchable)` —
 *     one row per device-watching-this-title. Boot flags the
 *     specific (user, session) rows terminated; the booted device's
 *     heartbeat finds its own row with terminated_at set and stops.
 *
 * last_beat_at + terminated_at drive the cap calculation. A row is
 * "active" when last_beat_at is within the heartbeat window and
 * terminated_at is null.
 *
 * Rows are never deleted on normal flow — they're kept for a short
 * tail so we can debug concurrency decisions. A scheduled GC (not
 * included in this migration) can prune rows older than a day.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_streams', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('session_id', 64);
            $t->string('watchable_type');
            $t->unsignedBigInteger('watchable_id');
            $t->timestamp('last_beat_at');
            $t->timestamp('terminated_at')->nullable();
            $t->timestamps();

            // One row per (user, session, title) — this is the property
            // watch_history lacked. A heartbeat upserts this row; boot
            // flags terminated_at on matching (user, session) rows.
            $t->unique(['user_id', 'session_id', 'watchable_type', 'watchable_id'], 'active_streams_unique');

            // Concurrency cap query: "how many distinct sessions for
            // this user have beaten recently and aren't terminated?".
            $t->index(['user_id', 'last_beat_at'], 'active_streams_user_beat_idx');
            $t->index(['user_id', 'session_id'], 'active_streams_user_session_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_streams');
    }
};
