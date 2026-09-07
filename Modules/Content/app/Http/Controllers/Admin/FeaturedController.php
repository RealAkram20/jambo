<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Content\app\Models\FeaturedItem;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;

/**
 * Featured: the hand-picked hero of the OTT homepage.
 *
 * Admins add a movie or a series, drag the rows into the order they want,
 * and that is the order the homepage banner and its poster rail render in.
 * With the list empty the homepage keeps its previous automatic behaviour,
 * so this can be deployed before anyone has picked anything.
 *
 * Only the homepage hero is driven from here. The banners on /movie,
 * /series and the VJ pages have their own queries and are untouched.
 */
class FeaturedController extends Controller
{
    /** Rows returned per search. Enough to choose from without a scroll marathon. */
    private const SEARCH_LIMIT = 12;

    public function index(): View
    {
        // A title deleted from the catalogue should not hold a hero slot.
        FeaturedItem::prune();

        return view('content::admin.featured.index', [
            'items' => FeaturedItem::forAdmin(),
        ]);
    }

    /**
     * Type-ahead over the whole catalogue, movies and series together.
     *
     * Replaces the two 300-row dropdowns this screen shipped with: those
     * could not reach a title outside the newest 300, which is most of the
     * catalogue. Results carry the poster, year and status so an admin can
     * tell two similarly-named VJ translations apart before adding one,
     * and already-featured titles come back flagged rather than missing,
     * so a search for something already in the hero explains itself.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $taken = FeaturedItem::get(['featurable_type', 'featurable_id'])
            ->map(fn ($i) => $i->featurable_type . ':' . $i->featurable_id)
            ->flip();

        $map = function ($model, string $kind) use ($taken) {
            return [
                'type' => $kind,
                'id' => $model->id,
                'title' => $model->title,
                'year' => $model->year,
                'status' => $model->status,
                'poster' => $model->poster_url ? media_img($model->poster_url, 120) : null,
                'featured' => $taken->has($model->getMorphClass() . ':' . $model->id),
            ];
        };

        $movies = Movie::where('title', 'like', "%{$term}%")
            ->orderByDesc('created_at')
            ->limit(self::SEARCH_LIMIT)
            ->get(['id', 'title', 'year', 'status', 'poster_url'])
            ->map(fn ($m) => $map($m, 'movie'));

        $shows = Show::where('title', 'like', "%{$term}%")
            ->orderByDesc('created_at')
            ->limit(self::SEARCH_LIMIT)
            ->get(['id', 'title', 'year', 'status', 'poster_url'])
            ->map(fn ($s) => $map($s, 'show'));

        // Alphabetical across both kinds. Each side was fetched newest-first
        // so a huge catalogue still surfaces recent titles, but the list an
        // admin reads is ordered by name — scanning for a remembered title
        // beats scanning by upload date.
        return response()->json([
            'results' => $movies->concat($shows)
                ->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)
                ->take(self::SEARCH_LIMIT)
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => 'required|in:movie,show',
            'id' => 'required|integer',
        ]);

        $isShow = $data['type'] === 'show';
        $model = $isShow ? Show::find($data['id']) : Movie::find($data['id']);

        if (!$model) {
            return back()->with('error', 'That title no longer exists.');
        }

        $type = $model->getMorphClass();

        if (FeaturedItem::where('featurable_type', $type)->where('featurable_id', $model->id)->exists()) {
            return back()->with('error', "\"{$model->title}\" is already featured.");
        }

        FeaturedItem::create([
            'featurable_type' => $type,
            'featurable_id' => $model->id,
            // New picks land at the end; the admin drags them where they want.
            'sort_order' => (int) FeaturedItem::max('sort_order') + 1,
        ]);

        $warning = $model->status !== 'published'
            ? ' It is not published yet, so it stays off the homepage until you publish it.'
            : '';

        return back()->with('success', "Added \"{$model->title}\" to the homepage hero." . $warning);
    }

    public function destroy(FeaturedItem $featured): RedirectResponse
    {
        $title = $featured->featurable?->title ?? 'That title';
        $featured->delete();

        return back()->with('success', "Removed \"{$title}\" from the homepage hero.");
    }

    /**
     * Persist the drag-and-drop order. Receives every featured row id in
     * display order; position becomes sort_order, which the homepage hero
     * follows directly. Same contract as the categories reorder endpoint.
     */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:featured_items,id',
        ]);

        foreach (array_values($data['order']) as $position => $id) {
            FeaturedItem::whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['ok' => true]);
    }

}
