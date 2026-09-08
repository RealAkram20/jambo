<?php

namespace Modules\Streaming\app\Services;

use App\Support\UserAgent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Streaming\app\Models\Device;

/**
 * Everything signed into an account, browser and app together.
 *
 * The website's device picker reads Laravel's `sessions` table; an app install
 * writes no session and lives in `devices`. Each list on its own is half the
 * picture, which is a real problem for a viewer: someone who hits their stream
 * cap because of a laptop at home has no way to free the slot from their
 * phone, and vice versa.
 *
 * This is the union. The plan calls for one cap across both
 * (docs/plans/mobile-offline-app.md §4.2), and this is the piece that makes
 * that answerable.
 *
 * **Counting app devices against the cap is off by default.** Turning it on
 * tightens behaviour for viewers who already have both a browser and the app:
 * they would start meeting the picker where they did not before. The switch is
 * `streams.count_app_devices`, and it is Rio's to flip once the app has
 * shipped and the numbers are visible.
 */
class AccountDeviceRegistry
{
    /**
     * Browser sessions signed into this account, most recent first.
     *
     * "Active" matches EnforceDeviceLimit: a row in the sessions table whose
     * last activity is inside the session lifetime.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function browserSessions(int $userId, ?string $currentSessionId = null): Collection
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('last_activity', '>', $this->cutoff())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'kind' => 'browser',
                'name' => $this->describeAgent($row->user_agent ?? ''),
                'ip_address' => $row->ip_address,
                'last_seen_at' => $row->last_activity
                    ? Carbon::createFromTimestamp($row->last_activity)->toIso8601String()
                    : null,
                'is_current' => $currentSessionId !== null && hash_equals($currentSessionId, (string) $row->id),
            ])
            ->values();
    }

    /**
     * App installs signed into this account.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function appDevices(int $userId, ?Device $current = null): Collection
    {
        return Device::query()
            ->where('user_id', $userId)
            ->active()
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn (Device $device) => [
                'id' => $device->uuid,
                'kind' => 'app',
                'name' => $device->name ?: $device->model ?: 'Jambo app',
                'platform' => $device->platform,
                'model' => $device->model,
                'app_version' => $device->app_version,
                'last_seen_at' => optional($device->last_seen_at)->toIso8601String(),
                'is_current' => $current !== null && $current->getKey() === $device->getKey(),
            ])
            ->values();
    }

    /**
     * One list, newest first, for a screen that shows the whole account.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function all(int $userId, ?string $currentSessionId = null, ?Device $currentDevice = null): Collection
    {
        return $this->browserSessions($userId, $currentSessionId)
            ->concat($this->appDevices($userId, $currentDevice))
            ->sortByDesc('last_seen_at')
            ->values();
    }

    /**
     * How many things are signed in and counting against the cap.
     *
     * App devices are included only when the setting says so — see the class
     * docblock. Callers get one number either way, so the decision lives here
     * rather than in every caller.
     */
    public function countAgainstCap(int $userId): int
    {
        $sessions = DB::table('sessions')
            ->where('user_id', $userId)
            ->where('last_activity', '>', $this->cutoff())
            ->count();

        if (! self::appDevicesCount()) {
            return $sessions;
        }

        return $sessions + Device::query()
            ->where('user_id', $userId)
            ->active()
            ->where('last_seen_at', '>', now()->subMinutes($this->lifetimeMinutes()))
            ->count();
    }

    /**
     * Whether app installs count against the concurrent-device cap.
     *
     * Default false, deliberately: switching it on tightens the live site for
     * anyone who has both a browser and the app.
     */
    public static function appDevicesCount(): bool
    {
        try {
            return (bool) setting('streams.count_app_devices', false);
        } catch (\Throwable) {
            // A settings-table problem must never break playback. The safe
            // direction is the current behaviour, which is sessions only.
            return false;
        }
    }

    /** Boot a browser session by deleting its row. */
    public function revokeBrowserSession(int $userId, string $sessionId): bool
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', $sessionId)
            ->delete() > 0;
    }

    private function cutoff(): int
    {
        return now()->subMinutes($this->lifetimeMinutes())->timestamp;
    }

    private function lifetimeMinutes(): int
    {
        return (int) config('session.lifetime', 120);
    }

    /**
     * A human label for a browser row.
     *
     * UserAgent::label is what the website's own picker renders, so the two
     * screens name the same device the same way.
     */
    private function describeAgent(string $agent): string
    {
        return $agent === '' ? 'Unknown browser' : UserAgent::label($agent);
    }
}
