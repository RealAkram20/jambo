import { apiClient } from '../../api/jambo';
import type { components } from '../../api/schema';

/**
 * Billing's own calls.
 *
 * A feature module rather than two more methods on `JamboApi`, per Rio's
 * modular ruling of 2026-09-09. `src/api/endpoints.ts` is past seven hundred
 * lines and is where three concurrent sessions kept colliding over unrelated
 * features; billing has no reason to be in that file.
 *
 * The type is generated from `docs/api/openapi.yaml` and never hand-written,
 * so a field renamed on the server breaks the build here rather than
 * rendering as blank space on somebody's invoice.
 */
export type PaymentOrder = components['schemas']['PaymentOrder'];

/**
 * One page of payment history, newest first.
 *
 * Cursor-paginated rather than offset-paginated, and the server explains why:
 * `created_at` is not unique, so a cursor keyed on it alone silently drops
 * every row that ties. Pass the previous page's `nextCursor` to continue.
 */
export async function fetchOrders(
  cursor?: string,
): Promise<{ items: PaymentOrder[]; nextCursor: string | null }> {
  /*
   * The query object is omitted rather than passed as undefined: the app
   * compiles with `exactOptionalPropertyTypes`, so an explicitly undefined
   * optional is a different thing from an absent one.
   */
  const { data } = await apiClient.request<{
    items?: PaymentOrder[];
    next_cursor?: string | null;
  }>('/subscription/orders', cursor === undefined ? {} : { query: { cursor } });

  return {
    items: data.items ?? [],
    nextCursor: data.next_cursor ?? null,
  };
}

/**
 * One order.
 *
 * Keyed on the merchant reference, NOT the row id — that is what the endpoint
 * takes, and it is the identifier the rest of the payment flow already treats
 * as canonical (callback URLs, IPN webhooks, the complete page's `?ref=`).
 * The website's own invoice route takes a numeric id instead, which is a
 * difference between the two clients and not a bug in either.
 */
export async function fetchOrder(reference: string): Promise<PaymentOrder> {
  const { data } = await apiClient.request<{ order: PaymentOrder }>(
    `/subscription/orders/${encodeURIComponent(reference)}`,
  );

  return data.order;
}

/**
 * The query keys billing owns.
 *
 * Declared in one place because a key shared between two screens **must** be a
 * shape shared between two screens. TypeScript cannot see a collision — a
 * cached value is typed by whichever queryFn the compiler happens to be
 * looking at — and slice 2c lost an afternoon to exactly that: the player and
 * the detail screen wrote `['title','series',slug]` with two different shapes
 * and the player crashed on every episode. Keys live beside the functions that
 * fill them so the pair cannot drift.
 */
export const billingKeys = {
  orders: ['billing', 'orders'] as const,
  order: (reference: string) => ['billing', 'order', reference] as const,
};

/**
 * Start paying for a plan.
 *
 * **The request names a plan and nothing else.** No amount, no currency, no
 * gateway — the server reads the price off the tier row, freezes it into the
 * order and picks the gateway from `payments.default_gateway`. That is what
 * makes it impossible for a client to pair a plan with a price of its own, and
 * it is why adding a second gateway is a server change the app never sees.
 *
 * ADR-0004 decides who may call this: the `direct` APK, never the Play build,
 * because Google's Payments policy requires Play Billing for subscription
 * video outside IN/KR/EEA/US. `CAN_SUBSCRIBE_IN_APP` gates the button.
 *
 * 🔴 **The response describes a presentation, not a gateway.** Rio's
 * requirement of 2026-09-10: the API passes through whatever the gateway
 * gives, *"because we don't want to lose sales because someone did not update
 * the app"*. So this returns `{ mode, url, return_url, cancel_url }` and the
 * app obeys it. Adding Flutterwave or Paystack next year is a server change;
 * a phone that has not updated in a year still completes the sale, because it
 * was only ever told "display this".
 *
 * Whatever presents it, the app polls `fetchOrder` afterwards: the gateway
 * confirms to the SERVER, and a handset that never comes back to the
 * foreground must not be what decides whether a subscription starts.
 */
export type Checkout = {
  /**
   * How to present it.
   *
   * A closed set the app ships knowing, so the SERVER chooses per order and a
   * gateway added later needs no app release. Anything unrecognised is treated
   * as `redirect`, which is why `url` is always present.
   */
  mode: 'webview' | 'redirect' | string;
  url: string;
  /** Navigating to either means the flow is over; the app closes the view. */
  return_url: string;
  cancel_url: string;
};

export async function startCheckout(tierSlug: string): Promise<{
  checkout: Checkout;
  reference: string;
}> {
  const { data } = await apiClient.request<{ checkout: Checkout; reference: string }>(
    '/subscription/orders',
    { method: 'POST', body: { tier_slug: tierSlug } },
  );

  return data;
}
