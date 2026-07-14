<?php

namespace Modules\Streaming\app\Services;

/**
 * Turns whatever origin URL an admin pasted into the URL the player
 * should actually be 302'd to. One chokepoint for every provider:
 *
 *   Dropbox    → same URL, normalized for inline playback (raw=1).
 *   Backblaze  → rewritten to the Bunny CDN pull-zone hostname and,
 *                when a token key is configured, signed with Bunny's
 *                SHA256 token authentication (expiring URL).
 *   Anything else → passed through untouched.
 *
 * Recognized Backblaze URL shapes (all copyable from the B2 UI):
 *   https://f005.backblazeb2.com/file/{bucket}/{path}       (Friendly URL)
 *   https://{bucket}.s3.{region}.backblazeb2.com/{path}     (S3 URL)
 *   https://s3.{region}.backblazeb2.com/{bucket}/{path}     (S3 path-style)
 *
 * The b2api download-by-id form has no stable path and is NOT
 * rewritten — it would bypass the CDN anyway. Only URLs whose bucket
 * matches streaming.cdn.b2_bucket are rewritten, so a pasted link to
 * some third-party B2 file never gets pointed at our pull zone.
 */
class CdnUrlResolver
{
    public function resolve(string $url): string
    {
        $url = $this->normalizeDropbox($url);

        return $this->rewriteBackblazeToBunny($url);
    }

    /**
     * Dropbox: strip any dl= variant and force raw=1.
     *
     * ?dl=1 makes Dropbox send `Content-Disposition: attachment`,
     * which iOS WebKit refuses to play inside <video> (fires
     * MEDIA_ERR_SRC_NOT_SUPPORTED before decoding a byte). ?raw=1
     * serves the same bytes inline and every browser plays it.
     */
    private function normalizeDropbox(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host !== 'dropbox.com'
            && !str_ends_with($host, '.dropbox.com')
            && !str_ends_with($host, '.dropboxusercontent.com')
        ) {
            return $url;
        }

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

    private function rewriteBackblazeToBunny(string $url): string
    {
        $cdnHost = config('streaming.cdn.hostname');
        $bucket  = config('streaming.cdn.b2_bucket');
        if (!$cdnHost || !$bucket) {
            return $url;
        }

        $filePath = $this->extractB2FilePath($url, $bucket);
        if ($filePath === null) {
            return $url;
        }

        $cdnUrl = 'https://' . $cdnHost . $this->encodePath($filePath);

        $tokenKey = config('streaming.cdn.token_key');
        if ($tokenKey) {
            $cdnUrl = $this->signBunnyUrl($cdnUrl, $tokenKey, (int) config('streaming.cdn.token_ttl', 28800));
        }

        return $cdnUrl;
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

    /** Re-encode a decoded path segment-by-segment (keeps the slashes). */
    private function encodePath(string $decodedPath): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $decodedPath)));
    }

    /**
     * Bunny Token Authentication (SHA256, URL-safe base64), matching
     * bunny.net's reference implementation: the hash input is
     * key + DECODED path + expires, while the returned URL keeps the
     * encoded path. Extra query params added later by the player
     * (e.g. cache busters on the passthrough route) never reach this
     * URL, so basic path signing is sufficient.
     */
    private function signBunnyUrl(string $cdnUrl, string $tokenKey, int $ttl): string
    {
        $expires = time() + max(60, $ttl);
        $signaturePath = urldecode((string) parse_url($cdnUrl, PHP_URL_PATH));

        $token = base64_encode(hash('sha256', $tokenKey . $signaturePath . $expires, true));
        $token = trim(strtr($token, '+/', '-_'), '=');

        return $cdnUrl . '?token=' . $token . '&expires=' . $expires;
    }
}
