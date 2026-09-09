<?php

/*
 * Re-dress the LOCAL development catalogue with the real one.
 *
 * Development database only. It rewrites titles, slugs and artwork on the
 * seeded rows and clones more rows to fill the rails, so the app renders the
 * real Jambo catalogue instead of lorem ipsum over picsum placeholders.
 *
 * Nothing here touches production, and nothing here is a migration: every
 * column written is content, and the whole thing is re-runnable.
 *
 * WHY THE PATHS ARE STORED APP-ABSOLUTE. `/storage/gallery/...` is exactly the
 * shape production stores, so the local API now returns exactly what the live
 * API will return. That is the point: the app's `imageUrl()` helper and the
 * site's `/img` resize proxy are both exercised for real, which the picsum
 * seed could never do.
 */

use Illuminate\Support\Str;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;

$file = getenv('JAMBO_CATALOGUE_DIR') . '/live-titles-local.json';
$titles = json_decode(file_get_contents($file), true);

$movies = array_values(array_filter($titles, fn ($t) => ! $t['is_show']));
$shows = array_values(array_filter($titles, fn ($t) => $t['is_show']));

echo 'source: ' . count($movies) . " movies, " . count($shows) . " series\n";

/**
 * Apply one real title to one local row.
 *
 * The row keeps its tier, its genres, its cast and its status — everything the
 * rails and the entitlement rules read — and only its identity and artwork
 * change. Cloning a row wholesale and inventing relationships would produce a
 * catalogue that looked right and behaved differently from the seed the tests
 * were written against.
 */
$apply = function ($row, array $title, int $index) {
    $row->title = $title['title'];
    $row->slug = Str::slug($title['title']) . '-' . $index;
    $row->poster_url = $title['poster_path'];
    // The site's cards carry no backdrop, and the detail screen falls back to
    // the poster anyway. Better an honest reuse than a wrong image.
    $row->backdrop_url = $title['poster_path'];
    $row->save();
};

// ── 1. Re-dress what already exists ──────────────────────────────────
$existingMovies = Movie::orderBy('id')->get();
foreach ($existingMovies as $i => $movie) {
    if (! isset($movies[$i])) break;
    $apply($movie, $movies[$i], $movie->id);
}
echo "re-dressed {$existingMovies->count()} movies\n";

$existingShows = Show::orderBy('id')->get();
foreach ($existingShows as $i => $show) {
    if (! isset($shows[$i])) break;
    $apply($show, $shows[$i], $show->id);
}
echo "re-dressed {$existingShows->count()} series\n";

// ── 2. Clone more, so the rails are full ─────────────────────────────
/*
 * A rail of ten needs more than twenty titles in the whole catalogue or every
 * shelf shows the same films. Clones copy a real row's columns and then take a
 * new identity, and they take the genre/category/cast pivots of the row they
 * were copied from so the taxonomy screens stay populated and consistent.
 */
$clone = function ($source, array $title, int $ordinal, array $relations) {
    $new = $source->replicate(['id', 'created_at', 'updated_at']);
    $new->title = $title['title'];
    $new->slug = Str::slug($title['title']) . '-c' . $ordinal;
    $new->poster_url = $title['poster_path'];
    $new->backdrop_url = $title['poster_path'];
    // Spread the catalogue over time so "Latest" and "Fresh" rails differ.
    $new->created_at = now()->subDays(random_int(1, 400))->subMinutes($ordinal);
    $new->published_at = $new->created_at;
    $new->views_count = random_int(120, 900000);
    $new->save();

    foreach ($relations as $name) {
        if (! method_exists($source, $name)) continue;

        /*
         * The pivot payload is copied, not just the ids. `movie_person` has a
         * required `role` column with no default, so syncing bare ids fails
         * outright — and a cast list with the roles stripped would be worse
         * than the error, because it would look like it had worked.
         */
        $related = $source->{$name}()->getRelated()->getTable();
        $rows = $source->{$name}()->get();
        if ($rows->isEmpty()) continue;

        $payload = [];
        foreach ($rows as $item) {
            $pivot = $item->pivot?->getAttributes() ?? [];
            unset($pivot['movie_id'], $pivot['show_id'], $pivot['person_id'],
                  $pivot['genre_id'], $pivot['category_id'], $pivot['tag_id'],
                  $pivot['vj_id'], $pivot['id'], $pivot['created_at'], $pivot['updated_at']);
            $payload[$item->id] = $pivot;
        }

        try {
            $new->{$name}()->sync($payload);
        } catch (\Throwable $e) {
            echo "  skipped {$name} on {$new->slug}: " . substr($e->getMessage(), 0, 90) . "
";
        }
    }

    return $new;
};

$movieSources = Movie::with(['genres', 'categories', 'cast'])->get();
$made = 0;
for ($i = $existingMovies->count(); $i < count($movies); $i++) {
    $source = $movieSources[$i % $movieSources->count()];
    $clone($source, $movies[$i], $i, ['genres', 'categories', 'cast', 'tags', 'vjs']);
    $made++;
}
echo "cloned {$made} movies\n";

$showSources = Show::with(['genres', 'categories', 'cast'])->get();
$madeShows = 0;
for ($i = $existingShows->count(); $i < count($shows); $i++) {
    $source = $showSources[$i % $showSources->count()];
    $clone($source, $shows[$i], $i, ['genres', 'categories', 'cast', 'tags', 'vjs']);
    $madeShows++;
}
echo "cloned {$madeShows} series\n";

echo "\ntotal: " . Movie::count() . ' movies, ' . Show::count() . " series\n";
