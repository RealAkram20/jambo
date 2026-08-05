<?php

namespace Modules\Monetization\app\Http\Controllers\Partner;

use Illuminate\Http\Request;
use Modules\Monetization\app\Models\MonetizationPeriod;
use Modules\Monetization\app\Models\QualifiedView;

class PartnerStatementController extends PartnerBaseController
{
    public function index()
    {
        $partner = $this->partner();

        // Partners only see CLOSED (settled) statements — drafts are
        // internal and can still change under recompute.
        $statements = $partner->statements()
            ->whereHas('period', fn ($q) => $q->where('status', MonetizationPeriod::STATUS_CLOSED))
            ->with('period')
            ->get()
            ->sortByDesc(fn ($s) => $s->period->period_month)
            ->values();

        return view('monetization::partner.statements', [
            'partner' => $partner,
            'statements' => $statements,
        ]);
    }

    public function show(int $period)
    {
        $partner = $this->partner();

        $statement = $partner->statements()
            ->where('period_id', $period)
            ->whereHas('period', fn ($q) => $q->where('status', MonetizationPeriod::STATUS_CLOSED))
            ->with('period')
            ->firstOrFail();

        return view('monetization::partner.statement-show', [
            'partner' => $partner,
            'statement' => $statement,
        ]);
    }

    /**
     * Live per-title performance for a chosen month (defaults to the
     * current one) — qualified views and split-weighted minutes per
     * attributed title.
     */
    public function titles(Request $request)
    {
        $partner = $this->partner();

        $month = $request->filled('month')
            ? now()->parse($request->string('month').'-01')->startOfMonth()
            : now()->startOfMonth();

        // Month's qualified minutes/views, aggregated ONCE up front.
        // The old shape ran two queries per split — fine for a pilot
        // catalogue, 1700+ queries for a real VJ with 850 titles.
        // Movies land on (watchable_type, watchable_id); episodes
        // roll up to their parent show via show_id.
        $movieStats = QualifiedView::query()
            ->where('period_month', $month->toDateString())
            ->whereNull('show_id')
            ->groupBy('watchable_type', 'watchable_id')
            ->selectRaw("CONCAT(watchable_type, '#', watchable_id) as k, SUM(minutes_credited) as minutes, COUNT(*) as views")
            ->get()->keyBy('k');
        $showStats = QualifiedView::query()
            ->where('period_month', $month->toDateString())
            ->whereNotNull('show_id')
            ->groupBy('show_id')
            ->selectRaw('show_id, SUM(minutes_credited) as minutes, COUNT(*) as views')
            ->get()->keyBy('show_id');

        $rows = $partner->splits()->with('splittable')->get()->map(function ($split) use ($movieStats, $showStats) {
            $isShow = str_contains($split->splittable_type, 'Show');
            $stat = $isShow
                ? $showStats->get($split->splittable_id)
                : $movieStats->get($split->splittable_type . '#' . $split->splittable_id);
            $minutes = (float) ($stat->minutes ?? 0);

            return [
                'id' => $split->splittable_id,
                'slug' => $split->splittable->slug ?? null,
                'title' => $split->splittable->title ?? $split->splittable->name ?? '(removed)',
                'type' => $isShow ? 'show' : 'movie',
                'exists' => $split->splittable !== null,
                'percent' => (float) $split->percent,
                'qualified_views' => (int) ($stat->views ?? 0),
                'minutes' => $minutes,
                'your_minutes' => round($minutes * ((float) $split->percent / 100), 1),
            ];
        });

        // Tab counts BEFORE the type filter (each tab keeps its number
        // while another is active) but AFTER search — the numbers then
        // always describe what the search found.
        if ($q = trim((string) $request->query('q', ''))) {
            $needle = mb_strtolower($q);
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower($r['title']), $needle));
        }
        $counts = [
            'all' => $rows->count(),
            'movie' => $rows->where('type', 'movie')->count(),
            'show' => $rows->where('type', 'show')->count(),
        ];
        $type = in_array($request->query('type'), ['movie', 'show'], true) ? $request->query('type') : '';
        if ($type !== '') {
            $rows = $rows->where('type', $type);
        }

        $rows = $rows->sortBy([['your_minutes', 'desc'], ['title', 'asc']])->values();

        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $perPage = 25;
        $rows = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        if ($request->ajax()) {
            return response()->json([
                'table' => view('monetization::partner.partials.titles-table', [
                    'rows' => $rows,
                    'partner' => $partner,
                ])->render(),
                'counts' => $counts,
            ]);
        }

        return view('monetization::partner.titles', [
            'partner' => $partner,
            'rows' => $rows,
            'month' => $month,
            'counts' => $counts,
            'filters' => ['q' => $q, 'type' => $type],
        ]);
    }
}
