import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';

import { billingKeys, fetchOrder } from './api';
import { OrderStatusBadge } from './OrderStatusBadge';
import {
  billingPeriodLabel,
  formatMoney,
  formatOrderDate,
  optionalDetail,
  planLabel,
} from './format';
import { api } from '../../api/jambo';
import { displayName } from '../../ui/profileFields';
import { ListCard, ListRow } from '../../ui/list';
import { Caption, ErrorState, Loading } from '../../ui/components';
import { Sheet } from '../../ui/overlay';
import { colors, fonts, hub, spacing, typography } from '../../ui/theme';

/**
 * One invoice, as a sheet.
 *
 * `resources/views/profile-hub/invoice.blade.php`, in order: the heading
 * *Invoice #reference*, a "Billed to" block with the account's name and
 * email, an "Order details" block with the date, the status badge, the
 * payment method and the tracking id, then a two-column table of one line
 * item and a Total row.
 *
 * **It was a route until 2026-09-10.** 258 lines of screen, opened from
 * exactly one row and leading nowhere — so the only thing a viewer could do
 * from it was leave. `docs/plans/account-area-audit.md` §4.3: *"A receipt is
 * a thing you glance at and dismiss, not a place you go."* Nothing about the
 * document changed; it stopped being somewhere you travel to.
 *
 * **`Sheet` from `ui/overlay.tsx`, which is the only place a `Modal` may come
 * from.** This is a new conversion rather than an exemption, so it does not
 * touch the shrinking allowlist in `eslint.config.js` — it never needed to be
 * on it.
 *
 * **Keyed on the merchant reference, not the row id.** That is what
 * `GET /subscription/orders/{reference}` takes, and it is the identifier the
 * rest of the payment flow already treats as canonical. The website's own
 * invoice route takes a numeric id, which is a difference between the two
 * clients rather than a fault in either.
 *
 * **Billed to comes from `/profile`, which the app already fetches**, because
 * the order payload carries no customer block. The query key is the one the
 * profile editor already writes, and it is shared deliberately: two places on
 * one key MUST have one shape, and the way to guarantee that is to share the
 * call rather than to write a second one that happens to look the same today.
 *
 * **There is no Print button.** The website's is `window.print()`, which has
 * no native equivalent without `expo-print` — a native module, and therefore a
 * prebuild. Rather than draw a control that cannot complete, it is absent and
 * named in the worklog.
 */
export function InvoiceSheet({
  reference,
  onClose,
}: {
  /** The order to show, or null for no sheet at all. */
  reference: string | null;
  onClose: () => void;
}) {
  return (
    <Sheet visible={reference !== null} onClose={onClose}>
      {/*
        Mounted only while there is something to show, so closing the sheet
        drops the two queries with it rather than leaving a stale invoice
        behind the scrim for the next row that is opened.
      */}
      {reference === null ? null : <InvoiceBody reference={reference} />}
    </Sheet>
  );
}

