<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The hand-picked hero of the OTT homepage.
 *
 * Before this, the big banner and its poster rail on `/` were the three
 * most-viewed movies and the three most-viewed series, interleaved. Admins
 * could not put a new release in front of visitors without waiting for its
 * view count to climb.
 *
 * One row per featured title. `sort_order` is the position in the rail and
 * is rewritten by drag-and-drop, exactly like categories. The morph columns
 * hold a Movie or a Show, so one list can mix both, which is what the rail
 * already displays.
 *
 * Scope note: this drives the homepage hero ONLY. The banners on /movie,
 * /series and the VJ pages are a different component fed by their own
 * queries and are deliberately untouched.
 *
 * No foreign key: the morph target spans two tables. Deleted titles are
 * filtered out on read and swept by FeaturedItem::prune(), so a stale row
 * can never resurrect a deleted movie on the homepage.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('featured_items', function (Blueprint $table) {
            $table->id();
            $table->morphs('featurable'); // indexes (featurable_type, featurable_id)
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // A title appears in the hero at most once. The admin form
            // relies on this to reject a duplicate rather than silently
            // showing the same banner twice.
            $table->unique(['featurable_type', 'featurable_id'], 'featured_items_unique_target');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_items');
    }
};
