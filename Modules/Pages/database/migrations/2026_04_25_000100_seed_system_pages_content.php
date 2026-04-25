<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-shot data migration that pre-fills the five system pages with the
 * legacy template's content as Quill-compatible HTML, so admins can
 * edit every paragraph from /admin/pages instead of touching blade
 * files. Only seeds rows whose content is still empty — never clobbers
 * an admin's edits.
 */
return new class extends Migration {
    public function up(): void
    {
        $now = now();

        $rows = [
            'about-us' => $this->aboutUs(),
            'contact-us' => $this->contactUs(),
            'faqs' => $this->faqs(),
            'privacy-policy' => $this->privacyPolicy(),
            'terms-of-use' => $this->termsOfUse(),
        ];

        foreach ($rows as $slug => $html) {
            DB::table('pages')
                ->where('slug', $slug)
                ->where(function ($q) {
                    $q->whereNull('content')->orWhere('content', '');
                })
                ->update([
                    'content' => $html,
                    'status' => 'published',
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        // Non-destructive: leaving content alone on rollback so we don't
        // wipe admin edits if the migration is re-run.
    }

    private function aboutUs(): string
    {
        return <<<'HTML'
<h2>About Jambo OTT Platform</h2>
<p>Welcome to Jambo, a next-generation streaming platform proudly developed by Jambo Design. We specialize in creating cutting-edge digital solutions, and Jambo is our latest breakthrough in the world of online entertainment. Whether you're a movie lover, a series binge-watcher, or enjoy live events, our platform is designed to deliver high-quality content directly to your device, ensuring a seamless, uninterrupted experience. Jambo combines advanced technology with a user-friendly interface to cater to audiences worldwide.</p>
<p>At Jambo Design, we aim to revolutionize digital content consumption with Jambo, a fast, reliable, and personalized streaming platform. Built with cutting-edge technology, it offers superior streaming quality, tailored recommendations, and an intuitive content management system. Our mission is to make entertainment seamless and accessible, anytime, anywhere.</p>

<h2>Why Choose Jambo</h2>
<p>Experience next-level entertainment with Jambo, the trusted streaming platform that delivers seamless content, unparalleled convenience, and high-quality entertainment. Whether you're watching, Jambo ensures a premium experience every time.</p>
<h3>10,000+ Movies and Series Across All Genres</h3>
<p>Dive into a diverse library of top-rated content, from blockbuster hits to exclusive originals.</p>
<h3>AI-Powered Recommendations</h3>
<p>Enjoy personalized suggestions tailored to your viewing preferences.</p>
<h3>99.9% Uptime and Buffer-Free Streaming</h3>
<p>Built for speed and reliability, Jambo ensures uninterrupted entertainment.</p>
<h3>Secure Payment &amp; Hassle-Free Subscriptions</h3>
<p>Get started in seconds with 100% secure transactions.</p>
HTML;
    }

    private function contactUs(): string
    {
        return <<<'HTML'
<h2>Get in touch anytime</h2>

<h3>Help &amp; Support</h3>
<p>Need quick, reliable support? Our team is always ready to help you.</p>
<p><a href="mailto:support@jambo.co">support@jambo.co</a></p>

<h3>Call Us</h3>
<p>Speak directly to one of our team members for assistance.</p>
<p>(145) 5847 9657</p>

<h3>Advertising</h3>
<p>Looking to advertise with us? Contact our advertising team.</p>
<p><a href="mailto:adds@jambo.co">adds@jambo.co</a></p>

<h3>Press Inquiries</h3>
<p>For media inquiries or products our press team is here to help.</p>
<p><a href="mailto:Inquiries@jambo.co">Inquiries@jambo.co</a></p>

<h2>Visit Us</h2>
<p>If you'd like to visit or write to us:</p>
<p><strong>Addresses</strong><br>
Jambo Headquarters<br>
123 Streaming Lane, Suite 100<br>
Media City, CA 90210, USA</p>

<h2>Business Inquiries</h2>
<p>For partnership opportunities, licensing, or media-related queries, please reach out to our business team.</p>
<p><a href="mailto:business@jambo.co">business@jambo.co</a></p>
HTML;
    }

    private function faqs(): string
    {
        $body = 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using &lsquo;Content here, content here&rsquo;, making it look like readable English.';

        $questions = [
            'What Is Jambo?',
            'Will my account work outside my country?',
            'I am facing video playback issues. What do I do?',
            'How can I manage notifications?',
            'What benefits do I get with the packs?',
        ];

        $html = '';
        foreach ($questions as $q) {
            $html .= "<h3>{$q}</h3>\n<p>{$body}</p>\n\n";
        }

        return rtrim($html);
    }

    private function privacyPolicy(): string
    {
        return <<<'HTML'
<h3>1. What Personal Information About Users Does Jambo Collect?</h3>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In quis nisl dignissim, placerat diam ac, egestas ante. Morbi varius quis orci feugiat hendrerit. Morbi ullamcorper consequat justo, in posuere nisi efficitur sed. Vestibulum semper dolor id arcu finibus volutpat. Integer condimentum ex tellus, ac finibus metus sodales in. Proin blandit congue ipsum ac dapibus. Integer blandit eros elit, vel luctus tellus finibus in. Aliquam non urna ut leo vestibulum mattis ac nec dolor. Nulla libero mauris, dapibus non aliquet viverra, elementum eget lorem.</p>

<h3>2. Cookies and Web Beacons</h3>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In quis nisl dignissim, placerat diam ac, egestas ante. Morbi varius quis orci feugiat hendrerit. Morbi ullamcorper consequat justo, in posuere nisi efficitur sed.</p>
<p>Vestibulum semper dolor id arcu finibus volutpat. Integer condimentum ex tellus, ac finibus metus sodales in. Proin blandit congue ipsum ac dapibus. Integer blandit eros elit, vel luctus tellus finibus in. Aliquam non urna ut leo vestibulum mattis ac nec dolor. Nulla libero mauris, dapibus non aliquet viverra, elementum eget lorem.</p>

<h3>3. Third Party Payment Gateway &ndash; Financial Information</h3>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In quis nisl dignissim, placerat diam ac, egestas ante. Morbi varius quis orci feugiat hendrerit.</p>

<h3>4. Disclosure Children&rsquo;s Privacy</h3>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In quis nisl dignissim, placerat diam ac, egestas ante. Morbi varius quis orci feugiat hendrerit.</p>

<h3>5. Data transfer, storage &amp; processing globally</h3>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In quis nisl dignissim, placerat diam ac, egestas ante. Morbi varius quis orci feugiat hendrerit.</p>
HTML;
    }

    private function termsOfUse(): string
    {
        return <<<'HTML'
<h3>1. Description of Service and Acceptance of Terms of Use</h3>
<p>The Jambo website provides streaming services for various entertainment content including movies, series, and documentaries.</p>
<p>By accessing or using the Jambo website, you agree to abide by the Terms of Use outlined herein.</p>
<p>Jambo requires users to have the latest version of compatible browsers such as Google Chrome, Firefox, or Safari, with JavaScript and cookies enabled for optimal performance.</p>

<h3>The Jambo website works best with:</h3>
<ul>
<li>Latest version of Google Chrome</li>
<li>Latest version of Firefox</li>
<li>Latest version of Safari</li>
<li>JavaScript and cookies enabled</li>
</ul>

<h3>2. Subscription Services</h3>
<p>Jambo offers subscription-based services to access exclusive content and features.</p>
<p>Subscribers gain access to a vast library of high-quality video content, ad-free streaming, and personalized recommendations.</p>
<p>Subscription plans are available on a monthly or yearly basis, with flexible pricing options to suit different user preferences.</p>

<h3>3. Third Party Payment Gateway &ndash; Financial Information</h3>
<p>To facilitate payments for subscription services, Jambo utilizes a third-party payment gateway.</p>
<p>Users are required to provide financial information such as credit card details or PayPal accounts for processing subscription payments securely.</p>
<p>Jambo ensures that all financial transactions are encrypted and comply with industry-standard security protocols to protect user data.</p>

<h3>4. Disclosure Children&rsquo;s Privacy</h3>
<p>Jambo is committed to protecting the privacy of children who use its services.</p>
<p>Users under the age of 13 are required to obtain parental consent before accessing certain features or providing personal information.</p>
<p>Jambo does not knowingly collect or solicit personal information from children under 13 without parental consent.</p>

<h3>5. Data transfer, storage &amp; processing globally</h3>
<p>By using Jambo&rsquo;s services, users acknowledge and agree that their personal data may be transferred, stored, and processed globally.</p>
<p>Data transfers may occur between servers located in different countries to provide seamless streaming experiences and improve service quality.</p>
<p>Jambo adheres to data protection regulations and implements appropriate safeguards to ensure the security and privacy of user data during global transfers and processing.</p>
HTML;
    }
};
