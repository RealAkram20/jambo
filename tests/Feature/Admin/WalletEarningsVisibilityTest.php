<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\PerformanceCredits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\ContentActivity;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Models\WithdrawalRequest;
use Modules\Wallet\app\Services\Ledger;
use Modules\Wallet\app\Services\Payouts;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The money doctrine the wallet page promises staff:
 *
 *  1. Rates are snapshotted per upload — changing a rate later never
 *     rewrites money already credited; only future uploads feel it.
 *  2. Withdrawing reduces the AVAILABLE balance but never changes what
 *     a given month shows as earned — so an admin can always answer
 *     "what did I make in March" even after cashing out.
 *  3. "Earned since last payout" counts only credits after the last
 *     PAID withdrawal, so months of un-withdrawn work stay visible.
 */
class WalletEarningsVisibilityTest extends TestCase
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
            'username' => 'staff_' . uniqid(),
            'email' => 'staff_' . uniqid() . '@test.local',
        ]);
        $this->admin->assignRole('admin');
    }

    private function upload(string $type = 'movie', int $contentId = 1): ContentActivity
    {
        return ContentActivity::create([
            'actor_id' => $this->admin->id,
            'actor_name' => $this->admin->username,
            'action' => ContentActivity::ACTION_CREATED,
            'content_type' => $type,
            'content_id' => $contentId,
            'content_title' => ucfirst($type) . ' ' . $contentId,
            'created_at' => now(),
        ]);
    }

    public function test_rate_changes_only_affect_future_uploads(): void
    {
        $this->upload('movie', 1); // credited at 2,000

        setting(['performance.price_per_movie', '5000']);

        // Neither the sweep nor anything else may touch the old credit.
        $this->assertSame(0, app(PerformanceCredits::class)->sweep());

        $this->upload('movie', 2); // credited at 5,000

        $amounts = LedgerEntry::query()
            ->where('owner_id', $this->admin->id)
            ->where('type', LedgerEntry::TYPE_PERFORMANCE_CREDIT)
            ->orderBy('id')
            ->pluck('amount')
            ->map(fn ($a) => (string) $a)
            ->all();

        $this->assertSame(0, bccomp('2000.00', $amounts[0], 2));
        $this->assertSame(0, bccomp('5000.00', $amounts[1], 2));
    }

    public function test_withdrawal_reduces_balance_but_not_monthly_earnings(): void
    {
        $this->upload('movie', 1); // +2,000 this month

        app(Payouts::class)->request($this->admin, '1500', 'Staff Member', '0700000000');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.wallet.index'))
            ->assertOk();

        // Balance reflects the hold; the month's earnings do not.
        $this->assertSame(0, bccomp('500.00', (string) $response->viewData('balance'), 2));
        $this->assertSame(0, bccomp('2000.00', (string) $response->viewData('earnedThisMonth'), 2));

        $monthly = $response->viewData('monthlyEarnings');
        $thisMonth = $monthly[now()->format('Y-m')] ?? [];
        $this->assertSame(0, bccomp('2000.00', $thisMonth['performance'] ?? '0', 2));
    }

    public function test_earned_since_last_payout_counts_only_credits_after_it(): void
    {
        $this->upload('movie', 1); // +2,000 before the payout

        $withdrawal = app(Payouts::class)->request($this->admin, '2000', 'Staff Member', '0700000000');
        $withdrawal->update([
            'status' => WithdrawalRequest::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $this->travel(1)->days();
        $this->upload('episode', 7); // +500 after the payout

        $response = $this->actingAs($this->admin)
            ->get(route('admin.wallet.index'))
            ->assertOk();

        $this->assertSame(0, bccomp('500.00', (string) $response->viewData('earnedSinceLastPayout'), 2));
        $this->assertSame(0, bccomp('500.00', (string) $response->viewData('balance'), 2));

        // Lifetime figure keeps the pre-payout work visible.
        $this->assertSame(0, bccomp(
            '2500.00',
            (string) app(Ledger::class)->totalOfType($this->admin, LedgerEntry::TYPE_PERFORMANCE_CREDIT),
            2
        ));
    }

    public function test_wallet_page_without_any_payout_shows_no_since_figure(): void
    {
        $this->upload('movie', 1);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.wallet.index'))
            ->assertOk()
            ->assertSee('No payouts yet');

        $this->assertNull($response->viewData('earnedSinceLastPayout'));
    }
}
