<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * og_image_meta() feeds the og:image / twitter:image tags in
 * seo::partials.head-tags. Locally-hosted images must come out as /img/
 * Glide-proxy URLs (JPEG, capped at 1200px) with real dimensions —
 * WhatsApp drops previews for WebP/oversized images and Facebook needs
 * og:image:width/height to render the first share of a URL with its
 * image. Foreign-host URLs must pass through untouched.
 */
class OgImageMetaTest extends TestCase
{
    /** 2x1 px so the width/height maths is observable. */
    private string $fixture = 'og-image-meta-test-fixture.png';

    protected function setUp(): void
    {
        parent::setUp();

        $img = imagecreatetruecolor(2, 1);
        imagepng($img, public_path($this->fixture));
        imagedestroy($img);
    }

    protected function tearDown(): void
    {
        @unlink(public_path($this->fixture));

        parent::tearDown();
    }

    public function test_local_file_is_proxied_as_jpeg_with_dimensions(): void
    {
        config(['app.url' => 'https://jambofilms.com']);

        $meta = og_image_meta('/' . $this->fixture);

        $this->assertStringContainsString('/img/' . $this->fixture, $meta['url']);
        $this->assertStringContainsString('fm=jpg', $meta['url']);
        $this->assertSame(2, $meta['width']);
        $this->assertSame(1, $meta['height']);
        $this->assertSame('image/jpeg', $meta['type']);
    }

    /**
     * Legacy bare filenames reach head-tags as full asset() URLs on our
     * own host. Those must be unwrapped and proxied like a local path,
     * not treated as an opaque external URL.
     */
    public function test_same_host_absolute_url_is_unwrapped_and_proxied(): void
    {
        config(['app.url' => 'https://jambofilms.com']);

        $meta = og_image_meta('https://jambofilms.com/' . $this->fixture);

        $this->assertStringContainsString('/img/' . $this->fixture, $meta['url']);
        $this->assertSame(2, $meta['width']);
    }

    public function test_foreign_url_passes_through_with_type_but_no_dimensions(): void
    {
        $meta = og_image_meta('https://cdn.example.com/poster.png');

        $this->assertSame('https://cdn.example.com/poster.png', $meta['url']);
        $this->assertNull($meta['width']);
        $this->assertNull($meta['height']);
        $this->assertSame('image/png', $meta['type']);
    }

    /**
     * A path whose file isn't on disk must fall back to the plain
     * absolute URL — emitting a proxy URL that will 404 would kill the
     * preview outright.
     */
    public function test_missing_local_file_falls_back_to_plain_absolute_url(): void
    {
        config(['app.url' => 'https://jambofilms.com']);

        $meta = og_image_meta('/storage/gallery/does-not-exist.jpg');

        $this->assertStringNotContainsString('/img/', $meta['url']);
        $this->assertStringContainsString('storage/gallery/does-not-exist.jpg', $meta['url']);
        $this->assertNull($meta['width']);
        $this->assertSame('image/jpeg', $meta['type']);
    }

    /**
     * Subdirectory installs (XAMPP: APP_URL=http://localhost/Jambo)
     * store FileManager paths WITH the base segment. The proxy path
     * handed to Glide is relative to public/, so the segment must be
     * stripped — same guard as MediaImgTest.
     */
    public function test_strips_the_app_base_path_from_a_stored_absolute_path(): void
    {
        config(['app.url' => 'http://localhost/Jambo']);

        $meta = og_image_meta('/Jambo/' . $this->fixture);

        $this->assertStringContainsString('/img/' . $this->fixture, $meta['url']);
        $this->assertStringNotContainsString('/img/Jambo/', $meta['url']);
        $this->assertSame(2, $meta['width']);
    }

    /**
     * The FileManager picker stores some paths URL-encoded
     * ("Rio%20akram.png") while the file on disk has literal spaces.
     * The encoded form must still find the file and proxy it.
     */
    public function test_url_encoded_stored_path_still_finds_the_file(): void
    {
        config(['app.url' => 'https://jambofilms.com']);

        $spaced = 'og image meta test.png';
        $img = imagecreatetruecolor(2, 1);
        imagepng($img, public_path($spaced));
        imagedestroy($img);

        try {
            $meta = og_image_meta('/' . rawurlencode($spaced));

            $this->assertStringContainsString('/img/og%20image%20meta%20test.png', $meta['url']);
            $this->assertSame(2, $meta['width']);
        } finally {
            @unlink(public_path($spaced));
        }
    }

    public function test_empty_value_yields_empty_url(): void
    {
        $this->assertSame('', og_image_meta(null)['url']);
        $this->assertSame('', og_image_meta('  ')['url']);
    }
}
