package expo.modules.jambomedia

import expo.modules.kotlin.records.Field
import expo.modules.kotlin.records.Record

/**
 * What to play, and where to start.
 *
 * One record rather than two props so the pair can never be applied out of
 * order — see the `source` prop in JamboMediaModule for why that matters.
 *
 * `uri` is whatever `POST /api/v1/playback/sessions` returned: a token-signed
 * CDN URL with a limited life. It is deliberately NOT persisted anywhere on
 * the device, and Phase 3 keeps that property by resolving a fresh one at
 * cache-open time rather than storing the signed URL with the download.
 */
class PlaybackSource : Record {
  @Field
  var uri: String = ""

  /**
   * Where to start, in milliseconds.
   *
   * Applied at prepare rather than as a seek after the first frame: seeking
   * afterwards shows the opening of the film for an instant before jumping,
   * which reads as a glitch every time somebody resumes.
   */
  @Field
  var startPositionMs: Int = 0
}
