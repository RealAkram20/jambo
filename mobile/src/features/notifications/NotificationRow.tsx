import { createElement, memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Check } from 'phosphor-react-native';
import { Image as ExpoImage } from 'expo-image';

import type { Notification } from '../../api/catalogue';
import { imageUrl } from '../../ui/media';
import { Focusable } from '../../ui/rails/Focusable';
import { colors, fonts, notifications as t } from '../../ui/theme';
import { iconFor } from './icons';
import { relativeTime, toneFor } from './list';

/**
 * One notification, wearing the website's own inbox row.
 *
 * `.jambo-hub-inbox__row` on `/{username}/notifications` is the same component
 * on the web product, so its radius, hairline, padding, gap, unread tint and
 * 40x40 leading square are captured rather than measured off the mockup. Rio
 * was asked about the one place the two disagree — the mockup draws a larger
 * thumbnail — and held the site's size.
 *
 * **Pressing marks it read, and does not navigate.** The row carries an
 * `action_url`, but it is a website URL and the app has no route table that
 * maps site URLs onto its own screens. A deep-link resolver is its own piece
 * of work; guessing at it would send a viewer to the wrong screen or out to a
 * browser they did not ask for. A row that quietly clears its own unread mark
 * is honest. A row that opens the wrong thing is not.
 */
export const NotificationRow = memo(function NotificationRow({
  item,
  now,
  selecting,
  selected,
  onPress,
  onLongPress,
}: {
  item: Notification;
  now: number;
  /** True while the list is in selection mode, whatever this row's state. */
  selecting: boolean;
  selected: boolean;
  onPress: (item: Notification) => void;
  onLongPress: (item: Notification) => void;
}) {
  const unread = item.read !== true;
  const when = relativeTime(item.created_at, now);
  const message = typeof item.message === 'string' ? item.message : '';

  return (
    <Focusable
      accessibilityRole="button"
      // Unread is carried by the word as well as by the dot and the tint,
      // because status must never be colour alone — and unread is the whole
      // point of the row.
      accessibilityLabel={[unread ? 'Unread' : null, item.title, message, when]
        .filter((part): part is string => typeof part === 'string' && part !== '')
        // The full stop is added between the parts, so a message that already
        // ends in one must not bring its own — TalkBack read "catalogue.. 3m
        // ago" out of the seeded inbox.
        .map((part) => part.replace(/[.!?]+$/, ''))
        .join('. ')}
      {...(selecting
        ? {}
        : unread
          ? { accessibilityHint: 'Marks this as read. Long press to select' }
          : { accessibilityHint: 'Long press to select' })}
      /*
       * `selected` while selecting, `disabled` otherwise. A row that is read
       * has nothing a press can do to it, and announcing that is honest — but
       * during selection every row is pressable, so the disabled state would
       * be a lie about the one thing the viewer is doing.
       */
      accessibilityState={selecting ? { selected } : { disabled: !unread }}
      ringRadius={t.rowRadius}
      onPress={() => onPress(item)}
      onLongPress={() => onLongPress(item)}
      style={[
        styles.row,
        unread && !selecting ? styles.rowUnread : null,
        selected ? styles.rowSelected : null,
      ]}
    >
      {/*
        The tick replaces the poster rather than sitting beside it. A checkbox
        added alongside would reflow every row on entering selection, and a
        list that jumps as it is long-pressed loses the row under the thumb.
      */}
      {selecting ? <Tick on={selected} /> : <Leading item={item} />}

      <View style={styles.body}>
        <View style={styles.titleRow}>
          <Text numberOfLines={1} style={styles.title}>
            {item.title}
          </Text>

          {/* The mockup's unread mark. The website has no dot — it tints the
              whole row — so this one is drawn at the app's own size. */}
          {unread ? <View style={styles.dot} /> : null}

          {when === '' ? null : <Text style={styles.when}>{when}</Text>}
        </View>

        {message === '' ? null : (
          <Text numberOfLines={2} style={styles.message}>
            {message}
          </Text>
        )}
      </View>
    </Focusable>
  );
});

/** The selection mark, in the leading square's own footprint. */
function Tick({ on }: { on: boolean }) {
  return (
    <View style={[styles.tile, on ? styles.tickOn : styles.tickOff]}>
      {on ? <Check size={t.tileIconSize} color={colors.onPrimary} weight="bold" /> : null}
    </View>
  );
}

/**
 * The 40x40 square: the title's poster when there is one, the notification's
 * own icon when there is not.
 *
 * Which of the two appears is the server's decision, not this component's —
 * `MovieAddedNotification` sends a poster and `NewDeviceLoginNotification`
 * sends `ph-device-mobile`, exactly as the website's blade chooses between
 * `__image` and `__icon` on the same field.
 */
function Leading({ item }: { item: Notification }) {
  const source = imageUrl(item.image_url, t.imageSize);

  if (source !== null) {
    return (
      <ExpoImage
        source={{ uri: source }}
        style={styles.image}
        contentFit="cover"
        // Decorative: the row's accessibility label already reads the title
        // and the message, and "image" announced before them is noise.
        accessibilityElementsHidden
        importantForAccessibility="no"
      />
    );
  }

  const tone = t.tones[toneFor(item.colour)];

  return (
    <View style={[styles.tile, { backgroundColor: tone.bg }]}>
      {/*
        `createElement` rather than `const Icon = iconFor(...)`. The lookup
        returns a stable reference out of a frozen map, but a capitalised
        variable assigned in render reads to the linter — and to anyone
        skimming — as a component defined per render, which would remount the
        icon on every keystroke elsewhere in the tree. This says plainly that
        the component is chosen, not made.
      */}
      {createElement(iconFor(item.icon), {
        size: t.tileIconSize,
        color: tone.fg,
        weight: 'regular',
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: t.rowGap,
    padding: t.rowPadY,
    borderRadius: t.rowRadius,
    borderWidth: t.rowBorderWidth,
    borderColor: t.rowBorder,
    backgroundColor: t.rowBg,
  },
  rowUnread: { backgroundColor: t.unreadBg, borderColor: t.unreadBorder },
  /* Selection outranks unread: a viewer picking rows needs to see the picking. */
  rowSelected: { backgroundColor: t.unreadBg, borderColor: colors.primary },
  tickOn: { backgroundColor: colors.primary },
  tickOff: { borderWidth: 1, borderColor: t.rowBorder },

  image: { width: t.imageSize, height: t.imageSize, borderRadius: t.imageRadius },
  tile: {
    width: t.tileSize,
    height: t.tileSize,
    borderRadius: t.tileRadius,
    alignItems: 'center',
    justifyContent: 'center',
  },

  body: { flex: 1, gap: 2 },
  titleRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  title: { flexShrink: 1, fontFamily: fonts.medium, fontSize: t.titleSize, color: t.titleColor },
  dot: {
    width: t.dotSize,
    height: t.dotSize,
    borderRadius: t.dotSize / 2,
    backgroundColor: colors.primary,
  },
  when: { marginLeft: 'auto', fontSize: t.timeSize, color: t.timeColor, fontFamily: fonts.regular },
  message: { fontFamily: fonts.regular, fontSize: t.messageSize, color: t.messageColor, lineHeight: 19 },
});
