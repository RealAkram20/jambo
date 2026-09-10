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
use Modules\Frontend\app\Models\HomeSection;
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
 *
 * The order below is the DEFAULT, not the answer. `HomeSection::arrange()` has
 * the last word: an admin's drag order on /admin/home-sections wins, a section
 * switched off there never reaches the app, and a rail added here before its
 * row exists still appears — last, never dropped. That is why adding a rail to
 * this list is still a one-line change.
 */
class HomeController extends Controller
{
    public function __construct(private readonly HomeRailsService $rails)
    {
    }

    /**
     * The home payload.
     *
     * **The heading keys below are the WEBSITE'S keys, deliberately.** Three of
     * them used to differ, and the result was that one shelf had two names
     * depending on which surface a viewer was looking at:
     *
     *   - Top 10 movies, 7 day: the site says "Top 10 Movies This Week"
     *     (`sectionTitle.top_ten`); the API said "Movies to Watch".
     *   - Top 10 series, 7 day: the site says "Top 10 Series This Week"
     *     (`sectionTitle.top_10_tvshow_to_watch`); the API said "Best in
     *     Series This Week".
     *   - Upcoming: the site asks for `sectionTitle.upcoming_title`; the API
     *     used `widgets.Upcoming`.
     *
     * Rio spotted it from a capture of the live site and asked for it cleaned
     * before the section-arrangement work went further, because an admin
     * dragging a row called "Movies to Watch" would otherwise watch a shelf
     * called "Top 10 Movies This Week" move.
     *
     * The website's wording won because it is what viewers already read on
     * jambofilms.com. **Adding a rail here means choosing the key the site
     * already uses for that shelf, not inventing a second one.** Headings come
     * from this response, so changing one needs no app release.
     */
    public function show(Request $request): JsonResponse
    {
        $data = $this->rails->forWeb();

        return ApiResponse::ok([
            'hero' => $this->heroCards($data['heroItems'] ?? collect(), $request),
            'rails' => HomeSection::arrange([
                $this->progressRail('continue_watching', HomeSection::headingFor('continue_watching'), $data['continueWatching'] ?? collect()),
                $this->titleRail('top_movies', HomeSection::headingFor('top_movies'), $data['topMovies'] ?? collect(), $request, 'numbered'),
                $this->titleRail('top_series', HomeSection::headingFor('top_series'), $data['topShows'] ?? collect(), $request, 'numbered'),
                $this->titleRail('top_picks', HomeSection::headingFor('top_picks'), $data['topPicks'] ?? collect(), $request),
                $this->titleRail('smart_shuffle', HomeSection::headingFor('smart_shuffle'), $data['recommendedMovies'] ?? collect(), $request),
                $this->titleRail('latest_movies', HomeSection::headingFor('latest_movies'), $data['latestMovies'] ?? collect(), $request),
                $this->titleRail('latest_series', HomeSection::headingFor('latest_series'), $data['latestShows'] ?? collect(), $request),
                $this->titleRail('fresh_picks', HomeSection::headingFor('fresh_picks'), $data['freshMovies'] ?? collect(), $request),
                $this->titleRail('exclusives', HomeSection::headingFor('exclusives'), $data['exclusiveMovies'] ?? collect(), $request),
                $this->titleRail('popular_movies', HomeSection::headingFor('popular_movies'), $data['popularMovies'] ?? collect(), $request),
                $this->titleRail('international_series', HomeSection::headingFor('international_series'), $data['internationalShows'] ?? collect(), $request),
                $this->titleRail('upcoming', HomeSection::headingFor('upcoming'), $data['upcomingMovies'] ?? collect(), $request),
                $this->bannerRail('top_movies_today', 'movies_today', $data['verticalFeatured'] ?? collect(), $request),
                ...$this->categoryRails($data, $request),
                $this->genreRail($data['homeGenres'] ?? collect()),
                $this->vjRail($data['homeVjs'] ?? collect()),
                $this->personRail($data['favoritePersonalities'] ?? collect()),
                $this->bannerRail('top_series_today', 'series_today', $data['tabSeries'] ?? collect(), $request),
            ]),
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

        // Offset paging, not a cursor, and for two reasons. The pinned rails
        // order with a raw FIELD() clause, which a cursor cannot be built
        // from; and the plain rails order on created_at, which seeded and
        // bulk-imported titles share to the second — a cursor on it skipped
        // every tied row, and page 2 of latest-movies came back empty on
        // real data during review. The website pages these by offset too.
        $page = $catalog->query($rail)->paginate($perPage);

        return ApiResponse::ok([
            'key' => $rail,
            'title' => $catalog->title($rail),
            'items' => $this->cards($page->getCollection(), $request),
            'page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
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
    private function titleRail(string $key, string $title, Collection $items, Request $request, ?string $style = null): ?array
    {
        $cards = $this->cards($items, $request);

        if ($cards === []) {
            return null;
        }

        $rail = [
            'key' => $key,
            'title' => $title,
            'kind' => 'titles',
        ];

        // Only the two ranked shelves carry a style. An absent one means "draw
        // this the ordinary way", which is what every other rail wants and
        // what an older client does with a style it has never heard of.
        if ($style !== null) {
            $rail['style'] = $style;
        }

        $rail['items'] = $cards;

        return $rail;
    }

    /**
     * A full-width banner: the two Top 10 *of the day* shelves.
     *
     * **`style`, not `key`, is what tells a client how to draw a rail**, and
     * this is the field the app's `catalogue.ts` asked for in a comment before
     * it existed. The app had a hardcoded set of two keys that got the Top 10
     * numerals, which meant a third ranked shelf would have needed an app
     * release to look right — in an endpoint whose whole design is that it
     * does not. `key` stays what it always was: an opaque identifier, open-
     * ended because category shelves are `category:<slug>`.
     *
     * No `title`. The website draws no heading over either of these — a
     * banner is the width of the screen and says what it is — and sending one
     * would invite a client to draw it. The admin screen still names them,
     * from `HomeSection::DEFAULTS`, because a row you can drag needs a label.
     *
     * `kind` is `banner` rather than `titles` because the items are a
     * different shape, which is what `kind` has always meant here: a slide
     * carries a backdrop, a synopsis, a rank and whether that rank was earned.
     * An older build that does not know the kind skips the rail, which is the
     * contract's own rule and the reason this needed no version negotiation.
     */
    private function bannerRail(string $key, string $style, Collection $items, Request $request): ?array
    {
        $slides = $items->values()->map(function ($item, $index) use ($request) {
            // 1-based, and it is the position in this shelf rather than
            // anything stored: `#3 in Movies Today` is a claim about today.
            $rank = $index + 1;

            return $item instanceof Show || ($item->_isShow ?? false)
                ? ShowResource::banner($item, $rank)->toArray($request)
                : MovieResource::banner($item, $rank)->toArray($request);
        })->all();

        return $slides === [] ? null : [
            'key' => $key,
            'kind' => 'banner',
            'style' => $style,
            'items' => $slides,
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
                // The tile's artwork, borrowed from the genre's most recent
                // published title — the same `featured_image_url` the site's
                // own `card-genres-grid` draws, so the two rails show the same
                // picture for the same genre. HomeRailsService has already
                // batched it; see Genre::attachFeaturedImages.
                'image_url' => media_url($genre->featured_image_url),
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

    /**
     * The banner's slides, which are not poster cards.
     *
     * The website's banner draws a backdrop, a texture-filled headline, a
     * certification or season badge, a star row, a runtime, a synopsis and
     * three taxonomy lines — see `components/partials/hero-banner.blade.php`,
     * which is the specification for this shape. A `card()` carries none of
     * that past the title, so the app's banner was a portrait poster with a
     * year under it until this call changed.
     *
     * Only the banner pays for it. The rails below it stay on `card()`,
     * because a shelf of thirty posters carrying thirty synopses is the cost
     * the two shapes exist to avoid, and there are at most a handful of
     * slides. HomeRailsService::withHeroAggregates() has already batched the
     * two aggregates these need.
     *
     * @return array<int, array<string, mixed>>
     */
    private function heroCards(Collection $items, Request $request): array
    {
        return $items->map(function ($item) use ($request) {
            $isShow = $item instanceof Show || ($item->_isShow ?? false);

            return $isShow
                ? ShowResource::hero($item)->toArray($request)
                : MovieResource::hero($item)->toArray($request);
        })->values()->all();
    }
}
