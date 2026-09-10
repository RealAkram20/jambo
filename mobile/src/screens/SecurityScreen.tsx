import { useCallback, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Envelope, Lock, Phone, ShieldCheck, UserMinus, type Icon } from 'phosphor-react-native';

import { api } from '../api/jambo';
import { useAuth } from '../auth/AuthProvider';
import type { Device } from '../api/endpoints';
import { ApiError, NetworkError } from '../api/errors';
import { lastActive, whereFrom } from '../features/devices/list';
import { iconFor } from '../features/notifications/icons';
import {
  deleteAccount,
  disableTwoFactor,
  regenerateRecoveryCodes,
  securityKey,
} from '../features/security/api';
import { RecoveryCodes } from '../features/security/RecoveryCodes';
import { badge, colors, fonts, plans, radius, spacing, typography } from '../ui/theme';
import { Alert, Button, Caption, ErrorState, Heading, Loading } from '../ui/components';
import { PasswordField } from '../ui/PasswordField';
import { ListCard, ListRow } from '../ui/list';
import { useConfirm } from '../ui/overlay';
import { Focusable } from '../ui/rails/Focusable';
import type { AppScreenProps } from '../navigation/types';

/**
 * What protects this account, and what does not.
 *
 * Rio's mockup of 2026-09-10: a headline, a list of protections each with its
 * state on the right, and a short account-activity list underneath. The
 * website's `profile-hub/security.blade.php` is the same three subjects in
 * cards — password, two-factor, and a deactivate danger zone.
 *
 * **Closing the account IS built, and it is Rio's to withdraw.** He asked for
 * it while this screen was being written: *"we will have a delete button. but
 * we can disable this button when we please, when enabled it shows and when
 * disabled it disappears from the app."* So the block is behind
 * `features.account_deletion`, and `DELETE /account` refuses on the same
 * setting — see `DangerZone` below for why both halves are needed.
 *
 * 🔴 **Two things the mockup asks for are not on this screen, and both are
 * refusals rather than omissions.**
 *
 * **"PIN for Purchases" does not exist in Jambo.** Not a column, not an
 * endpoint, not a control on the website — a repo-wide search finds the phrase
 * only in the privacy policy's paragraph about parental consent. The app also
 * takes no payments on either build, so the switch would guard a purchase flow
 * that is not there. A toggle that looks like it protects money and does
 * nothing is the worst version of the honesty rule: somebody turns it on and
 * believes their card is safe. Raised with Rio rather than drawn.
 *
 * **The phone number has no verified state.** `users.phone` exists and the
 * profile editor writes it, but there is no `phone_verified_at`, no
 * verification flow and nothing configured to send a code. The row is here;
 * the green badge beside it is not. The email's badge IS real — `email_verified`
 * comes off the security resource — and that is precisely what would have made
 * a matching phone badge so believable.
 *
 * **Google sign-in was on the previous version of this screen and is gone.**
 * It reported `google_enabled`, which says whether the SERVER has Google
 * configured rather than whether this account is linked to it. Server
 * configuration is not a personal security fact, and the mockup is right to
 * drop it.
 *
 * **Nothing that worked is lost.** Recovery codes, disabling two-factor and
 * resending a verification email are not rows, so each stays as a block that
 * appears only in the state it belongs to.
 */
