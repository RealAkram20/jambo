<?php

namespace Modules\Streaming\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Tests\TestCase;

/**
 * Continue Watching is capped at WatchHistoryItem::CONTINUE_WATCHING_LIMIT
 * distinct titles. Because users routinely abandon a title during the end
 * credits (so it never flips to completed), in-progress rows would pile up
 * forever without this cap. record() prunes the oldest title the moment a
 * new one pushes past the limit; a whole series counts as a single slot.
 */
class ContinueWatchingLimitTest extends TestCase
{
    use RefreshDatabase;

    private function beat(User $user, $item, int $whenOffsetSeconds): void
    {
        // Distinct watched_at per beat so "oldest" is deterministic.
        $this->travelTo(now()->addSeconds($whenOffsetSeconds));
        WatchHistoryItem::record($user->id, $item, position: 100, duration: 6000);
        $this->travelBack();
    }

    private function inProgressCount(User $user): int
    {
        return WatchHistoryItem::where('user_id', $user->id)->where('completed', false)->count();
    }

    public function test_continue_watching_keeps_only_the_five_most_recent_movies(): void
    {
        $user = User::factory()->create();
        $movies = Movie::factory()->count(6)->create();

        foreach ($movies as $i => $movie) {
            $this->beat($user, $movie, $i * 60);
        }

        // Six distinct movies started; the row holds only the five newest.
        $this->assertSame(WatchHistoryItem::CONTINUE_WATCHING_LIMIT, $this->inProgressCount($user));

        // The first (oldest) movie was evicted; the rest survive.
        $this->assertDatabaseMissing('watch_history', [
            'user_id' => $user->id,
            'watchable_type' => $movies[0]->getMorphClass(),
            'watchable_id' => $movies[0]->id,
        ]);
        $this->assertDatabaseHas('watch_history', [
            'user_id' => $user->id,
            'watchable_type' => $movies[5]->getMorphClass(),
            'watchable_id' => $movies[5]->id,
        ]);
    }

    public function test_a_whole_series_counts_as_one_slot(): void
    {
        $user = User::factory()->create();

        // One show with five in-progress episodes = a single Continue
        // Watching slot, so it must not crowd out five separate movies.
        $season = Season::factory()->for(Show::factory())->create();
        $episodes = Episode::factory()->count(5)->for($season, 'season')
            ->sequence(fn ($seq) => ['number' => $seq->index + 1])
            ->create();

        foreach ($episodes as $i => $episode) {
            $this->beat($user, $episode, $i * 60);
        }

        // Then five distinct movies, all newer than the show's episodes.
        $movies = Movie::factory()->count(5)->create();
        foreach ($movies as $i => $movie) {
            $this->beat($user, $movie, 1000 + $i * 60);
        }

        // Six distinct titles (1 show + 5 movies) → the oldest title, the
        // show, is evicted as a unit: every one of its episode rows goes.
        foreach ($episodes as $episode) {
            $this->assertDatabaseMissing('watch_history', [
                'user_id' => $user->id,
                'watchable_type' => $episode->getMorphClass(),
                'watchable_id' => $episode->id,
            ]);
        }

        // All five movies remain.
        foreach ($movies as $movie) {
            $this->assertDatabaseHas('watch_history', [
                'user_id' => $user->id,
                'watchable_type' => $movie->getMorphClass(),
                'watchable_id' => $movie->id,
            ]);
        }
    }

    public function test_completed_rows_do_not_count_against_the_cap(): void
    {
        $user = User::factory()->create();

        // Five finished movies (completed) plus five in-progress ones.
        // Completed rows aren't part of Continue Watching, so they neither
        // count toward the cap nor get pruned.
        $finished = Movie::factory()->count(5)->create();
        foreach ($finished as $i => $movie) {
            $this->travelTo(now()->addSeconds($i));
            WatchHistoryItem::record($user->id, $movie, position: 6000, duration: 6000);
            $this->travelBack();
        }

        $inProgress = Movie::factory()->count(5)->create();
        foreach ($inProgress as $i => $movie) {
            $this->beat($user, $movie, 1000 + $i * 60);
        }

        $this->assertSame(5, WatchHistoryItem::where('user_id', $user->id)->where('completed', true)->count());
        $this->assertSame(5, $this->inProgressCount($user));
    }
}
