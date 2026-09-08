<?php

namespace Modules\Content\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\app\Models\Comment;
use Modules\Content\app\Models\Episode;
use Modules\Notifications\app\Events\NewCommentPosted;

/**
 * The comment thread under an episode.
 *
 * Same rules as the website: approved comments only, top-level first with
 * their replies nested, newest first, and the NewCommentPosted event on every
 * new comment. FrontendController::storeEpisodeComment is the reference.
 *
 * Episodes only, because that is where the website puts them. Movies carry
 * reviews instead, which is a different table with a different shape.
 */
class CommentController extends Controller
{
    private const PAGE_SIZE = 30;

    /**
     * Top-level comments with their replies attached.
     *
     * The website renders top-level only and notes that replies are not
     * rendered yet. The data model already supports them through `parent_id`,
     * so the app gets them nested rather than being told to make a second
     * request per comment.
     */
    public function index(Request $request, int $episodeId): JsonResponse
    {
        $episode = Episode::find($episodeId);

        if (! $episode) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That episode does not exist.');
        }

        $threads = Comment::query()
            ->where('commentable_type', $episode->getMorphClass())
            ->where('commentable_id', $episode->id)
            ->where('is_approved', true)
            ->whereNull('parent_id')
            ->with('user:id,username,first_name,last_name')
            ->latest()
            // id as a tiebreaker: created_at is not unique, and a cursor on a
            // tied column silently drops every row that ties.
            ->orderByDesc('id')
            ->cursorPaginate(self::PAGE_SIZE);

        $parentIds = $threads->getCollection()->pluck('id');

        $replies = $parentIds->isEmpty()
            ? collect()
            : Comment::query()
                ->whereIn('parent_id', $parentIds)
                ->where('is_approved', true)
                ->with('user:id,username,first_name,last_name')
                ->oldest()
                ->get()
                ->groupBy('parent_id');

        $items = $threads->getCollection()->map(function (Comment $comment) use ($replies, $request) {
            return $this->card($comment, $request) + [
                'replies' => $replies->get($comment->id, collect())
                    ->map(fn (Comment $reply) => $this->card($reply, $request))
                    ->values(),
            ];
        })->values();

        return ApiResponse::ok([
            'items' => $items,
            'next_cursor' => $threads->nextCursor()?->encode(),
        ]);
    }

    public function store(Request $request, int $episodeId): JsonResponse
    {
        $episode = Episode::with('season.show')->find($episodeId);

        if (! $episode) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That episode does not exist.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        // A reply must belong to the same episode. Without this a client
        // could graft a reply from one show onto a thread in another, and
        // the thread query would happily render it.
        if (! empty($data['parent_id'])) {
            $parent = Comment::find($data['parent_id']);

            $sameEpisode = $parent
                && $parent->commentable_type === $episode->getMorphClass()
                && (int) $parent->commentable_id === (int) $episode->id;

            if (! $sameEpisode) {
                return ApiResponse::error(
                    ApiErrorCode::ValidationFailed,
                    'That comment is not on this episode.',
                    ['parent_id' => ['That comment is not on this episode.']],
                );
            }
        }

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'commentable_type' => $episode->getMorphClass(),
            'commentable_id' => $episode->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
            'is_approved' => true,
        ]);

        $showTitle = $episode->season?->show?->title;
        $contentTitle = $showTitle
            ? "{$showTitle} — {$episode->title}"
            : ($episode->title ?? 'episode');

        event(new NewCommentPosted(
            $comment->id,
            $request->user()->username,
            $contentTitle,
            $data['body'],
        ));

        return ApiResponse::created(['comment' => $this->card($comment->fresh('user'), $request)]);
    }

    /**
     * Delete your own comment. Admins may delete any, matching the website.
     *
     * Someone else's comment answers NOT_FOUND rather than 403: a 403 would
     * confirm the comment exists, which is a small thing to leak but a free
     * thing to avoid.
     */
    public function destroy(Request $request, int $commentId): JsonResponse
    {
        $comment = Comment::find($commentId);
        $user = $request->user();

        if (! $comment || (! $user->hasRole('admin') && $comment->user_id !== $user->id)) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That comment does not exist.');
        }

        $comment->delete();

        return ApiResponse::ok(null, 'Comment removed.');
    }

    // ── internals ────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function card(Comment $comment, Request $request): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'created_at' => optional($comment->created_at)->toIso8601String(),
            'author' => $comment->user ? [
                'username' => $comment->user->username,
                'name' => trim(($comment->user->first_name ?? '') . ' ' . ($comment->user->last_name ?? ''))
                    ?: $comment->user->username,
            ] : null,
            // So the app can show a delete affordance without working out
            // ownership from a username it may render differently.
            'is_mine' => $request->user() !== null && $comment->user_id === $request->user()->id,
        ];
    }
}
