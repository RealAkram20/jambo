import { createElement, useState } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';
import { useInfiniteQuery } from '@tanstack/react-query';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { LinearGradient } from 'expo-linear-gradient';
import {
  ArrowCircleDown,
  ArrowCircleUp,
  ArrowUUpLeft,
  Coins,
  Crown,
  Gift,
  Receipt,
  Wallet as WalletMark,
  type Icon,
} from 'phosphor-react-native';

import { api } from '../../api/jambo';
import type { AppStackParams, AppScreenProps } from '../../navigation/types';
import { EmptyState, ErrorState, Loading, Spinner } from '../../ui/components';
import { Focusable } from '../../ui/rails/Focusable';
import { colors, fonts, spacing, wallet as t } from '../../ui/theme';
import { WithdrawSheet } from './WithdrawSheet';
import {
  entryLabel,
  isOutgoing,
  money,
  signedMoney,
  walletDate,
  withdrawState,
  withdrawalLabel,
  type Entry,
  type Withdrawal,
} from './money';

/**
 * The wallet, from Rio's mockup of 2026-09-09.
 *
 * **Six of the mockup's controls were checked against the product and only two
 * survived.** Jambo's wallet has no top-up — money enters it as referral
 * rewards, refunds and statement credits, and there is no deposit code or
 * ledger type anywhere — so "Add Funds" became "Earn more", pointing at the
 * only thing that actually fills it. "Buy/Rent Movies" describes a product
 * Jambo is not: it is subscription-only, with no purchase or rental model.
 * "Gift Credits" and "Airtime Top Up" exist nowhere. All three are Rio's
 * calls, recorded in the worklog with what was checked.
 *
 * **Refer & Earn appears once, not twice.** His two answers together would
 * have put it in the primary row *and* in the quick actions; two routes to one
 * screen on one screen is the duplication §4 exists to stop, so the quick-
 * action row carries the destination the primary row does not.
 *
 * **This screen parses money into numbers, and the screen it replaces
 * forbade that.** That rule was written to protect a balance from binary
 * floating point, which is the right instinct and the wrong conclusion here,
 * for two reasons. The website itself does `number_format((float) $balance, 0)`
 * on every amount its wallet page prints, so string fidelity was never what
 * the product shipped. And the string is not trustworthy anyway:
 * `Ledger::balanceFor` returns "15000.00" on MySQL and "15000" on SQLite, so
 * rendering it verbatim makes the app's numbers depend on the database behind
 * the API. Formatting is in `money.ts`, with the reasoning and the tests.
 */
