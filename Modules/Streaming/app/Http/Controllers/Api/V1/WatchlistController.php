<?php

namespace Modules\Streaming\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\app\Http\Resources\EpisodeResource;
use Modules\Content\app\Http\Resources\MovieResource;
use Modules\Content\app\Http\Resources\ShowResource;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Frontend\app\Services\HomeRailsService;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Streaming\app\Models\WatchlistItem;

/**
 * The viewer's own lists: watchlist, Continue Watching, and history.
 *
 * The website toggles a watchlist entry through one endpoint, because a heart
 * icon is a toggle. The app gets POST to add and DELETE to remove instead:
 * a toggle over a flaky mobile connection is a coin flip — a retried request
 * silently undoes the first one — while add and remove can be retried safely.
 * Both write the same rows through WatchlistItem::addFor, so the two clients
 * cannot disagree about what is on a list.
 *
 * Continue Watching is served from HomeRailsService, the same source the home
 * screen and the website use, rather than a second query with its own idea of
 * dedupe and ordering.
 */
class WatchlistController extends Controller
{
    private const HISTORY_PER_PAGE = 30;

    /** The morph types a viewer may put on a list. */
    private const TYPES = ['movie', 'show', 'episode'];

    public function index(Request $request): JsonResponse
    {
        $items = WatchlistItem::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->with(['watchable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Movie::class => ['genres'],
                    Show::class => ['genres'],
                    Episode::class => ['season.show'],
                ]);
            }])
            ->get()
            // A title deleted or unpublished since it was saved leaves a row
            // pointing at nothing. Drop it rather than sending a null card.
            ->map(fn (WatchlistItem $row) => $this->card($request, $row->watchable))
            ->filter()
            ->values();

        return ApiResponse::ok(['items' => $items]);
    }

    /**
     * Add a title to the watchlist. Idempotent: adding something already on
     * the list succeeds and changes nothing, so a retry after a dropped
     * connection is safe.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', self::TYPES)],
            'id' => ['required', 'integer'],
        ]);

        $model = $this->find($data['type'], $data['id']);

        if (! $model) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That title does not exist.');
        }

        $existing = $this->rowFor($request, $model);

        if (! $existing) {
            WatchlistItem::addFor($request->user()->id, $model);
        }

        return ApiResponse::ok(['in_list' => true], 'Added to your watchlist.');
    }

    /**
     * Remove a title. Also idempotent: removing something that is not there
     * is a success, not a 404, because the viewer's intent is satisfied
     * either way and a retry must not fail.
     */
    public function destroy(Request $request, string $type, int $id): JsonResponse
    {
        if (! in_array($type, self::TYPES, true)) {
            return ApiResponse::error(ApiErrorCode::ValidationFailed, 'That is not a title type.');
        }

        $model = $this->find($type, $id);

        if ($model && $row = $this->rowFor($request, $model)) {
            $row->delete();
        }

        return ApiResponse::ok(['in_list' => false], 'Removed from your watchlist.');
    }

    /**
     * Continue Watching as its own screen. Same cards the home rail carries,
     * from the same service, so the two can never disagree about what the
     * viewer was watching or where they stopped.
     */
    public function continueWatching(Request $request, HomeRailsService $rails): JsonResponse
    {
        $items = $rails->continueWatchingForUser()->map(fn ($card) => [
            'title' => $card->title,
            'subtitle' => $card->subtitle,
            'image_url' => media_url($card->imagePath),
            'progress_percent' => $card->progressPercent,
            'minutes_left' => $card->minutesLeft,
            'position_seconds' => $card->positionSeconds,
            'resume' => ['type' => $card->resumeType, 'id' => $card->resumeId],
            'remove' => ['type' => $card->removeType, 'id' => $card->removeId],
        ])->values();

        return ApiResponse::ok(['items' => $items]);
    }

    /**
     * Everything watched, newest first, finished titles included — this is a
     * record rather than a to-do list, which is what separates it from
     * Continue Watching.
     */
    public function history(Request $request): JsonResponse
    {
        $rows = WatchHistoryItem::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('watched_at')
            ->with(['watchable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Movie::class => ['genres'],
                    Episode::class => ['season.show'],
                ]);
            }])
            ->cursorPaginate(self::HISTORY_PER_PAGE);

        $items = $rows->getCollection()
            ->map(function (WatchHistoryItem $row) use ($request) {
                $card = $this->card($request, $row->watchable);

                return $card === null ? null : [
                    'item' => $card,
                    'position_seconds' => (int) $row->position_seconds,
                    'progress_percent' => $row->progressPercent(),
                    'completed' => (bool) $row->completed,
                    'watched_at' => optional($row->watched_at)->toIso8601String(),
                ];
            })
            ->filter()
            ->values();

        return ApiResponse::ok([
            'items' => $items,
            'next_cursor' => $rows->nextCursor()?->encode(),
        ]);
    }

    // ── internals ────────────────────────────────────────────────────

    private function find(string $type, int $id): ?Model
    {
        return match ($type) {
            'movie' => Movie::find($id),
            'show' => Show::find($id),
            'episode' => Episode::with('season.show')->find($id),
            default => null,
        };
    }

    private function rowFor(Request $request, Model $model): ?WatchlistItem
    {
        return WatchlistItem::query()
            ->where('user_id', $request->user()->id)
            ->where('watchable_type', $model->getMorphClass())
            ->where('watchable_id', $model->getKey())
            ->first();
    }

    /** @return array<string, mixed>|null */
    private function card(Request $request, ?Model $watchable): ?array
    {
        return match (true) {
            $watchable instanceof Movie => MovieResource::card($watchable)->toArray($request),
            $watchable instanceof Show => ShowResource::card($watchable)->toArray($request),
            $watchable instanceof Episode => (new EpisodeResource($watchable))->toArray($request),
            default => null,
        };
    }
}
