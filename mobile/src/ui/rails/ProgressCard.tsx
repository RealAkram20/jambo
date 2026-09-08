import { StyleSheet, Text, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { Play, X } from 'phosphor-react-native';

import { colors, fonts, spacing, typography, watching } from '../theme';
import { imageUrl } from '../media';
import type { ContinueWatchingCard } from '../../api/catalogue';
import { Focusable } from './Focusable';

/**
 * Continue Watching — the one card on the home screen that is not a poster.
 *
 * Streamit gives it `aspect-ratio: 5/3` and lays the still over a dark
 * gradient on `.iq-image-box`. That gradient is load-bearing rather than
 * decorative: `.iq-preogress`, the strip holding the title and the progress
 * bar, has **no background of its own**, so without the scrim the text sits
 * directly on whatever the artwork happens to be. Probing `.iq-preogress` for
 * a background was the wrong question and returned "none"; the answer lives
 * one element up.
 *
 * The site also sets `mix-blend-mode: overlay` on the still, which React
 * Native has no equivalent for. The gradient alone carries the readability,
 * which is what the blend mode was there to support, so the effect is
 * reproduced and the technique is not.
 */
export type ProgressItem = ContinueWatchingCard;

export function ProgressCard({
  item,
  width,
  height,
  onPress,
  onRemove,
  hasTVPreferredFocus = false,
}: {
  item: ProgressItem;
  width: number;
  height: number;
  onPress?: (() => void) | undefined;
  onRemove?: (() => void) | undefined;
  hasTVPreferredFocus?: boolean;
}) {
  const title = item.title ?? 'Untitled';
  const percent = clampPercent(item.progress_percent);
  const minutesLeft = item.minutes_left;

  /*
   * The accessible name carries what the card shows, in one utterance: what it
   * is, where in it you are, and how much is left. A screen reader landing on
   * "Untitled, image" would have to hunt through four child nodes for that.
   */
  const spoken = [
    title,
    item.subtitle,
    `${percent}% watched`,
    typeof minutesLeft === 'number' ? `${minutesLeft} minutes left` : null,
  ]
    .filter((part): part is string => typeof part === 'string' && part !== '')
    .join(', ');

  return (
    <View style={styles.block}>
      <Focusable
        accessibilityLabel={spoken}
        accessibilityHint="Resumes playback"
        ringRadius={watching.radius}
        hasTVPreferredFocus={hasTVPreferredFocus}
        onPress={onPress}
        style={{ width, height }}
      >
        <View style={[styles.frame, { width, height, borderRadius: watching.radius }]}>
          <ExpoImage
            source={imageUrl(item.image_url, width)}
            style={StyleSheet.absoluteFill}
            contentFit="cover"
            // `object-position: top` on the site: a still is usually a frame
            // with faces near the top, and centring crops them out.
            contentPosition="top"
            recyclingKey={`${item.resume?.type ?? ''}${item.resume?.id ?? title}`}
            transition={120}
            cachePolicy="memory-disk"
            accessible={false}
          />

          {/* The site's own stops, converted from the captured CSS gradient. */}
          <LinearGradient
            colors={[...watching.scrimColors]}
            locations={[...watching.scrimLocations]}
            style={StyleSheet.absoluteFill}
            pointerEvents="none"
          />

          <View style={styles.strip} pointerEvents="none">
            {typeof minutesLeft === 'number' ? (
              <Text style={styles.left}>{minutesLeft} m Left</Text>
            ) : null}

            <View style={styles.titleRow}>
              <Text numberOfLines={1} style={styles.title}>
                {title}
              </Text>
              <Play size={16} color={colors.onPrimary} weight="fill" />
            </View>

            {item.subtitle !== undefined && item.subtitle !== '' ? (
              <Text numberOfLines={1} style={styles.subtitle}>
                {item.subtitle}
              </Text>
            ) : null}

            {/*
              Progress is stated in the accessible name above as well as drawn
              here. A bar is a colour and a length, and neither is available to
              somebody using a screen reader.
            */}
            <View style={styles.track}>
              <View style={[styles.bar, { width: `${percent}%` }]} />
            </View>
          </View>
        </View>
      </Focusable>

      {/*
        Remove is its own focusable sibling, not a child of the card. Nested
        pressables do not both receive a d-pad focus, so a remote could never
        reach it if it were inside — and this rail is the one place in the app
        where a viewer removes something.
      */}
      {onRemove ? (
        <Focusable
          accessibilityLabel={`Remove ${title} from Continue Watching`}
          ringRadius={watching.removeSize / 2}
          onPress={onRemove}
          style={styles.remove}
        >
          <View style={styles.removeInner}>
            <X size={14} color={colors.onPrimary} weight="bold" />
          </View>
        </Focusable>
      ) : null}
    </View>
  );
}

/**
 * A percentage the layout can trust.
 *
 * The server computes this and it is normally sane, but a width of `NaN%` or
 * `-3%` silently breaks the row rather than showing a wrong number, which is
 * the kind of thing that gets found on a handset and not in a test.
 */
function clampPercent(value: number | undefined): number {
  if (typeof value !== 'number' || Number.isNaN(value)) return 0;
  return Math.max(0, Math.min(100, Math.round(value)));
}

const styles = StyleSheet.create({
  block: { position: 'relative' },
  frame: { overflow: 'hidden', backgroundColor: colors.surface },

  strip: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: spacing.sm,
    paddingBottom: spacing.sm,
  },
  left: {
    ...typography.caption,
    fontFamily: fonts.medium,
    color: colors.onPrimary,
    marginBottom: spacing.xs,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  title: {
    ...typography.caption,
    fontFamily: fonts.medium,
    color: colors.onPrimary,
    flexShrink: 1,
  },
  subtitle: { ...typography.caption, color: colors.textMuted },

  track: {
    height: watching.progressHeight,
    backgroundColor: watching.progressTrack,
    marginTop: spacing.sm,
    overflow: 'hidden',
  },
  bar: { height: '100%', backgroundColor: watching.progressBar },

  remove: { position: 'absolute', top: 6, right: 6 },
  removeInner: {
    width: watching.removeSize,
    height: watching.removeSize,
    borderRadius: watching.removeSize / 2,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.header,
  },
});
