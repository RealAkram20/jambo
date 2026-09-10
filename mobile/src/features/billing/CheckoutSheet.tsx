import { useCallback, useRef, useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { WebView, type WebViewNavigation } from 'react-native-webview';
import { X } from 'phosphor-react-native';

import { Sheet } from '../../ui/overlay';
import { colors, fonts, radius, spacing, typography } from '../../ui/theme';
import { Focusable } from '../../ui/rails/Focusable';
import type { Checkout } from './api';

/**
 * The payment, inside the app.
 *
 * 🔴 **Rio, 2026-09-10, twice: "the same way we are given iframe from
 * pesapal", then "we are supposed to use a popup, not a redirect."** The first
 * cut opened the system browser, which works but hands the viewer to another
 * app in the middle of a purchase — they come back by remembering to, or they
 * do not come back.
 *
 * **It renders whatever the server describes, and knows nothing about
 * PesaPal.** The API answers `{ mode, url, return_url, cancel_url }`; this
 * loads the URL and watches for navigation to one of Jambo's own two URLs.
 * That is the property Rio asked for — *"we don't want to lose sales because
 * someone did not update the app"* — because a gateway added next year is a
 * different URL in the same shape, and this component does not change.
 *
 * **Finishing is decided by the server, never by this.** Reaching `return_url`
 * means the payer got to the end of the gateway's flow; it does **not** mean
 * the money arrived. Mobile money in Uganda is a USSD prompt that can be
 * approved after the page has moved on. So closing reports "the flow ended"
 * and the screen behind polls the order — the gateway confirms to the server,
 * and that is the only thing that decides whether a subscription starts.
 *
 * **It uses the app's own `Sheet`, which grew two props rather than this
 * growing a seventh hand-rolled overlay.** A payment needs the full height,
 * because a gateway's page cannot be measured, and it must not close on a
 * stray scrim tap while somebody is typing a mobile-money PIN. Both are now
 * options on the shared component — `fullHeight` and `dismissOnScrim` — so
 * the rule that bans a raw `Modal` did not need an exemption, and the next
 * overlay with the same need inherits them.
 */
export function CheckoutSheet({
  checkout,
  onClose,
}: {
  checkout: Checkout | null;
  /** `reached` says which of Jambo's URLs ended it, or null if dismissed. */
  onClose: (reached: 'return' | 'cancel' | null) => void;
}) {
  const [loading, setLoading] = useState(true);

  /*
   * Guards against reporting twice.
   *
   * A single page can fire `onNavigationStateChange` more than once — a
   * redirect chain, a fragment change — and the second one would close an
   * already-closed sheet and re-poll an order for no reason.
   */
  const settled = useRef(false);

  const onNavigate = useCallback(
    (event: WebViewNavigation) => {
      if (settled.current || checkout === null) return;

      const url = event.url ?? '';

      /*
       * Matched by prefix rather than equality: a gateway appends its own
       * query string to the return URL — `?OrderTrackingId=…&OrderMerchantReference=…`
       * on PesaPal — and an equality check would never fire, leaving the payer
       * staring at Jambo's own completion page inside a sheet with no way out
       * but the X.
       */
      if (startsWith(url, checkout.cancel_url)) {
        settled.current = true;
        onClose('cancel');
        return;
      }

      if (startsWith(url, checkout.return_url)) {
        settled.current = true;
        onClose('return');
      }
    },
    [checkout, onClose],
  );

  if (checkout === null) return null;

  return (
    <Sheet
      visible
      fullHeight
      /*
       * The scrim is inert here. A payment page is not something to dismiss by
       * tapping beside it — the X in the bar is the way out, and it is always
       * on screen.
       */
      dismissOnScrim={false}
      onClose={() => {
        settled.current = true;
        onClose(null);
      }}
    >
      <View style={styles.screen}>
        <View style={styles.bar}>
          <Text style={styles.title} numberOfLines={1}>
            Secure payment
          </Text>
          <Focusable
            accessibilityLabel="Close payment"
            accessibilityRole="button"
            onPress={() => {
              settled.current = true;
              onClose(null);
            }}
            ringRadius={radius.pill}
          >
            <View style={styles.close}>
              <X size={20} color={colors.text} weight="bold" />
            </View>
          </Focusable>
        </View>

        <View style={styles.body}>
          <WebView
            source={{ uri: checkout.url }}
            onNavigationStateChange={onNavigate}
            onLoadEnd={() => setLoading(false)}
            /*
             * A hosted checkout is a real browser document: it runs scripts,
             * sets cookies for its own session, and on PesaPal opens a
             * third-party bank or mobile-money page inside itself.
             */
            javaScriptEnabled
            domStorageEnabled
            sharedCookiesEnabled
            thirdPartyCookiesEnabled
            setSupportMultipleWindows={false}
            style={styles.web}
          />

          {loading ? (
            <View style={styles.loading} pointerEvents="none">
              <ActivityIndicator size="large" color={colors.primary} />
            </View>
          ) : null}
        </View>

        {/*
          Said once, where somebody deciding whether to type a PIN can read it.
          Not a reassurance about security — a fact about who is being paid.
        */}
        <View style={styles.foot}>
          <Text style={styles.footText}>Payment is handled by our payment provider.</Text>
        </View>
      </View>
    </Sheet>
  );
}

/** Prefix match that ignores a trailing slash difference between the two. */
function startsWith(url: string, base: string): boolean {
  if (base === '') return false;

  const trimmed = base.endsWith('/') ? base.slice(0, -1) : base;

  return url === trimmed || url.startsWith(trimmed + '?') || url.startsWith(trimmed + '/');
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  bar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.divider,
  },
  title: { flex: 1, fontFamily: fonts.medium, fontSize: 16, color: colors.text },
  close: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },

  body: { flex: 1 },
  /*
   * White, not the app's black. The gateway's page has its own light design
   * and a dark container behind a loading white document flashes inverted.
   */
  web: { flex: 1, backgroundColor: '#ffffff' },
  /*
   * The four sides written out rather than spread.
   *
   * This React Native build ships no `absoluteFillObject` in its types, so the
   * spread every RN codebase uses does not compile here. `absoluteFill` is a
   * registered style id rather than an object, so spreading THAT is wrong in a
   * second way that happens to typecheck. jambo-7d lost time to the same
   * symbol an hour before I did, which by this repo's own rule makes it a
   * pattern rather than an incident.
   */
  loading: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.background,
  },

  foot: { paddingHorizontal: spacing.lg, paddingTop: spacing.md },
  footText: { ...typography.caption, color: colors.textMuted, textAlign: 'center' },
});
