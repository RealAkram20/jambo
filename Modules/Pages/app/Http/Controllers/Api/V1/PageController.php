<?php

namespace Modules\Pages\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pages\app\Models\Page;

/**
 * About, FAQ, Privacy, Terms — the CMS pages.
 *
 * One generic endpoint rather than four, because these are rows an admin
 * edits, not features: adding a page on the website makes it available to the
 * app with no release. That matters more than it sounds — Google Play requires
 * a subscription app to show its terms and privacy policy, and an app that
 * hard-codes them drifts from the site the moment somebody edits one.
 *
 * `content` is the admin's HTML. The app renders it in a web view or an HTML
 * renderer; it is not sanitised further here because it is authored in the
 * admin, by staff, and the website renders exactly the same string.
 */
class PageController extends Controller
{
    /**
     * The pages worth listing.
     *
     * `footer` and anything flagged `is_system` are structural rows the
     * website assembles into chrome, not screens a viewer opens, so they are
     * excluded from the index. They stay fetchable by slug for a client that
     * genuinely wants one.
     */
    public function index(): JsonResponse
    {
        $pages = Page::published()
            ->where('is_system', false)
            ->orderBy('title')
            ->get(['slug', 'title', 'meta_description'])
            ->map(fn (Page $page) => [
                'slug' => $page->slug,
                'title' => $page->title,
                'summary' => $page->meta_description,
            ]);

        return ApiResponse::ok(['pages' => $pages]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $page = Page::published()->where('slug', $slug)->first();

        if (! $page) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That page does not exist.');
        }

        return ApiResponse::ok(['page' => [
            'slug' => $page->slug,
            'title' => $page->title,
            'content' => $page->content,
            'summary' => $page->meta_description,
            'image_url' => media_url($page->featured_image_url),
            'updated_at' => optional($page->updated_at)->toIso8601String(),
        ]]);
    }
}
