import { useCallback, useRef, useState } from 'react';
import { Modal, Pressable, StyleSheet, Text, useWindowDimensions, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Focusable } from './rails/Focusable';
import { colors, dialog as d, fonts, sheet as s, spacing } from './theme';

/**
 * Every overlay in this app: the bottom sheet, and the confirmation.
 *
 * **This file exists because Rio saw an Android system dialog.** Signing a
 * device out opened `Alert.alert`, which drew a grey slab with teal capitalised
 * buttons in the middle of an otherwise Jambo-blue product. His instruction,
 * 2026-09-10: *"we need a universal design for our system so that we don't use
 * these default generic design and make sure all the other agents use it when
 * it's needed it should be enforced."*
 *
 * So there are two things here and one rule.
 *
 * **`ConfirmDialog` replaces `Alert.alert` everywhere.** A platform dialog is
 * not neutral: it is a different product's design, it ignores every token this
 * app has, it capitalises its buttons on Android and not on iOS, and it cannot
 * show a destructive action in the app's own red. Nothing in Jambo should look
 * like it came from somewhere else.
 *
 * **`Sheet` replaces six hand-rolled bottom sheets.** The Watchlist's sort
 * sheet, the player's settings menu, the country picker, the referral code
 * editor and the withdrawal form each built their own `Modal`, scrim, handle
 * and radius. The *tokens* were already shared — `theme.sheet` — which is why
 * they still look alike; the markup was not, so the Android back button, the
 * safe-area padding and the scrim's press target were each solved five times
 * and correctly a different number of times.
 *
 * **The rule: nothing outside this file imports `Modal` or `Alert` from
 * `react-native`.** That is a lint error rather than a convention, because a
 * convention is what produced six sheets. See `eslint.config.js`.
 */

/* ── the bottom sheet ─────────────────────────────────────────────────── */

export function Sheet({
  visible,
  onClose,
  title,
  children,
  fullHeight = false,
  dismissOnScrim = true,
}: {
  visible: boolean;
  onClose: () => void;
  /** Optional. A sheet whose contents name themselves needs no heading. */
  title?: string;
  children: React.ReactNode;
  /**
   * Fill the screen instead of hugging the content.
   *
   * Added 2026-09-10 for the payment checkout, which hosts a foreign web
   * document: a gateway's page is a full page and cannot be measured, so a
   * content-height sheet either clips it or collapses. Off by default, so
   * every sheet already written is untouched.
   */
  fullHeight?: boolean;
  /**
   * Whether a tap on the scrim closes it.
   *
   * On by default, because that is what a sheet means. **A payment turns it
   * off**, and the reason is worth stating: the scrim sits under a page where
   * somebody is typing a mobile-money PIN, and a stray tap that abandoned a
   * half-entered payment would be indistinguishable from the app losing it.
   * The X in the header remains, so there is still one obvious way out — the
   * `apple-design` rule that every screen answers "how do I get out" is met by
   * a control, not by an invisible region.
   */
  dismissOnScrim?: boolean;
}) {
  const insets = useSafeAreaInsets();
  const { height } = useWindowDimensions();

  return (
    <Modal
      visible={visible}
      transparent
      animationType="slide"
      // What makes the Android back button dismiss the sheet rather than the
      // screen behind it. Missing from two of the six it replaces.
      onRequestClose={onClose}
      statusBarTranslucent
    >
      {dismissOnScrim ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Close"
          style={styles.scrim}
          onPress={onClose}
        />
      ) : (
        /*
         * Still painted, so the screen behind is dimmed and the sheet reads as
         * a layer — but inert, and hidden from the accessibility tree so it is
         * not announced as a control that does nothing.
         */
        <View style={styles.scrim} accessibilityElementsHidden importantForAccessibility="no" />
      )}

      <View
        style={[
          styles.sheet,
          fullHeight && styles.sheetFull,
          /*
           * A content sheet stops growing before it reaches the top.
           *
           * Without this a sheet simply hugs whatever it is given, so content
           * taller than the display grows off the top and carries its own last
           * rows with it — which is what the invoice did the first time it was
           * converted, putting its footnote at y=2835 on a 2856-tall screen.
           * The child scrolls inside the cap instead.
           *
           * Read from the window rather than fixed, so a tablet and a rotated
           * handset both get a panel rather than a screen. `fullHeight` is
           * exempt: a payment page IS the screen.
           */
          fullHeight ? null : { maxHeight: height * s.maxHeightRatio },
          { paddingBottom: Math.max(spacing.xl, insets.bottom) },
        ]}
      >
        {/*
          Decorative: it says "drag me" to a sighted viewer and nothing to a
          screen reader, which already has the scrim's Close button.
        */}
        <View style={styles.handle} accessibilityElementsHidden importantForAccessibility="no" />

        {title === undefined || title === '' ? null : (
          <Text numberOfLines={1} accessibilityRole="header" style={styles.sheetTitle}>
            {title}
          </Text>
        )}

        {children}
      </View>
    </Modal>
  );
}

