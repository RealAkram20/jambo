<?php

namespace Modules\Streaming\app\Services;

/**
 * Turns whatever origin URL an admin pasted into the URL the player
 * should actually be 302'd to. One chokepoint for every provider.
 *
 * resolve() walks the configured `streaming.cdn.zones` in order and
 * returns the first zone's rewrite; a URL that no zone claims is
 * returned untouched (YouTube, a one-off external link, etc.). Because
 * a Bunny pull zone fronts exactly one origin, there is one zone per
 * origin — a new source of a shape a driver already understands is a
 * config entry, not new code.
 *
 * Drivers:
 *   backblaze — the three copyable B2 URL shapes, rewritten to the
 *               zone's pull-zone host and (when a token key is set)
 *               signed with Bunny SHA256 token authentication:
 *                 https://f005.backblazeb2.com/file/{bucket}/{path}
 *                 https://{bucket}.s3.{region}.backblazeb2.com/{path}
 *                 https://s3.{region}.backblazeb2.com/{bucket}/{path}
 *               The b2api download-by-id form has no stable path and is
 *               NOT rewritten. Only URLs whose bucket matches the zone's
 *               bucket are touched.
 *   dropbox   — normalized to raw=1 for inline playback (?dl=1 sends
 *               Content-Disposition: attachment, which iOS WebKit
 *               refuses to play). When the zone has a hostname, the
 *               normalized URL is additionally routed through the pull
 *               zone; otherwise the normalized origin URL is returned.
 *   host      — any URL whose host equals the zone's origin_host: swap
 *               the host for the pull-zone hostname, keep path + query,
 *               sign when a key is set.
 */
class CdnUrlResolver
{
    public function resolve(string $url): string
    {
        foreach ($this->zones() as $zone) {
            $rewritten = $this->applyZone($url, $zone);
            if ($rewritten !== null) {
                return $rewritten;
            }
        }

        return $url;
    }

    /** @return array<int, array<string, mixed>> */
    private function zones(): array
    {
        return array_values((array) config('streaming.cdn.zones', []));
    }

    /**
     * Ask one zone to claim and rewrite the URL. Returns the final URL,
     * or null when this zone doesn't recognize it (try the next zone).
     */
    private function applyZone(string $url, array $zone): ?string
    {
        return match ($zone['driver'] ?? null) {
            'backblaze' => $this->resolveBackblaze($url, $zone),
            'dropbox'   => $this->resolveDropbox($url, $zone),
            'host'      => $this->resolveHost($url, $zone),
            default     => null,
        };
    }

    // ── backblaze ────────────────────────────────────────────────────

    private function resolveBackblaze(string $url, array $zone): ?string
    {
        $cdnHost = $zone['hostname'] ?? null;
        $bucket  = $zone['bucket'] ?? null;
        if (!$cdnHost || !$bucket) {
            return null;
        }

        $filePath = $this->extractB2FilePath($url, $bucket);
        if ($filePath === null) {
            return null;
        }

        $cdnUrl = 'https://' . $cdnHost . $this->encodePath($filePath);

        return $this->maybeSign($cdnUrl, $zone);
    }

    /**
     * Returns the DECODED file path (leading slash, real characters —
     * "/movies/x/original.mp4") when $url points at our bucket, else null.
     */
    private function extractB2FilePath(string $url, string $bucket): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($host === '' || $path === '' || !str_ends_with($host, '.backblazeb2.com')) {
            return null;
        }

        $bucketLower = strtolower($bucket);

        // Friendly URL: fNNN.backblazeb2.com/file/{bucket}/{path}
        // B2's UI encodes spaces as "+" in this form, so decode with
        // urldecode (handles both + and %20). The download-by-id API
        // path also starts with /b2api/ and is skipped by the prefix
        // check here.
        if (preg_match('/^f\d{3}\.backblazeb2\.com$/', $host)) {
            $prefix = '/file/' . $bucketLower . '/';
            if (str_starts_with(strtolower($path), $prefix)) {
                return '/' . urldecode(substr($path, strlen($prefix)));
            }
            return null;
        }

        // S3 virtual-host style: {bucket}.s3.{region}.backblazeb2.com/{path}
        // urldecode (not rawurldecode) here too: the B2 dashboard shows
        // spaces as "+" in every URL form it displays, and a literal
        // plus in a filename would be shown as %2B — so +→space is
        // always the right reading for copy-pasted B2 links.
        if (preg_match('/^' . preg_quote($bucketLower, '/') . '\.s3\.[a-z0-9-]+\.backblazeb2\.com$/', $host)) {
            return '/' . urldecode(ltrim($path, '/'));
        }

