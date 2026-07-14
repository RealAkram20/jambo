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

        $rows = $partner->splits()->with('splittable')->get()->map(function ($split) use ($month) {
            $isShow = str_contains($split->splittable_type, 'Show');

            $query = QualifiedView::query()
                ->where('period_month', $month->toDateString())
                ->when($isShow,
                    fn ($q) => $q->where('show_id', $split->splittable_id),
                    fn ($q) => $q->where('watchable_type', $split->splittable_type)
                        ->where('watchable_id', $split->splittable_id));

            $minutes = (float) $query->sum('minutes_credited');
            $views = (clone $query)->count();

            return [
                'id' => $split->splittable_id,
                'slug' => $split->splittable->slug ?? null,
                'title' => $split->splittable->title ?? $split->splittable->name ?? '(removed)',
                'type' => $isShow ? 'show' : 'movie',
                'exists' => $split->splittable !== null,
                'percent' => (float) $split->percent,
                'qualified_views' => $views,
                'minutes' => $minutes,
                'your_minutes' => round($minutes * ((float) $split->percent / 100), 1),
            ];
        })->sortByDesc('your_minutes')->values();

        return view('monetization::partner.titles', [
            'partner' => $partner,
            'rows' => $rows,
            'month' => $month,
        ]);
    }
}
