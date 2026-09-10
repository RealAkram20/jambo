# Rearranging the homepage sections

**Status: DONE, both stages. Kept for its findings, not as a work item.**
Stage 1 shipped as 1.8.36 on 2026-09-09. Stage 2 shipped as 1.8.38 on
2026-09-10 — see `docs/adr/0007-one-home-arrangement-for-both-surfaces.md`.

> 🔴 **§3's estimate was wrong, and that is the most useful thing in this
> file.** It calls the two surfaces' vocabularies "structural, not cosmetic"
> and defers the website behind an ADR. The website in fact already had a
> partial for almost every rail it was not drawing, each reading a collection
> `HomeRailsService::forWeb()` already returned, each simply never included;
> and the three `random-category-rail` slots were stand-ins for exactly those
> missing rails rather than a rival vocabulary. The mismatch was a subset, not
> a structure. **Read the second surface's files before sizing work against
> it** — that is now a rule in `~/.claude/skills/engineering-standards/SKILL.md`.

Written 2026-09-09 at Rio's request, for a different session to build.

> "we are planing to build the drag feature where we rearrange the sections on
> the homepage like we switch homepage elements and we arange them accordingly
> remember we also have categories too the sliders etc. so i was thinging of
> thaving the api that is responsible of the categories or the arrangement of
> the them we apply it on the app to keep thing organised and updated"
> — Rio, 2026-09-09

Every fact below was read from the code on that date. Nothing here was
inferred from documentation.

---

## 0. Read this first

**Most of this already exists, and the app needs no change at all.** The
temptation is to build an arrangement feature into the app. Do not. Read §1
before writing anything.

**One finding decides the whole shape of the work, and it is in §3:** the
website and the API describe the homepage with two different, non-matching
vocabularies. Any plan that assumes one list of sections is wrong.

---

## 1. What already works

### The API already serves an ordered list

`Modules/Frontend/app/Http/Controllers/Api/V1/HomeController::show()` returns
`rails` as **one ordered array**, each entry carrying a stable `key`. Its own
docblock states the intent:

> The response is one ordered list of rails rather than a fixed object, so the
> app renders whatever it is given and a rail added here does not need an app
> release. Empty rails are dropped — a heading over nothing is worse than no
> heading.

The keys, in the order the controller currently hard-codes them:

| # | Rail key | Source |
|---|---|---|
| 1 | `continue_watching` | watch history |
| 2 | `top_movies` | Top 10 movies, 7-day distinct viewers |
| 3 | `top_series` | Top 10 series, 7-day distinct viewers |
| 4 | `top_picks` | personalised |
| 5 | `smart_shuffle` | personalised ("AI Smart Shuffle") |
| 6 | `latest_movies` | recency |
| 7 | `latest_series` | recency |
| 8 | `fresh_picks` | personalised |
| 9 | `exclusives` | "Only on Jambo" |
| 10 | `popular_movies` | popularity |
| 11 | `international_series` | filter |
| 12 | `upcoming` | release date |
| 13.. | `category:<slug>` | one per admin category, already dynamic |
| .. | `genres`, `vjs`, `personalities` | taxonomy rails |

### The app already renders whatever order it is given

`mobile/src/screens/HomeScreen.tsx`:

```
const rails = renderableRails(data.rails);
...
{rails.map((rail) => ( ... ))}
```

**So reordering requires zero app changes.** State this to anyone who proposes
otherwise.

### The admin drag pattern exists three times

- `Modules/Content/resources/views/admin/featured/index.blade.php` — newest
  and closest, itself copied from the categories page
- `resources/views/DashboardPages/persons/PersonCategoies.blade.php`
- `Modules/Pages/resources/views/admin/pages/partials/footer-fields.blade.php`

All SortableJS. The persistence contract is already settled twice:

```php
// FeaturedController::reorder(), and CategoryController::reorder() identically
$data = $request->validate([
    'order' => 'required|array',
    'order.*' => 'integer|exists:featured_items,id',
]);

foreach (array_values($data['order']) as $position => $id) {
    FeaturedItem::whereKey($id)->update(['sort_order' => $position]);
}
```

**Copy this. Do not invent a fourth ordering contract.**

---

## 2. What is actually missing

