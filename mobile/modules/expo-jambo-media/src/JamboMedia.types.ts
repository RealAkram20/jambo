import type { ViewStyle } from 'react-native';

/**
 * What to play and where to start, as one value.
 *
 * The pair is a single prop rather than two because the native view applies
 * props in an unspecified order: a start position set before the URL it
 * belongs to is discarded by the prepare that follows, so a resume would work
 * or not depending on ordering. See `JamboMediaModule.kt`.
 */
export type PlaybackSource = {
  /**
   * The URL from `POST /playback/sessions`. Token-signed with a limited life.
   *
   * Never log it and never put it in a WebView — the contract in
   * `docs/api/openapi.yaml` says so, and the reason is that the website's own
   * player leaks its URL into the browser's network tab while the app's does
   * not. That difference is the point of resolving the URL server-side.
   */
  uri: string;
  /** Where to begin, in milliseconds. Applied at prepare, not as a later seek. */
  startPositionMs?: number;
};

/**
 * The player's state, reported every 250ms while playing and immediately on
 * every change.
 *
 * `durationMs` is 0 until the media is prepared rather than null or negative:
 * ExoPlayer reports `C.TIME_UNSET` before then, and passing that across would
 * give the seek bar a negative width.
 */
export type PlaybackStatus = {
  positionMs: number;
  durationMs: number;
  bufferedMs: number;
  isPlaying: boolean;
  isBuffering: boolean;
  isReady: boolean;
};

export type PlaybackEnded = {
  positionMs: number;
  durationMs: number;
};

export type PlaybackError = {
  /** A stable Media3 identifier, e.g. `ERROR_CODE_IO_BAD_HTTP_STATUS`. Branch on this. */
  code: string;
  /** For a log or a diagnostic line. Never branch on it. */
  message: string;
};

export type JamboPlayerViewProps = {
  source: PlaybackSource | null;
  paused?: boolean;
  volume?: number;
  speed?: number;
  /**
   * Blocks screenshots and screen recording (Android `FLAG_SECURE`).
   *
   * Off in Phase 2 and that is deliberate: the website streams the same bytes
   * to an unprotected browser, so it protects nothing the site protects, and
   * it blacks out the emulator screenshots this work is verified with.
   * ADR-0003 wants it for offline playback, which is Phase 3.
   */
  secure?: boolean;
  /** Keeps the screen on. Should follow "is playing", not "is mounted". */
  keepAwake?: boolean;
  style?: ViewStyle;
  onStatus?: (event: { nativeEvent: PlaybackStatus }) => void;
  onEnded?: (event: { nativeEvent: PlaybackEnded }) => void;
  onError?: (event: { nativeEvent: PlaybackError }) => void;
};

/** The imperative surface, reached through a ref on the view. */
export type JamboPlayerHandle = {
  play: () => Promise<void>;
  pause: () => Promise<void>;
  seekTo: (positionMs: number) => Promise<void>;
  seekBy: (deltaMs: number) => Promise<void>;
  /**
   * Swap rendition without losing the viewer's place.
   *
   * Distinct from setting `source`, which means "play a different title" and
   * correctly starts from that title's own resume position.
   */
  replaceSource: (uri: string, keepPosition: boolean) => Promise<void>;
};
