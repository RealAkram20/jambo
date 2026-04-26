<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Content\app\Http\Requests\StoreEpisodeRequest;
use Modules\Content\app\Http\Requests\UpdateEpisodeRequest;
use Modules\Content\app\Jobs\DownloadAndTranscodeJob;
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
            'video_url_low' => trim($data['video_url_low'] ?? '') ?: null,
            'tier_required' => $data['tier_required'] ?? null,
            'published_at' => $data['published_at'] ?? null,
        ]);

        // Queue transcode for whatever video source the admin provided.
        $this->handleVideoUpload($request, $episode);
        $this->handleLocalTranscode($request, $episode);
        $this->handleDropboxTranscode($request, $episode);

        $deferred = $this->applyPublishDeferral($episode);

        if ($episode->published_at && !$deferred) {
            event(new \Modules\Notifications\app\Events\EpisodeAdded(
                $show->title, $season->number, $episode->number,
                $episode->title, $show->slug, $episode->still_url ?? $show->poster_url,
            ));
        }

        $message = $deferred
            ? "Episode {$episode->number} saved. It will publish automatically once transcoding finishes."
            : "Episode {$episode->number} added.";

        return redirect()
            ->route('admin.series.seasons.episodes.edit', [$show, $season, $episode])
            ->with('success', $message);
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
            'video_url_low' => trim($data['video_url_low'] ?? '') ?: null,
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

        // Pick up new video source + dispatch transcode if needed.
        $this->handleVideoUpload($request, $episode);
        $this->handleLocalTranscode($request, $episode);
        $this->handleDropboxTranscode($request, $episode);

        $deferred = $this->applyPublishDeferral($episode);

        $justPublished = !$wasPublished && $episode->published_at !== null;
        if ($justPublished && !$deferred) {
            event(new \Modules\Notifications\app\Events\EpisodeAdded(
                $show->title, $season->number, $episode->number,
                $episode->title, $show->slug, $episode->still_url ?? $show->poster_url,
            ));
        }

        $message = $deferred
            ? 'Episode saved. It will publish automatically once transcoding finishes.'
            : 'Episode saved.';

        return redirect()
            ->route('admin.series.seasons.episodes.edit', [$show, $season, $episode])
            ->with('success', $message);
    }

    /**
     * Mirrors MovieController::applyPublishDeferral but sized for the
     * episode model — episodes don't have a `status` column, so the
     * "is this published?" signal is just whether published_at is set.
     */
    private function applyPublishDeferral(Episode $episode): bool
    {
        if (!$episode->published_at) {
            return false; // admin didn't ask to publish
        }

        $hasVideoSource = !empty($episode->video_url) || !empty($episode->dropbox_path) || !empty($episode->source_path);
        if (!$hasVideoSource) {
            return false;
        }

        if ($episode->transcode_status === 'ready') {
            return false;
        }

        $episode->forceFill([
            'published_at'        => null, // we'll set it for real on transcode complete
            'publish_when_ready'  => true,
        ])->save();

        return true;
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

        $isDropboxUrl = $dropbox !== '' && str_starts_with($dropbox, 'http');

        return match ($source) {
            'local'   => [$local !== '' ? $local : null, null],
            'dropbox' => [$isDropboxUrl ? $dropbox : null, $dropbox !== '' ? $dropbox : null],
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

    /**
     * When a local file is chosen via FileManager, copy it to the private
     * `source` disk and queue the transcode job.
     */
    private function handleLocalTranscode(Request $request, Episode $episode): void
    {
        if ($request->hasFile('video_file')) return;
        if (($request->input('video_source') ?? '') !== 'local') return;

        $localUrl = trim((string) $request->input('video_local'));
        if ($localUrl === '') return;

        if ($episode->transcode_status === 'ready' && $episode->video_url === $localUrl) return;
        if ($episode->transcode_status === 'queued' || $episode->transcode_status === 'transcoding') return;

        $absolute = $this->resolveLocalPath($localUrl);
        if (!$absolute || !file_exists($absolute)) return;

        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION) ?: 'mp4');
        $destPath = 'episodes/' . $episode->id . '/source.' . $ext;

        Storage::disk('source')->makeDirectory('episodes/' . $episode->id);
        $stream = fopen($absolute, 'r');
        Storage::disk('source')->put($destPath, $stream);
        if (is_resource($stream)) fclose($stream);

        if ($episode->hls_master_path) {
            Storage::disk('hls')->deleteDirectory('episode/' . $episode->id);
        }

        $episode->forceFill([
            'source_path' => $destPath,
            'hls_master_path' => null,
            'transcode_status' => 'queued',
            'transcode_error' => null,
        ])->save();

        TranscodeVideoJob::dispatch('episode', $episode->id);
    }

    private function resolveLocalPath(string $url): ?string
    {
        $parsed = urldecode(parse_url($url, PHP_URL_PATH) ?: $url);

        $base = parse_url(config('app.url'), PHP_URL_PATH) ?: '';
        $base = rtrim($base, '/');
        if ($base !== '' && str_starts_with($parsed, $base)) {
            $parsed = substr($parsed, strlen($base));
        }

        $absolute = public_path(ltrim($parsed, '/'));

        $real = realpath($absolute);
        if (!$real || !str_starts_with($real, realpath(public_path()))) {
            return null;
        }

        return $real;
    }

    private function handleDropboxTranscode(Request $request, Episode $episode): void
    {
        if ($request->hasFile('video_file')) return;
        if (($request->input('video_source') ?? '') !== 'dropbox') return;

        $dropbox = trim((string) $request->input('dropbox_path'));
        if ($dropbox === '' || !str_starts_with($dropbox, 'http')) return;

        if ($episode->transcode_status === 'ready' && $episode->dropbox_path === $dropbox) return;
        if (in_array($episode->transcode_status, ['queued', 'downloading', 'transcoding'])) return;

        $downloadUrl = $this->normaliseDropboxUrl($dropbox);

        $episode->forceFill([
            'transcode_status' => 'queued',
            'transcode_error'  => null,
            'hls_master_path'  => null,
        ])->save();

        DownloadAndTranscodeJob::dispatch('episode', $episode->id, $downloadUrl);
    }

    private function normaliseDropboxUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (str_starts_with($path, '/scl/')) {
            $url = preg_replace('/([?&])dl=\d+/', '$1dl=1', $url);
            if (!str_contains($url, 'dl=1')) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'dl=1';
            }
        } else {
            $url = preg_replace('#^https?://(www\.)?dropbox\.com/#i', 'https://dl.dropboxusercontent.com/', $url);
            $url = preg_replace('/([?&])dl=\d+(&|$)/i', '$1', $url);
            $url = rtrim($url, '?&');
        }

        return $url;
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
