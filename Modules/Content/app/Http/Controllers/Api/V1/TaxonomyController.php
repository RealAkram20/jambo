<?php

namespace Modules\Content\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\app\Http\Resources\MovieResource;
use Modules\Content\app\Http\Resources\ShowResource;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Tag;
use Modules\Content\app\Models\Vj;

/**
 * The screens behind the home screen's non-poster rails.
 *
 * `GET /home` returns genre, VJ and personality cards; without these endpoints
 * tapping one has nowhere to go. Four taxonomies, one controller, because they
 * are the same shape: a named thing with `movies()` and `shows()`. Writing
 * four near-identical controllers would be four places to fix the next time
 * the published-only rule changes.
 *
 * Public, like the website's own archive pages. Only published titles are
 * listed — the `published()` scopes carry the release rules, including that a
 * series needs at least one published episode before it counts as public.
 */
class TaxonomyController extends Controller
{
    private const DETAIL_LIMIT = 50;

    // ── genres ───────────────────────────────────────────────────────

    public function genres(Request $request): JsonResponse
    {
        $genres = Genre::withCount(['movies', 'shows'])
            ->orderByDesc('movies_count')
            ->get()
            ->map(fn (Genre $genre) => [
                'slug' => $genre->slug,
                'name' => $genre->name,
                'colour' => $genre->colour,
                'movies_count' => $genre->movies_count,
                'shows_count' => $genre->shows_count,
            ]);

        return ApiResponse::ok(['genres' => $genres]);
    }

    public function genre(Request $request, string $slug): JsonResponse
    {
        $genre = Genre::where('slug', $slug)->first();

        return $this->detail($request, $genre, 'genre', fn () => [
            'slug' => $genre->slug,
            'name' => $genre->name,
            'colour' => $genre->colour,
        ]);
    }

    // ── categories ───────────────────────────────────────────────────

