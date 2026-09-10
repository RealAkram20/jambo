<?php

namespace App\Support;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

/**
 * Phone numbers, read however they were typed and stored one way.
 *
 * The twin of `mobile/src/ui/phone.ts`, and it exists because **the app is not
 * the only writer**. The website's profile form and the admin both save this
 * column, so normalising only in the app would leave the database holding
 * exactly the mixture Rio asked us to stop having.
 *
 * Rio, 2026-09-10, listed fifteen ways the same Ugandan line gets typed —
 * `+256742078673`, `0742 078 673`, `00256 742 078 673`, `(0742) 078 673`,
 * `742-078-673` and the rest. All of them land here as `+256742078673`.
 *
 * **Stored as E.164, always.** It is the one form that is unambiguous, that two
 * rows can be compared on, and that a gateway will dial. Everything shown to a
 * viewer is derived from it.
 *
 * **`libphonenumber` rather than a regular expression**, by Rio's decision, and
 * the reason is the payout field: recognising his formats needs no library,
 * but knowing that `742078673` is a real mobile line, `0123456789` is not, and
 * `0414230000` is a landline does — and mobile money sent to a plausible typo
 * is gone.
 */
final class PhoneNumber
{
    /** The app's home market, and what a bare `0742…` is read against. */
    public const DEFAULT_REGION = 'UG';

    /**
     * Anything a person might type, as E.164 — or null when it cannot be read.
     *
     * **Null means "not a phone number", never "no phone given".** A caller
     * must not store it as an empty string: a column holding "" is a phone
     * number of no digits, which is a different claim from having none.
     */
    public static function toE164(?string $input, ?string $region = null): ?string
    {
        $trimmed = trim((string) $input);

        if ($trimmed === '') {
            return null;
        }

        /*
         * `00` is the international access prefix here and in most of the
         * world, and libphonenumber only accepts it when it knows which
         * country is dialling out. Rio's list includes `00256742078673`, so it
         * is rewritten to `+` rather than left to fail.
         *
         * `011` — the North American equivalent — is deliberately not handled.
         * Jambo has no North American market, and a wrong guess about a
         * leading `011` silently changes somebody's number.
         */
        $digits = preg_replace('/[^\d+]/', '', $trimmed) ?? '';
        if (preg_match('/^00\d/', $digits) === 1) {
            $trimmed = '+' . substr($digits, 2);
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse($trimmed, $region ?: self::DEFAULT_REGION);
        } catch (\Throwable) {
            // Unparseable is an answer, not a fault. Every caller renders it
            // as "we could not read that" and none of them may 500 on it.
            return null;
        }

        return $util->isValidNumber($parsed)
            ? $util->format($parsed, PhoneNumberFormat::E164)
            : null;
    }

    /**
     * Whether this is a line that can receive mobile money.
     *
     * The question the withdrawal form actually has to answer, and it is not
     * "is this valid". A Ugandan landline is a perfectly valid number and
     * cannot be paid.
     */
    public static function isMobile(?string $input, ?string $region = null): bool
    {
        $trimmed = trim((string) $input);

        if ($trimmed === '') {
            return false;
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse($trimmed, $region ?: self::DEFAULT_REGION);
        } catch (\Throwable) {
            return false;
        }

        if (! $util->isValidNumber($parsed)) {
            return false;
        }

        $type = $util->getNumberType($parsed);

        /*
         * `FIXED_LINE_OR_MOBILE` passes as well as `MOBILE`. It is what the
         * metadata says for countries whose numbering plan does not separate
         * the two, and refusing it would reject every number in those
         * countries — a stricter rule that is wrong more often than right.
         */
        return $type === PhoneNumberType::MOBILE || $type === PhoneNumberType::FIXED_LINE_OR_MOBILE;
    }

    /**
     * A stored number as a person should read it: `+256 742 078 673`.
     *
     * **Grouped in threes, which is Rio's specification and not the library's.**
     * libphonenumber's own Ugandan convention is `+256 742 078673` — four then
     * six — because that is what Google's metadata records as official. His
     * examples are in threes, and his examples are what was asked for, so
     * Uganda is overridden and every other country keeps the library's rules.
     *
     * Falls back to the raw value rather than blanking it: a number stored
     * before this class existed is still the only number that account has.
     */
    public static function forDisplay(?string $stored): string
    {
        $trimmed = trim((string) $stored);

        if ($trimmed === '') {
            return '';
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse($trimmed, self::DEFAULT_REGION);
        } catch (\Throwable) {
            return $trimmed;
        }

        if (! $util->isValidNumber($parsed)) {
            return $trimmed;
        }

        $code = $parsed->getCountryCode();
        $national = (string) $parsed->getNationalNumber();

        if ($code === 256) {
            return '+256 ' . trim(chunk_split($national, 3, ' '));
        }

        return $util->format($parsed, PhoneNumberFormat::INTERNATIONAL);
    }
}
