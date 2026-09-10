import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Dimensions, Linking, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Image as ExpoImage } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import {
  CaretRight,
  Check,
  Crown,
  DeviceMobileCamera,
  DownloadSimple,
  Gift,
  PlayCircle,
  Receipt,
  type Icon,
} from 'phosphor-react-native';

import { useFocusEffect } from '@react-navigation/native';

import { api } from '../api/jambo';
import { ApiError, NetworkError } from '../api/errors';
import { fetchOrder, startCheckout, type Checkout } from '../features/billing/api';
import { CheckoutSheet } from '../features/billing/CheckoutSheet';
import type { Plan, Subscription, TitleCard } from '../api/catalogue';
import { CAN_SUBSCRIBE_IN_APP } from '../config/env';
import { renewalLine } from '../ui/format';
import { imageUrl } from '../ui/media';
import { badge, colors, fonts, plans as planTokens, spacing, typography } from '../ui/theme';
import { Alert, Caption, ErrorState, Loading } from '../ui/components';
import { ListCard, ListRow } from '../ui/list';
import { Focusable } from '../ui/rails/Focusable';
import type { AppScreenProps } from '../navigation/types';

/**
 * Membership: what a plan costs, what it includes, and what you are on.
 *
 * This is `Pages/pricing-page.blade.php` and the top half of
 * `profile-hub/membership.blade.php`, brought onto a phone to Rio's mockup of
 * 2026-09-09. The mockup's layout, the website's components, the server's
 * figures — in that order of authority, which is §4a of the `screen` skill.
 *
 * **Every card value is captured, not eyeballed.** `.pricing-plan-wrapper`,
 * `.plan-main-price`, `.pricing-plan-discount` and `.jambo-period-tabs` are
 * probed by `design/export-tokens.mjs` and land in the `plans` block of
 * `theme.ts`. The first cut of this screen drew a plan as a list row because
 * the ladder was a placeholder; this one wears the site's card.
 *
 * **The mockup is crimson and the brand is blue**, the third time that
 * substitution has been made here. Rio's ruling stands: his layout, Jambo's
 * colours.
 *
 * **Four rules the website enforces and this screen inherits rather than
 * re-invents:**
 *
 *  - Free is never a card. Every signed-in viewer already holds it, so it is
 *    the strip at the top rather than something to "select".
 *  - Only periods that actually have paid tiers get a tab. A Weekly tab on a
 *    catalogue with no weekly plan is a tab onto nothing.
 *  - The "Most popular" ribbon is the server's `is_popular`, decided by
 *    `SubscriptionTier::popularFrom` — the same call the website's own page
 *    makes, so the two surfaces cannot mark different plans.
 *  - The default tab is the viewer's own period, then monthly, then whatever
 *    exists. Landing on a tab that does not contain the plan you are paying
 *    for is the small disorientation the website already avoids.
 *
 * **Read-only, and there is no Subscribe button.** Two independent reasons,
 * either sufficient: ADR-0004 makes the Play build consumption-only, because
 * Play's Payments policy requires Play Billing for video subscriptions outside
 * IN/KR/EEA/US and Uganda has no exception; and the `direct` build's PesaPal
 * checkout does not exist server-side yet, because it needs the website's
 * server-authored pricing, frozen price snapshot and referral discount pulled
 * into a shared service — money code earning its own slice. So the card's
 * button states where payment happens instead of pretending to take it.
 * `CAN_SUBSCRIBE_IN_APP` is read here so that when the direct flow lands, this
 * is the one place that changes.
 */

/** The three promises in the mockup's chip row, and where each one is true. */
const BENEFITS: { icon: Icon; label: string }[] = [
  { icon: PlayCircle, label: 'Ad-free streaming' },
  { icon: DownloadSimple, label: 'Download & watch offline' },
  { icon: DeviceMobileCamera, label: 'Watch on multiple devices' },
];

/** The tab order the website uses, shortest period last. */
const PERIOD_ORDER = ['monthly', 'weekly', 'daily', 'yearly'] as const;

const PERIOD_LABEL: Record<string, string> = {
  daily: 'Daily',
  weekly: 'Weekly',
  monthly: 'Monthly',
  yearly: 'Yearly',
};

