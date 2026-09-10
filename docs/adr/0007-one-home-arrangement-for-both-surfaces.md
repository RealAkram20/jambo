# ADR-0007: one home arrangement, governing the website as well as the app

- **Status:** Accepted
- **Date:** 2026-09-10
- **Deciders:** Rio
- **Supersedes:** stage 2 of `docs/plans/homepage-section-arrangement.md`

## Context

`/admin/home-sections` shipped on 2026-09-09 as stage 1 of that plan. It works:
eighteen rows, drag to order, a switch to hide, and `GET /api/v1/home` obeys
all of it. It governed the app and nothing else.

Rio, 2026-09-10:

> i feel it's off and we need to make sure it works and serves the home page
> and note: it's not meant for the mobile app but for the whole system.
> [...] having things half done for the sake of doing is as good as useless

The screen was half of a feature. An admin dragging a row watched the app
change and the website not change, with nothing on the screen saying so.

Stage 1 deferred the website for a stated reason: `ott-page.blade.php` is
Streamit template territory, the plan's §3 found the two surfaces describe the
home page in non-matching vocabularies, and reconciling them "deserves its own
ADR, not a paragraph in a feature ticket". This is that ADR.

### What reading the code changed about the plan's estimate

The plan's §3 called the mismatch structural and warned that any estimate
assuming one list of sections is wrong. Two findings from 2026-09-10 make it
much smaller than that:

1. **The website already has a partial for almost every rail the app draws.**
   `top-pict`, `latest-movies`, `fresh-picks-just-for-you`, `Popular-movies`
   and `best-of-international-shows` all exist, all read collections
   `HomeRailsService::forWeb()` already returns, and none of them were included
   on the home page. Only Latest Series had no partial, and it is
   `latest-movies` with the show collection.
2. **The website's three `random-category-rail` slots are not a section
   vocabulary of their own.** Their own comment says they replaced the retired
   Top Picks / Popular Movies / Fresh Picks rails. Those rails are exactly what
   finding 1 puts back, so the stand-ins have no reason to exist.
3. The two full-width daily sliders already had rows —`top_movies_today` and
   `top_series_today` — added on 2026-09-10.

So the vocabularies were never really two. The website was drawing a subset,
under stand-in names, from the same service.

## Decision

**One arrangement governs both surfaces. `home_sections` is the home page.**

The website's home page no longer lists its shelves. Order, visibility and
heading, for the website and the app alike, are the rows of that table.

Concretely:

- `HomeSection::WEB_VIEWS` sits beside `HomeSection::DEFAULTS` and maps each
  section to the Blade partial that draws it, the shared collection it reads,
  whether it is full-width, and any extra parameters. A section is **one row in
  two constants in one class and nowhere else.**
- `HomeSection::webPlan()` turns the stored rows into runs of partials for the
  page to include. Consecutive in-container sections are grouped so the page
  container opens once per run and a full-width banner breaks the run — which
  is necessary because an admin can now drag a banner anywhere, so where the
  container opens is not knowable at authoring time.
- `components/sections/arranged.blade.php` is the renderer. It is the whole of
  the website's half of the feature.
- `ott-page.blade.php` includes it and passes `upcomingItems`, the one
  collection the route action holds rather than the service.
- Every section partial takes `$sectionHeading`, falling back to the
  translation key it already used. An admin's label reaches the website the
  way it already reached the app, and every other page including these
  partials is unaffected.
- A section whose collection is empty is dropped, which is the rule the API
  already followed. A heading over nothing is worse than no heading.
- The category shelves are one movable block. Their order among themselves
  stays where it already was, on the Categories screen. Two controls for one
  order is how two orders start disagreeing.
- **Every Visible Home category becomes a shelf.** The service used to return
  the pool split as one "fixed" shelf plus three, and cap it at four. That
  split described the old fixed-plus-rotating slot layout, which no longer
  exists, and the cap was overruling the admin's own toggle — six categories
  flagged, four rendered. It was applied to the result rather than the query,
  so removing it costs nothing at the database. The count is now the admin's.
  The cost per shelf is written into the service beside it: the eager load
  reads every published title in a category to keep twelve, and bounding that
  needs a window function, not a `take()`.

**Rejected:** keeping the three rotating category slots as their own rows.
That would give the website four category positions the app has no equivalent
of, which is the drift this decision exists to end.

**Rejected:** disabling the six restored rails so the website looks unchanged.
Rio chose the full list. A section he does not want is one click on the screen,
which is the point of the screen.

## Consequences

**The website's home page composition changed.** It now draws Top Picks,
Latest Movies, Latest Series, Fresh Picks, Popular Movies and Best of
International Series, which it did not, and its category shelves are
consecutive rather than scattered. This is visible to viewers on the next
deploy and it is reversible entirely from the admin screen.

**Editing `ott-page.blade.php` to move a shelf is now a regression.** It would
put the two surfaces back out of step. The file says so.

**The template ground rule is respected in substance, not in letter.** This
repo never modifies Streamit CSS, JS or layouts. `ott-page.blade.php` is a page
view, not a layout, and it was already project-authored — its own comments
record an SEO title, an `<h1>` fix, a promoted Smart Shuffle and three replaced
rails. What replaced it is *less* template divergence, not more: fifteen
hard-coded includes became one, and the section partials each took a one-line
heading fallback. The upgrade path is better off than it was.

**`home_sections.key` is now a foreign key into two published contracts.** It
already bound the app's Top 10 numerals and View All destinations; it now also
binds a Blade partial. Adding a key stays free. Renaming one is still a
breaking change and still fails silently in the app.

**The guard is a test, not a convention.** `HomeSectionArrangementTest` now
asserts that the two constants carry the same keys in the same order, that
every mapped partial exists, that a drag moves a website heading, that a
disabled section leaves the website, and that a label renames it there. The
last two were verified by mutation: removing the enabled filter and ignoring
the label each fail their own test and nothing else.

## Verification

Rendered, not only tested. `http://127.0.0.1:8090/` as a guest: thirteen
headings in exactly the stored order, both full-width daily banners present,
Continue Watching and Upcoming correctly absent because both collections are
empty for a guest on this machine. Disabling Top 10 Movies removed it from the
page; renaming Popular Movies to "Uganda Loves These" and moving it to the top
did both. The table was restored afterwards.

**A real pointer drag was verified, and not by us.** Somebody reordered the
rows in a browser while this was being written: all eighteen positions were
written in one request at 20:26 UTC, moving both daily banners into the middle
of the page. The rendered home page matched the new order exactly. That was the
last item on the "not verified" list and it closed itself.

**The category block was verified with content.** Six categories flagged
Visible Home produced six shelves, contiguous, at the block's stored position,
on the website and in `/api/v1/home` alike. Before the cap came off it was
four. The flags were restored afterwards.

## What this does not do

- **No app change. No file under `mobile/` was touched.** The app already
  renders whatever ordered list it is given.
- **No rail key added, renamed or reordered in `DEFAULTS`.** The API payload is
  byte-for-byte what it was.
- **No reconciliation of the heading wording.** That was already done on
  2026-09-09; the website's wording won and the API adopted it.
- **`FrontendController::ott()` still computes `$featuredMovies`,
  `$latestMovies` and `$popularShows` that the composer then overwrites.**
  Three wasted queries per home page, found while doing this and deliberately
  left alone: restructuring a route action inside a feature slice is the
  failure mode, not the fix. It is in the worklog.
