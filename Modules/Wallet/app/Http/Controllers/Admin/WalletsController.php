<?php

namespace Modules\Wallet\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Models\WithdrawalRequest;
use Modules\Wallet\app\Services\Ledger;

/**
 * Central wallet oversight: every wallet on the platform — staff
 * (performance pay), members (referral rewards), and partner profiles
 * (revenue share) — in one read-only browser, with a bank-statement
 * view per wallet.
 *
 * Read-only on purpose. Money only ever moves through Ledger::append
 * (and its callers: statements, referral earnings, performance
 * credits, the withdrawal queue). This surface adds visibility, not a
 * new write path — that's what keeps the ledger trustworthy.
 */
class WalletsController extends Controller
{
    /** URL slug → owner morph class. */
    private const OWNER_TYPES = [
        'user' => User::class,
        'partner' => MonetizationPartner::class,
    ];

    /**
     * The monetization program has a "finance_can_view" valve: when a
     * super-admin turns it off, finance staff lose read access to the
     * ENTIRE partner back office (partners, statements, withdrawals).
     * Partner wallets are the same dataset, so this browser must honor
     * the same valve — otherwise /admin/wallets is a side door around
     * it. Super-admins always see everything.
     */
    protected function canSeePartnerWallets(Request $request): bool
    {
        return $request->user()->hasRole('super-admin')
            || \Modules\Monetization\app\Services\MonetizationSettings::financeCanView();
    }