export function PlansScreen({ navigation }: AppScreenProps<'Plans'>) {
  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['subscription'],
    queryFn: () => api.subscription(),
  });

  /*
   * The hero's backdrop, borrowed from the home rail rather than shipped as an
   * asset.
   *
   * Same query key the home screen uses, so on any normal path to this screen
   * it is already in cache and this costs nothing. A viewer who deep-links
   * here pays one request for it, and the hero renders without it in the
   * meantime — the gradient carries the headline on its own.
   *
   * **It is a real title from the catalogue, not a stock photograph.** The
   * mockup shows a face the product does not have, and the honest way to fill
   * that space is with content the viewer can actually watch.
   */
  const home = useQuery({ queryKey: ['home'], queryFn: () => api.home() });

  const [period, setPeriod] = useState<string | null>(null);

  /** The plan whose checkout is being opened, so only its button says so. */
  const [pending, setPending] = useState<string | null>(null);

  /** The checkout being shown in the popup, or null when none is. */
  const [checkout, setCheckout] = useState<Checkout | null>(null);
  const [checkoutError, setCheckoutError] = useState<string | null>(null);

  /**
   * Progress, not failure.
   *
   * 🔴 **Rio, 2026-09-10, after paying successfully: the screen still read
   * "Confirming your payment…" in the red error box while the card beside it
   * already said Current plan.** Two bugs in one line. It was written to
   * `checkoutError`, so a normal step of a working payment was dressed as a
   * fault; and nothing ever cleared it, so it outlived the thing it described.
   *
   * The message itself earns its place: mobile money is a USSD prompt and the
   * gateway can confirm to the server seconds after the sheet closes, so the
   * gap between "I paid" and "my plan is on" is real and has to be narrated.
   * It just has to be narrated as waiting, and it has to stop.
   */
  const [checkoutNotice, setCheckoutNotice] = useState<string | null>(null);

  /**
   * The reference of an order we opened and have not yet seen finish.
   *
   * Held in a ref rather than state because it must survive the app being
   * backgrounded while somebody pays, and reading it back on focus is not a
   * render. `null` means there is nothing outstanding to ask about.
   */
  const awaiting = useRef<string | null>(null);

  /** The pending retry, cleared on unmount so it cannot fire into a dead screen. */
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(
    () => () => {
      if (timer.current !== null) clearTimeout(timer.current);
    },
    [],
  );

  const queryClient = useQueryClient();

  /**
   * Start a payment: ask the server for a checkout, then leave the app.
   *
   * 🔴 **The server owns the money and the gateway; this owns a button.** The
   * request names a plan and nothing else — no amount, no currency, no gateway
   * — so a second gateway is a server change the app never learns about. Rio's
   * instruction of 2026-09-10, and it is also what makes the price
   * untamperable from here.
   *
   * **`Linking` rather than a Custom Tab, for now.** `expo-web-browser` would
   * keep the payment inside a tab the app controls, and it is a native module:
   * adding it means rebuilding the dev client, which would have stopped
   * another session mid-verification tonight. The system browser completes the
   * payment identically because the gateway confirms to the SERVER, not to the
   * page. Swapping it in later is one call site.
   */
  const subscribe = useCallback(
    async (tierSlug: string) => {
      setPending(tierSlug);
      setCheckoutError(null);
      setCheckoutNotice(null);

      try {
        const started = await startCheckout(tierSlug);

        awaiting.current = started.reference;

        /*
         * 🔴 **The payment happens inside the app.** Rio, 2026-09-10: *"we
         * don't have to send people outside the app"* and *"we are supposed to
         * use a popup, not a redirect."* Handing somebody to the system
         * browser mid-purchase means they come back by remembering to, or they
         * do not come back.
         *
         * **`redirect` is still honoured**, and that is the contract rather
         * than a leftover: the server chooses the mode per order, so a gateway
         * that cannot be framed still sells, and an older build that does not
         * know a newer mode falls through to a URL it can always open. That is
         * what stops a future integration needing an app release.
         */
        if (started.checkout.mode === 'redirect') {
          const opened = await Linking.canOpenURL(started.checkout.url);

          if (!opened) {
            throw new Error('no browser');
          }

          await Linking.openURL(started.checkout.url);
          return;
        }

        setCheckout(started.checkout);
      } catch (caught) {
        awaiting.current = null;
        setCheckoutError(
          caught instanceof NetworkError
            ? 'Could not reach Jambo. Check your connection and try again.'
            : caught instanceof ApiError && caught.message !== ''
              ? caught.message
              : 'We could not start the payment. Try again in a moment.',
        );
      } finally {
        setPending(null);
      }
    },
    [],
  );

  /**
   * Ask the server, repeatedly, until it has an answer.
   *
   * 🔴 **Rio, 2026-09-10: "I thought on completing the order the page
   * reloads."** It did not, and the reason is a real mistake rather than a
   * missing nicety: the refresh was wired to `useFocusEffect`, which fires
   * when a SCREEN regains navigation focus. Closing a modal is not that — the
   * screen never lost focus — so the poll never ran and the card only changed
   * when he touched something that happened to refetch.
   *
   * So the close starts a poll. **Mobile money is why it is a poll and not a
   * single request**: the payer approves on a USSD prompt and PesaPal confirms
   * to the server seconds later, so the answer at the moment the sheet closes
   * is usually still `pending`. Asking once and stopping is how a paid
   * subscription sits there looking unpaid.
   *
   * Every three seconds for a minute, then it stops and says so rather than
   * spinning forever. The server remains the authority throughout — this only
   * asks, it never decides.
   */
  const pollUntilSettled = useCallback(
    (reference: string) => {
      let attempts = 0;

      const ask = async (): Promise<void> => {
        attempts += 1;

        try {
          const order = await fetchOrder(reference);

          if (order.status === 'completed') {
            awaiting.current = null;
            setCheckoutNotice(null);
            setCheckoutError(null);
            await queryClient.invalidateQueries({ queryKey: ['subscription'] });
            await queryClient.invalidateQueries({ queryKey: ['me'] });
            return;
          }

          if (order.status === 'failed' || order.status === 'cancelled') {
            awaiting.current = null;
            setCheckoutNotice(null);
            setCheckoutError('That payment did not go through. Nothing was charged.');
            return;
          }
        } catch {
          /*
           * A failed check is not a failed payment. Keep asking — the
           * tri-state rule: only an explicit answer ends this.
           */
        }

        if (attempts >= 20) {
          setCheckoutNotice(
            'Still waiting for your payment to clear. It will appear here once it does.',
          );
          return;
        }

        timer.current = setTimeout(() => void ask(), 3000);
      };

      void ask();
    },
    [queryClient],
  );

  /**
   * When the viewer comes back, ask the server what happened.
   *
   * **The phone is not the authority and must not be.** PesaPal confirms to
   * the server; a handset that never returns, or returns on a dead battery,
   * must not be what decides whether a subscription starts. So this asks once
   * on focus rather than polling a timer, and the answer it trusts is the
   * order row.
   *
   * A `pending` order on return is the normal case for mobile money, where the
   * payer approves on a USSD prompt seconds later — so it says so rather than
   * calling it a failure, and the next visit asks again.
   */
  useFocusEffect(
    useCallback(() => {
      const reference = awaiting.current;

      if (reference === null) return;

      let cancelled = false;

      void fetchOrder(reference)
        .then((order) => {
          if (cancelled) return;

          if (order.status === 'completed') {
            awaiting.current = null;
            /*
             * Clear the waiting line before refreshing, so the screen never
             * shows "Confirming your payment…" beside a card that already
             * says Current plan — which is exactly what Rio saw.
             */
            setCheckoutNotice(null);
            setCheckoutError(null);
            void queryClient.invalidateQueries({ queryKey: ['subscription'] });
            void queryClient.invalidateQueries({ queryKey: ['me'] });
          } else if (order.status === 'failed' || order.status === 'cancelled') {
            awaiting.current = null;
            setCheckoutNotice(null);
            setCheckoutError('That payment did not go through. Nothing was charged.');
          } else {
            // Still a wait, still not a fault.
            setCheckoutNotice('Payment is still being confirmed. This screen will update once it is.');
          }
        })
        .catch(() => {
          /*
           * A failed check is not a failed payment, and saying so would be the
           * worst kind of wrong. `awaiting` is left set so the next visit asks
           * again — the tri-state rule: the destructive branch runs only on an
           * explicit no.
           */
        });

      return () => {
        cancelled = true;
      };
    }, [queryClient]),
  );

  const groups = useMemo(() => groupByPeriod(data?.plans ?? []), [data?.plans]);

  if (isPending) return <Loading label="Loading plans" />;

  if (isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load the plans.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </View>
    );
  }

  const current = data.subscription;
  const currentSlug = current?.tier?.slug;
  const periods = PERIOD_ORDER.filter((key) => (groups[key]?.length ?? 0) > 0);

  /*
   * The tab in force: the viewer's explicit choice, else the period they are
   * already paying for, else monthly, else whatever exists.
   */
  const currentPeriod = data.plans?.find((plan) => plan.slug === currentSlug)?.billing_period;
  const active =
    (period !== null && periods.includes(period as (typeof PERIOD_ORDER)[number]) ? period : null) ??
    (typeof currentPeriod === 'string' && periods.includes(currentPeriod as (typeof PERIOD_ORDER)[number])
      ? currentPeriod
      : null) ??
    periods[0] ??
    null;

  const shown = active === null ? [] : (groups[active] ?? []);
  const yearly = groups.yearly ?? [];
  const backdrop = home.data?.hero?.[0] as TitleCard | undefined;

  return (
    <View style={styles.screen}>
      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        <Hero poster={backdrop?.poster_url} />

        <View style={styles.benefits}>
          {BENEFITS.map((benefit) => (
            <Benefit key={benefit.label} icon={benefit.icon} label={benefit.label} />
          ))}
        </View>

        <CurrentStrip subscription={current} />

        {checkoutNotice === null ? null : (
          <View style={styles.checkoutError}>
            <Alert tone="ok">{checkoutNotice}</Alert>
          </View>
        )}

        {checkoutError === null ? null : (
          <View style={styles.checkoutError}>
            <Alert tone="error">{checkoutError}</Alert>
          </View>
        )}

        {periods.length === 0 ? (
          <Caption>No plans are published at the moment.</Caption>
        ) : (
          <>
            {/*
              One tab is not a choice. The website draws the bar unconditionally
              because a browser has the room; a phone does not, and a segmented
              control with a single segment is a label pretending to be a
              control.
            */}
            {periods.length > 1 ? (
              <View style={styles.tabs} accessibilityRole="tablist">
                {periods.map((key) => (
                  <PeriodTab
                    key={key}
                    label={PERIOD_LABEL[key] ?? key}
                    on={key === active}
                    onPress={() => setPeriod(key)}
                  />
                ))}
              </View>
            ) : null}

            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={[
                styles.ladder,
                /*
                 * A period with one or two plans centres, which is the
                 * website's own `row justify-content-center` rather than a
                 * decision taken here. Rendered left-aligned first: a single
                 * Weekly card hard against the left margin with two thirds of
                 * the screen empty beside it reads as a layout that failed to
                 * load the rest.
                 */
                shown.length < 3 && styles.ladderCentred,
              ]}
              /* Snap so a card always lands square, the way a rail does. */
              snapToInterval={CARD_WIDTH + spacing.sm}
              decelerationRate="fast"
            >
              {shown.map((plan) => (
                <PlanCard
                  key={plan.slug ?? plan.name}
                  plan={plan}
                  current={plan.slug !== undefined && plan.slug === currentSlug}
                  {...(plan.slug === undefined
                    ? {}
                    : {
                        onSubscribe: () => void subscribe(plan.slug as string),
                        subscribing: pending === plan.slug,
                      })}
                />
              ))}
            </ScrollView>
          </>
        )}

        {/*
          The yearly nudge, and it only appears when a yearly plan exists.

          The mockup promises "up to 2 months free". That number is not the
          app's to state — it is arithmetic across two prices, and a plan whose
          maths stops working would leave the screen making a claim the
          catalogue does not support. The real yearly price does the same job
          and cannot go stale.
        */}
        {yearly.length > 0 && active !== 'yearly' ? (
          <YearlyBanner plans={yearly} onPress={() => setPeriod('yearly')} />
        ) : null}

        <ListCard style={styles.history}>
          <ListRow
            icon={Receipt}
            label="Order history"
            detail="Every charge on your account"
            last
            onPress={() => navigation.navigate('Billing')}
          />
        </ListCard>

        <Text style={styles.note}>
          {CAN_SUBSCRIBE_IN_APP
            ? 'Payment is taken by our payment provider. Your plan starts once it clears.'
            : 'Plans are managed on the Jambo website. This app does not take payments.'}
        </Text>
      </ScrollView>

      {/*
        The popup. It knows nothing about PesaPal — it renders whatever the
        server described and reports which of Jambo's own URLs ended the flow.
      */}
      <CheckoutSheet
        checkout={checkout}
        onClose={() => {
          setCheckout(null);

          /*
           * Reaching the return URL is not proof of payment. Mobile money is a
           * USSD prompt that can be approved after the page has moved on, so
           * the order is asked about rather than assumed — and a cancel leaves
           * `awaiting` set too, because a payer who backed out of the page may
           * still complete on their handset.
           */
          const reference = awaiting.current;

          /*
           * A cancel polls too. Somebody who backed out of the page may still
           * approve the USSD prompt on their handset a moment later, and
           * treating their own hesitation as a refusal would leave a paid
           * account looking unpaid.
           */
          if (reference !== null) {
            setCheckoutNotice('Confirming your payment…');
            pollUntilSettled(reference);
          }

          void refetch();
        }}
      />
    </View>
  );
}

