<?php

namespace Modules\Pages\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Pages\app\Models\Page;

/**
 * Seeds the five system pages so they always appear in the admin list,
 * ready to be filled in. They start published with empty content — the
 * frontend shows the legacy template view until an admin saves content,
 * then their content takes over instantly. No extra publish step.
 */
class PagesDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $systemPages = [
            [
                'slug' => 'about-us',
                'title' => 'About Us',
                'meta_description' => 'Learn more about Jambo — who we are, what we do, and why we built this platform.',
            ],
            [
                'slug' => 'contact-us',
                'title' => 'Contact Us',
                'meta_description' => 'Get in touch with the Jambo team.',
            ],
            [
                'slug' => 'faqs',
                'title' => 'FAQs',
                'meta_description' => 'Answers to the most common questions about Jambo.',
            ],
            [
                'slug' => 'terms-of-use',
                'title' => 'Terms of Use',
                'meta_description' => 'The terms and conditions that govern your use of Jambo.',
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'meta_description' => 'How Jambo collects, uses, and protects your personal information.',
            ],
        ];

        foreach ($systemPages as $row) {
            Page::firstOrCreate(
                ['slug' => $row['slug']],
                [
                    'title' => $row['title'],
                    'content' => null,
                    'meta_description' => $row['meta_description'],
                    'status' => 'published',
                    'is_system' => true,
                ],
            );
        }
    }
}
