<?php

namespace Modules\Referrals\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Referrals\app\Services\ReferralSettings;
use Modules\Streaming\app\Models\Device;
use Modules\Wallet\app\Models\LedgerEntry;
use Modules\Wallet\app\Models\WithdrawalRequest;
use Modules\Wallet\app\Services\Ledger;
use Tests\TestCase;

/**
 * Withdrawal from the app, which is money leaving the business.
 *
 * The endpoint was added 2026-09-09 so the app's wallet screen could finish
 * the task instead of sending a viewer to a browser. It deliberately writes no
 * money logic of its own — every rule below is enforced by
 * `ReferralWalletService` and `Payouts::request`, the same code the website's
 * form runs — so **these tests exist to prove the API did not weaken any of
 * them**, which is the failure that would look like a working feature.
 */
class WalletWithdrawalApiTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    protected function setUp(): void
    {
        parent::setUp();
        ReferralSettings::flush();
    }

    public function test_a_withdrawal_needs_a_signed_in_viewer(): void
    {
        $this->postJson('/api/v1/wallet/withdrawals', [
            'amount' => '20000',
            'payee_name' => 'Test User',
            'payee_msisdn' => '0700000000',
        ])->assertStatus(401);
    }

    /** The happy path, and the three things it must move together. */
    public function test_a_request_creates_the_row_takes_the_hold_and_lowers_the_balance(): void
    {
        $user = $this->viewer();
        $this->fund($user, '50000.00');
        $token = $this->signIn($user);

        $response = $this->withFreshToken($token)->postJson('/api/v1/wallet/withdrawals', [
            'amount' => '20000',
            'payee_name' => 'Test User',
            'payee_msisdn' => '0700 000 000',
        ])
            ->assertOk()
            ->assertJsonPath('data.withdrawal.status', WithdrawalRequest::STATUS_REQUESTED)
            ->assertJsonPath('data.withdrawal.amount', '20000.00');

        // The balance the screen draws next, after the hold rather than before
        // it, so it never has to do the subtraction itself.
        $this->assertMoney('30000', $response->json('data.balance'));

        $this->assertSame(1, WithdrawalRequest::count());
        $this->assertMoney('30000', $this->balance($user));

        $hold = LedgerEntry::where('type', LedgerEntry::TYPE_WITHDRAWAL_HOLD)->firstOrFail();
        $this->assertSame('-20000.00', (string) $hold->amount);
        $this->assertSame(
            $hold->id,
            WithdrawalRequest::firstOrFail()->hold_entry_id,
            'The withdrawal must point at the hold that paid for it.',
        );
    }

    /**
     * A resubmit cannot create a second withdrawal.
     *
     * This is the property that makes the endpoint safe without an idempotency
     * key, and the reason one was not added: `Payouts::request` locks the owner
     * row and refuses a second open request, so the retry every dropped
     * connection produces is already harmless. If this test ever fails, the
     * missing key becomes a real gap rather than a redundant one.
     */
    public function test_a_second_request_while_one_is_open_is_refused(): void
    {
        $user = $this->viewer();
        $this->fund($user, '50000.00');
        $token = $this->signIn($user);

        $this->withFreshToken($token)->postJson('/api/v1/wallet/withdrawals', [
            'amount' => '20000',
            'payee_name' => 'Test User',
            'payee_msisdn' => '0700000000',
        ])->assertOk();

        $this->withFreshToken($token)->postJson('/api/v1/wallet/withdrawals', [
            'amount' => '10000',
            'payee_name' => 'Test User',
            'payee_msisdn' => '0700000000',
        ])->assertStatus(422);

        $this->assertSame(1, WithdrawalRequest::count(), 'A retry must not create a second withdrawal.');
        $this->assertMoney('30000', $this->balance($user), 'A refused retry must not take a second hold.');
    }

    public function test_below_the_minimum_is_refused_and_writes_nothing(): void
    {
        $user = $this->viewer();
        $this->fund($user, '50000.00');
        $token = $this->signIn($user);

        // The shipped default is 10,000.
        $this->withFreshToken($token)->postJson('/api/v1/wallet/withdrawals', [
            'amount' => '500',
            'payee_name' => 'Test User',
            'payee_msisdn' => '0700000000',
        ])->assertStatus(422);

        $this->assertSame(0, WithdrawalRequest::count());
        $this->assertMoney('50000', $this->balance($user));
    }

    /**
     * Overdrawing leaves nothing behind.
     *
     * The request row is written before the ledger is asked, so the rollback is
     * the only thing standing between a refused withdrawal and a permanent row
     * claiming money that was never held.
     */
    public function test_more_than_the_balance_is_refused_and_rolls_the_row_back(): void
    {
        $user = $this->viewer();
        $this->fund($user, '15000.00');
        $token = $this->signIn($user);

        $this->withFreshToken($token)->postJson('/api/v1/wallet/withdrawals', [
            'amount' => '20000',
            'payee_name' => 'Test User',
            'payee_msisdn' => '0700000000',
        ])->assertStatus(422);

        $this->assertSame(0, WithdrawalRequest::count(), 'A refused withdrawal must leave no row.');
        $this->assertMoney('15000', $this->balance($user), 'A refused withdrawal must take no hold.');
    }

    /**
     * The same phone-number rule the website's form applies.
     *
     * A number the browser rejects and the app accepts is a payout that fails
     * at the till, days later, against a row that looked fine.
     */
    public function test_a_malformed_mobile_money_number_is_refused(): void
    {
        $user = $this->viewer();
        $this->fund($user, '50000.00');
        $token = $this->signIn($user);

        $this->withFreshToken($token)->postJson('/api/v1/wallet/withdrawals', [
            'amount' => '20000',
            'payee_name' => 'Test User',
            'payee_msisdn' => 'not-a-number',
        ])->assertStatus(422);

        $this->assertSame(0, WithdrawalRequest::count());
    }

    // ── what the wallet reports back ─────────────────────────────────

    public function test_the_wallet_carries_the_withdrawal_history_and_the_open_flag(): void
    {
        $user = $this->viewer();
        $this->fund($user, '50000.00');
        $token = $this->signIn($user);

        $this->withFreshToken($token)->postJson('/api/v1/wallet/withdrawals', [
            'amount' => '20000',
            'payee_name' => 'Test User',
            'payee_msisdn' => '0700000000',
        ])->assertOk();

        $this->withFreshToken($token)->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('data.has_open_withdrawal', true)
            ->assertJsonCount(1, 'data.withdrawals')
            ->assertJsonPath('data.withdrawals.0.status', WithdrawalRequest::STATUS_REQUESTED)
            ->assertJsonPath('data.withdrawals.0.amount', '20000.00')
            // The hold is visible in the ledger too, which is what explains a
            // balance that has dropped with nothing paid out yet.
            ->assertJsonPath('data.entries.0.type', LedgerEntry::TYPE_WITHDRAWAL_HOLD);
    }

    public function test_a_settled_withdrawal_does_not_keep_the_wallet_open(): void
    {
        $user = $this->viewer();
        $this->fund($user, '50000.00');
        $token = $this->signIn($user);

        $this->withFreshToken($token)->postJson('/api/v1/wallet/withdrawals', [
            'amount' => '20000',
            'payee_name' => 'Test User',
            'payee_msisdn' => '0700000000',
        ])->assertOk();

        WithdrawalRequest::firstOrFail()->update(['status' => WithdrawalRequest::STATUS_PAID]);

        $this->withFreshToken($token)->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('data.has_open_withdrawal', false)
            ->assertJsonCount(1, 'data.withdrawals');
    }

    /**
     * The rejection note is shown on a rejection and withheld everywhere else.
     *
     * It is an internal note written by a clerk. On a paid row it would be
     * stale, and on any row it belongs to nobody but that row's owner.
     */
    public function test_a_rejection_reason_is_shown_only_on_a_rejected_withdrawal(): void
    {
        $user = $this->viewer();
        $this->fund($user, '50000.00');
        $token = $this->signIn($user);

        $this->withFreshToken($token)->postJson('/api/v1/wallet/withdrawals', [
            'amount' => '20000',
            'payee_name' => 'Test User',
            'payee_msisdn' => '0700000000',
        ])->assertOk();

        $withdrawal = WithdrawalRequest::firstOrFail();
        $withdrawal->update(['rejection_reason' => 'Name does not match the number.']);

        $this->withFreshToken($token)->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('data.withdrawals.0.rejection_reason', null);

        $withdrawal->update(['status' => WithdrawalRequest::STATUS_REJECTED]);

        $this->withFreshToken($token)->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('data.withdrawals.0.rejection_reason', 'Name does not match the number.');
    }

    /** One viewer's withdrawals are not another's. */
    public function test_one_viewers_withdrawals_are_not_anothers(): void
    {
        $mine = $this->viewer();
        $theirs = $this->viewer();
        $this->fund($mine, '50000.00');
        $this->fund($theirs, '50000.00');

        $theirToken = $this->signIn($theirs, 'device-uuid-wd-theirs');
        $this->withFreshToken($theirToken)->postJson('/api/v1/wallet/withdrawals', [
            'amount' => '20000',
            'payee_name' => 'Someone Else',
            'payee_msisdn' => '0700000001',
        ])->assertOk();

        $myToken = $this->signIn($mine, 'device-uuid-wd-mine');
        $this->withFreshToken($myToken)->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('data.has_open_withdrawal', false)
            ->assertJsonCount(0, 'data.withdrawals');
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    /** Seed the wallet the only way money actually arrives in one. */
    private function fund(User $user, string $amount): void
    {
        DB::transaction(fn () => app(Ledger::class)->append(
            owner: $user,
            type: LedgerEntry::TYPE_REFERRAL_REWARD,
            amount: $amount,
            currency: 'UGX',
            memo: 'seed',
        ));
    }

    private function balance(User $user): string
    {
        return app(Ledger::class)->balanceFor($user, 'UGX');
    }

    /**
     * Compare money by value, never by string.
     *
     * `Ledger::balanceFor` returns a string whose decimal formatting follows
     * the database driver: MySQL answers "15000.00" and SQLite, which these
     * tests run on, answers "15000". Asserting on the string would make this
     * file pass or fail on the connection rather than on the money, and it
     * would have hidden the real point — that the app must not assume two
     * decimals in a balance either.
     */
    private function assertMoney(string $expected, mixed $actual, string $message = ''): void
    {
        $this->assertIsString($actual, $message !== '' ? $message : 'Money must arrive as a string.');
        $this->assertSame(0, bccomp($expected, $actual, 2), $message !== '' ? $message : sprintf(
            'Expected %s but got %s.', $expected, $actual,
        ));
    }

    private function signIn(User $user, string $uuid = 'device-uuid-wd01'): string
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
