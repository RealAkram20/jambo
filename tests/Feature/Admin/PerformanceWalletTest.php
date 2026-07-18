<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\PerformanceCredits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\ContentActivity;
use Modules\Wallet\app\Models\LedgerEntry;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pay-per-upload → universal wallet sync: content activity credits the
 * acting admin's wallet exactly once at the configured rate.
 */
class PerformanceWalletTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['user', 'admin', 'super-admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['title' => ucfirst($role)]);
        }

        setting(['performance.price_per_movie', '2000']);
        setting(['performance.price_per_show', '3000']);
        setting(['performance.price_per_episode', '500']);

        $this->admin = User::factory()->create([
            'username' => 'uploader_' . uniqid(),
            'email' => 'uploader_' . uniqid() . '@test.local',
        ]);
        $this->admin->assignRole('admin');
    }

    private function logCreation(User $actor, string $type, int $contentId = 1): ContentActivity
    {
        return ContentActivity::create([
            'actor_id' => $actor->id,
            'actor_name' => $actor->username,
            'action' => ContentActivity::ACTION_CREATED,
            'content_type' => $type,
            'content_id' => $contentId,
            'content_title' => ucfirst($type) . ' ' . $contentId,
            'created_at' => now(),
        ]);
    }

    private function performanceEntries(User $user)
    {
        return LedgerEntry::query()
            ->where('owner_type', $user->getMorphClass())
            ->where('owner_id', $user->id)
            ->where('type', LedgerEntry::TYPE_PERFORMANCE_CREDIT);
    }

    public function test_creating_content_credits_the_admin_wallet(): void
    {
        $this->logCreation($this->admin, 'movie');
        $this->logCreation($this->admin, 'episode', 7);

        $entries = $this->performanceEntries($this->admin)->get();
        $this->assertCount(2, $entries);
        $this->assertSame(0, bccomp('2500.00',
            app(\Modules\Wallet\app\Services\Ledger::class)->balanceFor($this->admin), 2));
    }

    public function test_sweep_never_double_credits(): void
    {
        $this->logCreation($this->admin, 'movie');

        // The live hook already credited it; a sweep must be a no-op.
        $this->assertSame(0, app(PerformanceCredits::class)->sweep());
        $this->assertSame(1, $this->performanceEntries($this->admin)->count());
    }

    public function test_seasons_zero_rates_and_non_admins_earn_nothing(): void
    {
        // Seasons are tracked, never paid.
        $this->logCreation($this->admin, 'season');

        // Zero rate → no entry.
        setting(['performance.price_per_show', '0']);
        $this->logCreation($this->admin, 'show');

        // A non-staff actor (e.g. a partner uploading) earns via the
        // Monetization share, not per-upload rates.
        $viewer = User::factory()->create([
            'username' => 'viewer_' . uniqid(),
            'email' => 'viewer_' . uniqid() . '@test.local',
        ]);
        $viewer->assignRole('user');
        $this->logCreation($viewer, 'movie');

        $this->assertSame(0, LedgerEntry::where('type', LedgerEntry::TYPE_PERFORMANCE_CREDIT)->count());
    }

    public function test_admin_wallet_page_shows_both_earning_streams_on_one_balance(): void
    {
        // Performance stream: one movie upload (+2,000).
        $this->logCreation($this->admin, 'movie');

        // Referral stream: a reward landing on the same wallet (+3,000).
        \Illuminate\Support\Facades\DB::transaction(fn () => app(\Modules\Wallet\app\Services\Ledger::class)->append(
            owner: $this->admin,
            type: LedgerEntry::TYPE_REFERRAL_REWARD,
            amount: '3000.00',
            memo: 'seed reward',
        ));

        $this->actingAs($this->admin)
            ->get(route('admin.wallet.index'))
            ->assertOk()
            ->assertSee('Performance earnings')
            ->assertSee('Referral earnings')
            ->assertSee('2,000')   // performance tile
            ->assertSee('3,000')   // referral tile
            ->assertSee('5,000');  // one combined balance

        // Regular users have the profile-hub wallet, not the panel page.
        $viewer = User::factory()->create([
            'username' => 'plain_' . uniqid(),
            'email' => 'plain_' . uniqid() . '@test.local',
        ]);
        $viewer->assignRole('user');
        $this->actingAs($viewer)->get(route('admin.wallet.index'))->assertForbidden();
    }

    public function test_sweep_backfills_older_uploads_with_their_original_date(): void
    {
        // An upload logged while the sync didn't exist (simulated by
        // creating the row quietly, without firing model events).
        $activity = ContentActivity::withoutEvents(fn () => ContentActivity::create([
            'actor_id' => $this->admin->id,
            'actor_name' => $this->admin->username,
            'action' => ContentActivity::ACTION_CREATED,
            'content_type' => 'movie',
            'content_id' => 99,
            'content_title' => 'Old Movie',
            'created_at' => now()->subDays(10),
        ]));

        $this->assertSame(1, app(PerformanceCredits::class)->sweep());

        $entry = $this->performanceEntries($this->admin)->firstOrFail();
        $this->assertSame(0, bccomp('2000.00', (string) $entry->amount, 2));
        $this->assertTrue($entry->created_at->isSameDay($activity->created_at),
            'Backfilled credits must carry the upload date so period views stay truthful.');
    }
}
