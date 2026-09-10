<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Frontend\app\Models\HomeSection;

/**
 * Put the two daily Top 10 banners where the website puts them.
 *
 * `HomeSection::seedDefaults()` already gives a new section a row — the admin
 * screen calls it on load — but it appends, because an admin's arrangement
 * must survive a deploy. Appending is the right rule and the wrong result
 * here: it would drop both banners at the bottom of the home screen, next to
 * each other, on every install that already has the table. The website has one
 * between Upcoming and the personality rail and the other after it, and Rio
 * asked for the website's design rather than a version of it that needs two
 * drags before it looks right.
 *
 * So this is an insertion, not an append: every row at or after the slot moves
 * down one, exactly as a drag would have moved it.
 *
 * **It changes nothing an admin arranged.** The anchors are looked up by key
 * rather than by position, so a home screen that has already been rearranged
 * still gets each banner immediately after the section the website puts it
 * after. If an anchor is missing — switched off is fine, deleted is not, and
 * only a hand-edited table can delete one — the banner appends, which is
 * `seedDefaults()`'s own answer and never worse than not existing.
 *
 * Idempotent: a key that already has a row is left exactly where it is.
 */
return new class extends Migration
{
    /**
     * Each banner, and the section the website draws it after.
     *
     * `ott-page.blade.php`: `verticle-slider` follows `upcomming`, and
     * `tab-slider` follows `Your-Favourite-Personality`.
     */
    private const PLACEMENTS = [
        'top_movies_today' => 'upcoming',
        'top_series_today' => 'personalities',
    ];

    public function up(): void
    {
        // The create migration runs first on a fresh database and seeds from
        // DEFAULTS, which already carries both keys in these positions. This
        // guard is for the reverse case: a table that predates them.
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        foreach (self::PLACEMENTS as $key => $after) {
            if (HomeSection::query()->where('key', $key)->exists()) {
                continue;
            }

            $anchor = HomeSection::query()->where('key', $after)->first();

            if ($anchor === null) {
                HomeSection::query()->create([
                    'key' => $key,
                    'position' => ((int) HomeSection::query()->max('position')) + 1,
                ]);

                continue;
            }

            $slot = $anchor->position + 1;

            // Everything from the slot down moves one place. Ordered by
            // position descending is not needed — this is one statement, not a
            // loop — but the index on `position` makes it a range update.
            HomeSection::query()->where('position', '>=', $slot)->increment('position');

            HomeSection::query()->create(['key' => $key, 'position' => $slot]);
        }
    }

    /**
     * Removing the rows leaves a gap in `position`, and that is harmless:
     * `arrange()` sorts by position and never reads the numbers themselves, so
     * 0,1,3,4 is the same screen as 0,1,2,3. Closing the gap would mean
     * rewriting rows this migration never touched.
     */
    public function down(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        HomeSection::query()->whereIn('key', array_keys(self::PLACEMENTS))->delete();
    }
};