/**
 * The headline block.
 *
 * The mockup's composition: a portrait image bled into the right edge, the
 * headline over it in three weights, and the whole thing tied to the page by a
 * gradient that ends in the screen's own background rather than in a hard
 * line.
 *
 * With no image it is still a headline on a gradient. That is the state a cold
 * launch renders for a moment, and it has to look deliberate rather than
 * broken.
 */
function Hero({ poster }: { poster?: string | null | undefined }) {
  return (
    <View style={styles.hero}>
      {typeof poster === 'string' && poster !== '' ? (
        <ExpoImage
          source={imageUrl(poster, Math.round(SCREEN_WIDTH * 0.6))}
          style={styles.heroImage}
          contentFit="cover"
          contentPosition="top center"
          transition={200}
          /* Decoration. The headline beside it is the content. */
          accessible={false}
        />
      ) : null}

      {/*
        Two scrims, not one. The horizontal pass keeps the headline legible
        over whatever poster the catalogue happens to be leading with; the
        vertical pass lands the image into the page. A single diagonal gradient
        does neither job properly.
      */}
      <LinearGradient
        colors={['rgba(0,0,0,0.95)', 'rgba(0,0,0,0.75)', 'rgba(0,0,0,0.1)']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={StyleSheet.absoluteFill}
      />
      <LinearGradient
        colors={['transparent', 'rgba(0,0,0,0.85)', colors.background]}
        style={StyleSheet.absoluteFill}
      />

      <View style={styles.heroCopy}>
        <Text style={styles.heroEyebrow}>UNLOCK A</Text>
        <Text style={styles.heroLine}>BIGGER</Text>
        <Text style={[styles.heroLine, styles.heroLineAccent]}>ENTERTAINMENT</Text>
        <Text style={styles.heroLine}>EXPERIENCE</Text>
        <Text style={styles.heroSub}>More movies, exclusive content, no ads.</Text>
      </View>
    </View>
  );
}

function Benefit({ icon: Glyph, label }: { icon: Icon; label: string }) {
  return (
    <View style={styles.benefit} accessible accessibilityLabel={label}>
      <View style={styles.benefitChip}>
        <Glyph size={22} color={colors.primary} weight="fill" />
      </View>
      <Text style={styles.benefitLabel} numberOfLines={2}>
        {label}
      </Text>
    </View>
  );
}

/**
 * The strip the website puts above its pricing grid.
 *
 * `.jambo-strip` with a crown, the plan's name, and the date it runs to. It is
 * the whole of the membership screen's "Current plan" card that a viewer
 * standing in front of a price list actually needs: what am I on, and until
 * when.
 *
 * **"Active until" rather than "Renews" when auto-renew is off.** The website
 * says Renews unconditionally; that word is only true of a subscription that
 * is going to be charged again, and telling somebody they are about to be
 * billed when they are not is the one error on this screen that costs money.
 */
function CurrentStrip({ subscription }: { subscription: Subscription | null }) {
  const name = subscription?.tier?.name;
  /* Shared with the profile menu's Membership card, so the two surfaces
     cannot disagree about the word or the date. Null for a plan with no end
     date, which is drawn as no line rather than as "Renews —". */
  const renews = renewalLine(subscription?.ends_at, subscription?.auto_renew);

  return (
    <View
      style={styles.strip}
      accessible
      accessibilityLabel={
        subscription === null
          ? 'You have no active membership.'
          : `Current plan, ${name ?? 'unknown'}.${renews === null ? '' : ` ${renews}.`}`
      }
    >
      <Crown size={22} color={colors.primary} weight="fill" />
      <View style={styles.stripText}>
        <Text style={styles.stripTitle} numberOfLines={1}>
          {subscription === null ? 'No active membership' : `Current plan: ${name ?? '—'}`}
        </Text>
        {subscription === null || renews === null ? null : (
          <Text style={styles.stripMeta} numberOfLines={1}>
            {renews}
          </Text>
        )}
      </View>
      {subscription !== null ? (
        <Text style={[styles.badge, styles.badgeOk]}>{statusWord(subscription.status)}</Text>
      ) : null}
    </View>
  );
}

function PeriodTab({ label, on, onPress }: { label: string; on: boolean; onPress: () => void }) {
  return (
    <Focusable
      accessibilityLabel={label}
      accessibilityRole="tab"
      accessibilityState={{ selected: on }}
      onPress={onPress}
      ringRadius={planTokens.tabRadius}
      style={styles.tabWrap}
    >
      <View style={[styles.tab, on && styles.tabOn]}>
        <Text style={[styles.tabText, on && styles.tabTextOn]} numberOfLines={1}>
          {label}
        </Text>
      </View>
    </Focusable>
  );
}

/**
 * One plan, as `.pricing-plan-wrapper` draws it.
 *
 * The ribbon, the name and crown, the price with its period underneath, the
 * ticked feature list, and a footer button. Every colour, size and radius in
 * here comes from the `plans` token block, which comes from the rendered
 * pricing page.
 *
 * **The crown is keyed to access level, not to position.** The mockup gives
 * the middle card a crown and the right-hand one a gold crown; keying that to
 * "the third card" would put a gold crown on Basic the day an admin reorders
 * the ladder. Level 2 and above wears the site's own premium yellow, which is
 * the colour its catalogue badge already uses for exactly this idea.
 */
function PlanCard({
  plan,
  current,
  onSubscribe,
  subscribing,
}: {
  plan: Plan;
  current: boolean;
  /** Undefined on a build that may not sell. See the footer below. */
  onSubscribe?: (() => void) | undefined;
  subscribing?: boolean | undefined;
}) {
  const features = Array.isArray(plan.features) ? plan.features : [];
  const popular = plan.is_popular === true && !current;
  const premium = (plan.access_level ?? 0) >= 2;

  return (
    <View
      style={[styles.card, popular && styles.cardPopular, current && styles.cardCurrent]}
      accessible
      accessibilityLabel={[
        plan.name,
        priceOf(plan),
        periodOf(plan),
        current ? 'your current plan' : null,
        popular ? 'most popular' : null,
        features.length > 0 ? `Includes ${features.join(', ')}` : null,
      ]
        .filter((part): part is string => typeof part === 'string' && part !== '')
        .join('. ')}
    >
      {/*
        The ribbon occupies the card's full width at the top, exactly as
        `.pricing-plan-discount` does. On the card that has none, the space is
        not reserved — the site does not reserve it either, and a phone has
        less room to spend on an empty strip.
      */}
      {popular ? (
        <View style={styles.ribbon}>
          <Text style={styles.ribbonText}>Most popular</Text>
        </View>
      ) : null}

      <View style={styles.cardBody}>
        <View style={styles.cardHead}>
          <Text style={styles.name} numberOfLines={2}>
            {plan.name}
          </Text>
          <Crown
            size={14}
            weight="fill"
            color={premium ? badge.warnBg : colors.primary}
          />
        </View>

        <Text style={styles.price} numberOfLines={1}>
          {priceOf(plan)}
        </Text>
        <Text style={styles.period}>{periodOf(plan)}</Text>

        {current ? <Text style={[styles.badge, styles.badgeOk, styles.cardTag]}>Current plan</Text> : null}

        <View style={styles.features}>
          {features.map((feature) => (
            <View key={feature} style={styles.feature}>
              <Check
                size={11}
                color={planTokens.featureIconColor}
                weight="bold"
              />
              <Text style={styles.featureText}>{feature}</Text>
            </View>
          ))}
        </View>

        {/*
          A button on the build that may sell, a label on the one that may not.

          🔴 **ADR-0004 draws this line and Google's Payments policy is
          why.** Play Billing is required for subscription video outside
          IN/KR/EEA/US, and leading a Play user to another payment method is a
          listing-removal risk — so the Play build states where payment happens
          and offers nothing pressable. The direct APK, downloaded from
          jambofilms.com, takes PesaPal.

          `CAN_SUBSCRIBE_IN_APP` is the whole gate, and it defaults to the safe
          side: an unconfigured build hides a checkout that should be there,
          which is a bug report, rather than showing one that must not be,
          which costs the listing.
        */}
        {current ? (
          <View style={[styles.cta, styles.ctaQuiet]}>
            <Text style={[styles.ctaText, styles.ctaTextQuiet]}>Your plan</Text>
          </View>
        ) : CAN_SUBSCRIBE_IN_APP && onSubscribe !== undefined ? (
          <Focusable
            accessibilityLabel={`Subscribe to ${plan.name}, ${priceOf(plan)} ${periodOf(plan)}`}
            accessibilityRole="button"
            accessibilityState={{ busy: subscribing === true }}
            onPress={onSubscribe}
            ringRadius={planTokens.ctaRadius}
          >
            <View style={styles.cta}>
              <Text style={styles.ctaText}>{subscribing === true ? 'Opening…' : 'Subscribe'}</Text>
            </View>
          </Focusable>
        ) : (
          <View style={styles.cta}>
            <Text style={styles.ctaText}>On the website</Text>
          </View>
        )}
      </View>
    </View>
  );
}

/**
 * "Save more with a yearly plan", pointing at the tab that holds them.
 *
 * The mockup's copy promises up to two months free. That figure is arithmetic
 * across a yearly price and a monthly one, and an app that states it will keep
 * stating it after an admin changes either. The cheapest real yearly price
 * makes the same case out of a number the server actually sent.
 */
function YearlyBanner({ plans: yearly, onPress }: { plans: Plan[]; onPress: () => void }) {
  const cheapest = yearly.reduce<Plan | null>((best, plan) => {
    const value = Number(plan.price);
    if (!Number.isFinite(value)) return best;
    const bestValue = best === null ? Number.POSITIVE_INFINITY : Number(best.price);
    return value < bestValue ? plan : best;
  }, null);

  const from = cheapest === null ? null : `From ${priceOf(cheapest)} ${periodOf(cheapest)}`;

  return (
    <Focusable
      accessibilityLabel={`Save more with a yearly plan. ${from ?? ''}`}
      accessibilityRole="button"
      onPress={onPress}
      ringRadius={planTokens.stripRadius}
      style={styles.yearlyWrap}
    >
      <View style={styles.yearly}>
        <Gift size={24} color={colors.primary} weight="fill" />
        <View style={styles.yearlyText}>
          <Text style={styles.yearlyTitle}>Save more with a yearly plan</Text>
          {from === null ? null : <Text style={styles.yearlyMeta}>{from}</Text>}
        </View>
        <CaretRight size={18} color={colors.textMuted} weight="bold" />
      </View>
    </Focusable>
  );
}

/** Paid plans, bucketed by billing period. Free is never a card, as on the site. */
function groupByPeriod(all: Plan[]): Record<string, Plan[]> {
  const groups: Record<string, Plan[]> = {};

  for (const plan of all) {
    if (Number(plan.price) <= 0) continue;

    const key = typeof plan.billing_period === 'string' ? plan.billing_period : 'monthly';
    (groups[key] ??= []).push(plan);
  }

  return groups;
}

/**
 * The price, exactly as the server states it.
 *
 * `price` arrives as a decimal string (`"15000.00"`) and is rendered, never
 * recomputed: money a client has done arithmetic on is money the viewer cannot
 * check against an invoice. The thousands separator is presentation and is
 * inserted here; the digits are the server's.
 *
 * A plan with **no** price shows an em dash — printing `UGX 0` for a figure
 * nobody has would say "free" when the truth is "unknown".
 */
function priceOf(plan: Plan): string {
  const raw = plan.price;
  if (typeof raw !== 'string' || raw === '') return '—';

  const currency = typeof plan.currency === 'string' ? plan.currency : '';
  const amount = raw.replace(/\.00$/, '');

  if (Number(amount) === 0) return 'Free';

  const grouped = amount.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

  return `${currency} ${grouped}`.trim();
}

/**
 * The line under the price.
 *
 * `period_label` is authored server-side by `SubscriptionTier::periodLabel`,
 * which is the same string the website prints. Deriving "per month" from
 * `billing_period` here would be a second copy of a mapping that already
 * exists, and the copy that gets missed when a period is added.
 */
function periodOf(plan: Plan): string {
  const label = plan.period_label;

  return typeof label === 'string' && label !== '' ? label : '';
}

/** `ucfirst($status ?? 'active')`, exactly as the blade does it. */
function statusWord(status: string | null | undefined): string {
  const word = typeof status === 'string' && status !== '' ? status : 'active';

  return word.charAt(0).toUpperCase() + word.slice(1);
}

const SCREEN_WIDTH = Dimensions.get('window').width;

/**
 * A third of the width, which is the mockup's three-abreast ladder.
 *
 * Measured before it was drawn, because the first cut assumed it would not
 * fit and gave each card three-quarters of the screen. On this emulator the
 * viewport is 426dp, so a card is 126dp — and "UGX 30,000" at the type scale
 * below is 108dp of it. It fits, and a viewer comparing three plans should
 * not have to swipe between them to do it.
 *
 * The ladder still scrolls, because a catalogue with four paid monthly tiers
 * is a catalogue the admin is allowed to have.
 */
const CARD_WIDTH = Math.floor((SCREEN_WIDTH - spacing.lg * 2 - spacing.sm * 2) / 3);

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { paddingBottom: spacing.xxl },

  /* ── hero ─────────────────────────────────────────────────────────── */
  hero: { height: 300, justifyContent: 'flex-end', backgroundColor: colors.background },
  heroImage: {
    position: 'absolute',
    right: 0,
    top: 0,
    bottom: 0,
    width: '62%',
  },
  heroCopy: { paddingHorizontal: spacing.lg, paddingBottom: spacing.lg, gap: 2 },
  heroEyebrow: {
    fontFamily: fonts.medium,
    fontSize: 11,
    letterSpacing: 3,
    color: colors.textMuted,
    marginBottom: spacing.xs,
  },
  heroLine: {
    fontFamily: fonts.black,
    fontSize: 30,
    lineHeight: 34,
    color: colors.text,
  },
  heroLineAccent: { color: colors.primary },
  heroSub: {
    ...typography.caption,
    color: colors.textMuted,
    marginTop: spacing.sm,
    maxWidth: '72%',
  },

  /* ── the three promises ───────────────────────────────────────────── */
  benefits: {
    flexDirection: 'row',
    paddingHorizontal: spacing.lg,
    gap: spacing.sm,
    marginBottom: spacing.xl,
  },
  benefit: { flex: 1, alignItems: 'center', gap: spacing.sm },
  benefitChip: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: planTokens.tabBg,
    borderWidth: planTokens.cardBorderWidth,
    borderColor: planTokens.cardBorder,
  },
  benefitLabel: {
    fontFamily: fonts.regular,
    fontSize: 11,
    lineHeight: 14,
    textAlign: 'center',
    color: colors.textMuted,
  },

  /* ── the current-plan strip ───────────────────────────────────────── */
  strip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginHorizontal: spacing.lg,
    padding: planTokens.stripPad,
    borderRadius: planTokens.stripRadius,
    backgroundColor: planTokens.stripBg,
    borderWidth: 1,
    borderColor: planTokens.stripBorder,
  },
  stripText: { flex: 1, gap: 2 },
  stripTitle: { fontFamily: fonts.medium, fontSize: 15, color: colors.text },
  stripMeta: { ...typography.caption, color: colors.textMuted },

  checkoutError: { marginHorizontal: spacing.lg, marginTop: spacing.lg },

  /* ── period tabs ──────────────────────────────────────────────────── */
  tabs: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.xl,
    marginHorizontal: spacing.lg,
  },
  tabWrap: { flex: 1 },
  tab: {
    paddingVertical: planTokens.tabPadY,
    paddingHorizontal: spacing.sm,
    borderRadius: planTokens.tabRadius,
    backgroundColor: planTokens.tabBg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabOn: { backgroundColor: planTokens.tabActiveBg },
  tabText: {
    fontFamily: fonts.medium,
    fontSize: planTokens.tabSize,
    color: planTokens.tabFg,
  },
  tabTextOn: { color: planTokens.tabActiveFg },

  /* ── the ladder ───────────────────────────────────────────────────── */
  /*
   * `alignItems: stretch` is what makes the three cards one object rather than
   * three. Without it each card is as tall as its own feature list, so a plan
   * with four bullets sits a centimetre short of the one beside it and the
   * row reads as broken rather than as different. The site gets this free from
   * its grid row; a horizontal ScrollView has to be told.
   */
  ladder: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
    gap: spacing.sm,
    alignItems: 'stretch',
  },
  ladderCentred: { flexGrow: 1, justifyContent: 'center' },

  card: {
    width: CARD_WIDTH,
    borderRadius: planTokens.cardRadius,
    backgroundColor: planTokens.cardBg,
    borderWidth: planTokens.cardBorderWidth,
    borderColor: planTokens.cardBorder,
    overflow: 'hidden',
  },
  cardPopular: { borderColor: planTokens.ribbonBg },
  cardCurrent: { borderColor: badge.okBg },
  /*
   * `flex: 1` on the body, and on the feature list inside it, is what pins
   * every card's button to the same line. Without it the button sits directly
   * under the last bullet and the three cards end in three different places.
   */
  cardBody: { flex: 1, padding: 10, gap: 2 },

  cardHead: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs },
  /*
   * The site sets the name at 20px and the price at 38. Both come down here,
   * and the reason is the width rather than taste: a 126dp card cannot hold
   * 38px of "UGX 150,000" without wrapping the currency onto its own line,
   * and a wrapped price is harder to read than a smaller one. Everything else
   * about them — the weight, the colour, the order, the period underneath —
   * is the site's, captured.
   */
  name: {
    flex: 1,
    fontFamily: fonts.medium,
    fontSize: 12,
    lineHeight: 15,
    color: planTokens.nameColor,
  },

  /*
   * The site's price is 38px at weight 400 — large and light. It is scaled to
   * the card's width here rather than held at 38, because 38px of "UGX 15,000"
   * does not fit a 300pt card and a price that wraps is worse than a price
   * that is a size smaller. Everything else about it is the site's.
   */
  price: {
    fontFamily: fonts.medium,
    fontSize: 16,
    lineHeight: 21,
    color: planTokens.priceColor,
    marginTop: spacing.xs,
  },
  period: {
    fontFamily: fonts.regular,
    fontSize: 11,
    lineHeight: 15,
    color: planTokens.periodColor,
  },

  features: { flex: 1, gap: 6, marginTop: spacing.md },
  feature: { flexDirection: 'row', alignItems: 'flex-start', gap: 5 },
  featureText: {
    flex: 1,
    fontFamily: fonts.regular,
    /*
     * The site sets 18px here. On a phone card that is a bullet list set
     * larger than the body text of every other screen, so it comes down to
     * the app's own caption size. This is the "translating a layout" half of
     * §4a — the colour, the tick and the spacing are the site's; the type
     * scale is the phone's.
     */
    fontSize: 10.5,
    lineHeight: 14,
    color: planTokens.featureColor,
  },

  ribbon: {
    paddingVertical: 5,
    alignItems: 'center',
    backgroundColor: planTokens.ribbonBg,
  },
  ribbonText: {
    fontFamily: fonts.medium,
    fontSize: 9.5,
    letterSpacing: 0.6,
    textTransform: 'uppercase',
    color: planTokens.ribbonFg,
  },

  cta: {
    marginTop: spacing.md,
    height: 34,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: planTokens.ctaRadius,
    backgroundColor: planTokens.ctaBg,
  },
  ctaQuiet: {
    backgroundColor: 'transparent',
    borderWidth: 1,
    borderColor: planTokens.ctaQuietBorder,
  },
  ctaText: {
    fontFamily: fonts.medium,
    fontSize: 11,
    color: planTokens.ctaFg,
  },
  ctaTextQuiet: { color: planTokens.ctaQuietFg },

  cardTag: {
    alignSelf: 'flex-start',
    marginTop: spacing.xs,
    fontSize: 9,
    lineHeight: 13,
    paddingHorizontal: 5,
    paddingVertical: 2,
  },

  /* ── the yearly nudge ─────────────────────────────────────────────── */
  yearlyWrap: { marginTop: spacing.xl, marginHorizontal: spacing.lg },
  yearly: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: planTokens.stripPad,
    borderRadius: planTokens.stripRadius,
    backgroundColor: planTokens.stripBg,
    borderWidth: 1,
    borderColor: planTokens.stripBorder,
  },
  yearlyText: { flex: 1, gap: 2 },
  yearlyTitle: { fontFamily: fonts.medium, fontSize: 15, color: colors.text },
  yearlyMeta: { ...typography.caption, color: colors.textMuted },

  /* ── the rest of the screen ───────────────────────────────────────── */
  history: { marginTop: spacing.xl, marginHorizontal: spacing.lg },
  note: {
    ...typography.caption,
    color: colors.textMuted,
    marginTop: spacing.lg,
    marginHorizontal: spacing.lg,
  },

  badge: {
    fontSize: badge.size,
    fontWeight: String(badge.weight) as '700',
    lineHeight: badge.size + 4,
    paddingHorizontal: badge.padX,
    paddingVertical: badge.padY,
    borderRadius: badge.radius,
    overflow: 'hidden',
  },
  badgeOk: { backgroundColor: badge.okBg, color: badge.okFg },
});