export function WalletScreen(_: AppScreenProps<'Wallet'>) {
  const navigation = useNavigation<NativeStackNavigationProp<AppStackParams>>();
  const [withdrawing, setWithdrawing] = useState(false);

  const { data, isPending, isError, error, refetch, isFetching, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useInfiniteQuery({
      queryKey: ['wallet'],
      queryFn: ({ pageParam }: { pageParam: string | undefined }) => api.wallet(pageParam),
      initialPageParam: undefined as string | undefined,
      getNextPageParam: (last) => last.next_cursor ?? undefined,
    });

  if (isPending) return <Loading label="Loading your wallet" />;

  if (isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load your wallet.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </View>
    );
  }

  const first = data.pages[0];
  const currency = first?.currency ?? 'UGX';
  const balance = first?.balance ?? '';
  const entries: Entry[] = data.pages.flatMap((page) => page.entries ?? []);
  const withdrawals: Withdrawal[] = first?.withdrawals ?? [];
  const state = withdrawState(first ?? {});

  return (
    <View style={styles.screen}>
      <FlatList
        data={entries}
        keyExtractor={(entry, index) => String(entry.id ?? index)}
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
        refreshing={isFetching && !isFetchingNextPage}
        onRefresh={() => void refetch()}
        ItemSeparatorComponent={Gap}
        ListHeaderComponent={
          <View>
            <BalanceCard balance={balance} currency={currency} held={state.can === false && state.reason === 'open'} />

            <View style={styles.actions}>
              <Action
                icon={Gift}
                label="Earn more"
                onPress={() => navigation.navigate('Referrals')}
              />
              <Action
                icon={ArrowCircleUp}
                label="Withdraw"
                primary
                disabled={!state.can}
                onPress={() => setWithdrawing(true)}
              />
            </View>

            {/*
              Why the button is off, when it is. A disabled control that does
              not explain itself is the one people report as broken, and each
              of these is a real constraint rather than a hint.
            */}
            {state.can === false ? (
              <Text style={styles.constraint}>
                {state.reason === 'open'
                  ? 'A withdrawal is already in progress.'
                  : state.reason === 'empty'
                    ? 'Nothing to withdraw yet.'
                    : `Minimum withdrawal is ${money(state.minimum, currency)}.`}
              </Text>
            ) : null}

            <View style={styles.tiles}>
              <Tile
                icon={Crown}
                label="Upgrade Membership"
                onPress={() => navigation.navigate('Plans')}
              />
              {/*
                Billing rather than Streaming, as of 2026-09-09.
                
                The second tile was the streaming-preferences screen until Rio
                cut it that evening — "all of these settings are applied to the
                player itself" — which took its route with it. Billing is the
                honest replacement rather than the nearest one: this is a money
                screen, payment history is money, and it is the only other
                destination on it that a viewer looking at a balance might
                actually want. It is also not already a button above, which is
                what ruled Refer & Earn out.
              */}
              <Tile
                icon={Receipt}
                label="Billing"
                onPress={() => navigation.navigate('Billing')}
              />
            </View>

            {withdrawals.length > 0 ? (
              <>
                <Text accessibilityRole="header" style={styles.heading}>
                  Withdrawals
                </Text>
                {withdrawals.map((w) => (
                  <View key={String(w.id)} style={styles.withdrawalWrap}>
                    <WithdrawalRow withdrawal={w} currency={currency} />
                  </View>
                ))}
              </>
            ) : null}

            <Text accessibilityRole="header" style={styles.heading}>
              Recent Transactions
            </Text>
          </View>
        }
        ListEmptyComponent={<EmptyState title="Nothing here yet" detail="Refer friends to start earning." />}
        ListFooterComponent={
          isFetchingNextPage ? (
            <View style={styles.footer}>
              <Spinner />
            </View>
          ) : null
        }
        onEndReached={() => {
          if (hasNextPage && !isFetchingNextPage) void fetchNextPage();
        }}
        onEndReachedThreshold={0.5}
        renderItem={({ item }) => <EntryRow entry={item} currency={currency} />}
      />

      <WithdrawSheet
        visible={withdrawing}
        balance={balance}
        currency={currency}
        onClose={() => setWithdrawing(false)}
      />
    </View>
  );
}

function Gap() {
  return <View style={styles.gap} />;
}

/**
 * The balance card.
 *
 * The mark on the right is the website's own: its wallet page draws a filled
 * `ph-wallet` and nothing else. The mockup draws an illustration, and an
 * illustration this app does not have is not a thing to invent.
 */
function BalanceCard({
  balance,
  currency,
  held,
}: {
  balance: string;
  currency: string;
  held: boolean;
}) {
  return (
    <LinearGradient
      colors={[t.cardFrom, t.cardTo]}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={styles.card}
    >
      <View style={styles.cardText}>
        <Text style={styles.cardLabel}>Your Balance</Text>
        <Text
          accessibilityRole="text"
          accessibilityLabel={`Balance ${money(balance, currency)}`}
          style={styles.balance}
          numberOfLines={1}
          adjustsFontSizeToFit
        >
          {money(balance, currency)}
        </Text>
        {/*
          Only when it is true, and it is the one thing a viewer cannot work
          out from the number: a wallet with a withdrawal in flight reads lower
          than it did yesterday and nothing else on the card says why.
        */}
        {held ? <Text style={styles.cardNote}>A withdrawal is on hold.</Text> : null}
      </View>

      <WalletMark size={t.cardMarkSize} color={colors.onPrimary} weight="fill" />
    </LinearGradient>
  );
}

