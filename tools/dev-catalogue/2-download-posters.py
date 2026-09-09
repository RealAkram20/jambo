"""Download the live site's poster art into the local public/ tree.

Development aid. The point is not only that the app looks like the real thing:
storing the artwork at the same path production stores it means the local API
returns the same `/storage/gallery/...` shape production returns, so the app's
`imageUrl()` helper and the site's `/img` resize proxy are both exercised for
real — which is the one path the picsum-based dev seed could never test.

`public/storage` is git-ignored, so nothing here reaches the repository.
"""

import json
import os
import sys
import urllib.parse
import urllib.request

BASE = "https://jambofilms.com"
LIMIT = int(sys.argv[3]) if len(sys.argv) > 3 else 120


def local_path(public_root: str, url: str) -> tuple[str, str]:
    """Map a production URL to the same path under the local public/ root."""
    path = urllib.parse.urlparse(url).path.lstrip("/")  # storage/gallery/...
    decoded = urllib.parse.unquote(path)
    return os.path.join(public_root, *decoded.split("/")), "/" + path


def main() -> None:
    titles = json.load(open(sys.argv[1], encoding="utf-8"))
    public_root = sys.argv[2]

    movies = [t for t in titles if not t["is_show"]]
    shows = [t for t in titles if t["is_show"]]
    # Two thirds movies, one third series, roughly the site's own mix.
    chosen = movies[: int(LIMIT * 0.66)] + shows[: LIMIT - int(LIMIT * 0.66)]

    written, skipped, failed = 0, 0, 0
    out = []

    for title in chosen:
        target, served = local_path(public_root, title["poster_url"])

        if os.path.exists(target) and os.path.getsize(target) > 0:
            skipped += 1
        else:
            try:
                os.makedirs(os.path.dirname(target), exist_ok=True)
                request = urllib.request.Request(
                    title["poster_url"],
                    headers={"User-Agent": "Mozilla/5.0 (jambo-app dev seed)"},
                )
                with urllib.request.urlopen(request, timeout=30) as response:
                    data = response.read()
                if len(data) < 512:
                    raise ValueError(f"suspiciously small ({len(data)} bytes)")
                # Written whole, then moved into place: a failed download that
                # leaves a truncated file is worse than no file, because every
                # later run skips it as "already there".
                with open(target + ".part", "wb") as handle:
                    handle.write(data)
                os.replace(target + ".part", target)
                written += 1
            except Exception as error:  # noqa: BLE001 - dev script
                print(f"  FAILED {title['slug']}: {error}", file=sys.stderr)
                failed += 1
                continue

        out.append({**title, "poster_path": served})

    print(f"downloaded {written}, already present {skipped}, failed {failed}")
    print(f"usable: {len(out)}")

    with open(sys.argv[1].replace(".json", "-local.json"), "w", encoding="utf-8") as handle:
        json.dump(out, handle, indent=2, ensure_ascii=False)
    print("Wrote " + sys.argv[1].replace(".json", "-local.json"))


if __name__ == "__main__":
    main()
