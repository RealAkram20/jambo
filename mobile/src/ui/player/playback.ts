import type { Season } from '../../api/catalogue';

/**
 * The player's decisions, with no React and no native module in sight.
 *
 * Everything here is a pure function of its arguments, because these are the
 * parts that are worth testing and the parts that are wrong in ways a
 * screenshot cannot show: which rendition to ask for, where a resume actually
 * lands, which episode is next. The screen and the Kotlin view are the two
 * places where behaviour cannot be unit tested, so as little as possible is
 * decided in either.
 */

/** What `/account/preferences` stores. Not a resolution ladder — see below. */
export type VideoQualityPreference = 'auto' | 'high' | 'data_saver';

/** What `POST /playback/sessions` accepts. Two files exist; there is no third. */
export type Rendition = 'default' | 'low';

/**
 * The rendition this viewer wants, before checking what the title has.
 *
 * **`high` and `data_saver` are not 1080p and 720p.** Jambo hosts exactly two
 * renditions of a title, `video_url` and `video_url_low`, so a menu offering
 * 4K / 1080p / 720p / 480p would be four labels for two files. `high` means
 * the default file, `data_saver` means the low one, and `auto` means the
 * client decides from the connection. Anybody who wants a resolution label
 * here has to add a rendition first.
 *
 * Auto on cellular chooses `low`, which is the whole reason auto exists: the
 * viewers this app is for are on MTN and Airtel bundles, and the default file
 * is the one that empties them.
 */
export function preferredRendition(
  preference: VideoQualityPreference | null | undefined,
  isCellular: boolean,
): Rendition {
  if (preference === 'data_saver') return 'low';
  if (preference === 'high') return 'default';

  // `auto`, and also anything unrecognised. A preference this client does not
  // know about is a newer server, and guessing `default` on a metered
  // connection is the expensive way to be wrong.
  return isCellular ? 'low' : 'default';
}

/**
 * The rendition this title can actually serve.
 *
 * `available_qualities` is derived server-side from which columns are filled,
 * so a title with no `video_url_low` reports `['default']` and asking it for
 * `low` is refused with CONTENT_UNAVAILABLE rather than quietly served at
 * full size. That refusal is correct — silently spending someone's bundle is
 * the one thing Data Saver exists to prevent — so the app avoids provoking it
 * once it knows, and tells the viewer when it could not.
 *
 * On the FIRST request `available` is unknown, because it arrives in the
 * response being requested. The caller handles that by retrying at `default`
 * and saying why; see `WatchScreen`.
 */
export function usableRendition(
  wanted: Rendition,
  available: readonly string[] | null | undefined,
): Rendition {
  if (available === null || available === undefined || available.length === 0) return wanted;
  if (available.includes(wanted)) return wanted;
  // `default` is guaranteed present whenever playback was authorised at all:
  // a title with no default file has no session to return.
  return 'default';
}

/** How the two renditions are named on screen. The site's own words. */
export function renditionLabel(rendition: Rendition): string {
  return rendition === 'low' ? 'Data saver' : 'Default';
}

/**
 * Where a resume should actually start.
 *
 * Two things go wrong without this, and both were seen against the real API.
 *
 * 1. **A resume past the end of the media.** The seeded history for a title
 *    carries 1512 seconds while the file is 52 seconds long; seeking there
 *    leaves a player parked on the last frame with nothing to play. Positions
 *    beyond the media start from the beginning instead.
 * 2. **A resume within a few seconds of the end.** Resuming three seconds
 *    before the credits is not resuming. The server already refuses to return
 *    a position for a title it considers completed; this covers the gap below
 *    that threshold.
 *
 * `durationMs` is 0 until the media is prepared, in which case the position is
 * taken on trust — the native side clamps against the real duration once it
 * knows it.
 */
export function resumeStartMs(resumeSeconds: number | null | undefined, durationMs: number): number {
  const seconds = typeof resumeSeconds === 'number' && Number.isFinite(resumeSeconds) ? resumeSeconds : 0;
  if (seconds <= 0) return 0;

  const ms = Math.floor(seconds * 1000);
  if (durationMs <= 0) return ms;

  // Within the last 15 seconds, or past the end: start again.
  if (ms >= durationMs - 15_000) return 0;

  return ms;
}

/** Keep a position inside the media. `durationMs` of 0 means "not known yet". */
export function clampPosition(positionMs: number, durationMs: number): number {
  if (!Number.isFinite(positionMs) || positionMs <= 0) return 0;
  if (durationMs <= 0) return Math.floor(positionMs);
  return Math.min(Math.floor(positionMs), durationMs);
}

