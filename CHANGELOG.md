# Release Notes

This file now covers two things that ship separately. **Jambo** is the webapp
and its version is `version.txt`, which the in-app updater compares against an
update manifest. **Jambo App** is the Android app in `mobile/`, versioned in
its own `app.config.ts`. An app release does not bump `version.txt`: doing so
would advertise a webapp update containing no webapp change.

## Jambo App

### Unreleased — Google sign-in, wired for the first time

Rio, on the live build the hour it was deployed: *"login with google is not
working"*.

🔴 **It never could.** `SignInScreen`'s Google button was
`onPress={() => navigation.navigate('SignIn')}` — it navigated to the screen it
was already on, so tapping it did nothing whatsoever. Not a broken integration:
an integration that was never written, behind a control that looked finished.

**The server half has been correct since 1.8.23.** `POST /api/v1/auth/google`
verifies the ID token with Google, checks the audience, refuses an address
Google has not verified, honours two-factor and issues a device-bound session.
The only missing piece was the app asking Google for a token.

**Why nobody caught it.** The button is drawn from `features.google_sign_in`,
which is `(bool) config('services.google.client_id')` — set on the live server
for the website's own Google login, unset on every dev machine. So the dead
control was invisible locally and appeared the moment the app first spoke to
production.

**The system browser, not the WebView the app already has.** Google refuses
OAuth inside embedded WebViews, so the `react-native-webview` that PesaPal
checkout uses cannot be reused. `expo-auth-session` opens a Chrome Custom Tab,
which is what Google asks for and what lets a viewer use the Google session
they are already signed into.

**The audience check now reads a list, and that is a widening rather than a
weakening.** Google keys an Android OAuth client to the package name and the
signing certificate and puts THAT id in the token, so the app and the website
legitimately present different audiences. The check is still an allow-list of
clients we own; it just has more than one entry. It reads `client_ids` and
`client_id` together, so a config path that sets only one cannot silently
disarm it.

🔴 **A guard no test was asking, found by mutation.** Removing the `true`
from `in_array(..., true)` changed nothing: every audience in the suite is a
plain non-numeric string, where loose and strict comparison agree. The state
the strict flag defends against is a non-string — in PHP,
`true == 'any-client-id'` is TRUE, so under a loose comparison an `aud` of
`true` would match the first configured client and mint a session. Forced into
its own test.

**Both ends must be able to finish before the button is drawn.** The server
saying yes was never sufficient, and that is exactly how this shipped dead. A
build with no Google client configured now hides it.

**This cannot be tested without a build.** `expo-web-browser` is not in the
existing dev client, and the Android OAuth client has to be created against the
app's signing certificate.

### Unreleased — One cast page, and round avatars on both surfaces

Two instructions from Rio, an hour apart, and the second reverses part of what
the first slice had just matched.

**`/all-personality` is gone; `/cast-list` is the survivor.** *"i have noticed
the /all-personality should be /cast-list so we can remove the
/all-personality for casts … on both App and mobile"*. He was right and the
controller showed why: `cast_list()` and `all_personality()` ran the SAME query
byte for byte and differed only in which view they rendered — one card per row
against three. `/cast-list` survives because it is the page the product already
navigates to: the site's own sidebar links to it and marks it active, while the
other was reachable from a single "View All" on the home rail. The route, the
controller action and the duplicate view are deleted, the rail links to
`/cast-list`, and the app's screen is renamed with it and **re-measured**,
because the two pages showed the same people in different grids.

`all-personality` stays in the reserved-username list. Freeing a reserved name
is the one-way door: somebody takes it, the route comes back, and the route
shadows their profile.

**The home rail's people are round, smaller and four across, on both
surfaces.** *"make the people's cards like this and smaller on home for both
webapp and mobile"*. The website changed first and the app's numbers were then
read back off it, so the two cannot drift: an 89.5 slide holding a 75.5 circle,
the name at 12px, `data-mobile="4"`.

🔴 **Scoping that shape took two attempts, and a probe caught the first
one.** `cards/personality-card.blade.php` is included FIVE times — this rail
plus the cast and crew rows on the movie and TV detail pages — so the override
was written against `.favourite-person-block` to avoid touching the partial.
The detail pages wrap their rows in that same class. A probe of a movie page
read `border-radius: 50%` where nothing should have changed, and the rule is
now scoped to a `--home` modifier added for it. **The detail pages still draw
the rounded 1/1.3 portrait**, which is what "on home" means.

The radius carries `!important` on one line, and only that line: the card wears
Bootstrap's `rounded-3`, every Bootstrap utility ships `!important`, and a
plain declaration loses however specific the selector is.

**The cast list page keeps its rectangle** for the same reason — `/cast-list`
is not home. Its card is the site's 1/1.3 portrait, three across.

### Unreleased — The VJs and Personality rails, at the website's design

Rio, over a screenshot of the two: *"fix these sections"*. Both were drawn at
the app's own numbers rather than the site's, and both were missing the
"View All" the site's heading carries. It is the finding the Genres rail closed
on 2026-09-10 and explicitly left open for these two.

**A VJ card is a genre tile, and that is the website's doing rather than a
choice made here.** `sections/vjs.blade.php` includes
`cards/card-genres-grid` — the same partial the genres rail includes — so on the
site a VJ is a 5:3 still with the name centred over it. The app had a third
card of its own: a 16:9 crop, the name printed underneath, and **a "N titles"
line the site does not draw at all**. `VjCard` is now the tile, so the two
rails cannot drift apart again. The count is not lost — it is still announced
to a screen reader, which has room a 164pt tile does not.

**A personality card is a rounded rectangle, not a circle.**
`cards/personality-card.blade.php` renders `rounded-3` on a portrait the
stylesheet gives `aspect-ratio: 1 / 1.3`, measured at 165 x 214.5 with a 16pt
gap under it and the name at 14px/500. The app drew a round portrait — which is
what a streaming app's cast row usually looks like, and is not what this one
looks like.

**Both rails show two cards per view**, which is `data-mobile="2"` on both: a
179pt slide in a 358pt rail. The app was taking the VJs width from the poster
rail's card count and drawing four personalities across.

**Both headings now carry "View All", because only the `titles` arm forwarded
it.** The VJs one goes to the movie listing, and an earlier session recorded
that as an oddity on the website. Reading the controller settles it: `/movie`
is built out of VJ carousels, one row per VJ, paged by
`frontend.movie_more_vjs` — so the movies page IS where all the VJs are, and
there is no VJ index for the app to be missing.

**The Personality rail's "View All" is deliberately not wired yet.** Its
destination is `/all-personality`, a real page with no app screen and no
endpoint behind it, so the rail draws no control rather than one that leads
nowhere.

### Unreleased — The account area becomes a hub

`docs/plans/account-area-audit.md` §2.2. **Every account screen was a dead end
but for the back arrow.** Once inside Wallet the only ways out were back and
the Android gesture, while the website keeps its sidebar visible on every hub
page — Wallet to Security is one click there and was four taps here.

It was the one item of the audit left deliberately unbuilt, because it changes
navigation Rio had already signed off. Put to him on 2026-09-10 with the two-tap
path drawn out; he chose to build it.

**One `headerRight`, spread into the seven account screens.** Profile,
Security, Devices, Membership, Billing, Wallet and Refer & Earn each carry the
header's own account avatar on the right, so the profile menu is one tap from
anywhere inside the area. **The back arrow is untouched** — this adds a door
rather than replacing one.

**Two screens are deliberately not in the list.** The catalogue screens —
Title, Search, Taxonomy, Collection, Genres, Watch — are not account screens,
and an account avatar on a film's detail page is a second navigation the
product does not have. And **Notifications is the one account screen without
it**, because it sets its own `headerRight` from a `useLayoutEffect`: its
overflow menu would silently win, and two controls fighting for one slot is
worse than one screen keeping the shape it had. It is reached from the header's
bell rather than from the menu anyway.

**The avatar moved into `AccountButton` rather than being drawn twice.** It is
the same circle the tab header draws, and it now has one implementation instead
of two that happen to match today — which matters more than usual here, because
this circle shows a real person's face and its empty-state rule is the
website's own. `AppHeader` lost its `/profile` query with it; the button holds
the same query key, so the header, the menu and the account screens are still
one fetch between them rather than three.

### Unreleased — Billing says what you have spent, and the pre-mockup rows get tiles

`docs/plans/account-area-audit.md` §3, the last of the four open steps. Billing
was never badly built — it uses the shared row, the shared card, the real badge
and real tokens — it simply predates the language Rio's mockups introduced.

**A summary line above the order list: what this account has spent.**

🔴 **The figure is summed by the server, and that is the whole design.** Two
things go wrong if the app adds it up. It holds only the pages it has fetched,
so a total computed there is the first fifteen orders wearing the word
"total"; and `format.ts` already records why the digits are never recomputed
— a float round-trip is how a figure stops matching a bank statement. So
`GET /subscription/orders` gained a `totals` block: `SUM(amount)` over a
`decimal(10,2)` column, **completed orders only**, on the index
`['user_id', 'status']` that already existed.

**It refuses to add two currencies together.** `currency` is a free string
column with nothing tying an account to one, and a single figure formed by
adding shillings to dollars is the worst answer available because it looks
exactly like a right one. Both money fields come back null in that case and the
screen draws no summary; the order list still shows every charge in its own
currency, so nothing is hidden — only the sum is withheld, because the sum is
the part that cannot be stated truthfully. The count stays honest either way:
it counts orders, not money.

**The next charge is on Membership, not here**, which is §5's recommendation
taken rather than assumed. "When am I next charged" is a membership question
that people currently have to find in an order list, and it is one line on a
card they already open — it went onto the profile menu's Membership card
earlier in this slice. Billing answers the other half: what has already gone.

**Tiled row icons on Billing and on the notification switches.** The inbox's
own rows were already tiled; the two screens that were not are these. Every
Billing row gets the same receipt glyph, because every row on that screen is
the same kind of thing — a charge — and the state that differs between them is
on the badge, where it belongs.

🔴 **A guard that no test was asking, found by mutation.** Deleting
`spendSummary`'s zero-count check left the suite green: the case that covered
it passed a null amount, so the absent-amount guard below answered first and
the count check could not be observed. The state it actually defends against is
a figure arriving *with* a count of zero, which today's server cannot send and
a later one could — and the line it prevents is "UGX 0.00 across 0 charges",
where a zero on a money screen reads as free rather than as absent. Forced into
its own test, which now fails when the guard goes.

### Unreleased — Delivery folds into the inbox, and the invoice becomes a sheet

Two cuts from `docs/plans/account-area-audit.md`, both of the same kind: a
screen that existed only because something had to live somewhere.

**§4.2 — Notification settings was two switches behind a gear.** 174 lines,
reached from an overflow menu, on a subject the inbox is already about. They
are now a **Delivery** section at the head of the notifications screen, closed
until somebody opens it. The website keeps these on the notifications page
itself, so this is also the app coming back into line with the surface it was
ported from, and the overflow menu is left holding only things that HAPPEN when
pressed — which is why neither of its rows draws a chevron.

**Closed costs nothing, and that is the difference between folding it in and
pasting it in.** The preferences query does not run until the section is
opened, so a viewer who never touches it makes exactly the requests the inbox
made before. Closed also means closed for a screen reader: the switches are
clipped visually *and* removed from the accessibility tree, so nobody swiping
through the inbox meets two controls that are not on screen.

**§4.3 — The invoice was a route.** 258 lines of screen, opened from exactly one
row and leading nowhere, so the only thing a viewer could do from it was leave.
It is a `Sheet` now. Nothing about the document changed — the same measured
table, the same three states, the same absent Print button — it stopped being
somewhere you travel to. **This is a new conversion, not an exemption**, so it
does not touch the shrinking allowlist in `eslint.config.js`; the sheet comes
from `ui/overlay.tsx` like every other overlay in the app.

🔴 **`Sheet` now caps its own height, and that is a fix for every sheet, not
just this one.** A sheet hugs its content and had no upper bound, so content
taller than the display grew off the top and took its own last rows with it —
the invoice's footnote landed at y=2835 on a screen 2856 tall, which the view
tree reports as an inverted rectangle rather than as an error. The cap is read
from the window rather than fixed, so a tablet and a rotated handset both get a
panel; `fullHeight` sheets are exempt, because a payment page IS the screen.

**Two small shared additions rather than local copies.** `ListRow` learned an
`expanded` prop, which turns a row into a disclosure — announced as a button
with its open state instead of as a link — rather than a second row component
being written beside it. And the reduced-motion read that `PlayerControls` has
inline is now a `useReducedMotion` hook in `ui/motion.ts`; the player is left
alone deliberately and the hook says so, so whoever next opens it can retire
the fork in four lines.

