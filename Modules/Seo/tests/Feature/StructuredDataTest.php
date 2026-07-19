<?php

namespace Modules\Seo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Vj;
use Tests\TestCase;

/**
 * Structured data on the public content pages.
 *
 * The bug this guards against: we ranked for movie titles but Google
 * rendered the *site logo* as the result thumbnail. Cause — the pages
 * carried no JSON-LD at all, so Google fell back to guessing an image
 * from the DOM, and the first <img> tags on a detail page are the Jambo
 * logo, the IMDb badge and the Hulu badge. og:image did not help:
 * Google Search uses structured data for result thumbnails, not og:*.
 *
 * So the load-bearing assertions here are (a) a Movie/TVEpisode graph is
 * present at all, and (b) the poster is FIRST in its image array. Both
 * are silent, invisible-in-the-UI properties — exactly the kind that rot
 * without a test.
 */
class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pull every JSON-LD graph out of a rendered page, keyed by @type.
     *
     * @return array<string, array>
     */
    private function graphsFrom(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        $out = [];
        foreach ($m[1] as $json) {
            $decoded = json_decode($json, true);
            $this->assertSame(
                JSON_ERROR_NONE,
                json_last_error(),
                'Emitted a malformed JSON-LD block: ' . json_last_error_msg()
            );
            $out[$decoded['@type']] = $decoded;
        }

        return $out;
    }

    /**
     * tier_required is pinned to null on purpose: the factory picks it at
     * random from [null, null, 'basic', 'premium'], and a gated title
     * bounces a guest to login/pricing instead of rendering. Leaving it to
     * the faker makes any page-render assertion a coin flip.
     */
    private function publishedMovie(array $attrs = []): Movie
    {
        return Movie::factory()->create(array_merge([
            'status'        => Movie::STATUS_PUBLISHED,
            'published_at'  => now()->subDay(),
            'tier_required' => null,
            'poster_url'    => 'https://cdn.example.com/poster.jpg',
            'backdrop_url'  => 'https://cdn.example.com/backdrop.jpg',
        ], $attrs));
    }

    /**
     * @return array{0: Show, 1: Episode}
     */
    private function publishedEpisode(array $episodeAttrs = [], int $seasonNumber = 1): array
    {
        $show = Show::factory()->create([
            'status'        => Show::STATUS_PUBLISHED,
            'published_at'  => now()->subDay(),
            'tier_required' => null,
        ]);

        $season = Season::factory()->create([
            'show_id' => $show->id,
            'number'  => $seasonNumber,
        ]);

        $episode = Episode::factory()->create(array_merge([
            'season_id'     => $season->id,
            'published_at'  => now()->subDay(),
            'tier_required' => null, // see publishedMovie() — same coin-flip factory
        ], $episodeAttrs));

        return [$show, $episode];
    }

    public function test_movie_detail_page_emits_a_movie_graph(): void
    {
        $movie = $this->publishedMovie(['title' => 'Test Film', 'synopsis' => 'A synopsis.']);

        $graphs = $this->graphsFrom(
            $this->get(route('frontend.movie_detail', $movie->slug))->assertOk()->getContent()
        );

        $this->assertArrayHasKey('Movie', $graphs, 'Movie detail page emitted no Movie JSON-LD.');
        $this->assertSame('Test Film', $graphs['Movie']['name']);
        $this->assertSame(route('frontend.movie_detail', $movie->slug), $graphs['Movie']['url']);
    }

    /**
     * The whole point. Google reaches for the first entry in `image` when
     * it only needs one, so the poster must lead — not the backdrop, and
     * certainly not whatever <img> happens to sit highest in the DOM.
     */
    public function test_movie_graph_lists_the_poster_as_its_first_image(): void
    {
        $movie = $this->publishedMovie([
            'poster_url'   => 'https://cdn.example.com/the-poster.jpg',
            'backdrop_url' => 'https://cdn.example.com/the-backdrop.jpg',
        ]);

        $graphs = $this->graphsFrom(
            $this->get(route('frontend.movie_detail', $movie->slug))->getContent()
        );

        $this->assertSame(
            'https://cdn.example.com/the-poster.jpg',
            $graphs['Movie']['image'][0],
            'The poster must be the first image in the Movie graph — it is what Google shows in results.'
        );
    }

    /**
     * A Person node with a null name is invalid structured data. Person
     * stores first_name/last_name and has no `name` column, so reading
     * ->name (rather than ->full_name) silently emitted `"name": null`
     * for every actor. Regression guard.
     */
    public function test_cast_members_are_never_emitted_with_a_null_name(): void
    {
        $movie = $this->publishedMovie();
        $movie->cast()->attach(
            \Modules\Content\app\Models\Person::factory()->create([
                'first_name' => 'Jane',
                'last_name'  => 'Doe',
            ])->id,
            ['role' => 'actor']
        );

        $graphs = $this->graphsFrom(
            $this->get(route('frontend.movie_detail', $movie->slug))->getContent()
        );

        foreach ($graphs['Movie']['actor'] ?? [] as $actor) {
            $this->assertNotNull($actor['name'] ?? null, 'Emitted a Person node with a null name.');
            $this->assertNotSame('', trim($actor['name']));
        }
        $this->assertSame('Jane Doe', $graphs['Movie']['actor'][0]['name']);
    }

    /**
     * Inventing a rating — or shipping one with ratingCount 0 — is a
     * structured-data violation that risks a manual action. Silence is
     * the correct output for an unrated title.
     */
    public function test_no_aggregate_rating_is_emitted_for_an_unrated_movie(): void
    {
        $movie = $this->publishedMovie();

        $graphs = $this->graphsFrom(
            $this->get(route('frontend.movie_detail', $movie->slug))->getContent()
        );

        $this->assertArrayNotHasKey('aggregateRating', $graphs['Movie']);
    }

    /**
     * /watch/{slug} and /movie-detail/{slug} describe the same film with
     * the same title and synopsis, and both are crawlable. Without this
     * canonical they compete as duplicates and split the ranking signal.
     */
    public function test_watch_page_canonicalises_to_the_movie_detail_page(): void
    {
        $movie = $this->publishedMovie(['video_url' => 'https://cdn.example.com/v.mp4']);

        $this->get(route('frontend.watch', $movie->slug))
            ->assertSee(
                '<link rel="canonical" href="' . route('frontend.movie_detail', $movie->slug) . '">',
                false
            );
    }

    /**
     * Every page used to ship the same global meta description, because
     * the per-page @section only ever reached og:description. ~1,100 pages
     * of duplicate descriptions is a real ranking cost.
     */
    public function test_movie_detail_page_has_its_own_meta_description(): void
    {
        $movie = $this->publishedMovie(['synopsis' => 'A very specific plot summary.']);

        $html = $this->get(route('frontend.movie_detail', $movie->slug))->getContent();

        preg_match('#<meta name="description" content="([^"]*)"#', $html, $m);

        $this->assertStringContainsString('A very specific plot summary.', $m[1] ?? '');
        $this->assertNotSame(meta_description(), $m[1] ?? '');
    }

    public function test_episode_page_emits_a_tv_episode_graph_linked_to_its_series(): void
    {
        [$show, $episode] = $this->publishedEpisode([
            'number'    => 3,
            'still_url' => 'https://cdn.example.com/still.jpg',
        ]);

        $graphs = $this->graphsFrom(
            $this->get($episode->frontendUrl($show))->assertOk()->getContent()
        );

        $this->assertArrayHasKey('TVEpisode', $graphs);
        $this->assertSame(3, $graphs['TVEpisode']['episodeNumber']);
        $this->assertSame('https://cdn.example.com/still.jpg', $graphs['TVEpisode']['image'][0]);

        // The @id must match the one the series detail page declares, so
        // Google can stitch the episodes back onto the parent series.
        $this->assertSame(
            route('frontend.series_detail', $show->slug) . '#series',
            $graphs['TVEpisode']['partOfSeries']['@id']
        );
    }

    /**
     * A Blade comment containing a literal PHP-block directive silently
     * swallowed this page's variables and 500'd it (Blade extracts raw PHP
     * blocks with a non-greedy match *before* stripping comments). The page
     * rendering at all is the assertion that matters here.
     */
    public function test_episode_page_still_renders_its_body(): void
    {
        [$show, $episode] = $this->publishedEpisode([
            'number' => 5,
            'title'  => 'The Episode Title',
        ], seasonNumber: 2);

        $this->get($episode->frontendUrl($show))
            ->assertOk()
            ->assertSee('The Episode Title', false);
    }

    public function test_every_page_carries_the_site_organization_graph(): void
    {
        $graphs = $this->graphsFrom($this->get(route('frontend.ott'))->assertOk()->getContent());

        $this->assertArrayHasKey('Organization', $graphs);
        $this->assertArrayHasKey('WebSite', $graphs);
        $this->assertSame(app_name(), $graphs['Organization']['name']);
    }

    // -----------------------------------------------------------------
    // VJ pages — the site's highest-value queries ("vj junior", "vj
    // junior movies"). All three shipped <title>Jambo Films</title>, no
    // <h1> and no schema, so the keyword they rank for was stated
    // nowhere in the markup.
    // -----------------------------------------------------------------

    private function vjWithMovie(array $attrs = []): Vj
    {
        $vj = Vj::create(array_merge([
            'name' => 'Vj Junior', // as admins actually type it — see display_name
            'slug' => 'vj-junior',
        ], $attrs));

        $vj->movies()->attach($this->publishedMovie()->id);

        return $vj;
    }

    /**
     * The DB stores "Vj Junior"; the audience searches "VJ Junior". The CSS
     * capitalize on the cards fixed the look but not the markup, and a crawler
     * reads markup.
     */
    public function test_vj_name_is_normalised_to_uppercase_vj_for_title_and_heading(): void
    {
        $vj = $this->vjWithMovie();

        $html = $this->get(route('frontend.vj_detail', $vj->slug))->assertOk()->getContent();

        $this->assertStringContainsString('<title>VJ Junior', $html);
        $this->assertStringContainsString('>VJ Junior</h1>', $html);
    }

    /**
     * Spoken word order: "VJ Junior Movies", not "VJ Junior — Movies".
     */
    public function test_vj_movie_and_series_pages_use_spoken_keyword_order(): void
    {
        $vj = $this->vjWithMovie();

        $this->get(route('frontend.vj_movie_detail', $vj->slug))
            ->assertOk()
            ->assertSee('<title>VJ Junior Movies', false)
            ->assertSee('VJ Junior Movies</h1>', false);

        $this->get(route('frontend.vj_series_detail', $vj->slug))
            ->assertOk()
            ->assertSee('<title>VJ Junior Series', false)
            ->assertSee('VJ Junior Series</h1>', false);
    }

    /**
     * The genre pages exist to rank "vj junior action movies" — the keyword
     * must appear in <title> and <h1> in spoken word order while the URL
     * stays a plain slug pair, and the CollectionPage must hang off the same
     * Person @id as the hub so it reads as another shelf of the same entity.
     */
    public function test_vj_genre_page_puts_the_keyword_in_title_and_heading(): void
    {
        $vj = $this->vjWithMovie();
        $genre = Genre::create(['name' => 'Action', 'slug' => 'action']);
        $vj->movies->first()->genres()->attach($genre->id);

        $html = $this->get(route('frontend.vj_movie_genre', [$vj->slug, $genre->slug]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<title>VJ Junior Action Movies', $html);
        $this->assertStringContainsString('VJ Junior Action Movies</h1>', $html);

        $graphs = $this->graphsFrom($html);
        $this->assertSame(
            route('frontend.vj_detail', $vj->slug) . '#person',
            $graphs['CollectionPage']['about']['@id']
        );
    }

    /**
     * An empty VJ×genre combination must 404, not render an indexable blank
     * grid — the thin-page rule the sitemap applies with an even higher bar.
     */
    public function test_vj_genre_page_404s_when_the_combo_has_no_titles(): void
    {
        $vj = $this->vjWithMovie();
        $genre = Genre::create(['name' => 'Romance', 'slug' => 'romance']);

        $this->get(route('frontend.vj_movie_genre', [$vj->slug, $genre->slug]))
            ->assertNotFound();
    }

    /**
     * The Person node is the entity anchor every "vj junior ..." query hangs
     * off, and the collection points back at it by @id so the three VJ pages
     * read as one entity rather than three unrelated grids.
     */
    public function test_vj_hub_emits_a_person_entity_linked_to_its_collection(): void
    {
        $vj = $this->vjWithMovie();

        $graphs = $this->graphsFrom(
            $this->get(route('frontend.vj_detail', $vj->slug))->getContent()
        );

        $this->assertArrayHasKey('Person', $graphs);
        $this->assertSame('VJ Junior', $graphs['Person']['name']);

        $personId = route('frontend.vj_detail', $vj->slug) . '#person';
        $this->assertSame($personId, $graphs['Person']['@id']);
        $this->assertSame($personId, $graphs['CollectionPage']['about']['@id']);
    }

    /**
     * sameAs is the strongest entity signal available, but the social columns
     * land in a later migration. Until they exist the builder must simply omit
     * the key rather than emit an empty array or blow up.
     */
    public function test_vj_person_omits_same_as_until_social_columns_exist(): void
    {
        $vj = $this->vjWithMovie();

        $graphs = $this->graphsFrom(
            $this->get(route('frontend.vj_detail', $vj->slug))->getContent()
        );

        $this->assertArrayNotHasKey('sameAs', $graphs['Person']);
    }

    /**
     * 36 VJs sharing one templated bio is the thin-content problem we are
     * trying to avoid. An omitted description beats a duplicated one.
     */
    public function test_vj_person_omits_description_when_no_bio_is_written(): void
    {
        $vj = $this->vjWithMovie(['description' => null]);

        $graphs = $this->graphsFrom(
            $this->get(route('frontend.vj_detail', $vj->slug))->getContent()
        );

        $this->assertArrayNotHasKey('description', $graphs['Person']);
    }

    /**
     * The About card is the ONLY unique prose on a VJ page. Without it every VJ
     * page is a poster grid that reads as a clone of the other 35 — which is the
     * thin-content problem, and it gets worse once VJ x genre pages sit on top.
     */
    public function test_vj_hub_renders_the_about_card_with_bio_photo_and_socials(): void
    {
        $vj = $this->vjWithMovie([
            'description' => 'Luganda film narration by VJ Junior.',
            'photo_url'   => 'https://cdn.example.com/junior.jpg',
            'youtube_url' => 'https://youtube.com/@vjjunior',
        ]);

        $this->get(route('frontend.vj_detail', $vj->slug))
            ->assertOk()
            ->assertSee('vj-about__card', false)   // the card rendered
            ->assertSee('Narrated by')             // its eyebrow
            ->assertSee('Luganda film narration by VJ Junior.')
            ->assertSee('https://youtube.com/@vjjunior', false);
    }

    /**
     * An empty "About" shell on 36 pages is the same thin-content problem in
     * nicer packaging. Nothing to say -> no card.
     */
    public function test_vj_hub_omits_the_about_card_entirely_when_there_is_nothing_to_show(): void
    {
        $vj = $this->vjWithMovie(['description' => null]);

        $this->get(route('frontend.vj_detail', $vj->slug))
            ->assertOk()
            ->assertDontSee('vj-about-heading', false);
    }

    /**
     * The bio used to also print as a muted one-liner under the <h1>. Printing
     * the same prose twice on one page helps neither the reader nor Google.
     */
    public function test_vj_bio_appears_only_once_in_the_page_body(): void
    {
        $bio = 'Luganda film narration by VJ Junior.';
        $vj  = $this->vjWithMovie(['description' => $bio]);

        $html = $this->get(route('frontend.vj_detail', $vj->slug))->getContent();

        // Body only — the <head> legitimately repeats it across meta
        // description / og:description / twitter:description / JSON-LD.
        $body = preg_replace('#.*<body[^>]*>#is', '', $html);
        $body = preg_replace('#<script.*?</script>#s', '', $body);

        $this->assertSame(1, substr_count($body, e($bio)), 'The VJ bio is rendered more than once in the body.');
    }

    /**
     * Exactly one <h1> per page. This is invisible in the UI and had already
     * rotted in both directions: the home page emitted TEN (one per hero
     * slide) while every other page had none at all.
     */
    public function test_every_page_type_has_exactly_one_h1(): void
    {
        $vj      = $this->vjWithMovie();
        $movie   = $this->publishedMovie();
        [$show, $episode] = $this->publishedEpisode();

        $urls = [
            'home'         => route('frontend.ott'),
            'movies list'  => route('frontend.movie'),
            'series list'  => route('frontend.series'),
            'movie detail' => route('frontend.movie_detail', $movie->slug),
            'show detail'  => route('frontend.series_detail', $show->slug),
            'episode'      => $episode->frontendUrl($show),
            'vj hub'       => route('frontend.vj_detail', $vj->slug),
            'vj movies'    => route('frontend.vj_movie_detail', $vj->slug),
            'vj series'    => route('frontend.vj_series_detail', $vj->slug),
        ];

        foreach ($urls as $label => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertSame(
                1,
                preg_match_all('#<h1[^>]*>#', $html),
                "The {$label} page must have exactly one <h1>."
            );
        }
    }

    public function test_sitemap_is_well_formed_and_advertises_poster_images(): void
    {
        $movie = $this->publishedMovie(['poster_url' => 'https://cdn.example.com/sitemap-poster.jpg']);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertNotFalse(simplexml_load_string($xml), 'Sitemap is not well-formed XML.');
        $this->assertStringContainsString('xmlns:image=', $xml);
        $this->assertStringContainsString('<image:loc>https://cdn.example.com/sitemap-poster.jpg</image:loc>', $xml);
        $this->assertStringContainsString(route('frontend.movie_detail', $movie->slug), $xml);
    }
}
