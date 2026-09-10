import { useCallback, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Image as ExpoImage } from 'expo-image';

import { api } from '../api/jambo';
import { ApiError, NetworkError } from '../api/errors';
import { imageUrl } from '../ui/media';
import { card, colors, fonts, radius, spacing, typography } from '../ui/theme';
import { CountryPicker } from '../ui/CountryPicker';
import { PhoneField } from '../ui/PhoneField';
import { toE164 } from '../ui/phone';
import { memberSince, NOT_SET } from '../ui/profileFields';
import { useAvatarUpload } from '../ui/useAvatarUpload';
import { Focusable } from '../ui/rails/Focusable';
import { CaretDown, SealCheck, Warning } from 'phosphor-react-native';
import { Alert, Button, Caption, ErrorState, Field, Loading } from '../ui/components';
import type { Profile } from '../api/endpoints';
import type { AppScreenProps } from '../navigation/types';

/**
 * The viewer's own profile. Reading and editing are the same screen.
 *
 * **They were two screens until 2026-09-10**, and the audit's §4.1 is the
 * reason they are not: `ProfileScreen` showed Email, Phone, Country and Member
 * since read-only, above a button to this form — which already displayed all
 * four in boxes, plus the names and the username. It was a SUBSET of the
 * editor behind an extra tap. A viewer read four values, tapped Edit, and saw
 * the same four again.
 *
 * The split had already done real damage: the avatar upload lived on the
 * read-only half while this half's caption said pictures were changed on the
 * website, so one screen was denying a capability the other one shipped.
 * `useAvatarUpload` fixed that; deleting the screen finishes it.
 *
 * **Member since came across as a caption and nothing else did.** It was the
 * only fact on the old screen that is not a field here. The gradient banner
 * did NOT come with it: §8 of the plan names "give the forms heroes" as the
 * mistake this drift invites, and a 190dp headline above a form pushes the
 * first field under the keyboard.
 *
 * `PATCH /profile` requires `first_name`, `last_name`, `username` and `email`
 * together — it is not a partial update — so the form always sends all four
 * whichever one was touched. Sending only the changed field would blank the
 * others.
 */
export function ProfileEditScreen(_: AppScreenProps<'ProfileEdit'>) {
  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['profile'],
    queryFn: () => api.profile(),
  });

  if (isPending) return <Loading label="Loading your profile" />;

  if (isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load your profile.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </View>
    );
  }

  return <ProfileForm profile={data} />;
}

/**
 * The form, mounted only once the profile exists.
 *
 * The fields are seeded from props in `useState` initialisers rather than from
 * an effect. An effect that calls `setState` on every data change cascades
 * renders — and worse here, a background refetch landing while somebody is
 * halfway through typing their surname would throw that edit away. This
 * component keeps whatever the viewer typed for as long as it is mounted,
 * which is the behaviour a form should have.
 */
