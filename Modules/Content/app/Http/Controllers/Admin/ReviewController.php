<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Content\app\Models\Review;

class ReviewController extends Controller
{
    public function togglePublished(Review $review): RedirectResponse
    {
        $review->update(['is_published' => ! $review->is_published]);

        $status = $review->is_published ? 'published' : 'unpublished';

        return redirect()->back()->with('success', "Review #{$review->id} {$status}.");
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()->back()->with('success', 'Review deleted.');
    }
}
