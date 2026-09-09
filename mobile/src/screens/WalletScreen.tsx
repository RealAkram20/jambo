import { FlatList, StyleSheet, Text, View } from 'react-native';
import { useInfiniteQuery } from '@tanstack/react-query';

import { api } from '../api/jambo';
import { card, colors, fonts, radius, spacing, typography } from '../ui/theme';
import { EmptyState, ErrorState, Loading, Spinner } from '../ui/components';
import type { AppScreenProps } from '../navigation/types';

/**
 * The viewer's wallet: balance, the withdrawal floor, and the ledger.
 *
 * **Every amount on this screen is a string, from the wire to the Text node,
 * and is never converted to a number.** `balance`, `min_withdrawal` and each
 * entry's `amount` arrive as decimal strings, and parsing them into a
 * JavaScript number is how a balance loses its last cent to binary floating
 * point. This is somebody's actual money; the app's only job is to show
 * exactly what the server said.
 *
 * That rule is also why there is no total, no sum and no "you need X more to
 * withdraw" line: each would be arithmetic, and arithmetic on money belongs on
 * the server that can be audited.
 *
 * **Withdrawal is not offered.** `docs/api/coverage.md` records it as
 * deliberately not built — money leaving the business earns its own slice —
 * so the screen says where it happens instead of showing a button that cannot
 * complete.
 *
 * Not gated on the referral programme, deliberately: the website's own sidebar
 * carries a comment saying the same. A viewer whose earnings predate the
 * programme being switched off still reaches their balance here.
 */
export function WalletScreen(_: AppScreenProps<'Wallet'>) {
  const { data, isPending, isError, error, refetch, fetchNextPage, hasNextPage, isFetchingNextPage } =
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
  const currency = first?.currency ?? '';
  const entries = data.pages.flatMap((page) => page.entries ?? []);

  return (
    <View style={styles.screen}>
      <FlatList
        data={entries}
        keyExtractor={(entry, index) => String(entry.id ?? index)}
        contentContainerStyle={styles.content}
        ListHeaderComponent={
          <View style={styles.balanceCard}>
            <Text style={styles.balanceLabel}>Balance</Text>
            {/* The string, exactly as sent. */}
            <Text
              accessibilityLabel={`Balance ${currency} ${first?.balance ?? '0'}`}
              style={styles.balance}
            >
              {currency} {first?.balance ?? '0'}
            </Text>
            {first?.min_withdrawal !== undefined ? (
              <Text style={styles.floor}>
                Withdrawals start at {currency} {first.min_withdrawal}
              </Text>
            ) : null}
            <Text style={styles.note}>Withdrawals are made on the Jambo website.</Text>
          </View>
        }
        ListEmptyComponent={
          <EmptyState
            title="No transactions yet"
            detail="Money you earn from referrals and partner payouts appears here."
          />
        }
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
        renderItem={({ item }) => <LedgerRow entry={item} fallbackCurrency={currency} />}
      />
    </View>
  );
}

type Entry = NonNullable<Awaited<ReturnType<typeof api.wallet>>['entries']>[number];

function LedgerRow({ entry, fallbackCurrency }: { entry: Entry; fallbackCurrency: string }) {
  const amount = entry.amount ?? '0';
  const currency = entry.currency ?? fallbackCurrency;

  /*
   * Credit or debit is read off the string's own sign, not computed. A leading
   * "-" is the server saying money left; anything else is money arriving.
   * Sign is also stated in the accessible name, because a red minus is colour
   * carrying meaning on its own.
   */
  const outgoing = amount.trim().startsWith('-');

  return (
    <View
      accessible
      accessibilityLabel={[
        entry.type ?? 'Transaction',
        `${outgoing ? 'minus' : 'plus'} ${currency} ${amount.replace('-', '')}`,
        entry.memo,
        formatWhen(entry.created_at),
      ]
        .filter((part): part is string => typeof part === 'string' && part !== '')
        .join(', ')}
      style={styles.row}
    >
      <View style={styles.rowText}>
        <Text style={styles.rowType}>{humanise(entry.type)}</Text>
        {typeof entry.memo === 'string' && entry.memo !== '' ? (
          <Text style={styles.rowMemo}>{entry.memo}</Text>
        ) : null}
        {formatWhen(entry.created_at) === null ? null : (
          <Text style={styles.rowWhen}>{formatWhen(entry.created_at)}</Text>
        )}
      </View>

      <View style={styles.rowAmounts}>
        <Text style={[styles.amount, outgoing ? styles.outgoing : styles.incoming]}>
          {currency} {amount}
        </Text>
        {typeof entry.balance_after === 'string' ? (
          <Text style={styles.after}>after {entry.balance_after}</Text>
        ) : null}
      </View>
    </View>
  );
}

/** `referral_bonus` → `Referral bonus`. The server's word, made readable. */
function humanise(type: string | undefined): string {
  if (typeof type !== 'string' || type === '') return 'Transaction';
  const spaced = type.replaceAll('_', ' ').trim();
  return spaced.charAt(0).toUpperCase() + spaced.slice(1);
}

function formatWhen(iso: string | null | undefined): string | null {
  if (typeof iso !== 'string' || iso === '') return null;
  const when = new Date(iso);
  if (Number.isNaN(when.getTime())) return null;

  return when.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg, flexGrow: 1 },

  balanceCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.surface,
    padding: spacing.xl,
    marginBottom: spacing.xl,
    gap: spacing.xs,
  },
  balanceLabel: { ...typography.caption, color: colors.textMuted },
  balance: { ...typography.title, fontFamily: fonts.bold, color: card.titleColor },
  floor: { ...typography.caption, color: colors.textMuted, marginTop: spacing.sm },
  note: { ...typography.caption, color: colors.textMuted },

  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: spacing.md,
    paddingVertical: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  rowText: { flex: 1, gap: 2 },
  rowType: { ...typography.body, color: colors.text },
  rowMemo: { ...typography.caption, color: colors.textMuted },
  rowWhen: { ...typography.caption, color: colors.placeholder },
  rowAmounts: { alignItems: 'flex-end', gap: 2 },
  amount: { ...typography.body, fontFamily: fonts.medium },
  incoming: { color: colors.okText },
  outgoing: { color: colors.errorText },
  after: { ...typography.caption, color: colors.placeholder },

  footer: { paddingVertical: spacing.xl, alignItems: 'center' },
});
