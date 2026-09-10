import { useCallback, useRef, useState } from 'react';
import { ActivityIndicator, Modal, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { useMutation } from '@tanstack/react-query';
import { LinearGradient } from 'expo-linear-gradient';
import { CheckCircle, WarningCircle, X } from 'phosphor-react-native';

import { api } from '../../api/jambo';
import { Focusable } from '../../ui/rails/Focusable';
import { colors, fonts, referrals as t, spacing } from '../../ui/theme';

/**
 * Changing your referral code.
 *
 * **The website's own control, translated.** `refer.blade.php` renders a text
 * input beside a Save button and checks availability as you type — debounced
 * at 350ms against `/referrals/check-code`, with Save disabled while the
 * current value is known-taken and a status line saying why. The app had no
 * equivalent at all, so its only way to learn a code was taken was to submit
 * and read a 422. This is that control, at the app's own sizes.
 *
 * The three behaviours from the site's script that are easy to lose and are
 * kept here deliberately:
 *
 *  - **The debounce**, so typing "jambofilms" is one request rather than ten.
 *  - **The stale-response guard.** The site drops an answer whose code no
 *    longer matches what is in the box; without it a slow "taken" for `jam`
 *    lands after a fast "available" for `jambo` and disables Save on a code
 *    that is free.
 *  - **A network failure does not block saving.** The server re-validates on
 *    submit, so a check that could not run must not stop a viewer who is
 *    right.
 *
 * **It seeds its own state and is mounted only while open**, rather than
 * living permanently behind a `visible` flag and re-seeding in an effect. That
 * shape is a known input-eating bug in this app: state written from an effect
 * lands a frame after a keystroke and swallows it, and Jest never sees it
 * because nothing is typing. Mounting fresh makes `useState(currentCode)` the
 * whole of the reset.
 */
export function EditCodeSheet({
  currentCode,
  onClose,
  onSaved,
}: {
  currentCode: string;
  onClose: () => void;
  onSaved: (code: string) => void;
}) {
  const [value, setValue] = useState(currentCode);
  const [status, setStatus] = useState<{ available: boolean; message: string } | null>(null);
  const [checking, setChecking] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
  /** What the box held when the in-flight check was sent. */
  const inFlightFor = useRef<string>('');

  const runCheck = useCallback(
    (next: string) => {
      if (timer.current !== null) clearTimeout(timer.current);

      const trimmed = next.trim();

      // Unchanged, or empty: nothing to check and nothing to say.
      if (trimmed === '' || trimmed === currentCode) {
        setStatus(null);
        setChecking(false);
        return;
      }

      setChecking(true);
      timer.current = setTimeout(() => {
        inFlightFor.current = trimmed;

        api
          .checkReferralCode(trimmed)
          .then((answer) => {
            // The site's stale guard: ignore an answer for a code the box no
            // longer holds.
            if (inFlightFor.current !== trimmed) return;
            setStatus(answer);
          })
          .catch(() => {
            // A check that could not run must not block a viewer who is
            // right; the server validates again on save.
            if (inFlightFor.current !== trimmed) return;
            setStatus(null);
          })
          .finally(() => {
            if (inFlightFor.current === trimmed) setChecking(false);
          });
      }, 350);
    },
    [currentCode],
  );

  const save = useMutation({
    mutationFn: (code: string) => api.updateReferralCode(code),
    onSuccess: (saved) => {
      onSaved(saved ?? value.trim());
      onClose();
    },
    onError: (caught: unknown) => {
      // The server's own words. It knows which of the several rules failed and
      // rewording it here would lose that.
      setSaveError(
        caught instanceof Error && caught.message !== ''
          ? caught.message
          : 'We could not save that code.',
      );
    },
  });

  const trimmed = value.trim();
  const unchanged = trimmed === currentCode;
  const known = status !== null && !status.available;
  const blocked = trimmed === '' || unchanged || known || checking || save.isPending;

  return (
    <Modal visible transparent animationType="slide" onRequestClose={onClose}>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Close"
        style={styles.scrim}
        onPress={onClose}
      />

      <View style={styles.sheet}>
        <View style={styles.head}>
          <Text accessibilityRole="header" style={styles.title}>
            Your referral code
          </Text>
          <Focusable accessibilityLabel="Close" ringRadius={18} onPress={onClose} style={styles.close}>
            <X size={20} color={colors.text} weight="bold" />
          </Focusable>
        </View>

        <TextInput
          value={value}
          onChangeText={(next) => {
            setValue(next);
            setSaveError(null);
            runCheck(next);
          }}
          // A real label, not a placeholder: a placeholder disappears the
          // moment somebody types and a screen reader never sees it.
          accessibilityLabel="Referral code"
          autoCapitalize="none"
          autoCorrect={false}
          maxLength={50}
          returnKeyType="done"
          onSubmitEditing={() => {
            if (!blocked) save.mutate(trimmed);
          }}
          placeholder={currentCode}
          placeholderTextColor={colors.placeholder}
          style={styles.input}
        />

        {/*
          One status line, and it is announced as well as shown — the whole
          point of a live check is lost on somebody who cannot see the colour.
        */}
        <View style={styles.statusRow} accessibilityLiveRegion="polite">
          {checking ? (
            <>
              <ActivityIndicator size="small" color={t.labelColor} />
              <Text style={styles.checking}>Checking…</Text>
            </>
          ) : saveError !== null ? (
            <>
              <WarningCircle size={16} color={colors.errorText} weight="fill" />
              <Text style={styles.bad}>{saveError}</Text>
            </>
          ) : status === null ? (
            <Text style={styles.hint}>Letters, numbers, dots, dashes or underscores.</Text>
          ) : status.available ? (
            <>
              <CheckCircle size={16} color={t.copiedColor} weight="fill" />
              <Text style={styles.good}>{status.message}</Text>
            </>
          ) : (
            <>
              <WarningCircle size={16} color={colors.errorText} weight="fill" />
              <Text style={styles.bad}>{status.message}</Text>
            </>
          )}
        </View>

        <Focusable
          accessibilityRole="button"
          accessibilityLabel="Save code"
          accessibilityState={{ disabled: blocked, busy: save.isPending }}
          disabled={blocked}
          ringRadius={t.copyButtonRadius}
          onPress={() => save.mutate(trimmed)}
          style={[styles.save, blocked ? styles.saveOff : null]}
        >
          <LinearGradient
            colors={[t.copyFrom, t.copyTo]}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={StyleSheet.absoluteFill}
          />
          <Text style={styles.saveText}>{save.isPending ? 'Saving…' : 'Save'}</Text>
        </Focusable>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  scrim: { position: 'absolute', top: 0, right: 0, bottom: 0, left: 0, backgroundColor: t.scrim },
  sheet: {
    marginTop: 'auto',
    backgroundColor: t.sheetBg,
    borderTopLeftRadius: t.sheetRadius,
    borderTopRightRadius: t.sheetRadius,
    padding: spacing.xl,
    gap: spacing.md,
  },
  head: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  title: { fontFamily: fonts.medium, fontSize: 16, color: colors.text },
  close: { padding: spacing.xs },

  input: {
    height: 52,
    borderRadius: 12,
    paddingHorizontal: spacing.lg,
    backgroundColor: t.copyIconBg,
    borderWidth: 1,
    borderColor: t.copyIconBorder,
    fontFamily: fonts.medium,
    fontSize: 18,
    letterSpacing: 1,
    color: colors.fieldText,
  },

  statusRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, minHeight: 20 },
  checking: { fontFamily: fonts.regular, fontSize: 12, color: t.labelColor },
  hint: { fontFamily: fonts.regular, fontSize: 12, color: t.labelColor },
  good: { flex: 1, fontFamily: fonts.regular, fontSize: 12, color: t.copiedColor },
  bad: { flex: 1, fontFamily: fonts.regular, fontSize: 12, color: colors.errorText },

  save: {
    height: 48,
    borderRadius: t.copyButtonRadius,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
    marginTop: spacing.sm,
  },
  saveOff: { opacity: 0.45 },
  saveText: { fontFamily: fonts.medium, fontSize: 15, color: colors.onPrimary },
});
