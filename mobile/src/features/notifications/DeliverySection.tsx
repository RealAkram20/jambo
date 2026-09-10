import { useEffect, useState } from 'react';
import { Animated, Easing, StyleSheet, Switch, View } from 'react-native';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Bell, CaretDown, EnvelopeSimple, GearSix } from 'phosphor-react-native';

import { api } from '../../api/jambo';
import { ApiError, NetworkError } from '../../api/errors';
import { Alert, Caption, Spinner } from '../../ui/components';
import { ListCard, ListRow } from '../../ui/list';
import { useReducedMotion } from '../../ui/motion';
import { colors, list, spacing } from '../../ui/theme';

type Channels = { in_app: boolean; email: boolean; push: boolean };

/**
 * Delivery, at the head of the inbox, closed until somebody asks for it.
 *
 * **It was a screen behind a gear until 2026-09-10.** 174 lines for two
 * switches, reached from an overflow menu, on a subject the inbox is already
 * about. `docs/plans/account-area-audit.md` §4.2: *"One screen, one subject,
 * and the gear stops being a place you have to know about."* The website keeps
 * these on the notifications page itself, so this is also the app coming back
 * into line with the surface it was ported from.
 *
 * **Closed by default and that is the whole point of folding it in rather than
 * pasting it in.** A viewer opens this screen to read messages, not to
 * configure them, so the section costs one row until it is wanted. It fetches
 * nothing while closed either — the preferences query only runs once the
 * section has been opened, so the inbox still makes exactly the requests it
 * made before for everybody who never touches it.
 *
 * **Push is deliberately absent, and it is the interesting omission.** The
 * website has a third switch; this has two. There it drives a browser Web Push
 * subscription, so in the app it would do one of two wrong things: silence the
 * viewer's *browser* notifications from their phone, or toggle a flag for an
 * app channel that cannot deliver. The app registers no FCM token and
 * `docs/api/coverage.md` records that the registry has no sender. The endpoint
 * carries `push` either way, so the row is a few lines away on the day push
 * actually delivers.
 */
export function DeliverySection() {
  const queryClient = useQueryClient();
  const [open, setOpen] = useState(false);
  const [failed, setFailed] = useState<string | null>(null);

  /*
   * `enabled: open` is not an optimisation, it is the contract of a collapsed
   * section: a viewer who never opens this must not pay a request for it. It
   * stays enabled once opened, so closing and reopening is instant.
   */
  const prefs = useQuery({
    queryKey: ['notification-channels'],
    queryFn: () => api.notificationChannels(),
    enabled: open,
  });

  /*
   * `onMutate` moves the switch immediately and `onError` puts it back. A
   * toggle that waits for a round trip on a Ugandan mobile connection feels
   * broken, people press it again, and the second press undoes the first —
   * the same reasoning, and the same shape, as the streaming preferences.
   */
  const save = useMutation({
    mutationFn: (changes: Partial<Channels>) => api.setNotificationChannels(changes),
    onMutate: async (changes) => {
      setFailed(null);
      await queryClient.cancelQueries({ queryKey: ['notification-channels'] });

      const previous = queryClient.getQueryData<{ channels: Channels; emailVerified: boolean }>([
        'notification-channels',
      ]);

      if (previous !== undefined) {
        queryClient.setQueryData(['notification-channels'], {
          ...previous,
          channels: { ...previous.channels, ...changes },
        });
      }

      return { previous };
    },
    onError: (error, _changes, context) => {
      if (context?.previous !== undefined) {
        queryClient.setQueryData(['notification-channels'], context.previous);
      }

      setFailed(
        error instanceof NetworkError
          ? 'Could not reach Jambo, so that change was not saved.'
          : error instanceof ApiError
            ? error.message
            : 'That change was not saved.',
      );
    },
    onSettled: () => queryClient.invalidateQueries({ queryKey: ['notification-channels'] }),
  });

  return (
    <View style={styles.wrap}>
      <ListCard>
        <ListRow
          icon={GearSix}
          label="Delivery"
          chevron={false}
          expanded={open}
          accessibilityLabel="Delivery settings"
          onPress={() => setOpen((was) => !was)}
          accessory={<Caret open={open} />}
          last={!open}
        />
      </ListCard>

      <Disclosure open={open}>
        <View style={styles.body}>
          {failed !== null ? <Alert tone="error">{failed}</Alert> : null}

          {prefs.isError ? (
            <Alert tone="error">
              {prefs.error instanceof Error && prefs.error.message !== ''
                ? prefs.error.message
                : 'We could not load your notification settings.'}
            </Alert>
          ) : prefs.data === undefined ? (
            /* One row's worth of spinner. A full-screen `Loading` here would
               replace the inbox the viewer is reading. */
            <View style={styles.loading}>
              <Spinner />
            </View>
          ) : (
            <ListCard>
              <ListRow
                icon={Bell}
                label="In-app"
                accessory={
                  <Switch
                    value={prefs.data.channels.in_app}
                    onValueChange={(next) => save.mutate({ in_app: next })}
                    disabled={save.isPending}
                    accessibilityLabel="In-app notifications"
                    trackColor={{ false: colors.inputBg, true: colors.primary }}
                    thumbColor={colors.fieldText}
                  />
                }
              />

              <ListRow
                icon={EnvelopeSimple}
                label="Email"
                /*
                 * The one sentence in this section, and it earns its place: an
                 * unverified address is why the emails a viewer switched on
                 * will not arrive, and the switch cannot say that itself.
                 */
                {...(prefs.data.emailVerified
                  ? {}
                  : { detail: 'Your email address is not verified yet.' })}
                accessory={
                  <Switch
                    value={prefs.data.channels.email}
                    onValueChange={(next) => save.mutate({ email: next })}
                    disabled={save.isPending}
                    accessibilityLabel="Email notifications"
                    trackColor={{ false: colors.inputBg, true: colors.primary }}
                    thumbColor={colors.fieldText}
                  />
                }
                last
              />
            </ListCard>
          )}

          {/*
            Security-critical mail goes out whatever these say. A viewer who
            switches email off and then cannot find their password reset has
            been misled by a switch that told them the truth about the wrong
            thing.
          */}
          <View style={styles.caption}>
            <Caption>Security emails are always sent.</Caption>
          </View>
        </View>
      </Disclosure>
    </View>
  );
}

