<?php

namespace Modules\Content\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level `role:admin` middleware already enforces the gate.
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'synopsis' => 'nullable|string|max:5000',
            'year' => 'nullable|integer|min:1900|max:2100',
            'runtime_minutes' => 'nullable|integer|min:1|max:1000',
            'rating' => 'nullable|string|max:8',
            'poster_url' => 'nullable|url|max:500',
            'backdrop_url' => 'nullable|url|max:500',
            'trailer_url' => 'nullable|url|max:500',
            'dropbox_path' => 'nullable|string|max:500',
            'video_url' => 'nullable|url|max:500',
            'tier_required' => 'nullable|string|max:50',
            'status' => 'required|in:draft,published',

            'genre_ids' => 'nullable|array',
            'genre_ids.*' => 'integer|exists:genres,id',

            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',

            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',

            'cast' => 'nullable|array',
            'cast.*.person_id' => 'nullable|integer|exists:persons,id',
            'cast.*.role' => 'nullable|string|in:actor,director,writer,producer,cinematographer',
            'cast.*.character_name' => 'nullable|string|max:255',
            'cast.*.display_order' => 'nullable|integer|min:0',
        ];
    }
}
