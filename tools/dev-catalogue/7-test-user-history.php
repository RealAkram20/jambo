<?php
// Give the named test account something in Continue Watching and History, so
// those screens have real state rather than an empty state every session.
$user = App\Models\User::where('email', 'testuser@jambo.test')->firstOrFail();

$movies = Modules\Content\app\Models\Movie::whereNotNull('runtime_minutes')->take(3)->get();
foreach ($movies as $i => $movie) {
    $duration = $movie->runtime_minutes * 60;
    $fraction = [0.28, 0.64, 1.0][$i] ?? 0.4;
    $position = (int) ($duration * $fraction);

    Modules\Streaming\app\Models\WatchHistoryItem::updateOrCreate(
        ['user_id' => $user->id, 'watchable_type' => get_class($movie), 'watchable_id' => $movie->id],
        [
            'position_seconds' => $position,
            'duration_seconds' => $duration,
            // The third one is finished on purpose: History keeps completed
            // titles and Continue Watching drops them, and that difference is
            // the whole reason they are two screens.
            'completed' => $fraction >= 1.0,
            'watched_at' => now()->subHours(2 * ($i + 1)),
        ],
    );
    echo "  {$movie->title}: " . round($fraction * 100) . "%\n";
}
echo "seeded for user {$user->id}\n";
