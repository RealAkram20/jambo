<?php

namespace Modules\Streaming\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Streaming\app\Models\Device;

/**
 * The account's app installs: what is signed in, and booting one.
 *
 * This is the app-side twin of the website's device picker, which reads the
 * Laravel `sessions` table. Between them they are the whole picture of an
 * account, which is what the plan means by counting web sessions and app
 * devices against one cap (docs/plans/mobile-offline-app.md §4.2).
 */
class DeviceController extends Controller
{
    /**
     * Every install signed into this account, most recently seen first.
     *
     * Revoked devices are excluded rather than shown greyed out: a booted
     * device is gone, and listing it invites someone to try to boot it twice.
     */
    public function index(Request $request): JsonResponse
    {
        $current = Device::forToken($request->user()->currentAccessToken());

        $devices = Device::query()
            ->where('user_id', $request->user()->id)
            ->active()
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn (Device $device) => [
                'uuid' => $device->uuid,
                'name' => $device->name,
                'model' => $device->model,
                'platform' => $device->platform,
                'app_version' => $device->app_version,
                'last_seen_at' => optional($device->last_seen_at)->toIso8601String(),
                // So the app can label one row "This device" and think twice
                // before offering to sign it out from its own list.
                'is_current' => $current !== null && $current->getKey() === $device->getKey(),
            ]);

        return ApiResponse::ok(['devices' => $devices]);
    }

    /**
     * Boot a device: its token is deleted and anything it was playing stops
     * counting against the cap.
     *
     * Scoped to the caller's own devices, and a device belonging to someone
     * else answers DEVICE_NOT_FOUND rather than a 403 — a 403 would confirm
     * that the uuid exists on some other account.
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $device = Device::query()
            ->where('user_id', $request->user()->id)
            ->where('uuid', $uuid)
            ->active()
            ->first();

        if (! $device) {
            return ApiResponse::error(
                ApiErrorCode::DeviceNotFound,
                'That device is not signed in to this account.',
            );
        }

        $device->revoke();

        return ApiResponse::ok(null, 'Device signed out.');
    }
}