/* ── the confirmation ─────────────────────────────────────────────────── */

export type ConfirmRequest = {
  title: string;
  /** One line, and only when it carries a consequence the title cannot. */
  message?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  /** Draws the confirm in the app's red. For anything that cannot be undone. */
  destructive?: boolean;
};

/**
 * `await confirm({...})`, with a dialog that belongs to this app.
 *
 * The shape mirrors what it replaces so a call site reads the same way it did:
 * ask a question, get an answer, act on it. `Alert.alert` took callbacks, which
 * is why the code around it always ended up inside a closure; this resolves a
 * promise instead, so the action stays where it is written.
 *
 * ```tsx
 * const [confirm, confirmDialog] = useConfirm();
 * // ...
 * if (await confirm({ title: 'Sign this device out?', destructive: true })) {
 *   revoke.mutate(id);
 * }
 * // ...and render {confirmDialog} once, anywhere in the screen.
 * ```
 *
 * **Dismissing resolves false**, whether by the scrim, the cancel button or the
 * Android back button. There is no fourth outcome, and a caller that forgets to
 * handle one cannot exist.
 */
export function useConfirm(): [(request: ConfirmRequest) => Promise<boolean>, React.ReactElement] {
  const [request, setRequest] = useState<ConfirmRequest | null>(null);

  /*
   * The resolver for the promise currently in flight.
   *
   * A ref rather than state: settling it must not schedule a render, and it
   * must survive the render that closing the dialog causes. Held as a ref, the
   * answer is delivered exactly once — `resolve` is cleared as it is called, so
   * a scrim press racing a button press cannot resolve twice.
   */
  const pending = useRef<((answer: boolean) => void) | null>(null);

  const settle = useCallback((answer: boolean) => {
    const resolve = pending.current;
    pending.current = null;
    setRequest(null);
    resolve?.(answer);
  }, []);

  const confirm = useCallback((next: ConfirmRequest) => {
    /*
     * A second question while one is open answers the first with false rather
     * than dropping its promise on the floor. An unresolved promise is an
     * `await` that never returns, and the code after it simply never runs —
     * silently, which is the worst way for a confirmation to fail.
     */
    pending.current?.(false);

    return new Promise<boolean>((resolve) => {
      pending.current = resolve;
      setRequest(next);
    });
  }, []);

  const element = (
    <ConfirmDialog request={request} onCancel={() => settle(false)} onConfirm={() => settle(true)} />
  );

  return [confirm, element];
}

