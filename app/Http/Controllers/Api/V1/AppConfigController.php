<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * What the app needs to know before it knows who is using it.
 *
 * Called unauthenticated on launch. It exists so that a shipped APK can be
 * told things after it shipped: that it is too old to talk to this server,
 * that a feature is off, or what the server thinks the time is.
 *
 * The clock matters more than it looks. Offline licences are enforced against
 * the device's own clock (ADR-0003), and a viewer who winds it back must not
 * gain a month of downloads. The app anchors on the last server timestamp it
 * saw and refuses offline playback if the wall clock has moved backwards from
 * it, so this endpoint is where that anchor is refreshed.
 */
class AppConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return ApiResponse::ok([
            // Below this, the app must show an update wall rather than trying
            // to talk to endpoints it may not understand. A setting rather
            // than a constant, so raising it does not need a deploy.
            'min_app_version' => (string) setting('app.min_version', '1.0.0'),
            'server_time' => now()->toIso8601String(),
            'features' => [
                // Downloads are Phase 3. The flag exists now so the app can
                // ship its Downloads screen dark and have it lit server-side,
                // rather than needing a Play review to turn it on.
                'downloads' => (bool) setting('app.downloads_enabled', false),
                // ADR-0004: the Play build may not mention payment. The build
                // itself decides that, but the server can force it off.
                'in_app_subscribe' => (bool) setting('app.in_app_subscribe', false),
            ],
            'support' => [
                'site_url' => config('app.url'),
            ],
        ]);
    }
}