        // S3 path-style: s3.{region}.backblazeb2.com/{bucket}/{path}
        if (preg_match('/^s3\.[a-z0-9-]+\.backblazeb2\.com$/', $host)) {
            $prefix = '/' . $bucketLower . '/';
            if (str_starts_with(strtolower($path), $prefix)) {
                return '/' . urldecode(substr($path, strlen($prefix)));
            }
        }

        return null;
    }

    // ── dropbox ──────────────────────────────────────────────────────

    private function resolveDropbox(string $url, array $zone): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host !== 'dropbox.com'
            && !str_ends_with($host, '.dropbox.com')
            && !str_ends_with($host, '.dropboxusercontent.com')
        ) {
            return null;
        }

        $normalized = $this->normalizeDropbox($url);

        // No pull zone configured yet → just serve the normalized origin
        // URL, exactly as before. Set the zone hostname to route Dropbox
        // through the CDN with no other change.
        $cdnHost = $zone['hostname'] ?? null;
        if (!$cdnHost) {
            return $normalized;
        }

        // Dropbox paths are opaque and the query (rlkey=…) is REQUIRED by
        // the origin, so swap only the host and carry path+query through
        // unchanged. Bunny forwards the query string to the origin.
        $swapped = $this->swapHost($normalized, $cdnHost);

        return $this->maybeSign($swapped, $zone);
    }

    /**
     * Dropbox: strip any dl= variant and force raw=1.
     *
     * ?dl=1 makes Dropbox send `Content-Disposition: attachment`, which
     * iOS WebKit refuses to play inside <video> (fires
     * MEDIA_ERR_SRC_NOT_SUPPORTED before decoding a byte). ?raw=1 serves
     * the same bytes inline and every browser plays it.
     */
    private function normalizeDropbox(string $url): string
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        unset($query['dl']);
        $query['raw'] = '1';

        $rebuilt = ($parts['scheme'] ?? 'https') . '://' . $parts['host'];
        if (!empty($parts['port']))     $rebuilt .= ':' . $parts['port'];
        if (!empty($parts['path']))     $rebuilt .= $parts['path'];
        $rebuilt .= '?' . http_build_query($query);
        if (!empty($parts['fragment'])) $rebuilt .= '#' . $parts['fragment'];

        return $rebuilt;
    }

    // ── generic host ─────────────────────────────────────────────────

    private function resolveHost(string $url, array $zone): ?string
    {
        $originHost = $zone['origin_host'] ?? null;
        $cdnHost    = $zone['hostname'] ?? null;
        if (!$originHost || !$cdnHost) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host !== strtolower($originHost)) {
            return null;
        }

        return $this->maybeSign($this->swapHost($url, $cdnHost), $zone);
    }

    // ── shared helpers ───────────────────────────────────────────────

    /** Replace only the host of a URL, preserving path, query and fragment. */
    private function swapHost(string $url, string $newHost): string
    {
        $parts = parse_url($url);

        $rebuilt = ($parts['scheme'] ?? 'https') . '://' . $newHost;
        if (!empty($parts['path']))     $rebuilt .= $parts['path'];
        if (isset($parts['query']))     $rebuilt .= '?' . $parts['query'];
        if (!empty($parts['fragment'])) $rebuilt .= '#' . $parts['fragment'];

        return $rebuilt;
    }

    /** Sign the URL when the zone carries a token key, else return as-is. */
    private function maybeSign(string $cdnUrl, array $zone): string
    {
        $tokenKey = $zone['token_key'] ?? null;
        if (!$tokenKey) {
            return $cdnUrl;
        }

        return $this->signBunnyUrl($cdnUrl, $tokenKey, (int) ($zone['token_ttl'] ?? 28800));
    }

    /** Re-encode a decoded path segment-by-segment (keeps the slashes). */
    private function encodePath(string $decodedPath): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $decodedPath)));
    }

    /**
     * Bunny Token Authentication (SHA256, URL-safe base64), matching
     * bunny.net's reference implementation: the hash input is
     * key + DECODED path + expires, while the returned URL keeps the
     * encoded path. Query params (e.g. Dropbox's rlkey) are forwarded to
     * the origin but not part of the signature, so a URL that already
     * carries a query gets the token appended with `&`.
     */
    private function signBunnyUrl(string $cdnUrl, string $tokenKey, int $ttl): string
    {
        $expires = time() + max(60, $ttl);
        $signaturePath = urldecode((string) parse_url($cdnUrl, PHP_URL_PATH));

        $token = base64_encode(hash('sha256', $tokenKey . $signaturePath . $expires, true));
        $token = trim(strtr($token, '+/', '-_'), '=');

        $sep = str_contains($cdnUrl, '?') ? '&' : '?';

        return $cdnUrl . $sep . 'token=' . $token . '&expires=' . $expires;
    }
}
