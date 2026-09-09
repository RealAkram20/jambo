import { useCallback, useState } from 'react';
import { ScrollView, StyleSheet, Switch, Text, View } from 'react-native';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Check } from 'phosphor-react-native';

import { api } from '../api/jambo';
import type { StreamingPreferences } from '../api/endpoints';
import { ApiError, NetworkError } from '../api/errors';
import { Alert, Caption, ErrorState, Heading, Loading } from '../ui/components';
import { Focusable } from '../ui/rails/Focusable';
import { colors, fonts, profileMenu, radius, spacing, typography } from '../ui/theme';
import type { AppScreenProps } from '../navigation/types';

/**
 * How this viewer wants their video delivered.
 *
 * The screen Rio asked for on 2026-09-09 — "so all can edit and update their
 * streaming preferences" — and the reason the app is allowed to call itself a
 * pure streaming experience rather than a catalogue with an account attached.
 *
 * **Three settings, and the restraint is the design.** Every streaming app has
 * a quality menu reading 4K / 1080p / 720p / 480p. Jambo has exactly two
 * renditions, `video_url` and `video_url_low`, and `/playback/sessions` picks
 * between them with `quality: default|low`. Four labels over two files would
 * be three of them lying. So the choice offered is the choice that exists:
 * take the better one, take the smaller one, or let the app decide from the
 * connection.
 *
 * **Subtitles are not here** because the platform has no subtitle track
 * anywhere in Streaming or Content. A control for something the player cannot
 * deliver is worse than its absence: it makes the viewer think they turned
 * something on.
 *
 * Preferences live on the account rather than on the handset, so this screen
 * writes to the server and the answer is the truth. That is what makes the
 * setting hold on a second phone and, in Phase 4, on the television.
 */

/**
 * The three rendition policies, in the order a viewer should read them.
 *
 * Each one names what it costs, because "High" alone does not tell somebody on
 * MTN data the thing they most need to know. The wording is deliberately about
 * data rather than about pixels — there is no pixel count to quote that would
 * be true of both renditions on every title.
 */
const QUALITIES: {
  value: NonNullable<StreamingPreferences['video_quality']>;
  label: string;
  detail: string;
}[] = [
  {
    value: 'auto',
    label: 'Automatic',
    detail: 'Better picture on Wi-Fi, lighter on mobile data.',
  },
  {
    value: 'high',
    label: 'Best picture',
    detail: 'Always the higher quality file. Uses the most data.',
  },
  {
    value: 'data_saver',
    label: 'Data saver',
    detail: 'Always the lighter file. Best on a limited bundle.',
  },
];

function Card({ children }: { children: React.ReactNode }) {
  return <View style={styles.card}>{children}</View>;
}

/** One radio row in the quality group. */
function QualityRow({
  label,
  detail,
  selected,
  onPress,
}: {
  label: string;
  detail: string;
  selected: boolean;
  onPress: () => void;
}) {
  return (
    <Focusable
      accessibilityRole="button"
      accessibilityLabel={`${label}. ${detail}`}
      ringRadius={radius.surface}
      onPress={onPress}
      style={styles.quality}
    >
      <View style={styles.qualityText}>
        <Text style={[styles.qualityLabel, selected && styles.qualityLabelOn]}>{label}</Text>
        <Text style={styles.qualityDetail}>{detail}</Text>
      </View>

      {/*
        A tick, not colour alone. The selected row is also the only one with a
        filled mark and a brighter label, so which one is chosen survives a
        screenshot in greyscale and a viewer who cannot separate the blue.
      */}
      <View style={[styles.tick, selected && styles.tickOn]}>
        {selected ? <Check size={13} color={colors.onPrimary} weight="bold" /> : null}
      </View>
    </Focusable>
  );
}

function ToggleRow({
  label,
  detail,
  value,
  onChange,
  disabled,
}: {
  label: string;
  detail: string;
  value: boolean;
  onChange: (next: boolean) => void;
  disabled: boolean;
}) {
  return (
    <View style={styles.toggle}>
      <View style={styles.toggleText}>
        <Text style={styles.qualityLabel}>{label}</Text>
        <Text style={styles.qualityDetail}>{detail}</Text>
      </View>

      <Switch
        value={value}
        onValueChange={onChange}
        disabled={disabled}
        accessibilityLabel={label}
        accessibilityHint={detail}
        trackColor={{ false: colors.inputBg, true: colors.primary }}
        thumbColor={colors.fieldText}
      />
    </View>
  );
}