### Unreleased — The profile is one screen, not two

`docs/plans/account-area-audit.md` §4.1. **`ProfileScreen` showed a subset of
the fields the editor already displayed.** Email, Phone, Country and Member
since, read-only, above a button to a form that showed all four in boxes plus
the names and the username. A viewer tapped Profile, read four values, tapped
Edit, and saw the same four again. 327 lines, and one tap on the most-used path
in the menu.

The split had already done real damage rather than merely costing a tap: the
avatar upload lived on the read-only half while the editor's own caption said
pictures were changed on the Jambo website, so one screen denied a capability
the other one shipped. That was fixed on 2026-09-10 by moving the upload into
`useAvatarUpload`; deleting the screen is the rest of it.

**Member since crossed over as a caption in the footer and nothing else did.**
It was the only fact on the old screen that is not a field on this one. It sits
at the foot because it is a fact about the account rather than about any field,
and a fifth thing at the top that looks editable and is not would be worse than
a line at the bottom. An account whose join date is missing or unreadable gets
no line at all rather than "Member since —".

**The gradient banner did not come with it.** §8 of the plan names giving a
form a hero as the mistake this drift invites: a 190dp headline above two
fields pushes the fields under the keyboard. `profileBanner` is deleted from
the token file rather than left unspent, with a note where it was saying why —
a token block nothing uses is a design decision nobody can see is dead.

**The route is gone, not orphaned.** `Profile` is removed from the navigator
and from the route types, the menu's Profile row and its identity block both
open `ProfileEdit`, and the screen's title is now "Profile" rather than "Your
details" because it is the destination those two open. `activeRowFor` no longer
answers for the old route, and the test that asserted it does now asserts the
opposite, alongside the four other routes removed on 2026-09-09 — with the
difference recorded, because unlike those four **nothing was lost with this
one**.

`detail()` in `profileFields.ts` went too. It turned an absent value into an em
dash for a read-only row, and there are no read-only rows left.

### Unreleased — The profile menu reads as three groups, and Membership is a card

`docs/plans/account-area-audit.md` §6, which the audit itself called the
cheapest change in the document and the one Rio would feel first. **The reason
the account area felt large was never the number of screens behind it.** It was
that the menu is nine identical rows — same height, same weight, same chevron,
no hierarchy — so the eye reads all nine every time and a streaming app's
account behaves like a settings app.

**No row changed its destination, no screen was added and none was removed.**
The same rows are now read under ACCOUNT (Profile, Security, Devices), VIEWING
(Watchlist, Notifications) and MONEY (Billing, Wallet, Refer & Earn), in the
order the audit derived from how often a viewer of a streaming product actually
opens each one rather than from the website's sidebar order.

**Membership is promoted out of the list into a card**, carrying the plan's
name and the date it runs to. It is the most-opened account page in this
product because it answers "what am I paying", and as a row it was the seventh
of nine pills, indistinguishable from Billing.

**The tier pill left the identity block in the same change.** It said the
plan's name and nothing else; the card directly below it says the plan's name,
the date, and opens the screen. Two surfaces stating one fact is exactly what
makes an account area feel bigger than it is. The pill's own three colour
values became the card's, so no second blue was invented.

**"Renews" versus "Active until" is now decided in one place.** That sentence
was private to the Membership screen and the card needed the same one; telling
somebody they are about to be billed when their plan is merely running out is
the one error on these screens that costs money, and it is a sentence that must
not be able to differ between two surfaces. It also stopped printing
"Renews —" for a plan with no end date — a line that admits it does not know,
in the shape of a fact — and draws nothing instead.

**The grouping is a pure function with tests, not a shape in the screen file.**
Two of its behaviours are the point: a group whose rows are all switched off is
not drawn at all, because a heading over nothing reads as a feature that failed
to load; and a row nobody has assigned a group is still drawn, under no
heading, rather than disappearing. Adding a row and forgetting to group it is
the obvious mistake, and being loud about it is cheaper than being invisible.

🔴 **Two defects found by rendering it, both older than this change.**

- **The active row was invisible to a screen reader.** "Where you are" was a
  gradient, a heavier label and a filled icon — three signals, all of them
  visual. Found by trying to read the state out of `uiautomator dump` and
  getting `selected="false"` on every row including the lit one. Both the rows
  and the new card now carry `accessibilityState`.
- **The highlight did not follow you back.** Opening a destination from the
  menu and pressing back left the row from the visit before it lit.
  `openedRow()` is module state read during render, and `useNavigationState`
  deliberately does not re-render when its selected slice is unchanged — which
  it is not, because the tab underneath does not move. Reading `useIsFocused`
  is what makes the menu look again at the moment it returns to the front.

**One file left the lint allowlist without being converted.**
`ProfileMenuScreen.tsx` was on `eslint.config.js`'s shrinking list of files
allowed to import `Modal` and `Alert` from `react-native`, and it imports
neither and never did — it was added from a list of screens rather than from
its imports. The ratchet drops from six to five at no cost. Worth reading the
other five the same way before assuming each is real work.

### Unreleased — The two daily Top 10 banners, and a scrim that was never painted

Rio: *"i am not seeing the top 10 movies of the day banner on the app."* It was
not a regression. It had never been built: the website's two biggest home
blocks — the Top 10 Movies of the Day vertical slider and the Top 10 Series of
the Day tab slider — were composed by `HomeRailsService` under the keys
`verticalFeatured` and `tabSeries`, and neither key ever left it. The app's
home screen was the website's home screen minus both. Rio then asked for the
series one too, at the website's exact design.

**`GET /api/v1/home` gained a rail kind.** `kind: banner`, with `style` saying
which of the two, and slides in a shape of their own — a hero minus the Tags
and Starring lines that neither banner draws, plus four genres where the hero
takes three, because the blade takes four. Ten unread cast arrays on the first
paint of every session is what the separate shape avoids.

**`style` is new on every rail, and it retires a hardcoded list in the app.**
The Top 10 numerals used to be decided by an app-side set of two literal rail
keys, with a comment in `catalogue.ts` saying the field belonged on the server.
It does. `top_movies` and `top_series` now say `style: "numbered"`, and a third
ranked shelf would look right with no app release — which is the property the
whole endpoint was designed for.

🔴 **A rank is never printed unless the title earned it.** Both shelves rank on
distinct viewers in the last 24 hours and pad the rest of the ten from all-time
popularity, so a slide's *position* is always 1..10 while its *rank* is
sometimes nothing at all. `ranked_today` says which, and a padded slide reads
"Popular on Jambo" — the website's own rule, now on the wire. Rio asked whether
the missing numbers on his local site were a bug; they were one movie and one
series' worth of viewing history in the window, and step 15 of
`tools/dev-catalogue/` makes that visible on a dev machine.

**The banners carry no title.** A full-width slide has no shelf heading on
either surface, and sending one invites a client to draw it. The admin screen
still names both rows, from `HomeSection::DEFAULTS`, because a row you can drag
needs a label. A migration inserts them where `ott-page` puts them — the movies
banner after Upcoming, the series banner after the personality rail — rather
than letting `seedDefaults()` append both to the bottom.

### Unreleased — Webapp: the Top 10 banners get a scrim that reaches a pixel

Rio, over a screenshot of the movies slide on a phone: *"the texts here are not
visible enough work around the opacity to have it clear."*

The cause was not a missing gradient. Streamit declares one on
`.slider--image` — the element that **contains** the slide's `<img>` — and a
child image paints over its own parent's background. That scrim has never been
visible on any slide since the section was built, and how legible the banner
was depended entirely on which film was on screen.

**0.6, and it was solved rather than chosen.** Each of the ten movie backdrops
and three series backdrops was composited the way its slide composites it, the
luminance of the band the text occupies was read, and the veil was solved for
the smallest alpha that still clears WCAG AA — 4.5:1 — for the 16px synopsis.
The brightest movie needed 0.586 and the brightest series 0.607. Flat rather
than a gradient because this banner's content is centred and spans 326 of its
390: there is no edge for a gradient to hide in. The rule is in
`jambo-header.css` per ADR-0001; the app draws the same number from the same
arithmetic.

The series slider keeps its own three gradients and gains only the floor
beneath them, added as a `background-color` on the pseudo-element that already
carries them, so this file holds no second copy of three gradient definitions
to drift.

### Unreleased — Subscribing from the app, in a popup, through one gateway-agnostic API

Rio: *"we can not subscribe to any membership plan fix this."* The answer was
that it was **both deliberate and unbuilt**, and only ADR-0004 says which half
is which. Google Play requires Play Billing for subscription video outside
IN/KR/EEA/US and forbids pointing at another payment method, so the Play build
sells nothing — that part is protecting the listing. The direct APK was always
meant to take PesaPal and never got it. That half now exists.

**`POST /api/v1/subscription/orders` takes a plan and nothing else.** No
amount, no currency, no gateway name. The server reads the price off the tier
row, freezes it into the order's snapshot, computes any referral discount
itself and resolves the gateway from `payments.default_gateway`. A client
cannot pair a plan with a price of its own, and the test that proves it sends
`amount: 1` against a 30,000 shilling plan and asserts the gateway was handed
30,000.

🔴 **The response describes a presentation, not a provider.** It answers
`{ mode, url, return_url, cancel_url }`, and Rio's reason is the design
constraint: *"we don't want to lose sales because someone did not update the
app."* The app ships knowing a closed set of modes and the server picks one per
order, so a gateway integrated next year is a server change and a phone that
has not updated in a year still completes the sale. Verified against live
PesaPal — the URL it returns is PesaPal's own iframe, passed through untouched.

**The payment happens inside the app.** Rio again: *"we are supposed to use a
popup, not a redirect"*, and *"we don't have to send people outside the app."*
A full-height sheet hosts the gateway's page. Handing somebody to the system
browser mid-purchase means they return by remembering to, or not at all.

**Closing the popup decides nothing.** Reaching the return URL means the payer
got to the end of the gateway's flow, not that money arrived — mobile money is
a USSD prompt that can be approved after the page has moved on. The app asks
the server for the order's status instead, because the gateway confirms to the
server and that is the only authority.

**One money path, not two.** The website's pricing page now calls the same
`SubscriptionCheckout` service. The referral tests passing unchanged is what
says the extraction preserved behaviour rather than approximating it.

**The shared sheet grew two options rather than the app growing a seventh
overlay.** A payment needs full height and must not close on a stray scrim tap
while somebody is typing a PIN, so `Sheet` gained `fullHeight` and
`dismissOnScrim` — and the lint rule banning a raw `Modal` needed no exemption.

**Not yet done:** no payment has been completed end to end. PesaPal's
confirmation is a server-to-server call to a public URL, so a real completion
needs `payments.callback_base_url` pointing at a reachable host — a tunnel on a
development box. The Subscribe button also exists only in the `direct` build.


### Unreleased — The Genres rail, at the website's design

Rio, with the two rails side by side: *"the genere on teh webapp are showing
images and we have the view, fix it as well"*, and then *"learn from the
orignal from the webapp to get the exact work on design"*.

The site draws a 5:3 still with the genre name centred over it and a "View
All" beside the heading. The app drew a bordered chip with a title count and no
link. Both halves were missing for different reasons.

**The picture was a data gap.** A genre owns no artwork, so the site borrows a
still from its most recent published title through `Genre::featured_image_url`.
That never reached the API. It does now, as `image_url` on `GenreCard`, so both
surfaces show the same picture for the same genre.

🔴 **And the accessor behind it was an N+1 nobody could see.** It reads like a
property and runs up to **four queries per genre** — movie backdrop, movie
poster, show backdrop, show poster — which is up to forty on the home rail, on
every request, for a decoration. `Genre::attachFeaturedImages()` answers the
whole set in **two**, with the accessor's exact precedence, verified against it
across all eight genres. The website's home page and its `/all-genres` got
faster for free.

**"View all" needed a screen, not a collection.** `RailArchiveCatalog` has no
`genres` key and should not: a collection is a list of TITLES behind one rail,
and this is a list of genres. The website makes the same distinction, linking
to `/all-genres` rather than `/collection/genres`. So there is a `Genres`
screen, one full-width column of the same tile, which is what the site's grid
resolves to at phone width.

**Two faults that only rendering it found.** The rail still had no link after
the routing was written, because **only the `titles` arm ever forwarded
`onSeeAll` to the rail component** — genres, VJs and personalities never did.
And the tiles were 2.4 to a screen where the site shows exactly 2, because the
rail was using a width derived from the poster rail's card count.