/**
 * The caret, pointing down when closed and up when open.
 *
 * It turns rather than swapping for a second glyph, because the rotation is
 * what says the two states are the same control — and it is the one bit of
 * this section that can run on the native driver, so it stays smooth while
 * the list above it is scrolling.
 */
function Caret({ open }: { open: boolean }) {
  const reduced = useReducedMotion();
  /* `useState` with an initialiser rather than a ref, which is the idiom
     already in `PlayerControls` and the one the lint allows: an Animated.Value
     is read during render to build the transform. */
  const [turn] = useState(() => new Animated.Value(open ? 1 : 0));

  useEffect(() => {
    if (reduced) {
      turn.setValue(open ? 1 : 0);
      return;
    }

    /* From wherever it actually is, not from the opposite end: a caret caught
       mid-turn by a second press continues rather than snapping back first. */
    Animated.timing(turn, {
      toValue: open ? 1 : 0,
      duration: 180,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: true,
    }).start();
  }, [open, reduced, turn]);

  const rotate = turn.interpolate({ inputRange: [0, 1], outputRange: ['0deg', '180deg'] });

  return (
    <Animated.View style={{ transform: [{ rotate }] }}>
      <CaretDown size={list.chevronSize} color={list.chevronColor} weight="bold" />
    </Animated.View>
  );
}

/**
 * A section that grows to its own height and clips what is not showing yet.
 *
 * The height is **measured, never guessed**, and the measuring child is
 * **absolutely positioned** — which is the whole trick and it is not a style
 * preference. A child laid out normally inside a parent whose height is
 * animated to 0 reports 0 from `onLayout`, so the section opened to nothing:
 * the caret turned, the state flipped, and the body stayed shut. Rendered on
 * the emulator, that looked exactly like a press that did not register.
 *
 * An absolute child takes its width from `left`/`right` and its height from
 * its own content, so it measures at full size however short the clipped
 * parent currently is. It re-measures when the content changes, which it does
 * here — spinner, then two rows, then two rows plus an error.
 *
 * Height cannot run on the native driver, and the honest response to that is
 * to keep the movement short rather than to pretend otherwise. Under reduced
 * motion it does not travel at all; it still opens, which is the gentler
 * equivalent `apple-design` asks for rather than no feedback.
 *
 * **Closed means closed for a screen reader too.** Clipping hides the content
 * visually and would leave it in the accessibility tree, so somebody swiping
 * through the inbox would meet two switches that are not on screen.
 */
function Disclosure({ open, children }: { open: boolean; children: React.ReactNode }) {
  const reduced = useReducedMotion();
  const [content, setContent] = useState(0);
  const [height] = useState(() => new Animated.Value(0));

  useEffect(() => {
    const target = open ? content : 0;

    /* Nothing measured yet, or the viewer asked for no motion: jump. There is
       nothing to animate from in the first case and nothing to animate in the
       second. */
    if (reduced || content === 0) {
      height.setValue(target);
      return;
    }

    Animated.timing(height, {
      toValue: target,
      duration: 200,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: false,
    }).start();
  }, [open, content, reduced, height]);

  return (
    <Animated.View
      style={[styles.clip, { height }]}
      pointerEvents={open ? 'auto' : 'none'}
      accessibilityElementsHidden={!open}
      importantForAccessibility={open ? 'auto' : 'no-hide-descendants'}
    >
      <View
        style={styles.measured}
        onLayout={(event) => setContent(event.nativeEvent.layout.height)}
      >
        {children}
      </View>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  wrap: { paddingHorizontal: spacing.lg, paddingTop: spacing.md },
  clip: { overflow: 'hidden' },
  /* Absolute so it measures at its natural height rather than at the clipped
     parent's. See `Disclosure`. */
  measured: { position: 'absolute', left: 0, right: 0, top: 0 },
  body: { paddingTop: spacing.sm, gap: spacing.sm },
  loading: { paddingVertical: spacing.lg, alignItems: 'center' },
  caption: { paddingTop: spacing.xs },
});
