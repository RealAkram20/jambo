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
        return [
            'show_id' => 'required|integer|exists:shows,id',
            'number' => 'required|integer|min:1',
            'title' => 'nullable|string|max:255',
            'synopsis' => 'nullable|string|max:5000',
            'poster_url' => 'nullable|url|max:500',
            'released_at' => 'nullable|date',
        ];
    }
}
