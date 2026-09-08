<?php

namespace Modules\Streaming\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Streaming\app\Models\Device;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Streaming\app\Playback\PlaybackDecision;
use Modules\Streaming\app\Services\PlaybackAuthorizer;
use Modules\Streaming\app\Services\PlaybackBeatRecorder;
use Modules\Streaming\app\Services\StreamSourceResolver;

/**
 * Starting and sustaining playback from the app.
 *
 * Two endpoints, and neither contains a rule of its own. `sessions` asks
 * PlaybackAuthorizer the same question TierGate asks for the website and
 * StreamSourceResolver the same question StreamProxyController asks;
 * `heartbeat` calls the same PlaybackBeatRecorder the web heartbeat does. That
 * is the entire point of the 1.8.22 extraction: an app viewer and a browser
 * viewer are gated by one implementation, so they cannot drift.
 *
 * The one real difference from the website is the hop: a browser follows a 302
 * from /watch/src/ and lets the CDN URL appear in its network tab. The app
 * asks for the resolved URL and hands it straight to a native player, so the
 * URL never lands in a WebView or a log.
 */
class PlaybackController extends Controller
{
    /**
     * How often the app should beat, in seconds.
     *
     * Matches the website's 15s. It is sent to the client rather than hard-
     * coded in the app because a shipped APK cannot be re-tuned: if beats ever
     * cost too much on the KVM 2, this number moves server-side and every
     * installed app slows down without a Play release.
     */
    private const HEARTBEAT_SECONDS = 15;

    public function __construct(
        private readonly PlaybackAuthorizer $authorizer,
        private readonly StreamSourceResolver $source,
        private readonly PlaybackBeatRecorder $recorder,
    ) {
    }

    /**
     * Open a playback session: check entitlement, then hand back a URL.
     *
     * The authorizer is given this device's key, so starting a stream counts
     * against max_concurrent_streams exactly as opening a browser tab does.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:movie,episode'],
            'id' => ['required', 'integer'],
            'quality' => ['nullable', 'string', 'in:' . implode(',', StreamSourceResolver::QUALITIES)],
        ]);

        $content = $this->findContent($data['type'], $data['id']);

        if (! $content) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That title does not exist.');
        }

        $decision = $this->authorizer->authorizeStream(
            $content,
            $request->user(),
            $this->sessionKey($request),
        );

        if ($decision->denied()) {
            return $this->denied($decision);
        }

        // A YouTube-sourced title has no file of ours to hand over. The app
        // must open it in an embed, and it can never be downloaded.
        if ($this->source->isEmbed($content)) {
            return ApiResponse::ok([
                'source' => 'embed',
                'embed_url' => $content->streamSource()['embed_url'] ?? null,
                'resume_position' => $this->resumePosition($request, $content),
                'heartbeat_seconds' => self::HEARTBEAT_SECONDS,
            ]);
        }

        $quality = $data['quality'] ?? StreamSourceResolver::QUALITY_DEFAULT;
        $url = $this->source->resolve($content, $quality);

        // A Data Saver request for a title with no low rendition is refused
        // rather than quietly served at full size: silently spending someone's
        // bundle is the one thing Data Saver exists to prevent. The app can
        // retry at default quality once it has told the viewer.
        if (! $url) {
            return ApiResponse::error(
                ApiErrorCode::ContentUnavailable,
                $quality === StreamSourceResolver::QUALITY_LOW
                    ? 'This title has no data saver version.'
                    : 'This title has no video yet.',
            );
        }

        return ApiResponse::ok([
            'source' => 'file',
            'url' => $url,
            'quality' => $quality,
            'available_qualities' => $this->source->availableQualities($content),
            'resume_position' => $this->resumePosition($request, $content),
            'runtime_minutes' => $content->runtime_minutes,
            'heartbeat_seconds' => self::HEARTBEAT_SECONDS,
        ]);
    }

    /**
     * Keep the session alive: resume position, the live-stream signal the cap
     * reads, and the event partner earnings accrue from.
     *
     * A 409 means this device was booted from the account while it was
     * playing. The website answers that by killing the browser session; here
     * the token is already gone, so the app simply stops and signs out.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:movie,episode'],
            'id' => ['required', 'integer'],
            'position' => ['required', 'integer', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:1'],
        ]);

        $content = $this->findContent($data['type'], $data['id']);

        if (! $content) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That title does not exist.');
        }

        $outcome = $this->recorder->record(
            userId: $request->user()->id,
            content: $content,
            position: (int) $data['position'],
            duration: isset($data['duration']) ? (int) $data['duration'] : null,
            sessionKey: $this->sessionKey($request),
            ip: $request->ip(),
        );

        if ($outcome->terminated) {
            return ApiResponse::error(
                ApiErrorCode::DeviceRevoked,
                'This device was signed out from your account.',
                status: 409,
            );
        }

        return ApiResponse::ok([
            'position' => $outcome->position,
            'completed' => $outcome->completed,
        ]);
    }

    // ── internals ────────────────────────────────────────────────────

    private function findContent(string $type, int $id): Movie|Episode|null
    {
        return $type === 'movie'
            ? Movie::find($id)
            : Episode::with('season.show')->find($id);
    }

    /**
     * This device's key for active_streams.
     *
     * The device uuid, so an app install occupies exactly one slot in the
     * concurrency cap and appears in the device picker alongside browser
     * sessions. A token with no device row is not something the app produces,
     * but it must still get a stable key rather than share one with every
     * other such token — otherwise two of them would look like one device.
     */
    private function sessionKey(Request $request): string
    {
        $token = $request->user()->currentAccessToken();
        $device = Device::forToken($token);

        return $device?->uuid ?? 'token:' . $token?->getKey();
    }

    private function resumePosition(Request $request, Movie|Episode $content): int
    {
        $history = WatchHistoryItem::query()
            ->where('user_id', $request->user()->id)
            ->where('watchable_type', $content->getMorphClass())
            ->where('watchable_id', $content->getKey())
            ->first();

        // A finished title starts again from the beginning; resuming three
        // seconds before the credits is not resuming.
        return ($history && ! $history->completed) ? (int) $history->position_seconds : 0;
    }

    /**
     * Turn a refusal into the app's answer.
     *
     * The codes are the same strings PlaybackDenial uses, which ApiErrorCodeTest
     * guarantees, so the mapping is a lookup rather than a second switch that
     * could disagree with the first.
     */
    private function denied(PlaybackDecision $decision): JsonResponse
    {
        $code = ApiErrorCode::tryFrom($decision->code()) ?? ApiErrorCode::Forbidden;

        return ApiResponse::error(
            $code,
            $decision->message(),
            extra: $decision->requiredTier ? [
                'required_tier' => [
                    'name' => $decision->requiredTier->name,
                    'slug' => $decision->requiredTier->slug,
                    'access_level' => $decision->requiredTier->access_level,
                ],
            ] : [],
        );
    }
}
