<?php

namespace Modules\Seo\app\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Vj;

/**
 * Generates /sitemap.xml on demand. Cached for 6 hours so a Googlebot
 * burst doesn't re-scan the published table on every hit. The DB
 * itself caches its own queries, but XML serialisation isn't free
 * and the result rarely changes within a 6-hour window for a small
 * library.
 *
 * Returns an empty <urlset> when:
 *   - The seo.sitemap_enabled setting is off, OR
 *   - Required tables don't exist (fresh install before migrations).
 *
 * Empty sitemap is valid XML; Googlebot just records "no URLs to
 * crawl yet" rather than treating the response as broken.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        // Operator kill-switch. Default is on — a sitemap is harmless
        // even when content is sparse, and missing one is a missed
        // discovery opportunity for new uploads.
        if (!setting('seo.sitemap_enabled', true)) {
            return $this->emptySitemap();
        }

        // Outer try/catch: anything inside buildXml or the cache driver
        // that throws (rare cache backend failure, an unexpected DB
        // shape, an unloaded route, etc.) is logged but doesn't bubble
        // up as a 500. Crawlers see an empty but valid sitemap; we see
        // the stack trace in storage/logs/laravel.log.
        try {
            $xml = Cache::remember('seo.sitemap.xml', now()->addHours(6), function () {
                return $this->buildXml();
            });
        } catch (\Throwable $e) {
            Log::warning('[seo] sitemap build failed; serving empty', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return $this->emptySitemap();
        }

        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function buildXml(): string
    {
        $urls = collect();

        // Static high-value entry points. Only include routes that
        // actually exist — a 404 in the sitemap looks broken to
        // crawlers and dings perceived site quality.
        $staticRoutes = [
            ['name' => 'frontend.ott',      'priority' => '1.0', 'changefreq' => 'daily'],
            ['name' => 'frontend.movie',    'priority' => '0.9', 'changefreq' => 'daily'],
            ['name' => 'frontend.series',   'priority' => '0.9', 'changefreq' => 'daily'],
            ['name' => 'frontend.upcoming', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ];

        foreach ($staticRoutes as $row) {
            try {
                $urls->push([
                    'loc'        => route($row['name']),
                    'changefreq' => $row['changefreq'],
                    'priority'   => $row['priority'],
                ]);
            } catch (\Throwable $e) {
                Log::debug('[seo] static route skipped', ['name' => $row['name'], 'err' => $e->getMessage()]);
            }
        }

        // Movies. published() scope already enforces status=published
        // + published_at <= now(), so we won't leak draft / scheduled
        // titles into the sitemap.
        if (Schema::hasTable('movies')) {
            try {
                Movie::published()
                    ->select(['slug', 'updated_at'])
                    ->orderByDesc('updated_at')
                    ->chunk(500, function ($chunk) use ($urls) {
                        foreach ($chunk as $movie) {
                            $urls->push([
                                'loc'        => route('frontend.movie_detail', $movie->slug),
                                'lastmod'    => optional($movie->updated_at)->toAtomString(),
                                'changefreq' => 'weekly',
                                'priority'   => '0.8',
                            ]);
                        }
                    });
            } catch (\Throwable $e) {
                Log::warning('[seo] sitemap section failed', ['section' => 'movies', 'err' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
        }

        // Shows.
        if (Schema::hasTable('shows')) {
            try {
                Show::published()
                    ->select(['slug', 'updated_at'])
                    ->orderByDesc('updated_at')
                    ->chunk(500, function ($chunk) use ($urls) {
                        foreach ($chunk as $show) {
                            $urls->push([
                                'loc'        => route('frontend.series_detail', $show->slug),
                                'lastmod'    => optional($show->updated_at)->toAtomString(),
                                'changefreq' => 'weekly',
                                'priority'   => '0.8',
                            ]);
                        }
                    });
            } catch (\Throwable $e) {
                Log::warning('[seo] sitemap section failed', ['section' => 'shows', 'err' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
        }

        // VJs (translators / narrators) — high-traffic personas in
        // Jambo's audience, worth surfacing as standalone URLs.
        if (Schema::hasTable('vjs')) {
            try {
                Vj::query()
                    ->select(['slug', 'updated_at'])
                    ->orderByDesc('updated_at')
                    ->chunk(500, function ($chunk) use ($urls) {
                        foreach ($chunk as $vj) {
                            $urls->push([
                                'loc'        => route('frontend.vj_detail', $vj->slug),
                                'lastmod'    => optional($vj->updated_at)->toAtomString(),
                                'changefreq' => 'weekly',
                                'priority'   => '0.6',
                            ]);
                        }
                    });
            } catch (\Throwable $e) {
                Log::warning('[seo] sitemap section failed', ['section' => 'vjs', 'err' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
        }

        return $this->renderXml($urls);
    }

    private function renderXml($urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            if (!empty($u['lastmod'])) {
                $xml .= "    <lastmod>" . htmlspecialchars($u['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
            }
            if (!empty($u['changefreq'])) {
                $xml .= "    <changefreq>" . $u['changefreq'] . "</changefreq>\n";
            }
            if (!empty($u['priority'])) {
                $xml .= "    <priority>" . $u['priority'] . "</priority>\n";
            }
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>' . "\n";
        return $xml;
    }

    private function emptySitemap(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>' . "\n";
        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
