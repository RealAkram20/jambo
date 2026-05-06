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

        // Status comes from the form's Draft/Upcoming/Published picker
        // (UI helper — episodes don't have a status column, but using
        // the picker to drive published_at gives admins the same
        // mental model as movies). Mirrors MovieController: status
        // changing to published with no explicit date stamps now().
        $publishedAt = $this->resolveEpisodePublishedAt(
            $request->input('status', 'draft'),
            $data['published_at'] ?? null,
        );

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
            'published_at' => $publishedAt,
        ]);

        // Only notify if the show itself is publicly available —
        // otherwise the action_url on the notification (/series/slug)
        // 404s for draft shows, or lands on a "Coming Soon" stub for
        // upcoming shows. Either way the user can't actually watch
        // the episode the alert announces. See Show::isPubliclyAvailable.
        if ($episode->published_at && $show->isPubliclyAvailable()) {
            event(new \Modules\Notifications\app\Events\EpisodeAdded(
                $show->title, $season->number, $episode->number,
                $episode->title, $show->slug, $episode->still_url ?? $show->poster_url,
            ));
        }

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
            'video_url_low' => trim($data['video_url_low'] ?? '') ?: null,
            'tier_required' => $data['tier_required'] ?? null,
        ]);

        // Status picker (UI helper) drives published_at the same way
        // as the movies form: Draft clears the date, Published stamps
        // now() if the admin didn't pick one, Upcoming keeps whatever
        // future date they entered. Falls back to the previously
        // stored value if the form didn't send a status field at all.
        $statusInput = $request->input('status');
        if ($statusInput) {
            $episode->published_at = $this->resolveEpisodePublishedAt(
                $statusInput,
                $data['published_at'] ?? null,
            );
        } elseif (array_key_exists('published_at', $data)) {
            $episode->published_at = $data['published_at'] ?: null;
        }

        $episode->save();

        $justPublished = !$wasPublished && $episode->published_at !== null;
        // Same gate as store(): suppress when the show is still draft
        // or upcoming so the notification's "/series/slug" link doesn't
        // 404 / dead-end for the user.
        if ($justPublished && $show->isPubliclyAvailable()) {
            event(new \Modules\Notifications\app\Events\EpisodeAdded(
                $show->title, $season->number, $episode->number,
                $episode->title, $show->slug, $episode->still_url ?? $show->poster_url,
            ));
        }

        return redirect()
            ->route('admin.series.seasons.episodes.edit', [$show, $season, $episode])
            ->with('success', 'Episode saved.');
    }

    /**
     * Translate the form's Draft/Upcoming/Published picker into the
     * actual published_at value to store. Mirrors what MovieController
     * does inline — published_at is the single source of truth on
     * episodes, but admins think in terms of status, so we keep the
     * conversion logic explicit and shared between store() and
     * update().
     */
    private function resolveEpisodePublishedAt(string $status, mixed $rawDate): mixed
    {
        $rawDate = is_string($rawDate) && trim($rawDate) === '' ? null : $rawDate;

        return match ($status) {
            'draft'     => null,
            'published' => $rawDate ?: now(),
            'upcoming'  => $rawDate ?: null, // admin should pick a date; null = treated as draft
            default     => $rawDate ?: null,
        };
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

    public function destroy(Show $show, Season $season, Episode $episode): RedirectResponse
    {
        $number = $episode->number;
        $episode->delete();

        return redirect()
            ->route('admin.series.seasons.edit', [$show, $season])
            ->with('success', "Deleted episode {$number}.");
    }
}
