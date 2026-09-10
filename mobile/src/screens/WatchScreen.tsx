import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { BackHandler, Pressable, StyleSheet, Text, View } from 'react-native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useNetInfo } from '@react-native-community/netinfo';

import { JamboPlayerView, type JamboPlayerHandle, type PlaybackStatus } from '../../modules/expo-jambo-media';
import { api } from '../api/jambo';
import { seasonsOf } from '../api/catalogue';
import { ApiError } from '../api/errors';
import { assetUrl } from '../ui/media';
import { Button, Spinner } from '../ui/components';
import { PlayerControls, SEEK_STEP_MS } from '../ui/player/PlayerControls';
import { PlayerMenu } from '../ui/player/PlayerMenu';
import { useRemoteKeys } from '../ui/player/useRemoteKeys';
import {
  clampPosition,
  episodeLabel,
  nextEpisodeAfter,
  preferredRendition,
  resumeStartMs,
  usableRendition,
  type Rendition,
  type VideoQualityPreference,
} from '../ui/player/playback';
import { colors, fonts, player, spacing } from '../ui/theme';
import type { AppScreenProps } from '../navigation/types';

/**
 * The player screen.
 *
 * What it owns: the session, the heartbeat, resume, quality, and what happens
 * when a title ends. What it does NOT own: how anything looks (that is
 * `ui/player/`), how anything is decided (that is `ui/player/playback.ts`, and
 * it is pure so it can be tested), and how frames get on screen (that is
 * `expo-jambo-media`).
 *
 * Online only, per the phase. There is no download control anywhere on this
 * screen, because the licence, the encrypted cache and the vault it would
 * need are Phase 3 and a Download button that cannot download is exactly the
 * kind of promise slice 2b removed from the Continue Watching card.
 */
