package expo.modules.jambomedia

import androidx.media3.common.util.UnstableApi
import expo.modules.kotlin.modules.Module
import expo.modules.kotlin.modules.ModuleDefinition

/**
 * `expo-jambo-media` — the app's only native code, per ADR-0002.
 *
 * In this phase it owns exactly one thing: playing a stream from a URL. The
 * download engine, the AES-CTR cache and the licence vault the ADR also
 * assigns to this module are Phase 3 and are deliberately absent rather than
 * stubbed, because a stub that answers "not downloaded" is indistinguishable
 * from a bug at the call site.
 *
 * The surface is small on purpose. Everything that can be decided in
 * TypeScript is decided in TypeScript, where it can be unit tested and changed
 * without a native rebuild.
 */
@UnstableApi
class JamboMediaModule : Module() {
  override fun definition() = ModuleDefinition {
    Name("ExpoJamboMedia")

    View(JamboPlayerView::class) {
      Events("onStatus", "onEnded", "onError")

      /*
       * Source and start position are ONE prop, not two.
       *
       * As separate props they arrive in whatever order the view manager
       * applies them, so a resume could be set before the URL it belongs to
       * and then be cleared by the prepare — a film that resumes from zero
       * roughly half the time, depending on prop ordering. Together they
       * cannot disagree.
       */
      Prop("source") { view: JamboPlayerView, source: PlaybackSource? ->
        view.setSource(source?.uri, source?.startPositionMs?.toLong() ?: 0L)
      }

      Prop("paused") { view: JamboPlayerView, paused: Boolean ->
        view.setPaused(paused)
      }

      Prop("volume") { view: JamboPlayerView, volume: Float ->
        view.setVolume(volume)
      }

      Prop("speed") { view: JamboPlayerView, speed: Float ->
        view.setSpeed(speed)
      }

      Prop("secure") { view: JamboPlayerView, secure: Boolean ->
        view.setSecure(secure)
      }

      Prop("keepAwake") { view: JamboPlayerView, keepAwake: Boolean ->
        view.setKeepAwake(keepAwake)
      }

      AsyncFunction("play") { view: JamboPlayerView ->
        view.play()
      }

      AsyncFunction("pause") { view: JamboPlayerView ->
        view.pause()
      }

      AsyncFunction("seekTo") { view: JamboPlayerView, positionMs: Double ->
        view.seekTo(positionMs.toLong())
      }

      AsyncFunction("seekBy") { view: JamboPlayerView, deltaMs: Double ->
        view.seekBy(deltaMs.toLong())
      }

      /*
       * Changing quality mid-film. Separate from `source` because it means
       * something different: `source` is "play this title", and a re-prepare
       * from the same position is the *opposite* of what it should do when the
       * title genuinely changes.
       */
      AsyncFunction("replaceSource") { view: JamboPlayerView, uri: String, keepPosition: Boolean ->
        view.replaceSource(uri, keepPosition)
      }
    }
  }
}