Measured rather than matched: the tile renders at aspect **1.673** against the
site's **1.667**, and the scrim is the site's own left-to-right gradient, which
is easy to get wrong — the label is centred, so the obvious vertical scrim
would darken the artwork above and below it and leave the text on the
brightest band.

**Still to do, and reported rather than assumed:** the app's VJs and
Personalities rails have no "View all" where the site has both, the website's
VJs "View All" points at `/movie` rather than at any VJ index, and the
`/all-genres` heading reads "Geners".

### Unreleased — The home banner, at the website's design

Rio asked for the webapp home page's banners to exist in the app, *"strictly
remember we keep the design from the webapp"*. A scan of `/` — which is the
real home page; `/home` is the secondary one — found three banner-shaped
blocks and every ordinary rail already ported. This is the first of the three.

**The gap was data before it was styling.** The app's banner drew a portrait
poster, the title and a year, because `GET /api/v1/home` sent its hero as
`MovieResource::card()` and a card keeps `backdrop_url`, `synopsis`, `genres`,
`tags` and `cast` behind the `detail()` flag. So the resources gained a third
shape, `hero()`, which is exactly what `hero-banner.blade.php` renders and no
more — not `detail()`, because the banner is on the critical path of every
first paint and never draws `views_count`, `published_at`, `categories` or a
series' seasons. **The rails below it still send plain cards**, and a test
asserts they do: a shelf of thirty posters carrying thirty synopses is the
cost the two shapes existed to avoid.

The app's side is the site's banner rebuilt from measurements rather than by
eye — the slide's 440:390 ratio, the two horizontal scrim gradients, the
square certification badge, the meta row's 16pt gap, the 7pt-cornered CTA with
its filled play triangle. Every number is in `theme.banner` with the selector
it came from, read off the rendered page in a headless browser at 390x844.

**The texture-filled headline is now one component, not two.** Streamit fills
the hero title and the Top 10 numerals from the same `texure.webp` through the
same `background-clip: text`, which React Native does not have. The SVG
pattern trick that drew the numerals moved to `ui/rails/TextureText.tsx` and
both use it. It gained a measuring pass on the way: SVG has no ellipsis, so the
text is laid out first by a real invisible `<Text numberOfLines>` and RN is
asked what string it would have drawn. Estimating character widths from the
font size is fine for tabular digits and wrong for every film title.

🔴 **The five-star rating is gone from the app and from the website, and it
was never a rating.** `hero-banner.blade.php` read
`ratings()->avg('stars') ?? 5`; the `ratings` table holds nothing, 0 rows
against 120 titles, and **no controller, endpoint or form on either surface
can write to it** — only a seeder can. So `?? 5` was not an edge case, it was
the only value jambofilms.com had ever displayed: five filled gold stars on
every film, permanently, for a score nobody gave and nobody could give. Rio's
call once that was established.

Removed from five places, because it turned out to be everywhere: the home
banner, the Top 10 Movies banner, the guest home hero, the `parallax`
placeholder, and `movie-slider.blade.php` — which was the worst of them,
hardcoding three-and-a-half stars on **eight listing pages** without
consulting the database at all. `stars_avg` and the aggregate query behind it
came out of the API too; a field nothing draws is a field that grows a
consumer later.

**The IMDb mark stays**, on both, by Rio's call. With the invented score
beside it gone, it is a mark rather than a number attributed to IMDb. In the
app it is `ui/rails/ImdbMark.tsx`, a port of the site's own SVG rather than a
redraw — Metro has no SVG transformer here and adding one for a single 2KB
asset is a build change for nothing.

🔴 **And a bug in the same block.** The guest home hero drove its stars from
`$movie->rating`, which is a content **certification** — "PG-13", "NC-17" — so
`$i > "NC-17"` compared an integer against a string, and the certification was
printed beside the IMDb mark where a score belongs. Viewers read "NC-17" as a
rating out of ten. It is a proper badge now, the one the OTT hero already
used.

The same reasoning also removed the blade's `$item->rating ?: 'PG'` fallback
from the app's badge: a certification is a legal claim about a film, and
inventing one for a film nobody classified is the same fault as the stars.

**No thumbnail strip, and that is fidelity rather than a shortcut.** The
desktop banner has a poster rail that doubles as its navigation; at 390pt its
container computes to `display: none` and swiper's dots take over. The dots
are what the site actually shows a phone, so the dots are what the app draws —
10pt, primary blue, inactive at half opacity, as measured.

**No auto-rotation**, unchanged from the previous cut and for the same reason:
a carousel that moves on its own takes away the thing a viewer is reading, and
on a remote it moves the focus target out from under the d-pad.

Two things found while reading the page. **`streamTag.starrting` had the value
"Starting"**, so the OTT home said "Starting:" over the cast list while the
guest home said "Starring:" through a different key — the value is corrected,
the misspelled key stays because several blades reference it. And **the
website's own banner is two N+1s per slide**: `ratings()->avg()` per slide, and
a series' runtime found by walking every episode into memory through an
unloaded relation. The API path batches both into two queries for the whole
banner; the blade still does what it did.

### Unreleased — The inbox can be emptied, and rows can be picked

Rio, on the notifications screen: *"we can click and view the notfication but
we need the abillity to make all read and clear them"*, and then *"we can have
the long press event like long press to select and we can select multiple."*

**Both bulk actions moved into the inbox, and then out of its way again.** The
first cut copied the website's two header buttons literally — a filled blue
"Mark all read" and an outlined red "Delete all" above the chips. Rio's answer:
*"i think we can hide these initially or we can upgrade this in the way the
feels clean and modern simple creative."* He was right. On the site those
buttons sit in a wide desktop header and read as chrome; on a handset they are
the loudest thing on a screen whose whole job is a list, and the destructive
one is loudest of all. They are housekeeping.

So there is now one "⋮" in the header where the gear used to be, opening a
sheet with Mark all read, Notification settings and Delete all. **One control
replaced two pills and an icon.** Each action appears only when it has
something to do, which is what the website's own blade does with the same two
buttons — `$unreadCount > 0` and `$notifications->total() > 0`. Deleting
everything asks first, in the app's own dialog, in the website's own words.

**Long press picks rows.** The tick replaces the row's 40x40 poster rather than
appearing beside it, so entering selection does not reflow the list under the
thumb that is pressing it. Selection mode is simply the selection being
non-empty, so unpicking the last row and cancelling cannot disagree. The bar at
the bottom offers "Mark read" only when something picked is actually unread —
an action with nothing to do is not drawn at all.

🔴 **A defect this introduced and then fixed, because the shape is worth
keeping.** Hiding the header buttons on entering selection moved the whole list
97dp up the screen: the long press moved the row it had just picked, and the
next tap would have landed on a different one. They stay mounted and go dim
instead. A gesture must not move the thing it acted on, and "hide the control
that no longer applies" is the instinct that breaks that.

**"Mark all read" is gone from the notification settings screen.** It was
parked there when the inbox header had no room for it. It has room now, and two
ways to mark an inbox read is one too many.

**Shared, not copied.** The selection bar is `ui/SelectionBar.tsx`, and its
tokens name the Watchlist's own rather than restating them. The Watchlist still
has its private copy of the same bar; converting it is a commit of its own, not
a side effect of this one.

Two smaller corrections found on the way: `ListRow` drew a chevron for every
pressable row, and a caret promises a screen that "Mark all read" never opens;
and the inbox's filter chips announced as bare "Account", "Movies" and so on,
where "Account" collided with the header's own account control for anything
searching by label. They announce as "Account filter" now — a chip that filters
is not a place.

### Unreleased — The header

Rio: *"let us use the exact header as it is on our web app."* The mockup he
sent turned out to be the website's own header, which made this a port rather
than a design.

`header-default.blade.php` below 992px renders the brand, a search toggle, a
**notification bell with an unread badge**, the **viewer's avatar**, and a
**second row of genre chips**. The app had the first two and a generic account
circle. It now has all of it, from probes of the rendered site rather than from
the screenshot.

**The bell's absence had an expiry date.** Its note said a bell would open a
list that is always empty for reasons a viewer cannot see, because the
notifications screen did not exist. It does now.

**The header navigates itself.** All four tabs passed the same two callbacks;
adding the bell would have made that eight copies of one decision.

🔴 **The logo was too small, and the measurement appeared to disagree with
Rio.** The site's logo is 24 tall and the app's box was 108x32, which looked
larger. The brand is a **wide wordmark**, so `contain` fitted it to that box by
*width* and it landed around **16dp tall**. A fixed box can only be right for
one logo's aspect and the logo is admin-uploadable, so height drives it now —
`height; width: auto`, as the site does — with the ratio read from the image.

🔴 **Two probe defects worth knowing, both of which produced plausible
values.** The genre-chip probe matched the *first* chip, which is "All", which
is active on the home page — so the resting chip captured as the selected one
and every chip would have been drawn highlighted. And the bar's border colour
read as the text colour, because CSS defaults a border colour to `currentColor`
when no border is set; the captured widths are 0, so there is no border at all.

🔴 **The bell briefly made the Notifications screen unopenable.** The header
cached its unread count on the key the inbox uses for its All tab, and the two
read it with different query types — one storing pages, one storing a response
object. Whichever mounted first won, and when the header won the screen threw
on a missing `pages`. Two queries may share a key only if they share a shape.
The badge now caches at `['notifications', 'unread']`, which stays under the
prefix so invalidation still refreshes it, and a test asserts no category is
ever called "unread".

The logo went up again to 30 at Rio's request, a deliberate deviation from the
captured 24 and recorded beside it. It sits inside the site's own range: the
CSS rule is 36 and only renders shorter because a browser's mobile header
constrains it.

Four contrast pairs were measured before use. Three pass comfortably. The
unread badge is white on the brand blue at 3.01:1, which is under AA — the
tenth entry in that tally, the pair the whole app already wears, kept and named
rather than re-decided in one slice.

### Unreleased — Phone numbers

Rio, 2026-09-10, with nineteen worked examples of one Ugandan number: the app
must read all of them, carry a dial code from the selected country, and format
live as it is typed, the way a card field does.

**All nineteen now converge on `+256742078673`**, and that is what is stored.
E.164 is the only form that is unambiguous, comparable and dialable, so
everything typed converges on it and everything shown is derived from it. The
list is asserted literally in both the app and the server rather than
summarised — it is the contract, and a case dropped from a test is a shape
somebody can type that the system refuses.

**The server normalises too, because the app is not the only writer.** The
website's profile form and the admin save this column; normalising in one place
would have left the mixture in the database.

**The grouping is Rio's, not the library's.** libphonenumber formats Ugandan
numbers as `0742 078673` — four then six, the official convention. His examples
are threes, so Uganda is overridden and every other country keeps the library's
rules, where the library is the only specification available.

**libphonenumber earns its place on one field.** Recognising his formats needs
no library; they differ only in separators and prefix. Knowing that
`0414230000` is a *landline* does — a perfectly valid Ugandan number that
cannot receive mobile money. The withdrawal field now refuses it, which no
shape check could have caught.

**Existing numbers convert with a report, never silently.** `phones:normalise`
runs as a dry run by default and prints what it would change. Anything it
cannot read confidently is left exactly as it is and listed for a person,
because a confident guess at somebody's phone number is worse than leaving it.

🔴 **Two build traps, both recorded.** `libphonenumber-js/max` cannot be
bundled by Metro — it reaches for a `.json.js` shim that exists for Node's ESM
loader — so `metro.config.js` redirects that one specifier at the real JSON the
package also exports. And `EXPO_PUBLIC_API_BASE_URL` now lives in `mobile/.env`
rather than a shell prefix, after a restarted Metro dropped it and the app
silently called production.

### Unreleased — Security, rebuilt to the mockup, with a delete button Rio controls

Rio's mockup of 2026-09-10: a headline, the protections as rows with their
state on the right, and recent account activity underneath. Then, mid-build:
*"we will have a delete button. but we can disable this button when we please,
when enabled it shows and when disabled it disappears from the app."*

**Account deletion is now a row, and a switch in admin settings.** A new card,
"Account deletion (mobile app)", shows or hides it. 🔴 **The switch is
enforced twice.** `/app/config` carries `features.account_deletion` so the app
hides the row, and `DELETE /account` checks the same setting and refuses with
FORBIDDEN. A flag only the client reads is not a switch: a build already on
somebody's phone would keep closing accounts after it was turned off. The
refusal happens before the password is checked, so a disabled feature cannot be
used to test passwords. It defaults to ON, unlike the flags beside it, because
those gate unfinished features and this gates a finished one.

**It closes the account rather than erasing it, and says so.** Every device is
signed out, every token deleted and sign-in stops working immediately; records
are kept. The screen states that in as many words, because a Delete button that
quietly keeps everything is a promise somebody will hold us to. Google Play's
deletion policy is not met by this and that remains open.

