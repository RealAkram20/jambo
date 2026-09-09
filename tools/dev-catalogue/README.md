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

## What it does not import

Trailers, cast photographs, episode stills and season structure. The seeded
versions of those stay. Nothing here needed them, and each is another set of
requests against a live site for a development convenience.
