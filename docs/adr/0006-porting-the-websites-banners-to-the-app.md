# ADR-0006: Porting the website's banners to the app

**Status:** Accepted
**Date:** 2026-09-10
**Decided by:** Rio, 2026-09-10, on questions 1 and 3 below
**Asked for by:** Rio — *"i want you to scan the home page for webapp, so that
we make sure we have these banners built on the app. strictly remember we keep
the design from the webapp"*

## Context

The website's home page at `/` (`ott-page.blade.php`) carries three
banner-shaped blocks, as distinct from its fourteen ordinary rails:

| Banner | Source | State before this work |
|---|---|---|
| The hero rotator | `partials/hero-banner.blade.php` | In the app, at a reduced design |
| Top 10 Movies of the Day | `sections/verticle-slider.blade.php` | Absent, including its data |
| Top 10 Series of the Day | `sections/tab-slider.blade.php` | Absent, including its data |

Every ordinary rail already reaches the app through `GET /api/v1/home`. The
banners do not, and the reasons are not the same for each: the hero's data was
being sent in the wrong *shape*, while the other two have no API payload at
all — `verticalFeatured` and `tabSeries` are built in `HomeRailsService` and
never leave it, and neither has a `HomeSection` key, so even the arrangement
screen does not know they exist.

Three questions had to be answered before the first one could be built, and
each will be asked again for the other two. This records the answers so they
are decided once.

**1. What shape does a banner's data arrive in?** A banner draws a backdrop, a
synopsis and three taxonomy lines. `MovieResource` had two shapes — `card()`
for a poster in a rail, `detail()` for a page — and the hero was being sent as
a card, which is why the app's banner was a portrait poster with a year under
it. `detail()` would have fixed the symptom while putting `views_count`,
`published_at`, `categories` and every season of a series onto the critical
path of every first paint.

**2. How faithful is "keep the design from the webapp"?** The site's banner is
Bootstrap and vh units; the app has neither. Matching it by eye produces
something that looks close in a screenshot and is wrong in every measurement
that a designer would notice.

**3. What happens when the website's design is showing something untrue?**
This is not hypothetical and it was found while reading the page:

- `hero-banner.blade.php` reads `ratings()->avg('stars') ?? 5`. The `ratings`
  table holds **0 rows against 120 titles**, and — the part that settled it —
  **nothing in the product can ever write one.** No controller, no endpoint,
  no form, on the website or in the app; only a seeder touches that table. So
  every title on the live site showed five filled gold stars, next to an IMDb
  wordmark, for a rating nobody gave and nobody could give. The same `?? 5` is
  in `vertical-banner.blade.php`, and `movie-slider.blade.php` hardcodes 3.5
  stars on eight listing pages without consulting the database at all.
- The same blade badges an unclassified film `$item->rating ?: 'PG'`.
- `.text-gold`, which colours the "#1 in Movies Today" label on both of the
  other two banners, **resolves to no CSS rule at all** — not in the built
  bundle, not in the override file. That label is body-coloured on the live
  site, so "keep the design" has no design to keep.

## Decision

**A third resource shape, `hero()`, sits between `card()` and `detail()`.** It
carries exactly what `hero-banner.blade.php` renders: `backdrop_url` (already
falling back to the poster server-side), `synopsis`, three genres, three tags,
three cast names, and for a series `seasons_count` and
`episode_runtime_minutes`. The shared fields live in a
`RendersHeroFields` trait so the movie and series versions cannot drift.
**Rails keep sending `card()`**, and a test asserts it.

**Measurements come from the rendered site, not from the eye.** Every number
in `theme.banner` was read with `getComputedStyle` against `/` in a headless
browser at 390x844 — the same method and the same viewport as
`design/export-tokens.mjs`. Where a value is not obvious, the provenance is
written next to it. This is how the banner's 440:390 ratio, its two
*horizontal* scrim gradients and its square-cornered badge were found; all
three are things a careful eye gets wrong.

**Where the website's design displays fabricated data, the fabrication is
removed from BOTH surfaces rather than reproduced in the app or quietly
dropped from it.** This was Rio's decision, and it is stronger than what was
first proposed here — the original draft had the app draw nothing and leave
the website alone, which would have left the two surfaces disagreeing about a
fact.

