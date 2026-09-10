import type { WatchlistCard } from '../../api/catalogue';
import {
  columnsFor,
  filterByTab,
  itemCountLabel,
  kindOf,
  metaLine,
  playTargetFor,
  removalTarget,
  sortItems,
  titleOf,
} from './list';

/**
 * The list logic, tested where it is cheap to test.
 *
 * Every case here is one the screen would show a viewer wrongly rather than
 * crash on, which is the class of bug a rendered screenshot does not catch: a
 * series that cannot be removed, a sort that reorders the cache, a count that
 * says "1 Items", a season count printed as zero.
 *
 * The expected strings are the WEBSITE's. `profile-hub/watchlist.blade.php`
 * decides what a watchlist card says, and these assertions are that page's
 * output — so if the app ever drifts from the web app for the same row, one of
 * these fails.
 */

const card = (over: Record<string, unknown>): WatchlistCard => over as unknown as WatchlistCard;

const movie = (over: Record<string, unknown> = {}): WatchlistCard =>
  card({ type: 'movie', id: 1, slug: 'm', title: 'A Movie', year: 2023, ...over });

const series = (over: Record<string, unknown> = {}): WatchlistCard =>
  card({ type: 'series', id: 2, slug: 's', title: 'A Series', year: 2021, ...over });

const episode = (over: Record<string, unknown> = {}): WatchlistCard =>
  card({ type: 'episode', id: 3, title: 'An Episode', number: 4, season_number: 1, ...over });

describe('kindOf', () => {
  it('reads the discriminator across the three shapes', () => {
    expect(kindOf(movie())).toBe('movie');
    expect(kindOf(series())).toBe('series');
    expect(kindOf(episode())).toBe('episode');
  });

  it('calls a type it has never seen "other" rather than guessing', () => {
    expect(kindOf(card({ type: 'channel', id: 9 }))).toBe('other');
    expect(kindOf(card({ id: 9 }))).toBe('other');
  });
});

describe('filterByTab', () => {
  const items = [movie(), series(), episode(), card({ type: 'channel', id: 9 })];

  it('puts episodes under TV Shows, because that is what they are', () => {
    expect(filterByTab(items, 'shows').map(kindOf)).toEqual(['series', 'episode']);
  });

  it('keeps movies to themselves', () => {
    expect(filterByTab(items, 'movies').map(kindOf)).toEqual(['movie']);
  });

  it('shows an unrecognised type under All only, so nothing vanishes', () => {
    expect(filterByTab(items, 'all')).toHaveLength(4);
    expect(filterByTab(items, 'movies')).toHaveLength(1);
    expect(filterByTab(items, 'shows')).toHaveLength(2);
  });
});

describe('titleOf', () => {
  it('composes an episode the way the website does', () => {
    // widgets/watchlist-detail-card.blade.php: zero-padded, em dash, season
    // before episode.
    expect(titleOf(episode({ season_number: 1, number: 2, title: 'Pilot' }))).toBe(
      'S01E02 — Pilot',
    );
  });

  it('drops the season when the server did not send one', () => {
    expect(titleOf(episode({ season_number: null, number: 7, title: 'Alone' }))).toBe(
      'E07 — Alone',
    );
  });

  it('leaves a movie and a series title alone', () => {
    expect(titleOf(movie({ title: 'Dune' }))).toBe('Dune');
    expect(titleOf(series({ title: 'The Bear' }))).toBe('The Bear');
  });
});

describe('sortItems', () => {
  it('leaves Recently Added in the order the server sent', () => {
    const items = [movie({ title: 'Zulu' }), series({ title: 'Alpha' })];
    expect(sortItems(items, 'recent').map(titleOf)).toEqual(['Zulu', 'Alpha']);
  });

  it('never sorts the cached array in place', () => {
    const items = [movie({ title: 'Zulu' }), series({ title: 'Alpha' })];
    sortItems(items, 'title');
    expect(items.map(titleOf)).toEqual(['Zulu', 'Alpha']);
  });

  it('files accented titles with their unaccented letter', () => {
    const items = [movie({ title: 'Fargo' }), series({ title: 'Éclair' })];
    expect(sortItems(items, 'title').map(titleOf)).toEqual(['Éclair', 'Fargo']);
  });

  it('sorts an unknown year last, because an unknown is not an old film', () => {
    const items = [movie({ year: null, title: 'No year' }), series({ year: 1999, title: 'Old' }), movie({ year: 2024, title: 'New' })];
    expect(sortItems(items, 'year').map(titleOf)).toEqual(['New', 'Old', 'No year']);
  });
});

