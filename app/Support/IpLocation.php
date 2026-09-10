<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * An IP address, as a place a viewer would recognise.
 *
 * Added 2026-09-09 for the app's devices screen, where each row says where it
 * last signed in from so somebody can spot a session that is not theirs. Rio
 * asked for the city rather than the raw address the website shows.
 *
 * **It resolves locally, against a database file, and calls nothing.** That is
 * the whole design decision. The alternatives were a metered lookup API, which
 * costs money per request and would have needed asking, and a free one, whose
 * licence forbids commercial use — Jambo takes money, so that was never
 * available. A local MaxMind GeoLite2 file has neither problem: no per-request
 * cost, no rate limit, and **no viewer's IP address leaves this server**,
 * which matters more here than the cost does.
 *
 * **It returns null rather than guessing, and that is load-bearing.** A wrong
 * city on a security screen is worse than no city: the whole reason the line
 * exists is so somebody can say "I have never been to Nairobi" and act on it.
 * When the database is absent, or the address is private, or the lookup finds
 * nothing, this returns null and the screen falls back to showing the address
 * itself — which is what the website has always shown.
 *
 * **Nothing is stored.** The city is derived at read time from an IP that was
 * already recorded. Persisting resolved locations would turn one column into a
 * movement history, which is a different thing to hold about somebody.
 *
 * 🔴 **The database file is not in this repository and cannot be.** GeoLite2 is
 * free but needs a MaxMind account and a licence key to download, and the file
 * is ~60 MB and updated weekly. Until `services.geoip.database` points at a
 * real file, every call here returns null and every device row shows an IP.
 * See `docs/deploy/` for the fetch-and-refresh step.
 */
final class IpLocation
{
    /** Resolved addresses for the life of one request. */
    private static array $memo = [];

    /**
     * "Kampala, Uganda", or null when it cannot be known.
     *
     * Memoised per request because a devices list resolves the same handful of
     * addresses repeatedly — several rows are usually the same home connection
     * — and each miss otherwise reopens the database file.
     */
    public static function describe(?string $ip): ?string
    {
        if ($ip === null || trim($ip) === '') {
            return null;
        }

        $ip = trim($ip);

        if (array_key_exists($ip, self::$memo)) {
            return self::$memo[$ip];
        }

        return self::$memo[$ip] = self::lookup($ip);
    }

    /** Forget the per-request cache. For tests, which change the database. */
    public static function flush(): void
    {
        self::$memo = [];
    }

    /**
     * Is this an address that could have a location at all?
     *
     * A private or reserved address has none, and that is not an edge case —
     * it is every request in development and every request behind a proxy that
     * forwards badly. Answering "United States" for 127.0.0.1, which some
     * databases will, is exactly the wrong-city failure this class exists to
     * avoid.
     *
     * **Public, and separate from `describe()`, so it can be proved.** It began
     * as an early return inside the lookup and a test asserting that
     * `describe('127.0.0.1')` is null passed with the check deleted — because
     * on a machine with no geolocation database every path returns null and the
     * test could not tell which one had. A guard whose removal changes no test
     * is a guard nobody is checking.
     */
    public static function isPublic(string $ip): bool
    {
        return filter_var(
            trim($ip),
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }

    private static function lookup(string $ip): ?string
    {
        if (! self::isPublic($ip)) {
            return null;
        }

        $database = config('services.geoip.database');

        if (! is_string($database) || $database === '' || ! is_file($database)) {
            return null;
        }

        // The reader is optional on purpose: the app must boot, and this
        // screen must render, on a machine where the package was never
        // installed. `composer require geoip2/geoip2` turns this on.
        if (! class_exists(\GeoIp2\Database\Reader::class)) {
            return null;
        }

        try {
            $reader = new \GeoIp2\Database\Reader($database);
            $record = $reader->city($ip);

            $city = $record->city->name;
            $country = $record->country->name;

            return match (true) {
                is_string($city) && is_string($country) => $city . ', ' . $country,
                is_string($country) => $country,
                default => null,
            };
        } catch (\Throwable $e) {
            /*
             * Every failure here is the same answer — no location — and none of
             * them may break a device list. An address the database does not
             * know throws, a corrupt file throws, a file for the wrong edition
             * throws. Logged at debug because on a server with no database this
             * would otherwise fill the log once per row per request.
             */
            Log::debug('[geoip] lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
