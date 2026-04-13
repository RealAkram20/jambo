<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Genre;

class GenreController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:genres,slug',
            'colour' => 'nullable|string|max:7',
            'description' => 'nullable|string',
        ]);

        Genre::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'colour' => $data['colour'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Genre \"{$data['name']}\" created.");
    }

    public function update(Request $request, Genre $genre): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:genres,slug,' . $genre->id,
            'colour' => 'nullable|string|max:7',
            'description' => 'nullable|string',
        ]);

        $genre->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'colour' => $data['colour'] ?? $genre->colour,
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Genre \"{$genre->name}\" updated.");
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        $name = $genre->name;
        $genre->delete();

        return redirect()->back()->with('success', "Deleted genre \"{$name}\".");
    }
}
