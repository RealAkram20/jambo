<?php

namespace Modules\Content\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Vj;

/**
 * Assigns one VJ per movie / show, rotating through the 10 seeded VJs
 * so every VJ ends up with roughly the same workload. Episodes inherit
 * their show's VJ — the data model deliberately attaches VJs at the
 * series level, not per-episode.
 *
 * Safe to re-run: uses sync() so the assignment is deterministic by id.
 */
class VjAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $vjs = Vj::orderBy('id')->pluck('id')->all();
        if (empty($vjs)) {
            $this->command?->warn('No VJs found — run VjSeeder first.');
            return;
        }

        $n = count($vjs);

        $movies = Movie::orderBy('id')->get(['id']);
        foreach ($movies as $i => $m) {
            $m->vjs()->sync([$vjs[$i % $n]]);
        }
        $this->command?->info("Movies assigned: {$movies->count()} across {$n} VJs");

        $shows = Show::orderBy('id')->get(['id']);
        foreach ($shows as $i => $s) {
            // Offset by 3 so shows don't all get the same VJ as the first movies.
            $s->vjs()->sync([$vjs[($i + 3) % $n]]);
        }
        $this->command?->info("Shows assigned: {$shows->count()} across {$n} VJs");
    }
}
