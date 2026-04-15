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
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:vjs,slug',
            'colour' => 'nullable|string|max:7',
            'description' => 'nullable|string',
        ]);

        Vj::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'colour' => $data['colour'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Vj \"{$data['name']}\" created.");
    }

    public function update(Request $request, Vj $vj): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:vjs,slug,' . $vj->id,
            'colour' => 'nullable|string|max:7',
            'description' => 'nullable|string',
        ]);

        $vj->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'colour' => $data['colour'] ?? $vj->colour,
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Vj \"{$vj->name}\" updated.");
    }

    public function destroy(Vj $vj): RedirectResponse
    {
        $name = $vj->name;
        $vj->delete();

        return redirect()->back()->with('success', "Deleted Vj \"{$name}\".");
    }
}
