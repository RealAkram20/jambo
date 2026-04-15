<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Content\app\Http\Requests\StoreEpisodeRequest;
use Modules\Content\app\Http\Requests\UpdateEpisodeRequest;
use Modules\Content\app\Jobs\TranscodeVideoJob;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;

/**
 * Admin CRUD for episodes (nested under Series → Season).
 *
 * Routes: /admin/series/{show:slug}/seasons/{season:number}/episodes/{episode:number}/*
 * Middleware: web + auth + role:admin (set in the route file).
 */
class EpisodeController extends Controller
{
    public function create(Show $show, Season $season): View
    {
        return view('content::admin.episodes.create', [
            'season' => $season,
            'show' => $show,
            'episode' => new Episode([
                'season_id' => $season->id,
                'number' => ($season->episodes()->max('number') ?? 0) + 1,
            ]),
        ]);
    }

    public function store(StoreEpisodeRequest $request, Show $show, Season $season): RedirectResponse
    {
        $data = $request->validated();

        [$videoUrl, $dropboxPath] = $this->resolveVideoSource($data);

        $episode = $season->episodes()->create([
            'number' => $data['number'],
            'title' => $data['title'],
            'synopsis' => $data['synopsis'] ?? null,
            'runtime_minutes' => $data['runtime_minutes'] ?? null,
            'still_url' => $data['still_url'] ?? null,
            'dropbox_path' => $dropboxPath,
            'video_url' => $videoUrl,
            'tier_required' => $data['tier_required'] ?? null,
            'published_at' => $data['published_at'] ?? null,
        ]);

        $this->handleVideoUpload($request, $episode);

        return redirect()
            ->route('admin.series.seasons.episodes.edit', [$show, $season, $episode])
            ->with('success', "Episode {$episode->number} added.");
    }

    public function edit(Show $show, Season $season, Episode $episode): View
    {
        return view('content::admin.episodes.edit', [
            'episode' => $episode,
            'season' => $season,
            'show' => $show,
        ]);
    }

    public function update(UpdateEpisodeRequest $request, Show $show, Season $season, Episode $episode): RedirectResponse
    {
        $data = $request->validated();

        $wasPublished = (bool) $episode->published_at;
        [$videoUrl, $dropboxPath] = $this->resolveVideoSource($data);

        $episode->fill([
            'number' => $data['number'],
            'title' => $data['title'],
            'synopsis' => $data['synopsis'] ?? null,
            'runtime_minutes' => $data['runtime_minutes'] ?? null,
            'still_url' => $data['still_url'] ?? null,
            'dropbox_path' => $dropboxPath,
            'video_url' => $videoUrl,
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

        $this->handleVideoUpload($request, $episode);

        return redirect()
            ->route('admin.series.seasons.episodes.edit', [$show, $season, $episode])
            ->with('success', 'Episode saved.');
    }

    /**
     * Resolve the video source based on the active tab (video_source).
     * Mirror of MovieController::resolveVideoSource.
     */
    private function resolveVideoSource(array $data): array
    {
        $source = $data['video_source'] ?? null;
        $url = trim((string) ($data['video_url'] ?? ''));
        $local = trim((string) ($data['video_local'] ?? ''));
        $dropbox = trim((string) ($data['dropbox_path'] ?? ''));

        if (!$source) {
            if ($dropbox !== '') $source = 'dropbox';
            elseif ($local !== '') $source = 'local';
            else $source = 'url';
        }

        return match ($source) {
            'local'   => [$local !== '' ? $local : null, null],
            'dropbox' => [null, $dropbox !== '' ? $dropbox : null],
            default   => [$url !== '' ? $url : null, null],
        };
    }

    /**
     * Mirror of MovieController@handleVideoUpload — see comments there.
     * Duplicated instead of pushed into a shared trait because the two
     * methods may diverge (e.g. episode-specific rendition ladder).
     */
    private function handleVideoUpload(Request $request, Episode $episode): void
    {
        if (!$request->hasFile('video_file')) return;

        $file = $request->file('video_file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'mp4');

        Storage::disk('source')->putFileAs(
            'episodes/' . $episode->id,
            $file,
            'source.' . $ext
        );

        if ($episode->hls_master_path) {
            Storage::disk('hls')->deleteDirectory('episode/' . $episode->id);
        }

        $episode->forceFill([
            'source_path' => 'episodes/' . $episode->id . '/source.' . $ext,
            'hls_master_path' => null,
            'transcode_status' => 'queued',
            'transcode_error' => null,
            'video_url' => null,
        ])->save();

        TranscodeVideoJob::dispatch('episode', $episode->id);
    }

    public function destroy(Show $show, Season $season, Episode $episode): RedirectResponse
    {
        $number = $episode->number;
        $episode->delete();

        return redirect()
            ->route('admin.series.seasons.edit', [$show, $season])
            ->with('success', "Deleted episode {$number}.");
    }
}