export function WatchScreen({ route, navigation }: AppScreenProps<'Watch'>) {
  const { type, id, title: passedTitle, subtitle: passedSubtitle, seriesSlug } = route.params;
  const queryClient = useQueryClient();
  const netInfo = useNetInfo();

  const playerRef = useRef<JamboPlayerHandle>(null);

  const [status, setStatus] = useState<PlaybackStatus>({
    positionMs: 0,
    durationMs: 0,
    bufferedMs: 0,
    isPlaying: false,
    isBuffering: true,
    isReady: false,
  });
  const [paused, setPaused] = useState(false);
  const [controlsVisible, setControlsVisible] = useState(true);
  const [menuOpen, setMenuOpen] = useState(false);
  const [speed, setSpeed] = useState(1);
  const [notice, setNotice] = useState<string | null>(null);
  const [ended, setEnded] = useState(false);
  const [scrubbing, setScrubbing] = useState(false);

  /*
   * The viewer's account preference, read once per screen.
   *
   * `staleTime: Infinity` because a background refetch that landed while a
   * film was playing would re-derive the rendition and could swap the file
   * under the viewer. This is the player's version of the rule that a form
   * must not be re-seeded from a query mid-edit.
   */
  const { data: preferences } = useQuery({
    queryKey: ['preferences'],
    queryFn: () => api.preferences(),
    staleTime: Number.POSITIVE_INFINITY,
  });

  const isCellular = netInfo.type === 'cellular';

  const quality: VideoQualityPreference =
    (preferences?.video_quality as VideoQualityPreference | undefined) ?? 'auto';

  /*
   * ONLY an explicit choice is held in state, and only that is in the query
   * key. The automatic choice is resolved inside `queryFn`.
   *
   * This matters more than it looks. The first cut stored the resolved
   * rendition in state and seeded it from an effect, which put the connection
   * type into the query key — so a phone that dropped from wifi to cellular
   * mid-film changed the key, opened a new session, and re-prepared the player
   * from the server's last known position. The viewer would have seen the film
   * jump backwards several seconds for no reason they could observe.
   *
   * With the key holding only the override, a connection flap changes nothing
   * that is already playing, while the viewer choosing a quality still
   * refetches immediately. React Query calls the latest `queryFn`, so the auto
   * decision is made from current values without them being dependencies.
   */
  const [override, setOverride] = useState<Rendition | null>(null);

  const {
    data: session,
    isPending,
    isError,
    error,
    refetch,
  } = useQuery({
    queryKey: ['playback', type, id, override],
    queryFn: async () => {
      const wanted = override ?? preferredRendition(quality, isCellular);

      try {
        return await api.playbackSession(type, id, wanted);
      } catch (cause) {
        /*
         * A Data Saver request for a title with no low rendition is refused
         * rather than quietly served at full size — the server is right to do
         * that, since silently spending someone's bundle is the one thing Data
         * Saver exists to prevent. So the app retries at default AND SAYS SO.
         * Retrying in silence would leave a viewer on Data Saver watching the
         * full-size file believing otherwise.
         */
        if (cause instanceof ApiError && cause.code === 'CONTENT_UNAVAILABLE' && wanted === 'low') {
          setNotice('This title has no data saver version, so it is playing at full quality.');
          return await api.playbackSession(type, id, 'default');
        }
        throw cause;
      }
    },
    // Waits for the preference, so the first session is opened at the quality
    // the viewer actually chose rather than at a default that then swaps.
    enabled: preferences !== undefined,
    // A signed CDN URL has a limited life; refetching one mid-film would
    // replace a working URL with a new one and re-prepare the player.
    staleTime: Number.POSITIVE_INFINITY,
    retry: false,
  });

  /*
   * Landscape is set by the NAVIGATOR, not here.
   *
   * `RootNavigator` gives this route `orientation: 'landscape'`, which
   * react-native-screens applies to the Activity for as long as the screen is
   * on the stack and unwinds by itself on the way out. The first version of
   * this reached for `expo-screen-orientation` — a new native dependency, a
   * lock that has to be released by hand in a cleanup, and a package-lock
   * conflict with the session working alongside this one. The navigator
   * already owned the answer.
   *
   * It matters more than it sounds: a phone with rotation lock on would
   * otherwise hold the player in portrait, showing a letterboxed strip with
   * two thirds of the screen black.
   */

  // ── the heartbeat ──────────────────────────────────────────────────

  /*
   * The position is read from a ref rather than from state inside the
   * interval. The interval is established once; a closure over `status` would
   * beat the same position for the whole film.
   */
  const statusRef = useRef(status);

  // Written in an effect rather than during render: a ref updated in a render
  // React goes on to discard would leave the heartbeat reporting a position
  // that was never on screen.
  useEffect(() => {
    statusRef.current = status;
  }, [status]);

  const beat = useCallback(
    async (final = false) => {
      const { positionMs, durationMs } = statusRef.current;
      // Nothing to report before the first frame. A beat at 0 would overwrite
      // a real resume position with zero if the viewer left immediately.
      if (positionMs <= 0) return;

      try {
        await api.heartbeat(
          type,
          id,
          Math.floor(positionMs / 1000),
          durationMs > 0 ? Math.floor(durationMs / 1000) : undefined,
        );
        if (final) {
          // The rails that show a resume position are now wrong. Invalidated
          // rather than refetched so it costs nothing until something needs it.
          void queryClient.invalidateQueries({ queryKey: ['home'] });
          void queryClient.invalidateQueries({ queryKey: ['continue-watching'] });
          void queryClient.invalidateQueries({ queryKey: ['history'] });
        }
      } catch (cause) {
        /*
         * A 409 means this device was signed out of the account while it was
         * playing — someone booted it from the website's device list. The
         * client's own 401 handling signs out; here the job is simply to stop
         * playing rather than keep beating into a dead session.
         */
        if (cause instanceof ApiError && cause.code === 'DEVICE_REVOKED') {
          setPaused(true);
          setNotice('This device was signed out of your account.');
        }
        // Any other failure is a dropped connection. The next beat tries
        // again; a lost heartbeat costs a few seconds of resume accuracy and
        // is not worth interrupting a film for.
      }
    },
    [type, id, queryClient],
  );

  /**
   * The interval the SERVER chose.
   *
   * Never a constant in this app. A shipped APK cannot be re-tuned, so if
   * beats ever cost too much on the VPS this number moves server-side and
   * every installed copy slows down without a release.
   */
  const heartbeatMs = (session?.heartbeat_seconds ?? 15) * 1000;

  useEffect(() => {
    if (session === undefined || paused || !status.isPlaying) return undefined;

    const timer = setInterval(() => {
      void beat();
    }, heartbeatMs);

    return () => clearInterval(timer);
  }, [session, paused, status.isPlaying, heartbeatMs, beat]);

  /*
   * A final beat on the way out, so leaving mid-film resumes where the viewer
   * actually was rather than at the last interval tick — up to fifteen
   * seconds earlier, which is very noticeable on a short episode.
   */
  useEffect(
    () => () => {
      void beat(true);
    },
    [beat],
  );

  // ── the next episode ───────────────────────────────────────────────

  /*
   * Resolved from the series, which needs the series slug. A playback session
   * does not carry one, so it is either passed in by the caller or looked up
   * from `/episodes/{id}` — which returns the episode AND its series.
   */
  const { data: episodeContext } = useQuery({
    queryKey: ['episode-context', id],
    queryFn: () => api.episode(id),
    enabled: type === 'episode' && seriesSlug === undefined,
    staleTime: Number.POSITIVE_INFINITY,
  });

  const resolvedSeriesSlug = seriesSlug ?? episodeContext?.series?.slug;

  /*
   * `api.titleDetail`, and the SAME query key the detail screen uses.
   *
   * 🔴 The first version called `api.seriesDetail` under the key
   * `['title', 'series', slug]`, which is exactly the key
   * `TitleDetailScreen` already writes — with a different shape. Its queryFn
   * returns `{ detail, isReleased }`; mine returned `{ series, isReleased }`.
   * So opening an episode from a series page read the detail screen's cached
   * entry, `series.series` was undefined, and the player crashed on
   * `Cannot read property 'seasons' of undefined`.
   *
   * Typescript could not catch it: the cached value is typed by whichever
   * queryFn the compiler sees at each call site, and React Query keys are not
   * part of the type. Only rendering it found this.
   *
   * Sharing the call as well as the key fixes it properly and means opening
   * the player from a series page reuses the page's own fetch rather than
   * issuing a second one.
   */
  const { data: series } = useQuery({
    queryKey: ['title', 'series', resolvedSeriesSlug],
    queryFn: () => api.titleDetail('series', resolvedSeriesSlug as string),
    enabled: type === 'episode' && typeof resolvedSeriesSlug === 'string',
    staleTime: Number.POSITIVE_INFINITY,
  });

  const nextEpisode = useMemo(() => {
    if (type !== 'episode') return null;
    const detail = series?.detail;
    return detail === undefined ? null : nextEpisodeAfter(seasonsOf(detail), id);
  }, [type, series, id]);

  const goToNextEpisode = useCallback(() => {
    if (nextEpisode === null) return;
    // `replace`, not `push`: a viewer who watches six episodes should not have
    // six players stacked behind them, and back from episode four should
    // return to the series, not to episode three.
    navigation.replace('Watch', {
      type: 'episode',
      id: nextEpisode.id,
      title: series?.detail.title ?? passedTitle ?? '',
      subtitle: episodeLabel(nextEpisode),
      ...(resolvedSeriesSlug === undefined ? {} : { seriesSlug: resolvedSeriesSlug }),
    });
  }, [nextEpisode, navigation, series, passedTitle, resolvedSeriesSlug]);

  // ── controls ───────────────────────────────────────────────────────

  const showControls = useCallback(() => setControlsVisible(true), []);
  const hideControls = useCallback(() => setControlsVisible(false), []);

  const togglePlay = useCallback(() => {
    setPaused((current) => !current);
    setEnded(false);
  }, []);

  const seekTo = useCallback(
    (positionMs: number) => {
      void playerRef.current?.seekTo(clampPosition(positionMs, statusRef.current.durationMs));
      setEnded(false);
    },
    [],
  );

  const seekBy = useCallback((deltaMs: number) => {
    void playerRef.current?.seekBy(deltaMs);
    setEnded(false);
  }, []);

  useRemoteKeys({
    onPlayPause: togglePlay,
    onSeekBy: seekBy,
    onShowControls: showControls,
    onSettings: () => setMenuOpen(true),
    // While a menu is up it owns the d-pad. Otherwise pressing left inside the
    // quality list would also seek the film behind it.
    enabled: !menuOpen,
    seekStepMs: SEEK_STEP_MS,
  });

  /*
   * Android back closes the menu first, then leaves. Without this, back from
   * an open menu exits the player entirely and the viewer loses their place —
   * the "how do I get out" rule, answered one layer at a time.
   */
  useEffect(() => {
    const sub = BackHandler.addEventListener('hardwareBackPress', () => {
      if (menuOpen) {
        setMenuOpen(false);
        return true;
      }
      return false;
    });
    return () => sub.remove();
  }, [menuOpen]);

  const changeQuality = useCallback(
    (next: VideoQualityPreference) => {
      setMenuOpen(false);
      setNotice(null);

      /*
       * Written to the ACCOUNT, not kept here. `/account/preferences` is the
       * one source of truth for `video_quality`, which is what makes a choice
       * made on a phone true on the television later. PATCH with only the key
       * that moved — the endpoint merges, so resending the whole set would
       * reset a field the viewer did not touch.
       */
      void api
        .updatePreferences({ video_quality: next })
        .then((saved) => {
          queryClient.setQueryData(['preferences'], saved);
        })
        .catch(() => {
          setNotice('That preference could not be saved, but this title is playing at the new quality.');
        });

      const wanted = preferredRendition(next, isCellular);
      const usable = usableRendition(wanted, session?.available_qualities);

      if (usable !== wanted) {
        setNotice('This title has no data saver version, so it is playing at full quality.');
      }

      setOverride(usable);
    },
    [isCellular, session, queryClient],
  );

  const changeSpeed = useCallback((next: number) => {
    setSpeed(next);
    setMenuOpen(false);
  }, []);

  // ── render ─────────────────────────────────────────────────────────

  if (isError) {
    const message =
      error instanceof ApiError && error.message !== ''
        ? error.message
        : 'We could not start this title.';

    /*
     * A refusal is rendered honestly rather than as a generic failure. The
     * server distinguishes "you need a plan" from "this has no video yet" from
     * "you are already watching on too many devices", and each needs a
     * different thing from the viewer.
     */
    const isSubscription =
      error instanceof ApiError &&
      (error.code === 'SUBSCRIPTION_REQUIRED' || error.code === 'UPGRADE_REQUIRED');

    return (
      <View style={styles.screen}>
        <View style={styles.centre}>
          <Text style={styles.refusalTitle}>{message}</Text>
          {isSubscription ? (
            <>
              {/*
                Plans, not checkout. ADR-0004 forbids buying inside the Play
                build, and the `direct` build's PesaPal flow does not exist
                server-side — two independent reasons, either sufficient.
              */}
              <Button label="See plans" onPress={() => navigation.replace('Plans')} />
            </>
          ) : (
            <Button label="Try again" onPress={() => void refetch()} />
          )}
          <Pressable onPress={() => navigation.goBack()} accessibilityRole="button">
            <Text style={styles.refusalBack}>Go back</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  /*
   * A YouTube-backed title has no file to hand a native player.
   *
   * The website plays these in an iframe. The app has no WebView and adding
   * one for this would be a native dependency plus YouTube's embed rules on
   * Android; opening the system browser completes honestly instead. What it
   * cannot do is heartbeat or resume, so this screen says what it is rather
   * than pretending to be the player.
   */
  if (session?.source === 'embed') {
    return (
      <View style={styles.screen}>
        <View style={styles.centre}>
          <Text style={styles.refusalTitle}>{passedTitle ?? 'This title'} plays on YouTube.</Text>
          <Text style={styles.refusalDetail}>
            It opens in your browser, and your progress is not saved.
          </Text>
          <Button
            label="Open"
            onPress={() => {
              const url = session.embed_url;
              if (typeof url === 'string' && url !== '') {
                void import('react-native').then(({ Linking }) => Linking.openURL(url));
              }
            }}
          />
          <Pressable onPress={() => navigation.goBack()} accessibilityRole="button">
            <Text style={styles.refusalBack}>Go back</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  const uri = assetUrl(session?.url);

  // The end card only appears when there is nowhere else to go. With a next
  // episode the autoplay countdown owns that moment instead.
  const showEndCard = ended && nextEpisode === null;

  return (
    <View style={styles.screen}>
      {uri === null ? null : (
        <JamboPlayerView
          ref={playerRef}
          style={styles.video}
          source={{
            uri,
            /*
             * The resume goes in at PREPARE, not as a seek afterwards.
             * Seeking after the first frame shows the opening of the film for
             * an instant before jumping, which reads as a glitch every time
             * anybody resumes. `resumeStartMs` also refuses a position past
             * the end of the media — the seeded history in this database says
             * 1512s for a 52s fixture.
             */
            startPositionMs: resumeStartMs(session?.resume_position, 0),
          }}
          paused={paused}
          speed={speed}
          // Follows "is playing", not "is mounted": a paused player should not
          // hold the screen on while somebody reads something else.
          keepAwake={status.isPlaying}
          onStatus={({ nativeEvent }) => {
            // Ignored while a finger is on the seek bar. The player reports
            // four times a second and would otherwise fight the gesture — the
            // same class of bug as a background refetch discarding a typed
            // form value.
            if (!scrubbing) setStatus(nativeEvent);
          }}
          onEnded={() => {
            setEnded(true);
            void beat(true);
            if (preferences?.autoplay_next !== false && nextEpisode !== null) {
              goToNextEpisode();
            }
          }}
          onError={({ nativeEvent }) => {
            setNotice(
              nativeEvent.code === 'ERROR_CODE_IO_BAD_HTTP_STATUS'
                ? 'That video could not be loaded.'
                : 'Playback stopped unexpectedly.',
            );
          }}
        />
      )}

      {/*
        The whole surface toggles the controls. A tap must not fall through to
        the video and do nothing, which is what a viewer expects least.
      */}
      <Pressable
        style={StyleSheet.absoluteFill}
        onPress={() => (controlsVisible ? hideControls() : showControls())}
        accessibilityLabel={controlsVisible ? 'Hide controls' : 'Show controls'}
      />

      {isPending || (!status.isReady && !status.isPlaying) ? (
        <View style={styles.buffering} pointerEvents="none">
          <Spinner tone="onPrimary" />
        </View>
      ) : null}

      <PlayerControls
        title={passedTitle ?? ''}
        subtitle={passedSubtitle}
        positionMs={status.positionMs}
        durationMs={status.durationMs}
        bufferedMs={status.bufferedMs}
        isPlaying={status.isPlaying}
        visible={controlsVisible}
        onRequestShow={showControls}
        onRequestHide={hideControls}
        onPlayPause={togglePlay}
        onSeek={(ms) => {
          setScrubbing(false);
          seekTo(ms);
        }}
        onSeekBy={seekBy}
        onBack={() => navigation.goBack()}
        onSettings={() => setMenuOpen(true)}
        onNextEpisode={nextEpisode === null ? undefined : goToNextEpisode}
        hideTransport={showEndCard}
      />

      {notice === null ? null : (
        <View style={styles.notice} pointerEvents="box-none">
          <Text style={styles.noticeText}>{notice}</Text>
          <Pressable onPress={() => setNotice(null)} accessibilityLabel="Dismiss">
            <Text style={styles.noticeDismiss}>OK</Text>
          </Pressable>
        </View>
      )}

      {showEndCard ? (
        /*
         * `box-none` on the layer, so the title bar and the scrubber underneath
         * stay reachable. The first cut was a full-screen View that swallowed
         * every touch — on the emulator the settings gear became untappable
         * the moment a film ended, which is a control that exists and cannot
         * be used.
         */
        <View style={styles.endLayer} pointerEvents="box-none">
          <View style={styles.endCard}>
            <Text style={styles.endText}>Finished</Text>
            <View style={styles.endActions}>
              {/*
                Replay before Back. A viewer who reaches the end of something
                short most often wants it again, and without this the only way
                to rewatch is to leave the player and come back — which then
                resumes at the end and finishes immediately.
              */}
              <Button
                label="Watch again"
                onPress={() => {
                  setEnded(false);
                  setPaused(false);
                  void playerRef.current?.seekTo(0);
                  void playerRef.current?.play();
                }}
              />
              <Button label="Back" tone="quiet" onPress={() => navigation.goBack()} />
            </View>
          </View>
        </View>
      ) : null}

      {menuOpen ? (
        <>
          <Pressable
            style={[StyleSheet.absoluteFill, styles.scrim]}
            onPress={() => setMenuOpen(false)}
            accessibilityLabel="Close settings"
          />
          <View style={styles.menuAnchor}>
            <PlayerMenu
              quality={quality}
              available={session?.available_qualities ?? ['default']}
              onQuality={changeQuality}
              speed={speed}
              onSpeed={changeSpeed}
              onClose={() => setMenuOpen(false)}
            />
          </View>
        </>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: '#000000' },
  centre: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.md,
    padding: spacing.xl,
  },
  refusalTitle: {
    color: colors.text,
    fontFamily: fonts.medium,
    fontSize: 18,
    textAlign: 'center',
  },
  refusalDetail: {
    color: colors.textMuted,
    fontFamily: fonts.regular,
    fontSize: 14,
    textAlign: 'center',
  },
  refusalBack: {
    color: colors.textMuted,
    fontFamily: fonts.regular,
    fontSize: 14,
    paddingVertical: spacing.sm,
  },
  /*
   * `fill` rather than `StyleSheet.absoluteFill`: that export is a registered
   * style id, so it cannot be spread into an object literal, and this RN build
   * does not type `absoluteFillObject`. Written out once and reused.
   */
  video: { position: 'absolute', top: 0, left: 0, right: 0, bottom: 0 },
  buffering: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    alignItems: 'center',
    justifyContent: 'center',
  },
  notice: {
    position: 'absolute',
    left: spacing.md,
    right: spacing.md,
    bottom: 90,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: player.menuBg,
    borderRadius: player.menuRadius,
    paddingHorizontal: player.menuRowPadX,
    paddingVertical: player.menuRowPadY,
  },
  noticeText: {
    flex: 1,
    color: player.menuFg,
    fontFamily: fonts.regular,
    fontSize: player.menuLabelSize,
  },
  noticeDismiss: {
    color: player.menuFg,
    fontFamily: fonts.medium,
    fontSize: player.menuLabelSize,
  },
  endLayer: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    alignItems: 'center',
    justifyContent: 'center',
  },
  endCard: {
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing.lg,
    borderRadius: player.menuRadius,
    backgroundColor: player.menuBg,
  },
  endActions: { flexDirection: 'row', gap: spacing.md, alignItems: 'center' },
  endText: {
    color: colors.text,
    fontFamily: fonts.medium,
    fontSize: 20,
  },
  scrim: { backgroundColor: 'rgba(0, 0, 0, 0.4)' },
  menuAnchor: {
    position: 'absolute',
    right: spacing.md,
    top: spacing.xl * 2,
  },
});
