<?php

/*
 * Notifications for the test account. Local database only.
 *
 * WHY THIS STEP EXISTS. `testuser@jambo.test` has an empty inbox, so the app's
 * notification screen renders its empty state and nothing else. A screen
 * verified only in its empty state is a screen nobody has seen: the day
 * headings, the chips, the unread tint, the poster branch, the icon-tile
 * branch and the cursor all need rows to exist at all.
 *
 * WHAT THE ROWS ARE FOR. Each one exercises something the screen has to get
 * right rather than being there to look plausible:
 *
 *   - a movie and an episode carrying a REAL poster, which is the only way to
 *     see the 40x40 image branch and whether the crop is right;
 *   - one row in each of the five tile tones, because the site's
 *     `bg-{colour}-subtle` pairs differ per tone and the app deviates the
 *     glyph colour on all five;
 *   - read and unread rows side by side, so the blue tint and the dot are
 *     visible against a row that has neither;
 *   - rows dated today, this week and a month back, which is the only way the
 *     three day headings appear;
 *   - a title long enough to truncate and a message long enough to reach three
 *     lines, so the one-line and two-line clamps are seen doing their job;
 *   - a row with no message and no image at all, which is the thinnest row the
 *     component can be asked to draw;
 *   - an admin broadcast, because it is the only thing the Offers chip
 *     filters and an empty chip proves nothing;
 *   - and enough rows in total to cross the 30-row page, so `next_cursor` and
 *     the infinite scroll are exercised instead of assumed.
 *
 * THE ROWS ARE INERT. They are written straight into `notifications` with
 * `DB::table()`, so no notification is dispatched, no channel gate runs, no
 * mail leaves the machine and no queue job is created. The `type` column
 * carries the real class name because that is what the category filter reads.
 *
 * Every id starts `dev-notif-`, which is how --reset finds them and how a
 * human reading the table can tell them from real ones. Three sessions share
 * this database; nothing outside that prefix is touched.
 *
 * Usage:
 *   php artisan tinker --execute="require 'tools/dev-catalogue/12-notifications.php';"
 *   JAMBO_NOTIF_RESET=1 php artisan tinker --execute="require 'tools/dev-catalogue/12-notifications.php';"
 */

use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\Movie;

$email = getenv('JAMBO_TEST_EMAIL') ?: 'testuser@jambo.test';
$reset = (bool) getenv('JAMBO_NOTIF_RESET');

$user = App\Models\User::where('email', $email)->first();

if (! $user) {
    echo "No user {$email}. Run 6-test-user.php first.\n";

    return;
}

if ($reset) {
    $gone = DB::table('notifications')
        ->where('notifiable_id', $user->id)
        ->where('id', 'like', 'dev-notif-%')
        ->delete();

    echo "Removed {$gone} dev-notif- rows from {$email}.\n";

    return;
}

$ns = 'Modules\\Notifications\\app\\Notifications\\';

/*
 * Real titles, looked up rather than invented, so the poster a row shows is a
 * poster that exists in this database and the crop can actually be judged.
 */
$films = Movie::query()
    ->whereNotNull('poster_url')
    ->where('poster_url', '!=', '')
    ->orderBy('id')
    ->take(2)
    ->get(['title', 'slug', 'poster_url']);

if ($films->count() < 2) {
    echo "Fewer than two movies with a poster in this database. Run the catalogue steps first.\n";

    return;
}

$first = $films[0];
$second = $films[1];

