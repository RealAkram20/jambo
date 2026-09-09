<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * How a viewer wants their video delivered.
 *
 * Rio's ruling on 2026-09-09: the mobile app is a pure streaming experience,
 * so the settings it carries are the ones about watching, and they belong to
 * the account rather than to the handset — "all can edit and update their
 * streaming preferences". Signing in on a second device brings your choices
 * with you, and so will the television in Phase 4.
 *
 * **Every option here maps onto something the platform can actually do**, and
 * the temptation this class exists to resist is the ladder of quality labels
 * every streaming app has. Jambo has exactly two renditions — `video_url` and
 * `video_url_low`, and `/playback/sessions` takes `quality: default|low` — so
 * a menu offering 1080p, 720p and 480p would be three names for two files.
 * The three values below are the honest expression of two renditions plus the
 * decision to choose between them automatically.
 *
 * Subtitles are deliberately absent. The platform has no subtitle or caption
 * track anywhere in the Streaming or Content modules, and a preference for
 * something that cannot be delivered is a promise the player then breaks.
 */
class StreamingPreferencesController extends Controller
{
    /**
     * What a viewer gets before they have ever opened this screen.
     *
     * `auto` rather than `high`, because the default has to be right for
     * somebody on MTN data who has never thought about it. Autoplay on,
     * because the website's player switch is the behaviour people already
     * have. Wi-Fi-only downloads on, because the alternative default spends
     * somebody's bundle without asking — and on this network that is real
     * money.
     */
    public const DEFAULTS = [
        'video_quality' => 'auto',
        'autoplay_next' => true,
        'wifi_only_downloads' => true,
    ];

    /** The three renditions policies, and what each one asks the player for. */
    public const QUALITIES = ['auto', 'high', 'data_saver'];

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::ok(['preferences' => $this->preferences($request->user())]);
    }

    /**
     * Change some of them.
     *
     * PATCH and `sometimes` rather than PUT: a screen with three switches
     * sends the one that moved. A PUT would make every client resend the whole
     * set on every toggle, and any client that forgot a field would silently
     * reset it to the default — which is how a viewer's data-saver setting
     * turns itself off when they change an unrelated switch.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'video_quality' => ['sometimes', 'string', 'in:' . implode(',', self::QUALITIES)],
            'autoplay_next' => ['sometimes', 'boolean'],
            'wifi_only_downloads' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        /*
         * Merge onto what is stored, then onto the defaults — in that order.
         * Storing the merged whole rather than only the changed keys means a
         * later addition to DEFAULTS reaches accounts that were saved before
         * it existed, without a backfill.
         */
        $merged = array_merge($this->preferences($user), $data);

        $user->streaming_preferences = $merged;
        $user->save();

        return ApiResponse::ok(['preferences' => $merged]);
    }

    /**
     * The stored set, filled in from the defaults.
     *
     * Two things are deliberate. Unknown keys are dropped, so a value written
     * by a newer build cannot leak back to an older one that has no idea what
     * it means. And the shape is always complete, so no client ever branches
     * on a missing key — null-checking a preference is how a boolean setting
     * becomes three states.
     *
     * @return array<string, mixed>
     */
    private function preferences($user): array
    {
        $stored = $user->streaming_preferences;

        if (! is_array($stored)) {
            return self::DEFAULTS;
        }

        $out = [];

        foreach (self::DEFAULTS as $key => $default) {
            $out[$key] = array_key_exists($key, $stored) ? $stored[$key] : $default;
        }

        // A quality this build does not recognise falls back rather than being
        // handed to the player, which would ask Bunny for a rendition that
        // does not exist and give the viewer a spinner with no error.
        if (! in_array($out['video_quality'], self::QUALITIES, true)) {
            $out['video_quality'] = self::DEFAULTS['video_quality'];
        }

        $out['autoplay_next'] = (bool) $out['autoplay_next'];
        $out['wifi_only_downloads'] = (bool) $out['wifi_only_downloads'];

        return $out;
    }
}
