<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Tag;
use Modules\Frontend\app\Services\RailArchiveCatalog;
use Modules\Pages\app\Models\Page;
use Tests\TestCase;

/**
 * Gaps 6 and 7 of docs/api/coverage.md: the rest of the catalogue, and the
 * CMS pages.
 *
 * Static pages are not decoration — Google Play requires a subscription app to
 * show its terms and privacy policy, and an app that hard-codes them drifts
 * from the site the moment an admin edits one.
 */
class CatalogueExtrasTest extends TestCase
{
    use RefreshDatabase;

    // ── search suggestions ───────────────────────────────────────────

    public function test_suggestions_need_two_characters(): void
    {
        $this->movie('Queen of Katwe');

        $this->getJson('/api/v1/search/suggest?q=Q')
            ->assertOk()
            ->assertJsonCount(0, 'data.suggestions');
    }

    public function test_suggestions_cover_movies_and_series(): void
    {
        $this->movie('Queen of Katwe');
        $this->series('Queen Sono');

        $suggestions = collect(
            $this->getJson('/api/v1/search/suggest?q=Queen')->assertOk()->json('data.suggestions')
        );

        $this->assertCount(2, $suggestions);
        $this->assertEqualsCanonicalizing(['movie', 'series'], $suggestions->pluck('type')->all());
    }

    /**
     * The website ranks an exact title above one that merely starts with the
     * term, above one that contains it, above a synopsis-only match. The app
     * must not order them differently.
     */
    public function test_search_ranks_a_title_match_above_a_synopsis_match(): void
    {
        $this->movie('Something Else', synopsis: 'A film about a Queen and her court.');
        $this->movie('Queen');

        $titles = collect($this->getJson('/api/v1/search?q=Queen')->assertOk()->json('data.movies'))
            ->pluck('title');

        $this->assertSame('Queen', $titles->first(), 'An exact title match must rank first.');
        $this->assertContains('Something Else', $titles->all(), 'A synopsis match must still be found.');
    }

    // ── tags ─────────────────────────────────────────────────────────

    public function test_tags_list_and_detail(): void
    {
        $movie = $this->movie('Tagged Film');
        $tag = Tag::create(['name' => 'Kampala', 'slug' => 'kampala']);
        $movie->tags()->attach($tag->id);

        $this->getJson('/api/v1/tags')
            ->assertOk()
            ->assertJsonPath('data.tags.0.slug', 'kampala')
            ->assertJsonPath('data.tags.0.movies_count', 1);

        $this->getJson('/api/v1/tags/kampala')
            ->assertOk()
            ->assertJsonPath('data.tag.name', 'Kampala')
            ->assertJsonCount(1, 'data.movies');
    }

