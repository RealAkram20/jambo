<?php

namespace Modules\Content\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level `role:admin` middleware already enforces the gate.
        return true;
    }

    public function rules(): array
    {
        // season_id is intentionally NOT validated here. The route is
        // doubly nested — `/admin/series/{show}/seasons/{season}/episodes`
        // — so EpisodeController receives the parent Season via route
        // model binding and uses $season->episodes()->create() to set the
        // foreign key automatically. No form input submits season_id, so
        // requiring it here would simply block every form save.
        return [
            'number' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'synopsis' => 'nullable|string|max:5000',
            'runtime_minutes' => 'nullable|integer|min:1|max:1000',
            'still_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'dropbox_path' => 'nullable|string|max:500',
            'video_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'video_local' => ['nullable', 'string', 'max:500', 'regex:/^\//'],
            'video_url_low' => ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/)/'],
            'video_source' => 'nullable|in:url,local,dropbox',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime,video/x-matroska|max:2097152',
            'tier_required' => 'nullable|string|max:50',
            'published_at' => 'nullable|date',
        ];
    }
}
