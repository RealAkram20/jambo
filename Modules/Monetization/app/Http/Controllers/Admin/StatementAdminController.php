<?php

namespace Modules\Monetization\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Monetization\app\Models\MonetizationPeriod;
use Modules\Monetization\app\Services\MonthCloseService;

/**
 * Monthly statement review. Drafts can be recomputed at will;
 * "Close & Credit" (super-admin) settles the month into wallets and
 * freezes it forever.
 */
class StatementAdminController extends Controller
{
    public function index()
    {
        return view('monetization::admin.statements.index', [
            'periods' => MonetizationPeriod::query()
                ->withCount('statements')
                ->orderByDesc('period_month')
                ->paginate(24),
        ]);
    }

    public function show(MonetizationPeriod $period)
    {
        $period->load(['statements' => fn ($q) => $q->orderByDesc('amount'), 'statements.partner', 'closedBy']);

        return view('monetization::admin.statements.show', [
            'period' => $period,
        ]);
    }

    public function recompute(MonetizationPeriod $period, MonthCloseService $service): RedirectResponse
    {
        if ($period->isClosed()) {
            return back()->with('error', 'This period is closed and cannot be recomputed.');
        }

        $service->computeDraft(CarbonImmutable::parse($period->period_month));

        return redirect()
            ->route('admin.monetization.statements.show', $period)
            ->with('success', 'Draft recomputed with current qualified views, splits and settings.');
    }

    public function close(Request $request, MonetizationPeriod $period, MonthCloseService $service): RedirectResponse
    {
        if ($period->isClosed()) {
            return back()->with('error', 'This period is already closed.');
        }

        $service->closeAndCredit($period, $request->user()->id);

        return redirect()
            ->route('admin.monetization.statements.show', $period)
            ->with('success', 'Period closed — partner wallets have been credited.');
    }
}
