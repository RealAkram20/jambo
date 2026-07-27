<?php

namespace Modules\Frontend\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Tests\TestCase;

/**
 * The taxonomy archives lead with a newest-first poster grid of
 * everything in the term, with All | Movies | Series tabs
 * (?kind=movies|series). The tab narrows both the grid AND the per-VJ
 * blocks server-side, so /categories/x?kind=series must not leak any
 * movie titles anywhere on the page.
 */
class TaxonomyArchiveTabsTest extends TestCase
{
    use RefreshDatabase;

    private function categoryWithBoth(): array
    {
        $category = Category::create(['name' => 'Kina Uganda', 'slug' => 'kina-uganda']);

        $movie = Movie::factory()->create([
            'title'        => 'Archive Movie Alpha',
            'status'       => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
        // Show::published() requires at least one released episode —
        // a series with nothing watchable never surfaces on rails.
        $show = Show::factory()->create([
            'title'        => 'Archive Show Beta',
            'status'       => Show::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
        $season = \Modules\Content\app\Models\Season::factory()->create(['show_id' => $show->id, 'number' => 1]);
        \Modules\Content\app\Models\Episode::factory()->create([
            'season_id'    => $season->id,
            'published_at' => now()->subDay(),
        ]);

        $category->movies()->attach($movie);
        $category->shows()->attach($show);

        return [$category, $movie, $show];
    }

    public function test_archive_shows_both_kinds_with_tabs(): void
    {
        [$category] = $this->categoryWithBoth();

        $this->get('/categories/' . $category->slug)
            ->assertOk()
            ->assertSee('Archive Movie Alpha')
            ->assertSee('Archive Show Beta')
            ->assertSee('archive-grid')
            ->assertSee('kind=movies')
            ->assertSee('kind=series');
    }

    public function test_movies_tab_hides_series(): void
    {
        [$category] = $this->categoryWithBoth();

        $this->get('/categories/' . $category->slug . '?kind=movies')
            ->assertOk()
            ->assertSee('Archive Movie Alpha')
            ->assertDontSee('Archive Show Beta');
    }

    public function test_series_tab_hides_movies(): void
    {
        [$category] = $this->categoryWithBoth();

        $this->get('/categories/' . $category->slug . '?kind=series')
            ->assertOk()
            ->assertSee('Archive Show Beta')
            ->assertDontSee('Archive Movie Alpha');
    }

    public function test_all_view_has_kind_rows_but_no_vj_grouping(): void
    {
        [$category, $movie] = $this->categoryWithBoth();
        $vj = \Modules\Content\app\Models\Vj::create(['name' => 'Vj Junior', 'slug' => 'vj-junior']);
        $movie->vjs()->attach($vj);

        $this->get('/categories/' . $category->slug)
            ->assertOk()
            ->assertDontSee('by VJ') // no per-VJ grouping on All
            ->assertDontSee('Vj Junior');
    }

    public function test_kind_view_groups_by_vj_with_term_scoped_view_all(): void
    {
        [$category, $movie] = $this->categoryWithBoth();
        $vj = \Modules\Content\app\Models\Vj::create(['name' => 'Vj Junior', 'slug' => 'vj-junior']);
        $movie->vjs()->attach($vj);

        $this->get('/categories/' . $category->slug . '?kind=movies')
            ->assertOk()
            ->assertSee('Vj Junior')
            ->assertSee('kind=movies&amp;vj=vj-junior', false)
            // Kind views browse BY VJ — the flat grid only exists on
            // the All view and the single-VJ listing.
            ->assertDontSee('archive-grid');
    }

    public function test_vj_view_lists_only_that_vjs_titles_in_the_term(): void
    {
        [$category, $movie] = $this->categoryWithBoth();
        $vj = \Modules\Content\app\Models\Vj::create(['name' => 'Vj Junior', 'slug' => 'vj-junior']);
        $movie->vjs()->attach($vj);

        $other = Movie::factory()->create([
            'title'        => 'Archive Movie Gamma',
            'status'       => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
        $category->movies()->attach($other); // in category, different/no VJ

        $this->get('/categories/' . $category->slug . '?kind=movies&vj=vj-junior')
            ->assertOk()
            ->assertSee('Archive Movie Alpha')
            ->assertDontSee('Archive Movie Gamma')
            ->assertDontSee('by VJ'); // focused listing, no VJ grouping
    }

    public function test_genre_and_tag_archives_share_the_layout(): void
    {
        [, $movie] = $this->categoryWithBoth();

        $genre = \Modules\Content\app\Models\Genre::create(['name' => 'Action', 'slug' => 'action']);
        $genre->movies()->attach($movie);

        $this->get('/geners/' . $genre->slug)
            ->assertOk()
            ->assertSee('Archive Movie Alpha')
            ->assertSee('kind=series');
    }
}
