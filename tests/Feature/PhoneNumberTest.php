<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phone numbers on the server, where the website and the admin also write.
 *
 * **Rio's fifteen examples are the specification** and they are asserted
 * literally rather than summarised. The list IS the contract: a case dropped
 * here is a shape somebody can type that the system will refuse.
 *
 * The twin of `mobile/src/ui/phone.test.ts`. Both exist because both surfaces
 * write this column, and normalising in only one of them would leave the
 * database holding exactly the mixture this work removes.
 */
class PhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    private const E164 = '+256742078673';

    public static function everyFormatRioListed(): array
    {
        return array_map(fn ($n) => [$n], [
            // International
            '+256742078673',
            '+256 742 078 673',
            '+256-742-078-673',
            '+256 (742) 078-673',
            '256742078673',
            '256 742 078 673',
            '00256742078673',
            '00 256 742 078 673',
            // Local
            '0742078673',
            '0742 078 673',
            '0742-078-673',
            '(0742) 078 673',
            '742078673',
            '742 078 673',
            '742-078-673',
            // The ones he called out as accidental
            '+256 742078673',
            '+256-742078673',
            '0742 078673',
            '0742078 673',
            '0742-078673',
        ]);
    }

    /**
     * @dataProvider everyFormatRioListed
     */
    public function test_every_format_resolves_to_one_number(string $input): void
    {
        $this->assertSame(self::E164, PhoneNumber::toE164($input), "Failed to read: {$input}");
    }

    public function test_nothing_is_not_a_number(): void
    {
        $this->assertNull(PhoneNumber::toE164(null));
        $this->assertNull(PhoneNumber::toE164(''));
        $this->assertNull(PhoneNumber::toE164('   '));
    }

    /**
     * Null means "that is not a phone number", never "no phone given".
     *
     * The distinction is load-bearing: a caller that stores the first as an
     * empty string has recorded a phone number of no digits, which is a
     * different claim from having none.
     */
    public function test_something_that_is_not_a_number_is_refused(): void
    {
        $this->assertNull(PhoneNumber::toE164('0123456789'));
        $this->assertNull(PhoneNumber::toE164('12345'));
        $this->assertNull(PhoneNumber::toE164('call me maybe'));
    }

    public function test_a_number_is_read_against_the_country_it_is_given(): void
    {
        $this->assertSame('+254712345678', PhoneNumber::toE164('0712345678', 'KE'));
        $this->assertSame('+255755123456', PhoneNumber::toE164('0755123456', 'TZ'));
    }

    /**
     * The check the withdrawal endpoint depends on.
     *
     * A landline is the case a shape check can never catch: `0414230000` is a
     * perfectly valid Ugandan number and mobile money cannot be sent to it.
     */
    public function test_a_landline_is_valid_and_still_cannot_be_paid(): void
    {
        $this->assertSame('+256414230000', PhoneNumber::toE164('0414230000'));
        $this->assertFalse(PhoneNumber::isMobile('0414230000'));

        $this->assertTrue(PhoneNumber::isMobile('0742078673'));
        $this->assertTrue(PhoneNumber::isMobile(self::E164));
    }

    public function test_display_uses_the_grouping_rio_asked_for(): void
    {
        // Rio's threes, not libphonenumber's four-then-six.
        $this->assertSame('+256 742 078 673', PhoneNumber::forDisplay(self::E164));
    }

    /**
     * A number stored before any of this existed is still that account's only
     * number. Showing it raw beats blanking it, which would read as "no phone".
     */
    public function test_an_unreadable_stored_value_is_shown_rather_than_hidden(): void
    {
        $this->assertSame('ask at reception', PhoneNumber::forDisplay('ask at reception'));
        $this->assertSame('', PhoneNumber::forDisplay(null));
    }

    /**
     * An extension is read and then silently dropped, and that is worth
     * knowing rather than discovering.
     *
     * The first draft of the test above used `0742-078-673 ext 4` as an
     * example of something unreadable. libphonenumber reads it perfectly —
     * it understands `ext` — and `format(E164)` then discards the extension,
     * so the stored number becomes the line without it.
     *
     * **Left as it is, deliberately.** An extension is meaningless on a mobile
     * money number, which is the field where this matters, and E.164's own
     * `;ext=` syntax is not something any gateway here would accept. Recorded
     * so that if somebody later reports "my extension vanished", the answer is
     * findable rather than mysterious.
     */
    public function test_an_extension_is_dropped_rather_than_kept(): void
    {
        $this->assertSame('+256742078673', PhoneNumber::toE164('0742-078-673 ext 4'));
    }

    // ── the backfill ─────────────────────────────────────────────────

    /**
     * The dry run is the default, and it must write nothing.
     *
     * This is the assertion that matters most in the file: a data migration
     * that writes when somebody expected a report is not recoverable by
     * reading the output afterwards.
     */
    public function test_the_backfill_writes_nothing_without_the_write_flag(): void
    {
        $user = User::factory()->create(['phone' => '0742 078 673']);

        $this->artisan('phones:normalise')->assertSuccessful();

        $this->assertSame('0742 078 673', $user->fresh()->phone);
    }

    public function test_the_backfill_converts_with_the_write_flag(): void
    {
        $user = User::factory()->create(['phone' => '0742 078 673']);

        $this->artisan('phones:normalise', ['--write' => true])->assertSuccessful();

        $this->assertSame(self::E164, $user->fresh()->phone);
    }

    /**
     * Anything unreadable is left exactly as it was.
     *
     * A phone number is how somebody gets reached, and a confident guess at an
     * ambiguous one is worse than leaving it alone for a person to look at.
     */
    public function test_the_backfill_leaves_what_it_cannot_read(): void
    {
        $junk = User::factory()->create(['phone' => 'call me maybe']);
        $good = User::factory()->create(['phone' => '0742078673']);

        $this->artisan('phones:normalise', ['--write' => true])->assertSuccessful();

        $this->assertSame('call me maybe', $junk->fresh()->phone, 'An unreadable number must not be touched.');
        $this->assertSame(self::E164, $good->fresh()->phone);
    }

    /** A row already in E.164 is not rewritten, so a re-run is a no-op. */
    public function test_the_backfill_is_safe_to_run_twice(): void
    {
        $user = User::factory()->create(['phone' => '0742 078 673']);

        $this->artisan('phones:normalise', ['--write' => true])->assertSuccessful();
        $this->artisan('phones:normalise', ['--write' => true])->assertSuccessful();

        $this->assertSame(self::E164, $user->fresh()->phone);
    }
}
