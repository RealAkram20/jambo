<?php

namespace Modules\Content\app\Models\Concerns;

use Modules\Subscriptions\app\Support\ContentTiers;

/**
 * Exposes a display-safe plan label for content that carries a
 * `tier_required` slug.
 *
 * Views used to print the raw slug (`strtoupper($movie->tier_required)`),
 * which was fine while the only two values in play were 'basic' and
 * 'premium'. Now that any active tier can be assigned in bulk, the same
 * expression would render "BASIC-YEARLY" or "DAY-PASS" on a public poster —
 * and worse, imply four different paywalls where the gate enforces one.
 *
 * `plan_label` collapses a slug to the access level it actually gates at, so
 * every Basic-level plan shows as "Basic". Null means free.
 */
trait HasPlanLabel
{
    public function getPlanLabelAttribute(): ?string
    {
        return ContentTiers::label($this->tier_required);
    }
}
