<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Content\app\Models\ContentActivity;

/**
 * Admin Performance dashboard: tracks how much content each admin has
 * added (movies / shows / seasons / episodes), who created/edited/deleted
 * what, and the resulting earnings at the super-admin-configured per-item
 * rates. Distinct from the Monetization module (partner watch-time revenue
 * share) — this is internal staff-contribution tracking.
 *
 * Counts come from the append-only content_activity_log (action=created),
 * NOT the live content tables, so a deleted movie still counts toward the
 * admin who added it — earnings can't be erased by deleting content.
 */
class PerformanceController extends Controller
{
    /** Content types that earn, and the setting key holding each rate. */
    private const PRICE_KEYS = [
        'movie'   => 'performance.price_per_movie',
        'show'    => 'performance.price_per_show',
        'episode' => 'performance.price_per_episode',
        // seasons are tracked but never paid (grouping only)
        'season'  => null,
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        $period = in_array($request->query('period'), ['day', 'week', 'month'], true)
            ? $request->query('period')
            : 'month';
        $since = $this->periodStart($period);

        $prices = $this->prices();
        $currency = setting('payments.currency', config('payments.currency', 'UGX'));

        // This admin's own created-counts + earnings for the period.
        $myCounts = $this->createdCounts($since, $user->id);
        $myEarnings = $this->earningsFor($myCounts, $prices);

        // Super-admin also sees every admin's contribution, ranked.
        $leaderboard = [];
        if ($isSuperAdmin) {
            $leaderboard = $this->leaderboard($since, $prices);
        }

        // "Who did what" feed. Super-admin sees everyone; a regular admin
        // sees only their own actions.
        $feedQuery = ContentActivity::query()
            ->with('actor')
            ->where('created_at', '>=', $since)
            ->orderByDesc('id');
        if (!$isSuperAdmin) {
            $feedQuery->where('actor_id', $user->id);
        }
        $feed = $feedQuery->limit(50)->get();

        return view('DashboardPages.performance.index', [
            'period'       => $period,
            'isSuperAdmin' => $isSuperAdmin,
            'currency'     => $currency,
            'prices'       => $prices,
            'myCounts'     => $myCounts,
            'myEarnings'   => $myEarnings,
            'leaderboard'  => $leaderboard,
            'feed'         => $feed,
        ]);
    }

    public function settings(Request $request): View
    {
        $currency = setting('payments.currency', config('payments.currency', 'UGX'));

        return view('DashboardPages.performance.settings', [
            'prices'   => $this->prices(),
            'currency' => $currency,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'price_per_movie'   => ['required', 'numeric', 'min:0'],
            'price_per_show'    => ['required', 'numeric', 'min:0'],
            'price_per_episode' => ['required', 'numeric', 'min:0'],
        ]);

        setting(['performance.price_per_movie', (string) $data['price_per_movie']]);
        setting(['performance.price_per_show', (string) $data['price_per_show']]);
        setting(['performance.price_per_episode', (string) $data['price_per_episode']]);

        return redirect()
            ->route('dashboard.performance.settings')
            ->with('success', 'Performance rates saved.');
    }

    /* ---------------------------------------------------------------- */

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            'day'  => now()->startOfDay(),
            'week' => now()->subDays(7),
            default => now()->subDays(30),
        };
    }

    /**
     * @return array{movie:int, show:int, season:int, episode:int}
     */
    private function createdCounts(Carbon $since, ?int $actorId = null): array
    {
        $q = ContentActivity::query()
            ->where('action', ContentActivity::ACTION_CREATED)
            ->where('created_at', '>=', $since);
        if ($actorId !== null) {
            $q->where('actor_id', $actorId);
        }

        $rows = $q->select('content_type', DB::raw('COUNT(*) as n'))
            ->groupBy('content_type')
            ->pluck('n', 'content_type');

        return [
            'movie'   => (int) ($rows['movie'] ?? 0),
            'show'    => (int) ($rows['show'] ?? 0),
            'season'  => (int) ($rows['season'] ?? 0),
            'episode' => (int) ($rows['episode'] ?? 0),
        ];
    }

    /**
     * @param array{movie:int, show:int, season:int, episode:int} $counts
     * @param array<string, float> $prices
     */
    private function earningsFor(array $counts, array $prices): float
    {
        return $counts['movie'] * $prices['movie']
            + $counts['show'] * $prices['show']
            + $counts['episode'] * $prices['episode'];
        // seasons excluded on purpose — not a paid unit
    }

    /**
     * Per-admin created-counts + earnings for the period, ranked by
     * earnings desc. One grouped query, then priced in PHP.
     *
     * @return list<array{user:?User, counts:array, earnings:float}>
     */
    private function leaderboard(Carbon $since, array $prices): array
    {
        $rows = ContentActivity::query()
            ->where('action', ContentActivity::ACTION_CREATED)
            ->where('created_at', '>=', $since)
            ->whereNotNull('actor_id')
            ->select('actor_id', 'content_type', DB::raw('COUNT(*) as n'))
            ->groupBy('actor_id', 'content_type')
            ->get();

        $byActor = [];
        foreach ($rows as $r) {
            $byActor[$r->actor_id]['counts'][$r->content_type] = (int) $r->n;
        }

        $users = User::whereIn('id', array_keys($byActor))->get()->keyBy('id');

        $out = [];
        foreach ($byActor as $actorId => $data) {
            $counts = [
                'movie'   => $data['counts']['movie'] ?? 0,
                'show'    => $data['counts']['show'] ?? 0,
                'season'  => $data['counts']['season'] ?? 0,
                'episode' => $data['counts']['episode'] ?? 0,
            ];
            $out[] = [
                'user'     => $users->get($actorId),
                'counts'   => $counts,
                'earnings' => $this->earningsFor($counts, $prices),
            ];
        }

        usort($out, fn ($a, $b) => $b['earnings'] <=> $a['earnings']);
        return $out;
    }

    /**
     * @return array{movie:float, show:float, episode:float}
     */
    private function prices(): array
    {
        return [
            'movie'   => (float) setting(self::PRICE_KEYS['movie'], 0),
            'show'    => (float) setting(self::PRICE_KEYS['show'], 0),
            'episode' => (float) setting(self::PRICE_KEYS['episode'], 0),
        ];
    }
}
