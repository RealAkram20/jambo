<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Watchlist items — a user's saved "watch later" list. Polymorphic so a
 * single table covers movies, series, episodes, live events, etc.
 *
 * `added_at` is a domain-meaningful timestamp separate from the Eloquent
 * `created_at`; it's set explicitly by the model on insert and is the only
 * ordering signal the watchlist has. Everything that lists or plays the
 * watchlist sorts by it *ascending* — oldest saved first — so the list is a
 * queue the viewer works forward through. See WatchlistItem::scopeInPlayOrder.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('watchlist_items', function (Blueprint $t) {
            $t->id();

            $t->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Polymorphic target (movie, series, episode, ...).
            $t->morphs('watchable');

            // Domain timestamp — when the user added this entry.
            $t->timestamp('added_at');

            $t->timestamps();

            // One row per (user, item) — prevents duplicates.
            $t->unique(['user_id', 'watchable_type', 'watchable_id']);

            // "My watchlist, in play order" — serves the ASC sort.
            $t->index(['user_id', 'added_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlist_items');
    }
};
