import type { WatchlistCard } from '../../api/catalogue';
import { formatRuntime } from '../../ui/format';
import { cardsPerViewAt } from '../../ui/metrics';

/**
 * Everything the Watchlist screen decides about its list, as pure functions.
 *
 * **The rules here are the website's, not this screen's inventions.** Rio's
 * ruling of 2026-09-09 is that the app reuses the web app's own components
 * with their real design, behaviour and content, and an inspirational mockup
 * improves the experience around them rather than replacing them. So the
 * card's title, its meta line and what its Play control opens all come from
 * `profile-hub/watchlist.blade.php`, `card-style.blade.php` and
 * `widgets/watchlist-detail-card.blade.php`. Each is cited where it is used.
 */

/**
 * What a row actually is.
 *
 * `GET /watchlist` returns three shapes and the generated `WatchlistCard` is
 * their union, so `type` is the discriminator and this is the one place that
 * reads it. `other` is not defensive padding: the server's `WatchlistItem` is
 * a morph, and a fourth type added there must land somewhere sensible in an
 * app already on a viewer's phone rather than vanish from every tab.
 */
export type WatchlistKind = 'movie' | 'series' | 'episode' | 'other';

export function kindOf(item: WatchlistCard): WatchlistKind {
  const type = (item as { type?: string }).type;
  if (type === 'movie' || type === 'series' || type === 'episode') return type;
  return 'other';
}

/** The three filter tabs. */
export type WatchlistTab = 'movies' | 'shows' | 'all';

export const WATCHLIST_TABS: readonly { key: WatchlistTab; label: string }[] = [
  { key: 'movies', label: 'Movies' },
  { key: 'shows', label: 'TV Shows' },
  { key: 'all', label: 'All' },
];

/**
 * Filter to one tab.
 *
 * The website splits its watchlist into exactly two panes, Movies and Series
 * (`profile-hub/watchlist.blade.php`), and this keeps that split. **An episode
 * counts as a TV show**, because that is what it is — a viewer who saved one
 * episode of The Bear and then taps TV Shows is looking for it. An
 * unrecognised type appears under All only, the one tab that promises
 * everything.
 */
export function filterByTab(items: readonly WatchlistCard[], tab: WatchlistTab): WatchlistCard[] {
  if (tab === 'all') return [...items];

  return items.filter((item) => {
    const kind = kindOf(item);
    return tab === 'movies' ? kind === 'movie' : kind === 'series' || kind === 'episode';
  });
}

export type WatchlistSort = 'recent' | 'title' | 'year';

export const WATCHLIST_SORTS: readonly { key: WatchlistSort; label: string }[] = [
  { key: 'recent', label: 'Recently Added' },
  { key: 'title', label: 'Title A–Z' },
  { key: 'year', label: 'Newest Released' },
];

/**
 * Sort, without inventing an ordering the data cannot support.
 *
 * `recent` is the server's own order, so it is a copy and not a comparison —
 * there is no `added_at` on the payload to sort by and a client that
 * reconstructed one would be guessing.
 *
 * A copy, never in place: the array is React Query's cached data, and sorting
 * it where it lies mutates the cache under every other reader of that key.
 *
 * Titles compare with `localeCompare` so "Éclair" files with the E's, and a
 * missing year sorts last rather than as year zero — an unknown is not a very
 * old film.
 */
export function sortItems(
  items: readonly WatchlistCard[],
  sort: WatchlistSort,
): WatchlistCard[] {
  const copy = [...items];

  if (sort === 'title') {
    return copy.sort((a, b) =>
      titleOf(a).localeCompare(titleOf(b), undefined, { sensitivity: 'base' }),
    );
  }

  if (sort === 'year') {
    return copy.sort((a, b) => yearOf(b) - yearOf(a));
  }

  return copy;
}

function yearOf(item: WatchlistCard): number {
  const year = (item as { year?: number | null }).year;
  return typeof year === 'number' ? year : -Infinity;
}

/**
 * The card's title.
 *
 * An episode reads `S01E02 — Name`, which is the website's own composition in
 * `widgets/watchlist-detail-card.blade.php` — zero-padded, em dash, season
 * before episode. A bare episode name on a grid of posters says nothing about
 * where in a series it sits, which is the one thing a saved episode needs to
 * say.
 */
export function titleOf(item: WatchlistCard): string {
  const title = (item as { title?: string }).title ?? 'Untitled';

  if (kindOf(item) !== 'episode') return title;

  const season = (item as { season_number?: number | null }).season_number;
  const number = (item as { number?: number | null }).number;
  const pad = (value: number) => String(value).padStart(2, '0');

  if (typeof number !== 'number') return title;

  return typeof season === 'number'
    ? `S${pad(season)}E${pad(number)} — ${title}`
    : `E${pad(number)} — ${title}`;
}

