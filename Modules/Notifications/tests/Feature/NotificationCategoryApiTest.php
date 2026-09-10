<?php

namespace Modules\Notifications\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Notifications\app\Notifications\AdminBroadcastNotification;
use Modules\Notifications\app\Notifications\EpisodeAddedNotification;
use Modules\Notifications\app\Notifications\MovieAddedNotification;
use Modules\Notifications\app\Notifications\NewDeviceLoginNotification;
use Modules\Notifications\app\Notifications\SeasonAddedNotification;
use Modules\Notifications\app\Notifications\ShowAddedNotification;
use Modules\Notifications\app\Notifications\WatchlistAvailableNotification;
use Modules\Notifications\app\Support\NotificationCategories;
use Modules\Streaming\app\Models\Device;
use ReflectionClass;
use Tests\TestCase;

/**
 * The filter chips on the app's notification inbox.
 *
 * The chips exist because Rio's mockup of 2026-09-09 draws five of them. They
 * filter on the server because the list is cursor-paginated, and they filter
 * on `notifications.type` because that column already holds the answer — no
 * migration, and notifications written before the chips existed still sort
 * themselves correctly.
 */
class NotificationCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    /**
     * The drift guard.
     *
     * A literal map goes stale the moment somebody adds a notification and
     * does not think about the inbox. This walks the directory and fails on
     * the thirty-fourth class, so "which chip does this belong under" is a
     * question that gets asked at the time rather than discovered by a viewer
     * whose payment receipt is missing from Account.
     *
     * Answering it with UNCATEGORISED is a perfectly good answer. Not
     * answering it is what this catches.
     */
    public function test_every_notification_class_is_filed_or_deliberately_unfiled(): void
    {
        $unfiled = [];

        foreach (glob(__DIR__ . '/../../app/Notifications/*.php') as $file) {
            $short = basename($file, '.php');
            $fqn = 'Modules\\Notifications\\app\\Notifications\\' . $short;

            if (! class_exists($fqn)) {
                continue;
            }

            $reflection = new ReflectionClass($fqn);

            // The abstract base, and any helper that merely lives in the
            // folder, are not notifications and have no chip to belong to.
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(BaseNotification::class)) {
                continue;
            }

            $filed = NotificationCategories::forType($fqn) !== null
                || in_array($short, NotificationCategories::uncategorised(), true);

            if (! $filed) {
                $unfiled[] = $short;
            }
        }

        $this->assertSame([], $unfiled, implode("\n", [
            'These notification classes belong to no filter chip and are not',
            'listed as deliberately uncategorised, so the app cannot say where',
            'they go. Add each to NotificationCategories::MAP, or to',
            'UNCATEGORISED if it should only ever appear under All:',
            '  ' . implode(', ', $unfiled),
        ]));
    }

    public function test_a_card_carries_its_category_and_its_colour(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->seedNotification($user, MovieAddedNotification::class, 'New movie added', [
            'colour' => 'primary',
            'icon' => 'ph-film-strip',
        ]);

        $this->withFreshToken($token)->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.items.0.category', NotificationCategories::MOVIES)
            ->assertJsonPath('data.items.0.colour', 'primary')
            ->assertJsonPath('data.items.0.icon', 'ph-film-strip');
    }

    public function test_the_movies_chip_returns_only_movie_notifications(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->seedNotification($user, MovieAddedNotification::class, 'A movie');
        $this->seedNotification($user, ShowAddedNotification::class, 'A series');
        $this->seedNotification($user, NewDeviceLoginNotification::class, 'A login');

        $this->withFreshToken($token)->getJson('/api/v1/notifications?category=movies')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.title', 'A movie');
    }

    /** All three series types answer one chip, which is the point of a map. */
    public function test_the_series_chip_spans_show_season_and_episode(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->seedNotification($user, ShowAddedNotification::class, 'A series');
        $this->seedNotification($user, SeasonAddedNotification::class, 'A season');
        $this->seedNotification($user, EpisodeAddedNotification::class, 'An episode');
        $this->seedNotification($user, MovieAddedNotification::class, 'A movie');

        $this->withFreshToken($token)->getJson('/api/v1/notifications?category=series')
            ->assertOk()
            ->assertJsonCount(3, 'data.items');
    }

    /**
     * Offers is admin broadcasts.
     *
     * Rio's ruling of 2026-09-09, and it is the whole reason the chip is not a
     * lie: Jambo has no promotional notification type, and a broadcast is how
     * a promotion reaches a viewer today.
     */
    public function test_offers_is_admin_broadcasts(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->seedNotification($user, AdminBroadcastNotification::class, '30% off this month');
        $this->seedNotification($user, MovieAddedNotification::class, 'A movie');

        $this->withFreshToken($token)->getJson('/api/v1/notifications?category=offers')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.title', '30% off this month');
    }

    /**
     * A row under no chip is reachable under All, and only there.
     *
     * This is the assertion that makes UNCATEGORISED a decision rather than a
     * hole: the notification is not lost, it simply has no chip.
     */
    public function test_an_unfiled_notification_appears_under_all_and_no_chip(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->seedNotification($user, WatchlistAvailableNotification::class, 'Now available');

        $this->withFreshToken($token)->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.category', null);

        foreach (NotificationCategories::all() as $category) {
            $this->withFreshToken($token)->getJson('/api/v1/notifications?category=' . $category)
                ->assertOk()
                ->assertJsonCount(0, 'data.items');
        }
    }

    /**
     * An unknown chip is refused, not ignored.
     *
     * A filter that silently returns everything looks like a filter that found
     * everything, which is the kind of wrong that survives review.
     */
    public function test_an_unknown_category_is_refused(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->seedNotification($user, MovieAddedNotification::class, 'A movie');

        $this->withFreshToken($token)->getJson('/api/v1/notifications?category=sponsors')
            ->assertStatus(422);
    }

    /** The badge counts the inbox, not the chip. */
    public function test_the_unread_count_ignores_the_chip(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->seedNotification($user, MovieAddedNotification::class, 'A movie');
        $this->seedNotification($user, NewDeviceLoginNotification::class, 'A login');
        $this->seedNotification($user, ShowAddedNotification::class, 'A series');

        $this->withFreshToken($token)->getJson('/api/v1/notifications?category=movies')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.unread_count', 3);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    /**
     * A row written the way Laravel writes one: the class name in `type`.
     *
     * Inserted directly rather than dispatched because the four-layer channel
     * gate decides whether a real dispatch lands at all, and this file is
     * about how a stored row is read back, not about whether it was allowed.
     *
     * @param  array<string, mixed>  $extra
     */
    private function seedNotification(User $user, string $type, string $title, array $extra = []): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => $type,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode(array_merge(['title' => $title, 'message' => 'Body text.'], $extra)),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function signIn(User $user, string $uuid = 'device-uuid-notcat1'): string
    {
        RateLimiter::clear(strtolower($user->email) . '|127.0.0.1');

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => ['uuid' => $uuid, 'platform' => Device::PLATFORM_ANDROID],
        ])->assertOk()->json('data.token');
    }

    private function withFreshToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
