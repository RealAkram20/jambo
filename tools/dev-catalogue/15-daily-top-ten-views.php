<?php
// Give the two daily Top 10 shelves something to rank.
//
//   php artisan tinker --execute="require 'tools/dev-catalogue/15-daily-top-ten-views.php';"
//
// To put the table back, which deletes only the rows this wrote:
//
//   JAMBO_DAILY_VIEWS_RESET=1 php artisan tinker --execute="require 'tools/dev-catalogue/15-daily-top-ten-views.php';"
//
// WHY THIS EXISTS
//
// Rio, 2026-09-10: "we are not having the numbers, 2 in Movies Today, 3 in
// Movies Today ... is it a bug". It is not. Both shelves rank on DISTINCT
// VIEWERS IN THE LAST 24 HOURS and pad the rest of the ten from all-time
// popularity, and neither surface will print a rank a title did not earn — a
// padded slide reads "Popular on Jambo" instead. This database had one movie
// and one series watched inside the window, so exactly one slide on each shelf
// carried a number and the other twelve did not.
//
// That is correct behaviour, and it is invisible on a machine nobody watches
// anything on. Step 7 seeds the test account's own history, which is what
// Continue Watching and History need; this is a different question. A ranking
// counts DISTINCT USERS, so one account watching ten films ranks nothing.
//
// ⚠️ Development only, and the guard is not decoration. These are views nobody
// watched, in the table partner earnings are settled from. It refuses to run
// anywhere but `local`.

use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Frontend\app\Services\TopPicksRecommender;

$marker = 'seed-daily-views';

if (! app()->environment('local')) {
    echo "Refusing to run outside the local environment: these are views nobody watched.\n";

    return;
}

/**
 * Both shelves cache on a per-date key for a day. Writing history without
 * clearing them shows nothing until midnight, which reads exactly like a
 * feature that does not work.
 *
 * The keys come from the recommender's own public constants rather than from
 * a second copy of the format.
 */
$forgetDailyCaches = function () {
    $today = now()->toDateString();

    cache()->forget(
        TopPicksRecommender::CACHE_KEY_DAILY_MOVIES_PREFIX
        . $today
        . TopPicksRecommender::CACHE_KEY_DAILY_MOVIES_SUFFIX
    );

    cache()->forget(
        TopPicksRecommender::CACHE_KEY_DAILY_SERIES_PREFIX
        . $today
        . TopPicksRecommender::CACHE_KEY_DAILY_SERIES_SUFFIX
    );
};

if (env('JAMBO_DAILY_VIEWS_RESET')) {
    $removed = DB::table('watch_history')->where('session_id', $marker)->delete();
    $forgetDailyCaches();
    echo "removed {$removed} seeded rows; the shelves fall back to all-time popularity\n";

    return;
}

// Distinct users is the whole point, so a handful of accounts is the minimum
// this can work with. Real accounts rather than invented ones, because the
// ranking joins on user_id and a row pointing at nobody is a foreign key
// waiting to fail.
$users = App\Models\User::query()->orderBy('id')->take(12)->pluck('id')->all();

if (count($users) < 2) {
    echo "need at least two users to make distinct viewers — run 6-test-user.php first\n";

    return;
}

$movies = Movie::published()->orderByDesc('views_count')->take(10)->get();
$shows = Show::published()->with('seasons.episodes')->take(10)->get();

$rows = [];
$now = now();

$row = function (int $userId, string $type, int $id) use ($now, $marker) {
    return [
        'user_id' => $userId,
        'watchable_type' => $type,
        'watchable_id' => $id,
        'position_seconds' => 120,
        'duration_seconds' => 600,
        'completed' => false,
        // An hour inside the 24-hour window, so a slow run does not fall out
        // of it while it is writing.
        'watched_at' => $now->copy()->subHour(),
        'session_id' => $marker,
        'created_at' => $now,
        'updated_at' => $now,
    ];
};

// A descending number of distinct viewers per title, so the order is decided
// by the ranking itself rather than by its tie-breakers. A flat count would
// have produced ten slides ordered by views_count and proved nothing.
foreach ($movies->values() as $index => $movie) {
    $viewers = max(1, count($users) - $index);

    for ($i = 0; $i < $viewers; $i++) {
        $rows[] = $row($users[$i % count($users)], $movie->getMorphClass(), $movie->id);
    }
}

// A series ranks on views of its EPISODES, aggregated up to the show, which is
// why this writes episode rows and not show rows.
foreach ($shows->values() as $index => $show) {
    $episode = $show->seasons->flatMap->episodes->first();

    if ($episode === null) {
        continue;
    }

    $viewers = max(1, count($users) - $index);

    for ($i = 0; $i < $viewers; $i++) {
        $rows[] = $row($users[$i % count($users)], (new Episode)->getMorphClass(), $episode->id);
    }
}

DB::table('watch_history')->where('session_id', $marker)->delete();

foreach (array_chunk($rows, 200) as $chunk) {
    DB::table('watch_history')->insert($chunk);
}

$forgetDailyCaches();

echo 'wrote ' . count($rows) . ' rows across ' . $movies->count() . ' movies and '
    . $shows->count() . " series\n";
echo "undo with JAMBO_DAILY_VIEWS_RESET=1\n";
