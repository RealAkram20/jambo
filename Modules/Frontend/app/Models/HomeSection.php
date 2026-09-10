<?php

namespace Modules\Frontend\app\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * One section of the home screen, and where an admin has put it.
 *
 * The app renders whatever ordered list of rails `/api/v1/home` hands it, so
 * arranging the homepage is entirely a server-side concern: this table is the
 * arrangement, and no app release is involved in changing it.
 *
 * 🔴 **`key` is a foreign key into a published contract.** The app keys the
 * Top 10 numerals and the "View all" destinations on these literal strings in
 * `mobile/src/api/catalogue.ts`. Adding a key is free; renaming one is a
 * breaking change that fails silently. The migration's docblock has the full
 * warning and it is the one worth reading before editing this list.
 *
 * Two rules keep an admin from being able to break the homepage:
 *
 *   1. **An unknown rail sorts last, it is never dropped.** A rail added in
 *      code before its row exists still appears, at the bottom. Dropping it
 *      would mean a deploy order silently loses a section.
 *   2. **No table, or an empty one, means today's order.** If the migration
 *      has not run yet, or the seed has not, or somebody truncates the table,
 *      the home screen keeps the order the controller builds rather than
 *      going blank or 500ing. The literal array in `HomeController::show()`
 *      is the correct default, so falling back to it is returning the input
 *      unchanged.
 *
 *      This is not defensive decoration. Shipping without it took
 *      `GET /api/v1/home` down on this machine on 2026-09-09: the code landed
 *      before the migration ran and every home screen rendered a raw
 *      QueryException. A deploy that runs code before migrations must degrade,
 *      not fail — the app's home screen is the first request every device in
 *      the field makes.
 *
 * The admin screen deliberately does NOT get that fallback. It is the screen
 * that manages this table, so it should fail loudly if the table is missing
 * rather than show an admin an empty list they cannot explain.
 */
class HomeSection extends Model
{
    /**
     * The one row that stands for every `category:<slug>` rail.
     *
     * Categories are already drag-ordered on the categories screen. A second
     * control for the same order is how two orders start disagreeing, so the
     * whole block moves as one section here.
     */
    public const CATEGORY_GROUP = 'categories';

    /** Rail keys in this group are addressed by CATEGORY_GROUP. */
    private const CATEGORY_PREFIX = 'category:';

    /**
     * Every section, in the order `HomeController::show()` hard-coded before
     * this table existed, mapped to the translation key the controller uses
     * for its heading.
     *
     * This list is canonical: the migration seeds from it and the admin screen
     * names rows from it. The controller still builds its rails with literal
     * keys and literal `__()` calls — the pinning test asserts the two agree,
     * so a heading changed in one place and not the other fails loudly rather
     * than showing an admin a name the app does not use.
     *
     * @var array<string, string>
     */
    /**
     * Every section, and the translation key that names it.
     *
     * **This is the ONE place a heading key lives.** `HomeController` used to
     * repeat them as inline `__()` calls, which meant two lists that had to
     * agree and did not: three shelves had a different name on the website
     * than in the app, and a test asserting the admin screen and the API agree
     * is what caught it.
     *
     * The keys are the WEBSITE'S, because its wording is what viewers already
     * read on jambofilms.com — `sectionTitle.top_ten` is "Top 10 Movies This
     * Week", not "Movies to Watch".
     *
     * Adding a section means adding a row here and nowhere else. Use the key
     * the site already uses for that shelf rather than inventing a second one.
     */
    public const DEFAULTS = [
        'continue_watching' => 'sectionTitle.continue_watching',
        'top_movies' => 'sectionTitle.top_ten',
        'top_series' => 'sectionTitle.top_10_tvshow_to_watch',
        'top_picks' => 'sectionTitle.top_picks',
        'smart_shuffle' => 'sectionTitle.smart_shuffle',
        'latest_movies' => 'sectionTitle.latest_movies',
        'latest_series' => 'sectionTitle.latest_series',
        'fresh_picks' => 'sectionTitle.fresh_picks',
        'exclusives' => 'sectionTitle.only_on_streamit',
        'popular_movies' => 'sectionTitle.popular_movies',
        'international_series' => 'sectionTitle.international_shows',
        'upcoming' => 'sectionTitle.upcoming_title',
        // The Top 10 Movies of the Day banner sits here, and the Top 10 Series
        // of the Day banner at the very end, because that is where `ott-page`
        // puts them: the movie slider between Upcoming and the personality
        // rail, the series slider after it. This list is what a fresh install
        // seeds from, so its order is the home page a new deployment gets.
        //
        // They are also the only two sections whose heading the app never
        // draws — a full-bleed banner carries no shelf title on the website
        // either — so their strings exist for the screen that arranges them.
        'top_movies_today' => 'sectionTitle.top_movies_today',
        self::CATEGORY_GROUP => 'sidebar.categories',
        'genres' => 'streamTag.genre',
        'vjs' => 'streamTag.vjs',
        'personalities' => 'sectionTitle.your_favourite_personality',
        'top_series_today' => 'sectionTitle.top_series_today',
    ];