**Two things in the mockup were refused rather than drawn.** "PIN for
Purchases" does not exist anywhere in Jambo — no column, no endpoint, nothing
on the website — and the app takes no payments on either build, so the toggle
would guard a purchase flow that is not there. And the phone number has no
verified state: the column exists and the profile editor writes it, but nothing
verifies it. The row is there, the green badge is not. The email badge beside
it IS real, which is exactly what would have made a matching phone badge
believable.

**Google sign-in left the screen.** It reported whether the SERVER has Google
configured, not whether this account is linked. Server configuration is not a
personal security fact.

**`ListRow` gained an optional icon tile**, off by default, because the mockup
draws its icons in rounded tiles and the alternative was a second row component
for one screen. Recent account activity reuses the Devices screen's own query,
formatters and icon map rather than growing a second opinion about the same
rows.


### Unreleased — One overlay design, enforced

Rio, 2026-09-10, on seeing the device sign-out confirmation: *"we need a
universal design for our system so that we don't use these default generic
design and make sure all the other agents use it when it's needed it should be
enforced."* It was an Android system dialog — grey, teal, capitalised — in the
middle of a blue product.

`mobile/src/ui/overlay.tsx` is now the only place `Modal` or `Alert` may be
imported from `react-native`. It exports `useConfirm()` for anything
irreversible and `Sheet` for every bottom panel.

**The small problem was two `Alert.alert` calls. The large one was six
hand-rolled sheets.** The watchlist's sort sheet, the player's settings menu,
the country picker, the referral editor and the withdrawal form had each built
their own. **Shared tokens had hidden it**: they looked alike, so nobody
looked, while the Android back button, the safe-area padding and the scrim's
press target were solved five times and correctly a different number of times.

**Enforced with a lint rule and a shrinking allowlist.** The six files that
predate it are exempt until each converts; nothing new may be added to that
list. The rule lands today without turning another session's build red, and
converting the six stays deliberate work in its own commit.

Almost nothing in the new token block is new. The ground, radius and scrim are
the existing sheet block, so a dialog and a sheet are one object. The only pair
that needed deciding was the destructive red, and it reuses the one already
measured at 5.67:1 rather than introducing a second that nearly matches.

### Unreleased — Devices

The devices screen from Rio's mockup: a hero, a usage meter, an Active and
Inactive split, and a row per signed-in device with a direct sign out.

**Three of the mockup's elements described things Jambo does not have**, and
all three were checked against the website and the schema before anything was
built.

A **city per row**: nothing stored one, the website shows the raw IP, and app
installs had no address column at all. Rio's call was to add geolocation.

**"4 of 6 devices used"**: there is no device-registration cap; nothing stops
an account signing in a hundred installs. The only limit the tier enforces is
how many can watch at once, so the meter counts that and says so. It reads from
the same method the player calls before letting a stream start, so it cannot
promise room the player is about to refuse.

**The `+` button**: a device registers by signing in, so there was nothing to
add. It is gone until TV sign-in exists.

🔴 **The geolocation resolves nothing until a database is installed, and that
needs an account only Rio can create.** `IpLocation` reads a MaxMind GeoLite2
file locally — no per-request cost, no rate limit, and no viewer's address
leaves the server — but the file is licence-keyed, ~60 MB and weekly, so it is
fetched on the server rather than committed. Until `GEOIP_DATABASE` points at
one, every row shows its address, which is what the website has always shown.
A metered API costs per request and the no-account free one forbids commercial
use, so neither was an option.

**Storing the address was a decision.** `devices.last_ip` is new, holds only
the latest value, is nullable with no backfill, and the resolved city is never
persisted. One column that overwrites is a different thing to hold about
somebody than a movement history.

**The row icon comes from the website's own parser.** `UserAgent::parse`
already decides between a phone, a desktop and a globe and the hub's device
list draws it; the API was dropping it, so the app's first cut guessed from
`kind` and drew every browser identically. It is forwarded now and resolved
through the notification inbox's icon map rather than a second table. A
television still draws a desktop glyph because the shared parser has no TV
case — fixing that improves both surfaces and is not an app-side special case.

**Two defects found by rendering it, not by testing it.** Every app install
with no reported version rendered the literal string **"vnull"**, because the
check asked for `undefined` where the server sends null. And a section heading
carried margins while also sitting inside a row that carried the same ones,
stacking into a visible hole. Both fixed, the first with a regression test.

### Unreleased — Membership, rebuilt to the mockup, on the website's own card

Rio's mockup, 2026-09-09: a hero, three promises, billing-period tabs, a
three-abreast plan ladder and a yearly nudge. The screen was a list of rows
before this, because the ladder was a placeholder waiting for a design.

**The plans endpoint was thinner than the page it was derived from, and that
was the blocker rather than the layout.** `subscription_tiers.features` has
backed the website's pricing cards all along and `GET /plans` never sent it,
so the app could show what a plan costs and not what it includes. It now sends
`features`, `period_label` and `is_popular`. Nothing about the data changed --
only what the API was willing to forward.

**"Most popular" is decided in one place now.** The rule was written inline in
`pricing-page.blade.php`; it is `SubscriptionTier::popularFrom` and both the
website and the API call it. Two copies of that rule would have marked
different plans the first time an admin added a tier, and nobody would have
noticed until the two screens were put side by side.

**The card is the website's, measured rather than drawn.** Twelve probes were
added to the token exporter for `.pricing-plan-wrapper`, `.plan-main-price`,
`.pricing-plan-discount`, `.jambo-period-tabs` and `.jambo-strip`, taking the
token file from 174 values to 217. The type scale is the one thing translated
rather than copied: the site's 38px price does not fit a third of a phone, and
a price that wraps is worse than a price a size smaller. The mockup is crimson
and the app is blue, which is the third time that substitution has been made
and the same ruling each time.

🔴 **The website's "Most popular" ribbon fails WCAG badly** --
`#d1d0cf` on `#1a98ff` is **1.95:1**, on the one label whose whole purpose is
to be read. The app keeps the fill and draws the text white, which is 3.01:1.
That is still under AA and is deliberately not re-decided here, because
white-on-blue is the pair the entire app already wears. The website defect
wants its own fix.

**Four places the screen says something different from the mockup, all for the
same reason -- a screen may not state what the system cannot back.** The cards
read "On the website" rather than "Get Started", because neither build can take
a payment. The yearly banner shows the real cheapest yearly price rather than
"up to 2 months free", which is arithmetic that would keep being stated after
an admin changed a price. The hero image is a real catalogue poster rather than
a studio photograph the product does not own. And the mockup's script lettering
is absent, because the app ships no script face.

**Free is still never a card**, the tabs still only show periods that have paid
plans, and the default tab is still the one holding the plan you are paying
for. All three are the website's rules, inherited rather than re-invented.


### Unreleased — Wallet, and withdrawal from the phone

The wallet from Rio's mockup: a balance card, two actions, two tiles and the
ledger as rows. Withdrawal now completes in the app instead of sending a viewer
to a browser.

**Six of the mockup's controls were checked against the product before anything
was built, and only two survived.** Jambo's wallet has no top-up: money enters
it as referral rewards, refunds and statement credits, and there is no deposit
code or ledger type anywhere. "Buy/Rent Movies" describes a product Jambo is
not — it is subscription only, with no purchase or rental model. "Gift Credits"
and "Airtime Top Up" exist nowhere. Its three sample transactions were all
three kinds that cannot occur. Rio's calls: "Add Funds" becomes "Earn more"
pointing at Refer & Earn, which is what the website's own wallet card does, and
the quick actions become the real destinations.

**Withdrawal writes no money logic of its own.** The endpoint validates a
request shape and calls `ReferralWalletService::requestWithdrawal` — the entry
point the website's form posts to — and `Payouts::request` below that already
held the row lock, the one-open-request guard and the ledger hold, inside a
transaction that rolls the request row back when the ledger refuses.

**No idempotency key, deliberately.** The path is already idempotent by a
mechanism that was there: the service locks the owner row and refuses a second
open request, so the retry a dropped connection produces cannot create two
withdrawals. A key would be a second mechanism guaranteeing what the first
already does. One test makes that argument checkable rather than rhetorical.

**`GET /wallet` was thinner than the website and now is not.** The site has
shown withdrawal history all along; the endpoint dropped it. That matters
because the hold is taken the moment a withdrawal is requested, so a balance
that has dropped with nothing paid out had no explanation in the app.

🔴 **`Ledger::balanceFor` returns a string whose decimals follow the database
driver** — `"15000.00"` on MySQL, `"15000"` on SQLite. Money is now compared
with `bccomp` in tests and parsed once for display in the app. The screen this
replaces rendered the raw string on a rule about floating point; the instinct
was right and the conclusion was not, because the website itself casts to float
to format and the raw string makes the app's numbers depend on the database.

🔴 **A defect found on the money path and deliberately not fixed here.**
`wallet_withdrawal_requests.requested_at` is a bare MySQL `timestamp`, so it
carries the implicit `ON UPDATE CURRENT_TIMESTAMP`. The service inserts the
correct UTC value and then updates the row with the hold id, and **that update
silently overwrites the timestamp with the database server's clock in its own
timezone** — three hours out here, and it moves again on approve, pay and
reject. Every withdrawal ever made carries a wrong requested-at. It is
pre-existing and the website shares it. Not fixed in this slice on purpose: it
is a schema change to a money table with existing bad rows to decide about.

### Unreleased — Streaming preferences leave the menu, and wait for the player

Rio, 2026-09-09: *"remove the streaming menu, because all of these settings are
applied to the player itself."* Then, while the change was being made: *"wait
to build the player we are going to build it fully. so i need you to have it in
plan."*

So the Streaming row is gone from the profile menu, and `StreamingPreferencesScreen`
with it — the menu was its only door, the third screen this week to go the same
way after the Account hub and History. The `StreamingPreferences` route and its
entry in `AppStackParams` are removed. **The player was not touched**, because
it is about to be rebuilt in full; the two edits that had already been made to
`PlayerMenu` and `WatchScreen` were reverted, and both files diff clean against
HEAD.

🔴 **Nothing about where preferences are stored changed.** `GET`/`PATCH
/account/preferences` is still served, still documented, still backed by the
`streaming_preferences` column with its ten feature tests. `video_quality` is
still written to the account from the player's own settings menu on every
change — which is Rio's point: the screen was a second door onto a field the
player already owned. Deleting the preferences *screen* and no longer *saving
preferences* are one careless sentence apart, and only the first happened.

**One setting lost its only control, and it is named rather than buried.**
`autoplay_next` was read by the watch screen and settable nowhere else, so
until the player build lands it sits at its default of on and cannot be turned
off. That is the accepted cost of leaving a slice alone that is about to be
rebuilt. `wifi_only_downloads` also loses its switch, but it was gated behind
`features.downloads`, which is off, so no viewer could reach it; it belongs on
the Downloads screen in Phase 3, not in a player overlay. Both are written into
§6.5 of `docs/plans/mobile-offline-app.md`, which is what the player build
inherits.

`activeRowFor` now returns null for `StreamingPreferences` alongside `Account`,
`ContinueWatching` and `History`. Mutation-proved: putting the map entry back
fails the case.

The Wallet screen's Streaming tile was replaced with **Billing** rather than
deleted, so the two-tile row stays a row rather than reading as a rendering
fault.


### Unreleased — History leaves the profile menu

Rio, 2026-09-09: *"as you finish, please remove the history from the menu"*,
and, when asked what that leaves behind: *"history is used in background for
training our ai system."*

So the row is gone, and the screen with it — the menu was its only door, the
same way the Account hub was Continue Watching's an hour earlier. `HistoryScreen`,
the `History` route and its entry in `AppStackParams` are removed.

🔴 **This changed nothing about what is collected, and the distinction matters
enough to state plainly.** Watch history is written by
`PlaybackBeatRecorder::record`, from the playback heartbeat, into
`WatchHistoryItem`. The screen only ever read it back. `GET /history` is still
served and still documented. Deleting the History *screen* and stopping the
collection of *history* are one careless sentence apart, and only the first
happened.

`api.history()` keeps its place in the client with no caller. A documented
endpoint the app has quietly stopped being able to call is a worse thing to
leave than an unused method.

`activeRowFor` now returns null for `History` alongside `Account` and
`ContinueWatching`, with the assertion extended in the same change as the map
edit rather than before or after it — either order would have left the suite
red or the map untested for a window. Mutation-proved: putting `History` back
in the map fails the case.


### Unreleased — The old Account hub removed

