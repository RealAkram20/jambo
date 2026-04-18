<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adds a single editorial override knob to movies and shows for
     * the Top 10 ranking algorithm. 0 = no boost (default); higher
     * integers move a title up the ranking without disabling the
     * organic signals. One number keeps the admin UI simple — no
     * separate "featured", "pinned", "sort_order" fields to juggle.
     *
     * Scoring in SectionDataComposer multiplies this by 100 so a
     * `editor_boost = 1` is worth ~100 views of organic popularity,
     * and `2` effectively pins a title near the top.
     */
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $t) {
            $t->tinyInteger('editor_boost')->unsigned()->default(0)->after('tier_required');
        });

        Schema::table('shows', function (Blueprint $t) {
            $t->tinyInteger('editor_boost')->unsigned()->default(0)->after('tier_required');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $t) {
            $t->dropColumn('editor_boost');
        });

        Schema::table('shows', function (Blueprint $t) {
            $t->dropColumn('editor_boost');
        });
    }
};
