<?php

namespace Modules\Content\app\Http\Requests;

class UpdateMovieRequest extends StoreMovieRequest
{
    // Same rules as Store for v1. Extracted as its own class so we
    // can diverge later (e.g. relax title uniqueness for edits, or
    // block changes to tier_required once a movie has been purchased).
}
