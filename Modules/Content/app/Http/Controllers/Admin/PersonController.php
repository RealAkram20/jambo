<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Content\app\Http\Requests\StorePersonRequest;
use Modules\Content\app\Http\Requests\UpdatePersonRequest;
use Modules\Content\app\Models\Person;

/**
 * Admin CRUD for cast and crew. Flat — no pivots to sync from the
 * person side; the `movie_person` and `show_person` pivots are owned
 * by the Movie and Show controllers and reference persons by id.
 *
 * Routes: /admin/persons/*
 * Middleware: web + auth + role:admin (set in Modules/Content/routes/web.php).
 */
class PersonController extends Controller
{
    public function index(Request $request): View
    {
        $query = Person::query()
            ->withCount(['movies', 'shows']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('known_for', 'like', "%$search%");
            });
        }

        $persons = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('content::admin.persons.index', [
            'persons' => $persons,
            'search' => $search,
            'totalCount' => Person::count(),
        ]);
    }

    public function create(): View
    {
        return view('content::admin.persons.create', [
            'person' => new Person(),
        ]);
    }

    public function store(StorePersonRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $person = Person::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'slug' => $this->uniqueSlug($data['first_name'] . ' ' . $data['last_name']),
            'bio' => $data['bio'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'death_date' => $data['death_date'] ?? null,
            'photo_url' => $data['photo_url'] ?? null,
            'known_for' => $data['known_for'] ?? null,
        ]);

        return redirect()
            ->route('admin.persons.edit', $person)
            ->with('success', "Added \"{$person->first_name} {$person->last_name}\".");
    }

    public function edit(Person $person): View
    {
        $person->loadCount(['movies', 'shows']);
        $person->load(['movies:id,title', 'shows:id,title']);

        return view('content::admin.persons.edit', [
            'person' => $person,
        ]);
    }

    public function update(UpdatePersonRequest $request, Person $person): RedirectResponse
    {
        $data = $request->validated();

        $person->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'bio' => $data['bio'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'death_date' => $data['death_date'] ?? null,
            'photo_url' => $data['photo_url'] ?? null,
            'known_for' => $data['known_for'] ?? null,
        ]);

        if ($person->isDirty(['first_name', 'last_name'])) {
            $person->slug = $this->uniqueSlug(
                $data['first_name'] . ' ' . $data['last_name'],
                $person->id
            );
        }

        $person->save();

        return redirect()
            ->route('admin.persons.edit', $person)
            ->with('success', 'Person saved.');
    }

    public function destroy(Person $person): RedirectResponse
    {
        $label = trim($person->first_name . ' ' . $person->last_name);
        $person->delete();

        return redirect()
            ->route('admin.persons.index')
            ->with('success', "Deleted \"$label\".");
    }

    /**
     * AJAX search endpoint for the cast picker on the movie/show
     * forms. Returns Select2-shaped JSON: {results, pagination}.
     * Without this, every form load was rendering a <select> with
     * every Person row baked into the DOM — fine for a few hundred,
     * unworkable at the scale this catalog will reach.
     *
     * Matches first_name / last_name / known_for. Paginated 20 at a
     * time so even an empty query (used by Select2's first-open
     * prefetch) doesn't dump the whole table.
     */
    public function search(Request $request): JsonResponse
    {
        $q       = trim((string) $request->query('q', ''));
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        $base = Person::query();

        if ($q !== '') {
            $base->where(function ($qb) use ($q) {
                $qb->where('first_name', 'like', "%$q%")
                    ->orWhere('last_name', 'like', "%$q%")
                    ->orWhere('known_for', 'like', "%$q%");
            });
        }

        $total = (clone $base)->count();

        $persons = $base
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['id', 'first_name', 'last_name', 'photo_url', 'known_for']);

        return response()->json([
            'results' => $persons->map(fn ($p) => [
                'id'        => $p->id,
                'text'      => trim("{$p->first_name} {$p->last_name}"),
                'photo_url' => $p->photo_url,
                'known_for' => $p->known_for,
            ]),
            'pagination' => [
                'more' => $page * $perPage < $total,
            ],
        ]);
    }

    /**
     * Quick-create endpoint used by the "+ New person" button on the
     * cast picker. Minimal fields only (first_name, last_name,
     * optional known_for) so admins can add a missing actor mid-flow
     * without leaving the movie/show they're editing. Photo, bio,
     * birth date etc. live on the full edit page — link surfaced in
     * the modal copy.
     */
    public function quickStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'known_for'  => ['nullable', 'string', 'max:255'],
        ]);

        $person = Person::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'slug'       => $this->uniqueSlug($data['first_name'] . ' ' . $data['last_name']),
            'known_for'  => $data['known_for'] ?? null,
        ]);

        return response()->json([
            'ok'     => true,
            'person' => [
                'id'        => $person->id,
                'text'      => trim("{$person->first_name} {$person->last_name}"),
                'photo_url' => $person->photo_url,
                'known_for' => $person->known_for,
            ],
        ], 201);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $i = 2;

        while (Person::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "$base-$i";
            $i++;
        }

        return $slug;
    }
}
