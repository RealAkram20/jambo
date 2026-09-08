<?php

namespace Tests\Feature\Api\V1;

use App\Http\Api\ApiErrorCode;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * The spec and the routes must describe the same API.
 *
 * `docs/api/openapi.yaml` is hand-written, and a hand-written spec rots the
 * moment someone adds an endpoint and forgets it. That matters more here than
 * on a web project: the app's client is generated from this file and ships
 * inside an APK, so a mismatch is discovered by a viewer, months later, on a
 * version nobody can hot-fix.
 *
 * This does not attempt full schema validation — it checks the two things that
 * actually drift: which endpoints exist, and which error codes clients may see.
 */
class OpenApiSpecTest extends TestCase
{
    /**
     * Web routes that happen to live under /api/v1 for historical reasons.
     *
     * They are session-authenticated endpoints for the website's own
     * JavaScript, declared in routes/web.php and running in the `web` group.
     * They are not part of the app's contract and are deliberately not in the
     * spec. Anything NOT on this list must be documented.
     */
    private const WEBSITE_AJAX_ROUTES = [
        'api/v1/streaming/heartbeat',
        'api/v1/streaming/guest-view',
        'api/v1/episodes/{episode}/player-data',
        'api/v1/watchlist/{slug}/player-data',
        'api/v1/watchlist/{type}/{id}',
        'api/v1/watchlist/{id}',
        'api/v1/continue-watching/{type}/{id}',
        // Untouched module scaffold stubs. Each returns $request->user() and
        // is replaced as its module gets real endpoints.
        'api/v1/frontend',
        'api/v1/filemanager',
        'api/v1/notifications',
        'api/v1/payments',
        'api/v1/subscriptions',
        'api/v1/systemupdate',
        'api/v1/monetization',
    ];

    private function spec(): array
    {
        $path = base_path('docs/api/openapi.yaml');
        $this->assertFileExists($path, 'The API spec is missing.');

        return Yaml::parseFile($path);
    }

    public function test_the_spec_parses(): void
    {
        $spec = $this->spec();

        $this->assertSame('3.0.3', $spec['openapi']);
        $this->assertNotEmpty($spec['paths']);
    }

    public function test_every_app_endpoint_is_documented(): void
    {
        $spec = $this->spec();
        $documented = array_keys($spec['paths']);

        $undocumented = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/v1/') && $uri !== 'api/v1') {
                continue;
            }

            if (in_array($uri, self::WEBSITE_AJAX_ROUTES, true)) {
                continue;
            }

            // 'api/v1/movies/{slug}' -> '/movies/{slug}', which is how the
            // spec expresses it relative to the server URL.
            $specPath = '/' . substr($uri, strlen('api/v1/'));

            if (! in_array($specPath, $documented, true)) {
                $undocumented[$specPath] = $route->getName() ?? $uri;
            }
        }

        $this->assertSame(
            [],
            $undocumented,
            "These /api/v1 routes are not in docs/api/openapi.yaml:\n  "
            . implode("\n  ", array_keys($undocumented))
            . "\n\nDocument them in the same commit that adds them, or add them to "
            . 'WEBSITE_AJAX_ROUTES if they belong to the website rather than the app.'
        );
    }

    public function test_the_spec_documents_no_endpoint_that_does_not_exist(): void
    {
        $spec = $this->spec();

        $live = [];
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'api/v1/')) {
                $live[] = '/' . substr($route->uri(), strlen('api/v1/'));
            }
        }

        foreach (array_keys($spec['paths']) as $specPath) {
            $this->assertContains(
                $specPath,
                $live,
                "The spec describes {$specPath}, but no such route is registered. "
                . 'A client generated from this file would call an endpoint that 404s.'
            );
        }
    }

    /**
     * The enum in the spec is what a generated client can handle. A code the
     * server can emit but the spec omits arrives at the app as an unknown
     * value, and unknown values are exactly where clients fall back to a
     * generic "something went wrong" screen.
     */
    public function test_every_error_code_appears_in_the_spec_enum(): void
    {
        $spec = $this->spec();
        $documented = $spec['components']['schemas']['EnvelopeError']['properties']['code']['enum'] ?? [];

        foreach (ApiErrorCode::cases() as $case) {
            $this->assertContains(
                $case->value,
                $documented,
                "ApiErrorCode::{$case->name} ('{$case->value}') is missing from the spec's error enum."
            );
        }
    }
}
