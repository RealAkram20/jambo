/**
 * Formatters shared by more than one screen.
 *
 * `formatRuntime` lived privately in `TitleDetailScreen` until the Watchlist
 * needed the same string. Copying five lines would have been quicker and is
 * exactly how a codebase ends up with two runtimes that disagree the first
 * time somebody decides a feature should read "2 hr 49 min" — so it moved here
 * and the detail screen imports it back.
 */

/** "1h 30m", the way the website writes it, and never "90m" for a feature. */
export function formatRuntime(minutes: number): string {
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;
  if (hours === 0) return `${rest}m`;
  return rest === 0 ? `${hours}h` : `${hours}h ${rest}m`;
}

/**
 * "1hr : 30m", which is the banners only.
 *
 * The website writes a runtime two ways and this is not an accident anybody
 * should tidy up here: `card-style.blade.php` composes "2h 49m" for a card,
 * and `hero-banner.blade.php`, `vertical-banner.blade.php` and the trending
 * slide all compose `floor($m/60) . 'hr : ' . $m % 60 . 'm'`. Reusing
 * `formatRuntime` on a banner would be the app quietly picking one, which is
 * a decision for whoever owns the site's copy rather than for this file.
 *
 * It sits next to its sibling so the divergence is one thing to look at, not
 * five lines hidden in a component.
 */
export function formatBannerRuntime(minutes: number): string {
  return `${Math.floor(minutes / 60)}hr : ${minutes % 60}m`;
}
