<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Content\app\Http\Requests\StoreMovieRequest;
use Modules\Content\app\Http\Requests\UpdateMovieRequest;
use Modules\Content\app\Jobs\DownloadAndTranscodeJob;
use Modules\Content\app\Jobs\TranscodeVideoJob;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Vj;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Tag;

/**
 * Admin CRUD for movies.
 *
 * The form handles the movie row itself + its genre/category/tag
 * attachments + its cast. Poster and backdrop are stored as URLs for
 * now (the existing spatie/laravel-medialibrary integration will take
 * over in a later phase).
 *
 * Routes: /admin/movies/*
 * Middleware: web + auth + role:admin (set in the route file).
 */
class MovieController extends Controller
{
    public function index(Request $request): View
    {
        $query = Movie::query()
            ->with(['genres', 'categories'])
            ->withCount('cast');

        if ($search = trim((string) $request->query('q'))) {
            $query->where('title', 'like', "%$search%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $movies = $query
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('content::admin.movies.index', [
            'movies' => $movies,
            'search' => $search,
            'statusFilter' => $status,
            'statusCounts' => [
                'all' => Movie::count(),
                'draft' => Movie::where('status', 'draft')->count(),
                'upcoming' => Movie::where('status', 'upcoming')->count(),
                'published' => Movie::where('status', 'published')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('content::admin.movies.create', [
            'movie' => new Movie(['status' => 'draft', 'year' => now()->year]),
            'genres' => Genre::orderBy('name')->get(),
            'vjs' => Vj::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'persons' => Person::orderBy('last_name')->orderBy('first_name')->get(),
            'currentVjIds' => [],
        ]);
    }

    public function store(StoreMovieRequest $request): RedirectResponse
    {
        $wasPublished = false;
        $movie = DB::transaction(function () use ($request, &$wasPublished) {
            $data = $request->validated();
            [$videoUrl, $dropboxPath] = $this->resolveVideoSource($data);

            $movie = Movie::create([
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['title']),
                'synopsis' => $data['synopsis'] ?? null,
                'year' => $data['year'] ?? null,
                'runtime_minutes' => $data['runtime_minutes'] ?? null,
                'rating' => $data['rating'] ?? null,
                'poster_url' => $data['poster_url'] ?? null,
                'backdrop_url' => $data['backdrop_url'] ?? null,
                'trailer_url' => $data['trailer_url'] ?? null,
                'dropbox_path' => $dropboxPath,
                'video_url' => $videoUrl,
                'video_url_low' => trim($data['video_url_low'] ?? '') ?: null,
                'tier_required' => $data['tier_required'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'published_at' => ($data['status'] ?? 'draft') === 'published' ? now() : null,
            ]);

            $this->syncRelationships($movie, $data);

            $wasPublished = $movie->status === 'published';

            return $movie;
        });

        if ($wasPublished) {
            event(new \Modules\Notifications\app\Events\MovieAdded(
                $movie->id, $movie->title, $movie->slug, $movie->poster_url,
            ));
        }

        return redirect()
            ->route('admin.movies.edit', $movie)
            ->with('success', "Movie \"{$movie->title}\" created.");
    }

    public function edit(Movie $movie): View
    {
        $movie->load(['genres', 'vjs', 'categories', 'tags', 'cast']);

        return view('content::admin.movies.edit', [
            'movie' => $movie,
            'genres' => Genre::orderBy('name')->get(),
            'vjs' => Vj::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'persons' => Person::orderBy('last_name')->orderBy('first_name')->get(),
            'currentGenreIds' => $movie->genres->pluck('id')->toArray(),
            'currentVjIds' => $movie->vjs->pluck('id')->toArray(),
            'currentCategoryIds' => $movie->categories->pluck('id')->toArray(),
            'currentTagIds' => $movie->tags->pluck('id')->toArray(),
            'currentCast' => $movie->cast->map(fn ($p) => [
                'id' => $p->id,
                'role' => $p->pivot->role,
                'character_name' => $p->pivot->character_name,
                'display_order' => $p->pivot->display_order,
                'label' => trim($p->first_name . ' ' . $p->last_name),
            ])->values(),
        ]);
    }

    public function update(UpdateMovieRequest $request, Movie $movie): RedirectResponse
    {
        $justPublished = false;
        DB::transaction(function () use ($request, $movie, &$justPublished) {
            $data = $request->validated();
            [$videoUrl, $dropboxPath] = $this->resolveVideoSource($data);

            $movie->fill([
                'title' => $data['title'],
                'synopsis' => $data['synopsis'] ?? null,
                'year' => $data['year'] ?? null,
                'runtime_minutes' => $data['runtime_minutes'] ?? null,
                'rating' => $data['rating'] ?? null,
                'poster_url' => $data['poster_url'] ?? null,
                'backdrop_url' => $data['backdrop_url'] ?? null,
                'trailer_url' => $data['trailer_url'] ?? null,
                'dropbox_path' => $dropboxPath,
                'video_url' => $videoUrl,
                'video_url_low' => trim($data['video_url_low'] ?? '') ?: null,
                'tier_required' => $data['tier_required'] ?? null,
            ]);

            // Only re-slug if title actually changed.
            if ($movie->isDirty('title')) {
                $movie->slug = $this->uniqueSlug($data['title'], $movie->id);
            }

            // Status transitions: draft → published stamps published_at.
            $oldStatus = $movie->status;
            if (($data['status'] ?? $movie->status) !== $movie->status) {
                $movie->status = $data['status'];
                if ($data['status'] === 'published' && !$movie->published_at) {
                    $movie->published_at = now();
                }
            }

            $movie->save();

            $this->syncRelationships($movie, $data);

            $justPublished = $oldStatus !== 'published' && $movie->status === 'published';
        });

        if ($justPublished) {
            event(new \Modules\Notifications\app\Events\MovieAdded(
                $movie->id, $movie->title, $movie->slug, $movie->poster_url,
            ));
        }

        return redirect()
            ->route('admin.movies.edit', $movie)
            ->with('success', 'Movie saved.');
    }

    /**
     * Resolve the video source based on the active tab (video_source).
     * Returns [video_url, dropbox_path] with the inactive fields nulled so
     * only one source of truth is persisted.
     *
     * Falls back to autodetection if no source was posted (API/legacy clients).
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

        // If the Dropbox value looks like a full URL, store it as video_url
        // too so the player picks it up without relying on the fallback.
        $isDropboxUrl = $dropbox !== '' && str_starts_with($dropbox, 'http');

        return match ($source) {
            'local'   => [$local !== '' ? $local : null, null],
            'dropbox' => [$isDropboxUrl ? $dropbox : null, $dropbox !== '' ? $dropbox : null],
            default   => [$url !== '' ? $url : null, null],
        };
    }

    /**
     * Save an uploaded video to the private `source` disk and queue a
     * transcode job. Existing HLS output for this movie is cleared so the
     * old stream stops serving the moment a new source replaces it.
     *
     * A file upload always wins over the `video_url` field — keeping both
     * in sync would be surprising; picking one and sticking with it is
     * easier to reason about.
     */
    private function handleVideoUpload(Request $request, Movie $movie): void
    {
        if (!$request->hasFile('video_file')) return;

        $file = $request->file('video_file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        $path = 'movies/' . $movie->id . '/source.' . $ext;

        Storage::disk('source')->putFileAs(
            'movies/' . $movie->id,
            $file,
            'source.' . $ext
        );

        // Wipe the previous HLS output; the old stream URL becomes 404
        // until the new transcode finishes.
        if ($movie->hls_master_path) {
            Storage::disk('hls')->deleteDirectory('movie/' . $movie->id);
        }

        $movie->forceFill([
            'source_path' => $path,
            'hls_master_path' => null,
            'transcode_status' => 'queued',
            'transcode_error' => null,
            // Clear the URL field: the file is now the source of truth.
            'video_url' => null,
        ])->save();

        TranscodeVideoJob::dispatch('movie', $movie->id);
    }

    /**
     * When a local file is chosen via FileManager, copy it to the private
     * `source` disk and queue the transcode job — same outcome as a direct
     * upload but without needing a multipart POST.
     *
     * Skipped when:
     *  - a direct file upload was already handled (handleVideoUpload wins)
     *  - the video_source isn't 'local'
     *  - the local path hasn't changed (avoids re-transcoding on every save)
     */
    private function handleLocalTranscode(Request $request, Movie $movie): void
    {
        // A direct upload already handled transcoding.
        if ($request->hasFile('video_file')) return;

        if (($request->input('video_source') ?? '') !== 'local') return;

        $localUrl = trim((string) $request->input('video_local'));
        if ($localUrl === '') return;

        // If this is the same URL we already transcoded, don't re-do it.
        if ($movie->transcode_status === 'ready' && $movie->video_url === $localUrl) return;
        if ($movie->transcode_status === 'queued' || $movie->transcode_status === 'transcoding') return;

        // Resolve the local URL to an absolute filesystem path.
        // FileManager stores paths like "/Jambo/storage/media/movies/file.mp4"
        // which maps to public_path() or the XAMPP document root.
        $absolute = $this->resolveLocalPath($localUrl);
        if (!$absolute || !file_exists($absolute)) return;

        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION) ?: 'mp4');
        $destPath = 'movies/' . $movie->id . '/source.' . $ext;

        Storage::disk('source')->makeDirectory('movies/' . $movie->id);
        $stream = fopen($absolute, 'r');
        Storage::disk('source')->put($destPath, $stream);
        if (is_resource($stream)) fclose($stream);

        if ($movie->hls_master_path) {
            Storage::disk('hls')->deleteDirectory('movie/' . $movie->id);
        }

        $movie->forceFill([
            'source_path' => $destPath,
            'hls_master_path' => null,
            'transcode_status' => 'queued',
            'transcode_error' => null,
        ])->save();

        TranscodeVideoJob::dispatch('movie', $movie->id);
    }

    /**
     * Turn a web-relative path (e.g. "/Jambo/storage/media/movies/file.mp4")
     * into an absolute filesystem path by anchoring it to the document root.
     */
    private function resolveLocalPath(string $url): ?string
    {
        $parsed = urldecode(parse_url($url, PHP_URL_PATH) ?: $url);

        // Strip the app base prefix so we get a path relative to public/.
        $base = parse_url(config('app.url'), PHP_URL_PATH) ?: '';
        $base = rtrim($base, '/');
        if ($base !== '' && str_starts_with($parsed, $base)) {
            $parsed = substr($parsed, strlen($base));
        }

        $absolute = public_path(ltrim($parsed, '/'));

        // Basic safety: block directory traversal.
        $real = realpath($absolute);
        if (!$real || !str_starts_with($real, realpath(public_path()))) {
            return null;
        }

        return $real;
    }

    /**
     * When a Dropbox URL is provided, queue a download-then-transcode job
     * so the file gets pulled to the source disk and encoded into HLS.
     */
    private function handleDropboxTranscode(Request $request, Movie $movie): void
    {
        if ($request->hasFile('video_file')) return;
        if (($request->input('video_source') ?? '') !== 'dropbox') return;

        $dropbox = trim((string) $request->input('dropbox_path'));
        if ($dropbox === '' || !str_starts_with($dropbox, 'http')) return;

        // Don't re-download if already processed or in progress.
        if ($movie->transcode_status === 'ready' && $movie->dropbox_path === $dropbox) return;
        if (in_array($movie->transcode_status, ['queued', 'downloading', 'transcoding'])) return;

        // Normalise to a direct-download URL.
        $downloadUrl = $this->normaliseDropboxUrl($dropbox);

        $movie->forceFill([
            'transcode_status' => 'queued',
            'transcode_error'  => null,
            'hls_master_path'  => null,
        ])->save();

        DownloadAndTranscodeJob::dispatch('movie', $movie->id, $downloadUrl);
    }

    /**
     * Convert a Dropbox share URL to a direct-download URL.
     */
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

    public function destroy(Movie $movie): RedirectResponse
    {
        $title = $movie->title;
        $movie->delete();

        return redirect()
            ->route('admin.movies.index')
            ->with('success', "Deleted \"$title\".");
    }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: Str::random(8);
        $slug = $base;
        $i = 2;

        while (Movie::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "$base-$i";
            $i++;
        }

        return $slug;
    }

    /**
     * Sync genres, categories, tags, and the cast pivot in one place.
     */
    private function syncRelationships(Movie $movie, array $data): void
    {
        $movie->genres()->sync($data['genre_ids'] ?? []);
        $movie->vjs()->sync($data['vj_ids'] ?? []);
        $movie->categories()->sync($data['category_ids'] ?? []);
        $movie->tags()->sync($data['tag_ids'] ?? []);

        // Cast has a composite pivot (movie_id, person_id, role) with
        // extra columns, so we rebuild it from scratch rather than use
        // sync().
        $movie->cast()->detach();

        foreach ($data['cast'] ?? [] as $row) {
            if (empty($row['person_id']) || empty($row['role'])) {
                continue;
            }
            $movie->cast()->attach($row['person_id'], [
                'role' => $row['role'],
                'character_name' => $row['character_name'] ?? null,
                'display_order' => (int) ($row['display_order'] ?? 0),
            ]);
        }
    }
}
