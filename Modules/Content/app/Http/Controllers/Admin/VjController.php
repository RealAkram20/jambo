<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Vj;

/**
 * Admin CRUD for Vjs (voice-over translators).
 * Mirrors GenreController. Listing handled by DashboardController::vjs.
 */
class VjController extends Controller
{
    /**
     * Shared rules, so store() and update() can't drift apart — most of these
     * fields are only ever filled in from the edit form, and a field validated
     * on create but not on update is a silent data-quality hole.
     *
     * The social URLs must be absolute http(s): they are emitted verbatim into
     * schema.org `sameAs`, and a malformed value there is worse than an absent
     * one — Google discards the node rather than just the bad entry.
     */
    private function rules(?Vj $vj = null): array
    {
        $slugUnique = 'unique:vjs,slug' . ($vj ? ',' . $vj->id : '');

        $rules = [
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|' . $slugUnique,
            'colour'      => 'nullable|string|max:7',
            'photo_url'   => 'nullable|string|max:2048',
            'description' => 'nullable|string|max:2000',
        ];

        foreach (array_keys(Vj::SOCIAL_FIELDS) as $key) {
            $rules[$key] = 'nullable|url|starts_with:http://,https://|max:2048';
        }

        return $rules;
    }

    /**
     * Map the validated payload onto columns.
     *
     * Blank inputs become null, not '' — StructuredData's array_filter drops
     * nulls, but an empty string would survive and be emitted as a real (broken)
     * `sameAs` entry.
     */
    private function attributes(array $data): array
    {
        $out = [
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? '') ?: Str::slug($data['name']),
        ];

        $optional = array_merge(
            ['colour', 'photo_url', 'description'],
            array_keys(Vj::SOCIAL_FIELDS)
        );

        foreach ($optional as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            $out[$key] = $value !== '' ? $value : null;
        }

        return $out;
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $vj = Vj::create($this->attributes($data));

        return redirect()->back()->with('success', "Vj \"{$vj->name}\" created.");
    }

    public function update(Request $request, Vj $vj): RedirectResponse
    {
        $data = $request->validate($this->rules($vj));

        $vj->update($this->attributes($data));

        return redirect()->back()->with('success', "Vj \"{$vj->name}\" updated.");
    }

    public function destroy(Vj $vj): RedirectResponse
    {
        $name = $vj->name;
        $vj->delete();

        return redirect()->back()->with('success', "Deleted Vj \"{$name}\".");
    }
}