function Action({
  icon,
  label,
  onPress,
  primary = false,
  disabled = false,
}: {
  icon: Icon;
  label: string;
  onPress: () => void;
  primary?: boolean;
  disabled?: boolean;
}) {
  const tint = primary && !disabled ? colors.onPrimary : colors.text;

  return (
    <Focusable
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityState={{ disabled }}
      disabled={disabled}
      ringRadius={t.actionRadius}
      onPress={onPress}
      style={[
        styles.action,
        primary ? styles.actionPrimary : styles.actionQuiet,
        disabled ? styles.actionOff : null,
      ]}
    >
      {createElement(icon, { size: 18, color: tint, weight: 'bold' })}
      <Text style={[styles.actionLabel, { color: tint }]}>{label}</Text>
    </Focusable>
  );
}

function Tile({ icon, label, onPress }: { icon: Icon; label: string; onPress: () => void }) {
  return (
    <Focusable
      accessibilityRole="link"
      accessibilityLabel={label}
      ringRadius={t.tileRadius}
      onPress={onPress}
      style={styles.tile}
    >
      {createElement(icon, { size: t.tileIconSize, color: colors.primary, weight: 'regular' })}
      <Text style={styles.tileLabel} numberOfLines={2}>
        {label}
      </Text>
    </Focusable>
  );
}

/**
 * Which mark a ledger row wears.
 *
 * Derived from the entry's own type, so a row cannot be given a meaning it
 * does not have. Anything unrecognised gets the neutral coin rather than being
 * guessed at — an unknown ledger type is still money that moved.
 */
function markFor(type: string | null | undefined): Icon {
  switch (type) {
    case 'spend':
      return Crown;
    case 'referral_reward':
      return Gift;
    case 'refund':
    case 'hold_release':
      return ArrowUUpLeft;
    case 'withdrawal_hold':
      return ArrowCircleUp;
    case 'statement_credit':
    case 'performance_credit':
      return ArrowCircleDown;
    default:
      return Coins;
  }
}

function EntryRow({ entry, currency }: { entry: Entry; currency: string }) {
  const out = isOutgoing(entry.amount);
  const when = walletDate(entry.created_at);
  const label = entryLabel(entry.type);

  return (
    <View
      accessible
      accessibilityLabel={[label, entry.memo, signedMoney(entry.amount ?? null, currency), when]
        .filter((part): part is string => typeof part === 'string' && part !== '')
        .join('. ')}
      style={styles.row}
    >
      <View style={styles.mark}>
        {createElement(markFor(entry.type), {
          size: t.markIconSize,
          color: colors.primary,
          weight: 'regular',
        })}
      </View>

      <View style={styles.rowText}>
        <Text style={styles.rowTitle} numberOfLines={1}>
          {label}
        </Text>
        {typeof entry.memo === 'string' && entry.memo !== '' ? (
          <Text style={styles.rowMeta} numberOfLines={1}>
            {entry.memo}
          </Text>
        ) : null}
      </View>

      <View style={styles.rowRight}>
        <Text style={[styles.amount, out ? styles.outgoing : styles.incoming]}>
          {signedMoney(entry.amount ?? null, currency)}
        </Text>
        {when === '' ? null : <Text style={styles.rowMeta}>{when}</Text>}
      </View>
    </View>
  );
}

/**
 * One withdrawal request.
 *
 * The status is a word rather than only a colour, which is what §6 asks for —
 * and on this build it is load-bearing rather than belt-and-braces, because
 * the site's own `danger` is a slate grey that does not read as a problem.
 */
