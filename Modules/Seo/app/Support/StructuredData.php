<?php

namespace Modules\Seo\app\Support;

use Illuminate\Support\Str;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;

/**
 * Builds schema.org JSON-LD graphs for the public content pages.
 *
 * Why this exists: Google does NOT use og:image for the thumbnail it
 * shows next to a search result — og:image only drives social share
 * cards. Without structured data, Google picks an image off the page
 * by its own heuristics, and on our detail pages the first crawlable
 * <img> tags are the site logo, the IMDb badge and the Hulu badge —
 * which is exactly why we were ranking for titles but rendering the
 * Jambo logo as the result thumbnail.
 *
 * The `image` array below is the authoritative signal that fixes it.
 * Poster first (portrait is what Google prefers for a film), backdrop
 * second, then the cropped 16:9 / 1:1 variants Google asks for in its
 * Movie rich-result guidance.
 *
 * Everything here is defensive: a missing synopsis, a null year, an
 * orphaned episode or an empty cast must degrade to a smaller-but-valid
 * graph, never to an exception. A thrown exception in a <head> partial
 * takes down the whole page, and these pages are the ones that rank.
 */
class StructuredData
{
    /**
     * Google's Movie/TVEpisode guidance asks for the same still in
     * multiple aspect ratios and picks whichever fits the surface it's
     * rendering (16:9 for desktop, 4:3 and 1:1 for mobile/Discover).
     */
    private const CROPS = [
        ['w' => 1200, 'h' => 675], // 16:9
        ['w' => 1200, 'h' => 900], // 4:3
        ['w' => 1200, 'h' => 1200], // 1:1
    ];

    // ---------------------------------------------------------------
    // Entity graphs
    // ---------------------------------------------------------------

    public static function movie(Movie $movie): array
    {
        $url = route('frontend.movie_detail', $movie->slug);

        $graph = array_filter([
            '@context'      => 'https://schema.org',
            '@type'         => 'Movie',
            '@id'           => $url . '#movie',
            'url'           => $url,
            'name'          => $movie->title,
            'description'   => self::description($movie->synopsis, $movie->title),
            'image'         => self::images($movie->poster_url, $movie->backdrop_url),
            'datePublished' => $movie->year ? (string) $movie->year : null,
            'duration'      => self::duration($movie->runtime_minutes),
            'contentRating' => $movie->rating ?: null,
            'genre'         => self::names($movie, 'genres'),
            'actor'         => self::people($movie, 'actor'),
            'director'      => self::people($movie, 'director'),
            'inLanguage'    => self::language($movie),
            'contributor'   => self::vjs($movie),
            'aggregateRating' => self::aggregateRating($movie),
            'trailer'       => self::trailer($movie, $url),
            'potentialAction' => self::watchAction(
                route('frontend.watch', $movie->slug)
            ),
        ], static fn ($v) => $v !== null && $v !== [] && $v !== '');

        return $graph;
    }