One thing: **storage**. The order is a literal array in `HomeController::show()`.
There is no migration and no settings row holding a custom order.

---

## 3. 🔴 The finding that decides the design

**The website's homepage order is hard-coded in Blade, and its sections do not
map one-to-one onto the API's rail keys.**

`Modules/Frontend/resources/views/Pages/MainPages/ott-page.blade.php` renders,
in order: `continue-watching`, `recommended`, `top-ten-block`,
`top-ten-tvshow`, `category-rails`, `vjs`, `only-on-streamit`,
`random-category-rail` (slot 0), `upcomming`, `verticle-slider`,
`Your-Favourite-Personality`, `random-category-rail` (slot 1), `tab-slider`,
`geners`, `random-category-rail` (slot 2).

Compare that with the API list in §1 and the mismatch is structural, not
cosmetic:

- **The website has sections the API does not expose at all.**
  `verticle-slider` is the Top 10 Movies of the **day** (24h) and `tab-slider`
  is the Top 10 Series of the day — distinct from `top_movies` / `top_series`,
  which are the **7-day** rails. `random-category-rail` appears three times at
  fixed slots interleaved between other sections.
- **The API has rails the website has no matching include for**, at least not
  under a recognisable name: `top_picks`, `latest_movies`, `latest_series`,
  `fresh_picks`, `popular_movies`, `international_series`.
- **The names differ even where the content matches**: `only-on-streamit`
  versus `exclusives`, `geners` versus `genres`, `recommended` versus
  `smart_shuffle`.

**Consequence:** "one arrangement governing both surfaces" is not a small
change. It requires deciding a single section vocabulary and reconciling both
renderers to it. Any estimate that assumes otherwise is wrong.

### 🔴 Confirmed against the LIVE site, 2026-09-09

Rio supplied a full-page capture of `jambofilms.com` and asked why the app's
home screen differs. It does, and this is the evidence rather than a reading
of the Blade.

**Live order:** hero, Continue Watching, AI Smart Shuffle, Top 10 Movies This
Week, Top 10 Series This Week, New Releases, VJs, Only On Jambo, Indian
Movies, a full-width "#4 in Movies Today" slider, Your Favourite Personality,
Animation Movies, a full-width "#7 in Series Today" slider with an episode
panel, Genres, Korean Movies.

**App order:** hero, Continue Watching, Movies to Watch, Best in Series This
Week, Latest Movies, Latest Series, Fresh Picks Just For You, Only on Jambo,
Popular Movies, Top Picks for You, AI Smart Shuffle.

Three kinds of difference, and only the first is about ordering:

1. **Order.** AI Smart Shuffle is third on the web and near the bottom in the
   app.
2. **Composition.** The web interleaves its category rails at fixed slots
   (Indian, Animation, Korean) and carries two full-width day-based sliders the
   app has no equivalent of. The app carries Latest Movies, Latest Series,
   Fresh Picks, Popular Movies and Top Picks, none of which appear on the live
   page. "Upcoming" is in the Blade but rendered nothing, so it is presumably
   empty rather than missing.
3. 🔴 **The same rail is given two different headings**, which is the one that
   makes the two surfaces look like different products:

| Rail (identical data) | Website key → heading | App key → heading |
|---|---|---|
| Top 10 movies, 7 day | `top_ten` → **"Top 10 Movies This Week"** | `movies_to_watch` → **"Movies to Watch"** |
| Top 10 series, 7 day | `top_10_tvshow_to_watch` → **"Top 10 Series This Week"** | `best_in_tv` → **"Best in Series This Week"** |

`lang/en/sectionTitle.php` even carries `top_10_movies_to_watch` with the same
value as `top_ten`, so there are three keys in play for two headings.

**Consequence for this plan.** Stage 1 seeds `home_sections.label` from the
API's keys, which are the app's wording. Deciding the arrangement therefore
also means deciding the WORDING, or an admin will drag a row called "Movies to
Watch" and watch a shelf called "Top 10 Movies This Week" move on the website.
Reconciling the headings is cheap — it is a translation-key choice, not code —
and it should happen in stage 2 with the vocabulary reconciliation, not be
left implicit.

### 🔴 And the rail keys are already a published contract

Verified in `mobile/src/api/catalogue.ts`. Two app behaviours key on the
literal strings, beyond ordering:

- `isNumberedRail(key)` — decides the Top 10 numeral treatment, on
  `top_movies` and `top_series`.
- `collectionKeyFor(key)` — decides whether a rail gets a "View all" and where
  it points.

**The rail and collection key spaces do not match.** `/home` is underscored,
`/collections` is hyphenated, most convert by swapping the separator, three
rails have no archive at all, and `exclusives` maps to `only-on-streamit` by
an exception table with no rule behind it, pinned by unit tests.

So if a stored order keys on rail keys — and it should — then
**`home_sections.key` is a foreign key into a published contract**:

- Adding a rail server-side stays free.
- **Renaming one is a breaking change** that must move the app's mapping with
  it, and the symptom of getting it wrong is a silently dead "View all" rather
  than a loud failure.

That sentence belongs in the migration's docblock. Credit: jambo-69 raised it;
confirmed by reading `catalogue.ts` directly.

### Resume is safe

Also confirmed: an admin moving or disabling Continue Watching does not break
resume. `RailFor` switches on `rail.kind === 'progress'`, never on the key, and
the Continue Watching screen is a separate route calling `GET
/continue-watching` directly. Disabling the rail hides a shelf; it does not
remove the ability to resume.

---

## 4. The decision

**Which surfaces does one arrangement govern?**

| | A. API only | B. Both, one slice | C. Both, staged |
|---|---|---|---|
| App follows admin order | yes | yes | yes |
| Website follows | **no** | yes | later |
| Reconcile §3 vocabularies | avoided | required now | required now, applied later |
| Touches Streamit Blade | no | **yes** | second stage only |
| Size | small | large | small, then medium |

**Recommendation: C.**

Rio's own words are "have the API responsible for the arrangement... apply it
on the app", so the app is the surface he is asking for and it costs nothing.
But building the registry as **API-only** would make it the wrong shape to
extend, so the registry is designed for both from the start and only the
website's adoption is deferred.

The reason to defer the website specifically: `ott-page.blade.php` is Streamit
template territory, and this repo's binding ground rule is never to modify
template Blade layouts directly. Restructuring it to render from a registry is
a real decision about the upgrade path and **deserves its own ADR**, not a
paragraph in a feature ticket.

---

## 5. The work, stage 1

**Nothing in `mobile/` changes. If a diff touches `mobile/`, something is wrong.**

1. **Migration `home_sections`**, modelled on `featured_items`:
   `key` (string, unique), `label` (string, nullable — admin override),
   `position` (unsigned int, indexed, default 0), `enabled` (bool, default
   true), timestamps. Seed one row per key in §1's current order, so the
   default arrangement is exactly today's and the change is invisible until an
   admin drags something.
   **Docblock must carry the §3 warning about renaming keys.**
2. **`HomeSection` model** in `Modules/Frontend`, per the modularity standard
   now recorded in `D:\OS\references\standard-stack.md`.
3. **`HomeController::show()`** builds the same rails, then orders and filters
   by the table. **An unknown key is rendered last, never dropped** — a rail
   added in code before its row exists must still appear, or a deploy order
   silently loses a section.
4. **Admin screen**, copying `admin/featured/index.blade.php` including its
   SortableJS wiring and the `reorder` endpoint contract quoted in §1. Add an
   enable/disable toggle. `category:*` rails are already ordered by the
   existing categories page — **do not build a second control for them**;
   surface the category block as one movable section.
5. **Tests**: default order matches today's; a dragged order is reflected in
   `/home`; a disabled section is absent; an unknown key sorts last rather
   than vanishing; `continue_watching` disabled still leaves `GET
   /continue-watching` working.

## 6. Stage 2, needing an ADR first

Reconcile the §3 vocabularies and make `ott-page.blade.php` render from the
registry. The ADR should decide whether the website's day-based Top 10 sliders
and its three `random-category-rail` slots become first-class sections with
keys — and therefore appear in the app — or stay website-only, which means
accepting that the two surfaces are deliberately not identical.

---

## 7. What this plan does not do

- **No app changes.** Deliberate, and the single most important line here.
- **No new drag implementation.** Copy the featured one.
- **No renaming of any rail key.** See §3.
- **No reordering of category rails among themselves.** That already exists.
