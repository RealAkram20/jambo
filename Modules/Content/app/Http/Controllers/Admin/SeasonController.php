<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Content\app\Http\Requests\StoreSeasonRequest;
use Modules\Content\app\Http\Requests\UpdateSeasonRequest;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;

/**
 * Admin CRUD for seasons.
 *
 * There is no index view — seasons are always listed on the parent
 * show's edit page. create/store/edit/update/destroy only.
 *
 * Routes: /admin/seasons/*
 * Middleware: web + auth + role:admin (set in the route file).
 */
class SeasonController extends Controller
{
    public function create(Request $request): View
    {
        $request->validate([
            'show_id' => 'required|integer|exists:shows,id',
        ]);

        $show = Show::findOrFail($request->query('show_id'));

        return view('content::admin.seasons.create', [
            'show' => $show,
            'season' => new Season(['show_id' => $show->id, 'number' => ($show->seasons()->max('number') ?? 0) + 1]),
        ]);
    }

    public function store(StoreSeasonRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $season = Season::create([
            'show_id' => $data['show_id'],
            'number' => $data['number'],
            'title' => $data['title'] ?? null,
            'synopsis' => $data['synopsis'] ?? null,
            'poster_url' => $data['poster_url'] ?? null,
            'released_at' => $data['released_at'] ?? null,
        ]);

        return redirect()
            ->route('admin.seasons.edit', $season)
            ->with('success', "Season {$season->number} added.");
    }

    public function edit(Season $season): View
    {
        $season->load(['show', 'episodes' => fn ($q) => $q->orderBy('number')]);

        return view('content::admin.seasons.edit', [
            'season' => $season,
            'show' => $season->show,
            'episodes' => $season->episodes,
        ]);
    }

    public function update(UpdateSeasonRequest $request, Season $season): RedirectResponse
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
            ->route('admin.seasons.edit', $season)
            ->with('success', 'Season saved.');
    }

    public function destroy(Season $season): RedirectResponse
    {
        $showId = $season->show_id;
        $number = $season->number;
        $season->delete();

        return redirect()
            ->route('admin.shows.edit', $showId)
            ->with('success', "Deleted season {$number}.");
    }
}
