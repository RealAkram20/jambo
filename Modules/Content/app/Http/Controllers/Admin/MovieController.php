<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Content\app\Http\Requests\StoreMovieRequest;
use Modules\Content\app\Http\Requests\UpdateMovieRequest;
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
                // Release / publish date — admin may set one for an
                // upcoming title (to surface "Releases Mar 15" in the
                // UI) or a published one (to backdate). When nothing
                // is provided, we stamp now() iff status=published so
                // the public listing can order by it.
                'published_at' => ! empty($data['published_at'])
                    ? $data['published_at']
                    : (($data['status'] ?? 'draft') === 'published' ? now() : null),
            ]);

            $this->syncRelationships($movie, $data);

            return $movie;
        });

        if ($movie->status === 'published') {
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

            // Explicit release / publish date from the form. `array_key_exists`
            // rather than `isset` so an admin clearing the date field
            // (empty value) actually nulls the column. Applied BEFORE
            // the status-transition auto-stamp so a user-supplied value
            // always wins.
            if (array_key_exists('published_at', $data)) {
                $movie->published_at = $data['published_at'] ?: null;
            }

            // Status transitions: draft → published stamps published_at
            // when the admin didn't supply their own date.
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

    public function destroy(Movie $movie): RedirectResponse
    {
        $title = $movie->title;
        $movie->delete();

        return redirect()
            ->route('admin.movies.index')
            ->with('success', "Deleted \"$title\".");
    }

    /**
     * Bulk-delete a set of movies. Iterates with each() so model
     * deleting events fire and any pivot cleanup / file cleanup runs
     * the same way it does for a single delete.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $movies = Movie::whereIn('id', $data['ids'])->get();
        $count = $movies->count();
        $movies->each(fn (Movie $m) => $m->delete());

        return redirect()
            ->route('admin.movies.index')
            ->with('success', "Deleted $count movie" . ($count === 1 ? '' : 's') . '.');
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
