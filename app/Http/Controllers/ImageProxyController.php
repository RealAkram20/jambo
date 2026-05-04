<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\ServerFactory;

/**
 * On-the-fly image resize + WebP transcode.
 *
 * Cuts public-facing image weight 80–90% by serving each device the
 * pixel size it actually needs (320w mobile / 640w desktop / 1280w
 * retina) instead of the original upload. The first request to a
 * given size+format combo resizes; subsequent requests come from a
 * disk cache under storage/app/glide-cache/.
 *
 * Source root is public/ — admin-uploaded posters at
 * public/frontend/images/... and any other public asset are
 * addressable (the storage symlink at public/storage covers the
 * FileManager-uploaded posters under storage/app/public/...).
 * External (https://...) URLs are NOT proxied here; the media_img()
 * helper passes them through unchanged because Glide can't fetch
 * remote sources without extra setup.
 *
 * Implementation note on response handling:
 * Core league/glide v2 only ships PsrResponseFactory. Symfony /
 * Laravel response factories live in separate league/glide-symfony
 * and league/glide-laravel bridge packages. To avoid pulling another
 * dep we drive Glide's lower-level makeImage() ourselves and stream
 * the resulting cached file via Laravel's response()->file() helper.
 * Same on-disk caching, no missing-class headaches.
 */
class ImageProxyController extends Controller
{
    private const MAX_WIDTH = 1920;
    private const MAX_HEIGHT = 1920;
    private const ALLOWED_FORMATS = ['webp', 'jpg', 'pjpg', 'png', 'gif'];

    public function show(Request $request, string $path)
    {
        // Reject anything that isn't a real image extension up front
        // so we never waste cycles trying to "resize" arbitrary files
        // (and so the route can't be turned into a generic file
        // reader by a curious attacker).
        if (!preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $path)) {
            abort(404);
        }

        $params = $this->sanitiseParams($request->query());

        $cacheRoot = storage_path('app/glide-cache');

        $server = ServerFactory::create([
            'source'   => public_path(),
            'cache'    => $cacheRoot,
            // Imagick is faster + higher quality if the extension is
            // available; GD is the universal fallback bundled with
            // PHP on every CyberPanel box.
            'driver'   => extension_loaded('imagick') ? 'imagick' : 'gd',
            'defaults' => [
                'q'  => 80,
                'fm' => 'webp',
            ],
            // Hard ceiling on source pixel count so a malicious
            // upload (e.g. 50000x50000 image bomb) can't OOM the
            // process during resize.
            'max_image_size' => 4000 * 4000,
        ]);

        try {
            // makeImage() runs the full resize+cache pipeline and
            // returns the cache filename relative to $cacheRoot.
            $cacheFilename = $server->makeImage($path, $params);
        } catch (FileNotFoundException) {
            abort(404);
        } catch (\Throwable $e) {
            Log::warning('[image-proxy] resize failed', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
            abort(404);
        }

        $absolutePath = $cacheRoot . DIRECTORY_SEPARATOR . $cacheFilename;

        if (!is_file($absolutePath)) {
            // Defensive — makeImage said it succeeded but we can't
            // find the file on disk. Likely a permissions / SELinux
            // edge case. Better to 404 than to crash in response().
            Log::warning('[image-proxy] cache file missing after makeImage', [
                'path'     => $path,
                'expected' => $absolutePath,
            ]);
            abort(404);
        }

        return response()->file($absolutePath, [
            'Content-Type' => $this->mimeTypeFor($params['fm'] ?? pathinfo($path, PATHINFO_EXTENSION)),
            // Browsers will cache the resized variant for 7 days. The
            // /img/ URL itself includes ?w=...&fm=... so changing
            // either auto-busts. The vhost-level expires rule already
            // hits image responses too — this header is here for the
            // cases where the vhost rule doesn't apply (different
            // path, different match) so the behaviour is consistent.
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    private function mimeTypeFor(string $format): string
    {
        return match (strtolower($format)) {
            'webp'                => 'image/webp',
            'png'                 => 'image/png',
            'gif'                 => 'image/gif',
            'jpg', 'jpeg', 'pjpg' => 'image/jpeg',
            'avif'                => 'image/avif',
            default               => 'application/octet-stream',
        };
    }

    /**
     * Clamp width / height / quality / format to safe values. A
     * stray `?w=99999` from a crawler shouldn't be able to ask
     * Glide to allocate gigapixels of memory.
     */
    private function sanitiseParams(array $q): array
    {
        $out = [];
        if (isset($q['w'])) {
            $out['w'] = max(1, min((int) $q['w'], self::MAX_WIDTH));
        }
        if (isset($q['h'])) {
            $out['h'] = max(1, min((int) $q['h'], self::MAX_HEIGHT));
        }
        if (isset($q['q'])) {
            $out['q'] = max(40, min((int) $q['q'], 95));
        }
        if (isset($q['fm']) && in_array($q['fm'], self::ALLOWED_FORMATS, true)) {
            $out['fm'] = $q['fm'];
        }
        // Default fit=max preserves aspect ratio without cropping.
        $out['fit'] = $q['fit'] ?? 'max';
        return $out;
    }
}