function InvoiceBody({ reference }: { reference: string }) {
  const order = useQuery({
    queryKey: billingKeys.order(reference),
    queryFn: () => fetchOrder(reference),
  });

  /*
   * The account, for "Billed to". Its own query rather than a field on the
   * order, so a slow or failed profile fetch cannot stop the invoice itself
   * from rendering — see how the block is guarded below.
   */
  const profile = useQuery({
    queryKey: ['profile'],
    queryFn: () => api.profile(),
  });

  /*
   * A sheet hugs its content, so both of these need a height of their own or
   * the panel collapses to a strip while it loads.
   */
  if (order.isPending) {
    return (
      <View style={styles.state}>
        <Loading label="Loading your invoice" />
      </View>
    );
  }

  if (order.isError) {
    return (
      <View style={styles.state}>
        <ErrorState
          message={
            order.error instanceof Error && order.error.message !== ''
              ? order.error.message
              : 'We could not load this invoice.'
          }
          onRetry={() => {
            void order.refetch();
          }}
        />
      </View>
    );
  }

  const invoice = order.data;
  const money = formatMoney(invoice.amount, invoice.currency);
  const when = formatOrderDate(invoice.created_at);
  const method = optionalDetail(invoice.payment_method);
  const tracking = optionalDetail(invoice.tracking_id);
  const period = billingPeriodLabel(invoice);

  /*
   * `$user->full_name ?: $user->username`, which the app already implements
   * once in `profileFields.displayName` and which returns an empty string
   * rather than a placeholder. There is no `full_name` field on the API's
   * Profile resource; it is first and last name, composed.
   */
  const billedToName = optionalDetail(
    displayName(profile.data?.first_name, profile.data?.last_name, profile.data?.username),
  );
  const billedToEmail = optionalDetail(profile.data?.email);

  return (
    /* A long invoice scrolls inside the sheet's own cap — see `Sheet` — and a
       short one leaves the sheet at its natural height. */
    <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
      <Text style={styles.title} accessibilityRole="header">
        Invoice #{invoice.reference ?? '\u2014'}
      </Text>

      {/*
        "Billed to". Drawn only once the account is actually known — a block
        headed "Billed to" above an empty line is worse than no block, and
        this is a document somebody may show to somebody else.
      */}
      {billedToName === null && billedToEmail === null ? null : (
        <View style={styles.block}>
          <Caption>Billed to</Caption>
          {billedToName === null ? null : <Text style={styles.billedName}>{billedToName}</Text>}
          {billedToEmail === null ? null : <Text style={styles.billedMeta}>{billedToEmail}</Text>}
        </View>
      )}

      <View style={styles.block}>
        <Caption>Order details</Caption>
      </View>

      <ListCard>
        <ListRow label="Date" value={when ?? '\u2014'} muted={when === null} />
        <ListRow
          label="Status"
          accessory={<OrderStatusBadge status={invoice.status} />}
          accessibilityLabel={`Status, ${invoice.status ?? 'unknown'}`}
        />
        {/*
          Method and tracking are genuinely absent until the gateway reports,
          and the website drops each row rather than printing an empty label.
          So does this: an invoice for a pending charge simply has two fewer
          rows.
        */}
        {method === null ? null : <ListRow label="Method" value={method} />}
        {tracking === null ? null : <ListRow label="Tracking" value={tracking} last />}
      </ListCard>

      {/*
        The line-item table. Two columns on the website, two here: what was
        bought on the left, what it cost on the right, then the Total.
      */}
      <View style={styles.table}>
        <View style={styles.tableHead}>
          <Text style={styles.tableHeadCell}>Description</Text>
          <Text style={[styles.tableHeadCell, styles.right]}>Amount</Text>
        </View>

        <View style={styles.lineItem}>
          <View style={styles.lineItemText}>
            <Text style={styles.lineItemTitle}>{planLabel(invoice)}</Text>
            {period === null ? null : <Text style={styles.lineItemMeta}>{period}</Text>}
          </View>
          <Text style={styles.lineItemAmount}>{money}</Text>
        </View>

        {/*
          The total is the same figure the line item shows, and it is
          RE-RENDERED from the same server string rather than summed. There
          is one line item, so a sum would be arithmetic performed on money
          for no reason, and arithmetic on money is how a total stops
          matching a bank statement.
        */}
        <View style={styles.totalRow}>
          <Text style={styles.totalLabel}>Total</Text>
          <Text style={styles.totalAmount}>{money}</Text>
        </View>
      </View>

      <Text style={styles.note}>Need a printed copy? Open this invoice on the Jambo website.</Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  /* Loading and error both need a height, or the sheet is a strip. */
  state: { minHeight: 220, justifyContent: 'center' },

  content: { paddingBottom: spacing.lg },

  title: { ...typography.title, color: colors.text, marginBottom: spacing.xl },

  block: { marginBottom: spacing.md, gap: 2 },
  billedName: { ...typography.body, fontFamily: fonts.medium, color: colors.text },
  billedMeta: { ...typography.caption, color: colors.textMuted },

  /*
   * `table.table-dark` inside `.jambo-hub-card`, measured rather than chosen —
   * Rio's asset ruling of 2026-09-09. The first cut hand-built a table beside
   * the site's instead of wearing it: its own radius, its own divider colour,
   * its own cell padding. Every number below now comes from a probe against
   * the rendered billing page. The card around it is the hub card, at the hub
   * card's background, border and radius.
   */
  table: {
    marginTop: spacing.xl,
    backgroundColor: hub.cardBg,
    borderRadius: hub.cardRadius,
    borderWidth: 1,
    borderColor: hub.cardBorder,
    overflow: 'hidden',
  },
  tableHead: {
    flexDirection: 'row',
    paddingHorizontal: hub.tableCellX,
    paddingVertical: hub.tableCellY,
    borderBottomWidth: 1,
    borderBottomColor: hub.tableBorder,
  },
  tableHeadCell: {
    fontFamily: fonts.regular,
    fontSize: hub.headSize,
    color: hub.headColor,
    flex: 1,
  },
  right: { textAlign: 'right' },

  lineItem: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
    paddingHorizontal: hub.tableCellX,
    paddingVertical: hub.tableCellY,
  },
  lineItemText: { flex: 1, gap: 2 },
  lineItemTitle: {
    fontFamily: fonts.medium,
    fontSize: hub.tableSize,
    color: hub.tableFg,
  },
  /* `<div class="small text-muted">` under the plan name. */
  lineItemMeta: { ...typography.caption, color: hub.subtitleColor },
  lineItemAmount: {
    fontFamily: fonts.regular,
    fontSize: hub.tableSize,
    color: hub.tableFg,
    textAlign: 'right',
  },

  /* The blade's `<tr class="fw-bold">`: same cell, bold. */
  totalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: spacing.md,
    paddingHorizontal: hub.tableCellX,
    paddingVertical: hub.tableCellY,
    borderTopWidth: 1,
    borderTopColor: hub.tableBorder,
  },
  totalLabel: { fontFamily: fonts.bold, fontSize: hub.tableSize, color: hub.tableFg },
  totalAmount: { fontFamily: fonts.bold, fontSize: hub.tableSize, color: hub.tableFg },

  note: { ...typography.caption, color: colors.textMuted, marginTop: spacing.xl },
});
