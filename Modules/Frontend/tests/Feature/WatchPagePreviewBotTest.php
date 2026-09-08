<?php

namespace Modules\Frontend\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Tests\TestCase;

/**
 * Shared /watch links must never preview as the login page.
 *
 * The bug this guards against: users share the URL they're on while
 * watching (/watch/{slug}), and when playback is auth-gated — premium
 * tier, or the site-wide require_signup_to_watch switch — the guest
 * branch 302'd EVERY unauthenticated client to /login. Facebook's and
 * WhatsApp's scrapers are unauthenticated clients, so every shared
 * watch link previewed as "Login" (or nothing). Scrapers now bounce to
 * the content's public detail page, which carries the full OG tag set.
 * Human guests keep the login redirect (with intended() bounce-back).
 */
class WatchPagePreviewBotTest extends TestCase
{
    use RefreshDatabase;

    private const FB_UA = ['User-Agent' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'];

    /**
     * The "premium" tier these fixtures reference must actually exist.
     *
     * It did not, and the tests still passed, because the old
     * FrontendController::userCanWatch() refused guests one branch BEFORE it
     * looked the tier slug up - so an unresolvable slug read as "gated" on
     * this page while TierGate read the same slug as free and streamed it.
     * PlaybackAuthorizer settled that split in TierGate's favour, which is
     * what makes seeding this row necessary: with the tier present these
     * tests now exercise real premium gating instead of a missing row.
     */
    protected function setUp(): void
    {
        parent::setUp();

        SubscriptionTier::create([
            'name' => 'Premium',
            'slug' => 'premium',
            'price' => 30000,
            'currency' => 'UGX',
            'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
            'access_level' => SubscriptionTier::ACCESS_PREMIUM,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    private function premiumMovie(): Movie
    {
        return Movie::factory()->create([
            'status'        => Movie::STATUS_PUBLISHED,
            'published_at'  => now()->subDay(),
            'tier_required' => 'premium',
        ]);
    }

    public function test_scraper_on_gated_watch_page_is_sent_to_movie_detail(): void
    {
        $movie = $this->premiumMovie();

        $this->get('/watch/' . $movie->slug, self::FB_UA)
            ->assertRedirect(route('frontend.movie_detail', $movie->slug));
    }

    public function test_human_guest_on_gated_watch_page_still_goes_to_login(): void
    {
        $movie = $this->premiumMovie();

        $this->get('/watch/' . $movie->slug)
            ->assertRedirect(route('login'));
    }

    public function test_scraper_is_bounced_even_when_signup_switch_gates_free_content(): void
    {
        setting(['require_signup_to_watch', '1']);

        $movie = Movie::factory()->create([
            'status'        => Movie::STATUS_PUBLISHED,
            'published_at'  => now()->subDay(),
            'tier_required' => null,
        ]);

        $this->get('/watch/' . $movie->slug, self::FB_UA)
            ->assertRedirect(route('frontend.movie_detail', $movie->slug));
    }

    public function test_whatsapp_scraper_on_gated_episode_is_sent_to_series_detail(): void
    {
        $show = Show::factory()->create([
            'status'        => Show::STATUS_PUBLISHED,
            'published_at'  => now()->subDay(),
            'tier_required' => 'premium',
        ]);
        $season = Season::factory()->create(['show_id' => $show->id, 'number' => 1]);
        $episode = Episode::factory()->create([
            'season_id'     => $season->id,
            'number'        => 1,
            'published_at'  => now()->subDay(),
            'tier_required' => null,
        ]);

        $this->get("/episode/{$show->slug}/s1/ep1", ['User-Agent' => 'WhatsApp/2.23.20.0'])
            ->assertRedirect(route('frontend.series_detail', $show->slug));
    }
}
