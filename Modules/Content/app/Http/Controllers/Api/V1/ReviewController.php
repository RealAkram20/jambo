<?php

namespace Modules\Content\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Review;
use Modules\Content\app\Models\Show;
use Modules\Notifications\app\Events\NewReviewPosted;

/**
 * Reviews on a movie or a series.
 *
 * The rules are the website's, not new ones: published reviews only, newest
 * first, one review per (user, title) written with updateOrCreate so editing
 * overwrites instead of stacking duplicates, and the NewReviewPosted event
 * fired only on a genuinely new row. FrontendController::persistReview and
 * loadReviewData are the reference.
 *
 * Reviews attach to a movie or a show, never to an episode — that is the
 * morph the table was built with, and the website renders a series' reviews on
 * its episode pages for the same reason. An app asking to review an episode is
 * asking for something the data model does not have, so it gets a validation
 * failure rather than a surprise.
 */
class ReviewController extends Controller
{
    private const PAGE_SIZE = 20;

    /**
     * The review thread for a title, plus the aggregate the app shows next to
     * the stars, plus this viewer's own review if they have written one — so a
     * detail screen needs one request rather than three.
     */
    public function index(Request $request, string $type, string $slug): JsonResponse
    {
        $content = $this->findContent($type, $slug);

        if (! $content) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That title does not exist.');
        }

        $base = Review::query()
            ->where('reviewable_type', $content->getMorphClass())
            ->where('reviewable_id', $content->getKey());

        $published = (clone $base)->where('is_published', true);

        $reviews = (clone $published)
            ->with('user:id,username,first_name,last_name')
            ->latest()
            // id as a tiebreaker: created_at is not unique, and a cursor on a
            // tied column silently drops every row that ties.
            ->orderByDesc('id')
            ->cursorPaginate(self::PAGE_SIZE);

        $mine = $request->user()
            ? (clone $base)->where('user_id', $request->user()->id)->first()
            : null;

        return ApiResponse::ok([
            'stats' => [
                'count' => (clone $published)->count(),
                // Rounded to one place, exactly as the website's star display
                // does it, so the two never show a different number for the
                // same title.
                'average' => round((float) (clone $published)->avg('stars'), 1),
            ],
            'mine' => $mine ? $this->card($mine) : null,
            'items' => $reviews->getCollection()->map(fn (Review $r) => $this->card($r))->values(),
            'next_cursor' => $reviews->nextCursor()?->encode(),
        ]);
    }

    /**
     * Write or replace this viewer's review.
     *
     * updateOrCreate, so posting twice edits rather than duplicates — which
     * also makes the endpoint safe to retry on a connection that drops.
     */
    public function store(Request $request, string $type, string $slug): JsonResponse
    {
        $content = $this->findContent($type, $slug);

        if (! $content) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That title does not exist.');
        }

        // The same bounds the web form validates on.
        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'min:3', 'max:4000'],
        ]);

        $review = Review::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'reviewable_type' => $content->getMorphClass(),
                'reviewable_id' => $content->getKey(),
            ],
            [
                'stars' => $data['stars'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'],
                'is_published' => true,
            ],
        );

        // Only a genuinely new review notifies. wasRecentlyCreated is the
        // Eloquent flag for "this was an INSERT", so an edit does not ping
        // an admin a second time.
        if ($review->wasRecentlyCreated) {
            event(new NewReviewPosted(
                $review->id,
                $request->user()->username,
                $content->title,
                (int) $data['stars'],
            ));
        }

        return ApiResponse::ok(
            ['review' => $this->card($review->fresh('user'))],
            $review->wasRecentlyCreated ? 'Review posted.' : 'Review updated.',
        );
    }

    /**
     * Delete this viewer's own review. Idempotent: deleting one that is not
     * there succeeds, because the intent is satisfied either way and a retry
     * must not fail.
     */
    public function destroy(Request $request, string $type, string $slug): JsonResponse
    {
        $content = $this->findContent($type, $slug);

        if (! $content) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That title does not exist.');
        }

        Review::query()
            ->where('user_id', $request->user()->id)
            ->where('reviewable_type', $content->getMorphClass())
            ->where('reviewable_id', $content->getKey())
            ->delete();

        return ApiResponse::ok(null, 'Your review was removed.');
    }

    // ── internals ────────────────────────────────────────────────────

    /**
     * Movies and series only. `detailVisible` rather than `published`, so a
     * scheduled title's page can carry its reviews before release, matching
     * what the website's detail page does.
     */
    private function findContent(string $type, string $slug): ?Model
    {
        // Plural because the segment doubles as the catalogue path the app
        // already has (/movies/{slug}), so a review URL is that URL plus
        // /reviews rather than a second vocabulary to remember.
        return match ($type) {
            'movies' => Movie::where('slug', $slug)->detailVisible()->first(),
            'series' => Show::where('slug', $slug)->detailVisible()->first(),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function card(Review $review): array
    {
        return [
            'id' => $review->id,
            'stars' => (int) $review->stars,
            'title' => $review->title,
            'body' => $review->body,
            'created_at' => optional($review->created_at)->toIso8601String(),
            'updated_at' => optional($review->updated_at)->toIso8601String(),
            'author' => $this->author($review->user),
        ];
    }

    /**
     * Only what a review needs to be attributed. The user relation is loaded
     * with an explicit column list, and this narrows it again: a reviewer's
     * email address has no business in a public thread.
     *
     * @return array<string, mixed>|null
     */
    private function author(?object $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'username' => $user->username,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->username,
        ];
    }
}
