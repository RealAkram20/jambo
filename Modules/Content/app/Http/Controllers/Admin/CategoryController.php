<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Category;

class CategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'cover_url' => 'nullable|string|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'visible_home' => 'nullable|boolean',
        ]);

        Category::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'cover_url' => $data['cover_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'visible_home' => $request->boolean('visible_home'),
        ]);

        return redirect()->back()->with('success', "Category \"{$data['name']}\" created.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'cover_url' => 'nullable|string|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'visible_home' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'cover_url' => $data['cover_url'] ?? $category->cover_url,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
            'visible_home' => $request->boolean('visible_home'),
        ]);

        return redirect()->back()->with('success', "Category \"{$category->name}\" updated.");
    }

    /**
     * One-click switch used by the category admin table: flips whether
     * this category renders as a rail on the public homepage.
     */
    public function toggleHome(Category $category): RedirectResponse
    {
        $category->update(['visible_home' => ! $category->visible_home]);

        $state = $category->visible_home ? 'now visible on' : 'hidden from';

        return redirect()->back()->with('success', "Category \"{$category->name}\" is {$state} the homepage.");
    }

    /**
     * Persist the drag-and-drop order from the admin table. Receives
     * every category id in display order; position becomes sort_order,
     * which the homepage rails follow directly.
     */
    public function reorder(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:categories,id',
        ]);

        foreach (array_values($data['order']) as $position => $id) {
            Category::whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $name = $category->name;
        $category->delete();

        return redirect()->back()->with('success', "Deleted category \"{$name}\".");
    }
}
