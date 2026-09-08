<?php

namespace Modules\Streaming\app\Http\Middleware;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Modules\Streaming\app\Models\Device;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a signed-in app install honest on every authenticated API request.
 *
 * Booting a device deletes its Sanctum token, so the primary revocation is
 * already absolute: the next request fails authentication before it reaches
 * here. This middleware is the second line, and it exists for the case that
 * actually goes wrong in production — a token that outlives its device row
 * because a revoke half-completed, or a row restored from a backup taken
 * before the boot.
 *
 * It also carries the cheap half of the job: stamping last_seen_at, so the
 * device list can say "2 minutes ago" and the concurrency count can tell a TV
 * that is on from one that was unplugged last March.
 *
 * A request with a token but no device row is allowed through. Not every
 * token has to come from an app install, and refusing here would be a fine
 * way to lock everyone out the first time something issues a token by another
 * route.
 */
class EnsureDeviceIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $device = Device::forToken($user->currentAccessToken());

        if (! $device) {
            return $next($request);
        }

        if ($device->isRevoked()) {
            return ApiResponse::error(
                ApiErrorCode::DeviceRevoked,
                'This device was signed out from your account.',
            );
        }

        // saveQuietly, and only when the stamp is actually stale: a player
        // beating every 15 seconds must not write this row every beat.
        if (! $device->last_seen_at || $device->last_seen_at->lt(now()->subMinute())) {
            $device->touchSeen($request->header('X-App-Version'));
        }

        return $next($request);
    }
}
