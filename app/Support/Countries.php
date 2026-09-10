<?php

namespace App\Support;

use Locale;

/**
 * ISO-3166-1 alpha-2 country codes, and the names to show for them.
 *
 * Added 2026-09-09 for the profile screen's Country row. The mockup showed
 * "Uganda" against a field that did not exist anywhere in the system, and Rio
 * chose to make it real rather than drop the row.
 *
 * **The code is what is stored; the name is only ever derived.** Storing a
 * typed country name would give the database "Uganda", "uganda", "UG" and
 * "Ugnada" for one country, none of which can be counted or filtered, and all
 * of which have to be shown back to somebody exactly as they mistyped them.
 *
 * The display name comes from `intl` when it is loaded, which is the whole
 * ICU list in the viewer's language for free. **Validation deliberately does
 * NOT depend on `intl`**, and that split is the point: an extension missing on
 * one box would otherwise silently start rejecting every country, which is a
 * far worse failure than showing a code instead of a name. Without `intl` the
 * code is still stored, still valid, and still shown — just as "UG" rather
 * than "Uganda".
 */
final class Countries
{
    /**
     * Every assigned ISO-3166-1 alpha-2 code.
     *
     * A literal list rather than an `intl` query, for the reason above:
     * validation has to hold on a server without the extension. It is data,
     * it changes about once a decade, and it belongs in one place.
     *
     * @var list<string>
     */
    public const CODES = [
        'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT',
        'AU', 'AW', 'AX', 'AZ', 'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI',
        'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY',
        'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
        'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM',
        'DO', 'DZ', 'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK',
        'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL',
        'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM',
        'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR',
        'IS', 'IT', 'JE', 'JM', 'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN',
        'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS',
        'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK',
        'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW',
        'MX', 'MY', 'MZ', 'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP',
        'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM',
        'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW',
        'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM',
        'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF',
        'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW',
        'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI',
        'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW',
    ];

    /**
     * The countries a Ugandan streaming service's viewers are most likely to
     * pick, surfaced above the alphabetical rest.
     *
     * Not a judgement about which countries matter — it is a judgement about
     * scrolling. Jambo is Ugandan and its audience is Uganda and the East
     * African Community plus the diaspora, and making those seven the first
     * seven saves nearly every viewer a 250-row scroll.
     *
     * @var list<string>
     */
    public const SUGGESTED = ['UG', 'KE', 'TZ', 'RW', 'BI', 'SS', 'CD'];

    /** Whether this is an assigned alpha-2 code. Case-insensitive in, because
     *  a client sending "ug" means Uganda and refusing it helps nobody. */
    public static function isValid(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        return in_array(strtoupper($code), self::CODES, true);
    }

    /** The stored form: upper case, or null when it is not a country. */
    public static function normalise(?string $code): ?string
    {
        return self::isValid($code) ? strtoupper((string) $code) : null;
    }

    /**
     * What to show a viewer for a stored code.
     *
     * Returns null for null — an account with no country set has no country
     * name, and the screen renders an em dash rather than a guess. Falls back
     * to the code itself when `intl` is absent, which is wrong-looking but
     * true; inventing "Uganda" from a lookup that failed would not be.
     */
    public static function name(?string $code): ?string
    {
        $code = self::normalise($code);

        if ($code === null) {
            return null;
        }

        if (! extension_loaded('intl')) {
            return $code;
        }

        $name = Locale::getDisplayRegion('-' . $code, 'en');

        // ICU answers "Unknown Region" for anything it does not have, which is
        // a string a viewer must never see on their own profile.
        return ($name === '' || $name === 'Unknown Region') ? $code : $name;
    }

    /**
     * Every country as `{code, name}`, suggested ones first, the rest sorted
     * by name rather than by code — nobody scanning a list is looking for CI
     * before CN, they are looking for Côte d'Ivoire before China.
     *
     * @return list<array{code: string, name: string}>
     */
    public static function all(): array
    {
        $rest = array_values(array_diff(self::CODES, self::SUGGESTED));

        $named = array_map(
            static fn (string $code): array => ['code' => $code, 'name' => (string) self::name($code)],
            $rest,
        );

        usort($named, static fn (array $a, array $b): int => strcoll($a['name'], $b['name']));

        $suggested = array_map(
            static fn (string $code): array => ['code' => $code, 'name' => (string) self::name($code)],
            self::SUGGESTED,
        );

        return array_merge($suggested, $named);
    }
}
