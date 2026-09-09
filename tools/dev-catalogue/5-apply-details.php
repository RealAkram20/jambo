<?php
/*
 * Put the real synopses and years onto the LOCAL rows, matched by title.
 *
 * Matched on title rather than slug because the importer gives clones their
 * own slug suffix: the same film can legitimately exist twice locally to fill
 * the rails, and both copies should read the same.
 */

use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;

$file = getenv('JAMBO_CATALOGUE_DIR') . '/live-details.json';
$details = json_decode(file_get_contents($file), true);

$byTitle = [];
foreach ($details as $d) {
    $byTitle[$d['title']] = $d;
}

$updated = 0;
foreach ([Movie::class, Show::class] as $model) {
    foreach ($model::all() as $row) {
        $d = $byTitle[$row->title] ?? null;
        if ($d === null) continue;

        if ($d['synopsis'] !== '') $row->synopsis = $d['synopsis'];
        if ($d['year'] !== null) $row->year = $d['year'];
        $row->save();
        $updated++;
    }
}

echo "updated {$updated} rows with real synopses and years\n";
