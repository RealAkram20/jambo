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
            'poster_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'backdrop_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'trailer_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'dropbox_path' => 'nullable|string|max:500',
            'video_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'video_local' => ['nullable', 'string', 'max:500', 'regex:/^\//'],
            'video_url_low' => ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/)/'],
            'video_source' => 'nullable|in:url,local,dropbox',
            // 2 GB cap — anything bigger and you should be uploading to
            // object storage and passing a URL, not through PHP.
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime,video/x-matroska|max:2097152',
            'tier_required' => 'nullable|string|max:50',
            'status' => 'required|in:draft,published',

            'genre_ids' => 'nullable|array',
            'genre_ids.*' => 'integer|exists:genres,id',

            'vj_ids' => 'nullable|array',
            'vj_ids.*' => 'integer|exists:vjs,id',

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