Rio, 2026-09-09: *"we should remove this, and link this to our account page we
have designed, because it is linking to the older account page."* The profile
menu's identity block now opens the Profile screen, and the hub is deleted.

**It had been overtaken rather than superseded on purpose.** The hub was a
column of buttons — Notifications, Security, Plans, Devices — every one of
which is already a row in the profile menu, plus a subscription summary that
Membership renders more fully. Two things on it were not duplicated anywhere,
and both had to be dealt with before the file could go.

🔴 **Deleting it stranded two screens.** Continue Watching and History were
reachable only from that hub; nothing else in the app navigates to either. Both
became rows in the profile menu.

**Rio reversed half of that within the hour:** *"remove it we have it on the
homepage."* The home screen's Continue Watching rail already answers that
question and a menu row was a second answer, so the row went — **and its screen
with it.** That was checked rather than assumed: with no row it had no callers,
and `continue-watching` is not in `GET /collections`, so the home rail is
offered no "View all" and cannot route there either. A screen nothing can reach
is dead code.

**The consequence is worth stating.** The home rail is now the only place a
viewer sees in-progress titles, and it shows only what fits the rail. There is
no full list, by decision rather than by oversight.

**History stays.** It is not on the home page and nothing else reaches it. It is
also the row most likely to drop out of the active-row map later, since every
other row has a website equivalent to remind someone it exists — so the tests
name it, and name the two now-dead routes beside it.

**"Devices at once" moved to the Devices screen rather than being lost.** That
screen already told a viewer devices count towards how many can watch at once
and could not say how many. It reads the cap from the `me` query the rest of
the app shares, and its failure is deliberately not the screen's error state:
the device list is what the viewer came for.

Nothing was added to the Profile screen. It is identity, and viewing history is
not identity — putting them there would have rebuilt the hub that just went.

### Unreleased — The preloader plays once

Rio, 2026-09-09: *"we only need the preloader when we are opening the app, not
every screen."* The branded animation is now the app arriving and nothing else.
Screens waiting for data show a quiet spinner.

**One component changed rather than twenty-four screens.** `<Loading>` has 24
call sites; `Loading` now draws an `ActivityIndicator` and a new `Preloader`
draws the GIF, with `RootNavigator`'s launch state as its only caller. Every
existing call site gets the new behaviour untouched, which is what the shared
component is for.

**The reasoning is not taste.** A 200dp animated logo reads as an entrance, and
an entrance only works once. On every tab change and every pull-to-refresh it
stops saying "Jambo is starting" and starts saying "Jambo is slow", which is a
brand animation arguing against the brand.

`Loading` no longer accepts the admin's preloader `source`. Keeping the
parameter and ignoring it would have left two screens passing a value into
something with no use for it — code that looks deliberate and does nothing.

**A false alarm, corrected in the same session.** A mid-capture frame showed
the launch preloader with the S of "FILMS" cut off, and it was reported as a
possible defect in the asset. It is not: a later frame caught the animation at
rest and the lockup is complete. The GIF animates from a stacked mark to a
horizontal one, and a screenshot taken part-way through catches it mid-reveal.
Nothing is wrong with the file, and nothing was changed.

### Unreleased — Notifications

The inbox from Rio's mockup: five filter chips, day headings, an unread mark,
and a row carrying either a title's poster or a coloured icon tile. Behind its
gear, a notification settings screen. It replaces a flat, unfiltered list.

**The chips needed a category and the API had none, so one was derived rather
than migrated.** `notifications.type` already stores the notification's class
name, so `NotificationCategories` maps that — no column was added, no payload
rewritten, and **the chips work over notifications sent before they existed.**
The map is a literal rather than a string transformation on purpose: all 33
class names happen to snake_case into their own `settingKey()`, so a
transformation would work today and fail silently the first time someone names
one differently. `NotificationCategoryApiTest` walks the directory and fails if
a class is filed under neither a chip nor `UNCATEGORISED`.

Filtering happens on the server because the list is cursor-paginated. Filtering
the thirty rows the app happens to hold would hide matching rows until the
viewer scrolled far enough to load them, and `unread_count` deliberately
ignores the filter — it is the bell's badge, and a badge that moved when a chip
was pressed would be reporting the chip.

**Three things in the mockup had nothing behind them and all three went to
Rio.** "Offers" has no promotional notification type, and his call was to keep
the chip and point it at admin broadcasts, which is how a promotion actually
reaches a viewer today. The gear had no destination, and his call was to build
one. The thumbnail is drawn large where the website's is 40x40, and his call
was to hold the site's size exactly.

🔴 **All five of the website's icon-tile colour pairs fail WCAG AA, and this is
a website defect.** The tile fills are fine — each is 14.8:1 or better against
the black row — but Bootstrap's `-emphasis` foreground fails against every one
of them: warning `#ffe877` on `#fff7d2` at **1.14:1**, success 1.54, info 1.76,
danger 2.01, primary 2.11. A yellow glyph on pale yellow is an invisible icon.
The app keeps each captured fill and replaces the glyph colour with the row's
own background token, so the icon is a knockout of the surface behind it and no
raw value was invented to repair one that was wrong. **The website is
unchanged and this remains open against it.**

🔴 **Bootstrap's `--bs-primary` on this build is a salmon `#ef6b72`, not the
Jambo blue.** The Streamit theme left it and applies the brand through custom
CSS instead, so `bg-primary-subtle` renders as pale pink — and primary is the
commonest notification colour, which makes the most frequent tile in the app
pink on a blue-branded product. Faithful to the site, and raised with Rio
rather than decided here. The three screens that have hit this wall all express
the accent as `auth.buttonFrom`/`auth.buttonTo`, so that pair is the single
place to change it.

**The unread tint needed no ruling.** The mockup marks unread in crimson, and
the app has translated that to brand blue three times on the strength of the
brand guide. This time the site answered directly:
`.jambo-hub-inbox__row.is-unread` is already `rgba(26, 152, 255, 0.07)` over a
`rgba(26, 152, 255, 0.18)` border. It was captured, not chosen.

**The token exporter gained a third signed-in page** — the hub's own inbox at
`/{username}/notifications`, whose row styles live in a `<style>` block on that
one page and exist nowhere else. Tokens went from 143 to 174, and no existing
value changed. Three numbers that had been set by eye were replaced by probes:
the row's title is 16 at 600 and its message and timestamp are both 14, the
timestamp matching the message being a design decision rather than an
oversight.

**Two capabilities moved rather than being dropped.** "Mark all read" existed
on the screen this replaces, and Rio's header has three controls with no room
for a fourth, so it is the last row of the settings screen. And **push is
deliberately absent from that screen** although the website has a third switch:
there it drives a browser Web Push subscription, so in the app it would either
silence the viewer's *browser* notifications from their phone or toggle a flag
for a channel with no sender. The endpoint carries `push` either way.

**A defect no test could have caught.** The gear's touch target measured 30dp
on the emulator against the app's own 48dp floor — `spacing.xs` looked right
and was not. It is `touchPadding(22)` now, re-measured at exactly 48. The same
pattern is in `AppHeader` and is reported rather than changed.

### Unreleased — Editing the referral code, and a rule the API was missing

Rio asked for two things on Refer & Earn: the code should be editable, and the
percentages should follow whatever the admin sets. The second was already
true and is now proved; the first turned up a defect.

🔴 **The API's referral-code save was weaker than the website's form.** A
referral code shares a namespace with usernames, because the profile hub lives
at `/{username}` — so a code is also a URL segment. `ProfileHubController`
has always applied `ReservedUsername`; `Api\V1\ReferralController::updateCode`
checked the shape and both uniqueness columns and **not** the reserved-route
list. The mobile app could therefore take a code of `login`, `movies` or
`watch`, and the resulting referral link would resolve to a real page instead
of the referrer. `ReferralCodeController::check` even carried a comment
promising it "mirrors updateReferralCode's rules exactly"; that was true of the
web save and false of the API's.

Fixed by extraction rather than by copying the missing rule across.
`Modules/Referrals/app/Support/ReferralCodeRules.php` now holds the rules and
the availability answer, and all four call sites use it: the website's save,
the website's live check, the API's save, and a new API check. Third
de-duplication of this shape after `PlaybackAuthorizer` and
`WatchlistPlayResolver`.

**New: `POST /api/v1/referrals/code/check`.** The website has had a debounced
availability check since Refer & Earn was built; the app's only way to learn a
code was taken was to submit and read a 422. Because both go through one
class, "available" can never fail on save — which is the promise the web
comment made and the code did not keep.

**The app's editor is the website's control, translated.** A sheet with the
code, a live check debounced at 350ms, a status line, and Save disabled while
the value is known-taken. Three behaviours from the site's script kept
deliberately: the debounce, the stale-response guard that drops an answer for
a code no longer in the box, and a network failure that does not block saving
because the server validates again.

It is mounted only while open rather than living behind a `visible` flag and
re-seeding in an effect. Seeding form state from an effect is a known
input-eating bug in this app and eslint's `react-hooks/set-state-in-effect`
caught the first cut doing exactly that.

**The two copy controls now do different things**, per Rio: the icon copies
the code, the button copies the referral link and is labelled "Referral Link".
Both used to copy the code, which made one of them pointless.

**The percentages were already dynamic and are now verified.**
`ReferralSettings` reads the `settings` table, the endpoint forwards it, and
the screen trims decimal zeros the way `refer.blade.php` does. Changing the
admin values to 17.5 and 25 and re-rendering showed both the hero line and the
third How It Works step follow, with "17.50" printing as "17.5%". Restored to
10 and 10 afterwards.

Also, a note on method: the first run of that probe reported the endpoint did
NOT follow the setting. It was the probe that was wrong — `setting()` takes a
positional pair and the associative form silently writes nothing. Worth
recording because a false red on a money-adjacent setting is the kind of thing
that gets acted on.

### Unreleased — Refer & Earn

The referral screen from Rio's mockup: a hero, the code with two copy
controls, five share destinations, a three-step explainer and a rewards
summary. It replaces a plain list of statistics.

**No PHP changed, and that is the finding.** After the Watchlist — where the
API turned out to be thinner than the Blade view it came from — the first move
here was to read `resources/views/profile-hub/refer.blade.php` before writing
anything. It and `ReferralController` both go through
`ReferralDashboardService`, so the endpoint already carried every figure the
website shows. The one thing the page has and the endpoint does not is the
referral *list* — masked name, status, joined date, amount — and the mockup
shows no list, so that stays a named gap rather than a silent one.

🔴 **One tile in the mockup was not built, and it is the money one.** It reads
"15 Days Free Premium Earned". Jambo's referral reward is a percentage of what
the friend pays, credited as money to a wallet; there is no free-days
entitlement in `Modules/Referrals`, in the ledger, or on any subscription
tier. Telling someone they have earned fifteen days they cannot redeem is the
case the never-invent-a-value rule exists for. The tile shows what they
actually have, in currency, and this is flagged to Rio rather than dropped
quietly.

**Money is now printed the way the website prints it.** `refer.blade.php`
renders these figures as `number_format((float) $value, 0)`, so the same
balance read "UGX 21,000" in a browser and "UGX 21000.00" on a phone. The app
groups the digits by string surgery rather than by parsing to a number — the
site casts to float and gets away with it, but a balance large enough to lose
precision is exactly the balance that must not be misreported.

**The share row is five real destinations, not decoration.** WhatsApp,
Facebook and X through their public share endpoints, which open the installed
app when there is one and the web client when there is not; Copy through the
clipboard; More through the OS sheet. A destination that fails to open falls
back to the OS sheet rather than doing nothing, because the viewer's intent
was to share.

**Copy uses `Clipboard` from react-native core, deliberately.** It is
deprecated but present and linked in react-native-tvos 0.86, while
`expo-clipboard` needs a native rebuild that would have invalidated the dev
client two other sessions were running against. One call site, to swap at the
next rebuild.

Also: `tools/dev-catalogue/13-test-user-referrals.php` seeds five referrals,
three of them qualified, and credits the rewards **through `Ledger::append`**
rather than by inserting rows — the ledger computes `balance_after` itself and
refuses to run outside a transaction, and a fixture that wrote around it would
produce a balance the ledger disagrees with.


### Unreleased — My Watchlist, and the web app's own card

The watchlist screen from Rio's mockup: filter tabs, an item count, a sort
control, an Edit mode that removes several titles at once, and a grid of the
site's own poster cards.