    public static function tvSeries(Show $show): array
    {
        $url = route('frontend.series_detail', $show->slug);

        // seasons.episodes is eager-loaded by tvshow_detail(), so these
        // counts cost nothing. Guard on relationLoaded anyway so a
        // caller that didn't load them doesn't silently N+1.
        $seasons = $show->relationLoaded('seasons') ? $show->seasons : collect();

        return array_filter([
            '@context'         => 'https://schema.org',
            '@type'            => 'TVSeries',
            '@id'              => $url . '#series',
            'url'              => $url,
            'name'             => $show->title,
            'description'      => self::description($show->synopsis, $show->title),
            'image'            => self::images($show->poster_url, $show->backdrop_url),
            'datePublished'    => $show->year ? (string) $show->year : null,
            'contentRating'    => $show->rating ?: null,
            'genre'            => self::names($show, 'genres'),
            'actor'            => self::people($show, 'actor'),
            'director'         => self::people($show, 'director'),
            'inLanguage'       => self::language($show),
            'contributor'      => self::vjs($show),
            'aggregateRating'  => self::aggregateRating($show),
            'numberOfSeasons'  => $seasons->count() ?: null,
            'numberOfEpisodes' => $seasons->sum(
                static fn ($s) => $s->relationLoaded('episodes') ? $s->episodes->count() : 0
            ) ?: null,
            'trailer'          => self::trailer($show, $url),
        ], static fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    /**
     * TVEpisode, with the parent series + season nested by reference so
     * Google can stitch the show together across pages.
     */
    public static function tvEpisode(Episode $episode, Show $show, Season $season): array
    {
        $url = $episode->frontendUrl($show);
        if ($url === '#') {
            return []; // orphan — no stable URL, emit nothing
        }

        $seriesUrl = route('frontend.series_detail', $show->slug);

        // The episode still is the right image here; fall back to the
        // show's artwork so an episode without a still still gets a
        // correct (if less specific) thumbnail rather than the logo.
        $primary  = $episode->still_url ?: $show->poster_url;
        $fallback = $show->backdrop_url;

        return array_filter([
            '@context'      => 'https://schema.org',
            '@type'         => 'TVEpisode',
            '@id'           => $url . '#episode',
            'url'           => $url,
            'name'          => $episode->title ?: ('Episode ' . $episode->number),
            'episodeNumber' => $episode->number,
            'description'   => self::description(
                $episode->synopsis ?: $show->synopsis,
                $show->title
            ),
            'image'         => self::images($primary, $fallback),
            'duration'      => self::duration($episode->runtime_minutes),
            'datePublished' => $episode->published_at?->toDateString(),
            'inLanguage'    => self::language($show),
            'partOfSeason'  => array_filter([
                '@type'        => 'TVSeason',
                'seasonNumber' => $season->number,
                'name'         => 'Season ' . $season->number,
                'url'          => $seriesUrl,
            ]),
            'partOfSeries'  => array_filter([
                '@type' => 'TVSeries',
                '@id'   => $seriesUrl . '#series',
                'name'  => $show->title,
                'url'   => $seriesUrl,
                'image' => self::images($show->poster_url, $show->backdrop_url),
            ], static fn ($v) => $v !== null && $v !== [] && $v !== ''),
            'potentialAction' => self::watchAction($url),
        ], static fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    /**
     * VideoObject — what makes a page eligible for the Videos tab and
     * the video thumbnail in web results. Google requires name,
     * description, thumbnailUrl and uploadDate; without all four it
     * silently drops the item, so we bail rather than emit a partial.
     */
    public static function videoObject(
        string $name,
        ?string $description,
        ?string $primaryImage,
        ?string $fallbackImage,
        ?\DateTimeInterface $uploadDate,
        ?int $runtimeMinutes,
        string $pageUrl,
        ?string $contentUrl = null
    ): array {
        $thumbs = self::images($primaryImage, $fallbackImage);

        if ($thumbs === [] || $uploadDate === null) {
            return [];
        }

        return array_filter([
            '@context'     => 'https://schema.org',
            '@type'        => 'VideoObject',
            '@id'          => $pageUrl . '#video',
            'name'         => $name,
            'description'  => self::description($description, $name),
            'thumbnailUrl' => $thumbs,
            'uploadDate'   => $uploadDate->format(\DateTimeInterface::ATOM),
            'duration'     => self::duration($runtimeMinutes),
            'embedUrl'     => $contentUrl ?: null,
            'potentialAction' => self::watchAction($pageUrl),
        ], static fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    /**
     * @param array<int, array{name: string, url: string}> $trail
     */
    public static function breadcrumbs(array $trail): array
    {
        if ($trail === []) {
            return [];
        }

        $items = [];
        foreach (array_values($trail) as $i => $crumb) {
            if (empty($crumb['name']) || empty($crumb['url'])) {
                continue;
            }
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['name'],
                'item'     => $crumb['url'],
            ];
        }

        if ($items === []) {
            return [];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Site-wide Organization + WebSite. The WebSite node carries the
     * sitelinks searchbox action; the Organization node is what Google
     * uses for the knowledge panel and the favicon/name in results.
     */
    public static function organization(): array
    {
        $home = url('/');
        $logo = self::absolute(setting('seo.og_default_image', '') ?: branded_logo());

        return array_filter([
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            '@id'      => $home . '#organization',
            'name'     => app_name(),
            'url'      => $home,
            'logo'     => $logo ?: null,
            'sameAs'   => self::socialProfiles(),
        ], static fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    public static function webSite(): array
    {
        $home = url('/');

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            '@id'             => $home . '#website',
            'name'            => app_name(),
            'url'             => $home,
            'publisher'       => ['@id' => $home . '#organization'],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $home . '?s={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    // ---------------------------------------------------------------
    // Shared builders
    // ---------------------------------------------------------------

    /**
     * The image array Google actually reads. Order matters — the first
     * entry is what it reaches for when it only needs one.
     *
     * Locally-hosted images (/storage/..., legacy bare filenames) get
     * routed through the Glide proxy to produce the 16:9 / 4:3 / 1:1
     * crops Google asks for. External URLs (Dropbox, CDN) can't be
     * proxied, so they're emitted as-is — still a correct image, just
     * without the extra ratios.
     */
    private static function images(?string $primary, ?string $secondary = null): array
    {
        $out = [];

        foreach ([$primary, $secondary] as $raw) {
            if (empty($raw)) {
                continue;
            }

            $direct = self::absolute(media_url($raw));
            if ($direct === '' || in_array($direct, $out, true)) {
                continue;
            }
            $out[] = $direct;

            // Only the primary image gets the cropped variants — three
            // ratios of the backdrop as well would just be noise.
            if ($raw !== $primary || self::isExternal($raw)) {
                continue;
            }

            foreach (self::CROPS as $crop) {
                $cropped = self::croppedUrl($raw, $crop['w'], $crop['h']);
                if ($cropped !== '' && !in_array($cropped, $out, true)) {
                    $out[] = $cropped;
                }
            }
        }

        return $out;
    }

    /**
     * Build a /img/ proxy URL with fit=crop at an exact aspect ratio.
     * Mirrors media_img()'s path convention exactly — see app/helpers.php.
     */
    private static function croppedUrl(string $value, int $w, int $h): string
    {
        if (self::isExternal($value)) {
            return '';
        }

        $path = str_starts_with($value, '/')
            ? ltrim($value, '/')
            : 'frontend/images/' . ltrim($value, '/');

        // Glide only handles real raster sources; an SVG would 404 the
        // proxy and hand Google a dead image URL.
        if (!preg_match('/\.(jpe?g|png|webp|gif)$/i', $path)) {
            return '';
        }

        return url('img/' . $path) . '?' . http_build_query([
            'w'   => $w,
            'h'   => $h,
            'fit' => 'crop',
            'fm'  => 'jpg',
        ]);
    }

    private static function isExternal(string $value): bool
    {
        return (bool) preg_match('#^https?://#i', $value);
    }

    private static function absolute(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (self::isExternal($value)) {
            return $value;
        }
        return url(ltrim($value, '/'));
    }

    /**
     * Google truncates meta descriptions around 160 chars; schema.org
     * descriptions can run longer, but there's no value in shipping a
     * wall of text, so we cap at a readable length either way.
     */
    private static function description(?string $raw, string $fallbackSubject): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags((string) $raw)) ?? '');

        if ($clean === '') {
            return 'Watch ' . $fallbackSubject . ' free on ' . app_name() . '.';
        }

        return Str::limit($clean, 300);
    }

    private static function duration(?int $minutes): ?string
    {
        return $minutes && $minutes > 0 ? 'PT' . $minutes . 'M' : null;
    }

    /**
     * Pull names off an already-loaded belongsToMany relation. Returns
     * [] (not null) when the relation isn't loaded so we never trigger
     * a lazy query from inside a <head> render.
     */
    private static function names($model, string $relation): array
    {
        if (!$model->relationLoaded($relation)) {
            return [];
        }

        return $model->{$relation}
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Cast/crew as schema.org Person nodes, filtered by the pivot role.
     * The pivot may carry several roles for one person (actor AND
     * director), which is why we filter rather than partition.
     *
     * Person stores first_name/last_name and exposes them through the
     * full_name accessor — there is no `name` column, so reading $p->name
     * here would silently emit `"name": null` for every actor.
     */
    private static function people($model, string $role): array
    {
        if (!$model->relationLoaded('cast')) {
            return [];
        }

        return $model->cast
            ->filter(static fn ($p) => strtolower((string) ($p->pivot->role ?? '')) === $role)
            ->sortBy(static fn ($p) => $p->pivot->display_order ?? 0)
            ->unique('id')
            ->take(15) // Google ignores long tails; keep the payload small.
            ->map(static function ($p) {
                $name = trim((string) $p->full_name);
                if ($name === '') {
                    return null;
                }
                $node = ['@type' => 'Person', 'name' => $name];
                $character = $p->pivot->character_name ?? null;
                if ($character) {
                    $node['characterName'] = $character;
                }
                return $node;
            })
            ->filter() // drop nameless rows — a Person node with no name is invalid
            ->values()
            ->all();
    }

    /**
     * The VJ (voice-over artist) is the whole reason people search for
     * these titles — "spider man vj junior" is the actual query. There's
     * no schema.org type for a live translator, so they're emitted as
     * contributors, which is the closest honest mapping.
     */
    private static function vjs($model): array
    {
        if (!$model->relationLoaded('vjs')) {
            return [];
        }

        return $model->vjs
            ->map(static fn ($vj) => ['@type' => 'Person', 'name' => $vj->name])
            ->filter(static fn ($n) => !empty($n['name']))
            ->values()
            ->all();
    }

    /**
     * A VJ-translated title is performed in Luganda over the original
     * English audio. Titles with no VJ attached are left as English.
     */
    private static function language($model): array|string
    {
        $hasVj = $model->relationLoaded('vjs') && $model->vjs->isNotEmpty();

        return $hasVj ? ['lg', 'en'] : 'en';
    }

    /**
     * Only emit aggregateRating when the controller has actually
     * aggregated real user ratings (withAvg/withCount). Inventing a
     * rating — or emitting one with ratingCount 0 — is a structured-data
     * violation and risks a manual action, so silence is the safe default.
     */
    private static function aggregateRating($model): ?array
    {
        $count = (int) ($model->ratings_count ?? 0);
        $avg   = $model->ratings_avg_stars ?? null;

        if ($count < 1 || $avg === null) {
            return null;
        }

        return [
            '@type'       => 'AggregateRating',
            'ratingValue' => round((float) $avg, 1),
            'ratingCount' => $count,
            'bestRating'  => 5,
            'worstRating' => 1,
        ];
    }

    private static function trailer($model, string $pageUrl): ?array
    {
        if (empty($model->trailer_url)) {
            return null;
        }

        return array_filter([
            '@type'        => 'VideoObject',
            'name'         => $model->title . ' — Trailer',
            'description'  => self::description($model->synopsis, $model->title),
            'thumbnailUrl' => self::images($model->poster_url, $model->backdrop_url),
            'embedUrl'     => $model->trailer_url,
            'url'          => $pageUrl,
        ], static fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    private static function watchAction(string $target): array
    {
        return [
            '@type'  => 'WatchAction',
            'target' => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $target,
                'actionPlatform' => [
                    'http://schema.org/DesktopWebPlatform',
                    'http://schema.org/MobileWebPlatform',
                ],
            ],
        ];
    }

    /**
     * Social profiles feed the Organization node's sameAs, which is how
     * Google links the site to its social accounts in a knowledge panel.
     * Reuses whatever the operator already set in the general settings.
     */
    private static function socialProfiles(): array
    {
        $keys = ['facebook_url', 'twitter_url', 'instagram_url', 'youtube_url', 'tiktok_url'];

        $out = [];
        foreach ($keys as $key) {
            $value = trim((string) setting($key, ''));
            if ($value !== '' && preg_match('#^https?://#i', $value)) {
                $out[] = $value;
            }
        }

        return $out;
    }
}
