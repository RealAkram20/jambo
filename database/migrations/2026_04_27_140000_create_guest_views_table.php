<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks unique-device views from guests on free content.
 *
 * Logged-in users still go through watch_history (counts unique users).
 * This table mirrors that behaviour for guests using a long-lived
 * visitor cookie as the dedupe key, so the views_count column on
 * movies / shows reflects every individual who actually watched —
 * paying or not — without inflating on refreshes.
 *
 * The unique index is the load-bearing piece: insert-or-fail on
 * (visitor_id, watchable_type, watchable_id) gives us the "first time
 * seen?" signal atomically without a SELECT-then-INSERT race.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('guest_views', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 64);
            $table->string('watchable_type');
            $table->unsignedBigInteger('watchable_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['visitor_id', 'watchable_type', 'watchable_id'], 'gv_unique');
            $table->index(['watchable_type', 'watchable_id'], 'gv_content_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_views');
    }
};
