# Release Notes

## Jambo

### 1.8.34 — The review pass: four defects the suite could not see

Rio asked to look at what had just been finished. So every one of the 85
endpoints was walked against the real dev database rather than the test
suite, and the diff was read for the sharp edges. Four defects came out, and
the pattern behind all four is the same: each was invisible to a suite that
fakes mail, seeds one row at a time, and runs on SQLite.

**A cursor on a tied column drops every row that ties.** `GET
/collections/latest-movies` returned an empty second page on a database with
twenty movies in it. Seeded and bulk-imported titles share a `created_at` to
the second, and a cursor on that column alone skips everything that ties. The
same shape sat under reviews, comments, history, orders, the ledger and
notifications — every list paged on `latest()`. Each now carries `id` as a
tiebreaker, and there is a test that inserts thirty-one rows with an identical
timestamp and pages through all of them. Collections and the cast grid, whose
primary order is a raw expression a cursor cannot be built from at all, page
by offset now — exactly as the website does.

**An unhandled exception left the envelope.** The handler rendered validation,
authentication and 404 into the contract's shape and let everything else fall
through to Laravel's default 500 — not enveloped, and in debug mode carrying
the exception class and message. `SERVER_ERROR` was in the enum and nothing
emitted it. A catch-all now does, hiding the message outside debug, and a
deliberate `abort(401|403|429)` keeps its own status with the matching code.

**A mail outage turned forgot-password into an account-enumeration oracle.**
The endpoint promises an identical answer whether or not the address has an
account — but a fake address never touches mail, and a real one tries to send.
With the transport down, the real one threw and the fake one did not, so a
500 meant "this email exists" to anyone probing. The send is now wrapped, the
failure logged for ops with a hashed address, and the viewer sees the same
sentence either way. Found because the dev box points `MAIL_HOST` at a Docker
hostname that does not exist here; the website's own form has the same shape.

**Rate limits were keyed on the address for guests, and for Google sign-in.**
Behind carrier NAT that is one bucket per cell tower, and the type-ahead sends
a request per keystroke. The `api` limiter now keys on the viewer, else the
`X-Device-Id` header, else the address — with the address kept as a loose
outer layer at ten times the rate. The `auth` limiter falls through to the
device uuid before the address, which Google sign-in was missing because it
carries neither an email nor a challenge token. **The app must send
`X-Device-Id` on every request**, and the spec now says so at the top.

**Verified.** 544 tests, 2049 assertions; the same two pre-existing
`PricingPageCurrentPlanTest` failures. All 75 smoke calls against the real
database answer in the envelope with the status the contract says — up from
74 before this pass. The homepage still diffs clean against its pre-refactor
fingerprint.

**Not verified.** The pinned rails still order with MySQL's `FIELD()` and
still cannot execute under the SQLite suite; they were confirmed paging
correctly on real MariaDB. No real handset has sent `X-Device-Id` yet.

### 1.8.33 — Plans, billing, refer and earn — and the two things left out on purpose

Gaps 4 and 8 from [the coverage audit](docs/api/coverage.md), which closes the
audit's list. Plans, the viewer's current plan, payment history, refer and
earn, and the wallet.

**Read-only, and that is the design rather than a shortcut.** ADR-0004 makes
the Google Play build consumption-only: it shows a plan and a renewal date and
sells nothing, because Play's Payments policy requires Play Billing for
subscription content in Uganda and consumption-only is the documented way to
stay compliant. So `GET /plans` is public — the Play build must be able to
price a plan it cannot sell — and everything else here serves both builds.

**In-app checkout is deliberately not built.**
`PaymentController::createOrder` holds real money logic: the price is copied
off the tier server-side so a client cannot name its own, the amount is frozen
in a snapshot so an admin editing a price mid-checkout cannot void a genuine
payment, a referral discount is computed and recorded server-side, and it
explicitly refuses a client pairing an arbitrary amount with a
`SubscriptionTier` payable — the attack that would otherwise buy Premium for
one shilling. Reusing that safely means extracting it into a service both
callers share. Money code earns its own careful slice; it does not get tacked
onto the end of another one.

**Withdrawal is out for the same reason.** Money leaving the business is not
something to add at the end of a long session.

One rule worth naming because it looks like a bug otherwise: refer-and-earn is
a 404 while the programme is switched off — *except* for a viewer who already
has wallet history. Money someone earned must not become unreachable because
an admin flipped a setting. That is the website's rule, and it is now the
API's.

The Referrals module had web routes only and gained the API mapping the other
modules already had — the second module today to be missing one.

**Verified.** 533 tests, 1945 assertions; the same two pre-existing
`PricingPageCurrentPlanTest` failures. The homepage still diffs clean against
its pre-refactor fingerprint.

### 1.8.32 — Notifications, and a push registry that clears itself

Gap 3 from [the coverage audit](docs/api/coverage.md): the notification list,
its preferences, and the FCM token registry the app registers against.

**The list is the point, not the push.** Push is an accelerator, never the
transport — an OEM battery manager kills the process, a token goes stale, a
handset sits offline for a day. So everything a push would say is readable from
`GET /notifications`, and the app has to be usable with push switched off
entirely. That is rule 3 of the house push standard, and it decides whether a
missed notification is an inconvenience or a lost viewer.

**The FCM token lives on the device row, and that is the whole design.** The
standard's first rule is a registry that is per user, per install, idempotent,
and *deleted on sign-out* — because a shared handset that keeps the previous
account's token delivers their notifications, on the lock screen, to whoever is
holding the phone. `devices` is already exactly one row per (account, install),
and every path that ends a session — logout, being booted from another device,
a password reset — already runs through `Device::revoke()`. Hanging the token
there means all of them clear it without anyone having to remember to. There is
a test for each.

A token can also belong to only one install: registering one already held
elsewhere moves it. Android reissues the same token to a reinstall, and two
rows holding it would mean one push delivered twice and a delivery receipt that
cannot be matched back to a handset.

Registration logs *before* its guards, deliberately. "The app never registered"
and "it registered and there was no device row" otherwise leave identical
evidence — nothing — and need completely different fixes. That is the most
expensive lesson in the standard: KangaruRide dispatched thirty-eight job
offers to nobody because a token table was empty and no code was documented as
having anything to say about it. The log records the device uuid, whether a row
existed, and the last eight characters of the token — never the whole thing,
which is a credential for reaching a handset.

`push_subscriptions` is untouched. That table is web-push shaped and belongs to
the browser.

**Verified.** 522 tests, 1889 assertions; the same two pre-existing
`PricingPageCurrentPlanTest` failures.

**Not verified, and it matters.** Nothing has been sent to a real device. No
sender exists yet — this is the registry and the fallback list, not delivery.
The standard's device checklist (backgrounded, killed, locked, Android 13+
permission, Android 14+ full-screen intent, and the fallback poll alone) is
Phase 2 work with a real handset, and none of it can be claimed from here.

### 1.8.31 — One account, one device list

Gap 5 from [the coverage audit](docs/api/coverage.md), and the problem it
closes is concrete. A viewer hits their stream cap because a laptop at home is
still signed in. They are holding their phone. Until now the app could list and
boot app installs only, so there was nothing they could do from where they were
standing.

`GET /devices` now returns browser sessions beside app installs, and
`DELETE /devices/{id}` accepts either — a device uuid or a session id. Booting
a browser deletes its session row, which is exactly what the website's own
picker does.

**The cap change ships switched off.** `EnforceDeviceLimit` now counts through
`AccountDeviceRegistry`, which can include app installs in the same cap the
plan asks for (§4.2). But `streams.count_app_devices` defaults to false,
because turning it on tightens the live site: anyone who has both a browser and
the app would start meeting the device picker where they did not before. That
is a product decision to make once the app has shipped and the numbers are
visible, not something to switch on by deploying. The setting is read through a
guarded helper that falls back to the current behaviour if the settings table
ever misbehaves — playback must not break because a lookup did.

One contract change worth naming: the device list's `uuid` field is now `id`,
and every row carries a `kind` of `app` or `browser`. A session has an id where
a device has a uuid, and one field is what lets `DELETE /devices/{id}` take
either. Nothing has shipped against the old shape.

**Verified.** 508 tests, 1821 assertions; the same two pre-existing
`PricingPageCurrentPlanTest` failures. The homepage still diffs clean, and the
default-off behaviour is asserted in both directions — with the setting off
only browser sessions count, with it on both do.

### 1.8.30 — The rest of the catalogue, and the pages Play requires

Gaps 6 and 7 from [the coverage audit](docs/api/coverage.md): search
suggestions, tags, the cast grid, "see all" rail archives, and the CMS pages.

**Search now matches the website.** The API had been doing a plain title LIKE
while the site searched title *or* synopsis and ranked by how well the title
matched — an exact title first, then one that starts with the term, then one
that contains it, then synopsis-only matches. Searching "Queen" put the wrong
film first in the app. Same ranking on both now, with a thinner
`/search/suggest` for type-ahead, because a viewer sends that on every
keystroke over a mobile connection.

**"See all" comes from one catalog.** `FrontendController::railArchives()` — a
private map of eight rails with their queries, orderings and pinned items —
moved into `RailArchiveCatalog`, which the website's `/collection/{rail}` page
and the new `GET /api/v1/collections/{rail}` both call. A second copy would
have meant the app's "see all" quietly showing a different list than the
site's. Pinned first, green after.

**Something that pin turned up.** The three personalised rails order with
MySQL's `FIELD()`, which MariaDB has and SQLite does not — so those rails
cannot execute under the test suite and never have been covered. Production is
MariaDB, so this is a testing limitation rather than a live defect, and it is
now written down in the service, the test and the API docs rather than looking
like an oversight. Rewriting to portable SQL would change a live ranking and is
a decision, not a cleanup. The web archives were verified rendering against
real MariaDB, `top-picks` included.

**The CMS pages are one endpoint, not four.** About, FAQ, Privacy and Terms are
rows an admin edits, so `GET /pages/{slug}` serves whatever exists and a new
page needs no app release. That matters for more than tidiness: Google Play
requires a subscription app to show its terms and privacy policy, and an app
that hard-codes them drifts from the site the moment somebody edits one. The
Pages module had web routes only and gained the API mapping every other module
already had. Structural rows — the footer, anything flagged system — are chrome
the website assembles, so they stay out of the index.

**Verified.** 501 tests, 1797 assertions; the same two pre-existing
`PricingPageCurrentPlanTest` failures. The homepage still diffs clean against
its pre-refactor fingerprint, and `/collection/latest-movies`,
`/collection/top-picks` and `/about-us` all render on real data.

### 1.8.29 — The profile and security screens

Gap 2 from [the coverage audit](docs/api/coverage.md): view and edit a
profile, change the photo, manage two-factor, and deactivate an account.

**The rules that protect an account are the website's, not softer ones.**
Changing the account email costs the current password and clears the verified
flag — email is the recovery anchor, so with a stolen token an attacker could
swap it and then reset their way into a full takeover. Turning two-factor off
costs the password too: the website puts that behind password-confirmation
middleware, which is a session idea, and the token equivalent is asking here.
Removing a second factor must cost more than holding an unlocked handset.

**Two-factor setup is three calls, on purpose.** Start mints a pending secret
and recovery codes, confirm proves the viewer's authenticator actually has it,
and only then is it on. A one-shot enable would let someone lock themselves out
of their own account with a mis-scanned code. The app renders its own QR from
the secret rather than being handed a server-rendered SVG, which is a web
answer to a native question.

Deactivating signs out every device. A deactivated account is one sign-in
refuses, so leaving live tokens behind would let the app keep watching on it
until somebody noticed.

**A bug the tests found.** `phone` is optional, and Laravel's validator omits
an absent nullable key entirely — so an app sending only the fields it changed
hit an undefined index. The website never triggers it because its form always
posts the input. The same latent shape exists in `ProfileHubController` and was
deliberately left alone: it cannot fire from a browser form, and this release
is not the place to touch live profile code.

**Verified.** 482 tests, 1717 assertions; the same two pre-existing
`PricingPageCurrentPlanTest` failures. Additive — no webapp file modified.

### 1.8.28 — The app can create an account and recover one

Gap 1 from [the coverage audit](docs/api/coverage.md), and the one that
mattered most: until now the app only worked for someone who already had an
account and remembered the password. On the consumption-only Play build, where
ADR-0004 forbids sending people to a payment page, a new viewer could not start
at all.

Ten endpoints: register, Google sign-in, forgot password, reset password,
change password, and email verification status and resend.

**Registration is the website's rules, and says plainly what does not port.**
Same validation, same default role, same `Registered` event, same
`SignupAttempt` logging, so an account made in the app is indistinguishable
from one made in a browser. But the website's honeypot is a hidden form field
that only works because a bot is scraping a DOM, and reCAPTCHA v3 is a browser
token an Android app cannot produce. Neither is faked here. The route's
throttle — keyed on email plus IP, so carrier NAT does not make one bucket per
cell tower — and the signup log carry that weight instead. If app signup is
ever abused, Play Integrity is the answer, not a honeypot.

**Google sign-in verifies the token rather than trusting it.** The website
redirects through OAuth; a native app comes back holding an ID token, so the
server checks it with Google and — the part that matters — checks the audience
matches this app's client id. A valid Google token issued to somebody else's
app is still a valid Google token. Google being unreachable answers "could not
verify" rather than allowing the sign-in, because the destructive branch here
is granting access.

**One live-site extraction, and why it was worth it.** The social callback
carries an account-takeover mitigation: someone can register victim@gmail.com
locally with a password they chose and never verify it, and when the real owner
later signs in with Google — which proves the mailbox — that squatter's
password must stop working. Copying four lines of that into the API would have
put security logic in two places. It moved into `SocialAccountResolver`
instead, with a six-test pin written and green against the old controller
first, and green after. There had been no tests on that path at all.

Two smaller extractions keep the sign-in paths honest: `DeviceTokenIssuer` (one
live token per install, superseded tokens deleted — Sanctum's expiry is null
here, so anything left behind works forever) and `TwoFactorChallengeStore`,
because Google proves the mailbox and not possession of the authenticator, so
both sign-in paths must land on the same challenge.

Resetting a password signs out every device; changing one signs out every
*other* device, so the viewer is not thrown out of the app they are standing
in.

**Verified.** 468 tests, 1644 assertions; the same two pre-existing
`PricingPageCurrentPlanTest` failures. The homepage still diffs clean against
its pre-refactor fingerprint, and /login, /register, /forgot-password and
/pricing all render.

**Not verified.** Google sign-in was exercised against a faked HTTP client,
never against real Google, and `GOOGLE_CLIENT_ID` is not set locally. Before
release, confirm the Android client id the app ships matches
`services.google.client_id` — every real sign-in fails the audience check
otherwise.

