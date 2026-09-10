import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Envelope, Lock } from 'phosphor-react-native';

import { ApiError, NetworkError } from '../api/errors';
import { useAuth } from '../auth/AuthProvider';
import { GOOGLE_AVAILABLE, useGoogleSignIn, type GoogleSignInResult } from '../auth/googleSignIn';
import { AuthBackground, PlayWatermark } from '../ui/AuthBackground';
import {
  AuthField,
  Checkbox,
  GoogleMark,
  GradientButton,
  LinkText,
  OrDivider,
  QuietButton,
  RevealToggle,
} from '../ui/authComponents';
import { Alert } from '../ui/components';
import { auth, colors, spacing, typography } from '../ui/theme';
import type { AuthScreenProps } from '../navigation/types';

/**
 * The message a viewer reads, chosen by the server's `code`.
 *
 * Never by message text: `docs/api/openapi.yaml` is explicit that codes are
 * the contract and copy is reworded freely. A screen that switches on text
 * breaks on a day nobody touched the app.
 */
function messageFor(error: unknown): string {
  if (error instanceof NetworkError) {
    return 'Could not reach Jambo. Check your connection and try again.';
  }

  if (error instanceof ApiError) {
    switch (error.code) {
      case 'INVALID_CREDENTIALS':
        return 'That email and password do not match an account.';
      case 'ACCOUNT_DEACTIVATED':
        return 'This account has been deactivated. Contact Jambo to reopen it.';
      case 'RATE_LIMITED': {
        // A 429 from Laravel's throttle carries no envelope, so `message` is a
        // framework string. The seconds are the only useful thing in it.
        const seconds = error.retryAfterSeconds;
        return seconds === null
          ? 'Too many attempts. Wait a moment and try again.'
          : `Too many attempts. Try again in ${seconds} seconds.`;
      }
      case 'VALIDATION_FAILED':
        // Shown under the fields themselves; the banner would repeat it.
        return '';
      default:
        return error.message;
    }
  }

  return 'Something went wrong. Please try again.';
}

