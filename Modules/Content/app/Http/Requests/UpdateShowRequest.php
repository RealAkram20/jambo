<?php

namespace Modules\Content\app\Http\Requests;

class UpdateShowRequest extends StoreShowRequest
{
    // Same rules as Store for v1. Extracted as its own class so we
    // can diverge later (e.g. relax title uniqueness for edits).
}
