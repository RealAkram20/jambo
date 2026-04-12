<?php

namespace Modules\Content\app\Http\Requests;

class UpdateSeasonRequest extends StoreSeasonRequest
{
    public function rules(): array
    {
        // A season's parent show is set at creation and can't change
        // on update, so we drop show_id from the ruleset entirely.
        $rules = parent::rules();
        unset($rules['show_id']);

        return $rules;
    }
}
