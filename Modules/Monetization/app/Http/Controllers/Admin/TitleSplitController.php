<?php

namespace Modules\Monetization\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Monetization\app\Models\TitleSplit;
use Modules\Monetization\app\Services\AuditLogger;

/**
 * Earning attribution editor. Splits are the ONLY thing that routes a
 * title's qualified minutes to partners — VJ credits on content remain
 * display metadata. A title's percentages may sum to less than 100
 * (the remainder stays with the platform at month close), never more.
 */
class TitleSplitController extends Controller
{
    public function index(Request $request)
    {
        // Existing split groups, one row per title.
        $groups = TitleSplit::query()
            ->with('partner:id,display_name,status')
            ->get()
            ->groupBy(fn ($s) => $s->splittable_type.'#'.$s->splittable_id);

        $titles = $groups->map(function ($splits) {
            $first = $splits->first();
            [$type, $model] = $this->resolveTitle($first->splittable_type, $first->splittable_id);

            return [
                'type' => $type,
                'id' => $first->splittable_id,
                'title' => $model?->title ?? $model?->name ?? '(deleted title)',
                'splits' => $splits,
                'total' => $splits->sum(fn ($s) => (float) $s->percent),
            ];
        })->sortBy('title')->values();

        // Search box for attaching a first split to a new title.
        $results = [];
        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->trim().'%';
            $results = collect()
                ->concat(Movie::query()->where('title', 'like', $term)->limit(10)->get(['id', 'title'])
                    ->map(fn ($m) => ['type' => 'movie', 'id' => $m->id, 'title' => $m->title]))
                ->concat(Show::query()->where('title', 'like', $term)->limit(10)->get(['id', 'title'])
                    ->map(fn ($s) => ['type' => 'show', 'id' => $s->id, 'title' => $s->title]));
        }

        return view('monetization::admin.splits.index', [
            'titles' => $titles,
            'results' => $results,
            'search' => $request->string('q')->toString(),
        ]);
    }

    public function edit(string $type, int $id)
    {
        [$label, $model] = $this->resolveTitle($this->morphClassFor($type), $id);
        abort_if($model === null, 404);

        $splits = TitleSplit::query()
            ->where('splittable_type', $model->getMorphClass())
            ->where('splittable_id', $model->getKey())
            ->with('partner:id,display_name,type,status')
            ->get();

        return view('monetization::admin.splits.edit', [
            'type' => $type,
            'title' => $model,
            'splits' => $splits,
            'partners' => MonetizationPartner::query()->orderBy('display_name')->get(['id', 'display_name', 'type', 'status']),
        ]);
    }

    /**
     * Replace the full split set for a title atomically. Sum must not
     * exceed 100; suspended partners may keep splits (they just accrue
     * to the platform at close until re-enrolled).
     */
    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        [, $model] = $this->resolveTitle($this->morphClassFor($type), $id);
        abort_if($model === null, 404);

        $data = $request->validate([
            'splits' => 'present|array|max:20',
            'splits.*.partner_id' => 'required|integer|distinct|exists:monetization_partners,id',
            'splits.*.percent' => 'required|numeric|min:0.01|max:100',
        ]);

        $total = collect($data['splits'])->sum(fn ($s) => (float) $s['percent']);
        if ($total > 100.0001) {
            return back()->withInput()->with('error', sprintf(
                'Split percentages add up to %.2f%% — they cannot exceed 100%%.', $total
            ));
        }

        $before = TitleSplit::query()
            ->where('splittable_type', $model->getMorphClass())
            ->where('splittable_id', $model->getKey())
            ->get(['partner_id', 'percent'])
            ->map(fn ($s) => $s->partner_id.':'.$s->percent)
            ->implode(', ');

        DB::transaction(function () use ($model, $data) {
            TitleSplit::query()
                ->where('splittable_type', $model->getMorphClass())
                ->where('splittable_id', $model->getKey())
                ->delete();

            foreach ($data['splits'] as $split) {
                TitleSplit::create([
                    'splittable_type' => $model->getMorphClass(),
                    'splittable_id' => $model->getKey(),
                    'partner_id' => $split['partner_id'],
                    'percent' => $split['percent'],
                ]);
            }
        });

        $after = collect($data['splits'])->map(fn ($s) => $s['partner_id'].':'.$s['percent'])->implode(', ');
        AuditLogger::log('split.updated', $model, ['before' => ['splits' => $before], 'after' => ['splits' => $after]]);

        return redirect()
            ->route('admin.monetization.splits.edit', ['type' => $type, 'id' => $id])
            ->with('success', 'Splits saved.');
    }

    protected function morphClassFor(string $type): string
    {
        return $type === 'movie'
            ? (new Movie())->getMorphClass()
            : (new Show())->getMorphClass();
    }

    /** @return array{0: string, 1: ?\Illuminate\Database\Eloquent\Model} */
    protected function resolveTitle(string $morphClass, int $id): array
    {
        $movieClass = (new Movie())->getMorphClass();

        if ($morphClass === $movieClass) {
            return ['movie', Movie::find($id)];
        }

        return ['show', Show::find($id)];
    }
}
