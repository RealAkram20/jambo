<?php

/*
 * Titles on the test account's watchlist. Local database only.
 *
 * WHY THIS STEP EXISTS. The Watchlist screen was built from a mockup showing
 * six saved titles, and an empty list renders the empty state — which is a
 * real design, and not the one that needed checking. Nothing else in
 * `dev-catalogue` puts a row in `watchlist_items`: step 7 fills history, step
 * 8 fills notifications, and the watchlist stayed empty on every fresh
 * database. So the screen could be built and could not be rendered, and
 * rendering it is the bar this project sets.
 *
 * A MIX ON PURPOSE. Movies and shows both, because the screen's three filter
 * tabs are only exercised by a list that has some of each, and its meta line
 * differs by type — a movie has a runtime and a show does not. A list of six
 * movies would have shown a screen that looked right and hid half its states.
 *
 * IT WRITES THROUGH `WatchlistItem::addFor`, not through the table. That is
 * the same call `WatchlistController::store` and the website's own toggle use,
 * so this fixture cannot produce a row shaped differently from a real one —
 * including `added_at`, which the raw insert would have left null and the
 * play-order scope sorts on.
 *
 * Re-running is safe: `addFor` is idempotent. `JAMBO_WATCHLIST_RESET=1`
 * empties the list instead, which is how the empty state gets checked.
 */

use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Streaming\app\Models\WatchlistItem;

$user = App\Models\User::where('email', 'testuser@jambo.test')->first();

if ($user === null) {
    echo "no test user; run 6-test-user.php first\n";

    return;
}

if (getenv('JAMBO_WATCHLIST_RESET') === '1') {
    $removed = WatchlistItem::where('user_id', $user->id)->delete();
    echo "cleared {$removed} watchlist rows for {$user->email}\n";

    return;
}

/*
 * Four films and two series, which is the mockup's own split and enough to
 * put two full rows of cards on a phone. Published only: an unpublished title
 * is dropped by the endpoint on the way out, so seeding one would produce a
 * list that silently came back shorter than it was written.
 */
$movies = Movie::query()
    ->whereNotNull('published_at')
    ->where('published_at', '<=', now())
    ->orderBy('id')
    ->take(4)
    ->get();

$shows = Show::query()
    ->whereNotNull('published_at')
    ->where('published_at', '<=', now())
    ->orderBy('id')
    ->take(2)
    ->get();

if ($movies->isEmpty() && $shows->isEmpty()) {
    echo "no published titles; run the import steps first\n";

    return;
}

foreach ($movies->concat($shows) as $title) {
    WatchlistItem::addFor($user->id, $title);
    echo "saved {$title->getMorphClass()} {$title->id} {$title->title}\n";
}

$total = WatchlistItem::where('user_id', $user->id)->count();
echo "{$user->email} watchlist now holds {$total}\n";