**The card is the website's, not a new one, and that took two corrections to
get right.** The first cut drew a title, a meta line and a three-dot menu
under each poster — none of which the web app has. Rio's ruling, the same day:
use the original cards as they are in the web app, with the exact design,
behaviour and size; an inspirational mockup improves the experience around
them but never replaces a component we already ship. So the card is now the
poster and nothing else, three across at the site's own breakpoint ladder, with
the site's gutter and the site's premium crown. The title, year, genre and
runtime it used to print are still read — they moved to the card's
accessibility label, where the site itself puts the title as the image `alt`.

**A press plays, because that is what the web card's link does.**
`profile-hub/watchlist.blade.php` points every card at `watchlist_play` for a
movie and `watchlist_series_play` for a series, and both end in a player. The
app had been diverting to a details page the web card does not link to.

**`WatchlistPlayResolver` is new, and it exists so the two clients cannot
disagree.** "Play this series" has never meant "start at episode one" on this
site: `watchlistSeriesPlay` looks for the most recent *unfinished* episode in
the viewer's history and resumes it, falling back to the first. That rule was
inline in `FrontendController` and the app had re-derived it in TypeScript —
badly, starting at episode one and ordering seasons by number where the site
orders by id. It is now one service that the website's route and
`GET /api/v1/watchlist` both call, and the endpoint returns a resolved `play`
target per row. Same class of fix as `PlaybackAuthorizer` in 1.8.22.

**The endpoint also stopped being thinner than the page it came from.**
`GET /api/v1/watchlist` now sends `genres` (two names), `seasons_count` and
`season_number`, all of which `card-style.blade.php` has always rendered on the
website's watchlist and none of which the app could see. The season count is
`morphWithCount`, not a seasons load — the same N+1 the web controller added
its own count to avoid. These fields are on this endpoint only: a home screen
is thirteen rails of thirty cards and none of them show genres.

**Copy is cut throughout**, per Rio's instruction that screens carry no useless
guides or prompts. The explanatory sentence under the filter tabs is gone and
the empty state is one short line. `EmptyState` gained an optional `detail` so
a screen whose title says enough can say only that.

Also: `Focusable` accepts any real accessibility role and an
`accessibilityState`, so a filter tab announces itself as a tab rather than as
a button; `formatRuntime` moved to `ui/format.ts` rather than being copied out
of the detail screen; and two contrast defects introduced in the first cut were
measured and fixed before they shipped — a destructive button at 2.78:1 and a
selection tick at 2.90:1.


### Unreleased — Billing, the invoice, and the two-factor flow

The account screens the website has and the app did not. Five were asked for
and **two turned out to be completions rather than new screens**, which is the
finding worth recording: the plan ladder was already half of the website's
Membership page, drawn from the same `GET /subscription` call, and the security
screen's own docblock had scoped password change and two-factor enrolment into
a later slice. Building a second screen beside either would have shown a viewer
the same facts twice.

**Two-factor is a flow, and that is why it waited.** Slice 2b displayed the
status and stopped, deliberately: `POST /account/2fa` mints a pending secret
and two-factor is not on until `/confirm` succeeds, so a switch that turned it
on and never showed the recovery codes would lock people out of their own
accounts. The app now walks secret, authenticator, confirm, then eight recovery
codes behind an "I have saved my recovery codes" acknowledgement. Once it is
on, the security screen carries what the website's does: the codes, a fresh
batch behind a confirmation, and the way out behind the password.

**A pending setup is resumed, never restarted.** Calling `POST /account/2fa`
again mints a new secret and discards the old one, so somebody who had already
added Jambo to their authenticator would find their codes rejected forever. The
screen opens with the pending secret from `GET /account/security` instead.

**Cancelling a setup costs the password, and the website's does not.** The
website puts two-factor removal behind password-confirmation middleware, which
is a session concept with no token equivalent, so the API asks on the call
itself. The screen asks rather than firing a request that would be refused.

**The QR is kept and the manual key is made usable.** On a desktop the key is
the fallback; on a handset it is the main path, because nobody can scan a code
displayed on the device they are holding. So the key is grouped in fours and
selectable, which gives Copy through the platform's own long-press menu and
needs no clipboard package. The `otpauth://` URI is reproduced character for
character against what `pragmarx/google2fa` builds, so an entry scanned on the
website and one added in the app are the same entry under the same name.

**Billing's table becomes rows.** Five columns do not fit a phone, so the plan
is the label, the date is the second line, and the amount and status take the
right-hand side where the table's last two columns were. It uses the shared
`ListRow`, which exists because Rio asked for the UI to be uniform. **No new
design token:** the status badge spends the `okBg`/`okText`/`okBorder` and
`warning` values already captured off the site, which already mean exactly
this.

**Money is never recomputed.** Amounts arrive as decimal strings and the
separators are inserted into the string; nothing is parsed into a float and
printed back, and the invoice total is re-rendered from the same string rather
than summed. A test pins it past `Number.MAX_SAFE_INTEGER`, because that is the
only place the two implementations differ and a guard nothing can break is not
a guard.

**Two absences, each named rather than faked.** There is no Print button: the
website's is `window.print()` and the native equivalent needs `expo-print`, a
native module and therefore a prebuild. There is no Deactivate account card
either; `DELETE /account` exists and signing every device out permanently earns
its own slice.

**Where Billing is reached from, and why it is two places.** The website's own
Billing page has no entry in the profile-hub sidebar and is linked only from
the payment-complete page, which the app has no equivalent of. Rio's call: a
row in the profile menu and an Order history row on Membership.

**The badges and the invoice table are the website's, measured.** Rio ruled the
same day that the app wears the webapp's own assets at their exact design,
behaviour and size, and that an inspirational screen never replaces one. Two
components here failed that: the status badge had been approximated from the
semantic ok and warning colours and drew a hollow outline where the site paints
a solid fill, and the invoice's line item was a hand-built table beside the
site's own. `design/export-tokens.mjs` gained the profile hub's billing page as
a second signed-in capture — the hub's CSS lives in a `<style>` block in its
layout, so it exists on no public page — and both components now read from
probes. Tokens went from 117 to 143, with nothing existing moved.

🔴 **That capture found a live accessibility defect.** The site's
`.badge.bg-warning` is white text on `#ffd81c`, a contrast ratio of 1.39 to 1
where WCAG asks 4.5, so the website's own Pending badge is effectively
unreadable. Rio's call: keep the site's fill, darken the text in the app, and
fix the website separately so the two converge. It is the only value in the
badge block that deviates from what was captured.

**Screen copy is cut to what carries a fact**, per Rio's other ruling the same
day. The sentences under Order history, Current plan and All plans are gone, as
is the paragraph explaining what an authenticator app is. What stays is what a
viewer cannot otherwise see: that changing a password signs out every other
device, that a pending two-factor setup has an unconfirmed secret behind it,
and the two confirmations before a destructive action.

New work landed feature-shaped under `src/features/billing` and
`src/features/security`, per Rio's modular ruling. `src/api/endpoints.ts` was
not grown.

### Unreleased — The player

Phase 2's last screen, and the one every other screen was apologising for.
Movie and series pages said *"Playback arrives in the next update"*; Continue
Watching cards were deliberately not interactive because resume needed this.
Both lines are gone. No PHP was touched.

**`expo-jambo-media`, Kotlin on Media3, is now the app's only native code**
(ADR-0002). It draws frames and reports state, and nothing else: every control
a viewer touches is React Native. That split is the point rather than an
accident. The controls have to wear the design tokens captured off the
website's own watch page, they have to be reachable by a d-pad through the
app's existing `Focusable`, and a control written in Kotlin can be neither unit
tested nor changed without a native rebuild. `useController` is false and stays
false. Phase 3 adds the encrypted cache to this same ExoPlayer's data-source
chain, which is the reason for writing Kotlin now instead of reaching for
`expo-video` and replacing it later.

**The design tokens now come from behind the paywall.**
`design/export-tokens.mjs` signs in through the site's own login form and
captures `/watch/{slug}`, which needs a subscription. It found the player's 33
tokens — and three faults in the exporter itself, each of which would have
produced a plausible wrong answer:

1. 🔴 **A signed-in run silently deleted the sign-in screen's design.** The
   browser profile is deleted at startup inside a try/catch, and that delete
   fails whenever the previous run's browser still holds the files — which it
   usually does, because `browser.kill()` kills the spawned process while
   Edge's renderer and GPU children survive it. Twenty were counted after two
   runs. Harmless while every page was public; the moment the script learned
   to sign in, run two inherited run one's session cookie, `/login` redirected
   to the account page, and every `authCard` and `authField` probe matched
   nothing. Caught only because `color.surface` is on the required list and
   comes from that page. Fixed with a unique profile per run, which a lock
   cannot defeat.
2. 🔴 **Every colour in the control bar came back empty.** `player.css` is
   written almost entirely in `oklch()` and relative colour —
   `oklch(from currentColor l c h / 0.2)` — which Chromium returns from
   `getComputedStyle` verbatim, and the exporter's parser understood only
   `#rgb` and `rgba()`. Three conversions were measured against the browser
   and only one works: computed style preserves `oklch`, canvas `fillStyle`
   echoes it back unchanged, and painting it to a one-pixel canvas and reading
   the pixel converts it exactly. So that is what it does, which keeps the
   browser as the single authority on colour rather than putting an
   OKLab-to-sRGB implementation in the repository to disagree with the screen.
3. The pill radius `calc(infinity * 1px)` computes to `3.35544e+07px`, which
   the number parser rejected — so the seek bar's radius read as null, which
   means "no radius" when the truth is the exact opposite.

**The scrim is a gradient because the tokens said so.** The question of
`expo-blur` versus a gradient was settled by capture, not preference:
`color.playerControlsBg` is `transparent`, `effect.playerControlsBackdrop` is
`none`, and `effect.playerOverlay` is a real `linear-gradient`. The site
darkens behind its controls with a scrim and nothing else, so adding a blur
dependency would have imitated something the website does not do.

**The quality menu has two options because there are two files.**
`video_quality` is `auto | high | data_saver` and is not a resolution ladder:
Jambo has exactly two renditions, so `high` means the default file and
`data_saver` means the low one. A menu reading 4K/1080p/720p would be three
labels for two files. Choosing one writes to `/account/preferences` rather than
to the handset, so the answer follows the viewer to their television in Phase
4 — and it PATCHes only the key that moved, because the endpoint merges and a
client resending the whole set would silently reset a field nobody touched.

**No subtitles menu, and that is a deviation from what was asked.** There is
no subtitle or caption track anywhere in the Streaming or Content modules, so
the menu would open onto an empty list and a viewer who tapped it would
conclude they had turned something on. Raised rather than taken quietly.

**The heartbeat interval is the server's.** `POST /playback/sessions` returns
`heartbeat_seconds` and the app uses it; the number is never a constant in the
client. A shipped APK cannot be re-tuned, so if beats ever cost too much on the
VPS that number moves server-side and every installed copy slows down without
a release. A final beat fires on the way out, so leaving mid-film resumes where
the viewer actually was rather than up to fifteen seconds earlier.

**Remote keys are built now, though TV is Phase 4.** Play/pause on select,
±10s on left and right, and a menu on down, all through `useTVEventHandler`,
which is inert on a phone build. Retrofitting key handling into a finished
player means revisiting every control and every focus order; building it
alongside costs one file.

**Continue Watching resumes with no server change**, and the reason is worth
recording. The card carries `resume: {type, id}` and no slug, and the detail
endpoints are slug-only — `/movies/1` is a 404 — so slice 2b could not even
fall back to opening the title's page and shipped the card deliberately inert.
`POST /playback/sessions` takes exactly that pair. The play mark is back with
it.

**A development catalogue that can actually be played.** Nothing in the local
database was playable: every title carries a placeholder path, no CDN zone is
configured, so the endpoint returned a bare relative path and the player had
nothing to load. `tools/dev-catalogue/9-playable-video.php` points six movies
and one whole two-season series at real files, and `video-server.mjs` serves
them — a second process because `php artisan serve` answers
`Range: bytes=1000-2000` with the whole 4.3 MB file and a `200`, so seeking
would re-download the film, and because it is single-threaded, so video bytes
would block the heartbeat behind them.

Not built, and named rather than missed: everything in Phase 3 (downloads, the
encrypted cache, licences, the offline home) and no Download control anywhere;
subtitles; picture-in-picture, background audio and lock-screen controls;
Chromecast; guest playback; TV chrome beyond keys and focus; and checkout on a
refusal, which routes to the informational Plans screen per ADR-0004.
`FLAG_SECURE` is a prop that exists and is off — ADR-0003 wants it for offline
playback, and online the website streams the same bytes to an unprotected
browser.

### 1.0.0 — The shell, the tokens and the front door

