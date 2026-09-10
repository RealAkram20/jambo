# Dressing the development catalogue in the real one

The seeded development database is lorem ipsum over `picsum.photos`
placeholders. That is fine for tests and useless for judging a screen: a rail
of grey rectangles tells you nothing about whether the app looks like Jambo,
and Rio's note on 2026-09-09 was exactly that — *"we need more data to match
the live website so that we have almost the real live website feeling"*.

These five steps read the **public pages** of the live site and re-dress the
**local** database with the real catalogue: real titles, real poster art, real
synopses, real years.

## It also fixes a blind spot, and that is the better reason

The dev seed stores every image as an absolute `https://picsum.photos/...`
URL. Production stores app-absolute paths — `/storage/gallery/Moana/download
(16).png`, already percent-encoded, with no scheme and no host. Those are
different enough that **an app that worked perfectly against the dev seed
could render no images at all on production**, which is precisely the defect
`mobile/src/ui/media.ts` was written to fix and could not be tested against.

After running these, the local API returns the production shape, so
`imageUrl()` and the site's `/img` resize proxy are both exercised for real.

## Running it

```bash
export JAMBO_CATALOGUE_DIR=/tmp/jambo-catalogue      # anywhere writable
mkdir -p "$JAMBO_CATALOGUE_DIR"

python tools/dev-catalogue/1-scrape-titles.py    "$JAMBO_CATALOGUE_DIR/live-titles.json"
python tools/dev-catalogue/2-download-posters.py "$JAMBO_CATALOGUE_DIR/live-titles.json" ./public 120
python tools/dev-catalogue/3-fetch-details.py    "$JAMBO_CATALOGUE_DIR/live-titles-local.json" "$JAMBO_CATALOGUE_DIR/live-details.json"

php artisan tinker --execute="require 'tools/dev-catalogue/4-import.php';"
php artisan tinker --execute="require 'tools/dev-catalogue/5-apply-details.php';"
```

Then warm the resize cache, or the first render is a screen of grey cards:

```bash
# php artisan serve is single-threaded. Fifteen posters requested at once queue
# behind each other and time out; once Glide has cached each size, they are
# instant. Ask for the widths src/ui/media.ts actually uses.
python - <<'PY'
import json, os, urllib.request
titles = json.load(open(os.environ['JAMBO_CATALOGUE_DIR'] + '/live-titles-local.json', encoding='utf-8'))
for t in titles:
    for w in (160, 480):
        urllib.request.urlopen(f"http://127.0.0.1:8095/img{t['poster_path']}?w={w}&fm=webp").read()
PY
```

## What it does and does not touch

- **Local database only.** Nothing is written to production, and nothing here
  is a migration — every column it writes is content, and every step is
  re-runnable.
- **`public/storage` is git-ignored**, so the ~31 MB of downloaded artwork
  never reaches the repository.
- Existing rows are **re-dressed in place**, keeping their tier, genres, cast
  and status, so the entitlement rules and the tests keep behaving as seeded.
  Extra rows are **cloned** from real ones, pivot payloads included — the
  `movie_person` pivot has a required `role` with no default, so syncing bare
  ids fails outright.
- Synopses and years are read from each page's **JSON-LD**, which is a stated
  contract for machines, rather than from the rendered markup, which changes
  whenever a blade is restyled.

## The test account

Rio's, named on 2026-09-09: **`testuser@jambo.test` / `Jambo@2026`**, on the
highest tier so nothing in the catalogue is out of reach. One named account
with a known password beats a fresh throwaway per session — every worklog
entry after this can say "signed in as the test user" and mean the same thing.

```bash
php artisan tinker --execute="require 'tools/dev-catalogue/6-test-user.php';"
php artisan tinker --execute="require 'tools/dev-catalogue/7-test-user-history.php';"
```

The second step leaves three titles part-watched, one of them finished, so
Continue Watching and History have real state — and differ, which is the
whole reason they are two screens.

⚠️ **Android autofill will fight you.** Tapping the email field on the sign-in
screen can trigger a saved credential from an earlier session and quietly
replace what you typed; that is how a scripted sign-in ended up authenticated
as `admin@demo.com` while the script had typed the test address. Read the
field back with `uiautomator dump` before submitting, and check the resulting
account rather than assuming.

## Bytes the player can actually play

Added 2026-09-09 for the player slice, and it is a real gap rather than a
convenience. **Nothing in this catalogue is playable.** Every title carries a
placeholder `dropbox_path` like `/jambo/movies/hidden-storm-29765.mp4`, no such
file exists, and no CDN zone is configured locally — so `CdnUrlResolver`
returns the stored value untouched and `POST /api/v1/playback/sessions`
answers with a bare relative path. Verified by calling the endpoint:

