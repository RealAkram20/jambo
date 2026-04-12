<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Content\app\Http\Requests\StoreEpisodeRequest;
use Modules\Content\app\Http\Requests\UpdateEpisodeRequest;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Season;

/**
 * Admin CRUD for episodes.
 *
 * No index view — episodes are always listed on the parent season's
 * edit page. create/store/edit/update/destroy only.
 *
 * Routes: /admin/episodes/*
 * Middleware: web + auth + role:admin (set in the route file).
 */
class EpisodeController extends Controller
{
    public function create(Request $request): View
    {
        $request->validate([
            'season_id' => 'required|integer|exists:seasons,id',
        ]);

        $season = Season::with('show')->findOrFail($request->query('season_id'));

        return view('content::admin.episodes.create', [
            'season' => $season,
            'show' => $season->show,
            'episode' => new Episode([
                'season_id' => $season->id,
                'number' => ($season->episodes()->max('number') ?? 0) + 1,
            ]),
        ]);
    }

    public function store(StoreEpisodeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $episode = Episode::create([
            'season_id' => $data['season_id'],
            'number' => $data['number'],
            'title' => $data['title'],
            'synopsis' => $data['synopsis'] ?? null,
            'runtime_minutes' => $data['runtime_minutes'] ?? null,
            'still_url' => $data['still_url'] ?? null,
            'dropbox_path' => $data['dropbox_path'] ?? null,
            'tier_required' => $data['tier_required'] ?? null,
            'published_at' => $data['published_at'] ?? null,
        ]);

        return redirect()
            ->route('admin.episodes.edit', $episode)
            ->with('success', "Episode {$episode->number} added.");
    }

    public function edit(Episode $episode): View
    {
        $episode->load(['season.show']);

        return view('content::admin.episodes.edit', [
            'episode' => $episode,
            'season' => $episode->season,
            'show' => $episode->season->show,
        ]);
    }

    public function update(UpdateEpisodeRequest $request, Episode $episode): RedirectResponse
    {
        $data = $request->validated();

        $wasPublished = (bool) $episode->published_at;

        $episode->fill([
            'number' => $data['number'],
            'title' => $data['title'],
            'synopsis' => $data['synopsis'] ?? null,
            'runtime_minutes' => $data['runtime_minutes'] ?? null,
            'still_url' => $data['still_url'] ?? null,
            'dropbox_path' => $data['dropbox_path'] ?? null,
            'tier_required' => $data['tier_required'] ?? null,
        ]);

        // published_at: if the form sent a value, use it; otherwise if
        // the user cleared it, null it; otherwise leave as-is. We also
        // stamp now() automatically when toggling a draft episode to
        // published by setting a value for the first time.
        if (array_key_exists('published_at', $data)) {
            $episode->published_at = $data['published_at'] ?: null;
        }

        if (!$wasPublished && $episode->published_at === null && ($data['published_at'] ?? null)) {
            $episode->published_at = now();
        }

        $episode->save();

        return redirect()
            ->route('admin.episodes.edit', $episode)
            ->with('success', 'Episode saved.');
    }

    public function destroy(Episode $episode): RedirectResponse
    {
        $seasonId = $episode->season_id;
        $number = $episode->number;
        $episode->delete();

        return redirect()
            ->route('admin.seasons.edit', $seasonId)
            ->with('success', "Deleted episode {$number}.");
    }
}