/**
 * `1:05` under an hour, `1:02:03` over it — the same shape the site's
 * `<media-time>` renders, so a viewer who uses both does not see two clocks.
 *
 * Negative and non-finite inputs render as `0:00` rather than throwing: the
 * duration is genuinely unknown for the first moments of every stream, and a
 * player that crashes on its own loading state is worse than one that shows a
 * zero for 200ms.
 */
export function formatTime(ms: number): string {
  if (!Number.isFinite(ms) || ms <= 0) return '0:00';

  const total = Math.floor(ms / 1000);
  const seconds = total % 60;
  const minutes = Math.floor(total / 60) % 60;
  const hours = Math.floor(total / 3600);

  const ss = String(seconds).padStart(2, '0');

  if (hours > 0) return `${hours}:${String(minutes).padStart(2, '0')}:${ss}`;
  return `${minutes}:${ss}`;
}

/** Where a title sits in its series, so "next" has a meaning. */
export type EpisodeRef = {
  id: number;
  seasonNumber: number;
  episodeNumber: number;
  title: string;
  stillUrl?: string | undefined;
};

/**
 * Flatten a series into the order a viewer watches it in.
 *
 * Sorted by season then episode number rather than trusting the array order:
 * `/series/{slug}` returns what the database returns, and an admin who adds a
 * missing episode later leaves a series whose array order is not its watch
 * order. Sorting by the numbers the viewer sees is the only version that
 * cannot disagree with the screen.
 */
export function episodesInOrder(seasons: readonly Season[] | null | undefined): EpisodeRef[] {
  if (!seasons) return [];

  const ordered: EpisodeRef[] = [];

  for (const season of [...seasons].sort((a, b) => (a.number ?? 0) - (b.number ?? 0))) {
    const episodes = [...(season.episodes ?? [])].sort((a, b) => (a.number ?? 0) - (b.number ?? 0));
    for (const episode of episodes) {
      if (typeof episode.id !== 'number') continue;
      ordered.push({
        id: episode.id,
        seasonNumber: season.number ?? 0,
        episodeNumber: episode.number ?? 0,
        title: episode.title ?? '',
        stillUrl: episode.still_url,
      });
    }
  }

  return ordered;
}

/**
 * The episode after this one, across a season boundary, or null at the end.
 *
 * Crossing the boundary is the case that gets missed: the last episode of
 * season one is followed by the first of season two, not by nothing. It is
 * also the case a fixture covering one season cannot exercise, which is why
 * `tools/dev-catalogue/9-playable-video.php` gives a whole series a video.
 *
 * Returns null for an unknown id rather than the first episode. A viewer
 * whose episode is no longer in the series should be offered nothing, not
 * silently sent back to the pilot.
 */
export function nextEpisodeAfter(
  seasons: readonly Season[] | null | undefined,
  currentEpisodeId: number,
): EpisodeRef | null {
  const ordered = episodesInOrder(seasons);
  const index = ordered.findIndex((e) => e.id === currentEpisodeId);
  if (index === -1) return null;
  return ordered[index + 1] ?? null;
}

/** `S01E02 · Title`, the subtitle shape the Continue Watching card already uses. */
export function episodeLabel(episode: EpisodeRef): string {
  const s = String(episode.seasonNumber).padStart(2, '0');
  const e = String(episode.episodeNumber).padStart(2, '0');
  const prefix = `S${s}E${e}`;
  return episode.title === '' ? prefix : `${prefix} · ${episode.title}`;
}

/**
 * How far along, 0 to 1, for the seek bar's fill.
 *
 * Guarded because `durationMs` is 0 for the first moments of every stream and
 * `position / 0` is Infinity, which becomes a flex value of Infinity and a
 * layout that never recovers.
 */
export function progressFraction(positionMs: number, durationMs: number): number {
  if (durationMs <= 0 || !Number.isFinite(positionMs)) return 0;
  return Math.min(1, Math.max(0, positionMs / durationMs));
}

/**
 * Where a tap or drag on the seek bar lands.
 *
 * `x` is relative to the track's left edge, so the caller passes the touch
 * position minus the track's own offset — the grab point, not the centre of
 * the thumb. Anything else makes the bar jump on first touch, which
 * `apple-design` names as the thing that breaks direct manipulation
 * immediately.
 */
export function seekTargetMs(x: number, trackWidth: number, durationMs: number): number {
  if (trackWidth <= 0 || durationMs <= 0) return 0;
  const fraction = Math.min(1, Math.max(0, x / trackWidth));
  return Math.floor(fraction * durationMs);
}