function ProfileForm({ profile }: { profile: Profile }) {
  const queryClient = useQueryClient();

  const [firstName, setFirstName] = useState(profile.first_name ?? '');
  const [lastName, setLastName] = useState(profile.last_name ?? '');
  const [username, setUsername] = useState(profile.username ?? '');
  const [email, setEmail] = useState(profile.email ?? '');
  const [phone, setPhone] = useState(profile.phone ?? '');
  /*
   * The stored code and the name to show, seeded together from props. Keeping
   * the name in state as well as the code means the field does not go blank
   * for the moment between choosing a country and the list resolving its name
   * — the picker already knows both, so there is nothing to look up.
   */
  const [country, setCountry] = useState<string | null>(profile.country ?? null);
  const [countryName, setCountryName] = useState<string | null>(profile.country_name ?? null);
  const [pickingCountry, setPickingCountry] = useState(false);
  const [saved, setSaved] = useState(false);
  const [banner, setBanner] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const save = useMutation({
    mutationFn: () =>
      api.updateProfile({
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        username: username.trim(),
        email: email.trim(),
        /*
         * Converted once, here, rather than on every keystroke.
         *
         * A half-typed number has no E.164 form, so normalising as the viewer
         * types would have the field rewriting itself out from under them. An
         * empty box is null on the wire — not the empty string, which the
         * server would store as a phone number of zero characters — and a
         * number that cannot be read is sent as typed so the server can refuse
         * it and say why, rather than being silently dropped here.
         */
        phone: phone.trim() === '' ? null : (toE164(phone, country ?? undefined) ?? phone.trim()),
        // Same rule as phone: no country chosen is null on the wire, and this
        // endpoint is not a partial update — omitting the key clears it.
        country,
      }),
    onSuccess: async () => {
      setSaved(true);
      setBanner(null);
      setFieldErrors({});
      // `/me` carries the name the rest of the app draws, so it has to be
      // refetched too or the header keeps the old one until relaunch.
      await queryClient.invalidateQueries({ queryKey: ['profile'] });
      await queryClient.invalidateQueries({ queryKey: ['me'] });
    },
    onError: (caught: unknown) => {
      setSaved(false);

      if (caught instanceof ApiError && caught.code === 'VALIDATION_FAILED') {
        const next: Record<string, string> = {};
        for (const key of ['first_name', 'last_name', 'username', 'email', 'phone', 'country']) {
          const message = caught.fieldError(key);
          if (message !== null) next[key] = message;
        }
        setFieldErrors(next);
        // The per-field messages say it better than a banner repeating them.
        setBanner(Object.keys(next).length === 0 ? caught.message : null);
        return;
      }

      setBanner(
        caught instanceof NetworkError
          ? 'Could not reach Jambo. Your changes have not been saved.'
          : caught instanceof ApiError
            ? caught.message
            : 'We could not save your details. Try again in a moment.',
      );
    },
  });

  const onChange = useCallback((setter: (value: string) => void) => {
    return (value: string) => {
      setter(value);
      // The success banner belongs to the values that were saved, not to the
      // ones now on screen.
      setSaved(false);
    };
  }, []);

  /*
   * The em dash is a real answer here, not a fallback — `memberSince` gives
   * it for an absent or unparseable date, and "Member since —" is a caption
   * that says nothing. No line at all is the honest version.
   */
  const joined = memberSince(profile.joined_at);

  const complete =
    firstName.trim() !== '' &&
    lastName.trim() !== '' &&
    username.trim() !== '' &&
    email.trim() !== '';

  return (
    <View style={styles.screen}>
      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <Avatar profile={profile} />

        {banner !== null ? <Alert tone="error">{banner}</Alert> : null}
        {saved ? <Alert tone="ok">Your details have been saved.</Alert> : null}

        <View style={styles.row}>
          <View style={styles.half}>
            <Field
              label="First name"
              value={firstName}
              onChangeText={onChange(setFirstName)}
              error={fieldErrors.first_name ?? null}
              autoCapitalize="words"
              editable={!save.isPending}
            />
          </View>
          <View style={styles.half}>
            <Field
              label="Last name"
              value={lastName}
              onChangeText={onChange(setLastName)}
              error={fieldErrors.last_name ?? null}
              autoCapitalize="words"
              editable={!save.isPending}
            />
          </View>
        </View>

        <Field
          label="Username"
          value={username}
          onChangeText={onChange(setUsername)}
          error={fieldErrors.username ?? null}
          // The spec records that the username is also the referral code and
          // the public profile URL. Somebody changing it should know that.
          hint="Also your referral code and your profile link."
          autoCapitalize="none"
          autoCorrect={false}
          editable={!save.isPending}
        />

        <Field
          label="Email"
          value={email}
          onChangeText={onChange(setEmail)}
          error={fieldErrors.email ?? null}
          autoCapitalize="none"
          autoCorrect={false}
          keyboardType="email-address"
          editable={!save.isPending}
        />

        <EmailState
          verified={profile.email_verified === true}
          saved={typeof profile.email === 'string' ? profile.email : ''}
          typed={email}
        />

        {/*
          The dial code comes from the Country row below rather than being a
          fourth thing to type, and the number formats as it is entered. Rio's
          request of 2026-09-10; see `ui/phone.ts` for the fifteen input
          formats it has to read.
        */}
        <PhoneField
          label="Phone"
          value={phone}
          onChangeText={onChange(setPhone)}
          country={country}
          error={fieldErrors.phone ?? null}
          hint="Optional."
        />

        {/*
          A country is chosen, never typed. Free text would give the database
          "Uganda", "uganda" and "Ugnada" for one country and hand every one of
          them back to the viewer exactly as they mistyped it.
        */}
        <View style={styles.field}>
          <Text style={styles.fieldLabel}>Country</Text>
          <Focusable
            accessibilityRole="button"
            accessibilityLabel={
              countryName === null ? 'Country, not set' : `Country, ${countryName}`
            }
            accessibilityHint="Opens the country list"
            ringRadius={radius.input}
            onPress={() => setPickingCountry(true)}
            disabled={save.isPending}
            style={styles.select}
          >
            <Text
              style={[styles.selectValue, countryName === null && styles.selectPlaceholder]}
              numberOfLines={1}
            >
              {countryName ?? 'Choose your country'}
            </Text>
            <CaretDown size={16} color={colors.placeholder} weight="bold" />
          </Focusable>
          {fieldErrors.country !== undefined ? (
            <Text style={styles.fieldError}>{fieldErrors.country}</Text>
          ) : null}
        </View>

        <CountryPicker
          visible={pickingCountry}
          selected={country}
          onClose={() => setPickingCountry(false)}
          onSelect={(code, name) => {
            setCountry(code);
            setCountryName(name);
            setPickingCountry(false);
            // The success banner belongs to what was saved, not to what is now
            // on screen — same rule the text fields follow.
            setSaved(false);
          }}
        />

        <Button
          label="Save changes"
          busy={save.isPending}
          disabled={!complete || save.isPending}
          onPress={() => save.mutate()}
        />

        <View style={styles.footer}>
          {/*
            Member since, and it is the whole of what the deleted read-only
            screen carried that this one did not. A fact about the account
            rather than about any field, so it sits at the foot rather than
            becoming a fifth thing that looks editable and is not.
          */}
          {joined !== NOT_SET ? <Caption>{`Member since ${joined}`}</Caption> : null}

          {/*
            It said these were changed on the website, and that stopped being
            true when ChangePassword and TwoFactorSetup shipped. A screen that
            sends somebody to a browser for something the app does is worse
            than saying nothing, so it names the screen that owns them.
          */}
          <Caption>Password and two-factor are on the Security screen.</Caption>
        </View>
      </ScrollView>
    </View>
  );
}

