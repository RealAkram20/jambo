<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `announced_at` — the moment we told users a title exists.
 *
 * Previously the "New movie added" broadcast fired inline from the admin's
 * save, gated only on `status = published`. A title with a future
 * `published_at` therefore announced immediately while the public route
 * (which requires `published_at <= now()`) still 404'd. This column lets
 * the announcement move to the moment the title is genuinely watchable,
 * and makes it idempotent — draft → published → draft → published cannot
 * re-spam the audience.
 *
 * The backfill is load-bearing: it marks everything that ALREADY announced
 * under the old rules. Without it the first `content:announce-due` tick
 * would broadcast the entire back catalogue to every verified user.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['movies', 'shows', 'seasons', 'episodes'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->timestamp('announced_at')->nullable()->after('created_at');
                $t->index('announced_at');
            });
        }

        // Movies / shows: the old code announced on `status = published`,
        // regardless of date. Mirror that exactly.
        foreach (['movies', 'shows'] as $table) {
            DB::table($table)
                ->where('status', 'published')
                ->update(['announced_at' => DB::raw('COALESCE(published_at, created_at)')]);
        }

        // Seasons: the old SeasonController::store announced unconditionally,
        // so every existing season has already been broadcast.
        DB::table('seasons')->update(['announced_at' => DB::raw('created_at')]);

        // Episodes: announced whenever they had a publish date.
        DB::table('episodes')
            ->whereNotNull('published_at')
            ->update(['announced_at' => DB::raw('published_at')]);
    }

    public function down(): void
    {
        foreach (['movies', 'shows', 'seasons', 'episodes'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropIndex($table . '_announced_at_index');
                $t->dropColumn('announced_at');
            });
        }
    }
};