function ConfirmDialog({
  request,
  onCancel,
  onConfirm,
}: {
  request: ConfirmRequest | null;
  onCancel: () => void;
  onConfirm: () => void;
}) {
  const destructive = request?.destructive === true;

  return (
    <Modal
      visible={request !== null}
      transparent
      animationType="fade"
      onRequestClose={onCancel}
      statusBarTranslucent
    >
      {/*
        **Not announced, unlike the sheet's scrim, and the difference is the
        Cancel button.**

        This carried the same `accessibilityLabel` as the button below it, so a
        screen reader offered "Cancel, button" twice — once as a full-screen
        invisible region and once as the real control. A viewer who swiped to
        the first one had no way to tell it apart from the second. The sheet's
        scrim keeps its label because a sheet may have no dismiss button at
        all; a dialog always has one, and the back button dismisses it too.
      */}
      <Pressable
        style={styles.scrim}
        onPress={onCancel}
        accessibilityElementsHidden
        importantForAccessibility="no"
      />

      <View style={styles.centre} pointerEvents="box-none">
        <View
          style={styles.dialog}
          // One announcement rather than four, and it names the question
          // before the buttons, which is the order a screen reader user needs.
          accessible
          accessibilityViewIsModal
          accessibilityLabel={[request?.title, request?.message]
            .filter((part): part is string => typeof part === 'string' && part !== '')
            // The stop is added between the parts, so a title that already
            // ends in one must not bring its own — this read "Delete all
            // notifications?. This cannot be undone" on the emulator.
            .map((part) => part.replace(/[.!?]+$/, ''))
            .join('. ')}
        >
          <Text accessibilityRole="header" style={styles.title}>
            {request?.title ?? ''}
          </Text>

          {request?.message === undefined || request.message === '' ? null : (
            <Text style={styles.message}>{request.message}</Text>
          )}

          <View style={styles.actions}>
            <Focusable
              accessibilityRole="button"
              accessibilityLabel={request?.cancelLabel ?? 'Cancel'}
              ringRadius={d.buttonRadius}
              onPress={onCancel}
              style={[styles.button, styles.cancel]}
            >
              <Text style={styles.cancelText}>{request?.cancelLabel ?? 'Cancel'}</Text>
            </Focusable>

            <Focusable
              accessibilityRole="button"
              accessibilityLabel={request?.confirmLabel ?? 'Confirm'}
              ringRadius={d.buttonRadius}
              onPress={onConfirm}
              style={[styles.button, destructive ? styles.destructive : styles.confirm]}
            >
              <Text style={destructive ? styles.destructiveText : styles.confirmText}>
                {request?.confirmLabel ?? 'Confirm'}
              </Text>
            </Focusable>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  scrim: { position: 'absolute', top: 0, right: 0, bottom: 0, left: 0, backgroundColor: s.scrim },

  sheet: {
    marginTop: 'auto',
    backgroundColor: s.bg,
    borderTopLeftRadius: s.radius,
    borderTopRightRadius: s.radius,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
  },
  /*
   * Fills what the scrim leaves. `flex: 1` rather than a height, so the
   * keyboard shrinking the window shrinks the sheet with it — a fixed height
   * would push a gateway's PIN field under the keyboard.
   */
  sheetFull: { flex: 1, marginTop: 0 },
  handle: {
    alignSelf: 'center',
    width: 38,
    height: 4,
    borderRadius: 2,
    backgroundColor: s.handle,
    marginBottom: spacing.lg,
  },
  sheetTitle: {
    fontFamily: fonts.medium,
    fontSize: 13,
    color: colors.textMuted,
    marginBottom: spacing.sm,
  },

  centre: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: spacing.xl },
  dialog: {
    width: '100%',
    maxWidth: d.maxWidth,
    backgroundColor: s.bg,
    borderRadius: d.radius,
    borderWidth: 1,
    borderColor: d.border,
    padding: d.padding,
    gap: spacing.sm,
  },
  title: { fontFamily: fonts.bold, fontSize: d.titleSize, color: colors.fieldText },
  message: {
    fontFamily: fonts.regular,
    fontSize: d.messageSize,
    lineHeight: d.messageSize + 6,
    color: colors.textMuted,
  },

  actions: { flexDirection: 'row', justifyContent: 'flex-end', gap: spacing.sm, marginTop: spacing.md },
  button: {
    minHeight: d.buttonHeight,
    minWidth: 96,
    paddingHorizontal: spacing.lg,
    borderRadius: d.buttonRadius,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cancel: { borderWidth: 1, borderColor: d.border },
  cancelText: { fontFamily: fonts.medium, fontSize: d.buttonTextSize, color: colors.text },
  confirm: { backgroundColor: colors.primary },
  confirmText: { fontFamily: fonts.medium, fontSize: d.buttonTextSize, color: colors.onPrimary },
  destructive: { backgroundColor: d.destructiveBg },
  destructiveText: { fontFamily: fonts.medium, fontSize: d.buttonTextSize, color: d.destructiveFg },
});
