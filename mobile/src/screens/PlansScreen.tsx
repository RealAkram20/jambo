import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';

import { api } from '../api/jambo';
import type { Plan } from '../api/catalogue';
import { CAN_SUBSCRIBE_IN_APP } from '../config/env';
import { card, colors, fonts, radius, spacing, typography } from '../ui/theme';
import { Caption, ErrorState, Loading } from '../ui/components';
import type { AppScreenProps } from '../navigation/types';

/**
 * The plans, and which one this viewer is on.
 *
 * **Read-only on both build variants, and there is no Subscribe button.**
 * Two separate reasons, and either alone is enough:
 *
 *  - ADR-0004: the Google Play build is consumption-only. Play's Payments
 *    policy requires Play Billing for video subscriptions outside
 *    IN/KR/EEA/US, Uganda has no exception, and a button leading to a payment
 *    is a listing-removal risk rather than a bug.
 *  - The `direct` build's PesaPal checkout **does not exist server-side yet**
 *    — `docs/api/coverage.md` records in-app checkout as deliberately not
 *    built, because it needs the website's server-authored pricing, frozen
 *    price snapshot and referral discount extracted into a shared service.
 *
 * So the screen states where payment happens instead of pretending to offer
 * it. `CAN_SUBSCRIBE_IN_APP` is read here anyway, so that when the `direct`
 * flow is built this is the one place that has to change.
 */
export function PlansScreen(_: AppScreenProps<'Plans'>) {
  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['subscription'],
    queryFn: () => api.subscription(),
  });

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

  const currentSlug = data.subscription?.tier?.slug;

  return (
    <View style={styles.screen}>
      <ScrollView contentContainerStyle={styles.content}>
        {data.plans.length === 0 ? (
          <Caption>No plans are published at the moment.</Caption>
        ) : (
          data.plans.map((plan, index) => (
            <PlanCard
              key={plan.slug ?? String(index)}
              plan={plan}
              current={plan.slug !== undefined && plan.slug === currentSlug}
            />
          ))
        )}

        <Text style={styles.note}>
          {CAN_SUBSCRIBE_IN_APP
            ? 'Payments are being added to this build. For now, subscribe on the Jambo website.'
            : 'Plans are managed on the Jambo website. This app does not take payments.'}
        </Text>
      </ScrollView>
    </View>
  );
}

function PlanCard({ plan, current }: { plan: Plan; current: boolean }) {
  return (
    <View
      accessible
      accessibilityLabel={[
        plan.name,
        formatPrice(plan),
        current ? 'your current plan' : null,
      ]
        .filter((part): part is string => typeof part === 'string' && part !== '')
        .join(', ')}
      style={[styles.plan, current && styles.planCurrent]}
    >
      <View style={styles.planHead}>
        <Text style={styles.planName}>{plan.name}</Text>
        {/*
          "Current plan" as words, not as a coloured border alone. The border
          is the decoration; the label is the information.
        */}
        {current ? <Text style={styles.currentTag}>Current plan</Text> : null}
      </View>

      <Text style={styles.price}>{formatPrice(plan)}</Text>

      {typeof plan.description === 'string' && plan.description !== '' ? (
        <Text style={styles.planBody}>{plan.description}</Text>
      ) : null}
    </View>
  );
}

/**
 * The price, exactly as the server states it.
 *
 * `price` arrives as a decimal string (`"10000.00"`) and is rendered, never
 * recomputed: money a client has done arithmetic on is money the viewer cannot
 * check against an invoice.
 *
 * Two zeros that look alike and are not. A plan with **no** price shows an em
 * dash — printing `UGX 0` for a figure nobody has would tell a viewer it is
 * free when the truth is that it is unknown. A plan the server states as zero
 * really is free, and says so.
 */
function formatPrice(plan: Plan): string {
  const raw = plan.price;
  if (typeof raw !== 'string' || raw === '') return '—';

  const currency = typeof plan.currency === 'string' ? plan.currency : '';
  // Trim a trailing ".00" only. Anything else is a real fractional amount and
  // stays exactly as the server sent it.
  const amount = raw.replace(/\.00$/, '');
  const period = typeof plan.billing_period === 'string' ? ` / ${plan.billing_period}` : '';

  /*
   * A genuine zero is "Free", not "UGX 0 / monthly".
   *
   * This is the opposite of the rule about never printing a zero: that one is
   * about zero standing in for a figure nobody has. Here the server states
   * zero and means it — the plan is literally called Free — so the clear word
   * is the honest rendering, and a billing period on a price of nothing is
   * noise.
   */
  if (Number(amount) === 0) return 'Free';

  return `${currency} ${amount}${period}`.trim();
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg },

  plan: {
    padding: spacing.xl,
    borderRadius: radius.surface,
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.divider,
    marginBottom: spacing.lg,
  },
  planCurrent: { borderColor: colors.primary },
  planHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  planName: { ...typography.heading, color: card.titleColor, flexShrink: 1 },
  currentTag: {
    ...typography.caption,
    fontFamily: fonts.medium,
    color: colors.primary,
  },
  price: { ...typography.title, color: colors.text, marginTop: spacing.sm },
  planBody: { ...typography.caption, color: colors.textMuted, marginTop: spacing.sm },

  note: { ...typography.caption, color: colors.textMuted, marginTop: spacing.sm },
});
