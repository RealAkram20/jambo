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
  it('marks the two ranked shelves and nothing else', () => {
    expect(isNumberedRail('top_movies')).toBe(true);
    expect(isNumberedRail('top_series')).toBe(true);
    expect(isNumberedRail('latest_movies')).toBe(false);
    expect(isNumberedRail(undefined)).toBe(false);
  });

  it('never treats an unknown key as ranked', () => {
    // `key` is open-ended by contract — category shelves arrive as
    // `category:<slug>` — so anything unrecognised must fall through to the
    // ordinary poster card rather than be guessed at.
    expect(isNumberedRail('category:award-winners')).toBe(false);
    expect(isNumberedRail('something_invented_next_year')).toBe(false);
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
