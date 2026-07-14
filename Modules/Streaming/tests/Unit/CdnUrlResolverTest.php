<?php

namespace Modules\Streaming\tests\Unit;

use Modules\Streaming\app\Services\CdnUrlResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Locks the zone-driven resolver's behavior. The "unchanged" group is
 * the regression guard: with only the original B2 zone configured, the
 * output must match the pre-refactor resolver exactly.
 */
class CdnUrlResolverTest extends TestCase
{
    private function setZones(array $zones): void
    {
        config(['streaming.cdn.zones' => $zones]);
    }

    private function backblazeZone(array $overrides = []): array
    {
        return array_merge([
            'driver'    => 'backblaze',
            'bucket'    => 'JamboFilms',
            'hostname'  => 'jambofilms.b-cdn.net',
            'token_key' => null,
            'token_ttl' => 28800,
        ], $overrides);
    }

    private function dropboxZone(array $overrides = []): array
    {
        return array_merge([
            'driver'    => 'dropbox',
            'hostname'  => null,
            'token_key' => null,
            'token_ttl' => 28800,
        ], $overrides);
    }

    // ── regression: current production behavior ──────────────────────

    #[Test]
    public function backblaze_friendly_url_is_rewritten_to_the_pull_zone(): void
    {
        $this->setZones(['backblaze' => $this->backblazeZone()]);

        $out = app(CdnUrlResolver::class)->resolve(
            'https://f005.backblazeb2.com/file/JamboFilms/movies/heat/original.mp4'
        );

        $this->assertSame('https://jambofilms.b-cdn.net/movies/heat/original.mp4', $out);
    }

    #[Test]
    public function backblaze_s3_virtual_host_and_path_styles_are_rewritten(): void
    {
        $this->setZones(['backblaze' => $this->backblazeZone()]);
        $resolver = app(CdnUrlResolver::class);

        $this->assertSame(
            'https://jambofilms.b-cdn.net/a/b.mp4',
            $resolver->resolve('https://JamboFilms.s3.us-west-004.backblazeb2.com/a/b.mp4')
        );
        $this->assertSame(
            'https://jambofilms.b-cdn.net/a/b.mp4',
            $resolver->resolve('https://s3.us-west-004.backblazeb2.com/JamboFilms/a/b.mp4')
        );
    }

    #[Test]
    public function a_third_party_backblaze_bucket_is_left_untouched(): void
    {
        $this->setZones(['backblaze' => $this->backblazeZone()]);

        $url = 'https://f005.backblazeb2.com/file/SomeoneElse/x.mp4';
        $this->assertSame($url, app(CdnUrlResolver::class)->resolve($url));
    }

    #[Test]
    public function dropbox_without_a_zone_hostname_is_only_normalized_to_raw(): void
    {
        $this->setZones([
            'backblaze' => $this->backblazeZone(),
            'dropbox'   => $this->dropboxZone(),
        ]);

        $out = app(CdnUrlResolver::class)->resolve(
            'https://www.dropbox.com/scl/fi/abc/heat.mp4?rlkey=xyz&dl=1'
        );

        $this->assertStringStartsWith('https://www.dropbox.com/scl/fi/abc/heat.mp4?', $out);
        $this->assertStringContainsString('rlkey=xyz', $out);
        $this->assertStringContainsString('raw=1', $out);
        $this->assertStringNotContainsString('dl=1', $out);
    }

    #[Test]
    public function youtube_and_unknown_urls_pass_through_untouched(): void
    {
        $this->setZones([
            'backblaze' => $this->backblazeZone(),
            'dropbox'   => $this->dropboxZone(),
        ]);
        $resolver = app(CdnUrlResolver::class);

        foreach ([
            'https://www.youtube.com/watch?v=abc123',
            'https://media.example.com/clip.mp4',
        ] as $url) {
            $this->assertSame($url, $resolver->resolve($url));
        }
    }

    #[Test]
    public function no_zones_configured_returns_the_url_verbatim(): void
    {
        $this->setZones([]);

        $url = 'https://f005.backblazeb2.com/file/JamboFilms/x.mp4';
        $this->assertSame($url, app(CdnUrlResolver::class)->resolve($url));
    }

    // ── new capability: signing + Dropbox/host zones ─────────────────

    #[Test]
    public function backblaze_url_is_signed_when_the_zone_has_a_token_key(): void
    {
        $this->setZones(['backblaze' => $this->backblazeZone(['token_key' => 'secret-key'])]);

        $out = app(CdnUrlResolver::class)->resolve(
            'https://f005.backblazeb2.com/file/JamboFilms/a.mp4'
        );

        $this->assertStringStartsWith('https://jambofilms.b-cdn.net/a.mp4?token=', $out);
        $this->assertStringContainsString('&expires=', $out);
    }

    #[Test]
    public function dropbox_is_routed_through_the_pull_zone_when_hostname_is_set(): void
    {
        $this->setZones([
            'dropbox' => $this->dropboxZone(['hostname' => 'jambo-dbx.b-cdn.net']),
        ]);

        $out = app(CdnUrlResolver::class)->resolve(
            'https://www.dropbox.com/scl/fi/abc/heat.mp4?rlkey=xyz&dl=1'
        );

        // Host swapped to the CDN; the REQUIRED rlkey query survives.
        $this->assertStringStartsWith('https://jambo-dbx.b-cdn.net/scl/fi/abc/heat.mp4?', $out);
        $this->assertStringContainsString('rlkey=xyz', $out);
        $this->assertStringContainsString('raw=1', $out);
    }

    #[Test]
    public function signed_dropbox_url_appends_the_token_after_the_existing_query(): void
    {
        $this->setZones([
            'dropbox' => $this->dropboxZone([
                'hostname'  => 'jambo-dbx.b-cdn.net',
                'token_key' => 'secret-key',
            ]),
        ]);

        $out = app(CdnUrlResolver::class)->resolve(
            'https://www.dropbox.com/scl/fi/abc/heat.mp4?rlkey=xyz'
        );

        $this->assertStringContainsString('rlkey=xyz', $out);
        $this->assertStringContainsString('&token=', $out);
        $this->assertStringContainsString('&expires=', $out);
    }

    #[Test]
    public function a_host_zone_fronts_an_arbitrary_origin_by_config_alone(): void
    {
        $this->setZones([
            'external' => [
                'driver'      => 'host',
                'origin_host' => 'media.example.com',
                'hostname'    => 'jambo-ext.b-cdn.net',
                'token_key'   => null,
                'token_ttl'   => 28800,
            ],
        ]);

        $out = app(CdnUrlResolver::class)->resolve('https://media.example.com/a/clip.mp4?v=2');

        $this->assertSame('https://jambo-ext.b-cdn.net/a/clip.mp4?v=2', $out);
    }
}
