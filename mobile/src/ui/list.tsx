import React from 'react';
import { StyleSheet, Text, View, type ViewStyle } from 'react-native';
import { CaretRight, type Icon } from 'phosphor-react-native';

import { Focusable } from './rails/Focusable';
import { colors, fonts, list, notifications, plans, spacing, typography } from './theme';

/**
 * The row and the card, for every screen in the app that has them.
 *
 * **Rio asked for the UI to be uniform, and it was not.** Four screens had
 * each grown their own version of the same two shapes — the profile menu, the
 * profile details, the streaming settings and the country picker — with four
 * row heights, three icon sizes and two card treatments between them. Nothing
 * was wrong individually; together they read as four apps. This is the one
 * implementation they now share, and `list` in `theme.ts` is the one set of
 * numbers it spends.
 *
 * The rule from here: **a screen that wants a row uses `ListRow`.** If a row
 * needs something these do not offer, the honest move is to widen this file
 * rather than to hand-roll a fifth variant next to it.
 */

/**
 * A group of rows on one surface.
 *
 * Clips its children, so the first and last rows take the card's corners and
 * a pressed row's highlight cannot escape past them.
 */
export function ListCard({
  children,
  style,
}: {
  children: React.ReactNode;
  style?: ViewStyle;
}) {
  return <View style={[styles.card, style]}>{children}</View>;
}

/** A section heading above a card. Uppercase and tracked, as the menu draws it. */
export function ListSection({ children }: { children: string }) {
  return <Text style={styles.section}>{children}</Text>;
}

/**
 * One row.
 *
 * The shape covers what the app actually needs and no more: an icon, a label,
 * and then either a value on the right, an arbitrary accessory such as a
 * switch, or a chevron when the row opens something.
 *
 * `value` and `accessory` are mutually exclusive in practice — a row with both
 * a value and a switch has two answers to one question — so `accessory` wins
 * and `value` is ignored, rather than both being drawn in a fight for the same
 * space.
 */
export function ListRow({
  icon: RowIcon,
  label,
  detail,
  value,
  accessory,
  iconTile = false,
  onPress,
  chevron = true,
  expanded,
  muted = false,
  tone = 'normal',
  last = false,
  accessibilityLabel,
}: {
  icon?: Icon;
  label: string;
  /** A second line under the label, for a setting that needs explaining. */
  detail?: string;
  /** The right-hand value, for a row that reports rather than opens. */
  value?: string;
  /** A switch, a tick, a badge — anything that is not a plain string. */
  accessory?: React.ReactNode;
  /**
   * Sets the icon in a rounded tile instead of drawing it bare.
   *
   * Off by default, so every row already written keeps the look it has. Rio's
   * Security mockup of 2026-09-10 draws its icons this way and the app had no
   * way to say it — the alternative was a second row component for one screen,
   * which is what §4 exists to stop. The tile's fill and radius are the ones
   * the notification inbox already uses for the same idea.
   */
  iconTile?: boolean;
  onPress?: () => void;
  /**
   * Draws the chevron. On by default, because most pressable rows open a
   * screen and the caret is what says so.
   *
   * Pass `false` for a row that DOES something rather than going somewhere —
   * "Mark all read" in the inbox's overflow sheet acts and closes, and a caret
   * beside it promises a screen that never arrives.
   */
  chevron?: boolean;
  /** Draws the value at reduced strength: it is absent, not merely short. */
  muted?: boolean;
  /** `danger` is the sign-out treatment, and it colours the icon and label. */
  tone?: 'normal' | 'danger';
  last?: boolean;
  /** Overrides the announcement. Give one whenever the visible text alone
   *  would leave a screen reader user guessing. */
  accessibilityLabel?: string;
  /**
   * Turns the row into a disclosure: a button that opens a section in place
   * rather than a link that goes somewhere.
   *
   * Passing it changes the announced role from `link` to `button` and states
   * whether the section is open, which is the whole of what a screen reader
   * needs to use one. Undefined leaves every existing row exactly as it was.
   *
   * Added for the notification inbox's Delivery section rather than a second
   * row component being written beside this one, which is what section 4 of
   * the `screen` skill exists to stop.
   */
  expanded?: boolean;
}) {
  const tint = tone === 'danger' ? colors.errorText : list.iconColor;

  const body = (
    <View style={[styles.row, last && styles.rowLast]}>
      {RowIcon === undefined ? null : iconTile ? (
        <View style={styles.tile}>
          <RowIcon size={list.iconSize} color={tint} weight="regular" />
        </View>
      ) : (
        <RowIcon size={list.iconSize} color={tint} weight="regular" />
      )}

      <View style={styles.text}>
        <Text
          style={[styles.label, tone === 'danger' && styles.labelDanger]}
          numberOfLines={detail === undefined ? 1 : 2}
        >
          {label}
        </Text>
        {detail !== undefined ? <Text style={styles.detail}>{detail}</Text> : null}
      </View>

      {accessory !== undefined ? (
        accessory
      ) : value !== undefined ? (
        <Text style={[styles.value, muted && styles.valueMuted]} numberOfLines={1}>
          {value}
        </Text>
      ) : null}

      {onPress !== undefined && chevron ? (
        <CaretRight size={list.chevronSize} color={list.chevronColor} weight="bold" />
      ) : null}
    </View>
  );

  if (onPress === undefined) {
    /*
     * A row that does not open anything is not a button, so it is not focusable
     * and a remote skips it. `accessible` groups it into one announcement —
     * "Phone, not set" rather than three separate stops a viewer has to
     * assemble themselves.
     */
    return (
      <View accessible accessibilityLabel={accessibilityLabel ?? `${label}${valueSuffix(value)}`}>
        {body}
      </View>
    );
  }

  return (
    <Focusable
      accessibilityLabel={accessibilityLabel ?? label}
      accessibilityRole={expanded === undefined ? 'link' : 'button'}
      {...(expanded === undefined ? {} : { accessibilityState: { expanded } })}
      ringRadius={list.cardRadius}
      onPress={onPress}
    >
      {body}
    </Focusable>
  );
}

