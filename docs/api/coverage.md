# API v1 coverage — what the app can do, and what it cannot

**Updated:** 2026-09-08 (repo v1.8.34, after the review pass)
**Purpose:** the API is the only connection between the webapp and the mobile
app. Anything a viewer can do on jambofilms.com that has no endpoint here is a
screen the app cannot build.

Derived from the router, not from memory: the audit enumerates every
viewer-facing web route and every `/api/v1` route and pairs them. Admin,
partner and Streamit template demo routes (`dashboard.*`, `backend.*`) are out
of scope — the app is a viewer client, and ADR-0004's Play build is
consumption-only.

**Status today: 75 endpoints shipped (counted from the router on the `api.v1.` route name; an earlier figure of 85 came from a grep whose unescaped dot also matched `api/v1/` and swept in the website's own AJAX routes). All eight audited capability areas are
addressed; what remains is named below and is deliberate, not forgotten.**

---

## Shipped

| Viewer capability | Website | API | Since |
|---|---|---|---|
| Home screen, all rails | `frontend.ott` | `GET /home` | 1.8.25 |
| Browse movies | `frontend.movie` | `GET /movies` | 1.8.24 |
| Movie detail | `frontend.movie_detail` | `GET /movies/{slug}` | 1.8.24 |
| Browse series | `frontend.series` | `GET /series` | 1.8.24 |
| Series detail + seasons + episodes | `frontend.series_detail` | `GET /series/{slug}` | 1.8.24 |
| Episode detail | `frontend.episode` | `GET /episodes/{id}` | 1.8.24 |
| Search | `frontend.search` | `GET /search` | 1.8.24 |
| Genres | `frontend.all-genres`, `frontend.genres` | `GET /genres`, `/genres/{slug}` | 1.8.26 |
| Categories | `frontend.all-categories`, `frontend.category` | `GET /categories`, `/categories/{slug}` | 1.8.26 |
| VJs | `frontend.vj_detail` | `GET /vjs`, `/vjs/{slug}` | 1.8.26 |
| Cast | `frontend.cast_details` | `GET /cast/{slug}` | 1.8.26 |
| Play a title | `stream.src.*` + `tier_gate` | `POST /playback/sessions` | 1.8.24 |
| Keep a session alive | `streaming.heartbeat` | `POST /playback/heartbeat` | 1.8.24 |
| Watchlist | `frontend.watchlist_*` | `GET/POST/DELETE /watchlist` | 1.8.26 |
| Continue watching | home rail | `GET /continue-watching` | 1.8.26 |
| History | profile hub | `GET /history` | 1.8.26 |
| Reviews | `frontend.movie_review_store` etc. | `GET/POST/DELETE /{type}/{slug}/reviews` | 1.8.27 |
| Comments | `frontend.episode_comment_store` | `GET/POST /episodes/{id}/comments`, `DELETE /comments/{id}` | 1.8.27 |
| Sign in | `login` | `POST /auth/login` | 1.8.23 |
| Two-factor | `two-factor.challenge` | `POST /auth/2fa/challenge` | 1.8.23 |
| Sign out | `logout` | `POST /auth/logout` | 1.8.23 |
| Who am I, plan, caps | `profile.membership` | `GET /me` | 1.8.23 |
| App devices | — (new) | `GET /devices`, `DELETE /devices/{uuid}` | 1.8.23 |
| Client config, server clock | — (new) | `GET /app/config` | 1.8.23 |
| Register | `POST /register` | `POST /auth/register` | 1.8.28 |
| Google sign-in | `auth.social` | `POST /auth/google` | 1.8.28 |
| Forgot / reset password | `password.email`, `password.store` | `POST /auth/forgot-password`, `/auth/reset-password` | 1.8.28 |
| Change password | `password.update` | `PUT /auth/password` | 1.8.28 |
| Email verification | `verification.send` | `GET /auth/email`, `POST /auth/email/resend` | 1.8.28 |
| View / edit profile | `profile.show`, `profile.update` | `GET/PATCH /profile` | 1.8.29 |
| Avatar | `profile.avatar.*` | `POST/DELETE /profile/avatar` | 1.8.29 |
| Two-factor management | `account.security`, `two-factor.*` | `GET /account/security`, `POST/DELETE /account/2fa`, `/account/2fa/confirm`, `/account/2fa/recovery-codes` | 1.8.29 |
| Deactivate account | `account.deactivate` | `DELETE /account` | 1.8.29 |
| Search suggestions | `frontend.search.suggest` | `GET /search/suggest` | 1.8.30 |
| Tags | `frontend.tag`, `frontend.view-all-tags` | `GET /tags`, `/tags/{slug}` | 1.8.30 |
| Cast grid | `frontend.cast_list` | `GET /cast` | 1.8.30 |
| Rail archives ("see all") | `frontend.rail_archive` | `GET /collections`, `/collections/{rail}` | 1.8.30 |
| About / FAQ / Privacy / Terms | `frontend.about_us` etc. | `GET /pages`, `/pages/{slug}` | 1.8.30 |

---

## Open — in priority order

Priority is *what stops the app being usable*, not what is hardest.

### 1. Account creation and recovery — ✅ CLOSED in 1.8.28

Shipped 2026-09-08. Register, Google sign-in, forgot/reset password, change
password, and verification status and resend. Registration reuses the
website's validation, default role, `Registered` event and `SignupAttempt`
logging; the honeypot and reCAPTCHA deliberately do not port (see the
CHANGELOG). Google verifies the ID token with Google and checks the audience.

**Still open here:** the reset link lands on the website rather than deep-
linking into the app, and Play Integrity has not been considered as the
replacement for reCAPTCHA on the signup route.

### 2. Profile and account — ✅ CLOSED in 1.8.29

Shipped 2026-09-08. Profile read and edit (email changes cost the current
password and clear verification), avatar upload and removal, the full
two-factor lifecycle as three calls, and account deactivation which signs out
every device.

### 3. Notifications — CLOSED in 1.8.32 (server side)

`GET /notifications` with cursor paging and an unread count, mark-read /
read-all, delete / delete-all, per-category preferences, and FCM token
register / unregister.

The token is stored on the `devices` row, so **signing out, being booted, or a
password reset all clear it** — the shared-handset rule from the push standard.

**Delivery does not exist yet.** This is the registry and the polled fallback,
not a sender. Nothing has been pushed to a real device, and the standard's
device checklist (backgrounded, killed, locked, Android 13+ permission,
Android 14+ full-screen intent, fallback poll alone) is Phase 2 work with a
handset in hand.

### 4. Subscription and billing — CLOSED (read) in 1.8.33

`GET /plans` (public), `GET /subscription`, `GET /subscription/orders` and
`/orders/{reference}`.

**In-app checkout is deliberately NOT built.**
`PaymentController::createOrder` carries server-authored pricing, a frozen
price snapshot, a server-computed referral discount, and a guard against
pairing an arbitrary amount with a `SubscriptionTier` payable. Reusing it
safely means extracting it into a shared service — a money-code slice of its
own. The Play build needs no checkout at all under ADR-0004; only the direct
APK does.

### 5. Concurrency across web and app — ✅ CLOSED in 1.8.31

`GET /devices` lists browser sessions beside app installs and
`DELETE /devices/{id}` boots either, so a viewer can free a slot held by a
laptop from their phone. `EnforceDeviceLimit` counts through
`AccountDeviceRegistry`.

⚠️ **One cap over both is built but switched OFF.**
`streams.count_app_devices` defaults to false, because turning it on tightens
the live site for anyone with both a browser and the app. **Rio's call, once
the app has shipped.**

### 6. Catalogue completeness — ✅ MOSTLY CLOSED in 1.8.30

Shipped: search suggestions (and `/search` now matches the website's
title-or-synopsis ranking), tags, the cast grid, and rail archives from a
`RailArchiveCatalog` the website shares.

**Still open here:** the VJ sub-pages (`vj-movie/{slug}`, `vj-series/{slug}`
and their genre filters) and the guest view counter
(`streaming.guest-view`). A VJ's titles are already on `GET /vjs/{slug}`;
what is missing is the genre-filtered slice within one VJ.

### 7. Static pages — ✅ CLOSED in 1.8.30

`GET /pages` and `GET /pages/{slug}`, CMS-backed, so a page added in the admin
reaches the app with no release.

### 8. Referrals and wallet — CLOSED (read) in 1.8.33

`GET /referrals`, `PUT /referrals/code`, `GET /wallet`.

**Withdrawal is deliberately NOT built** — money leaving the business earns its
own slice.

---

## What is still genuinely open

Everything below is a decision or a piece of work that was named rather than
missed.

| Open | Why it is open |
|---|---|
| In-app checkout (`direct` build) | Needs money code extracted into a shared service. See gap 4. |
| Wallet withdrawal | Money leaving the business. See gap 8. |
| Standalone star ratings | The website has no viewer-facing way to rate a title; building one is a product decision. |
| VJ sub-pages, guest view counter | Small catalogue leftovers. See gap 6. |
| TV device-code sign-in | Needs a `/tv` page on the website; grouped with the TV work. |
| Push *delivery* | The registry and the polled fallback exist; no sender, and nothing tested on a real handset. |
| `streams.count_app_devices` | Built and switched OFF. Turning it on tightens the live cap for anyone with both a browser and the app. |
| Reset-password deep link | The reset link lands on the website rather than in the app. |
| Play Integrity on signup | The replacement for the reCAPTCHA that does not port to native. |
