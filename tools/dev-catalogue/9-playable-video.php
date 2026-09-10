<?php

/*
 * Bytes the player can actually fetch. Local database only.
 *
 * WHY THIS STEP EXISTS. Every title in this catalogue carries a placeholder
 * `dropbox_path` like `/jambo/movies/hidden-storm-29765.mp4`. No such file
 * exists, no CDN zone is configured locally, and `CdnUrlResolver` returns a
 * URL no zone claims untouched — so `POST /api/v1/playback/sessions` answers
 * with that bare relative path and a player has nothing to open. Checked
 * against the running API, not assumed:
 *
 *   {"source":"file","url":"/jambo/movies/hidden-storm-29765.mp4",
 *    "quality":"default","available_qualities":["default"],
 *    "resume_position":1512,"heartbeat_seconds":15}
 *
 * So the player could be built and could never be rendered, and rendering it
 * is the bar this project sets. This step points a handful of local titles at
 * two real files served by `video-server.mjs`.
 *
 * `video_url` is set and `dropbox_path` is LEFT ALONE. StreamSourceResolver
 * prefers `video_url` and only falls back to `dropbox_path`, so --reset
 * restores the original seeded behaviour exactly by clearing one column.
 *
 * THE TWO FIXTURES ARE DIFFERENT FILMS, on purpose and only here. Jambo has
 * exactly two renditions of one title; these are a 480p Sintel trailer and a
 * 240p Big Buck Bunny clip, because a quality switch that changes the picture
 * is unambiguous evidence the switch reached the network. A fixture that
 * looked identical at both qualities would prove nothing. Nobody should read
 * this file as a statement about how renditions work in production.
 *
 * Usage:
 *   php artisan tinker --execute="require 'tools/dev-catalogue/9-playable-video.php';"
 *   JAMBO_VIDEO_BASE=http://10.0.2.2:8097 php artisan tinker --execute="..."
 *   JAMBO_VIDEO_RESET=1 php artisan tinker --execute="..."     # put it back
 *
 * Then, in its own terminal, because artisan serve implements neither byte
 * ranges nor concurrency:
 *   node tools/dev-catalogue/video-server.mjs
 */

use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;

/*
 * 10.0.2.2 is the host as seen from inside the Android emulator, which is the
 * only consumer that matters here. A browser on this machine wants 127.0.0.1
 * and can be given it with JAMBO_VIDEO_BASE.
 */
$base = rtrim((string) (getenv('JAMBO_VIDEO_BASE') ?: 'http://10.0.2.2:8097'), '/');
$reset = (bool) getenv('JAMBO_VIDEO_RESET');

$default = $base . '/sintel-480.mp4';
$low = $base . '/bunny-240.mp4';

// How many movies get a source. Not all 79: the point is to prove the path,
// and leaving most titles unplayable keeps the CONTENT_UNAVAILABLE refusal
// reachable, which is a state the player has to render honestly too.
$movieCount = 6;

if ($reset) {
    $movies = Movie::query()->whereNotNull('video_url')->update(['video_url' => null, 'video_url_low' => null]);
    $episodes = Episode::query()->whereNotNull('video_url')->update(['video_url' => null, 'video_url_low' => null]);
    echo "reset: cleared video_url on {$movies} movies and {$episodes} episodes\n";
    echo "dropbox_path was never touched, so the seeded placeholder shape is back\n";

    return;
}

$fixtures = [
    'sintel-480.mp4' => 'https://media.w3.org/2010/05/sintel/trailer.mp4',
    'bunny-240.mp4' => 'https://www.w3schools.com/html/mov_bbb.mp4',
];

$dir = public_path('storage/dev-video');
if (! is_dir($dir)) {
    mkdir($dir, 0o755, true);
}

