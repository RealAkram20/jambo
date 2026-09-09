import { useState } from 'react';
import { ScrollView, Share, StyleSheet, Text, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';

import { api } from '../api/jambo';
import { ApiError } from '../api/errors';
import { card, colors, fonts, radius, spacing, typography } from '../ui/theme';
import { Button, Caption, EmptyState, ErrorState, Heading, Loading } from '../ui/components';
import type { AppScreenProps } from '../navigation/types';

/**
 * Refer and earn.
 *
 * **A 404 here is not an error.** The endpoint returns one when the referral
 * programme is switched off — with the exception the spec is explicit about,
 * that a viewer who already has wallet history still gets their dashboard,
 * because money someone earned must not become unreachable because an admin
 * changed a setting. So a 404 is rendered as "this is not available", calmly,
 * with no Try again button: retrying a switch that is off is not a fix and
 * offering it says the app thinks something broke.
 *
 * Whether this screen is *offered* at all is answered earlier and elsewhere,
 * by `features.referrals` on `/app/config`, which is what the profile drawer
 * reads. This screen still handles the off case, because a flag read at launch
 * and a setting changed since are two different moments.
 *
 * Every money value is a string end to end, for the reason set out in
 * `WalletScreen`: arithmetic on a balance belongs on the server.
 */
export function ReferralsScreen(_: AppScreenProps<'Referrals'>) {
  const [shareError, setShareError] = useState<string | null>(null);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['referrals'],
    queryFn: () => api.referrals(),
    // A 404 is a real answer, not a flake. Retrying it three times just delays
    // the screen the viewer is going to get anyway.
    retry: (count, caught) =>
      caught instanceof ApiError && caught.code === 'NOT_FOUND' ? false : count < 2,
  });

  if (isPending) return <Loading label="Loading Refer and Earn" />;

  if (isError) {
    const off = error instanceof ApiError && error.code === 'NOT_FOUND';

    return (
      <View style={styles.screen}>
        {off ? (
          <EmptyState
            title="Refer and Earn is not available"
            detail="Jambo is not running a referral programme at the moment. If you have earned anything before, it is still in your wallet."
          />
        ) : (
          <ErrorState
            message={
              error instanceof Error && error.message !== ''
                ? error.message
                : 'We could not load Refer and Earn.'
            }
            onRetry={() => {
              void refetch();
            }}
          />
        )}
      </View>
    );
  }

  const { code, share_url: shareUrl, currency, stats, terms } = data;

  return (
    <View style={styles.screen}>
      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.codeCard}>
          <Text style={styles.codeLabel}>Your referral code</Text>
          {/*
            The code is the whole point of the screen, so it is the largest
            thing on it and selectable — somebody will want to copy it by hand
            rather than share a link.
          */}
          <Text selectable style={styles.code}>
            {code ?? '—'}
          </Text>

          {typeof shareUrl === 'string' && shareUrl !== '' ? (
            <View style={styles.shareRow}>
              <Button
                label="Share your link"
                onPress={() => {
                  setShareError(null);
                  // The platform's own share sheet, not a copy button: sharing
                  // a link is what this is for, and the OS already knows every
                  // app the viewer would send it to.
                  Share.share({ message: shareUrl }).catch(() => {
                    setShareError('Could not open the share sheet. The link is above.');
                  });
                }}
              />
              <Caption>{shareUrl}</Caption>
              {shareError === null ? null : <Caption>{shareError}</Caption>}
            </View>
          ) : null}
        </View>

        <View style={styles.section}>
          <Heading>What it has earned</Heading>
          <View style={styles.statsCard}>
            <Stat label="Balance" value={money(currency, stats?.balance)} />
            <Stat label="Earned in total" value={money(currency, stats?.total_earned)} />
            <Stat label="People referred" value={count(stats?.total_referrals)} />
            {/*
              "Qualified" is the server's own word for referrals that have met
              whatever the programme requires. Shown as sent, not reworded into
              a claim about what qualifying means.
            */}
            <Stat label="Qualified" value={count(stats?.qualified)} />
          </View>
        </View>

        {terms === undefined ? null : (
          <View style={styles.section}>
            <Heading>The terms</Heading>
            <View style={styles.statsCard}>
              <Stat label="You earn" value={percent(terms.reward_percent)} />
              <Stat label="They save" value={percent(terms.discount_percent)} />
              <Stat label="Withdraw from" value={money(currency, terms.min_withdrawal)} />
            </View>
          </View>
        )}

        <Caption>Withdrawals are made on the Jambo website.</Caption>
      </ScrollView>
    </View>
  );
}

function Stat({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.stat}>
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={styles.statValue}>{value}</Text>
    </View>
  );
}

/**
 * Money, as a string, with its currency.
 *
 * An em dash when the server sent nothing. `UGX 0` for an unknown balance
 * would tell a viewer they have earned nothing when the truth is that nobody
 * asked.
 */
function money(currency: string | undefined, amount: string | null | undefined): string {
  if (typeof amount !== 'string' || amount === '') return '—';
  return `${currency ?? ''} ${amount}`.trim();
}

function count(value: number | undefined): string {
  // A real zero is worth showing here: "0 people referred" is true and useful,
  // unlike a zero standing in for a figure nobody has.
  return typeof value === 'number' ? String(value) : '—';
}

function percent(value: string | undefined): string {
  return typeof value === 'string' && value !== '' ? `${value}%` : '—';
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg, flexGrow: 1 },

  codeCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.surface,
    padding: spacing.xl,
    alignItems: 'center',
    gap: spacing.sm,
  },
  codeLabel: { ...typography.caption, color: colors.textMuted },
  code: {
    fontFamily: fonts.bold,
    fontSize: 30,
    letterSpacing: 2,
    color: card.titleColor,
  },
  shareRow: { alignSelf: 'stretch', gap: spacing.sm, marginTop: spacing.md },

  section: { marginTop: spacing.xl },
  statsCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.surface,
    paddingHorizontal: spacing.xl,
  },
  stat: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: spacing.md,
    paddingVertical: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  statLabel: { ...typography.body, color: colors.textMuted },
  statValue: { ...typography.body, fontFamily: fonts.medium, color: colors.text },
});
