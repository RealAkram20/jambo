<?php

namespace Modules\Seo\app\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Vj;

/**
 * Generates /sitemap.xml on demand. Cached for 6 hours so a Googlebot
 * burst doesn't re-scan the published table on every hit.
 *
 * The entries() method is reused by the admin SEO settings page so
 * operators can see exactly what the sitemap is publishing without
 * having to read XML.
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
        if (!setting('seo.sitemap_enabled', true)) {
            return $this->emptySitemap();
        }

        try {
            $xml = Cache::remember('seo.sitemap.xml', now()->addHours(6), function () {
                $entries = $this->entries();
                $flat = collect();
                foreach ($entries as $group) {
                    foreach ($group as $entry) {
                        $flat->push($entry);
                    }
                }
                return $this->renderXml($flat);
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

    /**
     * Public entry-point used by both the XML output and the admin
     * preview. Returns a structured array grouped by content type so
     * the admin form can render counts + samples per group.
     *
     * Each section is wrapped in try/catch so one bad table or
     * missing route doesn't take out the whole sitemap. Errors are
     * logged with file:line for diagnosis.
     *
     * @return array{static: Collection, movies: Collection, shows: Collection, episodes: Collection, vjs: Collection, pages: Collection}
     */
    public function entries(): array
    {
        return [
            'static'   => $this->buildStatic(),
            'pages'    => $this->buildPages(),
            'movies'   => $this->buildMovies(),
            'shows'    => $this->buildShows(),
            'episodes' => $this->buildEpisodes(),
            'vjs'      => $this->buildVjs(),
        ];
    }

    /**
     * High-traffic static landing pages (home, /movie, /series,
     * /upcoming). These don't change often but they're the front
     * door — having them in the sitemap helps Google index priority.
     */
    private function buildStatic(): Collection
    {
        $rows = collect();
        $defs = [
            ['name' => 'frontend.ott',      'priority' => '1.0', 'changefreq' => 'daily'],
            ['name' => 'frontend.movie',    'priority' => '0.9', 'changefreq' => 'daily'],
            ['name' => 'frontend.series',   'priority' => '0.9', 'changefreq' => 'daily'],
            ['name' => 'frontend.upcoming', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ];
        foreach ($defs as $def) {
            try {
                $rows->push([
                    'loc'        => route($def['name']),
                    'changefreq' => $def['changefreq'],
                    'priority'   => $def['priority'],
                    'label'      => $def['name'],
                ]);
            } catch (\Throwable $e) {
                Log::debug('[seo] static route skipped', ['name' => $def['name'], 'err' => $e->getMessage()]);
            }
        }
        return $rows;
    }

    /**
     * System content pages (About, Contact, FAQ, Terms, Privacy,
     * Pricing). All are admin-managed via the Pages module but
     * surfaced via well-known route names.
     */
    private function buildPages(): Collection
    {
        $rows = collect();
        $defs = [
            ['name' => 'frontend.about_us',         'priority' => '0.5', 'changefreq' => 'monthly', 'label' => 'About'],
            ['name' => 'frontend.contact_us',       'priority' => '0.5', 'changefreq' => 'monthly', 'label' => 'Contact'],
            ['name' => 'frontend.faq_page',         'priority' => '0.5', 'changefreq' => 'monthly', 'label' => 'FAQ'],
            ['name' => 'frontend.terms-and-policy', 'priority' => '0.3', 'changefreq' => 'yearly',  'label' => 'Terms'],
            ['name' => 'frontend.privacy-policy',   'priority' => '0.3', 'changefreq' => 'yearly',  'label' => 'Privacy'],
            ['name' => 'frontend.pricing-page',     'priority' => '0.6', 'changefreq' => 'monthly', 'label' => 'Pricing'],
        ];
        foreach ($defs as $def) {
            try {
                $rows->push([
                    'loc'        => route($def['name']),
                    'changefreq' => $def['changefreq'],
                    'priority'   => $def['priority'],
                    'label'      => $def['label'],
                ]);
            } catch (\Throwable $e) {
                Log::debug('[seo] page route skipped', ['name' => $def['name'], 'err' => $e->getMessage()]);
            }
        }
        return $rows;
    }

    private function buildMovies(): Collection
    {
        if (!Schema::hasTable('movies')) return collect();

        $rows = collect();
        try {
            Movie::published()
                ->select(['id', 'slug', 'title', 'updated_at'])
                ->orderByDesc('updated_at')
                ->chunk(500, function ($chunk) use ($rows) {
                    foreach ($chunk as $movie) {
                        $rows->push([
                            'loc'        => route('frontend.movie_detail', $movie->slug),
                            'lastmod'    => optional($movie->updated_at)->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority'   => '0.8',
                            'label'      => $movie->title,
                        ]);
                    }
                });
        } catch (\Throwable $e) {
            Log::warning('[seo] sitemap movies failed', ['err' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }
        return $rows;
    }

    private function buildShows(): Collection
    {
        if (!Schema::hasTable('shows')) return collect();

        $rows = collect();
        try {
            Show::published()
                ->select(['id', 'slug', 'title', 'updated_at'])
                ->orderByDesc('updated_at')
                ->chunk(500, function ($chunk) use ($rows) {
                    foreach ($chunk as $show) {
                        $rows->push([
                            'loc'        => route('frontend.series_detail', $show->slug),
                            'lastmod'    => optional($show->updated_at)->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority'   => '0.8',
                            'label'      => $show->title,
                        ]);
                    }
                });
        } catch (\Throwable $e) {
            Log::warning('[seo] sitemap shows failed', ['err' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }
        return $rows;
    }

    /**
     * Episodes — eager-load the season + show so we can build the
     * pretty /episode/<slug>/s<num>/ep<num> URL via the model's
     * frontendUrl() helper. That helper handles orphans (missing
     * season / show) gracefully so we don't have to filter here.
     */
    private function buildEpisodes(): Collection
    {
        if (!Schema::hasTable('episodes')) return collect();

        $rows = collect();
        try {
            Episode::query()
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->with(['season.show'])
                ->select(['id', 'season_id', 'number', 'title', 'updated_at', 'published_at'])
                ->orderByDesc('updated_at')
                ->chunk(500, function ($chunk) use ($rows) {
                    foreach ($chunk as $episode) {
                        $url = $episode->frontendUrl();
                        if ($url === '#') continue; // orphan — skip
                        $rows->push([
                            'loc'        => $url,
                            'lastmod'    => optional($episode->updated_at)->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority'   => '0.7',
                            'label'      => trim(($episode->season?->show?->title ?? '') . ' — ' . ($episode->title ?? ('Ep ' . $episode->number)), ' —'),
                        ]);
                    }
                });
        } catch (\Throwable $e) {
            Log::warning('[seo] sitemap episodes failed', ['err' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }
        return $rows;
    }

    private function buildVjs(): Collection
    {
        if (!Schema::hasTable('vjs')) return collect();

        $rows = collect();
        try {
            Vj::query()
                ->select(['id', 'slug', 'name', 'updated_at'])
                ->orderByDesc('updated_at')
                ->chunk(500, function ($chunk) use ($rows) {
                    foreach ($chunk as $vj) {
                        $rows->push([
                            'loc'        => route('frontend.vj_detail', $vj->slug),
                            'lastmod'    => optional($vj->updated_at)->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority'   => '0.6',
                            'label'      => $vj->name ?? $vj->slug,
                        ]);
                    }
                });
        } catch (\Throwable $e) {
            Log::warning('[seo] sitemap vjs failed', ['err' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }
        return $rows;
    }

    private function renderXml(Collection $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        // Browser-side stylesheet — turns the raw XML into a styled HTML
        // table when a human visits /sitemap.xml in a browser. Crawlers
        // (Googlebot, Bingbot) ignore this PI and read the XML directly.
        // The XSL file lives at public/sitemap.xsl, served as a static
        // file by the web server.
        $xml .= '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>' . "\n";
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
            . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>' . "\n";
        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
