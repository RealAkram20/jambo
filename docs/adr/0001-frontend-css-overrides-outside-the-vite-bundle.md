# ADR-0001: Frontend CSS overrides live outside the Vite bundle

**Status:** Accepted
**Date:** 2026-09-07

## Context

Jambo is the Streamit template by Iqonic Design. `jambo-setup.md` forbids
editing template CSS, SCSS, JS and layouts: an edited vendor file is
overwritten on the next template update, and the modification is invisible
until it vanishes (the "vendor code Rio has modified" category in
`D:\OS\jambo-os\Provenance.md`).

The public site's CSS is a Vite bundle built from
`Modules/Frontend/resources/assets/sass/app.scss`. The built output
(`public/build-frontend`) is gitignored and the production deploy is
pull-only (`docs/working-on-jambo.md` §3a) — there is no build step on the
VPS — so an SCSS edit is dormant until someone rebuilds by hand, and
production can end up running a different bundle from every developer's
machine.

Since the header rework, `public/frontend/css/jambo-header.css` has been
loaded by `Modules/Frontend/Resources/views/layouts/master.blade.php`
*after* the Vite bundle, through `versioned_asset()` (mtime cache-busting).
It has accumulated non-header rules: the /movie banner background fix
(9e8fef3), the mobile horizontal-overflow guard, `.btn-ghost`, the 7/8-column
poster grid, and in 1.8.9 the hero height caps, the 21:9 detail hero box and
the xl detail-description layout.

The 9e8fef3 fix patched both the CSS file and the SCSS source "so the next
Vite build keeps the fix". That was redundant — the file wins on load order
regardless of rebuilds — and it created exactly the dangerous category above.

## Decision

1. **Every frontend CSS override goes into
   `public/frontend/css/jambo-header.css`.** The name is historical; it is
   the site-wide override sheet. Vendor SCSS under
   `Modules/Frontend/resources/assets/sass/` is never edited.
2. **Overrides copy the vendor selector chain exactly** so they win on equal
   specificity plus cascade order. `!important` only where the vendor rule
   already uses it. When a Bootstrap utility (`!important` by design) is in
   the way, the honest fix is to remove the utility class from the Blade,
   not to out-shout it.
3. **Each block carries a comment** naming the vendor rule it overrides,
   the SCSS file it lives in, and why. A rule with no reason gets deleted by
   whoever finds it inconvenient.
4. **Design values are tokens.** Ratios, floors and ceilings are CSS custom
   properties on `:root` with a `--jambo-` prefix, defined once at the top
   of the block that uses them.

## Consequences

- A template update cannot destroy a Jambo customisation; the diff to
  review after an update is the vendor bundle, not our file.
- No build step on deploy for CSS changes: `git pull` +
  `php artisan view:clear`.
- The file grows. Past roughly 1500 lines, split by concern
  (`jambo-header.css`, `jambo-layout.css`, …) — still plain CSS, still
  loaded after the bundle, still no build.
- Escape hatch: if a change genuinely cannot be expressed as a later-cascade
  override (a vendor `!important` on a long chain), record it in
  `CHANGELOG.md` as a vendor patch and list the file in the Provenance
  page's modified-vendor table, so the next template update knows to
  re-apply it.

## Alternatives considered

- **Edit the SCSS and rebuild.** Cleanest single source, but it breaks the
  upgrade path, needs a build the deploy sequence does not have, and the
  bundle is not in git.
- **Patch both, as 9e8fef3 did.** Two places to keep in sync, and the SCSS
  copy is the invisible one.
- **A second Vite entry for Jambo's own SCSS.** Clean, but it reintroduces
  the build step on deploy for every CSS tweak. Not worth it while the
  override sheet is one file.