### 1.8.27 — Reviews, comments, and an honest list of what the API still cannot do

Reviews on movies and series, comments on episodes, and — because Rio asked
whether the API is finished — a coverage audit derived from the router rather
than from memory.

**Reviews and comments follow the website's rules, not new ones.** One review
per viewer per title via `updateOrCreate`, so posting again edits rather than
duplicating and a retry over a dropped connection is safe. `NewReviewPosted`
fires only on a genuinely new row, so editing does not ping an admin twice.
Comments keep the approved-only filter and now nest their replies, which the
data model always supported and the website has not rendered yet.

Two things the API adds because a public thread should not leak: a reply whose
`parent_id` belongs to a different episode is refused rather than silently
grafted onto the wrong thread, and someone else's comment answers NOT_FOUND
rather than 403, so the delete endpoint cannot be used to confirm a comment
exists. Author blocks carry a username and a display name and nothing else —
never an email.

The public read endpoints run `api.viewer` rather than `auth:sanctum`, so a
signed-in app gets `mine` on a review thread and `is_mine` on a comment while a
guest still gets the thread. That is the same trap `/home` hit in 1.8.25: a
public route resolves no bearer token on its own.

**[docs/api/coverage.md](docs/api/coverage.md) is new, and it is the honest
answer.** 34 endpoints are shipped and **eight capability areas are still
open** — account creation and recovery, profile, notifications, subscription
and billing, cross-device concurrency, some catalogue completeness, static
pages, and referrals. Each gap names the website route it corresponds to. The
API is the only connection between the webapp and the app, so a capability with
no endpoint is a screen the app cannot build.

**One thing deliberately not built.** `ratings` rows are only ever written by
`InteractionSeeder`. The website has no viewer-facing way to rate a title — the
table is read for star averages and moderated in admin, and a viewer's stars
reach it through a review. Building `POST /ratings` would add a product feature
the website does not have, so it is left for Rio to rule on.

**Verified.** 446 tests, 1565 assertions; the same two pre-existing
`PricingPageCurrentPlanTest` failures. Entirely additive — no webapp file was
modified.

### 1.8.26 — Taxonomy pages and the watchlist, for the app

The screens the home rails tap through to, and the list the detail page's main
CTA writes to. Phase 1 of [the mobile plan](docs/plans/mobile-offline-app.md).

**Nothing in the website changed.** This release adds two controllers, ten
routes and their tests. No existing file was modified other than the two module
`routes/api.php` files the new routes were appended to, the API spec and the
docs. The four live-site refactors Phase 1 needed are all behind us.

**One controller for four taxonomies.** Genres, categories, VJs and cast are
the same shape — a named thing with `movies()` and `shows()` — so they share
`TaxonomyController` rather than being four near-identical files that each need
fixing the next time the published-only rule changes. Only published titles are
listed, which is not a detail: an archive page is the easiest place in a
codebase to leak a draft, because the relation is the obvious query and the
scope is the easy thing to forget. There is a test per taxonomy that says so.

VJs are the one that is Jambo's own rather than the template's, and the list
excludes any VJ whose entire catalogue is still draft — otherwise the app would
show a VJ whose page is empty when you open it.

**The watchlist is add and remove, not toggle.** The website toggles through
one endpoint, which is right for a heart icon on a desktop. Over a mobile
connection a toggle is a coin flip: a request that times out and gets retried
silently undoes the first one. So the app gets `POST` to add and `DELETE` to
remove, both idempotent — adding something already listed succeeds and changes
nothing, and removing something absent is a success rather than a 404, because
the viewer's intent is satisfied either way. Both write through the same
`WatchlistItem::addFor` the website uses, so the two clients cannot disagree
about what is on a list.

Continue Watching gets its own screen, served from the same `HomeRailsService`
the home rail uses, so the two can never disagree about where someone stopped.
History is separate and keeps finished titles, because it is a record rather
than a to-do list.

A watchlist row whose title has since been deleted or unpublished is dropped
rather than returned as a null card — the app should not have to defend against
holes in a list.

**Verified.** 430 tests, 1493 assertions; the same two pre-existing
`PricingPageCurrentPlanTest` failures. On the real dev database: 8 genres, 6
categories and 1 VJ listed; the Action genre page returns 9 movies and 1 series
with no video URL in the body; an unknown slug is a clean 404; and adding the
same title twice leaves exactly one row while removing it twice succeeds twice.
The homepage was rendered again and diffed against the pre-refactor
fingerprint — identical.

### 1.8.25 — The app's home screen is the website's home screen

`GET /api/v1/home`, and the refactor that makes it honest.

**One homepage, two renderers.** `SectionDataComposer::build()` moved into
[HomeRailsService](Modules/Frontend/app/Services/HomeRailsService.php), which
the view composer and the API both call. The plan asked for this specifically:
the app's home should *be* the website's home by construction, not by
imitation. A second implementation would have drifted the first time an admin
dragged a category into a new position. Even the section headings come from
the same `sectionTitle` translation keys the blades render, so a wording change
reaches both at once.

The composer went from 437 lines to 87. What stayed is what is genuinely about
rendering a page: memoising per request, and the per-user watchlist index.

**A pin was written first, again.** The contract is not `build()` — that is the
method that moved. The contract is the set of variables the composer shares,
because every section blade reads them by name: a missing key renders an empty
rail, and a changed type is a 500 on the homepage. `HomeRailsPinTest` lists all
25, was green before the extraction, and is green after.

**The response is a list, not an object.** `rails` is ordered, each entry
carrying a `key`, a translated `title`, a `kind` and its items. The app renders
by `kind`, so a rail added server-side needs no app release, and category
shelves come through as `category:<slug>` in the admin's own drag order. Empty
rails are dropped — a heading over nothing is a screen of empty space on a
phone.

**Continue Watching says what to resume.** The website's card encodes that in a
URL, which a native player cannot follow, so each card now carries `resume`
(type and id) alongside `remove`. They are deliberately different for a series:
resume the *episode*, remove the *show*. Removing a single episode would let
the show re-surface on the next render, because the rail dedupes by show.

**A bug this found.** `/api/v1/home` is public, so `auth:sanctum` does not run
on it — and nothing else resolves a bearer token. Every `auth()->id()` deep
inside the rails and the recommender therefore read null even when the app sent
a perfectly valid token, and a signed-in viewer would have silently received
the guest home screen: no Continue Watching, no personalisation, and no error
anywhere to notice. Public endpoints that personalise now run `api.viewer`,
which resolves a token when one is sent and lets the request through when it is
not. It was caught by a test asserting Continue Watching appears.

**Verified.** 414 tests, 1407 assertions; the same two pre-existing
`PricingPageCurrentPlanTest` failures. Against the real dev database: the
website homepage still renders at the same size it did before the refactor, and
`/api/v1/home` returns six hero items and thirteen rails to a guest with no
empty rail and no video URL anywhere in the body. Signed in, Continue Watching
appears with the right title, the right resume target and the right position.

**Not verified.** The dev database has no upcoming titles and no visible-home
category with published content, so the `upcoming` and `category:*` rails were
exercised by tests but not seen on real data.

### 1.8.24 — The app can browse and watch, gated by the website's own rules

The endpoints the app lists titles with and plays them through, plus the
contract it is written against. Phase 1 of
[the mobile plan](docs/plans/mobile-offline-app.md).

**Nothing in these endpoints decides anything.** `POST /playback/sessions`
asks `PlaybackAuthorizer` the question `TierGate` asks for the website, and
`StreamSourceResolver` the question `StreamProxyController` asks;
`POST /playback/heartbeat` calls the same `PlaybackBeatRecorder` the web
heartbeat calls. An app viewer and a browser viewer are gated by one
implementation, so they cannot drift — which is the whole reason 1.8.22 went
first.

`StreamSourceResolver` is new for the same reason. Choosing between
`video_url` and `video_url_low`, falling back to the historic `dropbox_path`,
and handing the result to `CdnUrlResolver` were locked inside a private method
on `StreamProxyController`. The API needs the same answer — it cannot follow
the website's 302, it asks for the URL and gives it to a native player — so
the choice was extracted rather than written a second time.

**Nothing that could become a file URL leaves through a listing.** Every
catalogue response goes through an allow-listed resource. `video_url`,
`video_url_low` and `dropbox_path` sit on these models, and one of them in a
movie listing would hand the file to anyone, signed in or not — which is
precisely what the offline design exists to prevent. There are tests that
assert the string does not appear in the response body at all.

**One detail the app would otherwise get wrong.** An episode's own
`tier_required` is almost always null, because the admin Shows form puts the
plan on the series. A client reading that null as "free" would draw an
unlocked badge on premium content — the same mistake the server made until
1.8.22. So every episode publishes `effective_tier_required` beside it, with
the inheritance already resolved, and that is the field to draw a lock from.

Listings are cursor-paginated: a catalogue grows at the front, and offset
pages silently skip and repeat rows as someone scrolls, which on a phone reads
as "the app lost my place". A Data Saver request for a title with no low
rendition is refused rather than quietly served at full size — spending
someone's bundle without telling them is the one thing Data Saver exists to
prevent.

**The contract is written down.** `docs/api/openapi.yaml` describes every app
endpoint, and `OpenApiSpecTest` fails if a route exists that the spec does not
describe, if the spec describes a route that does not exist, or if an error
code can be returned that the spec's enum omits. A hand-written spec rots the
first time someone forgets it, and here the client is generated from this file
and ships inside an APK that cannot be hot-fixed.

**Ten tests had never run.** `phpunit.xml` globbed `Modules/*/tests/Feature`
but not `Modules/*/tests/Unit`, so `CdnUrlResolverTest` — which covers the
Bunny token-signing every stream URL passes through — was silently skipped on
every run since it was written. It passes when pointed at directly. The
directory is now in the suite, which is why the count jumped by more than the
new tests.

**Verified.** 394 tests, 1260 assertions; the same two
`PricingPageCurrentPlanTest` failures as before, both pre-existing. Then
driven against the real dev MySQL: browse movies with a cursor, open a detail
page, search, 404 on a bad slug, get refused as a guest, sign in, get refused
with `SUBSCRIPTION_REQUIRED`, take the matching plan, play, beat at 90
seconds, and see the next session resume at 90 — with `active_streams` keyed
on the device uuid, which is what makes an app install count against the
concurrent-stream cap through the existing picker and boot flow.

**Not verified.** The dev database's seeded titles carry placeholder paths
(`/jambo/movies/x.mp4`) rather than real Backblaze URLs, and the local
`BUNNY_TOKEN_KEY` is empty, so the smoke run could not confirm that a real
title comes back token-signed. `CdnUrlResolverTest` covers that path and now
actually runs, but whether Bunny Token Authentication is switched on for the
zone is still open question 9 and still needs one look at the dashboard.

**Not built yet.** Home rails (they need `SectionDataComposer` split into a
shared service, which is a live-site refactor of its own), genres, categories,
VJ pages, watchlist, continue-watching, ratings and reviews. Downloads are
Phase 3.

### 1.8.23 — The app can sign in, and every API route worked for the first time

Phase 1 of [the mobile plan](docs/plans/mobile-offline-app.md) continues: the
token layer the app signs in with, and the device list that lets an account
see and sign out a phone or a TV box.

**Every `/api/*` route on this site was returning a 500, and had been since
April.** The `api` middleware group referenced a `localization` middleware
that commit `9c8c192` — the i18n/RTL removal — deleted. The alias and the
group entry were left behind, so any request under `/api/` threw
`BindingResolutionException` before it reached a controller. Nothing caught
it for five months because nothing used those routes: the site's own JSON
endpoints (watchlist, heartbeat, player-data) are declared in
`routes/web.php` and run in the `web` group despite their `/api/v1/` paths,
and every module's `routes/api.php` held only unused scaffold. The first real
API route found it immediately. The reference is removed rather than the
middleware restored — this application has no i18n any more.

**Signing in.** `POST /api/v1/auth/login` uses the website's rules rather than
a second set of them: the account is looked up on a lowered email, compared
with `Hash::check`, refused if deactivated, and sent to two-factor when the
account has it. It shares the browser login's throttle bucket, so an attacker
cannot get five attempts at the web form and five more at the API. That
bucket is keyed on email *plus* IP, deliberately: East African carriers put
thousands of handsets behind one CGNAT address, and a per-IP limit would
throttle a whole cell tower because one person mistyped a password.

Two-factor is two calls instead of a redirect. Login answers
`TWO_FACTOR_REQUIRED` with a short-lived challenge token that is not an access
token and can do nothing else; the code goes back to
`POST /api/v1/auth/2fa/challenge`, which issues the real token. A wrong code
does not burn the challenge, because a typo should not send someone back to
the password screen.

**Devices.** Every sign-in must name the install it is for, and gets a token
bound to a row in the new `devices` table. `GET /api/v1/devices` lists them
and marks which one is asking; `DELETE /api/v1/devices/{uuid}` boots one,
which deletes its token and stops whatever it was playing counting against
the concurrent-stream cap. That last part works because a device's uuid is
the same "client session key" `active_streams` already stores for browser
sessions — an app device drops into the existing cap and picker without new
machinery.

Two details worth naming. Signing in again on the same handset replaces its
token instead of adding one; without that, tokens never expire here by config
and a reinstall cycle would quietly leave working credentials that no device
list shows and nobody can revoke. And a device belonging to another account
answers `DEVICE_NOT_FOUND` rather than 403, because a 403 would confirm the
uuid exists somewhere.

**One envelope.** Every `/api/v1` response is
`{success, message, data}` or `{success, code, message, errors}`, with `code`
from a single `ApiErrorCode` enum. Clients branch on the code and never on the
message, because message text gets reworded and an APK cannot be hot-fixed.
The exception handler renders validation, authentication and 404 failures into
that shape — scoped to token requests only, so the website's own `/api/v1/`
AJAX endpoints keep the shapes their JavaScript already reads.

**Verified.** 360 tests (20 new for auth and devices, 3 pinning the error-code
contract); the same two `PricingPageCurrentPlanTest` failures as before, both
pre-existing and unrelated. The whole flow was then driven against the real
dev MySQL — sign in on a phone, sign in on a TV, list both, boot the TV from
the phone, confirm the TV's token is dead and the phone's is not, sign out —
and behaved exactly as designed.

**A trap found while testing, recorded because it will recur.** Laravel's
`AuthManager` is a container singleton and `RequestGuard` caches the user it
resolved, so inside one test every later request reuses the first resolution
and a *deleted* token keeps working. Every revocation assertion here would
have passed no matter how broken revocation was. The tests call
`$this->app['auth']->forgetGuards()` before any request that must be
unauthenticated; production is unaffected, since each request is its own
process.

