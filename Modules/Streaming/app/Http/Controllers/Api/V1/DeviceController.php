<?php

namespace Modules\Streaming\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Streaming\app\Models\Device;
use Modules\Streaming\app\Services\AccountDeviceRegistry;
use Modules\Subscriptions\app\Models\UserSubscription;

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
    public function index(Request $request, AccountDeviceRegistry $registry): JsonResponse
    {
        $current = Device::forToken($request->user()->currentAccessToken());

        // Browser sessions AND app installs. Either list alone is half the
        // account: a viewer who hits their stream cap because of a laptop at
        // home needs to free that slot from their phone.
        $devices = $registry->all($request->user()->id, currentDevice: $current);

        /*
         * The usage meter on the app's devices screen, added 2026-09-09.
         *
         * **It is a concurrency limit, not a device limit, and the two are
         * easy to conflate.** Rio's mockup drew "4 of 6 devices used", which
         * describes a cap Jambo does not have: nothing stops an account
         * registering a hundred installs. What the tier actually enforces is
         * how many can *watch at once*, and that is what these two numbers
         * are. Naming them `watching`/`limit` rather than `used`/`total` is
         * the whole difference between a true meter and a plausible one.
         *
         * `countAgainstCap` is the same method `TierGate` calls before letting
         * a stream start, so the bar cannot say a viewer has room when the
         * player is about to refuse them.
         *
         * A null limit is a tier with no cap, and the app draws no bar for it.
         * Zero would read as "you may watch on nothing".
         */
        $limit = UserSubscription::with('tier')
            ->where('user_id', $request->user()->id)
            ->current()
            ->orderByDesc('ends_at')
            ->first()
            ?->tier?->max_concurrent_streams;

        return ApiResponse::ok([
            'devices' => $devices,
            'counts_app_devices' => AccountDeviceRegistry::appDevicesCount(),
            'streams' => [
                'watching' => $registry->countAgainstCap($request->user()->id),
                'limit' => $limit === null ? null : (int) $limit,
            ],
        ]);
    }

    /**
     * Boot a device: its token is deleted and anything it was playing stops
     * counting against the cap.
     *
     * Scoped to the caller's own devices, and a device belonging to someone
     * else answers DEVICE_NOT_FOUND rather than a 403 — a 403 would confirm
     * that the uuid exists on some other account.
     */
    public function destroy(Request $request, string $uuid, AccountDeviceRegistry $registry): JsonResponse
    {
        $userId = $request->user()->id;

        $device = Device::query()
            ->where('user_id', $userId)
            ->where('uuid', $uuid)
            ->active()
            ->first();

        if ($device) {
            $device->revoke();

            return ApiResponse::ok(null, 'Device signed out.');
        }

        // Not an app install - it may be a browser session. Deleting the row
        // is what the website's own picker does, and it is what frees a slot
        // for a viewer stuck behind a laptop they are nowhere near.
        if ($registry->revokeBrowserSession($userId, $uuid)) {
            return ApiResponse::ok(null, 'That browser has been signed out.');
        }

        return ApiResponse::error(
            ApiErrorCode::DeviceNotFound,
            'That device is not signed in to this account.',
        );
    }
}