foreach ($fixtures as $name => $source) {
    $path = $dir . DIRECTORY_SEPARATOR . $name;
    if (is_file($path) && filesize($path) > 0) {
        echo 'have ' . $name . ' (' . number_format(filesize($path)) . " bytes)\n";

        continue;
    }

    echo "downloading {$name} ...\n";
    $bytes = @file_get_contents($source, false, stream_context_create([
        'http' => ['timeout' => 120, 'user_agent' => 'Mozilla/5.0'],
    ]));

    if ($bytes === false || $bytes === '') {
        echo "  FAILED. Fetch it by hand into {$dir} and re-run.\n";

        continue;
    }

    file_put_contents($path, $bytes);
    echo '  ' . number_format(strlen($bytes)) . " bytes\n";
}

/*
 * Movies — PUBLICLY VISIBLE ones, ordered by id so a re-run picks the same
 * titles and the emulator's home screen does not change between runs.
 *
 * The visibility filter is not a nicety. The first version took the first six
 * by id, and two of them (`Goodbye Monster`, `Blades of the Guardians`) are
 * `draft`: `PlaybackAuthorizer::isReleased()` refuses a draft movie, so those
 * two answered CONTENT_UNAVAILABLE no matter what video they carried. Found on
 * the emulator by tapping a Continue Watching card and getting "This title is
 * not available yet." — the same mistake this script had already been fixed
 * for on the episode side, made twice because the two selections were written
 * separately.
 */
$movies = Movie::query()
    ->orderBy('id')
    ->get()
    ->filter(fn (Movie $movie) => $movie->isPubliclyVisible())
    ->take($movieCount)
    ->values();

foreach ($movies as $index => $movie) {
    /*
     * Only the first three get a low rendition. `available_qualities` is
     * derived from which columns are filled, so this leaves real titles in
     * BOTH states — some where Data Saver is offered and some where asking for
     * it must be refused with CONTENT_UNAVAILABLE rather than silently served
     * at full size. The refusal is a branch the player has to handle and it
     * cannot be tested if every title has both.
     */
    $movie->video_url = $default;
    $movie->video_url_low = $index < 3 ? $low : null;
    $movie->save();

    $qualities = $index < 3 ? 'default+low' : 'default only';
    echo "movie #{$movie->id} {$movie->title} -> {$qualities}\n";
}

/*
 * One whole SERIES — every season of it — so "next episode" has somewhere to
 * go and can be watched crossing a season boundary, which is the case that
 * gets missed when a fixture covers one season.
 *
 * The show must be publicly visible, and that is not a formality. The first
 * season in this database belongs to `Wonder Man VJ NEIL`, whose status is
 * `draft`; PlaybackAuthorizer::isReleased() refuses every episode of a draft
 * show, so the obvious `Season::orderBy('id')->first()` produced six episodes
 * that all answered CONTENT_UNAVAILABLE. Found by calling the endpoint rather
 * than by reading the fixture back.
 */
$show = null;
foreach (Season::query()->with('show')->orderBy('id')->get() as $candidate) {
    if ($candidate->show !== null && $candidate->show->isPubliclyVisible()) {
        $show = $candidate->show;
        break;
    }
}

if ($show === null) {
    echo "no publicly visible show in this database; episodes left alone\n";

    return;
}

$seasonIds = Season::query()->where('show_id', $show->id)->orderBy('number')->pluck('id');
$episodes = Episode::query()
    ->whereIn('season_id', $seasonIds)
    ->with('season')
    ->orderBy('season_id')
    ->orderBy('number')
    ->get();

foreach ($episodes as $episode) {
    $episode->video_url = $default;
    // Every episode carries both, because the quality menu must keep its
    // selection across an autoplay into the next episode and that is only
    // observable when the next episode actually has the rendition.
    $episode->video_url_low = $low;
    $episode->save();

    printf(
        "episode #%-4d S%dE%d %s\n",
        $episode->id,
        (int) ($episode->season->number ?? 0),
        (int) $episode->number,
        $episode->title,
    );
}

echo "\nseries: {$show->title} ({$show->slug}), " . $seasonIds->count() . " seasons\n";
echo "{$movies->count()} movies and {$episodes->count()} episodes are playable from {$base}\n";
echo "Start the byte-range server:  node tools/dev-catalogue/video-server.mjs\n";
