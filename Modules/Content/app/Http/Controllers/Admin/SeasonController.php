<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Content\app\Http\Requests\StoreSeasonRequest;
use Modules\Content\app\Http\Requests\UpdateSeasonRequest;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;

/**
 * Admin CRUD for seasons (nested under a Series).
 *
 * Routes: /admin/series/{show:slug}/seasons/{season:number}/*
 * Middleware: web + auth + role:admin (set in the route file).
 */
class SeasonController extends Controller
{
    public function create(Show $show): View
    {
        return view('content::admin.seasons.create', [
            'show' => $show,
            'season' => new Season([
                'show_id' => $show->id,
                'number' => ($show->seasons()->max('number') ?? 0) + 1,
            ]),
        ]);
    }

    public function store(StoreSeasonRequest $request, Show $show): RedirectResponse
    {
        $data = $request->validated();

        $season = $show->seasons()->create([
            'number' => $data['number'],
            'title' => $data['title'] ?? null,
            'synopsis' => $data['synopsis'] ?? null,
            'poster_url' => $data['poster_url'] ?? null,
            'released_at' => $data['released_at'] ?? null,
        ]);

        event(new \Modules\Notifications\app\Events\SeasonAdded(
            $show->title, $season->number, $show->slug, $season->poster_url ?? $show->poster_url,
        ));

        return redirect()
            ->route('admin.series.seasons.edit', [$show, $season])
            ->with('success', "Season {$season->number} added.");
    }

    public function edit(Show $show, Season $season): View
    {
        $season->load(['episodes' => fn ($q) => $q->orderBy('number')]);

        return view('content::admin.seasons.edit', [
            'season' => $season,
            'show' => $show,
            'episodes' => $season->episodes,
        ]);
    }

    public function update(UpdateSeasonRequest $request, Show $show, Season $season): RedirectResponse
    {
        $data = $request->validated();

        $season->fill([
            'number' => $data['number'],
            'title' => $data['title'] ?? null,
            'synopsis' => $data['synopsis'] ?? null,
            'poster_url' => $data['poster_url'] ?? null,
            'released_at' => $data['released_at'] ?? null,
        ])->save();

        return redirect()
            ->route('admin.series.seasons.edit', [$show, $season])
            ->with('success', 'Season saved.');
    }

    public function destroy(Show $show, Season $season): RedirectResponse
    {
        $number = $season->number;
        $season->delete();

        return redirect()
            ->route('admin.series.edit', $show)
            ->with('success', "Deleted season {$number}.");
    }
}
