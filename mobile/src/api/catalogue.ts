import type { components } from './schema';

/**
 * The catalogue's shapes, taken from the generated schema rather than typed by
 * hand.
 *
 * `docs/api/openapi.yaml` is the contract and `npm run api:check` fails when
 * this file's source drifts from it, so a field renamed on the server is a
 * compile error here rather than an empty space on a viewer's home screen. A
 * parallel hand-written interface would agree with the server exactly until
 * the first time somebody changed one and not the other.
 */

export type Home = components['schemas']['Home'];
export type Rail = components['schemas']['Rail'];
export type RailKind = NonNullable<Rail['kind']>;
export type RailItem = NonNullable<Rail['items']>[number];

export type MovieCard = components['schemas']['MovieCard'];
export type SeriesCard = components['schemas']['SeriesCard'];
export type TitleCard = MovieCard | SeriesCard;
export type ContinueWatchingCard = components['schemas']['ContinueWatchingCard'];
export type GenreCard = components['schemas']['GenreCard'];
export type VjCard = components['schemas']['VjCard'];
export type PersonCard = components['schemas']['PersonCard'];
export type MovieList = components['schemas']['MovieList'];
export type SeriesList = components['schemas']['SeriesList'];

/**
 * Which rails draw a numbered Top 10 card.
 *
 * **A lookup with a default, not a switch.** The contract is explicit that
 * `key` is open-ended — category shelves arrive as `category:<slug>` — so
 * nothing may branch on it exhaustively. This asks one question, "is this one
 * of the two ranked shelves", and every other key, present or future, falls
 * through to the ordinary poster card. A rail added on the server still
 * renders without an app release, which is the property the endpoint was built
 * for.
 *
 * The website marks these two by rendering `cards/top-ten-card.blade.php`
 * instead of `cards/card-style.blade.php`; the API does not currently say
 * which presentation a rail wants. **The better home for this is a `style`
 * field on the Rail resource** — one place, every client, no app release when
 * a third ranked rail appears. That is a PHP change and Phase 2 is scoped to
 * touch none, so it is named in the worklog instead of done quietly.
 */
const NUMBERED_RAILS: ReadonlySet<string> = new Set(['top_movies', 'top_series']);

export function isNumberedRail(key: string | undefined): boolean {
  return key !== undefined && NUMBERED_RAILS.has(key);
}

/**
 * The rail kinds this build knows how to draw.
 *
 * The contract says to treat an unknown `kind` as a rail to skip, and this is
 * where that happens — once, on the way in, rather than as a `default: return
 * null` buried in a renderer. A server that starts sending `kind: "channels"`
 * to a newer app must not make this one render a heading over an empty row.
 */
const RENDERABLE: ReadonlySet<string> = new Set<RailKind>([
  'titles',
  'progress',
  'genres',
  'vjs',
  'people',
]);

export function renderableRails(rails: Rail[] | undefined): Rail[] {
  return (rails ?? []).filter(
    (rail) =>
      rail.kind !== undefined &&
      RENDERABLE.has(rail.kind) &&
      Array.isArray(rail.items) &&
      rail.items.length > 0,
  );
}

/** Narrowing helpers. The rail's `kind` is what says which shape `items` holds. */
export function asTitleCards(rail: Rail): TitleCard[] {
  return (rail.items ?? []) as TitleCard[];
}

export function asProgressCards(rail: Rail): ContinueWatchingCard[] {
  return (rail.items ?? []) as ContinueWatchingCard[];
}

export function asGenreCards(rail: Rail): GenreCard[] {
  return (rail.items ?? []) as GenreCard[];
}

export function asVjCards(rail: Rail): VjCard[] {
  return (rail.items ?? []) as VjCard[];
}

export function asPersonCards(rail: Rail): PersonCard[] {
  return (rail.items ?? []) as PersonCard[];
}

/**
 * A stable key for a card in a list.
 *
 * Rails mix movies and series, and their ids are from different tables — so id
 * alone collides and React silently reuses the wrong row's state. The type
 * prefix is what makes it unique.
 */
export function cardKey(item: RailItem, index: number): string {
  const withType = item as { type?: string; id?: number; slug?: string };
  if (withType.id !== undefined) return `${withType.type ?? 'item'}:${withType.id}`;
  if (withType.slug !== undefined) return `${withType.type ?? 'item'}:${withType.slug}`;
  return `index:${index}`;
}
