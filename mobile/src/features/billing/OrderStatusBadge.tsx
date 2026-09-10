import { StyleSheet, Text } from 'react-native';

import { statusLabel, statusTone } from './format';
import { badge } from '../../ui/theme';

/**
 * A charge's status, as the website paints it.
 *
 * `billing.blade.php` and `invoice.blade.php` both render
 * `<span class="badge bg-success|bg-warning">{{ ucfirst($order->status) }}</span>`,
 * so the badge carries the **word** and the colour is emphasis rather than
 * message. That is why this can never become a coloured dot.
 *
 * **Every number here is measured, not chosen.** Rio's ruling of 2026-09-09 is
 * that the app wears the webapp's assets at their exact design, behaviour and
 * size. The first cut of this component approximated the site's badge with the
 * semantic `ok` and `warning` tokens and drew a hollow outline; the site paints
 * a solid fill. It looked reasonable and it was not the product. The values now
 * come from probes against the rendered, signed-in billing page — see the
 * `badge` block in `theme.ts`.
 *
 * 🔴 **One value deviates and it is the only one.** The site's `bg-warning`
 * puts white on #ffd81c, which measures 1.39:1 where WCAG asks 4.5:1 — its own
 * Pending badge is effectively unreadable. Rio's call: keep the site's fill,
 * darken the text, and report the contrast defect so the website is fixed and
 * the two converge. The deviation lives in `theme.ts` beside its reason rather
 * than here, so there is one place to undo it.
 */
export function OrderStatusBadge({ status }: { status: string | null | undefined }) {
  const ok = statusTone(status) === 'ok';

  return (
    <Text
      style={[styles.badge, ok ? styles.ok : styles.warn]}
      // The row's own accessibilityLabel already announces the status, so this
      // is decoration to a screen reader and announcing it twice is noise.
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
    >
      {statusLabel(status)}
    </Text>
  );
}

const styles = StyleSheet.create({
  badge: {
    fontSize: badge.size,
    fontWeight: String(badge.weight) as '700',
    lineHeight: badge.size + 4,
    paddingVertical: badge.padY,
    paddingHorizontal: badge.padX,
    borderRadius: badge.radius,
    // Clips the fill to the radius on Android, which otherwise paints the
    // background square behind a rounded Text.
    overflow: 'hidden',
  },
  ok: { backgroundColor: badge.okBg, color: badge.okFg },
  warn: { backgroundColor: badge.warnBg, color: badge.warnFg },
});
