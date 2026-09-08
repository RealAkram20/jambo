# API v1 coverage — what the app can do, and what it cannot

**Updated:** 2026-09-08 (repo v1.8.27)
**Purpose:** the API is the only connection between the webapp and the mobile
app. Anything a viewer can do on jambofilms.com that has no endpoint here is a
screen the app cannot build.

Derived from the router, not from memory: the audit enumerates every
viewer-facing web route and every `/api/v1` route and pairs them. Admin,
partner and Streamit template demo routes (`dashboard.*`, `backend.*`) are out
of scope — the app is a viewer client, and ADR-0004's Play build is
consumption-only.

**Status today: 34 endpoints shipped, 8 capability areas still open.**

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

---

## Open — in priority order

Priority is *what stops the app being usable*, not what is hardest.

### 1. Account creation and recovery — BLOCKING

Without these an app can only be used by someone who already has an account
and remembers the password. On the Play build, where ADR-0004 forbids sending
people to a payment page, it also means a new viewer cannot start at all.

| Missing | Website route | Note |
|---|---|---|
| Register | `POST /register` | Carries a honeypot, optional reCAPTCHA, `SignupAttempt` logging, referral-cookie attribution, and a username that doubles as a referral code. **None of it ports to a native form unchanged — this needs decisions, not just code.** |
| Forgot password | `POST /forgot-password` | Sends a reset link. The link lands on the website; the app can hand off to a browser. |
| Reset password | `POST /reset-password` | Only needed if the app handles the deep link itself. |
| Change password | `PUT /password` | Signed-in password change. |
| Google / social sign-in | `auth.social` | Streamit ships Socialite. Needs a native Google flow and an ID-token exchange endpoint, not the web redirect. |
| Email verification resend | `verification.send` | `MustVerifyEmail` is on the User model. |

### 2. Profile and account — BLOCKING for a complete app

| Missing | Website route |
|---|---|
| View profile | `profile.show` |
| Edit profile | `profile.update` |
| Avatar upload / remove | `profile.avatar.upload`, `profile.avatar.destroy` |
| Security page (2FA enable/disable/recovery codes) | `account.security`, `two-factor.*` |
| Deactivate account | `account.deactivate` |

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

### 5. Concurrency across web and app

The plan (§4.2) wants one cap over browser sessions *and* app devices. Today
`GET /devices` lists app installs only, and an app viewer who hits the cap has
no way to free a slot held by a browser.

| Missing | Website route |
|---|---|
| List browser sessions | `profile.devices` |
| Boot a browser session | `profile.devices.destroy`, `streams.boot` |
| Sign out everywhere else | `profile.devices.logout-others` |
| Reclaim a booted stream | `streams.reclaim` |

`EnforceDeviceLimit` also still counts sessions only — the `devices` table
exists but nothing counts it.

### 6. Catalogue completeness

Reachable on the website, not yet in the API.

| Missing | Website route |
|---|---|
| Search suggestions (type-ahead) | `frontend.search.suggest` |
| Tag pages | `frontend.tag`, `frontend.view-all-tags` |
| Rail archive ("see all" on a home rail) | `frontend.rail_archive` (`/collection/{rail}`) |
| VJ's movies / series, and genre filters within a VJ | `frontend.vj_movie_detail`, `frontend.vj_series_detail`, `frontend.vj_*_genre` |
| Cast list / all personalities | `frontend.cast_list`, `frontend.all_personality` |
| Guest view counter | `streaming.guest-view` |

### 7. Static pages

An app with no About or Terms screen fails Play review for a subscription app.

| Missing | Website route |
|---|---|
| About, FAQ, Privacy, Terms | `frontend.about_us`, `frontend.faq_page`, `frontend.privacy-policy`, `frontend.terms-and-policy` |
| Contact form | `frontend.contact_us.submit` |

Pages are CMS-backed (`Modules/Pages`), so one generic
`GET /pages/{slug}` covers all of them.

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