    public function categories(Request $request): JsonResponse
    {
        $categories = Category::orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'slug' => $category->slug,
                'name' => $category->name,
                'description' => $category->description,
                'cover_url' => media_url($category->cover_url),
                // Whether the admin put it on the homepage. The app uses the
                // same flag to decide what belongs in a "browse" grid.
                'visible_home' => (bool) $category->visible_home,
            ]);

        return ApiResponse::ok(['categories' => $categories]);
    }

    public function category(Request $request, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)->first();

        return $this->detail($request, $category, 'category', fn () => [
            'slug' => $category->slug,
            'name' => $category->name,
            'description' => $category->description,
            'cover_url' => media_url($category->cover_url),
        ]);
    }

    // ── VJs ──────────────────────────────────────────────────────────

    /**
     * VJs are Jambo's own idea, not Streamit's: the narrator who voices a
     * title. Only those with something published are listed, or the app would
     * show a VJ whose whole catalogue is still draft.
     */
    public function vjs(Request $request): JsonResponse
    {
        $vjs = Vj::query()
            ->where(function ($q) {
                $q->whereHas('movies', fn ($mq) => $mq->published())
                  ->orWhereHas('shows', fn ($sq) => $sq->published());
            })
            ->withCount([
                'movies as movies_count' => fn ($q) => $q->published(),
                'shows as shows_count' => fn ($q) => $q->published(),
            ])
            ->orderByRaw('(movies_count + shows_count) DESC')
            ->orderBy('id')
            ->get()
            ->map(fn (Vj $vj) => [
                'slug' => $vj->slug,
                'name' => $vj->name,
                'colour' => $vj->colour,
                'image_url' => media_url($vj->featured_image_url ?? $vj->photo_url),
                'movies_count' => $vj->movies_count,
                'shows_count' => $vj->shows_count,
            ]);

        return ApiResponse::ok(['vjs' => $vjs]);
    }

    public function vj(Request $request, string $slug): JsonResponse
    {
        $vj = Vj::where('slug', $slug)->first();

        return $this->detail($request, $vj, 'VJ', fn () => [
            'slug' => $vj->slug,
            'name' => $vj->name,
            'colour' => $vj->colour,
            'description' => $vj->description,
            'image_url' => media_url($vj->featured_image_url ?? $vj->photo_url),
            'links' => array_filter([
                'youtube' => $vj->youtube_url,
                'tiktok' => $vj->tiktok_url,
                'facebook' => $vj->facebook_url,
                'instagram' => $vj->instagram_url,
                'website' => $vj->website_url,
            ]),
        ]);
    }

    // ── tags ─────────────────────────────────────────────────────────

    public function tags(Request $request): JsonResponse
    {
        $tags = Tag::withCount(['movies', 'shows'])
            ->orderByDesc('movies_count')
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag) => [
                'slug' => $tag->slug,
                'name' => $tag->name,
                'movies_count' => $tag->movies_count,
                'shows_count' => $tag->shows_count,
            ]);

        return ApiResponse::ok(['tags' => $tags]);
    }

    public function tag(Request $request, string $slug): JsonResponse
    {
        $tag = Tag::where('slug', $slug)->first();

        return $this->detail($request, $tag, 'tag', fn () => [
            'slug' => $tag->slug,
            'name' => $tag->name,
        ]);
    }

    // ── cast and crew ────────────────────────────────────────────────

    /**
     * Everyone with something published — the "all personalities" grid.
     *
     * Ordered by how much of the catalogue they are in, which is what makes
     * the grid useful rather than alphabetical.
     */
    public function people(Request $request): JsonResponse
    {
        $people = Person::query()
            ->where(function ($q) {
                $q->whereHas('movies', fn ($mq) => $mq->published())
                  ->orWhereHas('shows', fn ($sq) => $sq->published());
            })
            ->withCount([
                'movies as movies_count' => fn ($q) => $q->published(),
                'shows as shows_count' => fn ($q) => $q->published(),
            ])
            ->orderByRaw('(movies_count + shows_count) DESC')
            ->orderBy('id')
            ->cursorPaginate(40);

        return ApiResponse::ok([
            'items' => $people->getCollection()->map(fn (Person $person) => [
                'slug' => $person->slug,
                'name' => $person->full_name,
                'photo_url' => media_url($person->photo_url),
                'movies_count' => $person->movies_count,
                'shows_count' => $person->shows_count,
            ])->values(),
            'next_cursor' => $people->nextCursor()?->encode(),
        ]);
    }

    public function person(Request $request, string $slug): JsonResponse
    {
        $person = Person::where('slug', $slug)->first();

        return $this->detail($request, $person, 'person', fn () => [
            'slug' => $person->slug,
            'name' => $person->full_name,
            'bio' => $person->bio,
            'known_for' => $person->known_for,
            'photo_url' => media_url($person->photo_url),
        ]);
    }

    // ── internals ────────────────────────────────────────────────────

    /**
     * One archive page: the thing itself, plus its published movies and
     * series.
     *
     * Movies and series come back as separate lists rather than one merged
     * one, because the app renders them as separate rails and merging would
     * need a sort key the two tables do not share.
     *
     * @param  callable():array<string, mixed>  $describe
     */
    private function detail(Request $request, ?Model $subject, string $label, callable $describe): JsonResponse
    {
        if (! $subject) {
            return ApiResponse::error(ApiErrorCode::NotFound, "That {$label} does not exist.");
        }

        // Columns are qualified because these are BelongsToMany: an
        // unqualified published_at is ambiguous the moment a pivot table
        // gains a column of the same name, and that fails as a SQL error in
        // production rather than as anything a test would catch by accident.
        $movies = $subject->movies()
            ->published()
            ->with('genres')
            ->orderByDesc('movies.published_at')
            ->limit(self::DETAIL_LIMIT)
            ->get();

        $shows = $subject->shows()
            ->published()
            ->with('genres')
            ->orderByDesc('shows.published_at')
            ->limit(self::DETAIL_LIMIT)
            ->get();

        return ApiResponse::ok([
            $this->subjectKey($label) => $describe(),
            'movies' => MovieResource::collection($movies)->toArray($request),
            'series' => ShowResource::collection($shows)->toArray($request),
        ]);
    }

    private function subjectKey(string $label): string
    {
        return match ($label) {
            'VJ' => 'vj',
            default => $label,
        };
    }
}