**Not built yet.** `POST /api/v1/auth/register` — mobile sign-up needs
decisions the web form answers with a honeypot, reCAPTCHA, `SignupAttempt`
logging and referral attribution, none of which port over unchanged, and a
half-ported version is worse than none. Catalogue, playback and download
endpoints are the next slices; the services they need already exist.

### 1.8.22 — One entitlement rule, so the app can share it

Groundwork for the mobile app. Nothing a viewer sees is meant to change;
this is Phase 1 of [the mobile, TV and offline plan](docs/plans/mobile-offline-app.md),
whose ADRs Rio accepted on 2026-09-08.

**Why now.** The app feeds off this webapp, so it needs an API, and the API
needs to answer "may this person watch this?" The rules for that were written
out three times: in `TierGate`, in `FrontendController` (as `userCanWatch()`,
`concurrencyExceeded()` and `contentReleased()`), and in
`StreamProxyController::streamable()`. `TierGate`'s own comment said the
copies must not "drift apart". They had. Adding a fourth copy for the app was
not an option, so the rules moved into
[PlaybackAuthorizer](Modules/Streaming/app/Services/PlaybackAuthorizer.php)
and everything else now calls it. The heartbeat got the same treatment in
[PlaybackBeatRecorder](Modules/Streaming/app/Services/PlaybackBeatRecorder.php),
because the app has to send beats without a Laravel session or a CSRF token.

That took 95 lines of duplicated logic out of `FrontendController`.

**Two places the copies disagreed, and which one won.**

A `tier_required` slug that matches no plan row is a data error, and both
copies carried a comment saying it should be treated as free. Only `TierGate`
actually did; `userCanWatch()` refused guests one branch before it looked the
slug up, so a mis-slugged title bounced a guest off the watch page while the
player and the stream URL served them the film. `TierGate` wins, because it is
the security boundary and it was already handing over the bytes — this closes
a contradiction rather than opening anything. No title in the database
currently has a slug like that, so the change has nothing to act on today.

The concurrency cap now applies to episodes that inherit their plan from the
series. `concurrencyExceeded()` read the episode's own `tier_required` with no
fallback to the show — and that column is normally empty, because the Shows
form puts the plan on the series. So the cap was skipped on `/watch` and
`/episode` for nearly every episode. It was not a hole: `TierGate` still
applied it to `/watch/src/`, which meant a viewer over their limit got a
player that silently refused to load instead of the device picker. The same
people are refused as before. They are now told why.

**The decision is now a value, not a boolean.** `PlaybackDecision` carries a
reason from `PlaybackDenial`, whose cases are the error codes the app will
branch on: `LOGIN_REQUIRED`, `SUBSCRIPTION_REQUIRED`, `UPGRADE_REQUIRED`,
`STREAM_LIMIT`, `CONTENT_UNAVAILABLE`. The web still renders "no plan" and
"plan too low" as one 403 with the same sentence it used before, because that
is what is live. The split exists so the app can offer someone with no plan a
"Subscribe" screen and someone on Basic an "Upgrade" one.

**Verified.** 337 tests pass; the two that fail
(`PricingPageCurrentPlanTest`) failed the same way before any of this and are
about a pricing-page badge. 16 of those tests are a pin written *before* the
extraction, asserting the HTTP behaviour of the player, the watch page and the
stream URL, and they pass unchanged after it. The homepage, `/movie`,
`/series` and `/pricing` were rendered against the real dev database through
the refactored path and returned 200; a `day-pass` title correctly sent a
guest to login from both the watch page and the player.

**Not verified.** The heartbeat and the device-picker kick were exercised by
their existing tests, not by a real browser session. No API endpoint exists
yet — that is the next commit, and no route in this change is new.

### 1.8.21 — Featured on the house shell, and the big banners auto-rotate

Two things from Rio: the Featured screen should look like the rest of the
admin, and "autoscroll is off for most of the sections that need it."

**Featured now uses the Categories shell.** 1.8.20 invented its own
layout. This rebuilds it on the same structure the Categories screen
uses — a `col-lg-4` add card beside a `col-lg-8` list card, the same
underlined headings, the same table classes, drag handle and delete
button. An admin screen that invents its own layout costs more to learn
than it gains in polish. The catalogue type-ahead from 1.8.20 is kept
in the position Categories puts its form, and is the only element with
scoped styles, because the admin has no house component for it.

Craft lives in the details rather than the structure: tabular rank
figures so digits do not jog mid-drag, the accent on slot 1, posters
with real proportions and depth, a row hover, a settle on the row that
was just moved, and an empty state that says what the homepage is doing
instead. The "Main banner" pill moved off the primary tone — the admin
accent is red, so it read as an error beside the green "Showing" and
orange "Hidden" pills.

**The banner sliders rotate now.** The template ships every slider with
autoplay off: the hero's `autoplay: true` is commented out in
`frontend/js/swiper.js`, and all 39 rails carry `data-autoplay="false"`.
That is correct for the poster rails and wrong for the banners. A hero
that never advances shows only the first title an admin curated under
Featured, and the "#N in Movies Today" and "#N in Series Today" banners
never reached #2.

[jambo-banner-rotate.js](public/frontend/js/jambo-banner-rotate.js) is
Jambo's file, not the template's (ADR-0001), and drives the existing
Swiper instances rather than re-initialising them, so the template keeps
every other slider setting. It covers the homepage hero (8s, it carries
the most copy), the vertical "Movies Today" banner, the trending series
tab slider and the /movie, /series and VJ listing banners (7s).

**The poster rails deliberately still do not move.** Content that slides
away while someone is reaching for it is hostile, and 39 rails moving at
once would be unreadable. If any specific rail should rotate, it is one
attribute on that section.

Pausing, because WCAG 2.2.2 requires moving content to be stoppable:
hover or keyboard focus inside the banner, a hidden browser tab, and a
20-second hold after the viewer swipes or uses the arrows.
`prefers-reduced-motion: reduce` disables rotation entirely.

Verified in a real browser on the homepage: the hero advanced from slide
0 to 1 on its own across nine slides; hovering held it at 0 and leaving
resumed it; a poster rail stayed on slide 0 across the same window. The
20 Featured tests still pass and the design detector is clean. Rendered
the rebuilt admin screen with four rows and the live search dropdown.

Not verified: the reduced-motion path, which is a read of the code path
rather than an executed check; and drag with a real pointer.

Deploy: pull-only + `php artisan view:clear`. No migration.
### 1.8.20 — Featured: catalogue search, and a screen shaped like the thing it controls

Rio asked for more craft on the Featured screen and a search to find a
title, keeping the behaviour from 1.8.19.

**Type-ahead over the whole catalogue.** The two dropdowns are gone. They
listed the newest 300 movies and the newest 300 series, so most of the
catalogue was simply unreachable — the wrong shape for a library that
grows daily. One field now searches movies and series together through
`admin.featured.search`, debounced so a fast typist sends one request
rather than one per keystroke, and each in-flight request is aborted when
a newer keystroke supersedes it. Results carry the poster, kind, year and
status, so two similarly-named VJ translations can be told apart before
one is added. A title already in the hero comes back listed and disabled
rather than missing, because a search that silently returns nothing for a
title you can see on the page reads as a bug. Arrow keys, Enter and
Escape work; the add itself is still a plain form post, so it behaves the
same without JavaScript.

**The list is now shaped like the hero it controls.** Slot 1 renders in
the banner's own 16:9 with the accent rank and a "Main banner" pill; the
rest are 2:3 posters, the way they appear in the rail beside it. Posters
carry the row instead of sitting in a 48px column, because the poster is
what an admin actually recognises. Dragging renumbers live, relabels the
new slot 1, and settles the moved row.

Craft fixes found by rendering rather than by tests:

- The reorder failure path was a blocking `alert()` reading "Could not
  save the new order. Reloading." It is now an inline status that names
  the problem and the recovery, and does not throw work away.
- Four elements sat under the contrast floor: the rank numeral, the
  search placeholder, the result meta line and the drag handle.
- The role label was a coloured uppercase kicker above the heading. Same
  information, moved into the meta line as a pill.
- On a wide monitor the row stretched the full card, putting a metre
  between a title and its remove button. The list is capped at a reading
  width.
- On a phone the lead title truncated to "Hidden Stor…" and the meta line
  left a dangling separator before the status badge.

Also: tabular figures on the rank so digits do not jog while dragging,
a themed focus ring, `prefers-reduced-motion` honoured, and every colour
drawn from a Bootstrap theme variable so the screen follows the admin
theme rather than pinning its own.

Verified: 20 tests, six of them new for search — both kinds found, a
title outside the newest 300 reachable, already-featured flagged,
sub-two-character terms ignored, unpublished titles returned with their
status, and admin-only access. Rendered at 1500px and 390px wide: empty
state, live search dropdown, and four featured rows including a draft.
The design detector reports clean.

Not verified: drag-and-drop with a real pointer; the reorder endpoint is
tested directly and the SortableJS wiring is unchanged from 1.8.19.
Local seed posters point at an external placeholder service, so a
poster that is slow there is a data artifact, not a layout fault.

Deploy: pull-only + `php artisan view:clear`. No migration.
### 1.8.19 — Featured: admins choose and order the homepage hero

Rio: a new menu between Categories and Vjs where an admin picks which
movie or series is the big homepage banner, dragged into the order they
want, the same way the categories page works.

**New screen at /admin/featured.** Add a movie or a series from two
pickers, drag rows by the handle to reorder, remove with one click. The
drag posts the new order immediately to
`admin.featured.reorder`, the same contract as
`admin.categories.reorder`, and the position column renumbers without a
reload. SortableJS from the same CDN the categories page already uses.

**The homepage hero follows that list.**
[SectionDataComposer::buildHero()](Modules/Frontend/app/View/Composers/SectionDataComposer.php)
now reads the featured rows first. With the list empty it keeps the old
automatic behaviour — the three most-viewed movies and three most-viewed
shows, interleaved — so deploying this changes nothing on the site until
someone curates, and the hero can never end up blank.

**Scope: the homepage hero only.** Rio asked explicitly that the /movie,
/series and VJ page banners stay as they are. Those are a different
partial (`movie-slider`) fed by FrontendController's own queries and are
untouched; a test pins that.

Two rules keep the screen honest for a non-technical admin:

- **Only publishable titles reach the site.** A featured draft, or a
  series with no published episode yet, is dropped from the homepage but
  still listed on the admin screen with a "Hidden" badge. Silently
  omitting it would leave nobody able to explain why the homepage looks
  wrong. Announced-but-unaired series are the realistic case.
- **Deleted titles stop holding a slot.** `FeaturedItem::prune()` sweeps
  orphaned rows when the screen loads.

New: `featured_items` table (morph target, `sort_order`, unique per
title), [FeaturedItem](Modules/Content/app/Models/FeaturedItem.php),
[FeaturedController](Modules/Content/app/Http/Controllers/Admin/FeaturedController.php),
the admin view, four routes, and one sidebar entry.

Verified: 14 tests in
[FeaturedHeroTest.php](Modules/Content/tests/Feature/FeaturedHeroTest.php)
covering the fallback, drag order beating view count, the movie/series
mix, drafts and episode-less series being held back while staying
visible in admin, add, duplicate rejection, reorder, remove, orphan
sweep, admin-only access, and the scope guard on /movie. Mutating the
`published()` filter out failed the draft test, restored. Rendered
locally: the empty state, four featured rows with both badge states, and
the homepage hero following the chosen order.

One bug caught by rendering rather than by tests: the row loop assigned
`$title`, and Blade shares a view's variables with its layout, where the
admin header partial renders `$title`. An Eloquent model stringifies to
JSON, so the whole movie record printed across the top of the page. The
variable is now scoped, and a test asserts no model is stringified into
the page.

Deploy: `git pull`, `php artisan migrate`, `php artisan view:clear`.
### 1.8.18 — Created by badge: one name, not two

Rio, on 1.8.17: "let it be one name not two names." The badge showed the
admin's full name; it now shows the first name only — "Grace Nakato"
renders as "Grace". A username like `vj_junior` is already one token and
is unchanged.

The full name moves to the badge's hover title ("Added by Grace Nakato"),
because two admins can share a first name and this column is the visible
half of the per-admin upload credit that the Performance dashboard pays
against. `creatorFullLabel()` holds the full form,
[creatorLabel()](Modules/Content/app/Models/Concerns/TracksContentActivity.php)
takes its first word, so the activity-log snapshot of a deleted admin
shortens exactly like a live record instead of staying long. The badge's
max width drops from 130px to 120px.

Verified: the eight tests from 1.8.17, now pinning both forms — the badge
carries one name, the hover carries the full name, on the live path and
the snapshot path. Mutating `creatorLabel()` back to the full name failed
four of them; restored. Rendered locally with "Grace" (live) and "Daniel"
(snapshot) side by side.

Deploy: pull-only + `php artisan view:clear`.

### 1.8.17 — Admin lists: a "Created by" badge naming who added each title

Rio asked for a badge like the Draft one, carrying the name of the admin
who created each movie or series, alongside Year, Genres, Cast and the
rest.

No migration was needed: `created_by` / `updated_by` and a `creator`
relation have been on movies, shows, seasons and episodes since the
2026_07_13 authorship migration, stamped by
[TracksContentActivity](Modules/Content/app/Models/Concerns/TracksContentActivity.php)
whenever a signed-in admin saves. Nothing was showing it.

New column between Status and Plan on both
[admin/movies](Modules/Content/resources/views/admin/movies/index.blade.php)
and [admin/series](Modules/Content/resources/views/admin/shows/index.blade.php),
rendered by one shared partial,
[creator-badge.blade.php](resources/views/components/partials/creator-badge.blade.php),
so the two lists cannot drift and episodes can reuse it. A grey pill with
a user glyph, matching the genre and plan badges; the name truncates at
130px with the full name on hover.

**Where the name comes from**, in order of trust:

1. the `creator` relation — the live user row;
2. the append-only activity log's `actor_name` snapshot, which is the only
   record left once an admin's user row is deleted (`created_by` is
   ON DELETE SET NULL — confirmed against the live schema, DELETE_RULE
   SET NULL). `hydrateCreatorLabels()` batches this into one query per
   page rather than one per row;
3. an em dash, with a tooltip saying why.

**The em dash is deliberate and will be common at first.** Content added
before 2026-07-13, or by a seeder, importer or console command, has no
actor recorded anywhere. A guessed name would be wrong in a way that
matters: the Performance dashboard counts uploads per admin, so a
fabricated credit here becomes someone's payout there. There is no honest
backfill — the log's `updated` rows name whoever last edited a title, not
who added it. Titles added from now on show a name.

