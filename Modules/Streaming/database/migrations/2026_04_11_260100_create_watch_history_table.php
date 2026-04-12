<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Watch history — one row per (user, asset) tracking playback progress.
 * Upserted on every progress heartbeat from the player. Powers the
 * "continue watching" rail and the "watched" badge.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('watch_history', function (Blueprint $t) {
            $t->id();

            $t->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Polymorphic target (movie, episode, ...).
            $t->morphs('watchable');

            // Playback state.
            $t->unsignedInteger('position_seconds')->default(0);
            $t->unsignedInteger('duration_seconds')->nullable();
            $t->boolean('completed')->default(false);

            // Heartbeat — updated on every progress save.
            $t->timestamp('watched_at');

            $t->timestamps();

            // One row per (user, item) — upsert target.
            $t->unique(['user_id', 'watchable_type', 'watchable_id']);

            // "Continue watching" queries.
            $t->index(['user_id', 'watched_at']);

            // Completed / in-progress filtering.
            $t->index(['user_id', 'completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_history');
    }
};
