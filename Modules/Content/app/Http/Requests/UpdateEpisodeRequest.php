<?php

namespace Modules\Content\app\Http\Requests;

class UpdateEpisodeRequest extends StoreEpisodeRequest
{
    public function rules(): array
    {
        // An episode's parent season is set at creation and can't
        // change on update, so we drop season_id from the ruleset.
        $rules = parent::rules();
        unset($rules['season_id']);

        return $rules;
    }
}