The first slice of Phase 2 of `docs/plans/mobile-offline-app.md`. An Expo app
exists in `mobile/`, it identifies itself to the API, and a subscriber can sign
in on it and see their plan and their devices. No PHP was touched.

**The design is exported from the running site, not copied from the SCSS.**
`design/export-tokens.mjs` drives headless Edge over the DevTools protocol,
loads four public pages at a 390×844 phone viewport, and reads computed styles
off real elements. It writes `design/tokens.json` — 47 tokens, each one
carrying the exact selector and property it came from — and
`mobile/src/ui/tokens.json`, which the app imports. The Streamit bundle ships
four skins and Jambo runs one of them, so reading `_variables.scss` would have
been right about a quarter of the time with no way to tell which quarter.

Three values in the plan's own table turned out to be wrong, which is the
argument for the script in one paragraph. **The background is `#000000`, not
`#0b0d17`** — that colour is the PWA manifest's `background_color` and nothing
renders it. `--bs-success` is `#27ae60`, not `#14e788`. And **`--bs-danger` is
`#545e75`, a slate blue**: whoever themed this build overrode Bootstrap's red,
so the conventionally-named token would have produced error messages in a
colour that reads as ordinary text. The app's error colours come from
`.jambo-auth-alert` instead — the styles the website actually shows somebody
whose password is wrong — captured by injecting the element, because a cleanly
loaded page has no error on it to read.

**Two capture bugs were found and fixed before the values were trusted**, both
of the kind that produce a plausible wrong answer. The sign-in page autofocuses
its email input, so the first run recorded the *focused* border — the primary
blue — as the resting one; every quiet outline in the app would have been
bright blue. And because that field carries `transition: border-color 0.15s`,
blurring and reading in the same tick still returns the focused colour: the
probe has to wait for the transition to settle. Resting and focused states are
now captured separately and both match the CSS by inspection.

**Every request carries `X-Device-Id`, and the client refuses to send one
without it.** The uuid is minted on first launch and kept in the platform
keystore under its own key, deliberately away from the session: a device id
cleared on sign-out would file a fresh row in `devices` on every login, fill
the viewer's device list with handsets they cannot recognise, and stop
`active_streams` recognising the same phone as the same phone — so the
concurrent-stream cap would silently stop counting it. There is a test for
that, and it was checked by mutation: making `clearSession()` delete the key
must fail it.

That mutation also exposed a weakness in the test itself. `randomUUID` was
mocked to a constant, so a regenerated id was indistinguishable from a
surviving one and the test stayed green while the thing it names was broken.
The mock now returns a different value on every call.

**Types are generated from the contract; the client is not.**
`openapi-typescript` emits `src/api/schema.d.ts` from `docs/api/openapi.yaml`,
and `npm run api:check` regenerates and fails on any drift — the app-side
counterpart of `OpenApiSpecTest`. The client itself is written, because it has
to do things a generated one does badly: hold the envelope, own two headers no
caller may override, and keep the whole refusal body. That last one is not
theoretical — `POST /auth/login` answers two-factor as a **422** with the
`challenge_token` at the root of the *error* envelope, so a client that keeps
only code, message and field errors throws away the next step of a successful
sign-in.

**Screens: sign in, two-factor, account, devices, and an update gate.** They
branch on `code` and never on message text. Sign-out clears the local session
whether or not the server can be reached, because a viewer tapping Sign out on
a phone with no signal is signed out of that phone. A 401 from anywhere clears
the session, which is what happens when somebody boots this handset from the
website's device list.

**One deliberate departure from the site, and it is invisible.** The site's
form field is 44dp and its button 46dp; Android's touch-target minimum is 48.
Controls are *drawn* at the site's height and given a touch target padded out
to 48, so the design is unchanged and the target is reachable.

Not built, and named rather than missed: register, Google sign-in, forgot
password, push (the API has the token registry but no sender), the `direct`
build's PesaPal checkout, anything in Phase 3, and TV beyond installing
`react-native-tvos` and its config plugin so the door stays open.

## Jambo

### 1.8.44 — The media picker gets the file manager's toolbox

Rio, 2026-09-12, on the picker used by the movie and series edit screens:
*"the three dots should be more options that comes even when right click [...]
bring every feature as we have it in the file-manager"*.

The picker is the same gallery in an iframe with `?picker=1`, and our own
`custom.js` strips the UI so a click selects a file rather than opening a
player. Three things then killed the menu: the CSS hid it; the three-dot button
is rendered **inside** the file anchor, so the capture-phase interceptor that
swallows `.files-a` clicks swallowed the button too; and `mousedown` fires for
every button, so a right-click was cancelled before the menu could open.

Rename, Move, Copy, Duplicate, New Folder, New File, Upload, Download, Copy
Link, Show Info and Open In New Tab are reachable again, by button or
right-click, keyboard included.

**The preview stays suppressed on purpose.** A plain click selects; it does not
open the lightbox or start a film in the middle of a form. So the menu's
"Open" does nothing here. Undoing that means removing the PhotoSwipe
suppression and the media-killer, which risks video autoplaying in an edit
screen — not something to ship unverified.

🔴 **`custom.js` is now force-overwritten on install.** It was not, so this fix
would have been skipped on every server that already had a copy: deployed in
appearance, absent in fact. `config.php` stays out of that list because it is
genuinely edited by admins and by the gallery itself, and is repaired
key-by-key instead.

**Not verified:** the menu opening in a browser. The change is source-level and
the tests are source-level. Open a movie edit screen and try the three dots and
a right-click.

### 1.8.43 — Self-hosting, finished properly this time

1.8.41 self-hosted the fourteen assets the gallery's page declares and broke
drag-and-drop, because the bundle lazy-loads twelve more packages on first use.
1.8.42 withdrew it. This finishes it, with the list **derived rather than
written down** — hand-listing is what failed, twice.

**`php artisan filemanager:vendor`** reads the gallery and works out everything
it can ask for: the declared tags, the plugin registry inside the bundle and
the files each plugin needs, CodeMirror's 121 syntax modes, and the three
per-language families. 280 assets, 4.6 MB. Re-run it after upgrading the
drop-in; `--check` reports gaps without downloading and needs no network.

**Two derivation bugs it caught that a hand list never would.** The uploader's
translations are keyed by full locale (`ar_SA`, not `ar`), so the obvious
derivation 404s all 41 of them. And the language picker maps some languages to
a different country's flag — `ar` is drawn with Saudi Arabia's, `en` with the
United Kingdom's — so deriving flags from the language code downloads files
that **exist and are wrong**, which is worse than a 404 because nothing reports
it.

**One asset genuinely is not published:** the gallery offers Norwegian as `no`,
dayjs ships `nb` and `nn`. That 404s on the CDN exactly as it would here, so it
is recorded in `UPSTREAM-MISSING.txt` and the check ignores it. "Missing" had
two meanings and only one of them is a bug.

**Verified by serving, not by reasoning.** All 279 vendored files fetched over
HTTP, every one a 200. The rendered page references 14 local assets and zero
external ones. Removing `uppy` — the exact package whose absence broke
production — fails the completeness test and nothing else.

🔴 **Never set `assets` again without `filemanager:vendor --check` passing.**
That check is the only thing standing between a config key and a silently
broken admin tool, and it is wired into the test suite so it cannot be skipped.

### 1.8.42 — Self-hosting is withdrawn, because 1.8.41 broke the file manager

Rio, on the live site after deploying 1.8.41: *"some features are lost like the
drag and drop feature along side another feature like the right click"*.

**My regression, and the cause is worth writing down.** The gallery's page
declares fourteen assets. Those were vendored, and the test checked them. But
the 320 KB application bundle **lazy-loads twelve more packages** the first time
a feature is used — `uppy` for drag-and-drop uploads, `plyr` and `hls.js` for
playback, `codemirror` for editing (with a syntax file fetched per language),
`pannellum`, `jsmediatags`, `headroom`, `marked`, `intersection-observer`, plus
date locales and flag icons. Repointing the asset path without those meant
every one of them 404'd, and a 404 on a lazily-injected `<script>` raises
nothing anybody can see. The features simply stopped existing.

**A test that reads what the page declares can never catch what the bundle
fetches at runtime.** That is the gap, not bad luck.

So `assets` is no longer enforced and the gallery is back on its CDN, which
works. `filemanager:install` now **actively deletes** a live `assets` line
rather than merely not writing one — every machine that took 1.8.41 has it
written down, and leaving it there would leave them broken.

The vendored files stay in the repo, staged rather than in use, because
finishing the job is still the right thing.

🔴 **Finishing it needs the remaining packages vendored** — roughly five
megabytes, much of it enumerated only inside a minified bundle — **and a check
derived from that bundle rather than hand-written.** A hand list is exactly
what failed. A new test now permits only two states: `assets` unset, or
`assets` set with every package the bundle names present. The half-state that
broke production is no longer expressible.

**Kept from 1.8.41 and unaffected:** the large-folder settings, the config
enforcement that survives the gallery's settings panel, and the in-app
updater running `filemanager:install`.

### 1.8.41 — The file manager is actually self-hosted now, and survives its own settings panel

Rio, 2026-09-12, on the file manager lagging: *"i thought we fully host the
filemanager? so that we don't get these request issues"*.

**We did not.** Files Gallery loaded its stylesheet, its 320 KB application
bundle and eleven libraries from `cdn.jsdelivr.net` on every cold open, **with
no integrity hashes on any of them**. That is 680 KB of unverified third-party
JavaScript executing inside an authenticated admin session that can upload to
and delete from the entire media tree. The latency was the symptom; the
supply-chain exposure was the finding.

All 44 files — 14 scripts and stylesheets plus 29 translations — are now
vendored in the repo and served from our own domain. Nothing upstream is
patched: `assets` is the gallery's own documented switch for this, so a gallery
upgrade stays a straight file replacement.

**Why the settings kept vanishing.** The gallery REWRITES
`_files/config/config.php` whenever anyone saves its settings panel, keeping
only what that panel knows about. That is how `menu_max_depth` was lost on
production, which is why a folder of 1,761 title directories stopped loading at
all. `filemanager:install` now re-asserts three keys on every run and leaves
every other line, including the admin's own comments, untouched.

**Measured, on a local copy of the live folder.** Listing 1,761 directories
costs 4 ms. Opening each one hunting for a poster costs 873 ms, and opening
each one again checking for subfolders costs 706 ms. So `folder_preview_image`
and `menu_max_depth` remove over half the work before a byte is sent, and lazy
loading in the browser could not have helped — the gallery already fetches its
listing separately and caches it.

🔴 **It refuses to claim self-hosting it cannot deliver.** If the vendored files
are missing or only partly copied, the `assets` key is withdrawn and the
gallery falls back to the CDN. A wrong local path would 404 every script tag
and leave an admin staring at a blank screen, which is strictly worse than the
thing being replaced. Six tests cover this, including the fallback and its
recovery; disabling the guard fails exactly one of them and nothing else.

**Three more guards came out of reviewing it before deploying.** The install
now **mirrors** the vendor directory rather than copying over the top, so an
upgrade cannot leave two gallery versions side by side. It **checks the
version**, because forty-four files of the wrong version still count as
forty-four and would 404 every one of them — the file count alone was not proof
of anything. And the config is written with `var_export` through
`preg_replace_callback`, so a value containing a quote, a dollar or a backslash
cannot corrupt the one config file an admin has.

**Not fixed, and not ours:** `php -l` segmentation faults on that server for
any file, which is the ionCube loader rather than anything in this codebase. It
made several diagnoses look like syntax errors that were not.

### 1.8.40 — Category shelves are dealt through the page, and the switch says when it does nothing

Rio, 2026-09-11: *"i have noticed the categories are packed into one block
after another yet we wanted it to place one category every after two sections
[...] also if we have more than 5 categories active we are showing about 4 of
them, i think it's a bug."*

**The block is dealt out.** 1.8.38 made the category shelves one movable block,
which was right for the admin screen and wrong for the page. The Categories row
is still one draggable row — its position now says where the FIRST shelf goes,
and the rest follow one after every two other sections. The gap is
`frontend.home.category_gap`, defaulting to two, so it changes without a
deploy; zero restores the block.

**One algorithm, both surfaces.** `HomeSection::spreadCategories()` runs at the
end of `arrange()` for the app and inside `webPlan()` for the website, so the
same shelves land in the same gaps. Two edges are deliberate: categories that
outlast the page fall consecutively at the end rather than being dropped, and
sections that outlast the categories simply continue.

**Where a shelf lands is still your drag.** With the Categories row near the
bottom there are few sections left to deal into, so most shelves stack at the
end. Move the row up and they spread from there.

