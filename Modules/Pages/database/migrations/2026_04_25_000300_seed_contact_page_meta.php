<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pre-fills the Contact Us page's structured meta with the original
 * Streamit template defaults so the live page keeps its 4-card layout,
 * sidebar, and embedded map. Every value here is editable from the
 * admin Pages edit screen.
 *
 * The richtext `content` column is cleared for contact-us so admins
 * aren't editing the same info in two places — the structured meta
 * drives the UI, content is now an optional intro field above the
 * cards.
 */
return new class extends Migration {
    public function up(): void
    {
        $defaults = [
            'page_heading' => 'Get in touch anytime',
            'cards' => [
                [
                    'title' => 'Help & support',
                    'desc' => 'Need quick, reliable support? Our team is always ready to help you.',
                    'icon' => 'ph-headset',
                    'link_type' => 'email',
                    'link_value' => 'support@jambo.co',
                ],
                [
                    'title' => 'Call Us',
                    'desc' => 'Speak directly to one of our team members for assistance.',
                    'icon' => 'ph-phone-call',
                    'link_type' => 'phone',
                    'link_value' => '(145) 5847 9657',
                ],
                [
                    'title' => 'Advertising',
                    'desc' => 'Looking to advertise with us? Contact our advertising team.',
                    'icon' => 'ph-megaphone-simple',
                    'link_type' => 'email',
                    'link_value' => 'adds@jambo.co',
                ],
                [
                    'title' => 'Press Inquiries',
                    'desc' => 'For media inquiries or products our press team is here to help.',
                    'icon' => 'ph-newspaper-clipping',
                    'link_type' => 'email',
                    'link_value' => 'Inquiries@jambo.co',
                ],
            ],
            'form_heading' => 'Start the conversation',
            'form_subheading' => 'Fill out the contact form, and one of our team members will be in touch shortly',
            'form_recipient_email' => 'hello@jambo.co',
            'form_button_label' => 'Send Message',
            'visit_us_heading' => 'Visit Us',
            'visit_us_intro' => "If you'd like to visit or write to us:",
            'address_label' => 'Address',
            'address_lines' => "Jambo Headquarters\n123 Streaming Lane, Suite 100\nMedia City, CA 90210, USA",
            'business_heading' => 'Business Inquiries',
            'business_body' => 'For partnership opportunities, licensing, or media-related queries, please reach out to our business team.',
            'business_email' => 'business@jambo.co',
            'follow_label' => 'Follow Us',
            'facebook_url' => 'https://www.facebook.com/',
            'twitter_url' => 'https://twitter.com/',
            'youtube_url' => 'https://www.youtube.com/',
            'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3902543.2003194243!2d-118.04220880485131!3d36.56083290513502!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80be29b9f4abb783%3A0x4757dc6be1305318!2sInyo%20National%20Forest!5e0!3m2!1sen!2sin!4v1576668158879!5m2!1sen!2sin',
            'map_height' => 600,
        ];

        DB::table('pages')
            ->where('slug', 'contact-us')
            ->whereNull('meta')
            ->update([
                'meta' => json_encode($defaults),
                'content' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Non-destructive — leave admin-customised values alone.
    }
};
