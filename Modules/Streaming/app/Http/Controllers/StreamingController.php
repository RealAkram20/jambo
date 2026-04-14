<?php

namespace Modules\Streaming\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Streaming\app\Models\WatchHistoryItem;

/**
 * Player + watch-history endpoints.
 *
 *   GET  /watch/movie/{movie:slug}        → watch page (gated by tier_gate)
 *   GET  /watch/episode/{episode}         → watch page (gated by tier_gate)
 *   POST /api/v1/streaming/heartbeat      → progress upsert (auth:sanctum)
 *
 * Gating happens in the `tier_gate` middleware on the route, so the
 * controller methods can assume the caller is authorised for this asset.
 */
class StreamingController extends Controller
{
    public function watchMovie(Movie $movie): View
    {
        $source = $movie->streamSource();

        $history = WatchHistoryItem::where('user_id', auth()->id())
            ->where('watchable_type', $movie->getMorphClass())
            ->where('watchable_id', $movie->id)
            ->first();

        return view('streaming::watch', [
            'title' => $movie->title,
            'backLabel' => 'Back to movie',
            'backUrl' => route('frontend.movie_detail', ['slug' => $movie->slug]),
            'poster' => $movie->backdrop_url ?: $movie->poster_url,
            'source' => $source,
            'payableType' => $movie->getMorphClass(),
            'payableId' => $movie->id,
            'resumePosition' => $history?->position_seconds ?? 0,
        ]);
    }

    public function watchEpisode(Episode $episode): View
    {
        $episode->load('season.show');
        $source = $episode->streamSource();
        $show = $episode->season?->show;

        $history = WatchHistoryItem::where('user_id', auth()->id())
            ->where('watchable_type', $episode->getMorphClass())
            ->where('watchable_id', $episode->id)
            ->first();

        return view('streaming::watch', [
            'title' => ($show?->title ?? 'Episode') . ' — S' . ($episode->season?->number ?? '?') . 'E' . $episode->number . ' · ' . $episode->title,
            'backLabel' => 'Back to series',
            'backUrl' => $show ? route('frontend.tvshow_detail', ['slug' => $show->slug]) : url('/'),
            'poster' => $episode->still_url,
            'source' => $source,
            'payableType' => $episode->getMorphClass(),
            'payableId' => $episode->id,
            'resumePosition' => $history?->position_seconds ?? 0,
        ]);
    }

    /**
     * Player heartbeat. Expected payload:
     *   { payable_type: "movie"|"episode", payable_id: 42,
     *     position: 123, duration: 5400 }
     *
     * Keeps the watchable_type contract narrow (only morph keys for
     * Movie + Episode) so the client can't write rows pointing at
     * arbitrary models. Duration is optional — early heartbeats fire
     * before metadata has loaded.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payable_type' => 'required|in:movie,episode',
            'payable_id' => 'required|integer',
            'position' => 'required|integer|min:0',
            'duration' => 'nullable|integer|min:1',
        ]);

        $model = match ($data['payable_type']) {
            'movie' => Movie::find($data['payable_id']),
            'episode' => Episode::find($data['payable_id']),
        };

        if (!$model) {
            return response()->json(['ok' => false, 'error' => 'not found'], 404);
        }

        $row = WatchHistoryItem::record(
            userId: $request->user()->id,
            item: $model,
            position: (int) $data['position'],
            duration: isset($data['duration']) ? (int) $data['duration'] : null,
        );

        return response()->json([
            'ok' => true,
            'position' => $row->position_seconds,
            'completed' => $row->completed,
        ]);
    }
}