Concretely, and all of it now live:

- **The five-star row is gone from the app and from the website.** It was
  never a rating. `ratings` holds 0 rows against 120 titles, and **nothing in
  the product can write one** — no controller, no endpoint, no form, on either
  surface; only a seeder touches that table. So `?? 5` was not a fallback for
  an edge case, it was the only value the site had ever displayed. Removed
  from `hero-banner`, `vertical-banner`, the guest home hero, `movie-slider`
  (which was worse — hardcoded 3.5 stars on eight listing pages, not reading
  the database at all) and the `parallax` placeholder.
- **`stars_avg` was removed from the API too**, along with the `loadAvg` that
  computed it. A field nothing draws is a field that grows a consumer later.
- **An unclassified film gets no certification badge**, where the blade
  printed the literal `'PG'`.
- **The IMDb wordmark stays**, on both surfaces, by Rio's explicit call. It is
  a mark rather than a claim about a particular title, and with the invented
  score beside it gone there is no longer a number being attributed to IMDb.

The test is whether a reasonable viewer would be misled about a fact. A gold
star row is a claim about what other people thought of a film. A 7pt corner
radius is not a claim about anything.

**One bug fell out of the same read.** The guest home page drove its stars
from `$movie->rating` — a content certification, "PG-13" or "NC-17" — so
`$i > "NC-17"` was comparing an integer against a string, and it printed that
certification beside the IMDb mark where a score belongs. A viewer read
"NC-17" as a rating out of ten. It is a proper badge now.

**Departures from the site's behaviour are allowed where the site's behaviour
is desktop-shaped, and each is named in the component.** For the hero those
are: no auto-rotation, because a carousel that moves on its own takes away
what a viewer is reading and moves a d-pad's target; and no thumbnail strip,
which is not a departure at all — the site's own strip computes to
`display: none` at phone width and its dots take over.

## Consequences

- The next two banners have their shape decided and their ethics decided.
  What they still need is a server payload, because neither dataset leaves
  `HomeRailsService` today. Both will also arrive carrying `?? 5` and the dead
  `.text-gold`; both answers are above.
- **Nine website files changed**, which is more than an app slice usually
  touches and was the right call rather than a scope overrun: the fabricated
  star row appeared on the home banner, the Top 10 banner, the guest home
  hero, the placeholder parallax block and every listing banner on the site.
  Leaving any of them would have meant the rule held in some places only.
- **`$movieRating` is now a prop that gates nothing.** Eight pages still pass
  it. Removing it is a tidy-up for whoever is next in `movie-slider`, not
  worth its own churn now.
- The website's banner still lazy-loads every episode of every hero series to
  find one runtime. The API path batches that into one aggregate; the blade
  was left alone, because changing its query shape is a different change from
  removing a star row. Its *other* N+1, `ratings()->avg()` per slide, is gone
  simply because the thing that needed it is gone.
- `theme.banner` is hand-measured rather than generated, which means it can go
  stale if the site is restyled. The mitigation is that its values are written
  with their selectors, so a re-measure is a re-run of the same probe rather
  than an archaeology exercise. Adding these selectors to
  `design/export-tokens.mjs` is the better answer and is not done.

## Alternatives considered

**Send the hero as `detail()`.** One line instead of a new shape. Rejected
because it puts a series' entire season and episode tree on the home screen's
critical path, and because "what the banner draws" then has no definition
anybody can test.

**Reproduce the website exactly, invented ratings included.** The literal
reading of "keep the design from the webapp", and it keeps the two surfaces
identical. Put to Rio and rejected by him: five gold stars beside an IMDb mark
is a statement about a film that no viewer made.

**Build real ratings.** Also put to Rio: a rate control on both surfaces, an
endpoint behind it, and the row driven by the real average. Rejected for now
as a feature rather than a fix, but it is the only option that would ever make
the stars mean something, and the layout is documented here if it is revived.

**Drop the stars from the app only, leave the website alone.** What this ADR
originally proposed. Rejected by Rio in favour of fixing both, and he was
right: two surfaces disagreeing about whether a film has been rated is worse
than either answer on its own.
