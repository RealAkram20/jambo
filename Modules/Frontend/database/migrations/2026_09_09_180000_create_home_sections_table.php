<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Frontend\app\Models\HomeSection;

/**
 * The arrangement of the homepage sections.
 *
 * Before this, the order of the rails on the app's home screen was a literal
 * array in `HomeController::show()`. Moving a shelf meant a deploy. One row
 * per section, `position` rewritten by drag-and-drop exactly like
 * `featured_items` and categories, `enabled` to switch a shelf off without
 * deleting anything.
 *
 * The table is seeded with today's order, so nothing an admin has not touched
 * moves. `enabled` defaults to true for the same reason.
 *
 * 🔴 **`key` is a foreign key into a published contract. Never rename one.**
 *
 * The app keys one behaviour on these literal strings, beyond ordering, in
 * `mobile/src/api/catalogue.ts`:
 *
 *   - `collectionKeyFor(key)` decides whether a rail gets a "View all" and
 *     where it points. The rail and collection key spaces do NOT match —
 *     `/home` is underscored, `/collections` is hyphenated, three rails have
 *     no archive at all, and `exclusives` maps to `only-on-streamit` by an
 *     exception table with no rule behind it.
 *
 * The Top 10 numerals used to be keyed here too — the app held a hardcoded
 * set of `top_movies` and `top_series`. They moved to a `style` field on the
 * rail on 2026-09-10, so presentation is now the server's answer and renaming
 * one of those two keys no longer un-ranks a shelf.
 *
 * So: **adding a section is free. Renaming one is a breaking change** that
 * must ship with the app's mapping, and getting it wrong fails silently — a
 * dead "View all" button, not an error anybody sees. Add a new key and retire
 * the old row instead.
 *
 * `category:<slug>` rails are NOT stored one per category. They are already
 * ordered by the existing categories screen, and a second control for the same
 * thing is how two orders start disagreeing. They arrive here as the single
 * grouped key `categories`, which moves the whole block.
 *
 * No foreign key: `key` names a rail built in code, not a row in another
 * table. A key with no matching rail is inert (nothing renders it); a rail
 * with no matching row still renders, last — see `HomeSection::arrange()`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();

            // The rail key the API emits. Unique because a section is one
            // shelf: two rows for one key would be two contradictory answers
            // to "where does this go".
            $table->string('key')->unique();

            // Admin override for the heading. Null means "use the translated
            // default", which is what every row ships as.
            $table->string('label')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('position');
        });

        // Seed today's arrangement, so the change is invisible until an admin
        // drags something. Reads the model's canonical list rather than a
        // second copy of it: two lists of section keys is exactly the drift
        // this table exists to remove.
        HomeSection::seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
