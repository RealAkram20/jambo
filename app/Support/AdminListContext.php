<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Keeps an admin list's paging/filter state alive across a round trip to a
 * detail page.
 *
 * The problem it solves: an admin working on /admin/movies?page=2 clicks
 * Edit, saves, hits "Back to list" — and lands on page 1, having lost their
 * place in a 200-row catalogue. Every "Back to list" link and post-action
 * redirect was a bare route('admin.movies.index') with no query string.
 *
 * Two mechanisms, deliberately layered:
 *
 *   1. URL threading (primary). index() hands the view its own query state,
 *      the view stitches it onto every Edit link, and the detail page reads
 *      it back off its own URL. Stateless and shareable — two tabs parked on
 *      different pages never fight, and a pasted link lands where it says.
 *
 *   2. Session fallback (secondary). index() also stashes the state under a
 *      per-list key. This covers the cases URL threading structurally can't:
 *      a POST/DELETE form whose action carried no query string, and a bare
 *      /admin/movies visit from the sidebar after you were deep in a filter.
 *
 * resolve() prefers the URL and falls back to the session, so the explicit
 * signal always wins over the remembered one.
 */
final class AdminListContext
{
    /**
     * Query params worth preserving. Deliberately a whitelist: anything an
     * index() doesn't actually read (stray UTM tags, a leftover _token)
     * would otherwise ride along and pollute every link on the page.
     */
    private const KEYS = ['page', 'q', 'status', 'sort', 'dir'];

    private const SESSION_PREFIX = 'admin.list_context.';

    /**
     * Record the current list state and return it for the view to thread
     * onto its row links. Call from a list controller's index().
     */
    public static function remember(string $list, Request $request): array
    {
        $state = self::extract($request);

        $request->session()->put(self::SESSION_PREFIX . $list, $state);

        return $state;
    }

    /**
     * Best guess at the list state a redirect or "Back to list" link should
     * return to: whatever this request carries, else whatever index() last
     * stashed for this list.
     */
    public static function resolve(string $list, Request $request): array
    {
        $fromUrl = self::extract($request);

        if ($fromUrl !== []) {
            return $fromUrl;
        }

        $stashed = $request->session()->get(self::SESSION_PREFIX . $list, []);

        return is_array($stashed) ? self::clean($stashed) : [];
    }

    /**
     * Pull the whitelisted params off a request, dropping empties so a link
     * doesn't end up with `?q=&status=&page=1` hanging off it.
     */
    private static function extract(Request $request): array
    {
        return self::clean($request->query());
    }

    private static function clean(array $params): array
    {
        $state = [];

        foreach (self::KEYS as $key) {
            $value = $params[$key] ?? null;

            // Arrays would break route() interpolation, and `page=1` is the
            // default the paginator produces anyway — neither earns a slot.
            if ($value === null || is_array($value) || trim((string) $value) === '') {
                continue;
            }
            if ($key === 'page' && (int) $value <= 1) {
                continue;
            }

            $state[$key] = (string) $value;
        }

        return $state;
    }
}
