"""Read the real Jambo catalogue off the live site's public pages.

Development aid only. It writes a JSON file; a separate artisan script applies
it to the LOCAL database so the app renders the real titles and the real
artwork instead of lorem ipsum and picsum placeholders.

Public pages only, and a handful of requests. Nothing is written to production.
"""

import json
import re
import sys
import urllib.request

BASE = "https://jambofilms.com"
PAGES = ["/", "/movie", "/series"]

CARD = re.compile(
    r'<a href="(?P<href>[^"]*?/(?:movie-detail|series)/(?P<slug>[^"/]+))"[^>]*>\s*'
    r'<img[^>]*src="(?P<src>[^"]+)"[^>]*alt="(?P<alt>[^"]*)"',
    re.S,
)


def fetch(path: str) -> str:
    request = urllib.request.Request(
        BASE + path,
        headers={"User-Agent": "Mozilla/5.0 (jambo-app dev seed)"},
    )
    with urllib.request.urlopen(request, timeout=30) as response:
        return response.read().decode("utf-8", "replace")


def absolute(src: str) -> str:
    """Turn a proxied `/img/storage/...?w=640` src back into the original file.

    The app's own `imageUrl()` decides the width it needs, so storing a URL
    that already carries `?w=640` would pin every card to the width the
    website's markup happened to ask for. The origin is kept absolute because
    these files live on production and the local server does not have them.
    """
    src = src.split("?")[0]
    if src.startswith("http"):
        pass
    elif src.startswith("/"):
        src = BASE + src
    else:
        src = f"{BASE}/{src}"

    # `/img/storage/x.png` is the resize proxy in front of `/storage/x.png`.
    return src.replace(f"{BASE}/img/", f"{BASE}/")


def main() -> None:
    seen: dict[str, dict] = {}

    for path in PAGES:
        try:
            html = fetch(path)
        except Exception as error:  # noqa: BLE001 - a dev script, report and move on
            print(f"  {path}: FAILED ({error})", file=sys.stderr)
            continue

        found = 0
        for match in CARD.finditer(html):
            slug = match.group("slug")
            if slug in seen:
                continue

            alt = match.group("alt").strip()
            title = re.sub(r"\s+poster$", "", alt, flags=re.I).strip()
            if title == "" or title.lower() == "movie poster":
                continue

            seen[slug] = {
                "slug": slug,
                "title": title,
                "poster_url": absolute(match.group("src")),
                "is_show": "/series/" in match.group("href"),
            }
            found += 1

        print(f"  {path}: {found} new titles")

    out = list(seen.values())
    movies = [t for t in out if not t["is_show"]]
    shows = [t for t in out if t["is_show"]]
    print(f"\n{len(out)} titles: {len(movies)} movies, {len(shows)} series")

    with open(sys.argv[1], "w", encoding="utf-8") as handle:
        json.dump(out, handle, indent=2, ensure_ascii=False)
    print(f"Wrote {sys.argv[1]}")


if __name__ == "__main__":
    main()
