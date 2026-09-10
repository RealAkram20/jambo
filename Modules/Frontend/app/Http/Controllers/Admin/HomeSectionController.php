<?php

namespace Modules\Frontend\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Frontend\app\Models\HomeSection;

/**
 * Home sections: the order the app's home screen renders its shelves in.
 *
 * Admins drag the rows and that is the order `/api/v1/home` returns, so
 * rearranging the homepage is a drag on this screen rather than a deploy. The
 * switch beside each row takes a shelf off the home screen without deleting
 * anything, which is what an admin actually wants for a seasonal rail.
 *
 * Nothing here touches the website's own homepage. `ott-page.blade.php`
 * describes its sections with a different, non-matching vocabulary and it is
 * Streamit template Blade; reconciling the two is stage 2 and needs an ADR
 * first. See docs/plans/homepage-section-arrangement.md §3 and §6.
 *
 * The drag contract is the one `FeaturedController::reorder()` and
 * `CategoryController::reorder()` already use — every row id in display order,
 * index becomes position. A fourth ordering contract would be one too many.
 */
class HomeSectionController extends Controller
{
    public function index(): View
    {
        // A rail added in code since the last visit gets its row here, at the
        // end, so the screen that arranges the home page always lists
        // everything the home page can render.
        HomeSection::seedDefaults();

        return view('frontend::admin.home-sections.index', [
            'sections' => HomeSection::forAdmin(),
        ]);
    }

    /**
     * Persist the drag order. Receives every section row id in display order;
     * position becomes the index, which `HomeSection::arrange()` sorts on.
     * Same contract as the featured and categories reorder endpoints.
     */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:home_sections,id',
        ]);

        foreach (array_values($data['order']) as $position => $id) {
            HomeSection::whereKey($id)->update(['position' => $position]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Show or hide one section on the app's home screen.
     *
     * Disabling never deletes the row, so the arrangement survives being
     * switched back on. Disabling Continue Watching hides that shelf and
     * nothing else: resume is a separate endpoint the app calls directly, and
     * a test pins that.
     */
    public function toggle(Request $request, HomeSection $homeSection): JsonResponse
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $homeSection->update(['enabled' => $data['enabled']]);

        return response()->json([
            'ok' => true,
            'enabled' => $homeSection->enabled,
        ]);
    }
}