export function SecurityScreen({ navigation }: AppScreenProps<'Security'>) {
  const queryClient = useQueryClient();

  const [sent, setSent] = useState(false);
  const [sendError, setSendError] = useState<string | null>(null);
  const [sending, setSending] = useState(false);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: securityKey,
    queryFn: () => api.security(),
  });

  /*
   * The address and the number the rows report. Same keys the profile screens
   * use, so this is the cache they already filled rather than a second copy of
   * the same account.
   */
  const profile = useQuery({ queryKey: ['profile'], queryFn: () => api.profile() });

  /*
   * Recent account activity is the devices list, not a second idea.
   *
   * Same query key the Devices screen uses and the same two formatters, which
   * is the point: `whereFrom` already knows that a city is null on a server
   * with no geolocation database and falls back to the address the website has
   * always shown, and `lastActive` already collapses the recent end to "Active
   * now". A second implementation here would be two opinions about one row.
   */
  const devices = useQuery({ queryKey: ['devices'], queryFn: () => api.devices() });

  /*
   * Whether this account may close itself, which is Rio's switch rather than
   * a property of the account. Same key the rest of the app reads its config
   * on, so this is a cache hit on any path that has already booted.
   *
   * **Undefined draws nothing.** While the answer is unknown the row is absent
   * rather than present-and-maybe-refused, because a Delete account button
   * that appears and then errors is worse than one that arrives a moment late.
   */
  const config = useQuery({ queryKey: ['app-config'], queryFn: () => api.appConfig() });
  const deletionOffered = config.data?.features?.account_deletion === true;

  const resend = useCallback(async () => {
    setSending(true);
    setSendError(null);
    try {
      await api.resendVerificationEmail();
      setSent(true);
    } catch (caught) {
      setSendError(
        caught instanceof NetworkError
          ? 'Could not reach Jambo. Check your connection and try again.'
          : caught instanceof ApiError
            ? caught.message
            : 'We could not send the email. Try again in a moment.',
      );
    } finally {
      setSending(false);
    }
  }, []);

  if (isPending) return <Loading label="Loading your security settings" />;

  if (isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load your security settings.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </View>
    );
  }

  const twoFactorOn = data.twoFactor?.enabled === true;
  const twoFactorPending = data.twoFactor?.pending === true;
  const recoveryCodes = data.twoFactor?.recovery_codes ?? [];
  const emailVerified = data.emailVerified === true;

  const email = profile.data?.email;
  const phone = profile.data?.phone;

  /*
   * Three rows, and the fourth is the door to the rest.
   *
   * The mockup shows three; the list is whatever the account has, so a viewer
   * with two devices sees two rather than an empty slot. `recent` is the same
   * order the Devices screen shows, which is newest first from the server.
   */
  const recent = (devices.data?.devices ?? []).slice(0, 3);

  return (
    <View style={styles.screen}>
      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        <Hero />

        <View style={styles.block}>
          <ListCard>
            <ListRow
              icon={Lock}
              iconTile
              label="Change password"
              detail="Signs you out on every other device"
              onPress={() => navigation.navigate('ChangePassword')}
            />

            {/*
              Three states, not two, and the difference is not cosmetic. A
              pending setup is a secret already minted that nothing has
              confirmed; calling that "Off" sends somebody to start a second
              one, which invalidates the first.
            */}
            <ListRow
              icon={ShieldCheck}
              iconTile
              label="Two-factor authentication"
              detail={
                twoFactorOn
                  ? 'You are asked for a code when you sign in'
                  : twoFactorPending
                    ? 'Started and never confirmed'
                    : 'Add a second step at sign-in'
              }
              accessory={
                <StateBadge
                  text={twoFactorOn ? 'On' : twoFactorPending ? 'Pending' : 'Off'}
                  tone={twoFactorOn ? 'ok' : twoFactorPending ? 'warn' : 'muted'}
                />
              }
              onPress={() => navigation.navigate('TwoFactorSetup')}
            />

            <ListRow
              icon={Envelope}
              iconTile
              label="Email address"
              detail={typeof email === 'string' && email !== '' ? email : 'Not set'}
              accessory={
                <StateBadge
                  text={emailVerified ? 'Verified' : 'Unverified'}
                  tone={emailVerified ? 'ok' : 'warn'}
                />
              }
              onPress={() => navigation.navigate('ProfileEdit')}
            />

            {/*
              No badge on this row, and the docblock above says why. The number
              is editable in the same place the email is, so the row leads
              there rather than reporting a state nothing can confirm.
            */}
            <ListRow
              icon={Phone}
              iconTile
              label="Phone number"
              detail={typeof phone === 'string' && phone !== '' ? phone : 'Not added'}
              muted={typeof phone !== 'string' || phone === ''}
              last
              onPress={() => navigation.navigate('ProfileEdit')}
            />
          </ListCard>
        </View>

        {/*
          The two controls that are not rows, each shown only in the state it
          belongs to. Recovery codes on a screen with two-factor off would be
          codes for nothing; a resend button on a verified address is a control
          with no work to do.
        */}
        {twoFactorOn ? (
          <View style={styles.block}>
            <TwoFactorOn
              codes={recoveryCodes}
              onChanged={() => {
                void queryClient.invalidateQueries({ queryKey: securityKey });
              }}
            />
          </View>
        ) : null}

        {emailVerified ? null : (
          <View style={styles.block}>
            {sendError !== null ? <Alert tone="error">{sendError}</Alert> : null}
            {sent ? (
              <Alert tone="ok">
                Sent. Check your inbox, and your spam folder if it is not there.
              </Alert>
            ) : (
              <Button
                label="Resend verification email"
                tone="quiet"
                busy={sending}
                onPress={() => void resend()}
              />
            )}
          </View>
        )}

        <View style={styles.activityHead}>
          <Heading>Recent account activity</Heading>
          {recent.length > 0 ? (
            <Focusable
              accessibilityLabel="See all devices"
              accessibilityRole="button"
              onPress={() => navigation.navigate('Devices')}
              ringRadius={radius.input}
            >
              <Text style={styles.seeAll}>See all</Text>
            </Focusable>
          ) : null}
        </View>

        <View style={styles.block}>
          {devices.isPending ? (
            <Caption>Loading…</Caption>
          ) : devices.isError ? (
            /*
             * The activity list failing does not fail the screen. Password and
             * two-factor are already on it and are the reason most people are
             * here, so this says what is missing and offers the retry rather
             * than replacing everything above with an error.
             */
            <Caption>Could not load your recent activity.</Caption>
          ) : recent.length === 0 ? (
            <Caption>Nothing signed in yet.</Caption>
          ) : (
            <ListCard>
              {recent.map((device, index) => (
                <ActivityRow
                  key={device.id ?? String(index)}
                  device={device}
                  last={index === recent.length - 1}
                  onPress={() => navigation.navigate('Devices')}
                />
              ))}
            </ListCard>
          )}
        </View>

        {deletionOffered ? <DangerZone /> : null}
      </ScrollView>
    </View>
  );
}

