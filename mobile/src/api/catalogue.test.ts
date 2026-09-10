import { collectionKeyFor, isNumberedRail, renderableRails, type Rail } from './catalogue';

/**
 * The two key spaces are real values read off the running API on 2026-09-09,
 * not invented examples. `GET /home` publishes underscored rail keys and
 * `GET /collections` publishes hyphenated collection keys, and they do not
 * line up one for one.
 */
describe('collectionKeyFor', () => {
  it('converts the six rails whose archive differs only by the separator', () => {
    expect(collectionKeyFor('top_picks')).toBe('top-picks');
    expect(collectionKeyFor('smart_shuffle')).toBe('smart-shuffle');
    expect(collectionKeyFor('fresh_picks')).toBe('fresh-picks');
    expect(collectionKeyFor('latest_movies')).toBe('latest-movies');
    expect(collectionKeyFor('latest_series')).toBe('latest-series');
    expect(collectionKeyFor('popular_movies')).toBe('popular-movies');
  });

  it('knows the one pair no rule derives', () => {
    // The rail is Jambo's wording, the collection is Streamit's, and both are
    // titled "Only on Jambo". Nothing about the strings connects them.
    expect(collectionKeyFor('exclusives')).toBe('only-on-streamit');
  });

  it('sends a category shelf nowhere, because it has a better screen', () => {
    // `category:<slug>` is not a collection; the taxonomy archive already
    // covers it, so the home screen routes those rails there instead.
    expect(collectionKeyFor('category:award-winners')).toBeNull();
    expect(collectionKeyFor('category:trending')).toBeNull();
  });

  it('returns null rather than a guess for a missing key', () => {
    expect(collectionKeyFor(undefined)).toBeNull();
    expect(collectionKeyFor('')).toBeNull();
  });

  it('still converts a rail that has no archive — the caller checks, not this', () => {
    /*
     * `top_movies`, `top_series` and `international_series` have no collection
     * on the server. This function deliberately does not know that: baking a
     * list of what exists into the app would mean a collection added
     * server-side stayed invisible until the next release. The home screen
     * checks the derived key against GET /collections instead.
     */
    expect(collectionKeyFor('top_movies')).toBe('top-movies');
    expect(collectionKeyFor('international_series')).toBe('international-series');
  });
});

describe('isNumberedRail', () => {
  const rail = (over: Partial<Rail>): Rail => ({ key: 'k', kind: 'titles', ...over });

  it('reads the style the server sent, not the rail key', () => {
    expect(isNumberedRail(rail({ key: 'top_movies', style: 'numbered' }))).toBe(true);
    expect(isNumberedRail(rail({ key: 'top_series', style: 'numbered' }))).toBe(true);
    expect(isNumberedRail(rail({ key: 'latest_movies' }))).toBe(false);
  });

  /*
   * 🔴 The one that pins the change of 2026-09-10.
   *
   * The app used to hold a set of two literal keys and give those the
   * numerals. A shelf added on the server could not be ranked without an app
   * release, and a renamed key would have silently un-ranked one. Both of
   * these would have passed under the old implementation and are the reason
   * the decision moved to the server.
   */
  it('does not rank a key that merely looks ranked', () => {
    expect(isNumberedRail(rail({ key: 'top_movies' }))).toBe(false);
  });

  it('ranks a shelf whose key this build has never seen', () => {
    expect(isNumberedRail(rail({ key: 'top_documentaries', style: 'numbered' }))).toBe(true);
  });

  it('falls through for a style this build does not know', () => {
    // Read with a default, never switched on: a style from a newer server
    // must draw the ordinary card rather than nothing at all.
    expect(isNumberedRail(rail({ style: 'invented_next_year' as never }))).toBe(false);
    expect(isNumberedRail(rail({}))).toBe(false);
  });
});

describe('renderableRails', () => {
  const rail = (over: Partial<Rail>): Rail => ({
    key: 'k',
    title: 't',
    kind: 'titles',
    items: [{ type: 'movie', id: 1 }],
    ...over,
  });

  it('skips a kind this build cannot draw, as the contract asks', () => {
    // A newer server sending `kind: "channels"` must not make this build
    // render a heading over an empty row.
    const kept = renderableRails([
      rail({ key: 'a' }),
      rail({ key: 'b', kind: 'channels' as never }),
    ]);

    expect(kept.map((r) => r.key)).toEqual(['a']);
  });

  it('drops an empty rail — a heading over nothing is worse than no heading', () => {
    const kept = renderableRails([rail({ key: 'a', items: [] }), rail({ key: 'b' })]);

    expect(kept.map((r) => r.key)).toEqual(['b']);
  });

  it('survives a response with no rails at all', () => {
    expect(renderableRails(undefined)).toEqual([]);
  });
});
