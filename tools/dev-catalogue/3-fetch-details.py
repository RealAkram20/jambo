"""Read real synopses and years off the live detail pages.

Each page carries JSON-LD (`Movie` / `TVSeries`), which is a stated contract
for machines rather than markup that changes with the theme — so this reads
`og:` and JSON-LD rather than scraping the rendered card, and does not break
the next time a blade is restyled.

Development aid. Writes JSON; a PHP script applies it to the LOCAL database.
"""

import html
import json
import re
import sys
import time
import urllib.request

BASE = "https://jambofilms.com"

OG_DESC = re.compile(r'<meta property="og:description" content="([^"]*)"')
LD_DESC = re.compile(r'"description"\s*:\s*"((?:[^"\\]|\\.)*)"')
YEAR = re.compile(r'"(?:dateCreated|datePublished|copyrightYear)"\s*:\s*"?(\d{4})')


def fetch(path: str) -> str:
    request = urllib.request.Request(
        BASE + path, headers={"User-Agent": "Mozilla/5.0 (jambo-app dev seed)"}
    )
    with urllib.request.urlopen(request, timeout=30) as response:
        return response.read().decode("utf-8", "replace")


def clean(text: str) -> str:
    text = text.encode().decode("unicode_escape", "ignore") if "\\u" in text else text
    return html.unescape(text).replace("\\/", "/").strip()


def main() -> None:
    titles = json.load(open(sys.argv[1], encoding="utf-8"))
    out = []
    ok = miss = fail = 0
    started = time.time()

    for index, title in enumerate(titles):
        path = ("/series/" if title["is_show"] else "/movie-detail/") + title["slug"]
        try:
            page = fetch(path)
        except Exception as error:  # noqa: BLE001 - dev script
            print(f"  FAILED {title['slug']}: {error}", file=sys.stderr)
            fail += 1
            continue

        # Prefer the longest description available: og: is often truncated with
        # an ellipsis while the JSON-LD carries the whole thing.
        candidates = [clean(m) for m in LD_DESC.findall(page)]
        og = OG_DESC.search(page)
        if og:
            candidates.append(clean(og.group(1)))

        synopsis = max(candidates, key=len) if candidates else ""
        if synopsis.endswith("...") and len(candidates) > 1:
            longer = [c for c in candidates if not c.endswith("...")]
            if longer:
                synopsis = max(longer, key=len)

        year_match = YEAR.search(page)

        if synopsis == "":
            miss += 1
        else:
            ok += 1

        out.append(
            {
                **title,
                "synopsis": synopsis,
                "year": int(year_match.group(1)) if year_match else None,
            }
        )

        if (index + 1) % 20 == 0:
            print(f"  {index + 1}/{len(titles)} ({time.time() - started:.0f}s)")

    print(f"\nsynopsis found {ok}, missing {miss}, failed {fail}")

    with open(sys.argv[2], "w", encoding="utf-8") as handle:
        json.dump(out, handle, indent=2, ensure_ascii=False)
    print("Wrote " + sys.argv[2])


if __name__ == "__main__":
    main()