```json
{"source":"file","url":"/jambo/movies/hidden-storm-29765.mp4",
 "quality":"default","available_qualities":["default"],
 "resume_position":1512,"heartbeat_seconds":15}
```

The player could therefore be built and never rendered, and rendering it is
this project's bar for calling anything done.

```bash
# Points six movies and one whole series at two real files, and downloads
# them into public/storage/dev-video (git-ignored) on first run.
php artisan tinker --execute="require 'tools/dev-catalogue/9-playable-video.php';"

# In its own terminal, and it must be its own process — see below.
node tools/dev-catalogue/video-server.mjs
```

**Why a second server rather than `artisan serve`.** Two measured reasons, not
a preference:

1. **It does not implement range requests.** Asked for `Range: bytes=1000-2000`
   of a 4.3 MB fixture it answers `200` with all 4,372,373 bytes, not `206`
   with 1,001. A player seeking to the middle of a film would re-download the
   film, and the seek bar would look broken for a reason that has nothing to
   do with the app.
2. **It is single-threaded.** Video bytes and API calls would share one
   process, so a stream in flight blocks the heartbeat behind it. The posters
   already taught this project that lesson; a two-hour film is the same
   failure with a longer fuse.

Apache is not the answer either: its vhost does not expose this path, and
pointing it here means editing the web server that also serves the Precious
project.

**What gets a video, and why not everything.** Six movies — three with a low
rendition and three without — plus every episode of the first publicly visible
series, across both its seasons. That mix is deliberate:

- The three without a low file keep the `CONTENT_UNAVAILABLE` refusal
  reachable, which is a state the player has to render honestly. If every
  title had both renditions, the Data Saver refusal could not be tested.
- Most titles stay unplayable, so the "this title has no video yet" path stays
  reachable too.
- A whole series, both seasons, because "next episode" crossing a season
  boundary is the case a single-season fixture cannot exercise.
- ⚠️ **The series must be publicly visible.** The first season in this database
  belongs to `Wonder Man VJ NEIL`, whose status is `draft`, and
  `PlaybackAuthorizer::isReleased()` refuses every episode of a draft show. The
  obvious `Season::orderBy('id')->first()` produced six episodes that all
  answered `CONTENT_UNAVAILABLE`. Found by calling the endpoint rather than by
  reading the fixture back.

**The two fixtures are different films, on purpose and only here.** A 480p
Sintel trailer as the default and a 240p Big Buck Bunny clip as the low
rendition, because a quality switch that visibly changes the picture is
unambiguous evidence the switch reached the network. Nobody should read that
as a statement about how renditions work in production, where the two files
are the same title.

To put the database back exactly as it was, which clears `video_url` and
leaves `dropbox_path` untouched:

```bash
JAMBO_VIDEO_RESET=1 php artisan tinker --execute="require 'tools/dev-catalogue/9-playable-video.php';"
```

## Step 15: the two daily Top 10 shelves have something to rank

```bash
php artisan tinker --execute="require 'tools/dev-catalogue/15-daily-top-ten-views.php';"
```

Rio, 2026-09-10: *"we are not having the numbers, 2 in Movies Today, 3 in
Movies Today ... is it a bug"*. It is not, and the answer is worth knowing
before anybody debugs it again.

**Both Top 10 *of the day* shelves rank on distinct viewers in the last 24
hours**, and pad the rest of the ten from all-time popularity. Neither surface
prints a rank a title did not earn — a padded slide reads "Popular on Jambo"
instead of "#4 in Movies Today". On this machine exactly one movie and one
series had been watched inside the window, so one slide on each shelf carried a
number and the other twelve did not. The feature was working; the database was
empty of the only thing it reads.

**This is not step 7.** That one seeds the test account's own history, which is
what Continue Watching and History need. A ranking counts DISTINCT USERS, so
one account watching ten films ranks nothing at all. This writes a descending
number of distinct viewers per title across the first twelve users, so the
shelf orders 1..10 by the ranking rather than by its tie-breakers.

⚠️ **It refuses to run outside `local`**, and the guard is not decoration:
these are views nobody watched, in the table partner earnings are settled from.

Undo, which deletes only the rows it wrote — they carry
`session_id = 'seed-daily-views'` — and never a real one:

```bash
JAMBO_DAILY_VIEWS_RESET=1 php artisan tinker --execute="require 'tools/dev-catalogue/15-daily-top-ten-views.php';"
```

**Both shelves cache on a per-date key for a whole day.** Changing watch
history without clearing them shows nothing until midnight, which reads exactly
like a broken feature. Both the seed and the undo clear them, using the
recommender's own public constants.

## What it does not import

Trailers, cast photographs, episode stills and season structure. The seeded
versions of those stay. Nothing here needed them, and each is another set of
requests against a live site for a development convenience.