/**
 * The line under the title.
 *
 * **Every part of it comes from the website's own watchlist card.**
 * `profile-hub/watchlist.blade.php` hands `card-style.blade.php` a `cardYear`,
 * a `cardGenres` of the first two genre names, and a `movietime` that is
 * `"2h 49m"` for a movie and `"N seasons"` for a show. Rio's mockup renders
 * those same three facts on one line rather than the web's chip list plus icon
 * row, which is the layout the mockup contributes and the content the web app
 * owns.
 *
 * One genre, not the web's two: the mockup's line is one line, and a second
 * genre wraps it on a 168dp card. The payload still carries both, so a wider
 * layout can show them without another endpoint change.
 *
 * A title with none of these renders an em dash. Nothing here is ever a
 * fabricated stand-in — this screen never prints a season count, a runtime or
 * a genre the server did not send.
 */
export function metaLine(item: WatchlistCard): string {
  const parts: string[] = [];
  const kind = kindOf(item);
  const runtime = (item as { runtime_minutes?: number | null }).runtime_minutes;
  const seasons = (item as { seasons_count?: number | null }).seasons_count;
  const genre = (item as { genres?: string[] }).genres?.[0];
  const year = (item as { year?: number | null }).year;

  if (kind === 'series') {
    // Seasons lead on a series, as they do in the mockup's "1 Season · Drama".
    if (typeof seasons === 'number' && seasons > 0) {
      parts.push(`${seasons} ${seasons === 1 ? 'Season' : 'Seasons'}`);
    } else if (typeof year === 'number') {
      parts.push(String(year));
    }
    if (genre !== undefined && genre !== '') parts.push(genre);
  } else if (kind === 'episode') {
    if (typeof runtime === 'number' && runtime > 0) parts.push(formatRuntime(runtime));
  } else {
    if (typeof year === 'number') parts.push(String(year));
    if (genre !== undefined && genre !== '') parts.push(genre);
    if (typeof runtime === 'number' && runtime > 0) parts.push(formatRuntime(runtime));
  }

  return parts.length === 0 ? '—' : parts.join(' • ');
}

/**
 * What this card's Play control opens, or null for no control at all.
 *
 * **Resolved by the server, not here.** `WatchlistPlayResolver` answers it
 * with the website's own rule — a series opens at the most recent *unfinished*
 * episode and only falls back to episode one — and both clients now call it.
 * The first cut of this screen re-derived that in TypeScript from the detail
 * endpoint and got it wrong in two ways: it always started at episode one, and
 * it ordered seasons by number where the site orders by id.
 *
 * Null means an unreleased title or a series with no episodes. Draw nothing
 * rather than a disabled control.
 */
export function playTargetFor(
  item: WatchlistCard,
): { type: 'movie' | 'episode'; id: number } | null {
  const play = (item as { play?: { type?: 'movie' | 'episode'; id?: number } | null }).play;

  if (play === null || play === undefined) return null;
  if (play.type === undefined || typeof play.id !== 'number') return null;

  return { type: play.type, id: play.id };
}

/** "6 Items", "1 Item". The plural is not free. */
export function itemCountLabel(count: number): string {
  return `${count} ${count === 1 ? 'Item' : 'Items'}`;
}

/**
 * What `DELETE /watchlist/{type}/{id}` wants.
 *
 * **The two vocabularies differ by one word and it is not cosmetic.**
 * `ShowResource` emits `type: "series"`, while `WatchlistController::TYPES` is
 * `['movie', 'show', 'episode']` — so sending the card's own type back would
 * fail validation on every series a viewer tried to remove. Returns null for a
 * row the server would not accept, so the caller draws no control rather than
 * one that always errors.
 */
export function removalTarget(
  item: WatchlistCard,
): { type: 'movie' | 'show' | 'episode'; id: number } | null {
  const id = (item as { id?: number }).id;
  if (typeof id !== 'number') return null;

  const kind = kindOf(item);
  if (kind === 'movie') return { type: 'movie', id };
  if (kind === 'series') return { type: 'show', id };
  if (kind === 'episode') return { type: 'episode', id };
  return null;
}

/**
 * How many columns of cards fit a width.
 *
 * **The site's own rail ladder, floored.** Rio, 2026-09-09: *"let these be 3
 * and a half cards the same way we have them on our webapp."* Streamit's rail
 * markup carries `data-mobile` / `data-tab` / `data-slide` and
 * `public/frontend/js/swiper.js` feeds them to swiper at 0 / 576 / 768 / 1025
 * / 1500, which is 3.5 cards on a phone. `cardsPerViewAt` in `ui/metrics.ts`
 * already models exactly that, so this asks it rather than keeping a second
 * idea of card size.
 *
 * Floored to whole columns, which is `PosterGrid`'s rule and its reasoning:
 * the half card is the *peek* that tells a viewer a rail scrolls sideways, and
 * a half card at the right edge of a grid — which scrolls down, not across —
 * reads as a clipped poster rather than as an invitation. So the phone gets
 * three columns of the 3.5-across card, and the poster is the size Rio asked
 * for even though the row is whole.
 *
 * A tablet and a television then get four and eight, because `cardsPerViewAt`
 * is the website's answer at those widths too.
 */
export function columnsFor(availableWidth: number): number {
  if (!Number.isFinite(availableWidth) || availableWidth <= 0) return 2;
  return Math.max(2, Math.floor(cardsPerViewAt(availableWidth)));
}
