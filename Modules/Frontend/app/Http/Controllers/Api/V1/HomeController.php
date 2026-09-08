<?php

namespace Modules\Frontend\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Content\app\Http\Resources\MovieResource;
use Modules\Content\app\Http\Resources\ShowResource;
use Modules\Content\app\Models\Show;
use Modules\Frontend\app\Services\HomeRailsService;
use Modules\Frontend\app\Services\RailArchiveCatalog;

/**
 * The app's home screen.
 *
 * Calls HomeRailsService — the same service the website's view composer calls
 * — so the rails, their order and the rules that pick them are the website's,
 * not a second implementation that drifts the first time an admin reorders a
 * category. Section headings are resolved from the same `sectionTitle`
 * translation keys the blades render, for the same reason.
 *
 * Public. Signing in changes what comes back rather than whether it does:
 * Continue Watching appears, and the personalised rails become personal.
 * A guest gets a full, useful home screen, which is what the website does.
 *
 * The response is one ordered list of rails rather than a fixed object, so the
 * app renders whatever it is given and a rail added here does not need an app
 * release. Empty rails are dropped — a heading over nothing is worse than no
 * heading.
 */
class HomeController extends Controller
{
    public function __construct(private readonly HomeRailsService $rails)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $data = $this->rails->forWeb();

