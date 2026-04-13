<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Content\app\Models\Rating;

class RatingController extends Controller
{
    public function destroy(Rating $rating): RedirectResponse
    {
        $rating->delete();

        return redirect()->back()->with('success', 'Rating deleted.');
    }
}
