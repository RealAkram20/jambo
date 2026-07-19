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

        $created = 0;
        foreach ($vj->movies()->get(['movies.id']) as $movie) {
            $created += (int) (bool) $this->attach($movie, $partner);
        }
        foreach ($vj->shows()->get(['shows.id']) as $show) {
            $created += (int) (bool) $this->attach($show, $partner);
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
