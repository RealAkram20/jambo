<?php

namespace Modules\Content\app\Http\Requests;

class UpdateSeasonRequest extends StoreSeasonRequest
{
    public function rules(): array
    {
        // Parent show is route-bound and not a form field — StoreSeasonRequest
        // now drops show_id too, so this just inherits the parent rules as-is.
        return parent::rules();
    }
}
