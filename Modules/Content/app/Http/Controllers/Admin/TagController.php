<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Tag;

class TagController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug',
        ]);

        Tag::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
        ]);

        return redirect()->back()->with('success', "Tag \"{$data['name']}\" created.");
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug,' . $tag->id,
        ]);

        $tag->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
        ]);

        return redirect()->back()->with('success', "Tag \"{$tag->name}\" updated.");
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $name = $tag->name;
        $tag->delete();

        return redirect()->back()->with('success', "Deleted tag \"{$name}\".");
    }
}
