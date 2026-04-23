<?php

namespace Modules\Content\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level `role:admin` middleware already enforces the gate.
        return true;
    }

    public function rules(): array
    {
        // show_id is intentionally NOT validated here. The route is nested —
        // `/admin/series/{show}/seasons` — so SeasonController receives the
        // parent Show via route model binding and uses $show->seasons()->
        // create() to set the foreign key automatically. Validating show_id
        // as a form field would only break the form, since no input submits
        // it. UpdateSeasonRequest already reflects this convention.
        return [
            'number' => 'required|integer|min:1',
            'title' => 'nullable|string|max:255',
            'synopsis' => 'nullable|string|max:5000',
            'poster_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'released_at' => 'nullable|date',
        ];
    }
}
