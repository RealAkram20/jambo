<?php

namespace Modules\Content\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'bio' => 'nullable|string|max:5000',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date|after_or_equal:birth_date',
            // string, not url: the media picker fills in site-relative
            // /storage/... paths, which the `url` rule rejects.
            'photo_url' => 'nullable|string|max:255',
            'known_for' => 'nullable|string|max:255',
        ];
    }
}
