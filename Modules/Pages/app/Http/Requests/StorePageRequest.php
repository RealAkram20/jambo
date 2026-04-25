<?php

namespace Modules\Pages\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
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
            'slug' => 'nullable|string|max:255|alpha_dash|unique:pages,slug',
            'content' => 'nullable|string',
            'featured_image_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'meta_description' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',
        ];
    }
}
