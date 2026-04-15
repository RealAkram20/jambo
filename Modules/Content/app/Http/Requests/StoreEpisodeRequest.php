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
        return [
            'season_id' => 'required|integer|exists:seasons,id',
            'number' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'synopsis' => 'nullable|string|max:5000',
            'runtime_minutes' => 'nullable|integer|min:1|max:1000',
            'still_url' => 'nullable|url|max:500',
            'dropbox_path' => 'nullable|string|max:500',
            'video_url' => 'nullable|url|max:500',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime,video/x-matroska|max:2097152',
            'tier_required' => 'nullable|string|max:50',
            'published_at' => 'nullable|date',
        ];
    }
}
