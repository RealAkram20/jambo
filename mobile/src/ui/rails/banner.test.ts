import {
  bannerBadge,
  bannerRuntime,
  capitalizeWords,
  rankLabel,
  releaseLabel,
  seriesMeta,
  slideGenres,
  slideRuntime,
  taxonomyNames,
} from './banner';
import type { BannerSlide, HeroCard } from '../../api/catalogue';

const movie = (over: Record<string, unknown> = {}): HeroCard =>
  ({ type: 'movie', id: 1, slug: 'm', title: 'A Movie', ...over }) as HeroCard;

const series = (over: Record<string, unknown> = {}): HeroCard =>
  ({ type: 'series', id: 2, slug: 's', title: 'A Series', ...over }) as HeroCard;

describe('bannerBadge', () => {
  it('is a certification for a movie', () => {
    expect(bannerBadge(movie({ rating: 'PG-13' }))).toBe('PG-13');
  });

  /**
   * The website prints "PG" on a film nobody classified. That is a legal
   * claim about the film, and this is the assertion that keeps the app from
   * repeating it.
   */
  it('invents no certification for an unclassified movie', () => {
    expect(bannerBadge(movie({ rating: null }))).toBeNull();
    expect(bannerBadge(movie({ rating: '' }))).toBeNull();
    expect(bannerBadge(movie())).toBeNull();
  });

  it('is a season count for a series, singular at one', () => {
    expect(bannerBadge(series({ seasons_count: 3 }))).toBe('3 Seasons');
    expect(bannerBadge(series({ seasons_count: 1 }))).toBe('1 Season');
  });

  it('says nothing for a series whose seasons were not counted', () => {
    // Absent because the relation was not loaded, not because there are none.
    // "0 Seasons" on a series a viewer can watch is a lie.
    expect(bannerBadge(series({ seasons_count: 0 }))).toBeNull();
    expect(bannerBadge(series())).toBeNull();
  });

  it('reads a series by its type, never by which fields it happens to carry', () => {
    // A series that also carries `rating` — every SeriesCard does — must
    // still badge its seasons. Branching on the presence of a field rather
    // than on `type` puts "PG" where "2 Seasons" belongs.
    expect(bannerBadge(series({ seasons_count: 2, rating: 'PG' }))).toBe('2 Seasons');
  });

  /**
   * The case a `typeof seasons_count === 'number'` branch gets wrong, and it
   * is reachable rather than theoretical: `seasons_count` is `whenLoaded`, so
   * it is simply absent whenever the relation was not eager-loaded, while
   * `rating` is on every SeriesCard. Deciding "is this a series" by looking
   * for the season count therefore badges such a series "PG" — a film
   * certification, on a series, because a relation was missing.
   *
   * Written after a mutation that flipped exactly that survived the tests
   * above.
   */
  it('badges nothing for a series whose seasons are absent, even when it has a certification', () => {
    expect(bannerBadge(series({ rating: 'PG' }))).toBeNull();
  });
});

describe('bannerRuntime', () => {
  it('writes a movie the way the banner does, not the way a card does', () => {
    // "1hr : 45m", not the card's "1h 45m".
    expect(bannerRuntime(movie({ runtime_minutes: 105 }))).toBe('1hr : 45m');
  });

  it('keeps the zero minutes a round runtime produces', () => {
    expect(bannerRuntime(movie({ runtime_minutes: 120 }))).toBe('2hr : 0m');
  });

  it('writes a series as a plain minute count', () => {
    expect(bannerRuntime(series({ episode_runtime_minutes: 45 }))).toBe('45m');
  });

  it('draws no clock rather than a zero one', () => {
    expect(bannerRuntime(movie({ runtime_minutes: 0 }))).toBeNull();
    expect(bannerRuntime(series({ episode_runtime_minutes: 0 }))).toBeNull();
    expect(bannerRuntime(movie())).toBeNull();
    expect(bannerRuntime(series())).toBeNull();
  });
});

describe('taxonomyNames', () => {
  it('joins the names the site joins', () => {
    expect(taxonomyNames([{ name: 'Drama' }, { name: 'Comedy' }])).toBe('Drama, Comedy');
  });

  it('is null for an absent or empty list, so the line is not drawn', () => {
    expect(taxonomyNames(undefined)).toBeNull();
    expect(taxonomyNames([])).toBeNull();
  });

  it('drops a term with no name rather than leaving a gap in the list', () => {
    // Renders as "Drama, , Comedy" without the filter.
    expect(taxonomyNames([{ name: 'Drama' }, {}, { name: 'Comedy' }])).toBe('Drama, Comedy');
  });
});

/*
 * ─── the two daily Top 10 banners ───────────────────────────────────────
 */

const slide = (over: Record<string, unknown> = {}): BannerSlide =>
  ({ type: 'movie', id: 1, slug: 'm', title: 'A Movie', rank: 1, ...over }) as BannerSlide;

