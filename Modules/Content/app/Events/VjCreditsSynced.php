<?php

namespace Modules\Content\app\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * Fired after a movie's/show's VJ credits pivot is synced on save.
 * Monetization listens to auto-attach title splits for enrolled
 * partners linked to the credited VJs — Content itself has no
 * knowledge of splits.
 */
class VjCreditsSynced
{
    public function __construct(public readonly Model $title)
    {
    }
}
