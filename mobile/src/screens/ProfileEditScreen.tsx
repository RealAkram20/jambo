import { useCallback, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Image as ExpoImage } from 'expo-image';

import { api } from '../api/jambo';
import { ApiError, NetworkError } from '../api/errors';
import { imageUrl } from '../ui/media';
import { card, colors, fonts, spacing } from '../ui/theme';
import { Alert, Button, Caption, ErrorState, Field, Loading } from '../ui/components';
import type { Profile } from '../api/endpoints';
import type { AppScreenProps } from '../navigation/types';

/**
 * Editing the viewer's own details.
 *
 * The website's profile form, minus the parts that are not the app's job:
 * password and two-factor live on the Security screen's note, and the avatar
 * is shown but not replaced here (see below).
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
        // An empty box means "no phone", which is null on the wire — not the
        // empty string, which the server would store as a phone number of
        // zero characters.
        phone: phone.trim() === '' ? null : phone.trim(),
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
        for (const key of ['first_name', 'last_name', 'username', 'email', 'phone']) {
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

        <Field
          label="Phone"
          value={phone}
          onChangeText={onChange(setPhone)}
          error={fieldErrors.phone ?? null}
          hint="Optional."
          keyboardType="phone-pad"
          editable={!save.isPending}
        />

        <Button
          label="Save changes"
          busy={save.isPending}
          disabled={!complete || save.isPending}
          onPress={() => save.mutate()}
        />

        <View style={styles.footer}>
          <Caption>Your password and two-factor settings are changed on the Jambo website.</Caption>
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
 * **Uploading is not here.** `POST /profile/avatar` exists, but replacing a
 * picture means an image picker, a permission prompt, client-side resizing and
 * a multipart upload with its own failure states. A camera-roll permission
 * request is not something to bolt onto a text form, and it earns its own
 * slice.
 */
function Avatar({
  profile,
}: {
  profile: { first_name?: string; last_name?: string; avatar_url?: string | null } | undefined;
}) {
  const initials = [profile?.first_name, profile?.last_name]
    .map((part) => (typeof part === 'string' ? part.trim().charAt(0) : ''))
    .join('')
    .toUpperCase();

  const url = imageUrl(profile?.avatar_url, 96);

  return (
    <View style={styles.avatarBlock}>
      {url === null ? (
        <View accessible accessibilityLabel="No profile picture" style={styles.initials}>
          <Text style={styles.initialsText}>{initials === '' ? '?' : initials}</Text>
        </View>
      ) : (
        <ExpoImage
          source={url}
          style={styles.avatar}
          contentFit="cover"
          accessibilityLabel="Your profile picture"
        />
      )}
      <Caption>Profile pictures are changed on the Jambo website.</Caption>
    </View>
  );
}

const AVATAR = 96;

const styles = StyleSheet.create({
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