    /**
     * How the WEBSITE draws each section.
     *
     * The app is handed an ordered list of rails and draws them itself; the
     * website is Blade, so the arrangement has to name a partial. This is that
     * mapping, and it sits beside DEFAULTS on purpose — **a section is one row
     * in two constants in one class, and nowhere else.** `HomeSectionCatalogTest`
     * asserts the two key sets are identical, so a section added to one and
     * forgotten in the other fails rather than silently missing a surface.
     *
     *   view    the partial under `frontend::components.sections`
     *   data    the shared collection(s) the partial reads. An empty one drops
     *           the section, which is the rule `/api/v1/home` already follows:
     *           a heading over nothing is worse than no heading.
     *   with    extra parameters the partial expects
     *   routes  parameters that are a URL, resolved at render (a const cannot
     *           call route())
     *   bleed   true for a full-width section that must sit OUTSIDE the
     *           container, which is how the two daily banners are drawn
     *
     * @var array<string, array<string, mixed>>
     */
    public const WEB_VIEWS = [
        'continue_watching' => [
            'view' => 'continue-watching',
            'data' => 'continueWatching',
            'with' => ['value' => '6', 'sectionPaddingClass' => true],
        ],
        'top_movies' => ['view' => 'top-ten-block', 'data' => 'topMovies'],
        'top_series' => ['view' => 'top-ten-tvshow', 'data' => 'topShows'],
        'top_picks' => ['view' => 'top-pict', 'data' => 'topPicks'],
        'smart_shuffle' => [
            'view' => 'recommended',
            'data' => 'recommendedMovies',
            'with' => ['viewAllBtn' => true],
            'routes' => ['viewAllRoute' => ['frontend.rail_archive', 'smart-shuffle']],
        ],
        'latest_movies' => ['view' => 'latest-movies', 'data' => 'latestMovies', 'with' => ['viewAllBtn' => true]],
        'latest_series' => ['view' => 'latest-series', 'data' => 'latestShows', 'with' => ['viewAllBtn' => true]],
        'fresh_picks' => ['view' => 'fresh-picks-just-for-you', 'data' => 'freshMovies'],
        'exclusives' => ['view' => 'only-on-streamit', 'data' => 'exclusiveMovies'],
        'popular_movies' => ['view' => 'Popular-movies', 'data' => 'popularMovies', 'with' => ['viewAllBtn' => true]],
        'international_series' => [
            'view' => 'best-of-international-shows',
            'data' => 'internationalShows',
            'with' => ['viewAllBtn' => true],
        ],
        // `upcomingItems` is not in HomeRailsService::forWeb() — the route
        // action passes it, so the home page hands it to the renderer as
        // extra data. See ott-page.blade.php.
        'upcoming' => ['view' => 'upcomming', 'data' => 'upcomingItems', 'with' => ['viewAllBtn' => true]],
        'top_movies_today' => ['view' => 'verticle-slider', 'data' => 'verticalFeatured', 'bleed' => true],
        // Every Visible Home category shelf, as one block. The website used to
        // scatter three of them at fixed slots standing in for rails that had
        // been retired; those rails are back, so the stand-ins are gone and
        // the block moves as one row, matching what `/api/v1/home` sends.
        self::CATEGORY_GROUP => [
            'view' => 'category-rails',
            'data' => 'homeCategories',
        ],
        'genres' => ['view' => 'geners', 'data' => 'homeGenres'],
        'vjs' => ['view' => 'vjs', 'data' => 'homeVjs'],
        'personalities' => ['view' => 'Your-Favourite-Personality', 'data' => 'favoritePersonalities'],
        'top_series_today' => ['view' => 'tab-slider', 'data' => 'tabSeries', 'bleed' => true],
    ];