/** ", value" or nothing, so an announcement never ends on a dangling comma. */
function valueSuffix(value: string | undefined): string {
  return value === undefined || value === '' ? '' : `, ${value}`;
}

const styles = StyleSheet.create({
  /*
   * The icon tile, at the notification inbox's captured size and radius.
   *
   * Its fill is the row's own surface lifted a step rather than a new colour:
   * the site has no tile behind a settings icon to capture, so the honest
   * options were a token that already exists for this shape or a hex typed
   * here, and §3 settles that.
   */
  tile: {
    width: notifications.tileSize,
    height: notifications.tileSize,
    borderRadius: notifications.tileRadius,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: plans.tabBg,
  },

  card: {
    backgroundColor: colors.surface,
    borderRadius: list.cardRadius,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: colors.divider,
    // Clips a row's corners and any pressed highlight to the card.
    overflow: 'hidden',
  },

  section: {
    fontFamily: fonts.medium,
    fontSize: list.sectionSize,
    letterSpacing: list.sectionTracking,
    textTransform: 'uppercase',
    color: colors.textMuted,
    paddingHorizontal: list.rowPaddingH,
    paddingBottom: spacing.sm,
  },

  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: list.rowGap,
    minHeight: list.rowMinHeight,
    paddingVertical: list.rowPaddingV,
    paddingHorizontal: list.rowPaddingH,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.divider,
  },
  rowLast: { borderBottomWidth: 0 },

  text: { flex: 1, gap: 2 },
  label: { ...typography.body, fontSize: list.labelSize, color: colors.text },
  labelDanger: { color: colors.errorText },
  detail: { ...typography.caption, fontSize: list.detailSize, color: colors.textMuted },

  value: {
    ...typography.body,
    fontSize: list.valueSize,
    color: colors.fieldText,
    flexShrink: 1,
    textAlign: 'right',
  },
  /* A dash is absence, not a value, so it is not drawn at full strength. */
  valueMuted: { color: colors.placeholder },
});
