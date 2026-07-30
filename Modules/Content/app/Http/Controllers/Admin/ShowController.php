<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminListContext;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Content\app\Http\Requests\StoreShowRequest;
use Modules\Content\app\Http\Requests\UpdateShowRequest;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Tag;
use Modules\Content\app\Models\Vj;
use Modules\Content\app\Services\ContentAnnouncer;
use Modules\Content\app\Services\ContentPlanAssigner;
use Modules\Subscriptions\app\Support\ContentTiers;

/**
 * Admin CRUD for shows.
 *
 * Mirrors MovieController. The show row itself + its genre/category/tag
 * attachments + its cast live on this form; seasons and episodes are
 * managed by their own controllers but surfaced on the show edit page.
 *
 * Routes: /admin/shows/*
 * Middleware: web + auth + role:admin (set in the route file).
 */
class ShowController extends Controller
{
    public function __construct(private readonly ContentAnnouncer $announcer)
    {
    }

    public function index(Request $request): View
    {
        $query = Show::query()
            ->with(['genres', 'categories'])
            ->withCount(['seasons', 'cast']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where('title', 'like', "%$search%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Sort: recently added (default), recently updated, or title.
        // Direction is admin-selectable; title defaults to A→Z, the
        // date sorts default to newest-first.
        $sortColumns = ['recent' => 'created_at', 'updated' => 'updated_at', 'title' => 'title'];
        $sort = (string) $request->query('sort');
        $sort = array_key_exists($sort, $sortColumns) ? $sort : 'recent';
        $dir = $request->query('dir');
        if (!in_array($dir, ['asc', 'desc'], true)) {
            $dir = $sort === 'title' ? 'asc' : 'desc';
        }

        $shows = $query
            ->orderBy($sortColumns[$sort], $dir)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('content::admin.shows.index', [
            'shows' => $shows,
            'search' => $search,
            'statusFilter' => $status,
            'sort' => $sort,
            'dir' => $dir,
            'listQuery' => AdminListContext::remember('series', $request),
            'tierOptions' => ContentTiers::pickerOptions(),
            'statusCounts' => [
                'all' => Show::count(),
                'draft' => Show::where('status', 'draft')->count(),
                'upcoming' => Show::where('status', 'upcoming')->count(),
                'published' => Show::where('status', 'published')->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('content::admin.shows.create', [
            'show' => new Show(['status' => 'draft', 'year' => now()->year]),
            'genres' => Genre::orderBy('name')->get(),
            'vjs' => Vj::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'persons' => Person::orderBy('last_name')->orderBy('first_name')->get(),
            'currentGenreIds' => [],
            'currentVjIds' => [],
            'currentCategoryIds' => [],
            'currentTagIds' => [],
            'currentCast' => [],
            'tierOptions' => ContentTiers::pickerOptions(),
            'listQuery' => AdminListContext::resolve('series', $request),
        ]);
    }

    public function store(StoreShowRequest $request): RedirectResponse
    {
        $show = DB::transaction(function () use ($request) {
            $data = $request->validated();

            $show = Show::create([
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['title']),
                'synopsis' => $data['synopsis'] ?? null,
                'year' => $data['year'] ?? null,
                'rating' => $data['rating'] ?? null,
                'poster_url' => $data['poster_url'] ?? null,
                'backdrop_url' => $data['backdrop_url'] ?? null,
                'trailer_url' => $data['trailer_url'] ?? null,
                'tier_required' => $data['tier_required'] ?? null,
                'status' => $data['status'] ?? 'draft',
                // Release / publish date — see MovieController for
                // rationale. User-supplied value wins; otherwise we
                // stamp now() on a published status, null otherwise.
                // LocalTime::toUtc reads the form's wall clock as EAT so
                // it doesn't land hours ahead of the UTC now() every
                // visibility check compares against.
                'published_at' => ! empty($data['published_at'])
                    ? LocalTime::toUtc($data['published_at'])
                    : (($data['status'] ?? 'draft') === 'published' ? now() : null),
            ]);

            $this->syncRelationships($show, $data);

            return $show;
        });

        $this->announcer->announceShow($show);

        return redirect()
            ->route('admin.series.edit', ['show' => $show] + AdminListContext::resolve('series', $request))
            ->with('success', "Show \"{$show->title}\" created.");
    }

    public function edit(Request $request, Show $show): View
    {
        $show->load(['genres', 'vjs', 'categories', 'tags', 'cast', 'seasons']);

        $seasons = $show->seasons()->withCount('episodes')->orderBy('number')->get();

        return view('content::admin.shows.edit', [
            'show' => $show,
            'seasons' => $seasons,
            'tierOptions' => ContentTiers::pickerOptions(),
            'listQuery' => AdminListContext::resolve('series', $request),
            'genres' => Genre::orderBy('name')->get(),
            'vjs' => Vj::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'persons' => Person::orderBy('last_name')->orderBy('first_name')->get(),
            'currentGenreIds' => $show->genres->pluck('id')->toArray(),
            'currentVjIds' => $show->vjs->pluck('id')->toArray(),
            'currentCategoryIds' => $show->categories->pluck('id')->toArray(),
            'currentTagIds' => $show->tags->pluck('id')->toArray(),
            'currentCast' => $show->cast->map(fn ($p) => [
                'id' => $p->id,
                'role' => $p->pivot->role,
                'character_name' => $p->pivot->character_name,
                'display_order' => $p->pivot->display_order,
                'label' => trim($p->first_name . ' ' . $p->last_name),
            ])->values(),
        ]);
    }

    public function update(UpdateShowRequest $request, Show $show): RedirectResponse
    {
        DB::transaction(function () use ($request, $show) {
            $data = $request->validated();

            $show->fill([
                'title' => $data['title'],
                'synopsis' => $data['synopsis'] ?? null,
                'year' => $data['year'] ?? null,
                'rating' => $data['rating'] ?? null,
                'poster_url' => $data['poster_url'] ?? null,
                'backdrop_url' => $data['backdrop_url'] ?? null,
                'trailer_url' => $data['trailer_url'] ?? null,
                'tier_required' => $data['tier_required'] ?? null,
            ]);

            // Only re-slug if title actually changed.
            if ($show->isDirty('title')) {
                $show->slug = $this->uniqueSlug($data['title'], $show->id);
            }

            // Explicit release / publish date from the form wins over
            // the auto-stamp. array_key_exists so clearing the field
            // nulls the column.
            if (array_key_exists('published_at', $data)) {
                $show->published_at = LocalTime::toUtc($data['published_at']);
            }

            // Status transitions: draft → published stamps published_at
            // when the admin didn't supply their own date.
            if (($data['status'] ?? $show->status) !== $show->status) {
                $show->status = $data['status'];
                if ($data['status'] === 'published' && !$show->published_at) {
                    $show->published_at = now();
                }
            }

            $show->save();

            $this->syncRelationships($show, $data);
        });

        $this->announcer->announceShow($show);

        return redirect()
            ->route('admin.series.edit', ['show' => $show] + AdminListContext::resolve('series', $request))
            ->with('success', 'Show saved.');
    }

    public function destroy(Request $request, Show $show): RedirectResponse
    {
        $title = $show->title;
        $show->delete();

        return redirect()
            ->route('admin.series.index', AdminListContext::resolve('series', $request))
            ->with('success', "Deleted \"$title\".");
    }

    /**
     * Bulk-delete a set of shows. Iterates with each() so model
     * deleting events fire and seasons/episodes cascade per the
     * Show model's relationships.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $shows = Show::whereIn('id', $data['ids'])->get();
        $count = $shows->count();
        $shows->each(fn (Show $s) => $s->delete());

        return redirect()
            ->route('admin.series.index', AdminListContext::resolve('series', $request))
            ->with('success', "Deleted $count series.");
    }

    /**
     * Bulk-assign a subscription plan to the selected series, cascading the
     * same tier down to every episode underneath them.
     *
     * The cascade is the point, not a convenience. A series row's
     * `tier_required` is only half a paywall: episodes carry their own
     * column, and anything left NULL there reads as free. Writing the tier
     * to both means the gate holds no matter which route the viewer reaches
     * — series page, episode page, bare player, or /watch/src passthrough.
     *
     * Deliberately overwrites per-episode tiers rather than only filling
     * NULLs, so "set this series to Premium" means exactly that and can't
     * leave a stale free episode behind from an earlier plan.
     */
    public function bulkTier(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            // See MovieController::bulkTier — Free is an explicit sentinel so
            // an untouched picker can't mass-unlock a set of series.
            'tier_required' => ['required', 'string', Rule::in(ContentTiers::assignableSlugs())],
        ]);

        $tier = ContentTiers::normalize($data['tier_required'] ?? null);

        [$showCount, $episodeCount] = DB::transaction(function () use ($data, $tier) {
            // The selected series go through assign() so each carries an
            // attributable activity-log entry for the pricing change.
            $shows = ContentPlanAssigner::assign(
                Show::whereIn('id', $data['ids'])->get(),
                $tier
            );

            // Episodes reach their show through seasons, so resolve the
            // season ids first — hasManyThrough can't drive an UPDATE. These
            // are dragged along rather than hand-picked and can number in the
            // hundreds, so they take the mass-update path.
            $seasonIds = Season::whereIn('show_id', $data['ids'])->pluck('id');

            $episodes = $seasonIds->isEmpty()
                ? 0
                : ContentPlanAssigner::cascade(
                    Episode::whereIn('season_id', $seasonIds),
                    $tier
                );

            return [$shows, $episodes];
        });

        $plan = ContentTiers::describe($tier);
        $episodeNote = $episodeCount > 0
            ? " and $episodeCount episode" . ($episodeCount === 1 ? '' : 's')
            : '';

        return redirect()
            ->route('admin.series.index', AdminListContext::resolve('series', $request))
            ->with('success', $tier
                ? "$showCount series$episodeNote now require $plan."
                : "$showCount series$episodeNote set to Free.");
    }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: Str::random(8);
        $slug = $base;
        $i = 2;

        while (Show::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "$base-$i";
            $i++;
        }

        return $slug;
    }

    /**
     * Sync genres, categories, tags, and the cast pivot in one place.
     */
    private function syncRelationships(Show $show, array $data): void
    {
        $show->genres()->sync($data['genre_ids'] ?? []);
        $show->vjs()->sync($data['vj_ids'] ?? []);
        event(new \Modules\Content\app\Events\VjCreditsSynced($show));
        $show->categories()->sync($data['category_ids'] ?? []);
        $show->tags()->sync($data['tag_ids'] ?? []);

        // Cast has a composite pivot (show_id, person_id, role) with
        // extra columns, so we rebuild it from scratch rather than use
        // sync().
        $show->cast()->detach();

        foreach ($data['cast'] ?? [] as $row) {
            if (empty($row['person_id']) || empty($row['role'])) {
                continue;
            }
            $show->cast()->attach($row['person_id'], [
                'role' => $row['role'],
                'character_name' => $row['character_name'] ?? null,
                'display_order' => (int) ($row['display_order'] ?? 0),
            ]);
        }
    }
}
