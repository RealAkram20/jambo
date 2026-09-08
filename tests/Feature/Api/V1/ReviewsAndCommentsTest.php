<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Content\app\Models\Comment;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Review;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Notifications\app\Events\NewCommentPosted;
use Modules\Notifications\app\Events\NewReviewPosted;
use Modules\Streaming\app\Models\Device;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Reviews on titles and comments on episodes.
 *
 * These are the endpoints where the app writes content other people read, so
 * the cases that matter are the ones that keep a thread honest: one review per
 * viewer, an edit not notifying an admin twice, a reply that cannot be grafted
 * onto another episode's thread, and nobody deleting anyone else's words.
 */
class ReviewsAndCommentsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    // ── reviews ──────────────────────────────────────────────────────

    public function test_reading_reviews_is_public(): void
    {
        $movie = $this->movie();

        $this->getJson("/api/v1/movies/{$movie->slug}/reviews")
            ->assertOk()
            ->assertJsonPath('data.stats.count', 0)
            ->assertJsonPath('data.mine', null);
    }

    public function test_writing_a_review_needs_a_signed_in_viewer(): void
    {
        $movie = $this->movie();

        $this->postJson("/api/v1/movies/{$movie->slug}/reviews", [
            'stars' => 5, 'body' => 'Excellent film.',
        ])->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_posting_a_review_and_reading_it_back(): void
    {
        Event::fake([NewReviewPosted::class]);
        $movie = $this->movie();
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)
            ->postJson("/api/v1/movies/{$movie->slug}/reviews", [
                'stars' => 4,
                'title' => 'Worth it',
                'body' => 'Held together right to the end.',
            ])
            ->assertOk()
            ->assertJsonPath('data.review.stars', 4)
            ->assertJsonPath('data.review.title', 'Worth it');

        Event::assertDispatched(NewReviewPosted::class);

        $this->withFreshToken($token)
            ->getJson("/api/v1/movies/{$movie->slug}/reviews")
            ->assertOk()
            ->assertJsonPath('data.stats.count', 1)
            // Compared numerically: json_encode drops a zero fraction, so a
            // whole average of 4.0 arrives as 4 while 4.5 arrives as 4.5.
            // Typed clients coerce both to a float; a strict === here would
            // only be testing PHP's encoder.
            ->assertJsonPath('data.stats.average', fn ($average) => (float) $average === 4.0)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.mine.stars', 4);
    }

    /**
     * One review per (viewer, title). Posting again edits, which also makes
     * the endpoint safe to retry when a mobile request times out.
     */
    public function test_posting_twice_edits_rather_than_duplicating(): void
    {
        $movie = $this->movie();
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->postJson("/api/v1/movies/{$movie->slug}/reviews", [
            'stars' => 2, 'body' => 'Did not land for me.',
        ])->assertOk();

        $this->withFreshToken($token)->postJson("/api/v1/movies/{$movie->slug}/reviews", [
            'stars' => 5, 'body' => 'Changed my mind on a rewatch.',
        ])->assertOk();

        $this->assertSame(1, Review::where('user_id', $user->id)->count());
        $this->assertSame(5, (int) Review::where('user_id', $user->id)->first()->stars);
    }

    /**
     * wasRecentlyCreated is the flag that separates an INSERT from an UPDATE.
     * Without it, every edit would ping an admin again.
     */
    public function test_editing_a_review_does_not_notify_a_second_time(): void
    {
        Event::fake([NewReviewPosted::class]);
        $movie = $this->movie();
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)->postJson("/api/v1/movies/{$movie->slug}/reviews", [
            'stars' => 3, 'body' => 'A first impression.',
        ]);
        $this->withFreshToken($token)->postJson("/api/v1/movies/{$movie->slug}/reviews", [
            'stars' => 4, 'body' => 'A second, better impression.',
        ]);

        Event::assertDispatchedTimes(NewReviewPosted::class, 1);
    }

    public function test_a_viewer_can_remove_their_own_review(): void
    {
        $movie = $this->movie();
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->postJson("/api/v1/movies/{$movie->slug}/reviews", [
            'stars' => 3, 'body' => 'On reflection, no.',
        ]);

        // Twice, because a retry must not fail.
        $this->withFreshToken($token)->deleteJson("/api/v1/movies/{$movie->slug}/reviews")->assertOk();
        $this->withFreshToken($token)->deleteJson("/api/v1/movies/{$movie->slug}/reviews")->assertOk();

        $this->assertSame(0, Review::where('user_id', $user->id)->count());
    }

    public function test_stars_are_bounded(): void
    {
        $movie = $this->movie();
        $token = $this->signIn($this->viewer());

        foreach ([0, 6] as $stars) {
            $this->withFreshToken($token)
                ->postJson("/api/v1/movies/{$movie->slug}/reviews", ['stars' => $stars, 'body' => 'Out of range.'])
                ->assertStatus(422)
                ->assertJsonPath('code', 'VALIDATION_FAILED');
        }
    }

    public function test_a_series_can_be_reviewed_too(): void
    {
        $show = $this->show();
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)
            ->postJson("/api/v1/series/{$show->slug}/reviews", ['stars' => 5, 'body' => 'Best thing this year.'])
            ->assertOk();

        $this->getJson("/api/v1/series/{$show->slug}/reviews")
            ->assertOk()
            ->assertJsonPath('data.stats.count', 1);
    }

    public function test_an_unknown_title_cannot_be_reviewed(): void
    {
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)
            ->postJson('/api/v1/movies/no-such-title/reviews', ['stars' => 5, 'body' => 'Ghost review.'])
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    /** A reviewer's email has no business in a public thread. */
    public function test_a_review_thread_exposes_no_private_details(): void
    {
        $movie = $this->movie();
        $user = $this->viewer();

        $this->withFreshToken($this->signIn($user))->postJson("/api/v1/movies/{$movie->slug}/reviews", [
            'stars' => 4, 'body' => 'Solid.',
        ]);

        $body = $this->getJson("/api/v1/movies/{$movie->slug}/reviews")->assertOk()->content();

        $this->assertStringNotContainsString($user->email, $body);
        $this->assertStringNotContainsString('password', $body);
    }

    // ── comments ─────────────────────────────────────────────────────

    public function test_reading_comments_is_public_and_writing_is_not(): void
    {
        $episode = $this->episode();

        $this->getJson("/api/v1/episodes/{$episode->id}/comments")
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this->postJson("/api/v1/episodes/{$episode->id}/comments", ['body' => 'Nice one'])
            ->assertStatus(401);
    }

    public function test_posting_a_comment_and_a_reply_nests_them(): void
    {
        Event::fake([NewCommentPosted::class]);
        $episode = $this->episode();
        $token = $this->signIn($this->viewer());

        $parentId = $this->withFreshToken($token)
            ->postJson("/api/v1/episodes/{$episode->id}/comments", ['body' => 'That ending though.'])
            ->assertStatus(201)
            ->json('data.comment.id');

        $this->withFreshToken($token)
            ->postJson("/api/v1/episodes/{$episode->id}/comments", [
                'body' => 'Right? Did not see it coming.',
                'parent_id' => $parentId,
            ])
            ->assertStatus(201);

        $threads = $this->withFreshToken($token)
            ->getJson("/api/v1/episodes/{$episode->id}/comments")
            ->assertOk()
            ->json('data.items');

        $this->assertCount(1, $threads, 'A reply must nest, not appear as a second thread.');
        $this->assertCount(1, $threads[0]['replies']);
        $this->assertTrue($threads[0]['is_mine']);

        Event::assertDispatchedTimes(NewCommentPosted::class, 2);
    }

    /**
     * Without this check a client could graft a reply from one show onto a
     * thread in another, and the thread query would render it happily.
     */
    public function test_a_reply_cannot_be_grafted_onto_another_episodes_thread(): void
    {
        $episodeA = $this->episode();
        $episodeB = $this->episode();
        $token = $this->signIn($this->viewer());

        $onA = $this->withFreshToken($token)
            ->postJson("/api/v1/episodes/{$episodeA->id}/comments", ['body' => 'Comment on A.'])
            ->json('data.comment.id');

        $this->withFreshToken($token)
            ->postJson("/api/v1/episodes/{$episodeB->id}/comments", [
                'body' => 'Sneaky reply.',
                'parent_id' => $onA,
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_a_viewer_can_delete_their_own_comment_but_not_anothers(): void
    {
        $episode = $this->episode();
        $mine = $this->signIn($this->viewer(), 'device-uuid-mine01');
        $theirs = $this->signIn($this->viewer(), 'device-uuid-their1');

        $id = $this->withFreshToken($mine)
            ->postJson("/api/v1/episodes/{$episode->id}/comments", ['body' => 'My words.'])
            ->json('data.comment.id');

        // Somebody else's comment reads as not found, so the endpoint cannot
        // be used to discover that a comment exists.
        $this->withFreshToken($theirs)->deleteJson("/api/v1/comments/{$id}")
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');
        $this->assertNotNull(Comment::find($id));

        $this->withFreshToken($mine)->deleteJson("/api/v1/comments/{$id}")->assertOk();
        $this->assertNull(Comment::find($id));
    }

    public function test_an_admin_can_remove_any_comment(): void
    {
        $episode = $this->episode();
        $viewerToken = $this->signIn($this->viewer(), 'device-uuid-viewer1');

        $id = $this->withFreshToken($viewerToken)
            ->postJson("/api/v1/episodes/{$episode->id}/comments", ['body' => 'Something to moderate.'])
            ->json('data.comment.id');

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
        $admin = $this->viewer();
        $admin->assignRole('admin');

        $this->withFreshToken($this->signIn($admin, 'device-uuid-admin01'))
            ->deleteJson("/api/v1/comments/{$id}")
            ->assertOk();

        $this->assertNull(Comment::find($id));
    }

    public function test_unapproved_comments_stay_out_of_the_thread(): void
    {
        $episode = $this->episode();
        $user = $this->viewer();

        Comment::create([
            'user_id' => $user->id,
            'commentable_type' => $episode->getMorphClass(),
            'commentable_id' => $episode->id,
            'body' => 'Held for moderation.',
            'is_approved' => false,
        ]);

        $this->getJson("/api/v1/episodes/{$episode->id}/comments")
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    // ── fixtures ─────────────────────────────────────────────────────

    private function movie(): Movie
    {
        return Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'video_url' => 'https://cdn.example.test/movie.mp4',
        ]);
    }

    private function show(): Show
    {
        $show = Show::create([
            'title' => 'Reviewed Series ' . uniqid(),
            'slug' => 'reviewed-series-' . uniqid(),
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'S1']);
        Episode::create([
            'season_id' => $season->id,
            'number' => 1,
            'title' => 'Pilot',
            'published_at' => now()->subDay(),
            'video_url' => 'https://cdn.example.test/ep.mp4',
        ]);

        return $show;
    }

    private function episode(): Episode
    {
        return $this->show()->episodes()->first();
    }

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function signIn(User $user, string $uuid = 'device-uuid-rev001'): string
    {
        RateLimiter::clear(strtolower($user->email) . '|127.0.0.1');

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => ['uuid' => $uuid, 'platform' => Device::PLATFORM_ANDROID],
        ])->assertOk()->json('data.token');
    }

    /** See AuthAndDevicesTest: the guard caches its user across requests. */
    private function withFreshToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
