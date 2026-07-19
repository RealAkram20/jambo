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

        // This admin's own created-counts, plus earnings as ACTUALLY
        // credited to their universal wallet (rates are snapshotted per
        // upload, so these figures are money, not an estimate).
        $myCounts = $this->createdCounts($since, $user->id);
        $myEarnings = (float) app(\App\Services\PerformanceCredits::class)->earnedSince($user, $since);
        $walletBalance = app(\Modules\Wallet\app\Services\Ledger::class)->balanceFor($user);

        // Super-admin also sees every admin's contribution, ranked.
        $leaderboard = [];
        if ($isSuperAdmin) {
            $leaderboard = $this->leaderboard($since);
        }

        // Super-admin can narrow the feed to one admin (?actor=<id>) to
        // see everything that person did in the period. Regular admins
        // are pinned to themselves, so the param is ignored for them.
        $actorId = $isSuperAdmin && ctype_digit((string) $request->query('actor'))
            ? (int) $request->query('actor')
            : null;

        // "Who did what" feed. Super-admin sees everyone (or the one
        // admin they filtered to); a regular admin sees only their own
        // actions.
        $feedQuery = ContentActivity::query()
            ->with('actor')
            ->where('created_at', '>=', $since)
            ->orderByDesc('id');
        if (!$isSuperAdmin) {
            $feedQuery->where('actor_id', $user->id);
        } elseif ($actorId !== null) {
            $feedQuery->where('actor_id', $actorId);
        }
        $feed = $feedQuery->limit(100)->get();

        // Dropdown options: everyone who has ever appeared in the log,
        // not just this period — so a quiet month still lets you pick
        // an admin and confirm they did nothing.
        $actors = $isSuperAdmin
            ? User::whereIn('id', ContentActivity::query()->whereNotNull('actor_id')->distinct()->pluck('actor_id'))
                ->orderBy('username')
                ->get(['id', 'username'])
            : collect();

        return view('DashboardPages.performance.index', [
            'period'       => $period,
            'isSuperAdmin' => $isSuperAdmin,
            'currency'     => $currency,
            'prices'       => $prices,
            'myCounts'     => $myCounts,
            'myEarnings'   => $myEarnings,
            'walletBalance' => $walletBalance,
            'leaderboard'  => $leaderboard,
            'feed'         => $feed,
            'actors'       => $actors,
            'actorId'      => $actorId,
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
     * Per-admin created-counts + WALLET-CREDITED earnings for the
     * period, ranked by earnings desc. Counts come from the activity
     * log; money comes from the universal ledger, so the board shows
     * what was actually paid, not an estimate at today's rates.
     *
     * @return list<array{user:?User, counts:array, earnings:float}>
     */
    private function leaderboard(Carbon $since): array
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

        // What each admin was actually credited this period.
        $paid = \Modules\Wallet\app\Models\LedgerEntry::query()
            ->where('owner_type', (new User())->getMorphClass())
            ->where('type', \Modules\Wallet\app\Models\LedgerEntry::TYPE_PERFORMANCE_CREDIT)
            ->where('created_at', '>=', $since)
            ->groupBy('owner_id')
            ->selectRaw('owner_id, SUM(amount) as total')
            ->pluck('total', 'owner_id');

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
                'earnings' => (float) ($paid[$actorId] ?? 0),
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