export function SignInScreen({ navigation }: AuthScreenProps<'SignIn'>) {
  const { signIn, signInWithGoogle, config } = useAuth();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [reveal, setReveal] = useState(false);
  const [remember, setRemember] = useState(true);
  const [busy, setBusy] = useState(false);
  const [banner, setBanner] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<{ email?: string; password?: string }>({});

  /*
   * Whether this server can complete a Google sign-in at all.
   *
   * From `GET /app/config`, not from a build flag: the same APK runs against a
   * server that has Google configured and one that does not, and a button that
   * can only fail is worse than no button — the viewer cannot tell whether
   * they typed something wrong or the platform is broken. Undefined (config
   * not loaded yet, or an older server) is treated as off, the safe direction.
   *
   * 🔴 **Both halves are required now, and the second half is why this was
   * broken.** The server saying yes was never enough: until 2026-09-11 the
   * button's handler was `navigation.navigate('SignIn')` — it navigated to the
   * screen it was already on, so it did nothing at all, and it was visible on
   * production only because the live server has a client id set.
   * `GOOGLE_AVAILABLE` is the other half: false when this BUILD has no Google
   * client configured, or when the native modules the flow needs are not in
   * the binary — see `googleSignIn.ts`, which crashed the app on this screen
   * before it loaded them by hand.
   */
  const googleEnabled = config?.features?.google_sign_in === true && GOOGLE_AVAILABLE;

  /**
   * Google sign-in, end to end.
   *
   * Two failure modes are handled apart on purpose. A CANCEL is the viewer
   * changing their mind and says nothing — an alert reading "sign-in failed"
   * because somebody pressed back is the app blaming them for a decision. A
   * FAILURE is Google answering with something unusable, and that gets a
   * sentence, because the viewer cannot otherwise tell it from a tap that did
   * not register, which is exactly the confusion this whole change exists to
   * end.
   */
  const onGoogleResult = async (result: GoogleSignInResult) => {
    if (result.kind === 'cancelled') return;

    if (result.kind === 'failed') {
      setBanner('Google did not complete that sign-in. Try again, or use your email.');
      return;
    }

    setBusy(true);
    setBanner(null);
    setFieldErrors({});

    try {
      const outcome = await signInWithGoogle(result.idToken);

      if (outcome.kind === 'two-factor') {
        // A Google account with 2FA on still has 2FA on.
        navigation.navigate('TwoFactor', { challengeToken: outcome.challengeToken });
      }
      // On `signedIn` the provider switches stacks; this screen unmounts.
    } catch (error) {
      setBanner(
        error instanceof ApiError
          ? error.message
          : 'Could not reach Jambo. Check your connection and try again.',
      );
    } finally {
      setBusy(false);
    }
  };

  const submit = async () => {
    if (busy) return;

    setBusy(true);
    setBanner(null);
    setFieldErrors({});

    try {
      const outcome = await signIn(email.trim(), password, { remember });

      if (outcome.kind === 'two-factor') {
        navigation.navigate('TwoFactor', {
          challengeToken: outcome.challengeToken,
          email: email.trim(),
        });
      }
      // On `signedIn` the provider switches stacks; this screen unmounts.
    } catch (error) {
      if (error instanceof ApiError && error.code === 'VALIDATION_FAILED') {
        setFieldErrors({
          ...(error.fieldError('email') !== null ? { email: error.fieldError('email')! } : {}),
          ...(error.fieldError('password') !== null
            ? { password: error.fieldError('password')! }
            : {}),
        });
      }

      const message = messageFor(error);
      if (message !== '') setBanner(message);
    } finally {
      setBusy(false);
    }
  };

  const canSubmit = email.trim() !== '' && password !== '' && !busy;

  return (
    <View style={styles.root}>
      <AuthBackground />

      <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          style={styles.fill}
        >
          <ScrollView
            contentContainerStyle={styles.scroll}
            keyboardShouldPersistTaps="handled"
            showsVerticalScrollIndicator={false}
          >
            <View style={styles.card}>
              <View style={styles.watermark} pointerEvents="none">
                <PlayWatermark />
              </View>

              <Text accessibilityRole="header" style={styles.greeting}>
                Welcome back 👋
              </Text>
              <Text style={styles.subtitle}>Sign in to keep watching.</Text>

              <View style={styles.form}>
                {banner !== null ? <Alert tone="error">{banner}</Alert> : null}

                <AuthField
                  label="Email"
                  icon={<Envelope size={22} color={colors.placeholder} weight="regular" />}
                  value={email}
                  onChangeText={setEmail}
                  error={fieldErrors.email ?? null}
                  placeholder="you@example.com"
                  autoCapitalize="none"
                  autoComplete="email"
                  autoCorrect={false}
                  keyboardType="email-address"
                  textContentType="emailAddress"
                  returnKeyType="next"
                  editable={!busy}
                />

                <View>
                  <AuthField
                    label="Password"
                    icon={<Lock size={22} color={colors.placeholder} weight="regular" />}
                    accessory={
                      <LinkText
                        label="Forgot password?"
                        onPress={() => navigation.navigate('ForgotPassword')}
                      />
                    }
                    value={password}
                    onChangeText={setPassword}
                    error={fieldErrors.password ?? null}
                    placeholder="Your password"
                    secureTextEntry={!reveal}
                    autoCapitalize="none"
                    autoComplete="current-password"
                    textContentType="password"
                    returnKeyType="go"
                    onSubmitEditing={() => {
                      if (canSubmit) void submit();
                    }}
                    editable={!busy}
                  />
                  {/* Overlaid on the well rather than placed in the text flow,
                      so the input keeps its full width. */}
                  <View style={styles.revealAnchor}>
                    <RevealToggle shown={reveal} onToggle={() => setReveal((r) => !r)} />
                  </View>
                </View>

                <Checkbox
                  checked={remember}
                  onToggle={() => setRemember((r) => !r)}
                  label="Remember me"
                />

                <GradientButton
                  label="Sign in"
                  busy={busy}
                  disabled={!canSubmit}
                  onPress={() => void submit()}
                />

                {/*
                  The social block appears only when BOTH ends can actually
                  complete it — the server has a client configured and so does
                  this build. Apple is deliberately absent: this API has no
                  Apple endpoint, no client configuration and nothing in the
                  spec, so the mockup's button would be one that can only fail.
                  Raised with Rio rather than shipped dark.

                  The Google button was shipped exactly that way by accident
                  and ran dead until 2026-09-11. That is the rule this comment
                  states, broken in the code directly beneath it.
                */}
                {googleEnabled ? (
                  <>
                    <OrDivider />
                    <View style={styles.socialRow}>
                      <GoogleButton
                        disabled={busy}
                        onResult={(result) => {
                          void onGoogleResult(result);
                        }}
                      />
                    </View>
                  </>
                ) : null}
              </View>
            </View>

            <View style={styles.footer}>
              <Text style={styles.footerText}>Don&rsquo;t have an account? </Text>
              <LinkText
                label="Create one"
                withArrow
                onPress={() => navigation.navigate('Register')}
              />
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </View>
  );
}