        return ApiResponse::ok([
            'hero' => $this->cards($data['heroItems'] ?? collect(), $request),
            'rails' => array_values(array_filter([
                $this->progressRail('continue_watching', __('sectionTitle.continue_watching'), $data['continueWatching'] ?? collect()),
                $this->titleRail('top_movies', __('sectionTitle.movies_to_watch'), $data['topMovies'] ?? collect(), $request),
                $this->titleRail('top_series', __('sectionTitle.best_in_tv'), $data['topShows'] ?? collect(), $request),
                $this->titleRail('top_picks', __('sectionTitle.top_picks'), $data['topPicks'] ?? collect(), $request),
                $this->titleRail('smart_shuffle', __('sectionTitle.smart_shuffle'), $data['recommendedMovies'] ?? collect(), $request),
                $this->titleRail('latest_movies', __('sectionTitle.latest_movies'), $data['latestMovies'] ?? collect(), $request),
                $this->titleRail('latest_series', __('sectionTitle.latest_series'), $data['latestShows'] ?? collect(), $request),
                $this->titleRail('fresh_picks', __('sectionTitle.fresh_picks'), $data['freshMovies'] ?? collect(), $request),
                $this->titleRail('exclusives', __('sectionTitle.only_on_streamit'), $data['exclusiveMovies'] ?? collect(), $request),
                $this->titleRail('popular_movies', __('sectionTitle.popular_movies'), $data['popularMovies'] ?? collect(), $request),
                $this->titleRail('international_series', __('sectionTitle.international_shows'), $data['internationalShows'] ?? collect(), $request),
                $this->titleRail('upcoming', __('widgets.Upcoming'), $data['upcomingMovies'] ?? collect(), $request),
                ...$this->categoryRails($data, $request),
                $this->genreRail($data['homeGenres'] ?? collect()),
                $this->vjRail($data['homeVjs'] ?? collect()),
                $this->personRail($data['favoritePersonalities'] ?? collect()),
            ])),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * "See all" behind a home rail.
     *
     * Uses RailArchiveCatalog, which the website's /collection/{rail} page
     * calls too, so the two lists cannot diverge. Paginated because an archive
     * is the whole rail rather than its first ten.
     *
     * NOTE: the personalised rails (top-picks, smart-shuffle, fresh-picks)
     * order with MySQL's FIELD(). That is fine on MariaDB in production and
     * cannot execute on SQLite, which is why the suite covers the portable
     * rails only.
     */
    public function collection(Request $request, string $rail, RailArchiveCatalog $catalog): JsonResponse
    {
        if (! $catalog->has($rail)) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That collection does not exist.');
        }

        $perPage = max(1, min((int) $request->query('per_page', 24), 60));
        $page = $catalog->query($rail)->cursorPaginate($perPage);

        return ApiResponse::ok([
            'key' => $rail,
            'title' => $catalog->title($rail),
            'items' => $this->cards($page->getCollection(), $request),
            'next_cursor' => $page->nextCursor()?->encode(),
        ]);
    }

    /** Which "see all" collections exist. */
    public function collections(RailArchiveCatalog $catalog): JsonResponse
    {
        return ApiResponse::ok([
            'collections' => collect($catalog->keys())->map(fn (string $key) => [
                'key' => $key,
                'title' => $catalog->title($key),
                'type' => $catalog->type($key),
            ])->values(),
        ]);
    }

    // ── rail builders ────────────────────────────────────────────────

    /**
     * A rail of posters. `kind` tells the app which card component to use, so
     * a new rail of an existing kind needs no app change at all.
     */
    private function titleRail(string $key, string $title, Collection $items, Request $request): ?array
    {
        $cards = $this->cards($items, $request);

        return $cards === [] ? null : [
            'key' => $key,
            'title' => $title,
            'kind' => 'titles',
            'items' => $cards,
        ];
    }

    /**
     * Continue Watching. Not a poster rail: each card carries progress and,
     * crucially, what to resume.
     *
     * `resume` is the episode for a series, while `remove` is the show —
     * they are deliberately different. Removing one episode would let the
     * show re-surface on the next render, so removal is by show; resuming a
     * show is meaningless, so resuming is by episode.
     */
    private function progressRail(string $key, string $title, Collection $cards): ?array
    {
        if ($cards->isEmpty()) {
            return null;
        }

        return [
            'key' => $key,
            'title' => $title,
            'kind' => 'progress',
            'items' => $cards->map(fn ($card) => [
                'title' => $card->title,
                'subtitle' => $card->subtitle,
                'image_url' => media_url($card->imagePath),
                'progress_percent' => $card->progressPercent,
                'minutes_left' => $card->minutesLeft,
                'position_seconds' => $card->positionSeconds,
                'resume' => ['type' => $card->resumeType, 'id' => $card->resumeId],
                'remove' => ['type' => $card->removeType, 'id' => $card->removeId],
            ])->values(),
        ];
    }

    /**
     * The admin's homepage category shelves, in the drag order they set. The
     * fixed shelf comes first, then the next three, exactly as the website
     * lays them out.
     *
     * @return array<int, array|null>
     */
    private function categoryRails(array $data, Request $request): array
    {
        $shelves = collect($data['homeCategories'] ?? [])
            ->concat($data['randomHomeCategories'] ?? []);

        return $shelves->map(fn ($category) => $this->titleRail(
            'category:' . $category->slug,
            $category->name,
            collect($category->railItems ?? []),
            $request,
        ))->all();
    }

    private function genreRail(Collection $genres): ?array
    {
        if ($genres->isEmpty()) {
            return null;
        }

        return [
            'key' => 'genres',
            'title' => __('streamTag.genre'),
            'kind' => 'genres',
            'items' => $genres->map(fn ($genre) => [
                'slug' => $genre->slug,
                'name' => $genre->name,
                'colour' => $genre->colour,
                'movies_count' => $genre->movies_count ?? null,
                'shows_count' => $genre->shows_count ?? null,
            ])->values(),
        ];
    }

    private function vjRail(Collection $vjs): ?array
    {
        if ($vjs->isEmpty()) {
            return null;
        }

        return [
            'key' => 'vjs',
            'title' => __('streamTag.vjs'),
            'kind' => 'vjs',
            'items' => $vjs->map(fn ($vj) => [
                'slug' => $vj->slug,
                'name' => $vj->name,
                // featured_image_url falls back through the VJ's most recent
                // published title, so a VJ with no photo still gets a card.
                'image_url' => media_url($vj->featured_image_url ?? $vj->photo_url),
                'movies_count' => $vj->movies_count ?? null,
                'shows_count' => $vj->shows_count ?? null,
            ])->values(),
        ];
    }

    private function personRail(Collection $people): ?array
    {
        if ($people->isEmpty()) {
            return null;
        }

        return [
            'key' => 'personalities',
            'title' => __('sectionTitle.your_favourite_personality'),
            'kind' => 'people',
            'items' => $people->map(fn ($person) => [
                'slug' => $person->slug,
                'name' => $person->full_name,
                'image_url' => media_url($person->photo_url),
            ])->values(),
        ];
    }

    /**
     * Map a mixed collection of movies and shows to cards.
     *
     * The rails carry both, tagged `_isShow` by the service so the blades can
     * branch without an instanceof. Here the model class is authoritative and
     * the flag is the fallback, because a curated hero row builds its items a
     * different way and only the flag is guaranteed on both paths.
     *
     * @return array<int, array<string, mixed>>
     */
    private function cards(Collection $items, Request $request): array
    {
        return $items->map(function ($item) use ($request) {
            $isShow = $item instanceof Show || ($item->_isShow ?? false);

            return $isShow
                ? ShowResource::card($item)->toArray($request)
                : MovieResource::card($item)->toArray($request);
        })->values()->all();
    }
}