export function StreamingPreferencesScreen(_: AppScreenProps<'StreamingPreferences'>) {
  const queryClient = useQueryClient();
  const [failed, setFailed] = useState<string | null>(null);

  const prefs = useQuery({
    queryKey: ['preferences'],
    queryFn: () => api.preferences(),
  });

  const config = useQuery({ queryKey: ['app-config'], queryFn: () => api.appConfig() });

  /*
   * The write. `onMutate` moves the switch immediately and `onError` puts it
   * back, because a toggle that waits for a round trip on a Ugandan mobile
   * connection feels broken — people press it again, and the second press
   * undoes the first.
   *
   * The server's answer replaces local state wholesale rather than being
   * merged into it: the endpoint returns the complete set, and trusting it is
   * what keeps this screen honest about what was actually saved.
   */
  const save = useMutation({
    mutationFn: (changes: Partial<StreamingPreferences>) => api.updatePreferences(changes),
    onMutate: async (changes) => {
      setFailed(null);
      await queryClient.cancelQueries({ queryKey: ['preferences'] });

      const previous = queryClient.getQueryData<StreamingPreferences>(['preferences']);

      if (previous !== undefined) {
        queryClient.setQueryData<StreamingPreferences>(['preferences'], {
          ...previous,
          ...changes,
        });
      }

      return { previous };
    },
    onError: (error, _changes, context) => {
      if (context?.previous !== undefined) {
        queryClient.setQueryData(['preferences'], context.previous);
      }

      setFailed(
        error instanceof NetworkError
          ? 'Could not reach Jambo, so that change was not saved.'
          : error instanceof ApiError
            ? error.message
            : 'That change could not be saved. Try again in a moment.',
      );
    },
    onSuccess: (saved) => {
      queryClient.setQueryData(['preferences'], saved);
    },
  });

  const set = useCallback(
    (changes: Partial<StreamingPreferences>) => save.mutate(changes),
    [save],
  );

  if (prefs.isPending) return <Loading label="Loading your streaming settings" />;

  if (prefs.isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            prefs.error instanceof NetworkError
              ? 'Could not reach Jambo. Check your connection.'
              : 'We could not load your streaming settings.'
          }
          onRetry={() => void prefs.refetch()}
        />
      </View>
    );
  }

  const current = prefs.data;
  const quality = current.video_quality ?? 'auto';

  /*
   * Downloads are Phase 3 and the server says whether they exist at all. A
   * Wi-Fi-only switch on a build with no downloads is a control that appears
   * to do nothing, so the row is absent rather than disabled — a greyed switch
   * still invites the question "why can I not turn this on?".
   */
  const downloadsOn = config.data?.features?.downloads === true;

  return (
    <ScrollView contentContainerStyle={styles.screen}>
      {failed !== null ? <Alert tone="error">{failed}</Alert> : null}

      <Heading>Video quality</Heading>
      <Caption>Applies to everything you watch on this account.</Caption>

      <Card>
        {QUALITIES.map((option, index) => (
          <View key={option.value}>
            {index > 0 ? <View style={styles.hair} /> : null}
            <QualityRow
              label={option.label}
              detail={option.detail}
              selected={quality === option.value}
              onPress={() => set({ video_quality: option.value })}
            />
          </View>
        ))}
      </Card>

      <View style={styles.section}>
        <Heading>Playback</Heading>
        <Card>
          <ToggleRow
            label="Autoplay next episode"
            detail="Roll straight into the next episode when one ends."
            value={current.autoplay_next ?? true}
            onChange={(next) => set({ autoplay_next: next })}
            disabled={save.isPending}
          />
        </Card>
      </View>

      {downloadsOn ? (
        <View style={styles.section}>
          <Heading>Downloads</Heading>
          <Card>
            <ToggleRow
              label="Download on Wi-Fi only"
              detail="Hold downloads until you are on Wi-Fi, so they never spend your bundle."
              value={current.wifi_only_downloads ?? true}
              onChange={(next) => set({ wifi_only_downloads: next })}
              disabled={save.isPending}
            />
          </Card>
        </View>
      ) : null}

      <View style={styles.footer}>
        <Caption>
          These settings follow your account, so they apply on every device you sign in on.
        </Caption>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: { padding: spacing.lg, paddingBottom: spacing.xxl },
  section: { marginTop: spacing.xl },

  card: {
    marginTop: spacing.md,
    backgroundColor: colors.surface,
    borderRadius: radius.surface,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: colors.divider,
    overflow: 'hidden',
  },
  hair: { height: StyleSheet.hairlineWidth, backgroundColor: colors.divider },

  quality: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
  },
  qualityText: { flex: 1, gap: 2 },
  qualityLabel: { ...typography.body, fontSize: 15, color: colors.fieldText },
  qualityLabelOn: { fontFamily: fonts.medium },
  qualityDetail: { ...typography.caption, fontSize: 13, color: colors.textMuted },

  tick: {
    width: 22,
    height: 22,
    borderRadius: 11,
    borderWidth: 2,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tickOn: { backgroundColor: profileMenu.activeFrom, borderColor: profileMenu.activeFrom },

  toggle: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
  },
  toggleText: { flex: 1, gap: 2 },

  footer: { marginTop: spacing.xl },
});
