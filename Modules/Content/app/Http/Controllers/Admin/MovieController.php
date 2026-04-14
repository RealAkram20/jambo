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
                'published' => Movie::where('status', 'published')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('content::admin.movies.create', [
            'movie' => new Movie(['status' => 'draft', 'year' => now()->year]),
            'genres' => Genre::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'persons' => Person::orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function store(StoreMovieRequest $request): RedirectResponse
    {
        $movie = DB::transaction(function () use ($request) {
            $data = $request->validated();

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
                'dropbox_path' => $data['dropbox_path'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'tier_required' => $data['tier_required'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'published_at' => ($data['status'] ?? 'draft') === 'published' ? now() : null,
            ]);

            $this->syncRelationships($movie, $data);

            return $movie;
        });

        return redirect()
            ->route('admin.movies.edit', $movie)
            ->with('success', "Movie \"{$movie->title}\" created.");
    }

    public function edit(Movie $movie): View
    {
        $movie->load(['genres', 'categories', 'tags', 'cast']);

        return view('content::admin.movies.edit', [
            'movie' => $movie,
            'genres' => Genre::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'persons' => Person::orderBy('last_name')->orderBy('first_name')->get(),
            'currentGenreIds' => $movie->genres->pluck('id')->toArray(),
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
        DB::transaction(function () use ($request, $movie) {
            $data = $request->validated();

            $movie->fill([
                'title' => $data['title'],
                'synopsis' => $data['synopsis'] ?? null,
                'year' => $data['year'] ?? null,
                'runtime_minutes' => $data['runtime_minutes'] ?? null,
                'rating' => $data['rating'] ?? null,
                'poster_url' => $data['poster_url'] ?? null,
                'backdrop_url' => $data['backdrop_url'] ?? null,
                'trailer_url' => $data['trailer_url'] ?? null,
                'dropbox_path' => $data['dropbox_path'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'tier_required' => $data['tier_required'] ?? null,
            ]);

            // Only re-slug if title actually changed.
            if ($movie->isDirty('title')) {
                $movie->slug = $this->uniqueSlug($data['title'], $movie->id);
            }

            // Status transitions: draft → published stamps published_at.
            if (($data['status'] ?? $movie->status) !== $movie->status) {
                $movie->status = $data['status'];
                if ($data['status'] === 'published' && !$movie->published_at) {
                    $movie->published_at = now();
                }
            }

            $movie->save();

            $this->syncRelationships($movie, $data);
        });

        return redirect()
            ->route('admin.movies.edit', $movie)
            ->with('success', 'Movie saved.');
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
