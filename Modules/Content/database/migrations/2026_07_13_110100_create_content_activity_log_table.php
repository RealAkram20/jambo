<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only trail of every create / update / delete an admin performs
 * on a movie, show, season or episode. Two jobs:
 *
 *   1. "Who did what" — the activity feed on the Performance dashboard.
 *   2. The authoritative source for per-admin upload counts + payouts.
 *      Content uses HARD deletes (no soft-delete), so a deleted movie
 *      leaves no row on its own table; this log survives the delete, so
 *      earnings already accrued can't be erased by deleting the content.
 *
 * Never updated in place — one row per action. Denormalised snapshots
 * (actor_name, content_title) keep it readable after the user or the
 * content row is gone. actor_id is SET NULL on user delete.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_activity_log', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('actor_name')->nullable();          // snapshot, survives user delete
            $t->string('action', 20);                      // created | updated | deleted
            $t->string('content_type', 20);                // movie | show | season | episode
            $t->unsignedBigInteger('content_id')->nullable(); // may point at a now-deleted row
            $t->string('content_title')->nullable();       // snapshot
            $t->json('meta')->nullable();                  // e.g. parent show/season context
            $t->timestamp('created_at')->nullable();

            $t->index(['actor_id', 'action', 'created_at']);
            $t->index(['content_type', 'content_id']);
            $t->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_activity_log');
    }
};