/** @var array<int, array{0:string,1:string,2:array<string,mixed>,3:int,4:bool}> */
$rows = [
    // id suffix, class, data, minutes ago, read
    ['0001', 'MovieAddedNotification', [
        'title' => 'New movie added',
        'message' => "“{$first->title}” just arrived in the Jambo catalogue.",
        'icon' => 'ph-film-strip',
        'colour' => 'primary',
        'image' => $first->poster_url,
    ], 2, false],

    ['0002', 'EpisodeAddedNotification', [
        'title' => 'New episode',
        'message' => "Episode 5 of “{$second->title}” is now streaming.",
        'icon' => 'ph-play-circle',
        'colour' => 'primary',
        'image' => $second->poster_url,
    ], 61, false],

    // The five tones, one row each. No image, so the icon tile is what draws.
    ['0003', 'SubscriptionActivatedNotification', [
        'title' => 'Premium is active',
        'message' => 'Early access to new releases, and no ads.',
        'icon' => 'ph-crown-simple',
        'colour' => 'success',
    ], 300, false],

    ['0004', 'SubscriptionExpiringNotification', [
        'title' => 'Your plan renews in 3 days',
        'message' => 'Nothing to do — this is a heads up.',
        'icon' => 'ph-hourglass-medium',
        'colour' => 'warning',
    ], 420, true],

    ['0005', 'PaymentFailedNotification', [
        'title' => 'Payment declined',
        'message' => 'Your last payment did not go through. Update your method to keep watching.',
        'icon' => 'ph-warning-octagon',
        'colour' => 'danger',
    ], 600, true],

    ['0006', 'WatchlistAvailableNotification', [
        'title' => 'On your watchlist: now available',
        'message' => 'Something you saved is ready to watch.',
        'icon' => 'ph-bookmark-simple',
        'colour' => 'info',
        'kind' => 'movie',
    ], 700, true],

    // The Offers chip's only occupant.
    ['0007', 'AdminBroadcastNotification', [
        'title' => 'Special offer',
        'message' => 'Get 30% off your next monthly plan. Limited time only.',
        'icon' => 'ph-megaphone',
        'colour' => 'primary',
    ], 2880, false],

    ['0008', 'NewDeviceLoginNotification', [
        'title' => 'New login',
        'message' => 'A new device signed in to your account from Kampala, Uganda.',
        'icon' => 'ph-device-mobile',
        'colour' => 'warning',
    ], 4320, true],

    // The clamps. One line for the title, two for the message.
    ['0009', 'ShowAddedNotification', [
        'title' => 'A series with a deliberately very long name that cannot fit on one line of a handset',
        'message' => 'This message is long on purpose. It runs past two lines so the clamp can be '
            . 'seen doing its job rather than assumed, and so the row height stays the same as '
            . 'every other row in the list instead of growing to fit whatever the admin typed.',
        'icon' => 'ph-television',
        'colour' => 'primary',
    ], 5760, false],

    // The thinnest row the component can be asked to draw.
    ['0010', 'EmailVerifiedNotification', [
        'title' => 'Email verified',
        'icon' => 'ph-envelope-open',
        'colour' => 'success',
    ], 10080, true],

    // A month back, so the Earlier heading exists.
    ['0011', 'ReferralRewardEarnedNotification', [
        'title' => 'Referral reward earned',
        'message' => 'A friend you referred completed their first payment.',
        'icon' => 'ph-gift',
        'colour' => 'success',
    ], 43200, true],

    ['0012', 'WelcomeUserNotification', [
        'title' => 'Welcome to Jambo',
        'message' => 'Your account is ready.',
        'icon' => 'ph-hand-waving',
        'colour' => 'primary',
    ], 44000, true],
];

/*
 * Past the page.
 *
 * `PER_PAGE` is 30 and the twelve above do not reach it, so nothing would ever
 * return a cursor and the infinite scroll would look like it worked. These
 * fill the list to 40 and are deliberately dull: their only job is to be the
 * second page.
 */
for ($i = 13; $i <= 40; $i++) {
    $rows[] = [
        str_pad((string) $i, 4, '0', STR_PAD_LEFT),
        'MovieAddedNotification',
        [
            'title' => 'New movie added',
            'message' => "Catalogue update number {$i}.",
            'icon' => 'ph-film-strip',
            'colour' => 'primary',
        ],
        44000 + $i * 60,
        true,
    ];
}

$written = 0;

foreach ($rows as [$suffix, $class, $data, $minutesAgo, $read]) {
    $id = 'dev-notif-' . $suffix;
    $at = now()->subMinutes($minutesAgo);

    DB::table('notifications')->updateOrInsert(
        ['id' => $id],
        [
            'type' => $ns . $class,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode($data),
            'read_at' => $read ? $at : null,
            'created_at' => $at,
            'updated_at' => $at,
        ],
    );

    $written++;
}

$unread = DB::table('notifications')
    ->where('notifiable_id', $user->id)
    ->whereNull('read_at')
    ->count();

echo "Wrote {$written} dev-notif- rows for {$email}. Unread now: {$unread}.\n";
