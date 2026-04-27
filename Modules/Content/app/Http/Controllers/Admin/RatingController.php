<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Content\app\Models\Rating;

class RatingController extends Controller
{
    /**
     * Adjust an existing rating in place. Admin uses this when a user
     * left a clearly off-target rating (e.g. 1 star with a glowing
     * review body, or a botted 5 star that doesn't match the title's
     * overall sentiment) — updating beats deleting because we keep
     * the user's review attached.
     */
    public function update(Request $request, Rating $rating): RedirectResponse
    {
        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $rating->forceFill(['stars' => $data['stars']])->save();

        return redirect()->back()->with('success', 'Rating updated.');
    }

    public function destroy(Rating $rating): RedirectResponse
    {
        $rating->delete();

        return redirect()->back()->with('success', 'Rating deleted.');
    }
}
