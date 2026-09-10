<?php

namespace Modules\Frontend\app\View\Composers;

use Illuminate\View\View;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Frontend\app\Services\HomeRailsService;
use Modules\Streaming\app\Models\WatchlistItem;

/**
 * Shares the collections that every section-template on the public frontend
 * needs, so the home pages, movie pages and show pages can all include the
 * same section partials without wiring data at every call site.
 *
 * The rails themselves live in HomeRailsService, which `GET /api/v1/home`
 * also calls — so the app's home screen is the website's home screen by
 * construction rather than by imitation. What stays here is what is about
 * rendering a page: memoising per request, and the per-user watchlist index.
 *
 * The collections are intentionally cheap: small takes, eager-loaded genres,
 * and a single query per slot. We cache per-request so including multiple
 * sections on one page only hits the DB once.
 */
class SectionDataComposer
{
    private static ?array $cache = null;
    private static ?array $perUserCache = null;

    /**
     * The shared collections, built once per request.
     *
     * The home page's section renderer needs to ask what a section would
     * contain before deciding to draw it, and it must ask the same memoised
     * copy every partial then reads — asking HomeRailsService again would run
     * the whole homepage's queries a second time.
     *
     * @return array<string, mixed>
     */
    public static function shared(): array
    {
        return self::$cache ??= app(HomeRailsService::class)->forWeb();
    }

    public function compose(View $view): void
    {
        foreach (self::shared() as $key => $value) {
            $view->with($key, $value);
        }

        // Per-user data that must NOT live in the static public cache —
        // otherwise cards on every page would show the first logged-in
        // user's watchlist state to everyone. Rebuilt once per request.
        if (self::$perUserCache === null) {
            self::$perUserCache = $this->buildPerUser();
        }
        foreach (self::$perUserCache as $key => $value) {
            $view->with($key, $value);
        }
    }

    /**
     * Lightweight lookup set so every card on the page can render its
     * "in watchlist / not in watchlist" state without N extra queries.
     * Keys are "<type>:<id>" where type is 'movie' | 'show' | 'episode'.
     * Empty array for guests.
     */
    private function buildPerUser(): array
    {
        $userId = auth()->id();
        if (!$userId) {
            return ['userWatchlistIndex' => []];
        }

        $rows = WatchlistItem::where('user_id', $userId)
            ->get(['watchable_type', 'watchable_id']);

        $typeMap = [
            (new Movie)->getMorphClass()   => 'movie',
            (new Show)->getMorphClass()    => 'show',
            (new Episode)->getMorphClass() => 'episode',
        ];

        $index = [];
        foreach ($rows as $row) {
            $kind = $typeMap[$row->watchable_type] ?? null;
            if ($kind) {
                $index[$kind . ':' . $row->watchable_id] = true;
            }
        }
        return ['userWatchlistIndex' => $index];
    }
}
