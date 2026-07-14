<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Bridges the admin's wall clock and the UTC we store.
 *
 * `<input type="datetime-local">` posts a bare "2026-07-14T20:33" with no
 * offset — it means 20:33 wherever the admin is sitting (EAT). The app clock
 * is UTC, so persisting that string verbatim lands `published_at` three hours
 * in the future, and `published_at <= now()` then hides the title for those
 * three hours even though the admin sees status=Published. Convert on the way
 * in, convert back on the way out, and the two clocks stop disagreeing.
 *
 * The database column stays UTC. Only the edges are localised.
 */
final class LocalTime
{
    public static function timezone(): string
    {
        return config('app.display_timezone') ?: config('app.timezone', 'UTC');
    }

    /**
     * Short label for the publishing clock, e.g. "EAT".
     *
     * Shown next to every admin date field. The whole reason the publish
     * time was landing in the wrong place is that the form never said which
     * clock it meant, leaving each admin to assume it was their own. An
     * admin in Riyadh, London or Kampala must all read the same label and
     * reach the same conclusion about when the title actually goes live.
     */
    public static function abbreviation(): string
    {
        return Carbon::now(self::timezone())->format('T');
    }

    /**
     * Admin wall-clock input (from a datetime-local field) → UTC Carbon.
     */
    public static function toUtc(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->utc();
        }

        return Carbon::parse((string) $value, self::timezone())->utc();
    }

    /**
     * Stored UTC → the "Y-m-d\TH:i" string a datetime-local field expects.
     */
    public static function forInput(mixed $value): ?string
    {
        return self::display($value)?->format('Y-m-d\TH:i');
    }

    /**
     * Stored UTC → Carbon in the display timezone, for rendering to humans.
     */
    public static function display(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->setTimezone(self::timezone());
    }
}
