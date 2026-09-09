<?php

/**
 * Seed unread notifications for the test account.
 *
 * Written for one purpose: the profile menu draws an unread pill on its
 * Notifications row, capped at "99+", and none of that can be *seen* on a
 * database where the count is zero. A screenshot of a menu with no badge does
 * not prove the badge works — it proves nothing at all.
 *
 * Local database only, same as the rest of this folder. Run:
 *
 *   php tools/dev-catalogue/8-test-user-notifications.php 120
 *
 * The argument is how many to create; 120 is the useful default because it is
 * the far side of the 99+ cap, which is the case with a rendering decision in
 * it. Re-running deletes the ones this script made first, so the count is what
 * you asked for rather than what has accumulated.
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = 'testuser@jambo.test';
$count = isset($argv[1]) ? max(0, (int) $argv[1]) : 120;

$user = App\Models\User::where('email', $email)->first();

if ($user === null) {
    fwrite(STDERR, "No such user: {$email}. Run 6-test-user.php first.\n");
    exit(1);
}

// A marker in the payload, so re-running replaces this script's own rows and
// never touches a notification that came from the application itself.
$marker = 'dev-catalogue-seed';

$removed = DB::table('notifications')
    ->where('notifiable_id', $user->id)
    ->where('data', 'like', '%"' . $marker . '"%')
    ->delete();

$titles = [
    'New on Jambo this week',
    'Your plan renews soon',
    'A new episode is out',
    'Continue where you left off',
    'Recommended for you',
];

$rows = [];

for ($i = 0; $i < $count; $i++) {
    $rows[] = [
        'id' => Illuminate\Support\Str::uuid()->toString(),
        'type' => 'App\\Notifications\\DevCatalogueSeed',
        'notifiable_type' => App\Models\User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode([
            'source' => $marker,
            'title' => $titles[$i % count($titles)],
            'message' => 'Seeded locally so the unread badge has something to count.',
            'icon' => 'ph-bell',
        ]),
        // Unread is the whole point: read_at stays null.
        'read_at' => null,
        'created_at' => now()->subMinutes($i),
        'updated_at' => now()->subMinutes($i),
    ];
}

foreach (array_chunk($rows, 200) as $chunk) {
    DB::table('notifications')->insert($chunk);
}

$unread = $user->unreadNotifications()->count();

echo "Removed {$removed} previously seeded rows.\n";
echo "Created {$count} unread notifications for {$email}.\n";
echo "Unread count is now {$unread}.\n";