    public function index(Request $request)
    {
        $currency = setting('payments.currency', config('payments.currency', 'UGX'));

        // One grouped pass over the ledger: balance, lifetime in/out,
        // and last movement for every wallet that has ever had a row.
        $rows = LedgerEntry::query()
            ->where('currency', $currency)
            ->groupBy('owner_type', 'owner_id')
            ->selectRaw('owner_type, owner_id,
                SUM(amount) as balance,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_in,
                SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END) as total_out,
                MAX(created_at) as last_activity,
                COUNT(*) as entries')
            ->get();

        if (!$this->canSeePartnerWallets($request)) {
            $rows = $rows->where('owner_type', '!=', (new MonetizationPartner())->getMorphClass());
        }

        // Total actually paid out (settled withdrawals) per owner.
        $paidOut = WithdrawalRequest::query()
            ->where('status', WithdrawalRequest::STATUS_PAID)
            ->groupBy('owner_type', 'owner_id')
            ->selectRaw("CONCAT(owner_type, '#', owner_id) as k, SUM(amount) as total")
            ->pluck('total', 'k');

        $userMorph = (new User())->getMorphClass();
        $partnerMorph = (new MonetizationPartner())->getMorphClass();

        $users = User::with('roles:id,name')
            ->whereIn('id', $rows->where('owner_type', $userMorph)->pluck('owner_id'))
            ->get(['id', 'username', 'first_name', 'last_name', 'email'])
            ->keyBy('id');
        $partners = MonetizationPartner::with('user:id,username,email')
            ->whereIn('id', $rows->where('owner_type', $partnerMorph)->pluck('owner_id'))
            ->get()
            ->keyBy('id');

        $wallets = $rows->map(function ($r) use ($users, $partners, $paidOut, $userMorph, $partnerMorph) {
            if ($r->owner_type === $partnerMorph) {
                $partner = $partners->get($r->owner_id);
                $kind = 'partner';
                $name = $partner?->display_name ?? ('Partner #' . $r->owner_id);
                $sub = $partner?->user?->email ?? 'no linked account';
                $slug = 'partner';
            } else {
                $user = $users->get($r->owner_id);
                $roles = $user?->roles->pluck('name') ?? collect();
                $kind = $roles->intersect(['admin', 'finance', 'super-admin'])->isNotEmpty() ? 'staff' : 'member';
                $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';
                $name = $fullName ?: ($user->username ?? ('User #' . $r->owner_id));
                $sub = $user?->email ?? 'account deleted';
                $slug = 'user';
            }

            return (object) [
                'owner_slug' => $slug,
                'owner_id' => (int) $r->owner_id,
                'kind' => $kind,
                'name' => $name,
                'sub' => $sub,
                'balance' => (float) $r->balance,
                'total_in' => (float) $r->total_in,
                'total_out' => (float) $r->total_out,
                'paid_out' => (float) ($paidOut[$r->owner_type . '#' . $r->owner_id] ?? 0),
                'entries' => (int) $r->entries,
                'last_activity' => $r->last_activity,
            ];
        });

        // Platform-wide tallies BEFORE search/kind filters — the
        // "held balance" tile is the platform's total liability, and
        // must not silently shrink to "balance of whatever I searched".
        $totals = [
            'wallets' => $wallets->count(),
            'balance' => $wallets->sum('balance'),
            'in' => $wallets->sum('total_in'),
            'out' => $wallets->sum('total_out'),
        ];

        if ($kind = $request->query('kind')) {
            $wallets = $wallets->where('kind', $kind);
        }
        if ($q = trim((string) $request->query('q', ''))) {
            $needle = mb_strtolower($q);
            $wallets = $wallets->filter(fn ($w) =>
                str_contains(mb_strtolower($w->name), $needle)
                || str_contains(mb_strtolower($w->sub), $needle));
        }

        $sort = in_array($request->query('sort'), ['balance', 'total_in', 'last_activity'], true)
            ? $request->query('sort')
            : 'balance';
        $wallets = $wallets->sortByDesc($sort)->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 25;
        $paginated = new LengthAwarePaginator(
            $wallets->forPage($page, $perPage)->values(),
            $wallets->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('wallet::admin.wallets-index', [
            'wallets' => $paginated,
            'totals' => $totals,
            'currency' => $currency,
            'filters' => [
                'kind' => $kind ?: '',
                'q' => $q ?? '',
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Request $request, string $ownerType, int $ownerId, Ledger $ledger)
    {
        abort_unless(isset(self::OWNER_TYPES[$ownerType]), 404);
        abort_unless($ownerType !== 'partner' || $this->canSeePartnerWallets($request), 403);
        $owner = self::OWNER_TYPES[$ownerType]::findOrFail($ownerId);

        $currency = setting('payments.currency', config('payments.currency', 'UGX'));

        if ($owner instanceof MonetizationPartner) {
            $title = $owner->display_name;
            $subtitle = 'Partner profile' . ($owner->user ? ' — @' . $owner->user->username : ' — no linked account');
            $kind = 'partner';
        } else {
            $fullName = trim(($owner->first_name ?? '') . ' ' . ($owner->last_name ?? ''));
            $title = $fullName ?: $owner->username;
            $subtitle = $owner->email;
            $kind = $owner->roles->pluck('name')->intersect(['admin', 'finance', 'super-admin'])->isNotEmpty()
                ? 'staff' : 'member';
        }

        // Lifetime totals per entry type — the wallet's P&L at a glance.
        $byType = LedgerEntry::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->where('currency', $currency)
            ->groupBy('type')
            ->selectRaw('type, SUM(amount) as total, COUNT(*) as n')
            ->get()
            ->keyBy('type');

        // Statement: newest first, filterable by type and date window.
        // Currency-scoped to match the headline balance — the ledger is
        // multi-currency, and balance_after is a per-currency running
        // total, so mixing currencies in one statement would make the
        // running balance appear to jump backwards between rows.
        $entriesQuery = $ledger->entriesFor($owner)
            ->where('currency', $currency)
            ->with('createdBy:id,username');
        if ($type = $request->query('type')) {
            $entriesQuery->where('type', $type);
        }
        if ($from = $request->query('from')) {
            $entriesQuery->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $entriesQuery->whereDate('created_at', '<=', $to);
        }

        $withdrawals = WithdrawalRequest::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->orderByDesc('requested_at')
            ->limit(10)
            ->get();

        return view('wallet::admin.wallets-show', [
            'ownerType' => $ownerType,
            'ownerId' => $ownerId,
            'title' => $title,
            'subtitle' => $subtitle,
            'kind' => $kind,
            'currency' => $currency,
            'balance' => $ledger->balanceFor($owner, $currency),
            'byType' => $byType,
            'entries' => $entriesQuery->paginate(25)->withQueryString(),
            'withdrawals' => $withdrawals,
            'totalPaidOut' => (float) WithdrawalRequest::query()
                ->where('owner_type', $owner->getMorphClass())
                ->where('owner_id', $owner->getKey())
                ->where('status', WithdrawalRequest::STATUS_PAID)
                ->sum('amount'),
            'filters' => [
                'type' => $type ?: '',
                'from' => $from ?: '',
                'to' => $to ?: '',
            ],
        ]);
    }
}
