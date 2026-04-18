# Jambo — Final Polish

Closeout punch list for pre-launch polish. Replaces the deleted
`frontend-stitch-plan.md`. This is a living checklist — strike items as
they ship.

**Last updated:** 2026-04-19

---

## A. Route & method cleanup — SHIPPED 2026-04-19

Deleted orphan/stub routes from `Modules/Frontend/routes/web.php` and
the matching controller methods + blade files:

- `/download` — removed. **Product decision: no user-facing movie
  downloads.** The "+" watchlist button covers the "save for later"
  use case instead.
- `/movie-player` — removed. Real player lives at `/watch/{slug}`.
- `/view-more` — removed. Stub with `href="view-all"` dead links.
- `/resticted` — removed. Tier-gate middleware returns a real response.
- `/person-detail` — removed. Cast details live at `/cast-details/{slug}`.
- `/profile-marvin` — removed. Profile hub lives at
  `/{username}` (profile.show).

Also purged: `Pages/BlogPaginationStyle/` (no blog exists),
`Pages/profile-page.blade.php`, `Pages/notifications-page.blade.php`,
orphan widgets `download-modal.blade.php` and `quick-view.blade.php`,
and the dangling `NotificationController::index` split that pointed to
a never-created frontend notifications blade.

`ReservedUsername.php` trimmed to match the current route surface.

---

## B. Phase 6c — Push notifications (not started)

The only real unshipped feature from the original plan.

- [ ] Add `laravel-notification-channels/webpush` to `composer.json`
- [ ] VAPID keys in `.env` + `config/webpush.php`
- [ ] Service worker at `public/sw.js`
- [ ] Opt-in UI in the profile's Security/Notifications tab
- [ ] Register the `webpush` channel in the three existing notification
      classes (`PaymentReceived`, `TestNotification`,
      `SubscriptionActivated`) via `via()`

Do NOT reuse the removed `push_notifications_enabled` flow — that
column is already on `users`. Just wire it up.

---

## C. Doc refresh (low priority, do opportunistically)

Three docs still describe state from several sessions ago. Not
actively harmful, but newcomers get the wrong mental model:

- [ ] `docs/frontend-guide.md` — still lists 21 blog pages, `/download`,
      `/movie-player`, `/profile-marvin`, `/playlist`. Rewrite or
      delete; the real route surface is `Modules/Frontend/routes/web.php`.
- [ ] `docs/admin-panel-guide.md` — still flags "duplicate Movies/Shows/
      Persons sidebar entries" that were unified ages ago.
- [ ] `docs/modules.md` — labels Content/Subscriptions/Payments/
      Streaming as "empty skeleton" — all four are shipped.
- [ ] `docs/SESSION-RESUME.md` — lists Phase 4b/4c/4d as "next"; all
      three shipped. Bump "Last session" date and truncate the
      "what's next" section.
- [ ] `docs/plans/frontend-wiring.md` — Phase 0 followups section
      still lists the `ProfileController` stub (resolved) and the
      shop lang keys (see §D).

---

## D. Phase 0 technical debt (small, grab when idle)

- [ ] Remove stale `shop`/`cart_page`/`wishlist_page` keys from
      `lang/en/frontendheader.php` — shop was ripped out in `3c867c4`.
- [ ] Audit `BackendController` for stale references to non-existent
      `Modules\Booking` / `Modules\Product`.
- [ ] Seed `settings.app_name = 'Jambo'` so `setting('app_name')`
      returns a real value instead of falling through to
      `config('app.name')`.
- [ ] Wire the error pages (`/error-page1`, `/error-page2`) to
      Laravel's exception handler so 404/500 responses actually render
      the themed pages. Currently they're just browsable URLs.

---

## Non-goals — do NOT do these

- **Don't reintroduce `/download`.** Product has explicitly ruled out
  local downloads.
- **Don't reintroduce playlists or the language switcher.** Both were
  deliberately removed — re-adding them means reviving infrastructure
  (middleware, RTL SCSS, model, etc.), not just a route.
- **Don't touch the card/section component library.** Every page uses
  `components/cards/card-style.blade.php` and the 20+ section blades —
  changes there ripple across the site. Fix data, not markup.
- **Don't add new CSS.** The Streamit design system is set; use
  existing utility classes. See `docs/ui-guidelines.md`.
