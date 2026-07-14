<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshots whether the title was free (no tier_required) at the
 * moment it qualified.
 *
 * The `monetization.free_content_earns` switch decides whether free
 * titles mint payable minutes at all, so when it's OFF no free-title
 * rows exist here to begin with. This column exists for the months
 * when it's ON: a partner asking "how much of my payout came from my
 * free catalogue?" gets an answer straight from the earning facts,
 * and a title that flips free→premium mid-month doesn't rewrite the
 * history of what already qualified under the old shelf.
 *
 * Nullable, no default: rows written before this migration genuinely
 * don't know, and "unknown" must not masquerade as "was premium".
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('qualified_views', function (Blueprint $table) {
            $table->boolean('content_was_free')->nullable()->after('runtime_minutes_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('qualified_views', function (Blueprint $table) {
            $table->dropColumn('content_was_free');
        });
    }
};
