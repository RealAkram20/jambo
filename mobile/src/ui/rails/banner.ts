import { formatBannerRuntime } from '../format';
import { bannerSeries } from '../../api/catalogue';
import type { BannerSlide, HeroCard, SeriesHero } from '../../api/catalogue';

/**
 * What the banner's meta row says, worked out away from the drawing.
 *
 * The row is the part of the banner that has real rules rather than layout —
 * a movie shows its certification and a series shows a season count, a movie's
 * runtime is its own and a series' is a mean over its episodes, and each part
 * disappears independently. None of that is observable from a screenshot, so
 * it lives here where a test can ask it directly.
 *
 * The five-star row that used to live here is gone from the app AND from the
 * website, by Rio's decision on 2026-09-10. See ADR-0006: it was never a
 * rating, because nothing in the product can write one.
 */

/**
 * The badge: a season count for a series, a certification for a movie.
 *
 * ⚠️ **The site's `?: 'PG'` fallback is deliberately absent.** A
 * certification is a legal claim about a film, and printing "PG" on one
 * nobody has classified is the same fault as its invented five stars. Null
 * draws no badge.
 */
export function bannerBadge(item: HeroCard): string | null {
  if (item.type === 'series') {
    return seasonCount((item as SeriesHero).seasons_count);
  }

  const certification = item.rating;

  return typeof certification === 'string' && certification !== '' ? certification : null;
}

/**
 * "2 Seasons", or null when there is no count to draw.
 *
 * Shared with the daily series banner, which prints the same phrase in its
 * meta line rather than in a badge. Two spellings of one count is how one
 * banner ends up saying "2 Seasons" and the other "2 seasons".
 *
 * The singular is handled and the website's is not: its blade prints
 * `{{ $count }} {{ __('streamEpisode.season') }}` against a key whose value is
 * the plural "Seasons", so a one-season show reads "1 Seasons" there.
 */
export function seasonCount(count: number | null | undefined): string | null {
  if (typeof count !== 'number' || count < 1) return null;

  return `${count} ${count === 1 ? 'Season' : 'Seasons'}`;
}

/**
 * The clock line: "1hr : 45m" for a movie, "45m" for a series.
 *
 * A series has no runtime of its own, so this is the mean episode length the
 * server computed. Zero is treated as absent rather than drawn — a runtime of
 * "0hr : 0m" is worse than no clock at all, and an unset column reads as 0
 * often enough that it is the likely case rather than the odd one.
 */
export function bannerRuntime(item: HeroCard): string | null {
  if (item.type === 'series') {
    const minutes = (item as SeriesHero).episode_runtime_minutes;

    return typeof minutes === 'number' && minutes > 0 ? `${minutes}m` : null;
  }

  const minutes = (item as { runtime_minutes?: number | null }).runtime_minutes;

  return typeof minutes === 'number' && minutes > 0 ? formatBannerRuntime(minutes) : null;
}

/** "Drama, Comedy, Romance" from a taxonomy list, or null when there is none. */
export function taxonomyNames(terms: readonly { name?: string }[] | undefined): string | null {
  const names = (terms ?? [])
    .map((term) => term.name)
    .filter((name): name is string => typeof name === 'string' && name !== '');

  return names.length === 0 ? null : names.join(', ');
}

/*
 * ─── the two daily Top 10 banners ───────────────────────────────────────
 *
 * Same split, same reason. Every rule below is a claim about a title that
 * can be wrong in a way a screenshot will not show: a rank that was never
 * earned, a release month off by a timezone, a season count of one rendered
 * "1 Seasons". They live beside the hero's rules rather than in a file of
 * their own because `seasonCount` is already shared by both, and two modules
 * that have to import from each other are one module.
 */

/**
 * The gold line beside the Top 10 plate.
 *
 * ⚠️ **A rank is not printed unless the title earned it.** Both shelves
 * backfill from all-time popularity when a day is quiet — `TopPicksRecommender`
 * pads them — and the website will not call a padded title "#4 in Movies
 * Today", because it was not. It says "Popular on Jambo". `ranked_today` is
 * the server telling the app which of the two it is holding, and this is the
 * only place that reads it.
 *
 * The strings are the site's, from `lang/en/streamMovies.php`:
 * `movies_today`, `series_today` and `popular_on_jambo`.
 */
export function rankLabel(item: BannerSlide): string {
  if (item.ranked_today !== true) return 'Popular on Jambo';

  const noun = item.type === 'series' ? 'Series' : 'Movies';

  return `#${item.rank ?? 1} in ${noun} Today`;
}

/**
 * The movies banner's clock line, "1hr : 40m", or null.
 *
 * Zero is absent rather than "0hr : 0m", for the reason the hero's row gives:
 * an unset column reads as 0 often enough to be the likely case.
 */
export function slideRuntime(item: BannerSlide): string | null {
  const minutes = (item as { runtime_minutes?: number | null }).runtime_minutes;

  return typeof minutes === 'number' && minutes > 0 ? formatBannerRuntime(minutes) : null;
}

/**
 * The series banner's release line, "August 2023", or null.
 *
 * **Parsed as a calendar month in UTC, not in the device's timezone.** The
 * server sends an ISO instant, and the website formats it with PHP's `F Y` on
 * the app timezone — so a title published at 23:30 UTC on 31 July would read
 * "July 2023" there and "August 2023" on a phone in Kampala if this used the
 * local getters. A month is not an instant, and the surfaces must agree.
 */
export function releaseLabel(publishedAt: string | null | undefined): string | null {
  if (typeof publishedAt !== 'string' || publishedAt === '') return null;

  const when = new Date(publishedAt);
  if (Number.isNaN(when.getTime())) return null;

  return `${MONTHS[when.getUTCMonth()]} ${when.getUTCFullYear()}`;
}

const MONTHS = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
] as const;

/**
 * The meta line under a series banner's synopsis: the release month, then the
 * season count, each dropped independently when it is not known.
 */
export function seriesMeta(item: BannerSlide): string[] {
  const series = bannerSeries(item);

  return [releaseLabel(series?.published_at), seasonCount(series?.seasons_count)].filter(
    (part): part is string => part !== null,
  );
}

/**
 * CSS `text-transform: capitalize`, which both banner headlines carry.
 *
 * It uppercases the first letter of each word and leaves the rest of the word
 * exactly as it was — so "Eternal Love of the Fox VJ BANKS" becomes "Eternal
 * Love Of The Fox VJ BANKS" and the two acronyms survive. Lowercasing the
 * remainder, which is what a naive title-caser does, would render them "Vj"
 * and "Banks" and quietly rewrite the VJ's name.
 */
export function capitalizeWords(text: string): string {
  return text.replace(/(^|\s)(\S)/g, (_, lead: string, first: string) => lead + first.toUpperCase());
}

/** The genre chips on the movies banner. At most four; the server sends four. */
export function slideGenres(item: BannerSlide): string[] {
  const genres = (item as { genres?: readonly { name?: string }[] }).genres ?? [];

  return genres
    .map((genre) => genre.name)
    .filter((name): name is string => typeof name === 'string' && name !== '');
}