function WithdrawalRow({ withdrawal, currency }: { withdrawal: Withdrawal; currency: string }) {
  const rejected = withdrawal.status === 'rejected';
  const paid = withdrawal.status === 'paid';

  return (
    <View
      accessible
      accessibilityLabel={[
        `Withdrawal ${money(withdrawal.amount ?? null, currency)}`,
        withdrawalLabel(withdrawal.status),
        withdrawal.rejection_reason,
        walletDate(withdrawal.requested_at),
      ]
        .filter((part): part is string => typeof part === 'string' && part !== '')
        .join('. ')}
      style={styles.row}
    >
      <View style={styles.mark}>
        <ArrowCircleUp size={t.markIconSize} color={colors.primary} weight="regular" />
      </View>

      <View style={styles.rowText}>
        <Text style={styles.rowTitle} numberOfLines={1}>
          {money(withdrawal.amount ?? null, currency)}
        </Text>
        {/*
          The clerk's note, and only on a rejection — which is the one status
          where a viewer needs to know what to change before asking again.
        */}
        {rejected && typeof withdrawal.rejection_reason === 'string' ? (
          <Text style={styles.rowMeta} numberOfLines={2}>
            {withdrawal.rejection_reason}
          </Text>
        ) : paid && typeof withdrawal.reference === 'string' && withdrawal.reference !== '' ? (
          <Text style={styles.rowMeta} numberOfLines={1}>
            {withdrawal.reference}
          </Text>
        ) : null}
      </View>

      <View style={styles.rowRight}>
        <Text style={[styles.status, paid ? styles.incoming : null]}>
          {withdrawalLabel(withdrawal.status)}
        </Text>
        <Text style={styles.rowMeta}>{walletDate(withdrawal.requested_at)}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { paddingHorizontal: spacing.lg, paddingBottom: spacing.xxl, flexGrow: 1 },

  card: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.lg,
    borderRadius: t.cardRadius,
    padding: t.cardPad,
    marginTop: spacing.md,
  },
  cardText: { flex: 1 },
  cardLabel: { fontFamily: fonts.medium, fontSize: t.cardLabelSize, color: colors.onPrimary, opacity: 0.85 },
  balance: { fontFamily: fonts.black, fontSize: t.balanceSize, color: colors.onPrimary, marginTop: 2 },
  cardNote: { fontFamily: fonts.regular, fontSize: t.cardLabelSize, color: colors.onPrimary, opacity: 0.85, marginTop: 4 },

  actions: { flexDirection: 'row', gap: t.actionGap, marginTop: spacing.lg },
  action: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    height: t.actionHeight,
    borderRadius: t.actionRadius,
  },
  actionPrimary: { backgroundColor: colors.primary },
  actionQuiet: { borderWidth: 1, borderColor: t.rowBorder },
  actionOff: { backgroundColor: 'transparent', borderWidth: 1, borderColor: t.rowBorder, opacity: 0.55 },
  actionLabel: { fontFamily: fonts.medium, fontSize: 14 },

  constraint: {
    fontFamily: fonts.regular,
    fontSize: t.metaSize,
    color: t.metaColor,
    marginTop: spacing.sm,
    textAlign: 'center',
  },

  tiles: { flexDirection: 'row', gap: t.actionGap, marginTop: spacing.lg },
  tile: {
    flex: 1,
    alignItems: 'center',
    gap: spacing.sm,
    padding: t.tilePad,
    borderRadius: t.tileRadius,
    borderWidth: 1,
    borderColor: t.tileBorder,
  },
  tileLabel: { fontFamily: fonts.medium, fontSize: t.tileLabelSize, color: colors.text, textAlign: 'center' },

  heading: { fontFamily: fonts.bold, fontSize: 17, color: colors.text, marginTop: spacing.xl, marginBottom: spacing.md },

  withdrawalWrap: { marginBottom: spacing.sm },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: t.rowGap,
    padding: t.rowPad,
    borderRadius: t.rowRadius,
    borderWidth: t.rowBorderWidth,
    borderColor: t.rowBorder,
  },
  mark: {
    width: t.markSize,
    height: t.markSize,
    borderRadius: t.markRadius,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: t.rowBorder,
  },
  rowText: { flex: 1, gap: 2 },
  rowTitle: { fontFamily: fonts.medium, fontSize: t.titleSize, color: t.titleColor },
  rowMeta: { fontFamily: fonts.regular, fontSize: t.metaSize, color: t.metaColor },
  rowRight: { alignItems: 'flex-end', gap: 2 },
  amount: { fontFamily: fonts.medium, fontSize: t.titleSize },
  status: { fontFamily: fonts.medium, fontSize: t.metaSize, color: t.titleColor },
  incoming: { color: t.incoming },
  outgoing: { color: t.outgoing },

  gap: { height: spacing.sm },
  footer: { paddingVertical: spacing.lg, alignItems: 'center' },
});
