<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * media_img() builds the /img/ proxy URL that ImageProxyController resizes
 * against public/. The path handed to Glide must be relative to public/,
 * never include the app's base segment.
 */
class MediaImgTest extends TestCase
{
    /**
     * The bug this guards: on a subdirectory install (XAMPP, APP_URL =
     * http://localhost/Jambo) the FileManager stores paths WITH the base
     * segment — "/Jambo/storage/gallery/x.png". Naively stripping only the
     * leading slash gives Glide "Jambo/storage/..." and the proxy URL becomes
     * ".../Jambo/img/Jambo/storage/...", which 404s because the file lives at
     * public/storage/..., not public/Jambo/storage/.... Every locally-picked
     * image broke this way.
     */
    public function test_strips_the_app_base_path_from_a_stored_absolute_path(): void
    {
        config(['app.url' => 'http://localhost/Jambo']);

        $url = media_img('/Jambo/storage/gallery/photo.png', 200);

        // The proxy segment must appear exactly once, immediately followed by
        // the public-relative path — no doubled base segment.
        $this->assertStringContainsString('/img/storage/gallery/photo.png', $url);
        $this->assertStringNotContainsString('/img/Jambo/', $url);
    }

    public function test_leaves_a_domain_root_absolute_path_untouched(): void
    {
        config(['app.url' => 'https://jambofilms.com']);

        $url = media_img('/storage/gallery/photo.png', 200);

        $this->assertStringContainsString('/img/storage/gallery/photo.png', $url);
        $this->assertStringNotContainsString('/img//', $url);
    }

    public function test_passes_external_urls_through_unchanged(): void
    {
        $external = 'https://cdn.example.com/photo.png';

        $this->assertSame($external, media_img($external, 200));
    }

    /**
     * A stored value that merely starts with the same letters as the base
     * segment (e.g. a real "/Jamboree/..." folder) must NOT be mangled — only a
     * whole leading path segment counts.
     */
    public function test_does_not_strip_a_partial_segment_match(): void
    {
        config(['app.url' => 'http://localhost/Jambo']);

        $url = media_img('/Jamboree/x.png', 200);

        $this->assertStringContainsString('/img/Jamboree/x.png', $url);
    }
}
