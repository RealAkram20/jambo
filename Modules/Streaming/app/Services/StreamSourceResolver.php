<?php

namespace Modules\Streaming\app\Services;

use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;

/**
 * Given a title and a rendition, the URL a player should actually open.
 *
 * Two jobs, and both were previously locked inside
 * StreamProxyController::getRawUrl(): pick the right column for the requested
 * quality (with the historic dropbox_path fallback), then hand it to
 * CdnUrlResolver, which rewrites a Backblaze URL onto the Bunny pull zone and
 * token-signs it.
 *
 * It was extracted when /api/v1 needed the same answer. The API cannot follow
 * the website's 302 — it asks for the resolved URL and gives it to a native
 * player — so without this the rendition rules would have been written twice,
 * which is the mistake PlaybackAuthorizer had just finished undoing.
 *
 * This service answers "where are the bytes". It deliberately does NOT ask
 * whether the viewer may have them; that is PlaybackAuthorizer's job, and
 * keeping them apart is what lets the download endpoints in Phase 3 authorize
 * once and resolve a fresh URL on every cache open.
 */
class StreamSourceResolver
{
    public const QUALITY_DEFAULT = 'default';
    public const QUALITY_LOW = 'low';

    public const QUALITIES = [self::QUALITY_DEFAULT, self::QUALITY_LOW];

    public function __construct(private readonly CdnUrlResolver $cdn)
    {
    }

    /**
     * The playable URL, or null when this title has no file for that quality.
     *
     * Note what "low" does NOT do: it does not fall back to the default
     * rendition. A Data Saver request that silently returned the full-size
     * file would burn a viewer's bundle without telling them, which is the
     * one thing Data Saver exists to prevent. The caller decides.
     */
    public function resolve(Movie|Episode $content, string $quality = self::QUALITY_DEFAULT): ?string
    {
        $url = $quality === self::QUALITY_LOW
            ? ($content->video_url_low ?? null)
            : ($content->video_url ?? null);

        // Historic shape: titles added before video_url existed carry their
        // link in dropbox_path. Only the default rendition ever had one.
        if (! $url && $quality !== self::QUALITY_LOW && ! empty($content->dropbox_path)) {
            $url = $content->dropbox_path;
        }

        if (! $url) {
            return null;
        }

        return $this->cdn->resolve($url);
    }

    /**
     * Is this title a YouTube embed rather than a file we host?
     *
     * Those are played in an iframe by the website and cannot be downloaded
     * or cached, so the app has to know before it offers either.
     */
    public function isEmbed(Movie|Episode $content): bool
    {
        return ($content->streamSource()['type'] ?? null) === 'youtube';
    }

    /** Which renditions this title actually has a file for. */
    public function availableQualities(Movie|Episode $content): array
    {
        return array_values(array_filter(
            self::QUALITIES,
            fn (string $quality) => $this->resolve($content, $quality) !== null,
        ));
    }
}
