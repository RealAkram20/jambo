<?php

namespace Modules\Streaming\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Streaming\app\Http\Middleware\EnsureVisitorId;
use Modules\Streaming\app\Models\ActiveStream;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;

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
            'backUrl' => $show ? route('frontend.series_detail', ['slug' => $show->slug]) : url('/'),
            'poster' => $episode->still_url,
            'source' => $source,
            'payableType' => $episode->getMorphClass(),
            'payableId' => $episode->id,
            'resumePosition' => $history?->position_seconds ?? 0,
        ]);
    }

    /**
     * Device picker — the landing page when a user is over their
     * account's concurrent-device cap.
     *
     * Built off Laravel's `sessions` table (SESSION_DRIVER=database),
     * not `active_streams`. The cap is account-level now — every
     * signed-in browser counts, whether or not it's actively streaming
     * — so the picker needs to list every session a user owns.
     *
     * Each session is decorated with:
     *   • browser + OS (parsed from user_agent)
     *   • ip_address, last_activity
     *   • is_current — flags the device rendering this page
     *   • watching — the most recent active_streams row for this
     *                session, so rows can show "Watching: The Dark
     *                Knight" when the device is mid-playback
     *
     * View receives:
     *   tier           — user's current tier (or null if no active sub)
     *   cap            — max_concurrent_streams on that tier
     *   sessions       — Collection of decorated session rows
     *   nextTier       — suggested upgrade tier (SubscriptionTier|null)
     *   intendedUrl    — URL the user was trying to reach
     */
    public function streamLimit(Request $request): View
    {
        $user = $request->user();

        $activeSub = UserSubscription::with('tier')
            ->where('user_id', $user->id)
            ->current()
            ->orderByDesc('ends_at')
            ->first();

        $tier = $activeSub?->tier;
        $cap  = $tier?->max_concurrent_streams;

        $currentSessionId = $request->session()->getId();
        $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;

        $rawSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>', $cutoff)
            ->orderByDesc('last_activity')
            ->get();

        // Decorate each session with its most-recent active premium
        // stream, if any. One query covers all rows — cheaper than
        // one join-per-session.
        $sessionIds = $rawSessions->pluck('id');
        $streams = $sessionIds->isEmpty()
            ? collect()
            : ActiveStream::query()
                ->whereIn('session_id', $sessionIds)
                ->whereNull('terminated_at')
                ->where('last_beat_at', '>', now()->subSeconds(ActiveStream::STREAM_IDLE_SECONDS))
                ->with('watchable')
                ->orderByDesc('last_beat_at')
                ->get()
                ->unique('session_id')
                ->keyBy('session_id');

        $decorated = $rawSessions->map(function ($s) use ($currentSessionId, $streams) {
            $s->is_current = hash_equals($currentSessionId, (string) $s->id);
            $s->watching   = $streams->get($s->id);
            return $s;
        });

        return view('streaming::stream-limit', [
            'tier'        => $tier,
            'cap'         => $cap,
            'sessions'    => $decorated,
            'nextTier'    => SubscriptionTier::nextTierUpFrom($cap),
            'intendedUrl' => $request->session()->get('url.intended'),
        ]);
    }

    /**
     * Boot a specific device — called via AJAX from the picker when
     * the user clicks "Disconnect" on one of their other active
     * sessions.
     *
     *   • Scoped strictly to the authed user's own rows.
     *   • Rejects an attempt to boot the caller's own current session
     *     (user should sign out via the header link, not the picker).
     *   • Terminates ALL active watch_history rows for (user, session)
     *     so the booted device can't just hop to different content
     *     to bypass the cap.
     */
    public function bootSession(Request $request, string $sessionId): JsonResponse
    {
        $user = $request->user();
        $isSelf = hash_equals($request->session()->getId(), $sessionId);

        // Two-layer kill:
        //   1. DELETE the Laravel session row. That device's cookie now
        //      points at nothing — its next request loads a fresh
        //      guest session, hits the auth middleware, and bounces
        //      through /login. This is what makes the cap an account-
        //      level limit: the device loses access to every route,
        //      not just playback.
        //
        //   2. Terminate any active_streams rows for the same session.
        //      An in-flight heartbeat uses this to raise the "signed
        //      out here" overlay instantly (≤15s) instead of waiting
        //      for the browser to hit an auth-required page.
        //
        // Scoped to the authed user's own sessions. A malicious caller
        // can't boot someone else's device by guessing a session id.
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete();

        $terminated = ActiveStream::terminateSession($user->id, $sessionId);

        // Self-boot is allowed at the account level — it's basically
        // a "sign out of this device" click. Tell the client to
        // redirect to the login page rather than try to keep the
        // picker open on a now-invalidated session.
        if ($isSelf) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json([
                'ok' => true,
                'self' => true,
                'terminated_rows' => $terminated,
                'login_url' => route('login'),
            ]);
        }

        return response()->json([
            'ok' => true,
            'terminated_rows' => $terminated,
        ]);
    }

    /**
     * "Take back here" action from the kicked-device overlay. Clears
     * the terminated_at flag on this session's rows, re-runs the
     * cap check, and either confirms resumption or 409s so the
     * overlay can tell the user they're still over cap.
     */
    public function reclaimStream(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentSession = $request->session()->getId();

        $activeSub = UserSubscription::with('tier')
            ->where('user_id', $user->id)
            ->current()
            ->orderByDesc('ends_at')
            ->first();

        $cap = $activeSub?->tier?->max_concurrent_streams;

        // Re-check cap excluding this session — the kicked device is
        // the one asking to come back, so we want to know how many
        // OTHER devices are active right now.
        if ($cap !== null && $cap > 0) {
            $others = ActiveStream::activeCount($user->id, $currentSession);
            if ($others >= $cap) {
                return response()->json([
                    'ok' => false,
                    'error' => 'still_over_cap',
                    'picker_url' => route('streams.limit'),
                ], 409);
            }
        }

        // Clear terminated_at on every active_streams row this session
        // owns so the next heartbeat resumes cleanly.
        ActiveStream::reviveSession($user->id, $currentSession);

        return response()->json(['ok' => true]);
    }

    /**
     * Bounce the user back to whatever they were trying to watch
     * before TierGate sent them to the picker. Picker's "Continue
     * watching" button points here. Uses Laravel's intended() so
     * it handles "URL is stale / nothing stashed" by falling back
     * to the site home.
     */
    public function continueStream(): RedirectResponse
    {
        return redirect()->intended(route('frontend.ott'));
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

        $userId = $request->user()->id;
        $sessionId = $request->session()->getId();

        // Kicked-device check: look up THIS session's active_streams
        // row for this title. Because active_streams is keyed on
        // (user, session, content) a second device playing the same
        // title cannot steal this row — each device has its own. If
        // the picker set terminated_at on this (user, session), the
        // row here carries the flag and we short-circuit with 409 so
        // the player overlays the "signed out" screen.
        $existingActive = ActiveStream::query()
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->where('watchable_type', $model->getMorphClass())
            ->where('watchable_id', $model->getKey())
            ->first();

        if ($existingActive && $existingActive->terminated_at !== null) {
            // Full session kill: logging the user out on this browser so
            // a new tab / page navigation / stream-URL refresh can't
            // keep them watching even if the overlay is closed. The
            // response's Set-Cookie clears their session cookie on the
            // way out; any subsequent request lands in the guest path
            // and bounces through login.
            //
            // We still return 409 with terminated:true so the player JS
            // can raise the overlay BEFORE the browser realises the
            // session is dead — otherwise the kicked user just sees a
            // random "Unauthenticated" redirect with no context.
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'ok' => false,
                'terminated' => true,
                'reason' => 'another_device',
                'login_url' => route('login'),
                'home_url' => route('frontend.ott'),
            ], 409);
        }

        // watch_history continues to own resume position + view count
        // (keyed per (user, title) — session-agnostic). active_streams
        // owns the live session signal (keyed per (user, session,
        // title)) that the cap + picker + kick flow depend on.
        $row = WatchHistoryItem::record(
            userId: $userId,
            item: $model,
            position: (int) $data['position'],
            duration: isset($data['duration']) ? (int) $data['duration'] : null,
            sessionId: $sessionId,
        );

        ActiveStream::markBeat($userId, $sessionId, $model);

        return response()->json([
            'ok' => true,
            'position' => $row->position_seconds,
            'completed' => $row->completed,
        ]);
    }

    /**
     * Guest view counter. Logged-in users hit the heartbeat above (which
     * upserts watch_history and increments views_count on first watch).
     * Guests can't reach heartbeat (it's auth-only) and that's fine —
     * heartbeat needs a user_id. This endpoint is the parallel path:
     * accept a (visitor_cookie, content_id) tuple, dedupe via the unique
     * index on guest_views, and bump the public views_count on first
     * sight per device.
     *
     * Premium content stays excluded — guests can't actually play it (the
     * watch route is tier-gated to the login flow), so a guest hitting
     * this endpoint with a premium ID is either a misconfig or someone
     * scripting; we no-op rather than count.
     */
    public function guestView(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:movie,episode',
            'id'   => 'required|integer|min:1',
        ]);

        $visitor = $request->cookie(EnsureVisitorId::COOKIE_NAME);
        if (!$visitor) {
            // Middleware should have set it on this same request; if it
            // isn't here something stripped cookies. Fail soft.
            return response()->json(['ok' => false, 'reason' => 'no_visitor'], 200);
        }

        $content = $data['type'] === 'movie'
            ? Movie::find($data['id'])
            : Episode::with('season')->find($data['id']);

        if (!$content) {
            return response()->json(['ok' => false, 'reason' => 'not_found'], 200);
        }

        // Guests only count on free content. Anything tier-gated would
        // never have made it past TierGate to play in the first place.
        if (!empty($content->tier_required)) {
            return response()->json(['ok' => false, 'reason' => 'tier_gated'], 200);
        }

        $morphType = $content->getMorphClass();
        $morphId   = $content->getKey();

        try {
            DB::table('guest_views')->insert([
                'visitor_id'     => $visitor,
                'watchable_type' => $morphType,
                'watchable_id'   => $morphId,
                'created_at'     => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate key on the unique index = this device has already
            // counted on this content. Idempotent: return 200, no count.
            $sqlState = $e->errorInfo[0] ?? null;
            $errCode  = $e->errorInfo[1] ?? null;
            if ($sqlState === '23000' || in_array($errCode, [1062, 19], true)) {
                return response()->json(['ok' => true, 'counted' => false]);
            }
            throw $e;
        }

        // Fresh row — same accounting rule as the authed path: movies
        // carry their own views_count, episodes credit the parent show.
        if ($content instanceof Movie) {
            Movie::whereKey($morphId)->increment('views_count');
        } elseif ($content instanceof Episode) {
            $showId = $content->season?->show_id;
            if ($showId) {
                Show::whereKey($showId)->increment('views_count');
            }
        }

        return response()->json(['ok' => true, 'counted' => true]);
    }
}
