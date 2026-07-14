<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index for the AI Smart Shuffle collaborative-filtering and trending
 * queries, both of which scan watch_history by (type, completed) and
 * either group by watchable_id or bound on watched_at.
 *
 * The table's existing indexes are all user-scoped — (user_id,
 * watched_at) and (user_id, completed) — plus the morphs index on
 * (watchable_type, watchable_id). None of them serve a catalog-wide
 * "who else completed this" or "what got finished in the last 14 days"
 * scan, which is exactly what the recommender now runs.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('watch_history', function (Blueprint $t) {
            $t->index(
                ['watchable_type', 'completed', 'watched_at'],
                'watch_history_reco_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('watch_history', function (Blueprint $t) {
            $t->dropIndex('watch_history_reco_idx');
        });
    }
};