/**
 * The Google button, and the only thing that calls `useGoogleSignIn`.
 *
 * 🔴 **It is a component rather than a hook call in `SignInScreen` because a
 * hook cannot be called conditionally.** The provider module is absent on a
 * build without Google configured, and on a dev client that predates the
 * native modules it needs — which is how the first version of this feature
 * crashed the app on its first screen. A component boundary is the guard: the
 * caller renders this only when `GOOGLE_AVAILABLE`, so the hook is never
 * reached otherwise.
 */
function GoogleButton({
  disabled,
  onResult,
}: {
  disabled: boolean;
  onResult: (result: GoogleSignInResult) => void;
}) {
  const { start } = useGoogleSignIn();

  return (
    <QuietButton
      label="Google"
      icon={<GoogleMark />}
      // `start` is null until the discovery document has loaded, so the button
      // is disabled rather than silently doing nothing when pressed early.
      disabled={disabled || start === null}
      onPress={() => {
        if (start === null) return;
        void start().then(onResult);
      }}
    />
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background },
  safe: { flex: 1 },
  fill: { flex: 1 },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
    // Horizontal centring for the capped card. Without it the card sits hard
    // against the left edge the moment the screen is wider than `cardMaxWidth`
    // — which is every landscape phone and every tablet.
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.xl,
  },

  card: {
    width: '100%',
    maxWidth: auth.cardMaxWidth,
    backgroundColor: auth.cardBg,
    borderRadius: auth.cardRadius,
    borderWidth: 1,
    borderColor: auth.cardBorder,
    padding: spacing.xxl,
    // Not clipped: in the mockup the play mark runs past the card's right
    // edge, and `overflow: hidden` would cut it off at the corner radius.
    overflow: 'visible',
  },
  // Bleeds off the card's top-right, as in the mockup. `overflow: hidden` on
  // the card clips it to the rounded corner rather than letting it float free.
  watermark: { position: 'absolute', top: -8, right: -16 },

  greeting: { ...typography.greeting, color: colors.fieldText },
  subtitle: {
    fontFamily: 'Roboto_400Regular',
    fontSize: 17,
    lineHeight: 24,
    color: colors.textMuted,
    marginTop: spacing.sm,
  },

  form: { marginTop: spacing.xxl },
  revealAnchor: { position: 'absolute', right: spacing.lg, top: 45 },

  socialRow: { flexDirection: 'row', gap: spacing.lg },

  footer: {
    width: '100%',
    maxWidth: auth.cardMaxWidth,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: spacing.xxl,
  },
  footerText: { fontFamily: 'Roboto_400Regular', fontSize: 15, color: colors.textMuted },
});
