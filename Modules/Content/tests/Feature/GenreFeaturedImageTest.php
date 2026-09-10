<?php

namespace Modules\Content\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Tests\TestCase;

/**
 * The genre tile's artwork.
 *
 * A genre owns no image, so both surfaces borrow a still from its most recent
 * published title. `Genre::attachFeaturedImages` answers that for a whole
 * collection in two queries where the accessor walks up to four per genre —
 * on the home page, on every request, for a decoration.
 *
 * The batched version has to agree with the accessor exactly, because the
 * website's blades still call the accessor and the two would otherwise show
 * different pictures for the same genre on the same page.
 */
class GenreFeaturedImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prefers_a_movie_backdrop_over_everything_else(): void
    {
        $genre = $this->genre('mixed');

        $this->movie($genre, backdrop: 'movies/new-backdrop.jpg', poster: 'movies/new-poster.jpg');
        $this->series($genre, backdrop: 'shows/backdrop.jpg', poster: 'shows/poster.jpg');

        $this->assertSame('movies/new-backdrop.jpg', $this->batched($genre));
    }

    public function test_it_falls_back_to_a_movie_poster_before_any_series(): void
    {
        $genre = $this->genre('no-movie-backdrop');

        $this->movie($genre, backdrop: null, poster: 'movies/poster.jpg');
        $this->series($genre, backdrop: 'shows/backdrop.jpg', poster: 'shows/poster.jpg');

        $this->assertSame('movies/poster.jpg', $this->batched($genre));
    }

    public function test_it_falls_back_to_a_series_when_the_genre_has_no_movies(): void
    {
        $genre = $this->genre('series-only');

        $this->series($genre, backdrop: 'shows/backdrop.jpg', poster: 'shows/poster.jpg');

        $this->assertSame('shows/backdrop.jpg', $this->batched($genre));
    }

    public function test_it_prefers_the_most_recently_published_title(): void
    {
        $genre = $this->genre('recency');

        $this->movie($genre, backdrop: 'movies/old.jpg', poster: null, published: now()->subYear());
        $this->movie($genre, backdrop: 'movies/new.jpg', poster: null, published: now()->subDay());

        $this->assertSame('movies/new.jpg', $this->batched($genre));
    }

    /**
     * Null is a real answer, and the tile draws its label on the surface
     * colour rather than an empty frame. It must not be a missing key.
     */
    public function test_a_genre_with_no_published_content_resolves_to_null(): void
    {
        $genre = $this->genre('empty');

        Movie::factory()->create(['status' => Movie::STATUS_DRAFT, 'published_at' => null])
            ->genres()->attach($genre->id);

        $genres = Genre::whereKey($genre->id)->get();
        Genre::attachFeaturedImages($genres);

        $this->assertNull($genres->first()->featured_image_url);
    }

    /**
     * The property this replaces an N+1 with, and the reason the method
     * exists. Three genres would be up to twelve queries through the accessor.
     */
    public function test_it_resolves_a_whole_collection_in_two_queries(): void
    {
        foreach (['one', 'two', 'three'] as $slug) {
            $this->movie($this->genre($slug), backdrop: "movies/{$slug}.jpg", poster: null);
        }

        $genres = Genre::all();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        Genre::attachFeaturedImages($genres);

        $this->assertSame(2, $queries, 'One pass over movies, one over shows, whatever the genre count.');
        $this->assertCount(3, $genres->filter(fn ($g) => $g->featured_image_url !== null));
    }

    /**
     * `featured_image_url` is not a column. Setting it must not leave the model
     * dirty, or an unrelated `save()` further down a request would try to write
     * it and fail.
     */
    public function test_attaching_an_image_does_not_make_the_model_dirty(): void
    {
        $genre = $this->genre('clean');
        $this->movie($genre, backdrop: 'movies/art.jpg', poster: null);

        $genres = Genre::whereKey($genre->id)->get();
        Genre::attachFeaturedImages($genres);

        $this->assertSame([], $genres->first()->getDirty());

        $genres->first()->name = 'Renamed';
        $genres->first()->save();

        $this->assertSame('Renamed', $genre->fresh()->name);
    }

    // ── fixtures ─────────────────────────────────────────────────────

    private function genre(string $slug): Genre
    {
        return Genre::create(['name' => ucfirst($slug), 'slug' => $slug]);
    }

    private function movie(Genre $genre, ?string $backdrop, ?string $poster, $published = null): Movie
    {
        $movie = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => $published ?? now()->subDay(),
            'backdrop_url' => $backdrop,
            'poster_url' => $poster,
        ]);
        $movie->genres()->attach($genre->id);

        return $movie;
    }

    private function series(Genre $genre, ?string $backdrop, ?string $poster): Show
    {
        $show = Show::create([
            'title' => 'Series ' . uniqid(),
            'slug' => 'series-' . uniqid(),
            'status' => 'published',
            'published_at' => now()->subDay(),
            'backdrop_url' => $backdrop,
            'poster_url' => $poster,
        ]);

        // Show::published() needs a published episode, or the join finds
        // nothing and the test passes for the wrong reason.
        $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'S1']);
        Episode::create([
            'season_id' => $season->id,
            'number' => 1,
            'title' => 'Pilot',
            'published_at' => now()->subDay(),
            'video_url' => 'https://origin.example.test/x.mp4',
        ]);

        $show->genres()->attach($genre->id);

        return $show;
    }

    /** The batched answer for one genre. */
    private function batched(Genre $genre): ?string
    {
        $genres = Genre::whereKey($genre->id)->get();
        Genre::attachFeaturedImages($genres);

        return $genres->first()->featured_image_url;
    }
}
