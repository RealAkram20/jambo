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
        ]);

        Category::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'cover_url' => $data['cover_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
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
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'cover_url' => $data['cover_url'] ?? $category->cover_url,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
        ]);

        return redirect()->back()->with('success', "Category \"{$category->name}\" updated.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $name = $category->name;
        $category->delete();

        return redirect()->back()->with('success', "Deleted category \"{$name}\".");
    }
}