describe('metaLine', () => {
  it('reads year, genre and runtime for a movie, as the mockup does', () => {
    expect(metaLine(movie({ year: 2023, genres: ['Action'], runtime_minutes: 169 }))).toBe(
      '2023 • Action • 2h 49m',
    );
  });

  it('leads a series with its season count, as the mockup does', () => {
    expect(metaLine(series({ seasons_count: 1, genres: ['Drama'] }))).toBe('1 Season • Drama');
    expect(metaLine(series({ seasons_count: 2, genres: ['Comedy'] }))).toBe('2 Seasons • Comedy');
  });

  it('shows one genre, not the two the payload carries', () => {
    // The web card takes two; one line on a 168dp card fits one.
    expect(metaLine(movie({ year: 2020, genres: ['Action', 'Thriller'] }))).toBe('2020 • Action');
  });

  it('falls back to the year when a series has no counted seasons', () => {
    expect(metaLine(series({ seasons_count: null, year: 2021 }))).toBe('2021');
  });

  it('never prints "0 Seasons" on a series a viewer can watch', () => {
    expect(metaLine(series({ seasons_count: 0, year: 2021 }))).toBe('2021');
  });

  it('gives an episode its runtime, having put the number in the title', () => {
    expect(metaLine(episode({ runtime_minutes: 42 }))).toBe('42m');
  });

  it('renders an em dash rather than inventing anything', () => {
    expect(metaLine(series({ year: null, seasons_count: null }))).toBe('—');
  });

  it('does not print a zero runtime as "0m"', () => {
    expect(metaLine(movie({ year: 2020, runtime_minutes: 0 }))).toBe('2020');
  });
});

describe('playTargetFor', () => {
  it('passes the server-resolved target straight through', () => {
    expect(playTargetFor(series({ play: { type: 'episode', id: 88 } }))).toEqual({
      type: 'episode',
      id: 88,
    });
  });

  it('returns null when there is nothing to play, so no control is drawn', () => {
    expect(playTargetFor(movie({ play: null }))).toBeNull();
    expect(playTargetFor(movie({}))).toBeNull();
  });

  it('refuses a half-built target rather than navigating nowhere', () => {
    expect(playTargetFor(movie({ play: { type: 'movie' } }))).toBeNull();
    expect(playTargetFor(movie({ play: { id: 5 } }))).toBeNull();
  });
});

describe('removalTarget', () => {
  it('translates a "series" card into the "show" the endpoint validates', () => {
    expect(removalTarget(series({ id: 7 }))).toEqual({ type: 'show', id: 7 });
  });

  it('passes movies and episodes through unchanged', () => {
    expect(removalTarget(movie({ id: 5 }))).toEqual({ type: 'movie', id: 5 });
    expect(removalTarget(episode({ id: 6 }))).toEqual({ type: 'episode', id: 6 });
  });

  it('returns null for a row the server would refuse', () => {
    expect(removalTarget(card({ type: 'channel', id: 9 }))).toBeNull();
    expect(removalTarget(card({ type: 'movie', title: 'No id' }))).toBeNull();
  });
});

describe('itemCountLabel', () => {
  it('does not say "1 Items"', () => {
    expect(itemCountLabel(1)).toBe('1 Item');
    expect(itemCountLabel(0)).toBe('0 Items');
    expect(itemCountLabel(6)).toBe('6 Items');
  });
});

describe('columnsFor', () => {
  /*
   * These numbers are Streamit's own, from `public/frontend/js/swiper.js` by
   * way of `cardsPerViewAt`. Rio asked for "3 and a half cards the same way we
   * have them on our webapp", and the ladder is what says how many that is at
   * each width — so if the site's breakpoints ever change, these move with
   * them rather than having to be found and edited.
   */
  it('draws the phone rail at three columns of its 3.5-across card', () => {
    expect(columnsFor(390)).toBe(3);
    expect(columnsFor(430)).toBe(3);
  });

  it('treats a tablet and a television as widths, not second layouts', () => {
    expect(columnsFor(768)).toBe(4);
    expect(columnsFor(1280)).toBe(8);
  });

  it('never returns fewer than two, whatever it is handed', () => {
    expect(columnsFor(0)).toBe(2);
    expect(columnsFor(-100)).toBe(2);
    expect(columnsFor(Number.NaN)).toBe(2);
  });
});