    protected $fillable = ['key', 'label', 'position', 'enabled'];

    protected $casts = [
        'position' => 'integer',
        'enabled' => 'boolean',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /**
     * Which section row governs a rail.
     *
     * Every `category:<slug>` rail answers to the one grouped row, so an admin
     * moves the category block rather than sixteen individual shelves.
     */
    public static function sectionKeyFor(string $railKey): string
    {
        return str_starts_with($railKey, self::CATEGORY_PREFIX)
            ? self::CATEGORY_GROUP
            : $railKey;
    }

    /** The heading a section carries when no admin has overridden it. */
    public static function defaultTitle(string $key): string
    {
        $translationKey = self::DEFAULTS[$key] ?? null;

        return $translationKey ? (string) __($translationKey) : $key;
    }

    /**
     * Order and filter the rails the controller built.
     *
     * One query of DEFAULTS-many rows — sixteen today, and it stays that size
     * because categories collapse into one row rather than one row each. If
     * this list ever reaches the hundreds it wants caching; at sixteen a cache
     * would only make an admin's drag take a TTL to appear.
     *
     * @param  array<int, array<string, mixed>|null>  $rails
     * @return array<int, array<string, mixed>>
     */
    /**
     * The heading for a section key.
     *
     * Falls back to the key itself rather than to an empty string: a shelf
     * labelled `top_movies` is obviously wrong and gets fixed, whereas a shelf
     * with no heading at all looks like a rendering glitch and gets lived with.
     */
    public static function headingFor(string $key): string
    {
        $translationKey = self::DEFAULTS[$key] ?? null;

        return $translationKey === null ? $key : (string) __($translationKey);
    }

    public static function arrange(array $rails): array
    {
        $rails = array_values(array_filter($rails));

        if ($rails === []) {
            return [];
        }

        try {
            $rows = static::query()->get()->keyBy('key');
        } catch (QueryException $e) {
            // 🔴 The table is not there yet. This is the deploy window between
            // code landing and migrations running, and the home screen is the
            // first thing every device in the field asks for — so it degrades
            // to the order the controller passed in rather than 500ing.
            //
            // The catch is this narrow on purpose. By the time arrange() runs,
            // the same connection has already served the entire catalogue that
            // built these rails, so a query failing here is a missing table,
            // not a database that is down. It also costs nothing on the happy
            // path, which a Schema::hasTable() probe on every request would
            // not: this is the hottest endpoint in the product.
            //
            // Logged at warning on every request through the window, and that
            // is deliberate — a deploy that left the migration unrun should be
            // loud in the log even while the app carries on working.
            Log::warning('home_sections is unreadable; serving the built-in home order.', [
                'exception' => $e->getMessage(),
            ]);

            return $rails;
        }

        // Nothing stored: the controller's own order stands. A missing seed
        // must not blank the home screen.
        if ($rows->isEmpty()) {
            return $rails;
        }

        $sortable = [];

        foreach ($rails as $index => $rail) {
            $key = (string) ($rail['key'] ?? '');
            $row = $rows->get(static::sectionKeyFor($key));

            // Switched off by an admin. The rail is not built differently, it
            // simply does not reach the app.
            if ($row && ! $row->enabled) {
                continue;
            }

            // An override renames one shelf. It is deliberately not applied
            // through the grouped category row, which stands for many rails
            // that each carry their own category name.
            //
            // It renames a heading; it never adds one. The two banner rails
            // send no title because neither surface draws one over a
            // full-width slide, and an admin's label on that row is for the
            // admin screen. Writing it in here would hand the app a heading it
            // has no place to put.
            if ($row && $row->label !== null && $row->key === $key && array_key_exists('title', $rail)) {
                $rail['title'] = $row->label;
            }

            $sortable[] = [
                // 0 before 1: a rail with no row sorts after every rail that
                // has one, and is never dropped.
                'placed' => $row ? 0 : 1,
                'position' => $row?->position ?? 0,
                // Ties keep the order the controller built them in, which is
                // what holds the category shelves in their own drag order.
                'index' => $index,
                'rail' => $rail,
            ];
        }

        usort(
            $sortable,
            fn (array $a, array $b) => [$a['placed'], $a['position'], $a['index']]
                <=> [$b['placed'], $b['position'], $b['index']]
        );

        return array_column($sortable, 'rail');
    }

    /**
     * The sections the website should draw, in order, already filtered.
     *
     * Degrades exactly as `arrange()` does, and for the same reason: the home
     * page must survive the window between code landing and migrations
     * running. No table or no rows means the built-in order, every section on.
     *
     * @return Collection<int, static>
     */
    public static function forWebRender(): Collection
    {
        try {
            $rows = static::ordered()->get();
        } catch (QueryException $e) {
            Log::warning('home_sections is unreadable; serving the built-in home order.', [
                'exception' => $e->getMessage(),
            ]);

            $rows = collect();
        }

        if ($rows->isEmpty()) {
            $rows = collect(array_keys(self::DEFAULTS))
                ->map(fn (string $key) => new static(['key' => $key, 'enabled' => true]));
        }

        return $rows->filter(fn (self $row) => $row->enabled)->values();
    }

    /**
     * The home page, as runs of partials to include.
     *
     * Consecutive sections that live inside the page container are grouped so
     * the container is opened once around each run, and a full-width section
     * breaks the run rather than being wrapped. That grouping is why this
     * returns runs and not a flat list: an admin can drag a banner anywhere,
     * so where the container opens and closes is not knowable at authoring
     * time the way it was when the order was hard-coded.
     *
     * @param  array<string, mixed>  $data  the shared collections the partials read
     * @return array<int, array{bleed: bool, steps: array<int, array{view: string, with: array<string, mixed>}>}>
     */
    public static function webPlan(array $data): array
    {
        $groups = [];

        foreach (static::forWebRender() as $row) {
            $spec = self::WEB_VIEWS[$row->key] ?? null;

            // A section the app can render and the website cannot yet. It is
            // skipped here rather than treated as an error: adding a rail
            // server-side must stay a one-line change.
            if ($spec === null || ! self::hasContent($spec['data'] ?? null, $data)) {
                continue;
            }

            $with = $spec['with'] ?? [];

            foreach ($spec['routes'] ?? [] as $parameter => $route) {
                $with[$parameter] = route(...$route);
            }

            // The admin's label reaches the website the same way it reaches
            // the app. Partials fall back to their own translation key, so
            // every other page that includes them is unaffected.
            $with['sectionHeading'] = $row->displayTitle();

            $bleed = (bool) ($spec['bleed'] ?? false);
            $step = ['view' => 'frontend::components.sections.'.$spec['view'], 'with' => $with];
            $last = array_key_last($groups);

            if ($last !== null && $groups[$last]['bleed'] === $bleed) {
                $groups[$last]['steps'][] = $step;

                continue;
            }

            $groups[] = ['bleed' => $bleed, 'steps' => [$step]];
        }

        return $groups;
    }

    /**
     * Does this section have anything to show?
     *
     * A key absent from the shared data counts as empty. That is deliberate:
     * a partial whose collection was never wired would otherwise draw a
     * heading over nothing, which is the failure the website's Upcoming
     * section spent months in.
     *
     * @param  string|array<int, string>|null  $keys
     * @param  array<string, mixed>  $data
     */
    private static function hasContent(string|array|null $keys, array $data): bool
    {
        if ($keys === null) {
            return true;
        }

        foreach ((array) $keys as $key) {
            $value = $data[$key] ?? null;

            if (is_countable($value) ? count($value) > 0 : $value !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Insert a row for any canonical section that has none, at the end.
     *
     * Called when the admin screen loads, so a rail added in code appears on
     * the screen that arranges it without needing its own migration. Existing
     * rows are never touched: an admin's arrangement survives a deploy.
     */
    public static function seedDefaults(): int
    {
        $existing = static::query()->pluck('key')->flip();

        // A fresh table starts at 0; an existing one appends after its last
        // row so nothing an admin already arranged is displaced.
        $next = $existing->isEmpty() ? 0 : ((int) static::query()->max('position')) + 1;
        $created = 0;

        foreach (array_keys(self::DEFAULTS) as $key) {
            if ($existing->has($key)) {
                continue;
            }

            static::query()->create([
                'key' => $key,
                'position' => $next++,
                'enabled' => true,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * The rows the admin screen shows: every section in display order,
     * including ones an admin has switched off, which are labelled rather
     * than hidden. Hiding a disabled section would leave nobody able to work
     * out why a shelf is missing from the app.
     */
    public static function forAdmin(): Collection
    {
        return static::ordered()->get();
    }

    /** The heading this section actually renders with. */
    public function displayTitle(): string
    {
        return $this->label ?? static::defaultTitle($this->key);
    }
}