/**
 * The avatar, or initials.
 *
 * `avatar_url` is genuinely nullable and most accounts have never uploaded
 * one, so the empty case is initials derived from this viewer's own name —
 * never a stock photograph of a stranger, which is what a default avatar
 * image amounts to.
 *
 * 🔴 **Uploading IS here now, and the line that said otherwise was
 * false.** This block used to end with the caption *"Profile pictures are
 * changed on the Jambo website"* — while `ProfileScreen`, one tap away, has
 * had a working picker and upload the whole time. Rio called it out on
 * 2026-09-10 as fake information, and he was right: the app was sending
 * somebody to a browser for something it already did.
 *
 * The flow was not copied in. It moved to `useAvatarUpload`, and both screens
 * call it — permission handling, MIME sniffing and four failure sentences are
 * exactly the shape that becomes two subtly different implementations within
 * a week.
 */
function Avatar({
  profile,
}: {
  profile: { first_name?: string; last_name?: string; avatar_url?: string | null } | undefined;
}) {
  const { pick, busy, error } = useAvatarUpload();

  const initials = [profile?.first_name, profile?.last_name]
    .map((part) => (typeof part === 'string' ? part.trim().charAt(0) : ''))
    .join('')
    .toUpperCase();

  const url = imageUrl(profile?.avatar_url, 96);

  return (
    <View style={styles.avatarBlock}>
      <Focusable
        accessibilityLabel={url === null ? 'Add a profile picture' : 'Change your profile picture'}
        accessibilityRole="button"
        accessibilityState={{ busy }}
        onPress={() => void pick()}
        ringRadius={48}
      >
        {url === null ? (
          <View style={styles.initials}>
            <Text style={styles.initialsText}>{initials === '' ? '?' : initials}</Text>
          </View>
        ) : (
          <ExpoImage source={url} style={styles.avatar} contentFit="cover" />
        )}
      </Focusable>

      {/*
        The picture is the control, so the words under it only have to say the
        one thing the picture cannot: that it is pressable. Rio's wording rule
        — a caption that restates its own heading carries nothing.
      */}
      <Caption>{busy ? 'Uploading…' : 'Tap to change your picture.'}</Caption>
      {error === null ? null : <Alert tone="error">{error}</Alert>}
    </View>
  );
}

const AVATAR = 96;