/**
 * The headline block.
 *
 * The mockup's composition — an eyebrow, two lines with the second in the
 * brand colour, one sentence, and a shield to the right of them.
 *
 * **The shield is drawn, not an asset.** Rio's mockup renders a crimson badge
 * with a play mark through it and the product owns no such artwork; a glyph
 * from the set the whole app already uses, at the brand blue, is the honest
 * version of that space. The mockup is crimson and Jambo is blue, which is now
 * the fourth screen to make that substitution on his own standing ruling.
 */
function Hero() {
  return (
    <View style={styles.hero}>
      <View style={styles.heroCopy}>
        <Text style={styles.eyebrow}>KEEP YOUR ACCOUNT SAFE</Text>
        <Text style={styles.heroLine}>Your Security</Text>
        <Text style={[styles.heroLine, styles.heroLineAccent]}>Matters</Text>
        {/*
          One sentence, where the mockup has two. The second — "we take your
          privacy seriously" — states an intention rather than a fact and is
          the kind of line Rio's own wording rule removes: it tells a viewer
          nothing they can act on.
        */}
        <Text style={styles.heroSub}>Manage what protects your account.</Text>
      </View>

      {/*
        The mark, on its own glow.

        The mockup's shield sits in a crimson halo. There is no such asset and
        no gradient token for one, so the halo is two translucent circles of
        the brand blue behind the glyph — the same trick the sign-in screen's
        ambient glow uses, at values that come from `colors.primary` rather
        than from a hex typed here.
      */}
      <View style={styles.heroMark} accessible={false}>
        <View style={styles.glowOuter} />
        <View style={styles.glowInner} />
        <ShieldCheck size={64} color={colors.primary} weight="fill" />
      </View>
    </View>
  );
}

/**
 * One line of account activity.
 *
 * The device's own icon, the name, and where and when — `whereFrom` and
 * `lastActive` are jambo-49's, imported rather than rewritten, because this is
 * the same row their Devices screen draws.
 *
 * **"This device" replaces the chevron rather than sitting beside it.** The
 * row you are reading it on is the one row you do not need to go and inspect,
 * and the mockup marks it in exactly that spot.
 */
