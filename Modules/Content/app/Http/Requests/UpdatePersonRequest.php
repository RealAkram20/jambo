<?php

namespace Modules\Content\app\Http\Requests;

class UpdatePersonRequest extends StorePersonRequest
{
    // Same rules as Store for v1; split into its own class so we can
    // diverge later without touching callers.
}