**The missing categories were not a cap.** 1.8.38 already removed the limit of
four and that is live. A Visible Home category is dropped for exactly one
reason now: it has no PUBLISHED titles, because a shelf cannot be built from
drafts. Nothing said so — the Categories screen showed the switch on and a
count that includes drafts, and the home page silently disagreed. That row now
reads "Nothing published, so no shelf" when the switch is on and the published
count is zero. The honest fix is to say it, not to render an empty rail.

**Also:** `category-rails.blade.php` is gone. Shelves are dealt individually
now, so the partial that looped them has nothing to loop.

### 1.8.39 — Seven shelves the product does not have any more

Rio, over the arrangement screen with seven rows switched off: *"now we need to
remove these completely, we don't need them we don't have them anymore."*

**Three of them were retired months ago and 1.8.38 brought them back by
accident.** `ott-page.blade.php` used to carry three "random category" shelves
whose own comments said what they replaced: Top Picks for You, Popular Movies,
and Fresh Picks Just For You. Rebuilding the home page from `home_sections`
seeded that table from every rail the API could BUILD rather than every shelf
the product actually HAS, so the retired three came back on the website. Latest
Movies, Latest Series, International Series and Upcoming go with them.

Gone completely, not hidden: the rows, both registry constants, the seven API
rails, the five website partials that nothing else included, and the four
shared collections that only fed them. Eleven sections remain.

**`latest-movies` and `upcomming` survive as partials** because `/home` — the
older index page — still renders them. Their home-page rails do not.

**Three rail archives went with their only entrances.** `/collection/top-picks`,
`/collection/fresh-picks` and `/collection/popular-movies` were reachable only
from the "View all" on the shelves now deleted. Neither the sitemap nor the SEO
module ever referenced them.

🔴 **`TopPicksRecommender::forUser()` and `forGuest()` now have no caller.**
They are the personalised Top Picks engine from
`docs/plans/top-picks-personalization.md`, and the shelf they ranked is gone,
so the `frontend.recommendations.enabled` flag is unreferenced too. Left in
place deliberately — deleting a personalisation engine because a shelf was
retired is a product decision, not a cleanup. It needs an answer either way.

**Also fixed:** the home route was still running an Upcoming query for a
section that no longer exists, and the category-pool comment still described
the fixed-slot layout removed in 1.8.38.

### 1.8.38 — Home Sections governs the website, not only the app

Rio: *"i feel it's off [...] it's not meant for the mobile app but for the
whole system. [...] having things half done for the seck of doing is as good as
useless."*

`/admin/home-sections` shipped in 1.8.36 and worked, on one surface. An admin
dragged a row, the app's home screen changed, and jambofilms.com carried on
drawing a different home page — twelve of the eighteen sections, in a different
order, with three rotating category shelves standing in for rails that had been
retired. Nothing on the screen said so.

**The website's home page no longer lists its shelves.** `ott-page.blade.php`
was fifteen hard-coded `@include`s; it is now one, and order, visibility and
heading for both surfaces are the `home_sections` rows. Moving a shelf by
editing that Blade is now a regression, and the file says so.

**The plan's estimate was wrong, and reading the code is what showed it.**
`docs/plans/homepage-section-arrangement.md` §3 called the two surfaces'
vocabularies structurally mismatched and deferred the reconciliation behind an
ADR. The website in fact already had a partial for almost every rail it was not
drawing — `top-pict`, `latest-movies`, `fresh-picks-just-for-you`,
`Popular-movies`, `best-of-international-shows` — each reading a collection
`HomeRailsService::forWeb()` already returned, each simply never included. Only
Latest Series had no partial, and it is `latest-movies` with the show
collection. The three `random-category-rail` slots were not a rival vocabulary
either: their own comment says they replaced Top Picks, Popular Movies and
Fresh Picks, which are exactly the rails that came back. They are gone and the
category shelves are one movable block, which is what `/api/v1/home` already
sent.

**So the home page now draws six shelves it was missing** and the category
rails sit together. Every one of them is one click away from being switched off
on the screen, which is the point of the screen. See ADR-0007.

**An admin's label now renames the heading on the website too.** Each section
partial takes `$sectionHeading` and falls back to the translation key it always
used, so every other page that includes them is unchanged.

**A section with nothing in it is dropped**, which is the rule `/api/v1/home`
already followed. A heading over nothing is worse than no heading.

**The screen itself lost its prose and its duplicate.** A paragraph explaining
that dragging a row moves it, above a list of draggable rows; and a status
badge repeating what the switch beside it already said. Both gone. The column
is now headed "Website & app", which carries the fact the paragraph was
carrying. The technical key had also been title-cased by the table theme —
`Top_movies` for `top_movies` — which is a literal identifier the app keys on
and now renders as stored.

**A cap of four category shelves is gone with the layout that needed it.**
`HomeRailsService` handed the page one "fixed" category plus three more,
because the old page had one fixed slot and three rotating ones. With the
shelves collapsed into one movable block that split described nothing, and the
cap was quietly overruling the admin: six categories were marked Visible Home
on the dev box and only four reached the page. It cost nothing to remove — the
cap was applied to the result, so the query had already read them all. Six
categories now produce six shelves, on the website and in `/api/v1/home` alike.

🔴 **The number of home category shelves is now the admin's**, set by the
Visible Home toggle and bounded by nothing in code. Before flagging a large
number, read the note now in `HomeRailsService`: each shelf eager-loads every
published title in its category to keep twelve, and bounding that properly
needs a window function rather than a `take()`.

**Not changed: the API payload.** No rail key added, renamed or reordered. No
file under `mobile/` touched.

### 1.8.37 — The billing page has been throwing a 500 since it was written

🔴 **The profile hub's Billing and Invoice pages fail for any account that has
ever paid.** Found while porting them to the app, reproduced against the real
database, and repaired:

    RelationNotFoundException: Call to undefined relationship [tier]
    on model [Modules\Subscriptions\app\Models\SubscriptionTier]

`ProfileHubController::billing()` eager-loaded `payable.tier` and both blades
read `$order->payable?->tier?->name`. But `payable` **is** the
`SubscriptionTier` — the polymorphic target of the order — not a row that has
one, and that model has no `tier` relation. The correct expression is
`$order->payable?->name`.

**Why nobody reported it.** An account with no orders never loads a payable, so
it lands on the empty state and the page renders perfectly. Every test that had
touched these pages was written against an empty order list. The failure needs
one completed payment to appear, and then it is total.

Fixed in four places — the two eager loads and the two blade expressions — with
`tests/Feature/ProfileHubBillingTest.php` pinning the case that matters: **the
page renders for an account that HAS orders**, and prints the plan that was
bought rather than the em dash the broken expression fell back to. The empty
state is pinned alongside it, so a repair that fixed one by breaking the other
cannot pass.

**`GET /api/v1/subscription/orders` could not say what was bought.** The
resource returned a `description` field; `payment_orders` has no such column
and never had one, so it was null on every order in the table's life. It is
replaced by `plan` (name and billing period), and joined by `payment_method`
and `tracking_id`, which are what the website's invoice actually prints. An
order pointing at a payable that is not a plan returns `plan: null` — the table
is polymorphic by design and is meant to carry rentals later — rather than
guessing. `docs/api/openapi.yaml` updated; the app's types regenerate from it.

The orders list now eager-loads its payables. Without that, a page of fifteen
orders was sixteen queries.

### 1.8.36 — The homepage sections are dragged, not deployed

Rio: *"we are planing to build the drag feature where we rearrange the sections
on the homepage... i was thinking of having the api that is responsible of the
arrangement so we apply it on the app to keep things organised and updated."*

**Almost all of this already existed, and the app needed no change at all.**
`/api/v1/home` has always returned one ordered list of rails with stable keys,
and `HomeScreen.tsx` has always rendered whatever order it was handed. The only
thing missing was somewhere to store an order other than the literal array in
`HomeController::show()`. So this release is a table, a sort, and a screen —
**not one line of `mobile/` was touched**, which is the point rather than an
omission.

`home_sections` holds one row per section: `key`, an optional `label` override,
`position`, `enabled`. It ships seeded with exactly the order the controller
already hard-coded, so deploying it changes nothing until an admin drags
something. The admin screen at `/admin/home-sections` is the Featured screen's
shell, its SortableJS wiring and — deliberately — the *same* reorder request
the Featured and Categories screens already send. This is the fourth drag list
in the product and a fourth ordering contract would have been one too many.

🔴 **`home_sections.key` is a foreign key into a published contract, and the
migration's docblock says so.** The app keys two behaviours on these literal
strings beyond ordering, in `mobile/src/api/catalogue.ts`: `isNumberedRail()`
gives `top_movies` and `top_series` the Top 10 numerals, and
`collectionKeyFor()` decides whether a rail gets a "View all" and where it
points. The rail and collection key spaces do not match — `/home` is
underscored, `/collections` hyphenated, three rails have no archive, and
`exclusives` maps to `only-on-streamit` by an exception with no rule behind it.
So adding a section is free; **renaming one is a breaking change**, and it
fails silently as a dead button rather than as an error.

**Two rules stop an admin, or a deploy, from losing a shelf.** A rail whose row
does not exist yet still renders — last, never dropped — so shipping a new rail
before its row exists cannot silently cost the home screen a section. And a
missing or empty table falls back to the order the controller built, which
matters more than it sounds: shipping without that fallback took
`GET /api/v1/home` down on the dev box for the time between the code landing
and the migration running, and every home screen rendered a raw
`QueryException`. The home screen is the first request an app makes and there
is nothing behind it, so it degrades rather than fails. There is a test that
drops the table and expects a 200.

Category shelves are one movable block, not sixteen rows. Their order among
themselves is already set on the Categories screen, and two controls for one
order is how two orders start disagreeing.

**The website's homepage is deliberately untouched.** `ott-page.blade.php`
describes its sections with a different, non-matching vocabulary — its Top 10
sliders are the *day's*, not the week's, and it has three fixed
`random-category-rail` slots the API does not expose at all. It is also
Streamit template Blade, which the ground rules say not to restructure
casually. Reconciling the two vocabularies is stage 2 and gets its own ADR.
See `docs/plans/homepage-section-arrangement.md`.

### 1.8.35 - Streaming preferences, and the flag the app was guessing

Two additions to `/api/v1`, both asked for by Rio while the mobile profile
drawer was being built: "let the api expose them all as they are needed", and
then "all can edit and update their streaming preferences".

**`GET` and `PATCH /account/preferences`, with a `streaming_preferences` JSON
column on `users`.** Video quality, autoplay-next and Wi-Fi-only downloads.
The design decision worth recording is that these live on the **account**, not
on the device. Rio was explicit about it, and it is what makes the same
answers hold on a second handset and, in Phase 4, on the television - a
data-saver choice made on a phone is worthless if the TV has never heard of
it.

**`video_quality` is `auto | high | data_saver`, and that is deliberately not
a resolution ladder.** Jambo has exactly two renditions, `video_url` and
`video_url_low`, and `/playback/sessions` selects between them with
`quality: default|low`. A menu offering 4K / 1080p / 720p / 480p would be four
labels over two files, three of them false. `high` asks for the default
rendition, `data_saver` for the low one, `auto` lets the client decide from
the connection it is on. Anyone wanting a third label should ship a third
rendition first.

**Subtitles were considered and left out.** There is no subtitle or caption
track anywhere in the Streaming or Content modules. A preference for something
the player cannot deliver is worse than its absence, because the viewer
believes they turned it on.

The defaults are `auto` and Wi-Fi-only downloads on, chosen for somebody on
MTN data who has never opened the screen rather than for somebody at a desk.
`PATCH` rather than `PUT` so a screen of switches can send only the one that
moved: a client that resent the whole set and forgot a field would silently
reset it, which is how a data saver turns itself off when a viewer toggles
autoplay. Ten feature tests, and the two load-bearing guards - a partial patch
leaving the rest alone, and an unrecognised stored quality falling back rather
than reaching the player - were each proved by mutation and restored.

**`features.referrals` on `/app/config`.** The website's profile sidebar hides
its Refer & Earn tab when `ReferralSettings::active()` is false; the app had
no way to know that and would have offered viewers a page the admin had
switched off. Same source of truth, now exposed.

A note for whoever edits PHP next. `AppConfigController` shipped broken for
part of that session: a Python heredoc turned the `\a` of
`Modules\Referrals\app\...` into a literal BEL byte, so the file was a
ParseError and `/app/config` - the first call the app makes on launch - 500d.
`grep -rlP '\x07' --include=*.php` finds that class of fault. Write PHP with a
file-writing tool, never through a shell or Python string.

### 1.8.34 — The review pass: four defects the suite could not see

Rio asked to look at what had just been finished. So every one of the 75
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
