<?php

/*
 * Signed-in devices for the test account. Local database only.
 *
 * WHY THIS STEP EXISTS. `testuser@jambo.test` has whatever the emulator has
 * signed in with — usually one app install and nothing else. The devices
 * screen has an Active section, an Inactive section, a usage meter and a
 * per-row sign-out, and **none of those can be seen with one row.** A screen
 * verified only in its thinnest state is a screen nobody has seen.
 *
 * WHAT THE ROWS ARE FOR. Each exercises something the screen must get right
 * rather than being there to look plausible:
 *
 *   - a DESKTOP browser and a TELEVISION browser, so the icon actually varies
 *     and is proved to come from `kind`/`platform` rather than from parsing
 *     the name;
 *   - a session with a PUBLIC address, which is the only way to see the
 *     location line resolve — or, on a machine with no geolocation database,
 *     to see it fall back to the address, which is the shipped behaviour;
 *   - a session with a PRIVATE address, which must never show a city;
 *   - one browser and one app install seen DAYS ago, so the Inactive section
 *     exists at all;
 *   - a long device name, so the one-line clamp is seen doing its job.
 *
 * THE APP INSTALL THE EMULATOR IS USING IS NEVER TOUCHED. It is the row that
 * carries "This device", and re-writing it would break the only case that
 * cannot be seeded.
 *
 * Browser sessions are written straight into `sessions` with `DB::table()`, so
 * no session is really established and nothing here can sign anybody in. Their
 * ids all start `dev-sess-`, and the seeded app installs' uuids all start
 * `dev-device-`, which is how --reset finds them.
 *
 * Usage:
 *   php artisan tinker --execute="require 'tools/dev-catalogue/14-test-user-devices.php';"
 *   JAMBO_DEVICES_RESET=1 php artisan tinker --execute="require 'tools/dev-catalogue/14-test-user-devices.php';"
 */

use Illuminate\Support\Facades\DB;
use Modules\Streaming\app\Models\Device;

$email = getenv('JAMBO_TEST_EMAIL') ?: 'testuser@jambo.test';
$reset = (bool) getenv('JAMBO_DEVICES_RESET');

$user = App\Models\User::where('email', $email)->first();

if (! $user) {
    echo "No user {$email}. Run 6-test-user.php first.\n";

    return;
}

if ($reset) {
    $sessions = DB::table('sessions')->where('user_id', $user->id)->where('id', 'like', 'dev-sess-%')->delete();
    $installs = Device::query()->where('user_id', $user->id)->where('uuid', 'like', 'dev-device-%')->delete();

    echo "Removed {$sessions} dev-sess- sessions and {$installs} dev-device- installs from {$email}.\n";

    return;
}

/*
 * The agents are real strings, because `AccountDeviceRegistry::describeAgent`
 * parses them. A made-up one produces "Unknown on Unknown", which would look
 * like a screen fault rather than a fixture fault.
 */
$sessions = [
    [
        'id' => 'dev-sess-desktop',
        'agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        // Public, and Ugandan. The only row where a city can appear at all.
        'ip' => '41.210.144.10',
        'minutes' => 4,
    ],
    [
        'id' => 'dev-sess-tv',
        'agent' => 'Mozilla/5.0 (SMART-TV; Linux; Tizen 6.0) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/4.0 Chrome/76.0.3809.146 TV Safari/537.36',
        'ip' => '41.210.144.11',
        'minutes' => 130,
    ],
    [
        // Private: must never resolve to a city, whatever database is present.
        'id' => 'dev-sess-lan',
        'agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
        'ip' => '192.168.1.64',
        'minutes' => 40,
    ],
    [
        // Days old, so the Inactive section has something in it.
        'id' => 'dev-sess-stale',
        'agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'ip' => '41.210.144.12',
        'minutes' => 60 * 24 * 3,
    ],
];

foreach ($sessions as $row) {
    DB::table('sessions')->updateOrInsert(
        ['id' => $row['id']],
        [
            'user_id' => $user->id,
            'ip_address' => $row['ip'],
            'user_agent' => $row['agent'],
            'payload' => '',
            'last_activity' => now()->subMinutes($row['minutes'])->timestamp,
        ],
    );
}

/*
 * App installs. `register()` is not used: it binds a Sanctum token, and these
 * are fixtures that must never be able to authenticate. Written directly, with
 * no `personal_access_token_id`, they list and they sign out, which is all the
 * screen does with them.
 */
$installs = [
    [
        'uuid' => 'dev-device-phone',
        'name' => 'Tecno Spark 10 Pro',
        'model' => 'TECNO KI7',
        'platform' => Device::PLATFORM_ANDROID,
        'ip' => '41.210.144.13',
        'minutes' => 12,
    ],
    [
        // The clamp case. A name nobody would type and every vendor ships.
        'uuid' => 'dev-device-longname',
        'name' => 'Samsung Galaxy S24 Ultra 5G Enterprise Edition (Kampala)',
        'model' => 'SM-S928B',
        'platform' => Device::PLATFORM_ANDROID,
        'ip' => '41.210.144.14',
        'minutes' => 200,
    ],
    [
        'uuid' => 'dev-device-tv',
        'name' => 'Living room TV box',
        'model' => 'MiBOX4',
        'platform' => Device::PLATFORM_ANDROID_TV,
        'ip' => null,
        'minutes' => 60 * 24 * 12,
    ],
];

foreach ($installs as $row) {
    $device = Device::withoutEvents(fn () => Device::firstOrNew([
        'user_id' => $user->id,
        'uuid' => $row['uuid'],
    ]));

    $device->forceFill([
        'user_id' => $user->id,
        'uuid' => $row['uuid'],
        'platform' => $row['platform'],
        'name' => $row['name'],
        'model' => $row['model'],
        'app_version' => '1.0.0',
        'last_ip' => $row['ip'],
        'last_seen_at' => now()->subMinutes($row['minutes']),
        'revoked_at' => null,
        'personal_access_token_id' => null,
    ])->save();
}

$total = Device::query()->where('user_id', $user->id)->active()->count();
$live = DB::table('sessions')->where('user_id', $user->id)->count();

echo "Seeded " . count($sessions) . " browser sessions and " . count($installs) . " app installs for {$email}.\n";
echo "The account now has {$total} active installs and {$live} sessions.\n";
