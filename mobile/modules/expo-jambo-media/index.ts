/**
 * expo-jambo-media — the app's only native code (ADR-0002).
 *
 * Phase 2 scope: play a stream from a URL, report where it is, and be driven
 * by controls that live in React Native so they can wear the website's design
 * and be reached by a d-pad.
 *
 * Phase 3 adds the download engine, the AES-CTR encrypted cache and the
 * licence vault to this same module — into the same ExoPlayer data-source
 * chain, which is why this is a Kotlin module rather than `expo-video`. An
 * offline path built on a different player would be a second player.
 */
export { JamboPlayerView } from './src/JamboPlayerView';
export type {
  JamboPlayerHandle,
  JamboPlayerViewProps,
  PlaybackEnded,
  PlaybackError,
  PlaybackSource,
  PlaybackStatus,
} from './src/JamboMedia.types';
