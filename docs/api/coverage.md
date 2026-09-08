# API v1 coverage — what the app can do, and what it cannot

**Updated:** 2026-09-08 (repo v1.8.31)
**Purpose:** the API is the only connection between the webapp and the mobile
app. Anything a viewer can do on jambofilms.com that has no endpoint here is a
screen the app cannot build.

Derived from the router, not from memory: the audit enumerates every
viewer-facing web route and every `/api/v1` route and pairs them. Admin,
partner and Streamit template demo routes (`dashboard.*`, `backend.*`) are out
of scope — the app is a viewer client, and ADR-0004's Play build is
consumption-only.

**Status today: 63 endpoints shipped, 3 capability areas still open.**

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
| Cast grid | `frontend.cast_list`, `frontend.all_personality` | `GET /cast` | 1.8.30 |
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

### 3. Notifications — BLOCKING for push

Push is the whole reason an OTT app gets reopened. The `background-push`
standard applies here.

| Missing | Website route |
|---|---|
| List notifications | `notifications.index` |
| Mark read / mark all read | `notifications.read`, `notifications.mark-all-read` |
| Delete / delete all | `notifications.destroy`, `notifications.destroy-all` |
| Preferences | `profile.notifications.prefs` |
| Push subscribe / unsubscribe | `notifications.push.subscribe` / `unsubscribe` — **web-push shaped; the app needs FCM tokens instead** |

### 4. Subscription and billing

ADR-0004 splits this by build variant. The `play` build shows status only; the
`direct` build carries PesaPal checkout.

| Missing | Website route | Which build |
|---|---|---|
| Plans / pricing | `frontend.pricing-page` | both (informational on `play`) |
| Subscription status | `profile.membership` | both — partially in `GET /me` |
| Start checkout | `payment.create-order` | `direct` only |
| Poll order status | `payment.status` | `direct` only |
| Billing history / invoice | `profile.billing`, `profile.invoice` | both |

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

### 8. Referrals and wallet

| Missing | Website route |
|---|---|
| Refer and earn, referral code | `profile.refer`, `profile.refer.code` |
| Apply / check a code | `referrals.apply-code`, `referrals.check-code` |
| Wallet, subscribe from wallet, withdraw | `profile.wallet`, `referrals.wallet.*` |

---

## Decisions still needed

**Standalone star ratings.** `ratings` rows are only ever written by
`InteractionSeeder`. The website has no viewer-facing way to rate a title — the
table is read for star averages and moderated in admin, and a viewer's stars
reach it through a Review. `POST /ratings` appears in the plan's §5, but
building it would add a product feature the website does not have. **Not built.
Rio to decide** whether the app gets tap-a-star rating, and if so whether the
website gets it too.

**Push transport.** The website uses web-push subscriptions. The app needs FCM.
These are different enough that `notifications.push.subscribe` cannot simply be
reused; see the `background-push` standard.

**TV sign-in.** Device-code (RFC 8628) plus a `/tv` page on the website. Listed
in Phase 1 but grouped with the TV work, since nobody types a password with a
remote.

---

## How this file stays true

`tests/Feature/Api/V1/OpenApiSpecTest.php` fails when a route exists that
`docs/api/openapi.yaml` does not describe, so the spec cannot drift from the
code. This file is the layer above that: it tracks what has no route *at all*.
Re-run the audit whenever a section is closed.
