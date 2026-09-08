<?php

namespace Modules\Content\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\app\Http\Resources\EpisodeResource;
use Modules\Content\app\Http\Resources\MovieResource;
use Modules\Content\app\Http\Resources\ShowResource;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;

/**
 * Browsing: what the app lists, searches and opens a detail page for.
 *
 * Public on purpose. Browsing a catalogue is marketing, and the website is
 * open too — the gate is on playback, where PlaybackAuthorizer applies it.
 * Nothing here reveals more than the public website already does, and the
 * resources are allow-listed so no file URL can leave through a listing.
 *
 * Lists are cursor-paginated. A catalogue grows at the front (newest first),
 * and offset pagination on a growing list silently skips and repeats rows as
 * a viewer scrolls, which on a phone reads as "the app lost my place".
 */
class CatalogueController extends Controller
{
    /** Deliberately small: these are posters over a mobile connection. */
    private const PER_PAGE = 24;
    private const MAX_PER_PAGE = 60;

    public function movies(Request $request): JsonResponse
    {
        $movies = Movie::published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->cursorPaginate($this->perPage($request));

        return $this->paginated($movies, MovieResource::class);
    }

    public function movie(Request $request, string $slug): JsonResponse
    {
        $movie = Movie::where('slug', $slug)
            ->detailVisible()
            ->with(['genres', 'categories', 'cast'])
            ->first();

        if (! $movie) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That title does not exist.');
        }

        return ApiResponse::ok([
            'movie' => MovieResource::detail($movie)->toArray($request),
            // Whether it is watchable YET, which is not the same as whether
            // this viewer may watch it. An upcoming title shows a date, not a
            // subscribe button.
            'is_released' => $movie->isPubliclyVisible(),
        ]);
    }

    public function series(Request $request): JsonResponse
    {
        $shows = Show::published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->cursorPaginate($this->perPage($request));

        return $this->paginated($shows, ShowResource::class);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $show = Show::where('slug', $slug)
            ->detailVisible()
            ->with([
                'genres', 'categories', 'cast',
                'seasons' => fn ($q) => $q->orderBy('number'),
                // season.show is eager loaded so EpisodeResource can resolve
                // each episode's inherited tier without a query per episode.
                'seasons.episodes' => fn ($q) => $q->orderBy('number'),
                'seasons.episodes.season.show',
            ])
            ->first();

        if (! $show) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That series does not exist.');
        }

        return ApiResponse::ok([
            'series' => ShowResource::detail($show)->toArray($request),
            'is_released' => $show->isPubliclyVisible(),
        ]);
    }

    public function episode(Request $request, int $id): JsonResponse
    {
        $episode = Episode::with('season.show')->find($id);

        if (! $episode) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That episode does not exist.');
        }

        return ApiResponse::ok([
            'episode' => (new EpisodeResource($episode))->toArray($request),
            'series' => ShowResource::card($episode->season?->show ?? $episode->show)->toArray($request),
        ]);
    }

    /**
     * Search across movies and series.
     *
     * Two short queries rather than one union: the tables have different
     * shapes, the app renders them in separate sections anyway, and a union
     * would need a sort key neither table shares.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        // Matching the website: under two characters returns an empty result
        // rather than an error, so a viewer mid-typing sees nothing rather
        // than a red message.
        if (mb_strlen($query) < 2) {
            return ApiResponse::ok(['query' => $query, 'movies' => [], 'series' => []]);
        }

        $like = '%' . $query . '%';

        return ApiResponse::ok([
            'query' => $query,
            'movies' => MovieResource::collection(
                Movie::published()->where('title', 'like', $like)->limit(20)->get()
            )->toArray($request),
            'series' => ShowResource::collection(
                Show::published()->where('title', 'like', $like)->limit(20)->get()
            )->toArray($request),
        ]);
    }

    // ── internals ────────────────────────────────────────────────────

    private function perPage(Request $request): int
    {
        $requested = (int) $request->query('per_page', self::PER_PAGE);

        return max(1, min($requested, self::MAX_PER_PAGE));
    }

    /**
     * One pagination envelope for every list, so the app writes its infinite
     * scroll once. `next_cursor` is null at the end of the list.
     */
    private function paginated($paginator, string $resourceClass): JsonResponse
    {
        return ApiResponse::ok([
            'items' => $paginator->getCollection()
                ->map(fn ($model) => $resourceClass::card($model)->toArray(request()))
                ->values(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'per_page' => $paginator->perPage(),
        ]);
    }
}