Verified: eight tests in
[CreatorCreditTest.php](Modules/Content/tests/Feature/CreatorCreditTest.php)
covering the live name, the username fallback when an admin has no full
name, the deleted-admin snapshot, the honest blank, the one-query
guarantee, and both list pages rendering. Two mutations proved the guards
bite — making the resolver guess "Admin" failed the two blank-state tests;
making the log fallback a no-op failed the deleted-admin and query-count
tests. Both restored. Rendered locally at 1500px with all three states on
screen at once.

Two notes from building it: the test DB is SQLite, which ignores a foreign
key added by a later `Schema::table()` call, so the deleted-admin test
stages the null itself instead of asserting a constraint that driver never
applies; and `users.first_name` / `last_name` are NOT NULL, so "no full
name" means empty strings, not null.

Deploy: pull-only + `php artisan view:clear`. No migration, no new column.

### 1.8.16 — Sessions last 8 idle days instead of 2 hours

Rio: "the session on the browser is reset every day, make it last at
least 8 days." The cause was smaller than a day: every environment
file, including `.env.production.example`, carried Laravel's
`SESSION_LIFETIME=120` — two hours of inactivity, the default meant
for web forms. A viewer who did not tick "Remember me" was signed out
between visits, and the 1.8.0 signup 419s came from the same setting
(that entry already suggested raising it).

Changes: the default in [config/session.php](config/session.php) is
11520 minutes (8 days idle) with the reasoning beside it; `.env`,
`.env.example` and `.env.production.example` say 11520;
`.env.production.example` now also says `SESSION_DRIVER=database`,
which production has used since device management (final-polish.md
Phase A) although the example still said `file`. The stream-limit page
in [StreamingController.php](Modules/Streaming/app/Http/Controllers/StreamingController.php)
listed every session younger than the lifetime as a device the viewer
might need to log out; that window is now capped at one day, because
a device idle for days cannot be holding a stream (streams are
heartbeat-keyed) and eight days of sessions would only pad the page.
The Devices page deliberately still lists everything — that is what
it is for.

The lifetime is idle time: each request refreshes the cookie and
`last_activity`, so anyone who opens Jambo at least once a week stays
signed in indefinitely; "Remember me" (5-year cookie) is unchanged and
still opt-in, which is the right default for shared computers.

**Deploy needs one server-side step**, because production's `.env`
sets the value explicitly and env wins over the config default:

```bash
sudo -u jambo2820 -i bash -c 'cd /home/jambofilms.com/public_html && git pull \
  && sed -i "s/^SESSION_LIFETIME=.*/SESSION_LIFETIME=11520/" .env \
  && php artisan config:cache && php artisan view:clear'
```

Existing sessions pick up the new expiry on their next request; anyone
already signed out signs in once more. Verified: the streaming and
auth test suites pass, and `config('session.lifetime')` reads 11520
both from `.env` and from the code default (checked with the `.env`
line temporarily removed). Not verified: production, until the `.env`
line is changed.

### 1.8.15 — Top 10 rails: slightly smaller rank numerals

Rio's request from a screenshot of the Top 10 rail: the numerals
covered most of each poster. Streamit sets `.top-ten-numbers` to 7.5em
on desktop and 4.5em below 992px. Both drop by one sixth, to 6.25em and
3.75em, in [jambo-header.css](public/frontend/css/jambo-header.css) per
ADR-0001 — two rules, because the vendor's mobile rule shares the
specificity and loads earlier, so a single base override would have
won on phones too. Anchor and hover lift unchanged.

Verified by rendering the home page rail locally at 1920×1080 and
390×844. Deploy: pull-only + `php artisan view:clear`.

### 1.8.14 — Rail titles say "This Week"; Movies Today shows ten; padded titles get no rank

Two follow-ups to 1.8.13 from Rio, plus one honesty fix found on the way.

**Titles.** The seven-day rails now say what they are:
`Top 10 Movies This Week`, `Top 10 Series This Week`, and `Best in
Series This Week` on /series (it is fed by the same weekly list).
Strings only, in [lang/en/sectionTitle.php](lang/en/sectionTitle.php).

**Ten in "Movies Today".** The vertical hero slider showed the top 5 of
the daily ten; Rio wants up to ten. The composer now passes the whole
daily shelf. The vendor swiper loops with three thumbs visible on
desktop, so ten scroll in the thumb column as they do in the series
tab slider.

**No badge a title did not earn.** Both daily shelves pad themselves
from all-time popularity on quiet days, and every slot wore a
"#X in … Today" rank, so a padded title claimed a rank nobody gave it
today; ten slots make that more likely than five. The recommender now
sets `recent_viewers` (the distinct-viewer count that ranked the title)
on ranked models only; it rides along into the cache. The two badge
partials show "#X in Movies Today" / "#X in Series Today" when that
count is present and "Popular on Jambo" otherwise, same gold pill, no
number. Rank numbers still count from 1 in shelf order, so a shelf with
four real titles reads #1–#4 then four "Popular on Jambo".

Files: [TopPicksRecommender.php](Modules/Frontend/app/Services/TopPicksRecommender.php),
[SectionDataComposer.php](Modules/Frontend/app/View/Composers/SectionDataComposer.php),
[vertical-banner.blade.php](Modules/Frontend/Resources/views/components/partials/vertical-banner.blade.php),
[tab-series-slide.blade.php](Modules/Frontend/Resources/views/components/partials/tab-series-slide.blade.php),
[verticle-slider.blade.php](Modules/Frontend/Resources/views/components/sections/verticle-slider.blade.php),
[lang/en/streamMovies.php](lang/en/streamMovies.php).

Verified: two new tests assert the ranked title carries its count and
the padded one carries none, for movies and series; the full
recommender class is green. Home and /series rendered locally: ten
slides in the Movies Today slider with the two badge variants, and the
new titles in place. Not verified: production data, and the slider's
loop behaviour with exactly ten slides on a real touch device.

Deploy: pull-only + `php artisan view:clear`. The daily caches keep
their keys: an entry cached before this deploy lacks `recent_viewers`,
so until midnight every slide on that box reads "Popular on Jambo";
run `php artisan cache:clear` after the pull to skip that.

### 1.8.13 — "To Watch" rails rank the last seven days; "Today" shelves stay daily

User question after 1.8.11: can the all-time ranking be weekly, with
the daily one kept? Yes. The home page now has two windows on purpose:

| Section | Window | Refresh |
|---|---|---|
| Top 10 Movies To Watch, Top 10 Series To Watch (also Best In Series on /series) | distinct viewers, last 7 days | daily, per-date cache key |
| "#X in Movies Today" slider, "#X in Series Today" tab slider | distinct viewers, last 24h | daily, per-date cache key |

Before: the series rail used `globalTopPicks()`, an all-time weighted
score the same titles hold for months, and 1.8.11 had put the movie
rail on the 24h ranking, which made it twitchy and identical to the
slider. Both rails now use a rolling seven-day window, so they move
with the week; the all-time score remains only as the padding when a
week is thin (movies) or as views_count order (series, unchanged), and
for the cold-start fallbacks elsewhere.

Implementation in [TopPicksRecommender.php](Modules/Frontend/app/Services/TopPicksRecommender.php):
the two daily computations became `computeTopMoviesByRecentViewers()`
and `computeTopSeriesByRecentViewers()` with a window in days; the
daily wrappers pass 1, the new `topMoviesOfTheWeek()` /
`topSeriesOfTheWeek()` pass 7 on their own per-date cache keys
(`top_movies_of_the_week:{date}:v1`, `top_series_of_the_week:{date}:v1`;
`CatalogCacheObserver` flushes the whole cache on content changes, so
nothing new to wire). The series result is now an Eloquent collection
too, closing the same trap 1.8.12 fixed for movies.
[SectionDataComposer.php](Modules/Frontend/app/View/Composers/SectionDataComposer.php)
feeds `topMovies` and `topShows` from the weekly shelves and the
vertical slider from the daily one; comments say which is which.

Section titles were left as they are ("… To Watch"); "… This Week"
would be more explicit and is a one-line lang change if wanted.

Verified: six new tests in
[TopPicksRecommenderTest.php](Modules/Frontend/tests/Feature/TopPicksRecommenderTest.php)
— five-day-old watches count and beat all-time totals, nine-day-old
ones do not, the weekly shelf caches on its own key without touching
the daily one, and a case where the day's leader and the week's leader
differ shows each shelf following its own window. Full recommender
class green; home page renders 200 locally on MySQL. Not verified: on
production data, whether a week has enough viewers to reorder the
rails or falls back that week.

Deploy: pull-only + `php artisan view:clear`. New cache keys, no flush
needed.

### 1.8.12 — Hotfix: 1.8.11 took the home page down (500)

1.8.11 built the daily movie shelf with `collect()`, a base
`Illuminate\Support\Collection`. `SectionDataComposer` then calls
`loadAvg('ratings', 'stars')` on the top 5 for the vertical hero
slider, and `loadAvg()` exists only on Eloquent collections —
`BadMethodCallException: Method Illuminate\Support\Collection::loadAvg
does not exist`, on every page that runs the composer. The tests
never called `loadAvg`, the manual check ran the method in isolation,
and the home page was not re-rendered before the push. That is the
whole failure: the 500 was shipped by me, not by the server.

Fix in [TopPicksRecommender.php](Modules/Frontend/app/Services/TopPicksRecommender.php):
the result starts as `(new Movie)->newCollection()`, so `concat()`,
`values()` and `take()` keep the Eloquent type all the way to
`loadAvg()`. The daily cache key suffix moves to `:v2`, because the
first 1.8.11 request on every box already cached a base collection
under today's `:v1` key and would keep serving it until midnight even
after the code fix — the new suffix orphans that entry, so the deploy
needs no `cache:clear` (running one is harmless).

Verified: two new assertions in
[TopPicksRecommenderTest.php](Modules/Frontend/tests/Feature/TopPicksRecommenderTest.php)
require an Eloquent collection on both the padded and the
fallback-only branch; they fail against the 1.8.11 code (checked by
mutating the fix back) and pass with it. The home page renders 200
locally on MySQL with the `:v2` key. Not verified: production.

Deploy: pull-only + `php artisan view:clear`.

### 1.8.11 — Top 10 Movies of the Day actually changes daily

User report: the Top 10 Movies rail and the "#X in Movies Today"
vertical hero slider showed the same titles every day, while Top 10
Series of the Day rotated. Cause: the two rails were fed by different
algorithms. The series tab slider uses `topSeriesOfTheDay()` (distinct
viewers in the last 24h, cached on a per-date key), but `topMovies` in
[SectionDataComposer.php](Modules/Frontend/app/View/Composers/SectionDataComposer.php)
came from `globalTopPicks()`, an all-time weighted score (views,
completions, watchlist adds, ratings, reviews, editor boost) with no
day window at all — so it only moved when the all-time totals did,
which for the top titles is never.

Fix: `topMoviesOfTheDay()` in
[TopPicksRecommender.php](Modules/Frontend/app/Services/TopPicksRecommender.php),
a movie twin of the series method — same 24h distinct-viewer ranking,
same per-date cache (flushed by CatalogCacheObserver on content
changes like every other shelf), no episode hop because movies are
watched directly. When daily activity is thin it backfills from
`globalTopPicks()`, which is exactly what the rail showed before, so a
quiet day looks like the old rail rather than a half-empty one. The
composer feeds both the Top 10 Movies rail and the vertical slider's
top 5 from it, so the "#X in Movies Today" badge is now honest.
`globalTopPicks()` is untouched for its other callers (Top 10 Series
to Watch rail, cold-start fallbacks).

Verified: four new tests in
[TopPicksRecommenderTest.php](Modules/Frontend/tests/Feature/TopPicksRecommenderTest.php)
mirroring the series ones — daily viewers beat all-time popularity,
watches older than 24h do not count, cold catalog falls back to the
all-time ranking, second call in the day hits cache. The 24h guard
was proven by mutation (widened the window, watched the test fail,
restored). Not verified: on production data — whether movies have
enough daily viewers to reorder the shelf on a given day, or fall back
that day, depends on real traffic. Deploy: pull-only +
`php artisan view:clear` (no migration; the daily cache key is new so
no flush is needed).

### 1.8.10 — Detail/watch/episode pages: tighter rail rhythm

Follow-up to 1.8.9 from the live series detail page: with the rails
on the home rhythm (3.75em between them) Rio still read the detail,
watch and episode pages as too spaced out. Those pages are a title
block followed by secondary rails, so they get their own token,
`--jambo-rail-gap` (2.5em on desktop, 1.5em under lg), applied by a
`.jambo-detail-rails` class on the rails wrapper of the four pages in
[jambo-header.css](public/frontend/css/jambo-header.css): the
wrapper's top gap and every swiper's bottom margin inside it. The
episode number grid reads the same token from
[episode-layout-assets.blade.php](Modules/Frontend/Resources/views/components/partials/episode-layout-assets.blade.php).
Home rails do not carry the class and keep the vendor spacing.

Verified by rendering the series detail page at 1069×1700 locally.
Not verified: watch and episode pages (same wrapper class and token,
not re-rendered this time). Deploy: pull-only + `php artisan view:clear`.

### 1.8.9 — Frontend: hero heights on tall screens + one rail rhythm on detail/watch pages

Two reports from a portrait monitor (1069×1700): the home and
listing heroes showed "huge uncontrolled spacing", and the movie /
series / episode detail and watch pages had oversized gaps between
sections. Four causes, all fixed without touching the Vite SCSS
pipeline (same approach as the 9e8fef3 banner fix: rules live in
[jambo-header.css](public/frontend/css/jambo-header.css), which loads
after the bundle and is cache-busted by `versioned_asset()`; see
ADR-0001).

