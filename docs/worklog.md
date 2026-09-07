# Worklog

Append-only record of work in this repository. Newest at the bottom.

Read it before touching anything. Add your entry **before** you write code.
Release notes with the *why* of each change stay in `CHANGELOG.md`; this file
holds session state, claims, gotchas and what was deliberately not built.

## The rules

1. Claim your files before you edit them.
2. Re-read the tree before you write; `git status` from an hour ago is stale.
3. Never re-implement a shared module. Extend it.
4. Say what you did not build.

## Entries

### 2026-09-07 — Mobile, TV and offline app: plan and proposed ADRs

**Status:** complete (planning only; no application code)
**Owns:** docs/plans/mobile-offline-app.md, docs/adr/0002-mobile-and-tv-app-stack.md,
docs/adr/0003-offline-content-protection.md, docs/adr/0004-app-distribution-and-billing.md,
docs/worklog.md (created)
**Shares:** none

**What this is:** the plan for one Expo app (phone, tablet, Android TV) that
keeps the Streamit design and adds offline downloads that never expose a
playable file, plus the API and backend changes it needs. Three decisions are
written as Proposed ADRs for Rio. Facts about the codebase were read from the
code at v1.8.12; external facts (Expo TV, Play policy, Bunny DRM, DRM
vendors) were read from the web the same day and are cited in the plan.

**Verified:** nothing executed. Codebase facts are `asserted` (read, not run):
no API beyond stubs, heartbeat is web-session, concurrency keyed on Laravel
session id, entitlement logic duplicated in TierGate and userCanWatch, MP4
two-rendition delivery via signed Bunny 302, no mobile client anywhere on the
machine.

**Not verified:** the Media3 cipher sink/source class names in the current
release; whether Bunny MediaCage Enterprise issues offline licences; whether
the Play developer account can register as a merchant from Uganda; which
Bunny pricing tier the pull zone is on; whether Bunny Token Authentication is
ON. Each is listed as a precondition or spike item in the plan.

**Deliberately not built:**
- No code, migrations, API routes or Expo project — the plan needs Rio's
  three decisions first (ADR-0002/3/4), and Phase 0 is a spike.
- No OpenAPI spec yet — Phase 1 deliverable.
- No wiki-side cost commitments — every price is marked approximate.

**For whoever is next:** start with `docs/plans/mobile-offline-app.md` §0 and
§10. Phase 0 is a 5-day spike; the `PlaybackAuthorizer` extraction in Phase 1
must land with behaviour-pinning tests before any API controller calls it.
The Streamit skin in the built CSS bundle carries four `--bs-primary` values;
the live one is `#1a98ff` (manifest `theme_color`), so export tokens from the
rendered site, not from SCSS.

### 2026-09-08 — Featured hero: admin-picked, drag-ordered homepage banner

**Status:** complete (shipped as 1.8.19)
**Owns:** Modules/Content/database/migrations/*_create_featured_items_table.php,
Modules/Content/app/Models/FeaturedItem.php,
Modules/Content/app/Http/Controllers/Admin/FeaturedController.php,
Modules/Content/resources/views/admin/featured/index.blade.php,
Modules/Content/tests/Feature/FeaturedHeroTest.php
**Shares:** Modules/Content/routes/web.php — exact edit: one route group block for
featured index/store/destroy/reorder;
resources/views/components/partials/vertical-nav.blade.php — exact edit: one
nav-item between Categories and Vjs;
Modules/Frontend/app/View/Composers/SectionDataComposer.php — exact edit:
buildHero() reads featured rows first, existing auto-pick becomes the fallback.

**What this is:** Rio wants the OTT homepage hero (the big banner + poster rail
on `/`) chosen by hand instead of by views_count, with drag ordering copied
from the categories page. Scope is the homepage hero ONLY — Rio said explicitly
not to touch the /movie, /series or VJ page banners, which are a different
partial (`movie-slider`) fed by FrontendController's own `$featured`.

**For whoever is next:** the other active session owns docs/plans/ and
docs/adr/0002-4 (mobile app planning, no code) — no overlap.

**Verified:** 14 feature tests pass (fallback, drag order, mixed types, drafts
and episode-less series held back, CRUD, reorder, orphan sweep, admin-only,
and a scope guard proving /movie did not change). Mutation: removing the
`published()` filter failed the draft test, restored. Rendered on a local
server as an admin: empty state, four rows with both badge states, and the
homepage hero following the chosen order.

**Not verified:** production; drag-and-drop was not exercised by a real mouse
(the reorder endpoint is tested directly, the SortableJS wiring is copied
verbatim from the categories page); no mobile-width render of the admin table.

**Gotcha worth remembering:** a Blade view shares its variables with its
layout. The row loop used `$title`, and the admin header partial renders
`$title`; an Eloquent model stringifies to JSON, so the entire movie record
printed across the top of the page. Tests did not catch it because they
asserted on content, not on absence of junk. Rendering caught it. There is now
a test asserting no model is stringified into the page.

**Deliberately not built:**
- No "featured until" scheduling — Rio asked for manual control only.
- No per-page featured lists; /movie and /series banners are explicitly out of
  scope and still use FrontendController's own queries.
- No search in the two pickers; they list the 300 most recent titles each.
  Worth revisiting once the catalogue outgrows that.

### 2026-09-08 — Featured screen: catalogue search + craft pass (1.8.20)

**Status:** complete
**Owns:** same files as the 1.8.19 entry above.
**Shares:** none new.

**What this is:** Rio asked for AJAX search and a more considered screen,
functionality unchanged. The two 300-row dropdowns became one debounced
type-ahead over movies and series (`admin.featured.search`); the list was
reshaped so slot 1 reads as the 16:9 banner and the rest as the 2:3 rail.

**Verified:** 20 tests (6 new for search). Rendered at 1500px and 390px:
empty, search open, four rows including a draft. Impeccable detector clean.

**Not verified:** real-pointer drag; the endpoint is tested directly and the
SortableJS wiring is untouched from 1.8.19.

**Gotchas for whoever is next:**
- Local seed posters are `picsum.photos` URLs. `media_img()` passes external
  URLs through untouched, so a blank poster locally is external latency, not
  a layout bug. Do not chase it.
- `tools/cdp-shots.mjs`: never inject JS that awaits every image. Images with
  `loading="lazy"` below the fold never fire load/error, so the promise never
  settles and the run hangs. Force `loading='eager'` and wait a fixed time.
- A hung run leaves headless Edge holding the profile directory, so the next
  run reuses a logged-in session, finds no login form, and throws. Kill stray
  `msedge` processes before re-running.
