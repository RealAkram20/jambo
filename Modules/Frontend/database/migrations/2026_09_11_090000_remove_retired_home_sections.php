<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Frontend\app\Models\HomeSection;

/**
 * Delete the seven sections Rio retired on 2026-09-11.
 *
 * Three of them — Top Picks, Popular Movies and Fresh Picks — had been retired
 * from the website months earlier and replaced by category shelves; the
 * comments in `ott-page.blade.php` said so. 1.8.38 rebuilt the home page from
 * this table and brought them back, because the table listed every rail the
 * API could build rather than every shelf the product actually has. He hid all
 * seven from the admin screen and then said to remove them:
 *
 *   > "now we need to remove these completely, we don't need them we don't
 *   > have them anymore"
 *
 * They are gone from `HomeSection::DEFAULTS` and `WEB_VIEWS` in the same
 * change, so this only clears rows that no longer have any code behind them.
 * Leaving them would show an admin seven rows that arrange nothing.
 *
 * **Not reversible in the useful sense, and deliberately not pretending to
 * be.** `down()` puts the rows back at the end, because the arrangement they
 * once had is not recoverable from anything this migration knows and inventing
 * one would be worse than appending. The keys are what matter: with them back
 * in DEFAULTS, `seedDefaults()` would recreate these rows on the next visit to
 * the admin screen anyway.
 */
return new class extends Migration
{
    /**
     * The keys removed. Written out rather than derived from a diff against
     * DEFAULTS: a migration must do the same thing in a year's time, when
     * DEFAULTS has moved on and the difference would be a different set.
     */
    private const RETIRED = [
        'top_picks',
        'latest_movies',
        'latest_series',
        'fresh_picks',
        'popular_movies',
        'international_series',
        'upcoming',
    ];

    public function up(): void
    {
        DB::table('home_sections')->whereIn('key', self::RETIRED)->delete();
    }

    public function down(): void
    {
        $next = (int) DB::table('home_sections')->max('position') + 1;

        foreach (self::RETIRED as $key) {
            // A key that has since been reused must not be duplicated.
            if (DB::table('home_sections')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('home_sections')->insert([
                'key' => $key,
                'position' => $next++,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