**1. Heroes were sized from viewport height alone.** Streamit pins
the OTT home hero and the guest home hero to `min-height: 92vh` and
the listing banner (`movie-slider` partial: /movie, /series,
/upcoming, /genres/*, VJ pages) to `72vh`. On a 16:9 monitor that is
roughly a widescreen frame of the width, so it looks intentional. On
a portrait or 5:4 screen the same rule made a 1069px-wide hero
1560px tall — title and CTA in the middle, empty backdrop above and
below, a full screen to scroll before the first rail. Now the vendor
value stays as the ceiling but a hero can never be taller than a
widescreen frame of the viewport *width*: `clamp(34em, 56.25vw, 92vh)`
for the home heroes, `clamp(26em, 50vw, 72vh)` for listing banners.
On 16:9 landscape clamp() resolves to the vendor value, so desktop
monitors do not change (1920×1080 before/after renders are
identical); the 1069×1700 hero went from 1560px to ~600px.
Desktop-only: below Streamit's own mobile breakpoints the vendor SCSS
already switches to content-driven height, so the caps do not fire
there. Selectors mirror the vendor chains so equal specificity plus
load order wins without `!important`.

**2. Detail hero carried a phantom header allowance.** The vendor
sizes the detail hero box as `calc(42% + var(--header-height, 5em))`;
the `+ header` compensated for the template's fixed transparent
header overlapping the hero. Jambo's header is sticky and in flow and
`--header-height` is never defined anywhere, so every movie and
series detail page rendered ~80px of black below the backdrop. The
box is now 21:9, matching the fallback backdrop's inline
`aspect-ratio`; trailers are absolutely positioned and follow the box.

**3. One rail rhythm on watch, episode, series-detail and
movie-detail.** Rails on those pages stacked `.show-episode
.section-padding` (3.75em top *and* bottom) with each swiper's own
`mt-4 mb-5` and the vendor `.swiper { margin-bottom: 3.75em }`, so
consecutive rails sat ~170px apart while home rails sit 60px apart.
Now the `.overflow-hidden` wrapper carries the single top gap
(`section-padding-top`, which the vendor already scales down on
tablet and phone), each rail relies on the vendor swiper margin, the
episode number grid gets the same 3.75em in
[episode-layout-assets.blade.php](Modules/Frontend/Resources/views/components/partials/episode-layout-assets.blade.php)
so toggling scroller/grid never moves the section below, and the
reviews block drops its top padding (`section-padding-bottom`) since
the rail above already ends with the swiper margin. Net: heading →
cards 24px, cards → next heading 60px, identical to the home page.
Edited: [watch-page.blade.php](Modules/Frontend/Resources/views/Pages/Movies/watch-page.blade.php),
[episode-page.blade.php](Modules/Frontend/Resources/views/Pages/TvShows/episode-page.blade.php),
[TvShows/detail-page.blade.php](Modules/Frontend/Resources/views/Pages/TvShows/detail-page.blade.php),
[Movies/detail-page.blade.php](Modules/Frontend/Resources/views/Pages/Movies/detail-page.blade.php),
[reviews-block.blade.php](Modules/Frontend/Resources/views/components/partials/reviews-block.blade.php).

**4. Detail description overlay collided with "Starring" on xl
laptops (found while verifying, pre-existing).** `_page-content.scss`
overlays `.movie-detail-part` on the trailer from xl (1200px) up,
absolutely positioned at `top: 28%`. On 1200–1399px the description
column is 50% of a narrow page, an ordinary VJ title wraps to three
lines, and the block ran ~200px past the hero box straight through
the "Starring" heading (rendered at 1280×1024; the old 80px-taller
box had the same collision). No banner height at those widths can
hold that block, so between 1200 and 1399.98px the details go back
into flow below the banner — the layout the page already uses below
xl — with the 80%-width column; the overlay stays for 1400px+ where
the box is 600px+ and titles fit in two lines (rendered at 1440×900,
1920×1080). **Trade-off:** on a 1366×768 laptop the CTA row now sits
at the bottom edge of the first screen instead of inside the banner.
If that matters, the follow-up is a shorter (cropped) hero box at
xl widths, not a return to the overlay.

**Verified** by rendering locally (headless Edge driven over the
DevTools protocol against `php artisan serve`, logged in as a
throwaway subscribed user that was deleted afterwards), before and
after: OTT home, /home, /movie, /series, series detail, movie detail,
watch and episode pages at 1069×1700; OTT home and /movie at
1920×1080 (unchanged); movie detail at 1280×1024, 1366×768, 1440×900
and 1920×1080 for the overlay rule. Blades compile (pages served with
the new markup after `view:clear`).

**Not verified:** real phones (the caps deliberately do not fire
below lg, so the vendor mobile layout is untouched, but it was not
re-rendered); a detail page with a trailer that actually plays (seed
data has dead YouTube URLs, so the box was checked with the fallback
backdrop and the dead player); the series detail page at xl widths
(same rule and markup as movie detail, not separately rendered);
/upcoming, /genres/* and VJ pages (same `movie-slider` partial as
/movie, not separately rendered).

**Deliberately not built:** the SCSS source was not patched. The
9e8fef3 fix patched both, but the CSS file already wins on load
order regardless of rebuilds, and `jambo-setup.md` forbids editing
template SCSS (ADR-0001 records the convention). `--header-height`
was not defined either: its only other consumers are the vendor
`.custom-header-relative .main-content` padding (already overridden
to 0) and `.iq-restriction_box`, which is on no Jambo page. The
/movie page's own ~108px gap between banner and first VJ rail is
page padding, not the banner, and was left alone.

Deploy: pull-only (§3a) + `php artisan view:clear`. No build, no
migration, no dependency.

### 1.8.3 — Home: Smart Shuffle promoted to position 2

User asked for Continue Watching → Smart Shuffle → Top 10 as the
new attention sequence on the OTT home page (the post-login
landing). Smart Shuffle was rendering near the bottom of the
page, below Genres — meaning users had to scroll past every
other rail before seeing the personalised half-familiar /
half-discovery shelf that's most likely to surface a tap.

Moved the `@include('frontend::components.sections.recommended',
['recommended' => __('sectionTitle.smart_shuffle'), 'viewAllBtn' => true])`
up to right after `continue-watching` in
[Pages/MainPages/ott-page.blade.php](Modules/Frontend/resources/views/Pages/MainPages/ott-page.blade.php).
Top 10 Movies and Top 10 TV Shows now follow Smart Shuffle.
Every other rail (VJs, Only on Streamit, Fresh Picks, Upcoming,
Vertical Slider, Personalities, Popular Movies, Tab Slider,
Genres, Top Pick) keeps its existing position.

No data, no controller, no service change — pure ordering.

### 1.8.2 — Perf: route the remaining full-screen backdrops through /img

User report: poster cards load fine, but the **big background
images** on detail pages, listing pages, and the home hero feel
slow. Code review confirmed the gap: the 1.6.0 image-proxy work
optimized poster CARDS via `media_img()` + `srcset`, but five
templates that paint full-screen `background-image` backdrops
were still calling raw `media_url()` and serving the original
uploaded file (often 4K source, 3-5MB) to every visitor.

Five one-liner swaps to `media_img($value, 1920, $fallback)` so
every full-screen backdrop now goes through the same Glide
proxy as the cards (WebP, q=80, 7-day disk + browser cache):

- [movie-slider.blade.php](Modules/Frontend/resources/views/components/cards/movie-slider.blade.php) —
  banner reused across **TV shows / Movies / Upcoming / Genres
  / every VJ page**. The single biggest fix.
- [Pages/Movies/detail-page.blade.php](Modules/Frontend/resources/views/Pages/Movies/detail-page.blade.php) —
  21:9 backdrop on movie detail.
- [Pages/TvShows/detail-page.blade.php](Modules/Frontend/resources/views/Pages/TvShows/detail-page.blade.php) —
  21:9 backdrop on series detail.
- [hero-banner.blade.php](Modules/Frontend/resources/views/components/partials/hero-banner.blade.php) —
  OTT home page hero rotator.
- [tab-series-slide.blade.php](Modules/Frontend/resources/views/components/partials/tab-series-slide.blade.php) —
  Trending tab slider.

Expected impact per affected page: **~5MB → ~150KB** per
backdrop, identical visual quality (WebP q=80). External URLs
(TMDB / IMDB / etc.) pass through unchanged because
`media_img()` bails to the original URL when the value starts
with `http(s)://` — see [app/helpers.php](app/helpers.php).

First visit per unique backdrop pays a one-time ~200ms resize
cost while Glide caches the WebP variant; subsequent hits are
served straight from disk + the 7-day browser cache from the
1.5.33 vhost rule.

No deploy steps beyond `git pull` + `php artisan view:clear`.
No new dependency, no migration, no DB change.

### 1.8.1 — Sidebar: group operational pages under "System info"

The four admin-side operational pages — System Updates, Error log,
System status, Signup attempts — were either scattered as separate
top-level links (Updates) or sitting awkwardly between Settings and
SEO (Logs, Status). With the 1.8.0 addition of Signup attempts,
the sidebar started feeling crowded.

Bundled all four under a new collapsible **"System info"** group
in [resources/views/components/partials/vertical-nav.blade.php](resources/views/components/partials/vertical-nav.blade.php),
matching the existing Payments group pattern at line ~234. The
group auto-expands when the active route is one of its children
and highlights the parent as active. Mirrors the pattern admins
already know from Payments / Movies / Series / Person submenus.

No route or controller changes — purely a navigation tidy-up.

### 1.8.0 — Signup diagnostics + friendly 419 retry

User reports of "I tried to sign up and got an error, no account
appeared in your admin list" turned out — after deep audit — to be
**CSRF token expiry (HTTP 419 "Page Expired")**. Users were leaving
the `/register` form open for hours and submitting after their
session token had rolled. Laravel's generic 419 page told them
nothing; they reported it as a 404 and gave up. Worse: we had no
way to tell *which* of the signup form's six outcomes (success +
five failure modes) each user actually hit, so support was
guessing.

Three changes in this release:

1. **`signup_attempts` log table.** Every public POST to
   `/register` now writes one row recording the outcome
   (`success` | `honeypot` | `recaptcha_fail` | `validation_error`
   | `csrf_expired` | `throttle` | `exception`) plus IP, user-agent,
   email, username and a JSON `details` blob (validation messages,
   exception details, etc.). Writes are wrapped in try/catch so a
   diagnostic write can never crash the actual signup it's
   describing.

2. **Friendly 419 handler.** `App\Exceptions\Handler` now catches
   `TokenMismatchException` on POST `/register` and bounces the
   user back to the form with a flash message: *"Your session
   expired before you could sign up — please try again."* The
   `register.blade.php` view renders the flash as a soft alert so
   the user knows what happened instead of staring at the generic
   "Page Expired" wall. `ThrottleRequestsException` is also caught
   so 429s get logged (default 429 page still shows; we just gain
   visibility).

3. **Admin triage page** at `/admin/diagnostics/signups` (gated to
   `role:admin`). Shows a last-7-days success-rate summary, filters
   by outcome, free-text search over email/username/IP. Now when
   any user reports "signup is broken", it's a 30-second admin-page
   lookup instead of guessing.

Architecture doc:
[docs/architecture/signup-diagnostics.md](docs/architecture/signup-diagnostics.md)
— covers every logging entry point, the privacy notes, and a
"triage checklist" mapping each outcome to the corresponding
support response.

Operator notes for deploy:
- `php artisan migrate` runs the new
  `2026_05_07_120000_create_signup_attempts_table.php` migration.
- No code changes to the signup happy-path behaviour — successful
  signups proceed exactly as before, just with one extra row
  written to `signup_attempts`.
- Consider bumping `SESSION_LIFETIME` in `.env` from the default
  120 minutes to ~720 (12 hours) to cover the most common
  long-idle-tab signup case so 419s become genuinely rare.

### 1.7.8 — Cache invalidation: contributor docs + model warnings

Follow-up to 1.7.7. The fix is correct, but it has one prerequisite
that the codebase wasn't enforcing: every mutation of `status` or
`published_at` on `Show`, `Movie`, `Episode` must go through Eloquent
so the `CatalogCacheObserver` actually fires. Query-builder mass
updates (`Show::query()->update([...])`, `DB::table()->update([...])`)
silently bypass model events — and a future contributor who doesn't
know that could re-introduce the "new content not appearing" bug
without realising it.

Three layers of defense added so this can't happen again:

1. **Inline warnings on the model docblocks** —
   [Show](Modules/Content/app/Models/Show.php),
   [Movie](Modules/Content/app/Models/Movie.php),
   [Episode](Modules/Content/app/Models/Episode.php) now each carry
   an `IMPORTANT — content-cache invalidation contract:` paragraph
   right at the top of the class docblock. Anyone editing these
   files sees the rule before they touch any code.

2. **Architecture doc** at
   [docs/architecture/content-cache-invalidation.md](docs/architecture/content-cache-invalidation.md).
   Comprehensive walk-through: every cache layer + key + TTL, both
   invalidation observers (Catalog + Personalisation), the rationale
   for `Cache::flush()` over surgical forgetting, operator escape
   hatches, and a 5-step diagnostic checklist for "new content not
   appearing" reports. ~150 lines, indexed in the docs/ folder for
   onboarding.

3. **Cross-references** — the `CatalogCacheObserver` docblock now
   points at the architecture doc; the doc points back at every
   related file in a "File map" table at the bottom. Anyone who
   touches one piece of this system can find every other piece in
   two clicks.

No behaviour change in this release — purely defensive. The runtime
fix in 1.7.7 still does all the work.

### 1.7.7 — Newly-published content visible immediately on home rails

User report: publishing a fully-stocked series doesn't make it
appear on the home page or browsable rails for up to an hour —
yet the click-through from the episode notification works fine,
producing inconsistent perception of "is this live or not". On
2026-05-06 the workaround was a manual `optimize:clear` on the
VPS. Risk for end users: they trust the notification, browse for
the same content elsewhere, can't find it, churn.

Root cause traced in conversation:

- The notification path goes `EpisodeAdded → /series/{slug} → scopeDetailVisible`
  — a permissive query, no caching, always fresh.
- The home / browse path goes through
  `TopPicksRecommender` (Top Picks, Smart Shuffle, Fresh Picks,
  Upcoming) which caches per-user shelves under
  `user:{id}:top_picks:v1` etc., TTL 30-60 minutes.
- The existing `PersonalisationCacheObserver` only flushes those
  keys when the *user* takes a signal action (watch / rate /
  watchlist). Admin-side publishes never triggered an
  invalidation, so the cache sat on yesterday's candidate pool.

Fix: new `Modules\Frontend\app\Observers\CatalogCacheObserver`
attached to `Show`, `Movie` and `Episode`. On `created` /
`deleted` it always flushes; on `updated` it flushes only when
`status` or `published_at` actually changed (so saving an
episode's description or runtime doesn't pointlessly trash the
cache). Calls `Cache::flush()` — O(1) regardless of user count,
much simpler than iterating per-user keys, and acceptable
because admin publishes are rare events. Wrapped in a
`try/catch` so a transient cache outage during admin save can't
500 the request.

Sessions are unaffected — Laravel's default config puts sessions
on the SESSION_DRIVER store, distinct from CACHE_DRIVER. Rate
limiter counters do reset, which on this surface is harmless.

Registered in `FrontendServiceProvider::boot()` next to the
existing `PersonalisationCacheObserver` registration.

### 1.7.6 — Notifications: don't alert on episodes whose show is draft

User report: when an episode is published while its parent
series is still in `draft`, users still get the
"New episode" notification. Clicking the notification's
`/series/{slug}` link 404s because the public series detail
route uses `scopeDetailVisible` — which allows `published` and
`upcoming` shows but rejects `draft`. Even for `upcoming` shows
the link lands on a "Coming Soon" stub which is a logical
dead-end (admin announced an episode the user can't watch).

Fix: gate the `EpisodeAdded` event dispatch in
[EpisodeController](Modules/Content/app/Http/Controllers/Admin/EpisodeController.php)
on a new `Show::isPubliclyAvailable()` instance method that
returns true only when the show's `status === 'published'` AND
`published_at <= now()`. Both the `store()` and `update()`
publish-detection paths now check this before firing.

Mirrors the first three conditions of `Show::scopePublished`
without the `whereHas` episode check (which is redundant at
the call site since we're in the act of publishing an episode).

**Known follow-up (not in this release):** episodes published
during a draft window will never trigger a notification, even
after the show eventually goes public. A retroactive listener
on Show status changes (draft → published) could fire
`EpisodeAdded` for any episode whose `published_at` is in the
past. Worth doing if anyone misses these alerts.

### 1.7.5 — Cast picker: drop `required` on hidden modal inputs

Follow-up to 1.7.4. After fixing the nested-form bug, saving a
movie/series with a cast row added still failed silently in
Chrome with the console error:

> An invalid form control with name='first_name' is not focusable.

The cast-picker modal's `first_name` and `last_name` inputs had
`required` attributes. Once the modal is part of the outer movie
form's DOM (which it is — it's a `<div>` inside that `<form>`
since 1.7.4), every required input participates in the outer
form's submit validation. Chrome can't focus a required input
inside a hidden modal, so it aborts the submit with this error
instead of saving the movie.

Removed `required` from both fields. The JS `savePerson()`
handler already validates them with an `if (!first || !last)`
check before AJAX, and the server-side `quickStore()` validation
catches anything the client misses. The red asterisk in the
label is preserved so the UX still signals "this is required".

Episode saves were a red herring — episode views don't include
the cast-picker partial, but the original report bundled both
because the user happened to test them together. With this fix
movie + series + episode saves should all work.

### 1.7.4 — Cast picker: fix nested-form bug breaking outer Save button

User report: after 1.7.x cast-picker work, the "Save movie" /
"Save series" buttons stopped working — clicking them did
nothing. Root cause was my own bug from 1.7.0:
[cast-picker-helpers.blade.php](Modules/Content/resources/views/admin/persons/partials/cast-picker-helpers.blade.php)
contains a Bootstrap modal whose content was wrapped in a
`<form id="jambo-quick-person-form">`. The partial is included
from `movies/form.blade.php` and `shows/form.blade.php`, both of
which are themselves rendered inside the outer
`<form action="store/update">` from `create.blade.php` /
`edit.blade.php`. Result: nested `<form>` tags in the parsed
HTML.

HTML5 disallows nested forms. When the browser's parser hits the
inner `<form>` it silently closes the outer one — orphaning the
"Save" submit button at the bottom of the page from any form.
The button click then has nowhere to submit to and does nothing.

Fix: switched the modal wrapper from `<form>` to `<div>`,
changed the inner submit button to `type="button"`, and rewired
the save flow as a JS click handler. Enter-key submit is
preserved via a `keydown` listener on the modal inputs. No
real form, no nesting, the outer movie/series save button works
again.

The episode save report appears to be unrelated — episode views
don't include the cast-picker partial — but worth re-testing
after this fix to confirm.

### 1.7.3 — Cast picker: pass `isSelect2 => true` to layouts.app

Real root cause of the empty Select2 trigger from 1.7.2: the
Streamit admin layout (`resources/views/layouts/app.blade.php`)
gates the Select2 library load behind an `'isSelect2' => true`
flag passed via `@extends`. Both `select2.min.js` (the library)
and `dashboard/js/plugins/select2.js` (Streamit's init for
`.select2-basic-*` classes) live inside that flag block.

The movie + show CRUD pages were never passing `isSelect2`, so
the library never loaded. The console error
`$(...).select2 is not a function at select2.js:31` was
Streamit's init script firing without the library — the page
must have been getting the init from somewhere else (likely
included by a partial or a stale cache). Either way, our
cast-picker JS poll for `$.fn.select2` timed out at 6 seconds
because the function was genuinely missing.

Added `'isSelect2' => true` to:
- `Modules/Content/resources/views/admin/movies/create.blade.php`
- `Modules/Content/resources/views/admin/movies/edit.blade.php`
- `Modules/Content/resources/views/admin/shows/create.blade.php`
- `Modules/Content/resources/views/admin/shows/edit.blade.php`

Now the layout loads Select2's CSS in `<head>` and the library
+ init scripts before our cast-picker code runs. The polling
fallback from 1.7.2 still protects against future ordering
surprises.

### 1.7.2 — Cast picker: fix script timing + portal dropdown to body

User report after 1.7.1: cast field rendered as a Select2-styled
empty trigger but clicking it did nothing — couldn't search,
couldn't select. Two issues conspiring:

1. **Script-timing race.** The Vite `app.js` bundle (which
   imports jQuery + Select2) ships as a deferred ES module, so it
   executes AFTER inline `<script>` tags in the body. The cast
   picker's synchronous `if (!$.fn.select2) return;` at parse
   time was always bailing out before the bundle finished
   loading, so Select2 never initialised at all. Replaced with a
   100ms-interval poll for `window.jQuery + window.jQuery.fn.select2 + window.bootstrap`,
   capped at 6 seconds.

2. **Dropdown clipped by parent overflow.** Even when Select2
   did init, the dropdown panel rendered inside the input-group
   inside the card-body, both of which have overflow clipping
   that hid the open results list. Added
   `dropdownParent: $('body')` so Select2 portals the dropdown
   to `<body>` and escapes every ancestor's clipping. Combined
   with the 1080 z-index from 1.7.1, the dropdown is now both
   above and outside the rest of the page chrome.

### 1.7.1 — Cast picker: fix Select2 dropdown hidden behind sidebar

User report after 1.7.0 deploy: the Select2 dropdown panel was
opening below the form layer — the input itself worked, results
streamed in, but the visible options list was hidden behind the
publishing-sidebar card and the sticky admin header. Default
Select2 z-index is 1051 which loses to most admin chrome.

Bumped both `.select2-container--open` and the dropdown itself
to 1080 (above Bootstrap's modal layer at 1060) in
[cast-picker-helpers.blade.php](Modules/Content/resources/views/admin/persons/partials/cast-picker-helpers.blade.php).
Also added a 1090 ceiling for Select2 used inside an open
Bootstrap modal so future modal-hosted pickers don't repeat
this issue.

### 1.7.0 — Cast picker: AJAX search + inline person create

The cast row on the movie and series forms used to render every
single Person row as `<option>` elements inside the
`<select name="cast[i][person_id]">` — fine for the demo dataset
of a few hundred names, completely unworkable at the scale this
catalog will reach (millions of rows). Admins reported
near-impossible scrolling and slow form loads as soon as the
table grew past a few thousand persons. Adding a missing person
also forced a full page navigation away from the in-progress
movie/series edit, losing unsaved fields.

Both problems addressed in this release:

**Backend:**
- New `PersonController::search()` returning Select2-shaped JSON
  (`results` + `pagination`). Matches `first_name`, `last_name`,
  `known_for` with `LIKE` and pages 20 results at a time. Even
  an empty query is paginated, so the full table is never
  flattened into a single response.
- New `PersonController::quickStore()` for the "+ New person"
  inline flow. Validates first_name + last_name (required), an
  optional known_for, generates a unique slug, and returns the
  new person in the same shape the search endpoint emits so the
  client can drop it straight into the dropdown.
- Two new routes — `GET /admin/persons/search` and
  `POST /admin/persons/quick`. Registered BEFORE
  `Route::resource('persons', ...)` so they aren't shadowed by
  the resource's wildcard routes.

**Frontend:**
- `cast-row.blade.php` no longer renders all persons. The
  `<select>` is empty by default; only the currently-attached
  person (if any) renders as a single pre-selected option so
  edit forms display the existing value on first paint without
  waiting for an AJAX call. Search-as-you-type populates the
  rest via Select2.
- New shared `cast-picker-helpers.blade.php` partial pulled in
  by both `movies/form.blade.php` and `shows/form.blade.php`.
  Bundles the Select2 stylesheet, the dark-theme overrides, the
  "+ New person" Bootstrap modal, and the JS that wires the
  whole pipeline together. One source of truth for both forms.
- Each cast row gets a "+" button next to the person picker
  that opens the modal scoped to that row. Modal submit POSTs
  via AJAX, on success the new person is appended to that row's
  `<select>` as a pre-selected option and the modal closes —
  the rest of the form stays intact, no navigation, no lost
  edits.
- Validation errors from `quickStore` render as inline
  `.invalid-feedback` under the offending field rather than a
  Laravel redirect (since the form doesn't POST normally).

Episodes intentionally excluded: the `persons` Eloquent
relations (`Person::movies()`, `Person::shows()`) and the
`movie_person` / `show_person` pivot tables don't include an
`episode_person` analogue — episodes don't have their own cast.

### 1.6.3 — Notifications: bulk "Delete all" button

Pairs with the existing "Mark all read" so users with months of
piled-up notifications don't have to click 200 trash icons one by
one. Hard delete (no soft-delete column on the notifications
table), so the click goes through a `confirm()` first.

- New `destroyAll()` method on `NotificationController`.
- New route `DELETE /notifications/all`, named
  `notifications.destroy-all`. Registered BEFORE the
  `DELETE /notifications/{id}` wildcard so Laravel doesn't match
  `id=all` against the single-row destroy and 404.
- "Delete all" button on the user inbox
  ([resources/views/profile-hub/notifications.blade.php](resources/views/profile-hub/notifications.blade.php))
  next to "Mark all read" — visible whenever the inbox has any
  notifications (read or unread).
- Same button on the admin notifications page
  ([Modules/Notifications/resources/views/index.blade.php](Modules/Notifications/resources/views/index.blade.php))
  on the Inbox tab. Both reload the page on success so the
  empty-state placeholder + bell badge re-render cleanly.

### 1.6.2 — Define the missing `.btn-ghost` class

User report: "Mark all as read" button missing from the user
notifications page — they have to click each notification one by
one. Investigation showed the button DOES exist in
[resources/views/profile-hub/notifications.blade.php](resources/views/profile-hub/notifications.blade.php),
the click handler IS wired to the existing
`notifications.mark-all-read` endpoint, and the unread-count
visibility logic IS in place. The issue was purely visual: the
button uses `class="btn btn-ghost btn-sm"`, but `.btn-ghost` is
**referenced 30+ times across the codebase and documented in
docs/ui-guidelines.md as "Ghost = back/cancel only"** —
yet never actually defined as a CSS rule anywhere. Bootstrap
doesn't ship it. The button was rendering as un-styled text
against the dark inbox card, invisible to anyone who wasn't
hovering over it.

Fix: added a `.btn-ghost` rule at the bottom of
[public/frontend/css/jambo-header.css](public/frontend/css/jambo-header.css)
(loaded site-wide by the frontend master layout). Neutral
transparent → grey-on-hover styling that works on both dark and
light surfaces. This makes ALL frontend `btn-ghost` usages
visible at once, not just the notifications button.

Cache-busts via `versioned_asset()` automatically.

Note: admin-side `btn-ghost` buttons (back/cancel in admin
forms) still render unstyled because admin pages load their
CSS from `dashboard/*` instead of `frontend/css/`. We'll mirror
the rule into the admin stylesheet when an admin user reports
the same issue, or proactively if you want me to do it now.

### 1.6.1 — Player: fullscreen actually fills the screen on Android TV

User report: clicking fullscreen on the watch page on Android TV
left ~160px of black on each side instead of filling the panel.
Root cause was two design constraints leaking into fullscreen
mode in [public/frontend/css/player.css](public/frontend/css/player.css):

- `.jambo-player-frame { max-width: 1600px; aspect-ratio: 16/9 }`
- `.jambo-watch-hero  { max-width: 1600px }`

Both kept applying when the element entered `:fullscreen`, so on
a 1920×1080 TV the player was capped at 1600px wide (black bars
on either side). Added explicit `:fullscreen` /
`:-webkit-full-screen` overrides on the frame, the hero wrapper,
the Media Chrome skin, and the underlying `<video>` element so
fullscreen really fills the viewport regardless of which element
the fullscreen API targets. `object-fit: contain` is preserved
on the video so 21:9 movies still letterboxes cleanly instead
of stretching.

Browser cache is auto-busted via the existing
`versioned_asset()` helper that all three watch pages use — the
mtime change appends a fresh `?v=` to the player.css URL.

### 1.6.0 — Perf: on-the-fly image resize + WebP via /img proxy

Network-panel diagnostics on the home page showed **35.4 MB
transferred / 47s to finish** with cache disabled — almost all of
it was raw uploaded images served at full source resolution to
every device. Even with the 1.5.33 browser-cache headers in place,
first visits paid the full bill. This release ships a real
responsive-image pipeline.

**New `/img/{path}` route.** Backed by `league/glide` and a thin
`App\Http\Controllers\ImageProxyController`. Accepts `?w=`, `?h=`,
`?q=`, `?fm=` query params (clamped to safe ranges so a bad bot
can't ask for gigapixels). Sources files from `public/`; caches
resized variants to `storage/app/glide-cache/` so subsequent
requests for the same size+format are served straight from disk.
Imagick is used when available, GD as the universal fallback.

**Two new helpers in `app/helpers.php`:**
- `media_img($value, $width, $fallback?, $legacyDir?)` — returns a
  proxy URL with `?w=N&fm=webp`. External URLs pass through
  unchanged because Glide can't proxy remote sources.
- `media_srcset($value, [320, 640, ...], ...)` — builds a real
  `srcset` string so the browser picks the right size per
  viewport / DPR.

**Card components updated to use both, with `loading="lazy"
decoding="async"` and a `sizes` attribute** so phones get 320w
and desktops/TVs get 640w (or 384w for cast headshots):

- `card-style.blade.php`, `genres-card.blade.php`,
  `top-ten-card.blade.php`, `continue-watch-card.blade.php`,
  `personality-card.blade.php`, `episode-card.blade.php`

**Hero backdrop now routed through the proxy at 1920w WebP**
(`Pages/MainPages/index-page.blade.php`). Previously the heaviest
assets on the page — typically 4K source files at 3-5MB each
across multiple slides. Cuts ~90% per backdrop with no visible
difference at TV / desktop resolutions.

**Expected impact** based on the 35.4MB baseline:
- Hero: ~15MB → ~1MB
- Posters: ~36MB across all carousels → ~2-3MB
- **Total page weight: ~35MB → ~3-4MB on first load**
- Subsequent visits hit the 1y browser cache from 1.5.33

**Operator notes for deploy:**
- `composer require league/glide` adds the dependency.
- PHP must have GD or Imagick. CyberPanel default PHP includes
  GD; verify with `php -m | grep -i gd`.
- `storage/app/glide-cache/` will be created on first request and
  needs to be writable by the web user (`jambo2820`).
- Cache populates lazily as users visit; first request to a
  given size resizes (~150-300ms), subsequent are instant.

### 1.5.33 — Perf: browser caching, lazy-load posters, drop dead deps

Targeted fixes for users on weak networks (Android TV being the
loudest complaint) where every navigation was re-downloading every
asset.

1. **Browser-cache static assets via `public/.htaccess`.** Hashed
   Vite output (`/build/assets/*.js`, `*.css`, fonts) now ships with
   a 1-year `Cache-Control: public, max-age=31536000, immutable` —
   safe because the filename hash changes on every release, so it
   self-invalidates. Static images get a 7-day window. HTML/Laravel
   responses are deliberately untouched so per-user session state
   keeps working. Wrapped in `<IfModule>` blocks so the file no-ops
   if `mod_expires` / `mod_headers` aren't loaded.

2. **Lazy-load below-the-fold poster images.** `card-style` (the
   most-used poster card on the home page) and `genres-card` now
   carry `loading="lazy" decoding="async"`. Existing `top-ten-card`,
   `continue-watch-card`, `personality-card`, and `episode-card`
   already had it, so the home page is now consistent.

3. **Dropped two unused dependencies from package.json.**
   `popper.js@1.16` (legacy v1, superseded by `@popperjs/core@2`
   which `app.js` actually imports) and the typo `i@0.3.7`. Run
   `npm install` to update lockfile.

### 1.5.32 — SEO: live diagnostic for "tag not detected on website"

The Google Tag field already accepted `G-XXXXXXXXXX` IDs and the
controller already extracted the ID when an admin pasted the
entire `<!-- Google tag (gtag.js) -->` snippet — so storage was
fine. The recurring "Google says my tag isn't detected" issue
was almost always one of two silent gates:

1. **Master switch is OFF** (the default). Saving a tag ID alone
   doesn't render anything; you also have to flip "Enable
   analytics tracking" on.
2. **You're testing it logged in as admin** while "Don't track
   logged-in admins" is ON (also default). The tag IS being
   rendered for anonymous visitors, but suppressed in your own
   browser, so View Source comes up empty.

Added a live diagnostic block at the top of the Analytics card
that shows, at a glance:

- ✓/✗ Tag ID saved (and the value)
- ✓/✗ Master switch ON/OFF
- ✓/✗ Whether an anonymous visitor would see the tag right now
- ✓/✗ Whether you (the logged-in admin) would see it, with an
  explicit incognito hint when admin exclusion is hiding it
- A collapsible "Show the exact snippet being injected" panel
  with the rendered gtag.js snippet for copy/paste verification

Also relabelled the field from "Google Analytics 4 — Measurement
ID" to "Google Tag (gtag.js) — Measurement ID" and clarified in
the help text that pasting the entire snippet works too.

### 1.5.31 — Notifications: use "series" not "shows" + fix dead URLs

The notification copy still said "show" / "shows" in places
that reach end users, even though every other surface of the
app calls them "series". Fixed:

- **ShowAddedNotification** — title "New **show** added" →
  "New **series** added"; action button label "Open show" →
  "Open series".
- **NotificationSetting::definitions()** (admin Settings tab) —
  the toggle labelled "TV **show** added" → "TV **series**
  added"; description "...existing **show**" → "...existing
  **series**".
- **WelcomeUserNotification** — "movies, **shows**, and add
  favourites" → "movies, **series**, and add favourites".

While editing the same notification classes, fixed a related
URL bug: action_url on Show/Episode/Season-added notifications
pointed at `/tv-show-detail/{slug}` (which 301-redirects, an
extra hop) and fell back to `/tv-shows` (which doesn't exist —
404). Rewrote both to `/series/{slug}` and `/series` so the
"Open series" button lands cleanly.

Note for ops: existing notification rows in the database have
the OLD title/message baked in at the time they were sent —
this release only affects notifications dispatched from now on.
The admin Settings tab labels update immediately because they
render live from PHP rather than storage.

### 1.5.30 — VJ pages: use "series" instead of "shows"

The VJ overview (`/vj/{slug}`) and the VJ series-detail
(`/vj-series/{slug}`) pages were rendering "shows" via
`__('streamTag.shows')`. Site-wide convention is "series" — the
matching key `streamTag.series` already exists in
`lang/en/streamTag.php`. Switched both call sites.

### 1.5.29 — Share-link previews on the watch + watchlist-play pages

Follow-up to 1.5.28: extended the per-page SEO metadata to the
two player pages. The realistic share moment isn't from the
detail page (where the user is just browsing) — it's from the
player itself, when someone is enjoying a title and wants to
send it to a friend.

- `/watch/{slug}` (movie player): now sets the same `<title>`,
  og:image, og:description as the movie detail page, scoped to
  the movie being watched.
- `/watchlist/{slug}` (in-playlist movie player): same pattern,
  reading from `$watchable` (the resolved Movie) and `$poster`
  (the controller already computes both for the player chrome).

Episode pages (`/episode/...`) were already covered in 1.5.28.

### 1.5.28 — Share-link previews for movies, series, episodes

Until now, sharing a link to a movie/series/episode page produced
a generic preview ("Jambo" + the site-wide default image +
default description) — the per-page SEO meta tags weren't being
filled in correctly. After this release, a link to e.g.
`/movie-detail/some-movie` previews on WhatsApp / Telegram /
Twitter / Facebook with:

- **Title:** "Movie Title - Jambo"
- **Description:** the movie's synopsis (truncated to 200 chars)
- **Image:** the movie's backdrop (or poster fallback)

Three concrete fixes:

1. **Movies + Series detail pages had a wrong field name.** They
   were reading `$movie->description` / `$show->description`,
   neither of which exists on the model. The actual prose field
   is `synopsis`. Result: every detail page was silently falling
   back to the global default description. Now uses `synopsis`.

2. **No `<title>` was set on detail pages.** The browser tab
   read just "Jambo" and the og:title fallback inherited that.
   Now passes `'title' => $movie->title` (and `$show->title`)
   into the layout's `@extends`.

3. **Episode pages had zero SEO metadata.** Added title (formatted
   as "Show — S01E03: Episode Title"), description (episode
   synopsis with show synopsis fallback), and image (episode still
   with show backdrop / poster fallback).

Image URLs now run through `media_url()` before reaching head-tags
so legacy bare filenames (Streamit-template-era values like
`media/foo.webp`) get resolved to public asset paths instead of
becoming broken absolute URLs at the root.

### 1.5.27 — VJ overview hero showed wrong titles (composer collision)

The combined VJ overview at `/vj/{slug}` (added in 1.5.24) was
rendering a hero of titles that didn't belong to the visited VJ.
Cause: the controller passed `$heroItems` scoped to the VJ, but
`SectionDataComposer` registers a global `heroItems` for every
`frontend::Pages.*` view (the homepage's mixed movies+series
banner). View composer data is merged in **after** controller
data, so the global feed silently overwrote the VJ's hero.

Renamed the controller variable to `$vjHeroItems` (and updated
the overview view to consume the new name) so there's no key
collision. Left a comment at both ends explaining why the
unusual name matters, since the trap is invisible — you only
notice when the hero shows wrong content.

### 1.5.26 — Add missing streamTag.vjs translation key

The new homepage VJs slider header rendered the literal string
"streamTag.vjs" because the translation key was missing. The
`?? 'VJs'` fallback in the template was dead code — Laravel's
`__()` returns the key itself (a truthy string) when a
translation is missing, so the null-coalesce never fires.
Added `'vjs' => 'VJs'` next to `'genre' => 'Genres'` in
`lang/en/streamTag.php`.

### 1.5.25 — Hotfix: homepage 500 from misaligned withCount key

The homeVjs query in 1.5.24 had two spaces between "shows" and
"as" in the withCount alias key (`'shows  as shows_count'`),
visually aligning it with the `'movies as movies_count'` line
above. Production Laravel parsed the literal string as the
relation method name, throwing:

> Call to undefined method
> Modules\\Content\\app\\Models\\Vj::shows  as shows_count()

Collapsed to a single space. No other functional change.

### 1.5.24 — VJs: homepage slider + combined overview at /vj/{slug}

Two related changes that together promote VJs from a hidden
secondary axis (only reachable from the per-row "View All" links
on /movie and /series) to a first-class browse axis with its own
landing page.

**1. New homepage VJs slider.** Inserted into `ott-page` (the
homepage) between **Top 10 Series to Watch** and **Only on Jambo**.
Mirrors the existing genres slider pixel-for-pixel — same
`card-genres-grid` partial, same swiper config, same View All
behaviour. Cards link to the new combined VJ overview page below.
Data wired by `SectionDataComposer` as `$homeVjs` (top 12 VJs
ranked by combined published movies + shows count). Each card uses
the new `Vj::featured_image_url` accessor, which falls back through
the VJ's most recent published movie / show backdrop / poster —
mirrors the `Genre::featured_image_url` accessor exactly.

**2. Route restructure — `/vj/{slug}` is now the combined overview.**

- `/vj/{slug}` (NEW) → `vjOverview()` → hero rotating through the
  VJ's newest titles regardless of type (movies + series mixed),
  then a Movies rail and a Series rail below it. Each rail caps at
  20 with View All linking to the type-scoped catalogue page.
- `/vj-movie/{slug}` (formerly `/vj/{slug}`) → `vjMovieDetail()`
  → unchanged movies-only catalogue, organised by genre with
  Load More.
- `/vj-movie/{slug}/more` (formerly `/vj/{slug}/more`) →
  `vjMovieGenreLoadMore()`.
- `/vj-series/{slug}` → unchanged.
- `/vj/{slug}/more` 301-redirects to `/vj-movie/{slug}/more` so
  any external bookmarks pointing at the old movies-only Load
  More endpoint keep working.

The route name `frontend.vj_detail` was kept on the new combined
overview, so the ~5 internal callers that already used it (homepage
sitemap, mobile-footer, vj-carousel) all naturally land on the
combined page now — which is what every caller actually wanted
("show me this VJ"). The one place that genuinely needed
movies-only — vj-carousel's "View All" link on a movies row — was
explicitly switched to `frontend.vj_movie_detail`.

No DB migration; no data backfill; existing bookmarks at the old
`/vj/{slug}` URL now land on the more-useful combined overview
instead of the movies-only page.

### 1.5.23 — Cast: add "Actress" alongside "Actor" everywhere

Admin cast rows only had a single "Actor" option for performers,
which forced female performers to be miscredited. Added "Actress"
as a second performer role and made every cast/Starring section
treat the two as one group.

**Admin** (`admin/movies/partials/cast-row.blade.php`):
- New `<option value="actress">Actress</option>` in the role
  dropdown, placed right after Actor.
- Character-name placeholder updated to "actors / actresses".

**Validation** (`StoreMovieRequest`, `StoreShowRequest`):
- `cast.*.role` `in:` rule now accepts `actress` in addition to
  the previous five roles.

**Frontend — Starring sections include both:**
- `Pages/Movies/detail-page.blade.php` and
  `Pages/TvShows/detail-page.blade.php`: cast filter changed
  from `role === 'actor'` to
  `in_array(role, ['actor', 'actress'])`. Per-card fallback
  label (when no `character_name` is set) now reflects the
  actual role — an actress without a character name shows
  "Actress" instead of being mislabeled "Actor".
- `Pages/TvShows/episode-page.blade.php`: same filter change in
  the Read-more modal.
- `Pages/MainPages/index-page.blade.php`: hero banner's
  `$heroCast` now picks up actresses too.

**Recommendations** (`TopPicksRecommender`,
`SectionDataComposer`):
- All four `wherePivotIn('role', […])` eager-loads now include
  `actress`, so personalised rails and the homepage hero pull
  actress credits the same way they pull actor credits.

No DB migration — `role` is a free-form string column, so
existing rows are unaffected and "actress" works the moment the
admin saves a cast row with that role.

### 1.5.22 — VJ rails: fix "Nothing here yet" on every VJ except the first

Follow-up to 1.5.21 — that release exposed the underlying bug
rather than fixing it.

**Symptom:** on `/movie` (and the genre/series twins), only the
first VJ row rendered cards; every other VJ row showed
"Nothing here yet." even when those VJs had published movies
visible on their own `/vj/{slug}` detail page.

**Root cause:** classic Eloquent eager-load gotcha. The four
`topVjsFor*()` loaders did:

```php
->with(['movies' => fn ($q) => $q->published()->limit(20)])
```

Eloquent doesn't loop per parent — it issues a single
`SELECT … WHERE vj_id IN (1,2,3,…) LIMIT 20`. So the top-ranked
VJ in the result set hogs all 20 rows; every subsequent VJ's
relation collection comes back empty, and the carousel partial
renders its `@empty` branch.

**Fix:**
- Removed `->limit(20)` from all four `topVjsFor*()` eager-loads.
- Moved the per-VJ cap into `vj-carousel.blade.php` via
  `$items = $items->take(20)`, where it actually applies per VJ.

The eager-load now fetches every published title for every
selected VJ in one query, which is fine at this scale (worst
case: 100 VJs × ~30 titles = 3 000 small rows).

### 1.5.21 — VJ rails: render every VJ + bump per-VJ items 10 → 20

Two related bugs on the VJ-rail pages (`/movie`, `/series`,
`/geners/{slug}/vjs`):

1. **Some VJs never appeared.** The four entry points
   (`movie()`, `tv_show()`, `genreVjs()`, `genreVjsShows()`)
   only rendered the **top 5 VJs by catalogue size** server-side,
   leaving smaller VJs (1–3 titles) reachable only via the Load
   More button. Content team's expectation is "every VJ I've
   published for is visible on first load". Bumped the initial
   render cap to **100 VJs** — covers the foreseeable catalogue
   while keeping a hard ceiling. The Load More button stays as
   a safety net for anything beyond.

2. **The carousels that did appear couldn't scroll.** Each VJ
   row is a swiper-card that surfaces 7 cards per view at
   desktop. The eager-load on the `topVjsFor*()` loaders capped
   each VJ at 10 latest titles — which left only 3 hidden cards
   to swipe to (sometimes zero, since `watchOverflow` disables
   nav when `slidesPerView >= slides`). Bumped the per-VJ
   eager-load to **20** in all four sibling loaders so the rails
   behave consistently and have meaningful slide depth.

Files: `Modules/Frontend/app/Http/Controllers/FrontendController.php`
(`topVjsForPage`, `topVjsForGenre`, `topVjsForShowsByGenre`,
`topVjsForShowsPage`, plus the four call sites in `movie()`,
`tv_show()`, `genreVjs()`, `genreVjsShows()`).

Note for content team: a VJ still only appears when at least one
of their movies is `status = Published` AND `published_at <= now()`.
A VJ with assigned-but-draft movies is correctly hidden — publish
the movies and the VJ row appears.

### 1.5.20 — fix "Undefined variable $items" on /movie Load More

`FrontendController::moreVjsForMoviesPage()` (the AJAX endpoint
behind the Load More button on /movie) was passing the wrong
variable name to the `vj-carousel` partial — `'movies'` instead
of `'items'`. The partial uses `@forelse ($items as $item)`, so
every Load More click 500'd with "Undefined variable $items"
deep in `storage/framework/views/...`.

Three sibling endpoints (genreVjs, genreVjsShows,
moreVjsForShowsPage) were already correct; only this one had
drifted. Fixed; also added `contentKind => 'movie'` for parity
with the others, even though the partial defaults to that.

### 1.5.19 — spam-folder notices during sender-reputation warm-up

We're sending mail from a brand-new VPS IP, so Gmail / Outlook / etc.
spam-fold our verification + reset-link emails until reputation
builds (typically 2–4 weeks of users marking "Not spam"). SPF, DKIM,
DMARC and rDNS are all correctly configured — this is purely IP
reputation cold-start, not a misconfiguration.

To stop new signups bouncing off "I never got the email":

- `auth/verify-email` shows a prominent yellow notice: *"Don't see it
  within a minute? Check your Spam or Junk folder. Marking it 'Not
  spam' tells your provider to deliver future emails to your inbox."*
- `auth/forgot-password` shows the same notice immediately after a
  reset-link is requested (only when the success flash is present so
  the empty initial state stays clean).
- The post-registration welcome flash now names the email address and
  prompts the user to check spam if it doesn't arrive in a couple of
  minutes.

These notices have an inline comment with a removal hint: *"once
we've been live ~6 weeks and the bounce-to-spam rate has dropped"*.

### 1.5.18 — password reset 405 fix + queue worker for verify-email

Two pre-existing production bugs (both predate the recent security
work; just only got noticed now).

- **Password reset returned 405 Method Not Allowed.** The
  `auth/reset-password.blade.php` form posted to `password.update`
  (a PUT route used for in-app password change from the security
  tab) instead of `password.store` (the public reset POST handler).
  Fixed.
- **Email verification mail wasn't reaching new signups.** The
  `QueuedVerifyEmail` notification implements `ShouldQueue` so the
  email goes onto the `jobs` table; without a queue worker draining
  it, the row sits forever. Forgot-password and the other
  ChannelGated notifications send synchronously which is why those
  worked. Added a scheduled `queue:work --stop-when-empty
  --max-time=55` running every minute via the existing scheduler
  cron (no supervisord setup needed).

After deploy: drain the existing backlog once with
`php artisan queue:work --stop-when-empty` so any verify-emails
queued while the worker was missing actually go out, then the
new scheduler line keeps it draining automatically.

### 1.5.17 — WhatsApp / Telegram social-preview reliability

WhatsApp + Telegram are stricter than LinkedIn / Facebook on the
Open Graph image tag set. Without the supplementary tags they
sometimes silently drop the image. Added:

- `og:image:secure_url` — pins the HTTPS variant
- `og:image:type` — derived from the URL's file extension
  (jpg/jpeg/png/webp/gif → standard MIME)
- `og:image:alt` — defaults to the page title

Width/height are intentionally not emitted — admin-pasted URLs
(Dropbox / Contabo / external CDNs) make accurate dimensions
impossible without an extra HEAD request per pageview. Pages that
need them can `@push('seo:head', ...)` per-template.

Also: WhatsApp caches link previews for ~7 days. To force a
re-scrape, paste the URL through Facebook's Sharing Debugger
(developers.facebook.com/tools/debug) and click "Scrape Again",
or share with a `?v=N` query param to fool the cache.

### 1.5.16 — three quality fixes

- Subscription-expired notification (and any other ChannelGated
  notification) now addresses the recipient by their first name —
  "Hi Akram," rather than "Hi there," — and the admin-facing
  message names the affected user ("Jane Smith's subscription has
  expired") instead of the legacy "User #11" label. Falls back
  through username and email-local-part if first/last aren't set.
- Open Graph / Twitter Card image now renders correctly on shared
  links. Previously even with a featured image set, the meta tag
  was empty: managed Pages, Movie detail, and TV-show detail views
  weren't yielding `seo:image`. They do now (movies + shows prefer
  the wider backdrop_url for `summary_large_image`). The head-tags
  partial also force-converts relative paths to absolute URLs since
  Facebook silently drops relative `og:image` values.
- Contact form now ships the same honeypot + reCAPTCHA defence as
  register/login/forgot-password. Throttle was already in place; the
  bot-defence component is now rendered in the form and the controller
  silently flashes success on honeypot trigger.

### 1.5.15 — anti-bot signup defences + admin reCAPTCHA toggle

Production was seeing low-volume but persistent automated signups
(~1/hr) that never confirmed their email. None ever exploited
anything — they just polluted the users table. Layered defences,
no schema changes, no module changes.

Defences (all unconditional, free, zero UX friction)
- **Honeypot field** in register / forgot-password / login forms.
  Hidden from humans (CSS off-screen + tabindex=-1 + autocomplete=off
  + aria-hidden); bots fill it. Server silently accepts the request
  but creates no user / sends no reset link.
- **Throttle** on `POST /register` and `POST /forgot-password` —
  5 attempts per IP per 10 minutes.
- **Nightly cleanup** `jambo:purge-unverified --days=7` runs at
  03:10 UTC and deletes accounts with `email_verified_at IS NULL`
  older than 7 days. Skips anyone with the `admin` role for safety.
  `--dry-run` lists candidates without deleting.

Optional reCAPTCHA (v2 + v3, configurable from admin)
- New "Google reCAPTCHA" card in `/admin/settings`: enable toggle,
  v2/v3 selector, site key, secret key (encrypted at rest), v3
  score threshold.
- Site key + secret are stored in the settings table. Secret is
  Crypt-encrypted on save like the SMTP password. Blank secret on
  re-save = "keep existing".
- Verifies on register / login / forgot-password when enabled. When
  disabled (the default), pass-through — only honeypot + throttle
  run. No code changes needed to enable, just paste keys in admin.
- New `App\Services\RecaptchaService`, new
  `<x-auth.bot-defence />` blade component shared across the three
  forms.

### 1.5.14 — security pass + diagnostics

Pre-launch hardening sweep. No data migrations, no module changes — all
changes are code/config/views; existing movies, episodes, payment
orders, users, settings, branding, and gallery files are untouched.

Security
- Pesapal IPN/callback no longer trust the `OrderTrackingId` from the
  request — re-poll always uses the tracking ID stored at order
  creation. Closes the order-forging path where an attacker paired
  their own pending merchant_reference with someone else's completed
  tracking ID. `payment/ipn` is POST-only.
- `db:seed` no longer creates `admin@demo.com / 12345678` unless
  `IS_DUMMY_DATA=true`.
- `confirm-password` is rate-limited (`throttle:6,1`).
- Notifications Guest model uses an explicit `$fillable = ['id']`.
- `SystemUpdate.allow_users_id` reads from `JAMBO_UPDATER_USER_IDS`
  env so production can pin updater access to specific user IDs.
- New `App\Http\Middleware\SecurityHeaders` adds X-Frame-Options,
  X-Content-Type-Options, Referrer-Policy, Permissions-Policy, and
  HSTS to every response.
- CORS narrowed to the production origin (and localhost dev hosts),
  configurable via `CORS_ALLOWED_ORIGINS` env. No more wildcard.
- SVG dropped from branding (logo/favicon/preloader) and Files Gallery
  upload allowlists. Existing SVG files keep displaying — only new
  uploads are blocked.
- SEO verification file upload rejects bodies containing
  script/iframe/event-handler patterns or `javascript:` URIs.
- New `App\Support\HtmlSanitizer` cleans Quill-authored Pages content
  on save (allow-list of safe tags + attributes, drops `on*=`,
  `javascript:`, etc.). No new composer dependency.
- `axios` bumped to `^1.7.7+` (resolved at `^1.15.x`) to clear the
  CVE-2023-45857 / CVE-2024-39338 class.
- SystemUpdate verifies the downloaded archive's SHA-256 against the
  manifest when present, and the zip extractor enforces a realpath /
  no-`..` containment check (zip-slip guard).
- Socialite `same-email` merge now auto-sets `email_verified_at` when
  matching an unverified local row, since Google has already verified
  the address — closes the email-squatting takeover path.

Admin
- New "Error Log" admin page (after SEO menu): tail any file under
  `storage/logs/` with a per-file Clear button.
- New "System Status" admin page: app/Laravel/PHP versions, debug
  flag, environment, runtime drivers, DB connectivity, free disk,
  storage symlink presence, PHP extensions, and module enable map.

## [Unreleased](https://github.com/laravel/laravel/compare/v10.2.7...10.x)

## [v10.2.7](https://github.com/laravel/laravel/compare/v10.2.6...v10.2.7) - 2023-10-31

- Postmark mailer configuration update by [@ninjaparade](https://github.com/ninjaparade) in https://github.com/laravel/laravel/pull/6228
- [10.x] Update sanctum config file by [@ahmed-aliraqi](https://github.com/ahmed-aliraqi) in https://github.com/laravel/laravel/pull/6234
- [10.x] Let database handle default collation by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/laravel/pull/6241
- [10.x] Increase bcrypt rounds to 12 by [@valorin](https://github.com/valorin) in https://github.com/laravel/laravel/pull/6245
- [10.x] Use 12 bcrypt rounds for password in UserFactory by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/laravel/pull/6247
- [10.x] Fix typo in the comment for token prefix (sanctum config) by [@yuters](https://github.com/yuters) in https://github.com/laravel/laravel/pull/6248
- [10.x] Update fixture hash to match testing cost by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/laravel/pull/6259
- [10.x] Update minimum `laravel/sanctum` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/laravel/pull/6261
- [10.x] Hash improvements by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/laravel/pull/6258
- Redis maintenance store config example contains an excess space by [@hedge-freek](https://github.com/hedge-freek) in https://github.com/laravel/laravel/pull/6264

## [v10.2.6](https://github.com/laravel/laravel/compare/v10.2.5...v10.2.6) - 2023-08-10

- Bump `laravel-vite-plugin` to latest version by [@adevade](https://github.com/adevade) in https://github.com/laravel/laravel/pull/6224

## [v10.2.5](https://github.com/laravel/laravel/compare/v10.2.4...v10.2.5) - 2023-06-30

- Allow accessing APP_NAME in Vite scope by [@domnantas](https://github.com/domnantas) in https://github.com/laravel/laravel/pull/6204
- Omit default values for suffix in phpunit.xml by [@spawnia](https://github.com/spawnia) in https://github.com/laravel/laravel/pull/6210

## [v10.2.4](https://github.com/laravel/laravel/compare/v10.2.3...v10.2.4) - 2023-06-07

- Add `precognitive` key to $middlewareAliases by @emargareten in https://github.com/laravel/laravel/pull/6193

## [v10.2.3](https://github.com/laravel/laravel/compare/v10.2.2...v10.2.3) - 2023-06-01

- Update description by @taylorotwell in https://github.com/laravel/laravel/commit/85203d687ebba72b2805b89bba7d18dfae8f95c8

## [v10.2.2](https://github.com/laravel/laravel/compare/v10.2.1...v10.2.2) - 2023-05-23

- Add lock path by @taylorotwell in https://github.com/laravel/laravel/commit/a6bfbc7f90e33fd6cae3cb23f106c9689858c3b5

## [v10.2.1](https://github.com/laravel/laravel/compare/v10.2.0...v10.2.1) - 2023-05-12

- Add hashed cast to user password by @emargareten in https://github.com/laravel/laravel/pull/6171
- Bring back pusher cluster config option by @jesseleite in https://github.com/laravel/laravel/pull/6174

## [v10.2.0](https://github.com/laravel/laravel/compare/v10.1.1...v10.2.0) - 2023-05-05

- Update welcome.blade.php by @aymanatmeh in https://github.com/laravel/laravel/pull/6163
- Sets package.json type to module by @timacdonald in https://github.com/laravel/laravel/pull/6090
- Add url support for mail config by @chu121su12 in https://github.com/laravel/laravel/pull/6170

## [v10.1.1](https://github.com/laravel/laravel/compare/v10.0.7...v10.1.1) - 2023-04-18

- Fix laravel/framework constraints for Default Service Providers by @Jubeki in https://github.com/laravel/laravel/pull/6160

## [v10.0.7](https://github.com/laravel/laravel/compare/v10.1.0...v10.0.7) - 2023-04-14

- Adds `phpunit/phpunit@10.1` support by @nunomaduro in https://github.com/laravel/laravel/pull/6155

## [v10.1.0](https://github.com/laravel/laravel/compare/v10.0.6...v10.1.0) - 2023-04-15

- Minor skeleton slimming by @taylorotwell in https://github.com/laravel/laravel/pull/6159

## [v10.0.6](https://github.com/laravel/laravel/compare/v10.0.5...v10.0.6) - 2023-04-05

- Add job batching options to Queue configuration file by @AnOlsen in https://github.com/laravel/laravel/pull/6149

## [v10.0.5](https://github.com/laravel/laravel/compare/v10.0.4...v10.0.5) - 2023-03-08

- Add replace_placeholders to log channels by @alanpoulain in https://github.com/laravel/laravel/pull/6139

## [v10.0.4](https://github.com/laravel/laravel/compare/v10.0.3...v10.0.4) - 2023-02-27

- Fix typo by @izzudin96 in https://github.com/laravel/laravel/pull/6128
- Specify facility in the syslog driver config by @nicolus in https://github.com/laravel/laravel/pull/6130

## [v10.0.3](https://github.com/laravel/laravel/compare/v10.0.2...v10.0.3) - 2023-02-21

- Remove redundant `@return` docblock in UserFactory by @datlechin in https://github.com/laravel/laravel/pull/6119
- Reverts change in asset helper by @timacdonald in https://github.com/laravel/laravel/pull/6122

## [v10.0.2](https://github.com/laravel/laravel/compare/v10.0.1...v10.0.2) - 2023-02-16

- Remove unneeded call by @taylorotwell in https://github.com/laravel/laravel/commit/3986d4c54041fd27af36f96cf11bd79ce7b1ee4e

## [v10.0.1](https://github.com/laravel/laravel/compare/v10.0.0...v10.0.1) - 2023-02-15

- Add PHPUnit result cache to gitignore by @itxshakil in https://github.com/laravel/laravel/pull/6105
- Allow php-http/discovery as a composer plugin by @nicolas-grekas in https://github.com/laravel/laravel/pull/6106

## [v10.0.0 (2022-02-14)](https://github.com/laravel/laravel/compare/v9.5.2...v10.0.0)

Laravel 10 includes a variety of changes to the application skeleton. Please consult the diff to see what's new.