/**
 * Whether this address is verified, and the way to fix it if it is not.
 *
 * 🔴 **Rio, 2026-09-10, looking at this exact field: "this does not show
 * or help to verify the email address."** He was right twice over. The field
 * printed the address and nothing else, and the server it talks to was worse:
 * `PATCH /profile` cleared `email_verified_at` on a change and answered "Check
 * your new address for a verification link" **while sending nothing**. So the
 * one moment somebody is watching their inbox was the moment no mail came.
 * The send is now in `ProfileController::update` with a test that fails
 * without it.
 *
 * Three states, because the third is the one that was missing:
 *
 *  - **Verified.** A quiet line. Nothing to do.
 *  - **Not verified.** The state, and a control that sends a fresh link
 *    through `POST /auth/email/resend` — the same endpoint the Security screen
 *    uses, not a second one.
 *  - **Edited but not yet saved.** Neither of the above is true of what is on
 *    screen: the typed address has no verification state at all, and offering
 *    to send a link to an address the server has never seen would send it to
 *    the old one. It says what saving will do instead.
 *
 * The state is a word and a colour, never a colour alone.
 */
function EmailState({
  verified,
  saved,
  typed,
}: {
  verified: boolean;
  saved: string;
  typed: string;
}) {
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);
  const [failed, setFailed] = useState<string | null>(null);

  const changed = typed.trim().toLowerCase() !== saved.trim().toLowerCase();

  const send = useCallback(async () => {
    setSending(true);
    setFailed(null);
    try {
      await api.resendVerificationEmail();
      setSent(true);
    } catch (caught) {
      setFailed(
        caught instanceof NetworkError
          ? 'Could not reach Jambo. Check your connection and try again.'
          : caught instanceof ApiError && caught.message !== ''
            ? caught.message
            : 'We could not send the link. Try again in a moment.',
      );
    } finally {
      setSending(false);
    }
  }, []);

  if (changed) {
    return (
      <View style={styles.emailState}>
        <Caption>Saving sends a verification link to the new address.</Caption>
      </View>
    );
  }

  if (verified) {
    return (
      <View style={styles.emailState}>
        <View style={styles.emailRow}>
          <SealCheck size={15} color={colors.okText} weight="fill" />
          <Text style={styles.emailVerified}>Verified</Text>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.emailState}>
      {sent ? (
        <Caption>Link sent. Check your inbox, and your spam folder if it is not there.</Caption>
      ) : (
        <>
          <View style={styles.emailRow}>
            <Warning size={15} color={colors.warning} weight="fill" />
            <Text style={styles.emailUnverified}>Not verified</Text>
          </View>
          {failed === null ? null : <Caption>{failed}</Caption>}
          <View style={styles.emailAction}>
            <Button
              label="Send verification link"
              tone="quiet"
              busy={sending}
              onPress={() => void send()}
            />
          </View>
        </>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  emailState: { marginTop: -spacing.sm, marginBottom: spacing.md, gap: spacing.xs },
  emailRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs },
  emailVerified: { ...typography.caption, color: colors.okText },
  emailUnverified: { ...typography.caption, color: colors.warning },
  emailAction: { alignSelf: 'flex-start' },

  field: { gap: spacing.xs },
  fieldLabel: { ...typography.caption, fontSize: 13, color: colors.textMuted },
  select: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    height: 46,
    paddingHorizontal: spacing.md,
    borderRadius: radius.input,
    backgroundColor: colors.inputBg,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: colors.border,
  },
  selectValue: { ...typography.field, color: colors.fieldText, flex: 1 },
  selectPlaceholder: { color: colors.placeholder },
  fieldError: { ...typography.caption, fontSize: 13, color: colors.errorText },
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg, paddingBottom: spacing.xxl },

  avatarBlock: { alignItems: 'center', gap: spacing.sm, marginBottom: spacing.xl },
  avatar: {
    width: AVATAR,
    height: AVATAR,
    borderRadius: AVATAR / 2,
    backgroundColor: colors.surface,
  },
  initials: {
    width: AVATAR,
    height: AVATAR,
    borderRadius: AVATAR / 2,
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  initialsText: { fontFamily: fonts.medium, fontSize: 32, color: card.titleColor },

  row: { flexDirection: 'row', gap: spacing.md },
  half: { flex: 1 },

  footer: { marginTop: spacing.xl, borderTopWidth: 1, borderTopColor: colors.divider, paddingTop: spacing.lg },
});
