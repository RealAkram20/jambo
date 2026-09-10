import type { ComponentType } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { X } from 'phosphor-react-native';

import { Focusable } from './rails/Focusable';
import { colors, fonts, selection as t, spacing } from './theme';

/** One action in the bar, beside Cancel. */
export type SelectionAction = {
  key: string;
  label: string;
  /** Announced to a screen reader, where "Delete" alone does not say how many. */
  accessibilityLabel: string;
  icon: ComponentType<{ size: number; color: string; weight: 'bold' }>;
  destructive?: boolean;
  onPress: () => void;
};

/**
 * The bar that appears while rows are selected.
 *
 * Built for the inbox on Rio's ask of 2026-09-10 — *"long press to select and
 * we can select multiple"* — and shaped so the Watchlist's identical bar can
 * move onto it in a commit of its own rather than as a side effect here.
 *
 * **Cancel is first and always enabled.** A viewer who long-pressed by
 * accident needs the way out to be the control their thumb is already near,
 * and it must never be the one greyed out. The destructive action sits at the
 * far end, where a mis-tap aimed at Cancel cannot reach it.
 *
 * **An action with nothing to do is not drawn at all.** The website's inbox
 * does the same with its header buttons, showing "Mark all as read" only while
 * something is unread. A disabled button teaches nothing; an absent one asks
 * no question.
 */
export function SelectionBar({
  count,
  busy,
  bottomInset,
  actions,
  onCancel,
}: {
  count: number;
  busy: boolean;
  /** The safe-area inset, so the bar clears the gesture pill rather than sitting under it. */
  bottomInset: number;
  actions: readonly SelectionAction[];
  onCancel: () => void;
}) {
  return (
    <View
      style={[styles.bar, { paddingBottom: Math.max(spacing.md, bottomInset) }]}
      // A live region: the count changes under the viewer's thumb as they tap
      // rows, and that change is the only feedback that a tap registered.
      accessibilityLiveRegion="polite"
    >
      <Focusable
        accessibilityRole="button"
        accessibilityLabel="Cancel selection"
        ringRadius={t.radius}
        onPress={onCancel}
        style={styles.cancel}
      >
        <X size={16} color={colors.text} weight="bold" />
        <Text style={styles.cancelText}>{count}</Text>
      </Focusable>

      {actions.map((action) => (
        <Focusable
          key={action.key}
          accessibilityRole="button"
          accessibilityLabel={action.accessibilityLabel}
          accessibilityState={{ disabled: busy, busy }}
          disabled={busy}
          ringRadius={t.radius}
          onPress={action.onPress}
          style={[
            styles.action,
            action.destructive === true ? styles.actionDanger : styles.actionQuiet,
            busy ? styles.actionOff : null,
          ]}
        >
          <action.icon
            size={16}
            color={action.destructive === true ? t.destructiveFg : colors.text}
            weight="bold"
          />
          <Text
            style={[
              styles.actionText,
              action.destructive === true ? styles.actionTextDanger : null,
            ]}
            numberOfLines={1}
          >
            {action.label}
          </Text>
        </Focusable>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  bar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
    backgroundColor: t.bg,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: t.border,
  },
  cancel: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    height: t.buttonHeight,
    paddingHorizontal: spacing.lg,
    borderRadius: t.radius,
    borderWidth: 1,
    borderColor: t.border,
  },
  cancelText: { fontFamily: fonts.medium, fontSize: t.textSize, color: t.countColor },

  action: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    height: t.buttonHeight,
    paddingHorizontal: spacing.md,
    borderRadius: t.radius,
  },
  actionQuiet: { borderWidth: 1, borderColor: t.border },
  actionDanger: { backgroundColor: t.destructiveBg },
  actionOff: { opacity: 0.45 },
  actionText: { fontFamily: fonts.medium, fontSize: t.textSize, color: colors.text },
  actionTextDanger: { color: t.destructiveFg },
});
