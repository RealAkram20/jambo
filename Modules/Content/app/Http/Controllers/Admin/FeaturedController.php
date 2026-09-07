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
    /** Titles offered in the picker. Enough to choose from, not the whole catalogue. */
    private const PICKER_LIMIT = 300;

    public function index(): View
    {
        // A title deleted from the catalogue should not hold a hero slot.
        FeaturedItem::prune();

        $items = FeaturedItem::forAdmin();
        $taken = $items->map(fn ($i) => $i->featurable_type . ':' . $i->featurable_id)->all();

        return view('content::admin.featured.index', [
            'items' => $items,
            // Already-featured titles are excluded so the picker cannot
            // offer a duplicate the unique index would reject anyway.
            'movieOptions' => $this->pickerOptions(Movie::query(), (new Movie)->getMorphClass(), $taken),
            'showOptions' => $this->pickerOptions(Show::query(), (new Show)->getMorphClass(), $taken),
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

    /**
     * Options for one of the two picker selects, newest first, with the
     * already-featured titles removed and unpublished ones labelled so an
     * admin is not surprised when the pick does not appear on the site.
     */
    private function pickerOptions($query, string $morphClass, array $taken): array
    {
        return $query->select('id', 'title', 'status', 'year')
            ->orderByDesc('created_at')
            ->limit(self::PICKER_LIMIT)
            ->get()
            ->reject(fn ($m) => in_array($morphClass . ':' . $m->id, $taken, true))
            ->map(fn ($m) => [
                'id' => $m->id,
                'label' => trim($m->title . ($m->year ? " ({$m->year})" : ''))
                    . ($m->status === 'published' ? '' : ' — ' . ucfirst((string) $m->status)),
            ])
            ->values()
            ->all();
    }
}
