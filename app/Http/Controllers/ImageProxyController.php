<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Responses\SymfonyResponseFactory;
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
 * addressable. External (https://...) URLs are NOT proxied here;
 * the media_img() helper passes them through unchanged because
 * Glide can't fetch remote sources without extra setup.
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

        $server = ServerFactory::create([
            // SymfonyResponseFactory is bundled with core league/glide.
            // Laravel's HTTP Response extends Symfony's, so the
            // StreamedResponse Glide returns flows through Laravel's
            // kernel cleanly without a glue package.
            'response' => new SymfonyResponseFactory($request),
            'source'   => public_path(),
            'cache'    => storage_path('app/glide-cache'),
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
            return $server->getImageResponse($path, $params);
        } catch (FileNotFoundException) {
            abort(404);
        } catch (\Throwable $e) {
            Log::warning('[image-proxy] resize failed', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
            abort(404);
        }
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
