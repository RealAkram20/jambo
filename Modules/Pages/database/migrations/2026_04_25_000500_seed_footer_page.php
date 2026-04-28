<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the "footer" system page so admins can edit the public footer
 * (link columns, contact info, socials, app store links, copyright)
 * from /admin/pages just like the other system pages.
 *
 * The footer is rendered by every public page via
 * Modules/Frontend/resources/views/components/partials/footer-default.blade.php
 * — there's no /footer URL.
 */
return new class extends Migration {
    public function up(): void
    {
        $defaults = [
            'contact' => [
                'email_label' => 'Email Us',
                'email_address' => 'customer@jambo.co',
                'helpline_label' => 'Helpline Number',
                'helpline_phone' => '+(480) 555-0103',
            ],
            'columns' => [
                [
                    'title' => 'Movies to Watch',
                    'links' => [
                        ['label' => 'Top Trending', 'url' => '/view-all'],
                        ['label' => 'Recommended', 'url' => '/view-all'],
                        ['label' => 'Popular', 'url' => '/view-all'],
                    ],
                ],
                [
                    'title' => 'Quick Links',
                    'links' => [
                        ['label' => 'Contact Us', 'url' => '/contact-us'],
                        ['label' => 'Pricing Plan', 'url' => '/pricing'],
                        ['label' => 'FAQ', 'url' => '/faq_page'],
                    ],
                ],
                [
                    'title' => 'About Company',
                    'links' => [
                        ['label' => 'About Us', 'url' => '/about-us'],
                        ['label' => 'Terms and Use', 'url' => '/terms-and-policy'],
                        ['label' => 'Privacy Policy', 'url' => '/privacy-policy'],
                    ],
                ],
            ],
            'newsletter' => [
                'title' => 'Newsletter',
                'placeholder' => 'Email',
                'button_label' => 'Subscribe',
                'enabled' => true,
            ],
            'follow_label' => 'Follow Us',
            'socials' => [
                ['icon' => 'icon icon-facebook-share', 'url' => 'https://www.facebook.com/'],
                ['icon' => 'ph ph-x-logo', 'url' => 'https://twitter.com/'],
                ['icon' => 'ph ph-instagram-logo', 'url' => 'https://www.instagram.com/'],
                ['icon' => 'ph ph-tiktok-logo', 'url' => 'https://www.tiktok.com/'],
            ],
            'copyright' => '&copy; ' . date('Y') . ' <span class="text-primary">JAMBO.</span> All rights reserved.',
            'download_app_title' => 'Download App',
            'play_store_url' => '',
            'app_store_url' => '',
        ];

        // Idempotent — only insert if the footer row doesn't already
        // exist, so re-running the migration set never duplicates.
        $exists = DB::table('pages')->where('slug', 'footer')->exists();
        if ($exists) {
            return;
        }

        DB::table('pages')->insert([
            'slug' => 'footer',
            'title' => 'Footer',
            'content' => null,
            'meta' => json_encode($defaults),
            'featured_image_url' => null,
            'meta_description' => null,
            'status' => 'published',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('pages')->where('slug', 'footer')->delete();
    }
};