    public function test_an_unknown_tag_is_a_clean_not_found(): void
    {
        $this->getJson('/api/v1/tags/no-such-tag')
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    // ── cast index ───────────────────────────────────────────────────

    public function test_the_cast_index_lists_only_people_with_published_work(): void
    {
        $movie = $this->movie('Cast Film');
        $working = Person::create(['first_name' => 'Ada', 'last_name' => 'Actor', 'slug' => 'ada-actor']);
        $movie->cast()->attach($working->id, ['role' => 'actor']);

        Person::create(['first_name' => 'No', 'last_name' => 'Work', 'slug' => 'no-work']);

        $slugs = collect($this->getJson('/api/v1/cast')->assertOk()->json('data.items'))->pluck('slug');

        $this->assertContains('ada-actor', $slugs->all());
        $this->assertNotContains('no-work', $slugs->all());
    }

    // ── rail archives ────────────────────────────────────────────────

    /**
     * 🔴 The field is `image_url`, which is what this endpoint's own schema
     * has always said.
     *
     * It sent `photo_url` until 2026-09-10 while declaring `PersonCard`, and
     * nothing caught it because nothing had ever called the endpoint. The
     * first client would have read `image_url`, got undefined, and rendered
     * every personality as a grey box — a screen that looks built and shows
     * nothing.
     *
     * Both halves are asserted: the right key present AND the wrong key gone.
     * Asserting only the first would pass on a payload carrying both, which is
     * exactly what a careless "fix" produces.
     */
    public function test_the_cast_index_names_the_image_field_as_its_contract_does(): void
    {
        $movie = $this->movie('Cast Film');
        $person = Person::create([
            'first_name' => 'Ada',
            'last_name' => 'Actor',
            'slug' => 'ada-actor',
            'photo_url' => 'gallery/ada.jpg',
            'known_for' => 'director',
        ]);
        $movie->cast()->attach($person->id, ['role' => 'actor']);

        $item = collect($this->getJson('/api/v1/cast')->assertOk()->json('data.items'))
            ->firstWhere('slug', 'ada-actor');

        $this->assertNotNull($item, 'the fixture person is missing from the index');
        $this->assertArrayHasKey('image_url', $item);
        $this->assertArrayNotHasKey('photo_url', $item);
        $this->assertStringContainsString('gallery/ada.jpg', (string) $item['image_url']);
    }

    /** The role line the site prints under each name, and null when unset. */
    public function test_the_cast_index_carries_known_for_and_leaves_it_null_when_unset(): void
    {
        $movie = $this->movie('Cast Film');

        $named = Person::create([
            'first_name' => 'Ada', 'last_name' => 'Actor', 'slug' => 'ada-actor',
            'known_for' => 'producer,director',
        ]);
        $blank = Person::create([
            'first_name' => 'Bee', 'last_name' => 'Blank', 'slug' => 'bee-blank',
        ]);
        $movie->cast()->attach([$named->id => ['role' => 'actor'], $blank->id => ['role' => 'actor']]);

        $items = collect($this->getJson('/api/v1/cast')->assertOk()->json('data.items'));

        $this->assertSame('producer,director', $items->firstWhere('slug', 'ada-actor')['known_for']);
        // Null, not '' — an empty string renders as a caption with no text.
        $this->assertNull($items->firstWhere('slug', 'bee-blank')['known_for']);
    }

    // ── rail archives ─────────────────────────────────────────────────

    public function test_the_collections_index_lists_every_rail(): void
    {
        $keys = collect($this->getJson('/api/v1/collections')->assertOk()->json('data.collections'))
            ->pluck('key');

        foreach (app(RailArchiveCatalog::class)->keys() as $expected) {
            $this->assertContains($expected, $keys->all());
        }
    }

    /**
     * Only the portable rails are exercised: the personalised three order with
     * MySQL's FIELD(), which SQLite has no equivalent for. Production is
     * MariaDB, so this is a suite limitation rather than a live defect, and it
     * is stated here so nobody reads the gap as an oversight.
     */
    public function test_a_portable_rail_archive_returns_its_titles(): void
    {
        $this->movie('Archive Movie One');
        $this->movie('Archive Movie Two');

        $response = $this->getJson('/api/v1/collections/latest-movies?per_page=1')->assertOk();

        // Offset-paged since the review pass: a cursor on created_at dropped
        // every tied title, and page 2 came back empty on real data.
        $response->assertJsonPath('data.key', 'latest-movies')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.next_page', 2);
    }

    public function test_the_exclusives_archive_only_carries_gated_titles(): void
    {
        $this->movie('Free One');
        $this->movie('Gated One', tier: 'premium');

        $titles = collect($this->getJson('/api/v1/collections/only-on-streamit')->assertOk()->json('data.items'))
            ->pluck('title');

        $this->assertContains('Gated One', $titles->all());
        $this->assertNotContains('Free One', $titles->all());
    }

    public function test_an_unknown_collection_is_a_clean_not_found(): void
    {
        $this->getJson('/api/v1/collections/not-a-rail')
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_a_collection_never_leaks_a_video_url(): void
    {
        $this->movie('Archive Movie');

        $body = $this->getJson('/api/v1/collections/latest-movies')->assertOk()->content();

        $this->assertStringNotContainsString('video_url', $body);
    }

    // ── CMS pages ────────────────────────────────────────────────────

    public function test_pages_list_excludes_structural_rows(): void
    {
        // updateOrCreate: the Pages module seeds about-us, footer and the
        // rest as part of its migrations, so these slugs already exist.
        Page::updateOrCreate(['slug' => 'about-us'], ['title' => 'About Us', 'content' => '<p>Hello</p>', 'status' => 'published', 'is_system' => false]);
        Page::updateOrCreate(['slug' => 'footer'], ['title' => 'Footer', 'content' => '<p>Chrome</p>', 'status' => 'published', 'is_system' => true]);

        $slugs = collect($this->getJson('/api/v1/pages')->assertOk()->json('data.pages'))->pluck('slug');

        $this->assertContains('about-us', $slugs->all());
        $this->assertNotContains('footer', $slugs->all(), 'A system row is chrome the website assembles, not a screen.');
    }

    public function test_a_page_returns_its_content(): void
    {
        Page::updateOrCreate(['slug' => 'privacy-policy'], [
            'title' => 'Privacy Policy',
            'content' => '<p>What we collect.</p>',
            'status' => 'published',
            'is_system' => false,
        ]);

        $this->getJson('/api/v1/pages/privacy-policy')
            ->assertOk()
            ->assertJsonPath('data.page.title', 'Privacy Policy')
            ->assertJsonPath('data.page.content', '<p>What we collect.</p>');
    }

    public function test_an_unpublished_page_is_not_reachable(): void
    {
        Page::updateOrCreate(['slug' => 'draft-page'], ['title' => 'Draft', 'content' => 'x', 'status' => 'draft', 'is_system' => false]);

        $this->getJson('/api/v1/pages/draft-page')->assertStatus(404);
    }

    // ── fixtures ─────────────────────────────────────────────────────

    private function movie(string $title, ?string $tier = null, ?string $synopsis = null): Movie
    {
        return Movie::factory()->create([
            'title' => $title,
            'synopsis' => $synopsis,
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'tier_required' => $tier,
            'video_url' => 'https://cdn.example.test/movie.mp4',
        ]);
    }

    private function series(string $title): Show
    {
        $show = Show::create([
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . uniqid(),
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'S1']);
        Episode::create([
            'season_id' => $season->id,
            'number' => 1,
            'title' => 'Pilot',
            'published_at' => now()->subDay(),
            'video_url' => 'https://cdn.example.test/ep.mp4',
        ]);

        return $show;
    }
}