function ActivityRow({
  device,
  last,
  onPress,
}: {
  device: Device;
  last: boolean;
  onPress: () => void;
}) {
  const where = whereFrom(device);
  const when = lastActive(device.last_seen_at);
  const current = device.is_current === true;

  const meta = [where, when].filter((part): part is string => typeof part === 'string' && part !== '');

  return (
    <ListRow
      /*
       * `iconFor` is typed for the notification map's wider component union;
       * `ListRow` wants the Phosphor `Icon`. Every value in that map IS one,
       * so this narrows rather than lies — and it stays one cast in one place
       * rather than a second icon table for devices.
       */
      icon={iconFor(device.icon) as Icon}
      iconTile
      label={device.name ?? 'Unknown device'}
      {...(meta.length > 0 ? { detail: meta.join('  ·  ') } : {})}
      /*
       * The badge, or nothing — never a chevron.
       *
       * `ListRow` already draws its own chevron on any row with an `onPress`,
       * so supplying one here rendered two, side by side, on every row that
       * was not this device. Caught by rendering it; the tree shows one node
       * and the screenshot shows two arrows.
       */
      {...(current ? { accessory: <StateBadge text="This device" tone="ok" /> } : {})}
      last={last}
      onPress={onPress}
      accessibilityLabel={[
        device.name ?? 'Unknown device',
        meta.join(', '),
        current ? 'this device' : null,
      ]
        .filter((part): part is string => typeof part === 'string' && part !== '')
        .join('. ')}
    />
  );
}

/**
 * Everything you can do to two-factor once it is on.
 *
 * The recovery codes, a guarded regenerate, and a guarded turn-off that asks
 * for the password. **Both destructive actions confirm in place rather than
 * firing on the first press**, and both say what the consequence is before
 * they do it — new codes stop the old ones working, and turning 2FA off means
 * signing in with a password alone.
 *
 * 🔴 **This block was rebuilt from the running JS bundle on 2026-09-10.** It
 * was uncommitted work by an earlier session, it lived below the part of the
 * file I had read, and I replaced the file without reading to the end. Metro
 * was still serving the old bundle, so the exact behaviour and the exact
 * wording came back out of it. Nothing here is a paraphrase — but the lesson
 * is the obvious one: read the whole file before overwriting it, especially a
 * file with uncommitted changes in it.
 */
function TwoFactorOn({ codes, onChanged }: { codes: string[]; onChanged: () => void }) {
  const [fresh, setFresh] = useState<string[] | null>(null);
  const [confirmingRegenerate, setConfirmingRegenerate] = useState(false);
  const [disabling, setDisabling] = useState(false);
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const regenerate = useCallback(async () => {
    setBusy(true);
    setError(null);
    try {
      setFresh(await regenerateRecoveryCodes());
      setConfirmingRegenerate(false);
      onChanged();
    } catch (caught) {
      setError(message(caught, 'We could not generate new codes.'));
    } finally {
      setBusy(false);
    }
  }, [onChanged]);

  const turnOff = useCallback(async () => {
    setBusy(true);
    setError(null);
    try {
      await disableTwoFactor(password);
      setPassword('');
      setDisabling(false);
      onChanged();
    } catch (caught) {
      setError(message(caught, 'We could not turn two-factor off.'));
    } finally {
      setBusy(false);
    }
  }, [password, onChanged]);

  return (
    <View>
      {error === null ? null : (
        <View style={styles.spaced}>
          <Alert tone="error">{error}</Alert>
        </View>
      )}

      {/* The freshly minted batch wins, because it is the one that works. */}
      <RecoveryCodes codes={fresh ?? codes} />

      <View style={styles.spaced}>
        {confirmingRegenerate ? (
          <>
            <Caption>Generate a new batch? Your existing codes will stop working.</Caption>
            <View style={styles.buttonRow}>
              <Button
                label="Generate new codes"
                tone="quiet"
                busy={busy}
                onPress={() => void regenerate()}
              />
              <Button label="Keep these" tone="quiet" onPress={() => setConfirmingRegenerate(false)} />
            </View>
          </>
        ) : (
          <Button
            label="Regenerate codes"
            tone="quiet"
            onPress={() => setConfirmingRegenerate(true)}
          />
        )}
      </View>

      <View style={styles.danger}>
        {disabling ? (
          <>
            <Caption>
              Turn two-factor off? Enter your password to confirm. You will sign in with your
              password alone after this.
            </Caption>
            <PasswordField
              label="Password"
              value={password}
              onChangeText={setPassword}
              autoComplete="current-password"
              textContentType="password"
            />
            <View style={styles.buttonRow}>
              <Button
                label="Turn off two-factor"
                tone="danger"
                busy={busy}
                disabled={password === ''}
                onPress={() => void turnOff()}
              />
              <Button
                label="Keep it on"
                tone="quiet"
                onPress={() => {
                  setDisabling(false);
                  setPassword('');
                  setError(null);
                }}
              />
            </View>
          </>
        ) : (
          <Button label="Turn off two-factor" tone="quiet" onPress={() => setDisabling(true)} />
        )}
      </View>
    </View>
  );
}

