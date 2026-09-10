package expo.modules.jambomedia

import android.content.Context
import android.view.WindowManager
import androidx.media3.common.MediaItem
import androidx.media3.common.PlaybackException
import androidx.media3.common.Player
import androidx.media3.common.util.UnstableApi
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.AspectRatioFrameLayout
import androidx.media3.ui.PlayerView
import expo.modules.kotlin.AppContext
import expo.modules.kotlin.viewevent.EventDispatcher
import expo.modules.kotlin.views.ExpoView

/**
 * The video surface. Nothing else.
 *
 * This view draws frames and reports state. Every control a viewer touches —
 * play, seek, the quality menu, the back button — is React Native in
 * `src/ui/player/`, and that split is deliberate rather than incidental:
 *
 *  - The controls must be the WEBSITE's controls. They are built from the
 *    design tokens captured off the real watch page, which Media3's own
 *    `PlayerView` control bar could never match.
 *  - Every control must be focusable by a d-pad through the app's existing
 *    `Focusable`, because ADR-0002 builds one codebase for phone, tablet and
 *    television and Phase 4 is meant to be configuration, not a rewrite.
 *  - A control that exists in Kotlin cannot be tested by the app's Jest suite
 *    or changed without a native rebuild.
 *
 * So `useController` is false and stays false.
 *
 * Phase 3 adds the encrypted cache to this class's data-source chain. That is
 * why the player is here at all rather than `expo-video`: the download engine,
 * the cipher and the licence vault of ADR-0003 have to live inside the same
 * ExoPlayer that plays online, or offline playback becomes a second player.
 */
