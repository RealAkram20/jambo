<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Console\Command;

/**
 * Convert the phone numbers already stored to one shape.
 *
 * Rio, 2026-09-10, asked for the numbers in the database to be converted "with
 * a dry run that reports what it cannot parse rather than guessing" — which is
 * the whole design of this command and the reason it does nothing by default.
 *
 * **It reports before it writes, every time.** Running it with no flags changes
 * nothing and prints what it would do. `--write` is the only thing that touches
 * a row, and even then anything it cannot read confidently is **left exactly as
 * it is and listed**, because a phone number is how somebody gets reached and a
 * confident guess at an ambiguous one is worse than leaving it alone.
 *
 * The ambiguity that matters, and why nothing is guessed: a bare `742078673`
 * is a Ugandan mobile line read against Uganda, and something else entirely
 * read against another country. Rows whose owner has no country are read
 * against Uganda — the app's home market and where every account has come from
 * so far — and any row where that produces something the library refuses is
 * reported rather than rewritten.
 */
class NormalisePhoneNumbers extends Command
{
    protected $signature = 'phones:normalise
                            {--write : Actually change rows. Without it, nothing is written.}
                            {--limit=0 : Stop after this many rows. 0 means all.}';

    protected $description = 'Convert stored phone numbers to E.164, reporting anything it cannot read.';

    public function handle(): int
    {
        $write = (bool) $this->option('write');
        $limit = (int) $this->option('limit');

        $this->line($write
            ? '<fg=yellow>WRITING.</> Rows will be changed.'
            : 'Dry run. Nothing will be written — add --write when the report looks right.');
        $this->newLine();

        $query = User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $already = 0;
        $changed = [];
        $unreadable = [];

        $query->chunkById(200, function ($users) use ($write, &$already, &$changed, &$unreadable) {
            foreach ($users as $user) {
                $stored = (string) $user->phone;

                // The country the account chose, when it chose one. Everything
                // else is read against Uganda — see the class docblock.
                $e164 = PhoneNumber::toE164($stored, $user->country ?: null);

                if ($e164 === null) {
                    $unreadable[] = [$user->id, $user->username ?? '—', $stored];
                    continue;
                }

                if ($e164 === $stored) {
                    $already++;
                    continue;
                }

                $changed[] = [$user->id, $user->username ?? '—', $stored, $e164];

                if ($write) {
                    // saveQuietly: this is a data migration, not something a
                    // viewer did. Firing model events would send "your profile
                    // changed" notifications to everybody at once.
                    $user->forceFill(['phone' => $e164])->saveQuietly();
                }
            }
        });

        if ($changed !== []) {
            $this->line('<fg=green>' . ($write ? 'Converted' : 'Would convert') . ':</>');
            $this->table(['id', 'username', 'stored', 'becomes'], $changed);
        }

        if ($unreadable !== []) {
            $this->newLine();
            $this->line('<fg=red>Left alone — could not be read confidently:</>');
            $this->table(['id', 'username', 'stored'], $unreadable);
            $this->line('These need a person to look at them. Nothing was changed.');
        }

        $this->newLine();
        $this->line(sprintf(
            '%d %s, %d already correct, %d unreadable.',
            count($changed),
            $write ? 'converted' : 'to convert',
            $already,
            count($unreadable),
        ));

        if (! $write && $changed !== []) {
            $this->newLine();
            $this->line('Re-run with <fg=yellow>--write</> to apply.');
        }

        return self::SUCCESS;
    }
}