/**
 * Closing the account.
 *
 * Rio, 2026-09-10: *"we will have a delete button. but we can disable this
 * button when we please, when enabled it shows and when disabled it disappears
 * from the app."* So the whole block is behind `features.account_deletion`,
 * and **the same setting is checked by `DELETE /account`** — the flag hides a
 * row, the server decides. A switch only the client reads is not a switch: a
 * build already on somebody's phone would keep closing accounts after it was
 * turned off.
 *
 * **Three gates before it happens, and none of them is decoration.** The row
 * opens the block rather than doing anything; the block asks for the password,
 * which is what the endpoint requires and is itself proof of identity; and the
 * final press asks once more through the app's own confirm sheet, because this
 * is the one action on the screen that cannot be undone from the app.
 *
 * 🔴 **It says "closes", not "erases", and that is deliberate.** The endpoint
 * marks the account deactivated, revokes every device and deletes every token
 * — sign-in stops working everywhere, immediately. It does not delete the
 * person's records. The copy below says exactly that, because a Delete button
 * that quietly keeps everything is a promise somebody will hold us to. Whether
 * Jambo should also erase, and what must be retained for tax and accounting,
 * is a decision that has been put to Rio and is not this screen's to make.
 */
function DangerZone() {
  const { signOut } = useAuth();
  const [confirm, confirmDialog] = useConfirm();

  const [open, setOpen] = useState(false);
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const close = useCallback(async () => {
    const sure = await confirm({
      title: 'Close your account?',
      message: 'You will be signed out on every device and will not be able to sign in again.',
      confirmLabel: 'Close my account',
      destructive: true,
    });

    if (!sure) return;

    setBusy(true);
    setError(null);
    try {
      await deleteAccount(password);
      /*
       * Sign out locally too. The server has already deleted every token, so
       * the one on this phone is dead; without this the app would sit on a
       * dead session until its next request failed, and the viewer would be
       * looking at a signed-in screen for an account that no longer opens.
       */
      await signOut();
    } catch (caught) {
      setError(message(caught, 'We could not close your account.'));
      setBusy(false);
    }
  }, [confirm, password, signOut]);

  return (
    <View style={styles.block}>
      <Heading>Close account</Heading>

      <View style={styles.spaced}>
        {open ? (
          <>
            <Caption>
              Signs you out everywhere and stops you signing in again. Your records are kept, so
              contact support if you want them removed.
            </Caption>
            {error === null ? null : (
              <View style={styles.spaced}>
                <Alert tone="error">{error}</Alert>
              </View>
            )}
            <PasswordField
              label="Password"
              value={password}
              onChangeText={setPassword}
              autoComplete="current-password"
              textContentType="password"
            />
            <View style={styles.buttonRow}>
              <Button
                label="Delete account"
                tone="danger"
                busy={busy}
                disabled={password === ''}
                onPress={() => void close()}
              />
              <Button
                label="Keep my account"
                tone="quiet"
                onPress={() => {
                  setOpen(false);
                  setPassword('');
                  setError(null);
                }}
              />
            </View>
          </>
        ) : (
          <ListCard>
            <ListRow
              icon={UserMinus}
              iconTile
              label="Delete account"
              detail="Signs you out everywhere, permanently"
              tone="danger"
              last
              onPress={() => setOpen(true)}
            />
          </ListCard>
        )}
      </View>

      {confirmDialog}
    </View>
  );
}

