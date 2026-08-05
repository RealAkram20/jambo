<?php

namespace Modules\Monetization\app\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Content\app\Models\Vj;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Monetization\app\Models\TitleSplit;

/**
 * Bridges VJ catalog credit to earning attribution. VJ credit on a
 * title is display metadata; only TitleSplit rows route minutes to
 * partners at month close. For partners with a linked VJ, this
 * service creates those rows at the super-admin-configured default
 * percent — in bulk for the existing catalog, and per-title when VJ
 * credits change on save.
 *
 * A title's split percentages may sum to under 100 (remainder stays
 * with the platform) but never over: the default is clamped to the
 * headroom left by existing splits, and titles with no headroom are
 * skipped. Existing (title, partner) splits are never touched, so a
 * hand-tuned percent survives every later sync.
 */
class VjTitleSplits
{
    /**
     * Attach splits for every title credited to the partner's linked
     * VJ. Returns the number of splits created.
     *
     * Bulk implementation on purpose: a real VJ catalogue runs to
     * hundreds of titles (VJ Junior: 850+), and the original
     * one-attach()-per-title loop issued ~4 queries per title — enough
     * to blow past max_execution_time mid-loop on production, leaving
     * a handful of splits created and the rest silently missing. This
     * version does the same work in a handful of set-based queries.
     */
    public function attachAllForPartner(MonetizationPartner $partner): int
    {
        if (!$partner->vj_id || $partner->status !== MonetizationPartner::STATUS_ENROLLED) {
            return 0;
        }

        $vj = Vj::find($partner->vj_id);
        if (!$vj) {
            return 0;
        }

        $titles = $vj->movies()->get(['movies.id'])
            ->map(fn ($m) => ['type' => $m->getMorphClass(), 'id' => (int) $m->id])
            ->concat($vj->shows()->get(['shows.id'])
                ->map(fn ($s) => ['type' => $s->getMorphClass(), 'id' => (int) $s->id]));

        if ($titles->isEmpty()) {
            return 0;
        }

        $defaultPercent = (float) MonetizationSettings::defaultSplitPercent();
        $now = now();
        $created = 0;

        foreach ($titles->groupBy('type') as $type => $group) {
            $ids = $group->pluck('id');

            // Existing (title, this partner) splits — never touched, so
            // a hand-tuned percent survives every later sync.
            $mine = TitleSplit::query()
                ->where('splittable_type', $type)
                ->where('partner_id', $partner->id)
                ->whereIn('splittable_id', $ids)
                ->pluck('splittable_id')
                ->flip();

            // Percent already taken per title (all partners) — the new
            // split is clamped to the remaining headroom; titles with
            // none left are skipped.
            $taken = TitleSplit::query()
                ->where('splittable_type', $type)
                ->whereIn('splittable_id', $ids)
                ->groupBy('splittable_id')
                ->selectRaw('splittable_id, SUM(percent) as total')
                ->pluck('total', 'splittable_id');

            $rows = [];
            foreach ($ids as $id) {
                if (isset($mine[$id])) {
                    continue;
                }
                $percent = min($defaultPercent, 100 - (float) ($taken[$id] ?? 0));
                if ($percent <= 0) {
                    continue;
                }
                $rows[] = [
                    'splittable_type' => $type,
                    'splittable_id' => $id,
                    'partner_id' => $partner->id,
                    'percent' => number_format($percent, 2, '.', ''),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                TitleSplit::query()->insert($chunk);
            }
            $created += count($rows);
        }

        if ($created > 0) {
            // One summary audit row instead of one per title — the
            // per-split detail is reconstructable from title_splits.
            AuditLogger::log('split.auto_attached_bulk', $partner, ['after' => [
                'splits_created' => $created,
                'default_percent' => (string) $defaultPercent,
            ]]);
        }

        return $created;
    }

    /**
     * After a title's VJ credits are synced: create missing splits for
     * every credited VJ that has an enrolled partner. Returns splits
     * created.
     */
    public function syncTitle(Model $title): int
    {
        $vjIds = $title->vjs()->pluck('vjs.id');
        if ($vjIds->isEmpty()) {
            return 0;
        }

        $created = 0;
        MonetizationPartner::query()
            ->whereIn('vj_id', $vjIds)
            ->where('status', MonetizationPartner::STATUS_ENROLLED)
            ->get()
            ->each(function (MonetizationPartner $partner) use ($title, &$created) {
                $created += (int) (bool) $this->attach($title, $partner);
            });

        return $created;
    }

    protected function attach(Model $title, MonetizationPartner $partner): ?TitleSplit
    {
        $exists = TitleSplit::query()
            ->where('splittable_type', $title->getMorphClass())
            ->where('splittable_id', $title->getKey())
            ->where('partner_id', $partner->id)
            ->exists();
        if ($exists) {
            return null;
        }

        $taken = (float) TitleSplit::query()
            ->where('splittable_type', $title->getMorphClass())
            ->where('splittable_id', $title->getKey())
            ->sum('percent');

        $percent = min((float) MonetizationSettings::defaultSplitPercent(), 100 - $taken);
        if ($percent <= 0) {
            return null;
        }

        $split = TitleSplit::create([
            'splittable_type' => $title->getMorphClass(),
            'splittable_id' => $title->getKey(),
            'partner_id' => $partner->id,
            'percent' => number_format($percent, 2, '.', ''),
        ]);

        AuditLogger::log('split.auto_attached', $partner, ['after' => [
            'title_type' => $title->getMorphClass(),
            'title_id' => $title->getKey(),
            'percent' => $split->percent,
        ]]);

        return $split;
    }
}