@UnstableApi
class JamboPlayerView(context: Context, appContext: AppContext) :
  ExpoView(context, appContext) {

  private val onStatus by EventDispatcher()
  private val onEnded by EventDispatcher()
  private val onError by EventDispatcher()

  private val playerView = PlayerView(context)
  private var player: ExoPlayer? = null

  /** What the JS side last asked for, so a re-prepare can honour it. */
  private var wantsToPlay = true
  private var pendingStartMs = 0L
  private var currentUri: String? = null

  /** Where playback was when the view left the window, for a re-attach. */
  private var detachedAtMs = 0L

  /**
   * How often the position is reported while playing.
   *
   * 250ms rather than every frame. The seek bar is a few hundred pixels wide,
   * so a finer interval sends bridge traffic that cannot change a single
   * rendered pixel — and this is the one part of the player that runs
   * continuously for two hours on a phone the `ui-performance` skill says to
   * assume is a mid-range Android. Progress is ALSO emitted immediately on
   * every state change, so nothing waits up to 250ms to look responsive.
   */
  private val progressIntervalMs = 250L

  private val progressTicker = object : Runnable {
    override fun run() {
      emitStatus()
      if (player?.isPlaying == true) {
        postDelayed(this, progressIntervalMs)
      }
    }
  }

  private val listener = object : Player.Listener {
    override fun onPlaybackStateChanged(state: Int) {
      if (state == Player.STATE_ENDED) {
        // Ended is reported on its own channel rather than as a status the JS
        // side has to infer. Autoplay-next hangs off this, and inferring "the
        // position stopped changing near the end" would fire it on a stall.
        onEnded(mapOf("positionMs" to positionMs(), "durationMs" to durationMs()))
      }
      emitStatus()
      scheduleTicker()
    }

    override fun onIsPlayingChanged(isPlaying: Boolean) {
      emitStatus()
      scheduleTicker()
    }

    override fun onPlayerError(error: PlaybackException) {
      onError(
        mapOf(
          "code" to error.errorCodeName,
          // errorCodeName is the stable identifier ("ERROR_CODE_IO_BAD_HTTP_STATUS");
          // the message is for a human reading a log, never for a branch.
          "message" to (error.message ?: "Playback failed"),
        ),
      )
      emitStatus()
    }
  }

  /**
   * Let Android lay this view's contents out inside the bounds Yoga gives it.
   *
   * Without it React Native ignores `requestLayout()` from the child, and a
   * PlayerView requests one as soon as it learns the video's real dimensions —
   * so the surface would keep whatever size it was measured at before the
   * first frame arrived.
   */
  override val shouldUseAndroidLayout: Boolean = true

  init {
    playerView.useController = false
    playerView.resizeMode = AspectRatioFrameLayout.RESIZE_MODE_FIT
    // Black, not transparent. A surface that lets the screen behind it show
    // through flashes the previous screen's colour on every prepare.
    playerView.setShutterBackgroundColor(android.graphics.Color.BLACK)
    /*
     * MATCH_PARENT explicitly. `ExpoView` extends `LinearLayout`, so a child
     * added with default params gets WRAP_CONTENT and a video surface sized to
     * nothing — a screen that is correctly black and completely empty, which
     * is the hardest kind of blank screen to diagnose.
     */
    addView(
      playerView,
      LayoutParams(LayoutParams.MATCH_PARENT, LayoutParams.MATCH_PARENT),
    )
  }

  // ── props, called from the module definition ─────────────────────────

  fun setSource(uri: String?, startPositionMs: Long) {
    // Re-preparing the same URI would restart a film the viewer is watching,
    // which is what a re-render would otherwise do on every status event.
    if (uri == currentUri) return

    currentUri = uri
    pendingStartMs = startPositionMs

    if (uri.isNullOrEmpty()) {
      release()
      return
    }

    prepare(uri, startPositionMs)
  }

  fun setPaused(paused: Boolean) {
    wantsToPlay = !paused
    player?.playWhenReady = wantsToPlay
  }

  fun setVolume(volume: Float) {
    player?.volume = volume.coerceIn(0f, 1f)
  }

  /**
   * FLAG_SECURE, off by default in this phase.
   *
   * ADR-0003 wants it for OFFLINE playback, where the threat is a downloaded
   * file being captured. Online, the website streams the same bytes to a
   * browser with no such protection, so turning it on here would protect
   * nothing the site protects and would black out every screenshot this slice
   * is verified with. The switch exists so Phase 3 turns it on rather than
   * discovering it needs a native change.
   */
  fun setSecure(secure: Boolean) {
    val window = appContext.currentActivity?.window ?: return
    if (secure) {
      window.setFlags(WindowManager.LayoutParams.FLAG_SECURE, WindowManager.LayoutParams.FLAG_SECURE)
    } else {
      window.clearFlags(WindowManager.LayoutParams.FLAG_SECURE)
    }
  }

  /** Keep the screen on while a film is playing, and only while it is. */
  fun setKeepAwake(keepAwake: Boolean) {
    val window = appContext.currentActivity?.window ?: return
    if (keepAwake) {
      window.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)
    } else {
      window.clearFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)
    }
  }

  fun setSpeed(speed: Float) {
    player?.setPlaybackSpeed(speed.coerceIn(0.25f, 4f))
  }

  // ── view functions ───────────────────────────────────────────────────

  fun play() {
    wantsToPlay = true
    player?.playWhenReady = true
  }

  fun pause() {
    wantsToPlay = false
    player?.playWhenReady = false
  }

  /** Absolute seek, clamped to the media. */
  fun seekTo(positionMs: Long) {
    val p = player ?: run {
      // No player yet means the source has not prepared. Remember the target
      // so it is honoured at prepare rather than silently discarded — this is
      // the path a resume position takes when the screen mounts.
      pendingStartMs = positionMs.coerceAtLeast(0)
      return
    }
    p.seekTo(clamp(positionMs))
    emitStatus()
  }

  /** Relative seek. The ±10s buttons and the remote's left/right keys. */
  fun seekBy(deltaMs: Long) {
    val p = player ?: return
    p.seekTo(clamp(p.currentPosition + deltaMs))
    emitStatus()
  }

  /**
   * Swap the rendition without losing the viewer's place.
   *
   * Changing quality is a new URL for the same film, so the position has to be
   * carried across by hand — ExoPlayer has no concept that these two files are
   * the same title. The position is read BEFORE the release, because a
   * released player reports 0 and the viewer would restart from the beginning.
   */
  fun replaceSource(uri: String, keepPosition: Boolean) {
    val resumeAt = if (keepPosition) positionMs() else 0L
    val wasPlaying = wantsToPlay
    currentUri = uri
    prepare(uri, resumeAt)
    wantsToPlay = wasPlaying
    player?.playWhenReady = wasPlaying
  }

  // ── internals ────────────────────────────────────────────────────────

  private fun prepare(uri: String, startMs: Long) {
    release()

    val exo = ExoPlayer.Builder(context).build()
    player = exo
    playerView.player = exo
    exo.addListener(listener)

    exo.setMediaItem(MediaItem.fromUri(uri))
    // The start position goes in at prepare rather than as a seek afterwards.
    // Seeking after the first frame renders shows the opening of the film for
    // an instant before jumping, which reads as a glitch on a resume.
    if (startMs > 0) exo.seekTo(startMs)
    exo.playWhenReady = wantsToPlay
    exo.prepare()

    pendingStartMs = 0
    scheduleTicker()
  }

  private fun release() {
    removeCallbacks(progressTicker)
    player?.removeListener(listener)
    playerView.player = null
    player?.release()
    player = null
  }

  private fun scheduleTicker() {
    removeCallbacks(progressTicker)
    if (player?.isPlaying == true) postDelayed(progressTicker, progressIntervalMs)
  }

  private fun clamp(positionMs: Long): Long {
    val duration = durationMs()
    val upper = if (duration > 0) duration else Long.MAX_VALUE
    return positionMs.coerceIn(0, upper)
  }

  private fun positionMs(): Long = player?.currentPosition?.coerceAtLeast(0) ?: 0

  /**
   * Duration, or 0 when it is not known yet.
   *
   * ExoPlayer reports `C.TIME_UNSET` (Long.MIN_VALUE + 1) before the media is
   * prepared. Passing that across the bridge would give the seek bar a
   * negative duration and the time display something like -2562047h.
   */
  private fun durationMs(): Long {
    val d = player?.duration ?: 0
    return if (d > 0) d else 0
  }

  private fun emitStatus() {
    val p = player
    onStatus(
      mapOf(
        "positionMs" to positionMs(),
        "durationMs" to durationMs(),
        "bufferedMs" to (p?.bufferedPosition?.coerceAtLeast(0) ?: 0),
        "isPlaying" to (p?.isPlaying ?: false),
        "isBuffering" to (p?.playbackState == Player.STATE_BUFFERING),
        "isReady" to (p?.playbackState == Player.STATE_READY),
      ),
    )
  }

  override fun onDetachedFromWindow() {
    // Releasing here rather than only on unmount matters on Android: a view
    // detached while a film plays keeps decoding audio otherwise, so leaving
    // the screen would leave the film audible over the next one.
    //
    // The position is kept so a re-attach resumes where it was — see below.
    detachedAtMs = positionMs()
    setKeepAwake(false)
    release()
    super.onDetachedFromWindow()
  }

  /**
   * Re-prepare after a detach that was not a teardown.
   *
   * Android detaches and re-attaches views for reasons that are nothing to do
   * with navigation. Without this the player would be released on the way out
   * and never rebuilt on the way back, because `setSource` short-circuits when
   * the uri has not changed — a black screen with the controls still working,
   * which looks exactly like a broken video rather than a lifecycle problem.
   */
  override fun onAttachedToWindow() {
    super.onAttachedToWindow()
    val uri = currentUri
    if (player == null && !uri.isNullOrEmpty()) {
      prepare(uri, detachedAtMs)
    }
  }
}