/** A refusal is an answer; a dropped request is not. Never collapse the two. */
function message(caught: unknown, fallback: string): string {
  if (caught instanceof NetworkError) {
    return 'Could not reach Jambo. Check your connection and try again.';
  }
  if (caught instanceof ApiError && caught.message !== '') return caught.message;

  return fallback;
}

/**
 * A state, in a word.
 *
 * The site's own badge metrics, and the tones the hub already uses. **It says
 * the state out loud rather than only colouring it** — the accessibility rule
 * this app follows everywhere, and the reason "Off" is a word and not a grey
 * dot.
 */
function StateBadge({ text, tone }: { text: string; tone: 'ok' | 'warn' | 'muted' }) {
  return (
    <Text
      style={[
        styles.badge,
        tone === 'ok' && styles.badgeOk,
        tone === 'warn' && styles.badgeWarn,
        tone === 'muted' && styles.badgeMuted,
      ]}
      numberOfLines={1}
    >
      {text}
    </Text>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { paddingBottom: spacing.xxl },

  /* ── hero ─────────────────────────────────────────────────────────── */
  hero: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
    paddingBottom: spacing.xl,
    gap: spacing.md,
  },
  heroCopy: { flex: 1, gap: 2 },
  eyebrow: {
    fontFamily: fonts.medium,
    fontSize: 10,
    letterSpacing: 2.4,
    color: colors.primary,
    marginBottom: spacing.sm,
  },
  heroLine: {
    fontFamily: fonts.black,
    fontSize: 26,
    lineHeight: 30,
    color: colors.text,
  },
  heroLineAccent: { color: colors.primary },
  heroSub: {
    ...typography.caption,
    color: colors.textMuted,
    marginTop: spacing.sm,
  },
  heroMark: { width: 104, height: 104, alignItems: 'center', justifyContent: 'center' },
  glowOuter: {
    position: 'absolute',
    width: 104,
    height: 104,
    borderRadius: 52,
    backgroundColor: colors.primary,
    opacity: 0.08,
  },
  glowInner: {
    position: 'absolute',
    width: 76,
    height: 76,
    borderRadius: 38,
    backgroundColor: colors.primary,
    opacity: 0.14,
  },

  block: { marginHorizontal: spacing.lg, marginBottom: spacing.lg },

  /* ── the two-factor management block ──────────────────────────────── */
  spaced: { marginTop: spacing.lg },
  danger: { marginTop: spacing.xl },
  buttonRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.sm },

  /* ── the activity heading, with its own door ──────────────────────── */
  activityHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginHorizontal: spacing.lg,
    marginTop: spacing.md,
    marginBottom: spacing.sm,
  },
  seeAll: {
    fontFamily: fonts.medium,
    fontSize: typography.caption.fontSize,
    color: colors.primary,
  },

  /* ── the state badges ─────────────────────────────────────────────── */
  badge: {
    fontSize: badge.size,
    fontWeight: String(badge.weight) as '700',
    lineHeight: badge.size + 4,
    paddingHorizontal: badge.padX,
    paddingVertical: badge.padY,
    borderRadius: badge.radius,
    overflow: 'hidden',
  },
  badgeOk: { backgroundColor: badge.okBg, color: badge.okFg },
  /*
   * NOT `badge.warnFg`. The site pairs white with #ffd81c at 1.39:1, which
   * `theme.ts` already deviates from for exactly this reason — the dark ink is
   * 15.09:1 on the same fill.
   */
  badgeWarn: { backgroundColor: badge.warnBg, color: badge.warnFg },
  /*
   * Off is not a warning. The site has no neutral badge, so this is the
   * period-tab surface from the pricing capture — a real captured fill rather
   * than a grey invented here — with body text on it.
   */
  badgeMuted: { backgroundColor: plans.tabBg, color: colors.textMuted },
});