const seriesSlide = (over: Record<string, unknown> = {}): BannerSlide =>
  ({ type: 'series', id: 2, slug: 's', title: 'A Series', rank: 1, ...over }) as BannerSlide;

describe('rankLabel', () => {
  it('names the position when the title earned it today', () => {
    expect(rankLabel(slide({ rank: 3, ranked_today: true }))).toBe('#3 in Movies Today');
    expect(rankLabel(seriesSlide({ rank: 1, ranked_today: true }))).toBe('#1 in Series Today');
  });

  /**
   * 🔴 The assertion this function exists for.
   *
   * Both shelves pad themselves from all-time popularity when a day is quiet,
   * so a slide's POSITION is always 1..10 while its rank is sometimes nothing
   * at all. Printing "#4 in Movies Today" over a padded title would be the
   * app inventing a statistic, which is the same fault as the five invented
   * stars that came off both surfaces the same week.
   */
  it('claims no rank the title did not earn', () => {
    expect(rankLabel(slide({ rank: 4, ranked_today: false }))).toBe('Popular on Jambo');
    expect(rankLabel(seriesSlide({ rank: 2, ranked_today: false }))).toBe('Popular on Jambo');
  });

  it('treats a missing flag as unearned rather than as earned', () => {
    // An older server, or a field dropped in transit. The safe reading of
    // "I do not know" is the one that makes no claim.
    expect(rankLabel(slide({ rank: 2 }))).toBe('Popular on Jambo');
  });
});

describe('releaseLabel', () => {
  it('is the calendar month the site prints', () => {
    expect(releaseLabel('2023-08-14T09:00:00+00:00')).toBe('August 2023');
  });

  /**
   * 🔴 Read in UTC, deliberately.
   *
   * The website formats this instant with PHP's `F Y` in the app timezone. A
   * phone reading it with local getters would move a late-in-the-month title
   * into the next month for half the world — "July 2023" on the site and
   * "August 2023" in the app, for the same series, with no way for anyone to
   * tell which was right.
   */
  it('does not let the device timezone move a title into another month', () => {
    const almostMidnightUtc = '2023-07-31T23:30:00+00:00';

    expect(releaseLabel(almostMidnightUtc)).toBe('July 2023');
  });

  it('draws nothing rather than "Invalid Date"', () => {
    expect(releaseLabel(null)).toBeNull();
    expect(releaseLabel(undefined)).toBeNull();
    expect(releaseLabel('')).toBeNull();
    expect(releaseLabel('not a date')).toBeNull();
  });
});

describe('seriesMeta', () => {
  it('is the release month and the season count, in that order', () => {
    expect(
      seriesMeta(seriesSlide({ published_at: '2024-05-31T16:19:07+00:00', seasons_count: 2 })),
    ).toEqual(['May 2024', '2 Seasons']);
  });

  it('drops either half independently rather than leaving a stray separator', () => {
    expect(seriesMeta(seriesSlide({ seasons_count: 2 }))).toEqual(['2 Seasons']);
    expect(seriesMeta(seriesSlide({ published_at: '2024-05-31T16:19:07+00:00' }))).toEqual([
      'May 2024',
    ]);
    expect(seriesMeta(seriesSlide())).toEqual([]);
  });
});

describe('capitalizeWords', () => {
  it('is CSS `text-transform: capitalize`, which both headlines carry', () => {
    expect(capitalizeWords('Eternal Love of the Fox')).toBe('Eternal Love Of The Fox');
  });

  /**
   * The reason this is not a title-caser. Lowercasing the rest of each word
   * would rewrite "VJ BANKS" as "Vj Banks" — a person's name, on the banner
   * that names them.
   */
  it('leaves the rest of a word alone, so an acronym survives', () => {
    expect(capitalizeWords('Lanterns VJ MOSCO')).toBe('Lanterns VJ MOSCO');
    expect(capitalizeWords('mortal kombat 2 vj junior')).toBe('Mortal Kombat 2 Vj Junior');
  });

  it('survives an empty title', () => {
    expect(capitalizeWords('')).toBe('');
  });
});

describe('slideRuntime and slideGenres', () => {
  it('formats a runtime the way the banner prints it', () => {
    expect(slideRuntime(slide({ runtime_minutes: 100 }))).toBe('1hr : 40m');
  });

  it('draws no clock for an unset runtime, which reads as 0', () => {
    expect(slideRuntime(slide({ runtime_minutes: 0 }))).toBeNull();
    expect(slideRuntime(slide())).toBeNull();
  });

  it('takes the genre names and skips the ones with none', () => {
    expect(
      slideGenres(slide({ genres: [{ name: 'Action' }, { name: '' }, { slug: 'x' }] })),
    ).toEqual(['Action']);
    expect(slideGenres(slide())).toEqual([]);
  });
});
