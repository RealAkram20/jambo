<?php

namespace Modules\Pages\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Pages\app\Http\Requests\StorePageRequest;
use Modules\Pages\app\Http\Requests\UpdatePageRequest;
use Modules\Pages\app\Models\Page;

/**
 * Admin CRUD for static pages (About, Contact, FAQ, Terms, Privacy, plus
 * any custom additions).
 *
 * Routes: /admin/pages/*
 * Middleware: web + auth + role:admin (set in the route file).
 *
 * System pages (`is_system = true`) can be edited but not deleted —
 * the seeder created them so the public footer always has links to
 * resolve.
 */
class PageController extends Controller
{
    public function index(Request $request): View
    {
        $query = Page::query();

        if ($search = trim((string) $request->query('q'))) {
            $query->where('title', 'like', "%$search%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $pages = $query
            ->orderByDesc('is_system')
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('pages::admin.pages.index', [
            'pages' => $pages,
            'search' => $search,
            'statusFilter' => $status,
            'statusCounts' => [
                'all' => Page::count(),
                'published' => Page::where('status', 'published')->count(),
                'draft' => Page::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages::admin.pages.create', [
            'page' => new Page(['status' => 'published']),
        ]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $page = Page::create([
            'title' => $data['title'],
            'slug' => $data['slug'] ?: $this->uniqueSlug($data['title']),
            'content' => $data['content'] ?? null,
            'featured_image_url' => $data['featured_image_url'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'status' => $data['status'],
            'is_system' => false,
        ]);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', "Page \"{$page->title}\" created.");
    }

    public function edit(Page $page): View
    {
        return view('pages::admin.pages.edit', [
            'page' => $page,
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();

        $page->fill([
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'featured_image_url' => $data['featured_image_url'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'status' => $data['status'],
        ]);

        // Slug edits are blocked for system pages — the public footer
        // and FrontendController route the five default URLs by slug,
        // so changing one would 404 the public page.
        if (!$page->is_system && !empty($data['slug']) && $data['slug'] !== $page->slug) {
            $page->slug = $data['slug'];
        }

        // Merge structured meta on top of existing values so partial
        // submits (e.g. the form posting only the cards section) don't
        // wipe unrelated keys.
        if (array_key_exists('meta', $data) && is_array($data['meta'])) {
            $page->meta = array_replace((array) ($page->meta ?? []), $data['meta']);
        }

        $page->save();

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        if ($page->is_system) {
            return redirect()
                ->route('admin.pages.index')
                ->with('error', "\"{$page->title}\" is a system page and cannot be deleted. Set it to draft to hide it.");
        }

        $title = $page->title;
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', "Deleted \"$title\".");
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: Str::random(8);
        $slug = $base;
        $i = 2;

        while (Page::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "$base-$i";
            $i++;
        }

        return $slug;
    }
}
