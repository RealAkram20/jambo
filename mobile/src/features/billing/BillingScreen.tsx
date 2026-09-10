import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useInfiniteQuery } from '@tanstack/react-query';

import { billingKeys, fetchOrders, type PaymentOrder } from './api';
import { OrderStatusBadge } from './OrderStatusBadge';
import { formatMoney, formatOrderDate, planLabel } from './format';
import { ListCard, ListRow } from '../../ui/list';
import { Button, EmptyState, ErrorState, Loading } from '../../ui/components';
import { colors, fonts, spacing, typography } from '../../ui/theme';
import type { AppScreenProps } from '../../navigation/types';

/**
 * Every charge on the account.
 *
 * This is `resources/views/profile-hub/billing.blade.php`: an "Order history"
 * card whose subtitle is *"Every charge on your account. Click a row to see
 * the invoice"*, a table of Date, Plan, Amount, Status and a View link, and an
 * empty state offering the plans.
 *
 * **The table becomes rows, and that is a translation rather than a
 * redesign.** Five columns do not fit a phone, and the app already has one
 * answer for a list of things you can open: `ListRow` in `ui/list.tsx`, which
 * exists because Rio asked for the UI to be uniform after four screens had
 * grown four row heights. The plan is the label, the date is the second line,
 * and the amount and status sit on the right where the table's last two
 * columns were. The View link becomes the chevron every openable row carries.
 *
 * **Explicit paging, not an infinite feed.** The website paginates with
 * numbered links and the endpoint is cursor-paginated, so older orders arrive
 * on a control the viewer presses. It also keeps the screen inside the app's
 * one list idiom — a `ScrollView` holding a `ListCard` — rather than
 * introducing a third.
 *
 * **The website reaches this page from nowhere.** It has no entry in the
 * profile-hub sidebar and is linked only from the payment-complete page, which
 * the app has no equivalent of. Rio's call, 2026-09-09: the app gets both a
 * Billing row in the profile menu and an Order history row on Membership.
 */
export function BillingScreen({ navigation }: AppScreenProps<'Billing'>) {
  const { data, isPending, isError, error, refetch, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useInfiniteQuery({
      queryKey: billingKeys.orders,
      queryFn: ({ pageParam }: { pageParam: string | undefined }) => fetchOrders(pageParam),
      initialPageParam: undefined as string | undefined,
      getNextPageParam: (last) => last.nextCursor ?? undefined,
    });

  if (isPending) return <Loading label="Loading your order history" />;

  if (isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load your order history.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </View>
    );
  }

  const orders: PaymentOrder[] = data.pages.flatMap((page) => page.items);

  if (orders.length === 0) {
    return (
      <View style={[styles.screen, styles.emptyScreen]}>
        <EmptyState title="No orders yet" detail="Nothing has been charged." />
        {/*
          The website offers "Browse plans" here and links to its pricing page.
          The app's equivalent is its own plan ladder, which sells nothing per
          ADR-0004 and says so on itself. A control that leads somewhere real,
          rather than a checkout the app cannot open.
        */}
        <View style={styles.emptyAction}>
          <Button label="Browse plans" tone="quiet" onPress={() => navigation.navigate('Plans')} />
        </View>
      </View>
    );
  }

  return (
    <View style={styles.screen}>
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.heading}>Order history</Text>

        <ListCard>
          {orders.map((order, index) => (
            <OrderRow
              // The merchant reference is unique per the migration, so it is a
              // real key. The index fallback is for a row the server sent
              // without one: that should not happen and must not crash a list.
              key={order.reference ?? `order-${index}`}
              order={order}
              last={index === orders.length - 1}
              {...(order.reference === undefined
                ? {}
                : {
                    onOpen: () =>
                      navigation.navigate('Invoice', { reference: order.reference as string }),
                  })}
            />
          ))}
        </ListCard>

        {hasNextPage ? (
          <View style={styles.more}>
            <Button
              label="Load older orders"
              tone="quiet"
              busy={isFetchingNextPage}
              onPress={() => {
                void fetchNextPage();
              }}
            />
          </View>
        ) : null}
      </ScrollView>
    </View>
  );
}

function OrderRow({
  order,
  last,
  onOpen,
}: {
  order: PaymentOrder;
  last: boolean;
  onOpen?: () => void;
}) {
  const when = formatOrderDate(order.created_at);
  const money = formatMoney(order.amount, order.currency);

  return (
    <ListRow
      label={planLabel(order)}
      {...(when === null ? {} : { detail: when })}
      last={last}
      {...(onOpen === undefined ? {} : { onPress: onOpen })}
      /*
       * The table's Amount and Status columns, stacked. `ListRow` takes one
       * accessory, and a badge is not a value, so both ride together here
       * rather than widening the shared row with a second right-hand slot that
       * only this screen would ever use.
       */
      accessory={
        <View style={styles.amounts}>
          <Text style={styles.amount}>{money}</Text>
          <OrderStatusBadge status={order.status} />
        </View>
      }
      /*
       * Read as one sentence rather than as four fragments a screen-reader
       * user has to assemble. The reference is included because it titles the
       * invoice, and it is the only thing on the row that is unique.
       */
      accessibilityLabel={[
        planLabel(order),
        money,
        order.status ?? 'status unknown',
        when,
        order.reference,
      ]
        .filter((part): part is string => typeof part === 'string' && part !== '')
        .join(', ')}
    />
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg },

  heading: { ...typography.heading, color: colors.text, marginBottom: spacing.lg },

  amounts: { alignItems: 'flex-end', gap: 4 },
  amount: { ...typography.body, fontFamily: fonts.medium, color: colors.text },

  more: { marginTop: spacing.xl },

  emptyScreen: { justifyContent: 'center', padding: spacing.lg },
  emptyAction: { marginTop: spacing.lg, paddingHorizontal: spacing.xl },
});
