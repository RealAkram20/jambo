<?php

namespace Modules\Content\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\ContentActivity;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The "Created by" credit on the admin movie and series lists.
 *
 * The case that matters is the deleted admin: `created_by` is
 * ON DELETE SET NULL, so removing a staff account would silently blank the
 * credit on everything they ever uploaded. The append-only activity log
 * keeps a name snapshot for exactly that, and the list must fall back to
 * it. The other case that matters is the honest blank: content from before
 * authorship tracking, or from a seeder, has no actor and must render an
 * em dash rather than a guess — the Performance dashboard counts uploads
 * per admin, so a guessed name here becomes someone's payout there.
 */
class CreatorCreditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
    }

    public function test_creator_label_is_the_admin_who_created_the_row(): void
    {
        $admin = $this->admin(['first_name' => 'Grace', 'last_name' => 'Nakato']);

        $movie = $this->actingAs($admin)->createMovie('Dragon Eyes');

        $this->assertSame($admin->id, $movie->created_by, 'Creating while signed in stamps created_by.');
        $this->assertSame('Grace', $movie->fresh()->creatorLabel(), 'The badge shows one name, never two.');
        $this->assertSame('Grace Nakato', $movie->fresh()->creatorFullLabel(), 'The hover title keeps the full name.');
    }

    public function test_creator_label_falls_back_to_username_when_no_full_name(): void
    {
        // first_name / last_name are NOT NULL in the schema, so "no full
        // name" means empty, and the label falls through to the username.
        $admin = $this->admin(['first_name' => '', 'last_name' => '', 'username' => 'vj_junior']);

        $movie = $this->actingAs($admin)->createMovie('Entrapment');

        $this->assertSame('vj_junior', $movie->fresh()->creatorLabel());
    }

    public function test_creator_label_survives_the_admin_being_deleted(): void
    {
        $admin = $this->admin(['first_name' => 'Grace', 'last_name' => 'Nakato']);
        $movie = $this->actingAs($admin)->createMovie('Aladdin');

        $admin->delete();

        // On MySQL the movies_created_by_foreign constraint (DELETE_RULE
        // SET NULL, checked against the live schema) nulls this for us.
        // Tests run on SQLite, which ignores a foreign key added by a later
        // Schema::table() call, so stage the same end state by hand instead
        // of asserting a constraint this driver never applies.
        DB::table('movies')->where('id', $movie->id)->update(['created_by' => null]);

        $movie = Movie::with('creator')->find($movie->id);
        $this->assertNull($movie->created_by);

        Movie::hydrateCreatorLabels(collect([$movie]));

        $this->assertSame(
            'Grace Nakato',
            $movie->creatorFullLabel(),
            'The activity-log snapshot keeps the credit readable after the user row is gone.',
        );
        $this->assertSame('Grace', $movie->creatorLabel(), 'A snapshot shortens to one name exactly like a live record.');
    }

    public function test_creator_label_is_null_when_no_actor_was_ever_recorded(): void
    {
        // No authenticated user: a seeder, an import, or anything added
        // before the 2026_07_13 authorship migration.
        $movie = Movie::factory()->create(['title' => 'Legacy import']);

        $this->assertNull($movie->created_by);

        Movie::hydrateCreatorLabels(collect([$movie]));

        $this->assertNull($movie->creatorLabel(), 'No actor recorded must stay blank, never a guessed name.');
    }

    public function test_hydrate_costs_one_query_regardless_of_row_count(): void
    {
        $admin = $this->admin();
        $movies = collect();
        for ($i = 0; $i < 5; $i++) {
            $movies->push($this->actingAs($admin)->createMovie('Title ' . $i));
        }

        // Wipe created_by on all five so every row needs the fallback —
        // the shape of a page listing content from deleted admins.
        DB::table('movies')->update(['created_by' => null]);
        $rows = Movie::with('creator')->whereIn('id', $movies->pluck('id'))->get();

        DB::enableQueryLog();
        DB::flushQueryLog();
        Movie::hydrateCreatorLabels($rows);

        $this->assertCount(1, DB::getQueryLog(), 'One activity-log query for the whole page, not one per row.');
        $this->assertNotNull($rows->first()->creatorLabel());
    }

    public function test_admin_movie_list_shows_the_creator_name(): void
    {
        $admin = $this->admin(['first_name' => 'Grace', 'last_name' => 'Nakato']);
        $this->actingAs($admin)->createMovie('The Lincoln Lawyer');

        $html = $this->actingAs($admin)->get(route('admin.movies.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Created by', $html);
        $this->assertStringContainsString('>Grace</span>', $html, 'Badge carries one name.');
        $this->assertStringContainsString('Added by Grace Nakato', $html, 'Hover carries the full name.');
    }

    public function test_admin_series_list_shows_the_creator_name(): void
    {
        $admin = $this->admin(['first_name' => 'Grace', 'last_name' => 'Nakato']);

        $this->actingAs($admin);
        Show::create([
            'title' => 'The Wire',
            'slug' => 'the-wire-' . uniqid(),
            'status' => Show::STATUS_DRAFT,
        ]);

        $html = $this->actingAs($admin)->get(route('admin.series.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Created by', $html);
        $this->assertStringContainsString('>Grace</span>', $html, 'Badge carries one name.');
        $this->assertStringContainsString('Added by Grace Nakato', $html, 'Hover carries the full name.');
    }

    public function test_list_renders_an_em_dash_for_content_with_no_creator(): void
    {
        $admin = $this->admin();
        Movie::factory()->create(['title' => 'Legacy import']); // no actor

        $html = $this->actingAs($admin)->get(route('admin.movies.index'))->assertOk()->getContent();

        $this->assertStringContainsString('No creator recorded', $html, 'The blank explains itself on hover.');
    }

    private function admin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes + [
            'username' => 'admin_' . uniqid(),
            'email' => 'admin_' . uniqid() . '@test.local',
        ]);

        $user->assignRole('admin');

        return $user;
    }

    private function createMovie(string $title): Movie
    {
        return Movie::create([
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . uniqid(),
            'status' => Movie::STATUS_DRAFT,
        ]);
    }
}
