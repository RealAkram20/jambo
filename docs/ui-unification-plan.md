# Jambo UI Unification Plan

Audit date: 2026-07-13. Covers **admin**, **partner (Creator Studio)**, and **user (account/profile)** surfaces.

> **Hard constraint: KEEP the current admin panel.** The Streamit admin shell/theme is the
> reference design — it is NOT to be replaced or restyled. The admin panel is the *anchor*;
> partner and user surfaces are unified to *match it*. Any admin-side change must be
> pixel-identical (like Phase 0's token centralization).

## TL;DR

You are **closer than it looks**. All three audiences already share the same foundation:

- **Bootstrap 5.3** everywhere (no real Tailwind — the Breeze/Tailwind bits are dead scaffolding).
- **Phosphor icons** (`ph ph-*`) as the de-facto standard across admin, partner, and user hub.
- **Dark theme** (`data-bs-theme="dark"`) and **primary `#1A98FF`** everywhere.
- Near-identical table / badge / empty-state markup — the modules were deliberately built to match.

The problem is **not the framework** — it's that the same concepts are **coded many different ways**, tokens are **copy-pasted**, and there are **legacy islands**. Unify the *vocabulary*, keep the three shells.

---

## Current state

### Three shells (KEEP — they serve different audiences)
| Audience | Shell | Chrome |
|---|---|---|
| Admin | `resources/views/layouts/app.blade.php` | Streamit sidebar + header |
| Partner | `Modules/Monetization/resources/views/layouts/partner.blade.php` | Top navbar "Creator Studio" |
| User | `resources/views/profile-hub/_layout.blade.php` (via `frontend::layouts.master`) | Sidebar rail |

Different chrome per audience is correct. We unify what goes *inside* them.

### What's fragmented (FIX)

1. **Icon sources aren't singular.** Phosphor is the standard, but **Font Awesome 6.4 is force-loaded on every admin page** (`components/partials/head/head.blade.php:55`) and unused, plus ad-hoc inline `<svg>` in `sidebar.blade.php:10` and `sub-header.blade.php:11`.
2. **Primary token `#1A98FF` is duplicated** as inline `:root` blocks in **5+ layout heads** (`app.blade.php:27`, `frontend master:22`, `jambo-auth.blade.php:30`, `auth.blade.php:15`, `guest.blade.php:23`) — no single source.
3. **Custom variants aren't centrally defined.** `btn-ghost`, `btn-*-subtle`, `bg-*-subtle text-*-emphasis` are used everywhere but not documented/owned.
4. **Inline typography.** `style="font-size:11/12/13px;letter-spacing:.5px;text-transform:uppercase"` is copy-pasted dozens of times instead of a utility class.
5. **Same concept, different code:**
   - *Stat/counts:* KPI cards (`DashboardPages/IndexPage1.blade.php:12`) vs inline dotted text (`Content …/movies/index.blade.php:11`) vs partner cards (`partner/dashboard.blade.php:17`).
   - *Page headers:* card-header flex bar vs bare `<h3>` vs 145px image banner — 3 styles.
   - *Delete confirm:* SweetAlert2 (`movies/index.blade.php:139`) vs native `confirm()` (everywhere else).
   - *Status badges:* solid `bg-success` vs subtle `bg-success-subtle`; logic as `@if` vs `@switch` vs `@class`.
6. **User area has legacy duplication:** new **profile-hub** (`jambo-hub-card`) overlaps live **PMPro pages** (`pmpro_card`/`pmpro_form`, `Modules/Frontend/.../Pages/Profile/**`) rendering the same data two different ways. Auth (`jambo-auth`) is a standalone CSS island. Dead: `dashboard.blade.php` (Tailwind), `layouts/auth.blade.php`, `layouts/guest.blade.php`.

---

## The target design system

**One token source + one icon set + one component kit + typography utilities**, consumed by all three shells.

### 1. Single token partial
Create `resources/views/components/partials/theme-tokens.blade.php` — the ONLY place `--bs-primary` etc. live. Include it in every layout head; delete the 5 duplicated `:root` blocks.
```
:root{
  --bs-primary:#1A98FF; --bs-primary-rgb:26,152,255;
  --bs-link-color:#1A98FF; --bs-link-hover-color:#147acc;
  --brand-accent:#89F425; --brand-muted:#adafb8;
  /* dark-panel scale (replaces hardcoded #0f1422/#0b0f17/#141923/#1f2738) */
  --panel-1:#0b0f17; --panel-2:#141923; --panel-3:#1f2738;
}
```

### 2. One icon set = Phosphor
- Remove the Font Awesome CDN line from the admin head (unused).
- Convert the stray `<svg>`s in `sidebar`/`sub-header` to `ph ph-*`.
- **Realign the `x-ui` kit from `bi` → `ph`** (it currently uses Bootstrap Icons; the app uses Phosphor).

### 3. One component kit — extend `resources/views/components/ui/`
Already built: `stat-card`, `card`, `page-header`, `badge`, `empty-state`, `chart`.
Add to complete coverage: `data-table` (wraps the `custom-table` + uppercase head + pagination), `filter-bar`, `status-badge` (owns the status→variant map so it's defined once), `confirm-delete` (standardize on SweetAlert2), `btn` variants doc.

### 4. Typography utilities (SCSS, defined once)
Replace repeated inline styles with classes:
```
.text-eyebrow{font-size:11px;letter-spacing:.5px;text-transform:uppercase;color:var(--bs-secondary);}
.text-meta{font-size:12px;} .text-meta-sm{font-size:11px;}
.stat-value{font-size:24px;font-weight:600;line-height:1.2;}
```

---

## Rollout (phased, low-risk)

**Phase 0 — Foundation (no visual change) — ✅ DONE 2026-07-13**
- ✅ Created `resources/views/components/partials/theme-tokens.blade.php` (single source of truth for `--bs-primary` + brand/panel tokens). Included in all 7 layouts (`app`, `auth`, `guest`, `jambo-auth`, partner, frontend `master` + `blank`); deleted every duplicated `:root` block.
- ✅ Added typography utilities (`.text-eyebrow`, `.text-meta`, `.text-body-sm`, `.stat-value`) as plain CSS in the same partial — live immediately, no build step.
- ✅ Realigned `x-ui` kit + demo + docs from Bootstrap Icons (`bi`) to Phosphor (`ph`).
- ✅ Verified on live app: primary still `#1A98FF`, no Blade error, new tokens + utilities resolve.

**Phase 1 — Icons**
- Drop Font Awesome from admin head; convert stray SVGs. Verify no `fa fa-` remains outside the theme demo pages.

**Phase 2 — Admin vocabulary**
- Introduce `<x-ui.page-header>`, `<x-ui.status-badge>`, `<x-ui.data-table>`, `<x-ui.confirm-delete>` into the module index/form pages (Content, Payments, Subscriptions, Pages, Monetization, Seo). One module at a time; each is a visible before/after.
- Standardize delete-confirm on SweetAlert2.

**Phase 3 — Partner**
- Swap partner dashboard's hand-rolled KPI cards for `<x-ui.stat-card>`; adopt `<x-ui.page-header>`, `<x-ui.data-table>`. Keep the top-navbar shell.

**Phase 4 — User account**
- Decide the source of truth: profile-hub (recommended) vs PMPro. Migrate/redirect the legacy PMPro profile pages into the hub. Adopt `x-ui` cards/badges/empty-states in the hub.
- Optional: align auth (`jambo-auth`) inputs to the shared field styling; retire dead `dashboard.blade.php` / `layouts/auth` / `layouts/guest`.

**Phase 5 — Cleanup**
- Remove dead Tailwind config/deps if truly unused; de-duplicate eager asset loads (Swiper/Select2/Flatpickr loaded twice; DataTables CSS bundled but unused).

---

## Reference: full audit findings
See the three area audits captured in this session (admin / partner / user) for exhaustive file:line citations behind each point above.
