<?php

namespace Modules\Seo\app\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * Dynamic robots.txt — replaces the static public/robots.txt with a
 * route so the Sitemap directive can point at the canonical URL
 * derived from APP_URL (production HTTPS host) at runtime instead of
 * being hardcoded.
 *
 * The disallow list keeps Googlebot out of:
 *   - /admin/*       — operator surface, no public value
 *   - /profile/*     — per-user surfaces, indexable would mean
 *                      indexing 5,000 user pages with thin content
 *   - /watch/src/*   — signed redirect endpoints, not real pages
 *   - /api/*         — JSON endpoints
 *   - /login, /register, /password/* — auth flows
 *
 * Otherwise everything is allowed. Each canonical content URL is
 * surfaced via the sitemap so crawlers find new uploads quickly.
 */
class RobotsController extends Controller
{
    public function index(): Response
    {
        $sitemapUrl = url('/sitemap.xml');

        // Plain string concatenation rather than heredoc — heredoc's
        // indented-closing-marker rules can choke on subtle whitespace
        // differences (tabs vs spaces, editor reformatting), which
        // surfaces as a hard PHP parse error on every request. A plain
        // implode(\n) is safer and easier to edit.
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /profile',
            'Disallow: /profile/',
            'Disallow: /watch/src',
            'Disallow: /watch/src/',
            'Disallow: /api',
            'Disallow: /api/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /password/',
            '',
            'Sitemap: ' . $sitemapUrl,
        ];

        return response(implode("\n", $lines) . "\n", 200, [
            'Content-Type'  => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
