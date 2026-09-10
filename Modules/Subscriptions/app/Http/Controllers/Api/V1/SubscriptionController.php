<?php

namespace Modules\Subscriptions\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\Log;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Payments\app\Services\SubscriptionCheckout;
use Modules\Streaming\app\Services\PlaybackAuthorizer;
use Modules\Subscriptions\app\Models\SubscriptionTier;

/**
 * Plans, the viewer's current plan, and their payment history.
 *
 * **Read-only, and that is a decision.** ADR-0004 splits the app in two: the
 * Google Play build is consumption-only — no purchase, no checkout, no link to
 * a payment page, because Play's Payments policy requires Play Billing for
 * subscription content in Uganda and a consumption-only app is the documented
 * way to stay compliant. Only the direct APK carries in-app PesaPal.
 *
 * So these endpoints serve both builds: the Play build shows the plan and the
 * renewal date, and the direct build will add checkout on top.
 *
 * **In-app checkout is deliberately not here.** `PaymentController::createOrder`
 * holds real money logic — the price is copied off the tier server-side so a
 * client cannot name its own, the amount is frozen in a snapshot so an admin
 * editing a price mid-checkout cannot void a genuine payment, and a referral
 * discount is computed and recorded server-side. It also explicitly refuses a
 * client that pairs an arbitrary amount with a SubscriptionTier payable, which
 * is the attack that would otherwise buy Premium for one shilling. Reusing
 * that safely means extracting it into a service both callers share, and
 * money code earns its own careful slice rather than being tacked onto the end
 * of another one. See docs/api/coverage.md.
 */
class SubscriptionController extends Controller
{
    /**
     * The plans on offer.
     *
     * Public: the website's pricing page is, and the Play build needs to show
     * what a plan costs even though it cannot sell one.
     */
    public function plans(): JsonResponse
    {
        $all = SubscriptionTier::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('access_level')
            ->get();

        /*
         * The pill, decided once here rather than in each client.
         *
         * `pricing-page.blade.php` drew it from a rule written inline; that
         * rule now lives on the model and both surfaces call it, so the app
         * and the website cannot disagree about which plan is the popular
         * one. Free tiers are excluded, as they are on the website — a plan
         * nobody pays for is not the popular one.
         */
        $popularSlug = SubscriptionTier::popularFrom(
            $all->filter(fn (SubscriptionTier $tier) => (float) $tier->price > 0)
        )?->slug;

        $tiers = $all->map(fn (SubscriptionTier $tier) => [
            'slug' => $tier->slug,
            'name' => $tier->name,
            'description' => $tier->description,
            'price' => $tier->price,
            'currency' => $tier->currency,
            'billing_period' => $tier->billing_period,
            /*
             * The suffix under the price, authored server-side because the
             * website authors it server-side. A client deriving "per month"
             * from `billing_period` is a second copy of a mapping that
             * already exists, and it is the copy that will be missed when a
             * period is added.
             */
            'period_label' => $tier->periodLabel(),
            'access_level' => $tier->access_level,
            // null means the plan sets no cap. Render "unlimited", never
            // "0" and never "none".
            'max_concurrent_streams' => $tier->max_concurrent_streams,
            /*
             * What the plan actually includes, as the admin wrote it.
             *
             * Published 2026-09-09. The column has backed the website's
             * pricing cards since before this API existed, and the endpoint
             * simply never forwarded it — so the app had a plan ladder with
             * prices and no reason to choose between them. An app that
             * invents its own bullet list is an app that will one day
             * promise something the admin has withdrawn.
             *
             * Always an array. Null on the column means the admin has not
             * written any, which is an empty list rather than a missing
             * field the client has to guard.
             */
            'features' => is_array($tier->features) ? array_values($tier->features) : [],
            'is_popular' => $popularSlug !== null && $tier->slug === $popularSlug,
        ]);

        return ApiResponse::ok(['plans' => $tiers]);
    }

    /**
     * What this viewer is on, and until when.
     *
     * `GET /me` carries a summary for the launch screen; this is the
     * membership screen, with the plan list beside it so an upgrade prompt can
     * be drawn from one request.
     */
    public function show(Request $request, PlaybackAuthorizer $authorizer): JsonResponse
    {
        $subscription = $authorizer->activeSubscription($request->user());
        $tier = $subscription?->tier;

        return ApiResponse::ok([
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'starts_at' => optional($subscription->starts_at)->toIso8601String(),
                'ends_at' => optional($subscription->ends_at)->toIso8601String(),
                'auto_renew' => (bool) $subscription->auto_renew,
                'tier' => [
                    'slug' => $tier?->slug,
                    'name' => $tier?->name,
                    'access_level' => $tier?->access_level,
                    'max_concurrent_streams' => $tier?->max_concurrent_streams,
                ],
            ] : null,
            // So the app can say "you are on Basic, Premium gives you X"
            // without a second request.
            'plans' => $this->plans()->getData(true)['data']['plans'],
        ]);
    }

    /**
     * Start a subscription payment.
     *
     * 🔴 **The half of ADR-0004 that was never built.** That decision
     * gives the Play build no checkout at all — Google's Payments policy
     * requires Play Billing for subscription video outside IN/KR/EEA/US and
     * forbids pointing at another payment method — and gives the direct APK a
     * PesaPal hosted checkout. Only the first half shipped, so Rio found on
     * 2026-09-10 that he could not subscribe from any build.
     *
     * **The server does not police which build calls this, and that is
     * deliberate.** Google inspects the binary, not the API; a variant flag
     * sent by a client is not a security control and pretending otherwise
     * would give false comfort. `CAN_SUBSCRIBE_IN_APP` in the app decides
     * whether the button exists, and the Play build never draws it.
     *
     * The controller stays thin: validate, call `SubscriptionCheckout`, map
     * failures to codes. Every rule about money — the price coming off the
     * tier, the frozen snapshot, the server-computed referral discount —
     * lives in that service and is shared with the website's own pricing page,
     * so there is one way to buy a plan rather than two that drift.
     *
     * **There is no `amount` in the request and there cannot be.** The client
     * names a plan; the server reads what it costs. That is what makes the
     * attack the website's controller has to guard against — pairing an
     * arbitrary amount with a tier payable — unexpressible here.
     */
    public function createOrder(Request $request, SubscriptionCheckout $checkout): JsonResponse
    {
        $data = $request->validate([
            'tier_slug' => ['required', 'string', 'max:100'],
        ]);

        if (! $checkout->isConfigured()) {
            return ApiResponse::error(
                ApiErrorCode::ServerError,
                'Payments are not available right now. Please try again later.',
            );
        }

        $tier = $checkout->tierBySlug($data['tier_slug']);

        if ($tier === null) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That plan is no longer available.');
        }

        /*
         * A free plan has nothing to pay for. Refused rather than sent to a
         * gateway that would be asked for zero shillings, which PesaPal
         * rejects with a message nobody can act on.
         */
        if ((float) $tier->price <= 0) {
            return ApiResponse::error(
                ApiErrorCode::ValidationFailed,
                'That plan is free — there is nothing to pay.',
                ['tier_slug' => ['That plan is free.']],
            );
        }

        $order = null;

        try {
            $started = $checkout->start(
                $request->user(),
                $tier,
                // No cookie from a phone. The service still reads the
                // account's own referral state, so a viewer referred at signup
                // is credited either way.
                null,
                $this->paymentUrl('payment.callback'),
                $this->paymentUrl('payment.complete', ['result' => 'cancelled']),
            );

            /*
             * 🔴 **A presentation the app obeys, not a URL it
             * interprets.** Rio, 2026-09-10: *"our api will pass anything we
             * get from the payment gateway integration, including future
             * payment integrations, because we don't want to lose sales
             * because someone did not update the app."*
             *
             * So the response describes HOW to show a checkout, not which
             * gateway produced it. The app ships knowing a small closed set of
             * modes and the server picks one per order; a gateway added next
             * year that hands back a hosted page or an iframe is a server
             * change, and a phone that has not been updated in a year still
             * completes the sale.
             *
             * `webview` is the mode every hosted checkout fits — PesaPal's
             * iframe, Flutterwave, Paystack, DPO, Stripe Checkout are all "a
             * URL to display". `redirect` exists for the one that cannot be
             * framed, and because a build without a WebView can still honour
             * it. **A client that meets a mode it does not know must fall back
             * to `redirect`**, which is why the URL is always present.
             *
             * `return_url` and `cancel_url` are how the app knows the flow has
             * finished without reading the gateway's page: it watches for
             * navigation to either. They are Jambo's own URLs, so they do not
             * change when the gateway does.
             */
            return ApiResponse::ok([
                'checkout' => [
                    'mode' => 'webview',
                    'url' => $started['redirect_url'],
                    'return_url' => $this->paymentUrl('payment.complete'),
                    'cancel_url' => $this->paymentUrl('payment.complete', ['result' => 'cancelled']),
                ],
                'reference' => $started['merchant_reference'],
            ], 'Complete the payment to activate your plan.');
        } catch (Throwable $e) {
            Log::error('[payments] app checkout failed', [
                'user_id' => $request->user()->id,
                'tier_slug' => $tier->slug,
                'error' => $e->getMessage(),
            ]);

            $checkout->markFailed($order);

            return ApiResponse::error(
                ApiErrorCode::ServerError,
                'Could not start the payment. Please try again.',
            );
        }
    }

    /**
     * A callback the gateway can actually reach.
     *
     * `payments.callback_base_url` exists because a tunnelled dev box and the
     * production host are different origins, and PesaPal calls the URL it was
     * given rather than the one that looks right.
     */
    private function paymentUrl(string $routeName, array $params = []): string
    {
        $base = config('payments.callback_base_url');

        if ($base) {
            return rtrim($base, '/') . '/' . ltrim(route($routeName, $params, false), '/');
        }

        return route($routeName, $params);
    }

    /**
     * Payment history.
     *
     * Amount and currency come off the order row rather than the live tier: an
     * invoice must say what was actually paid, not what the plan costs today.
     */
    public function orders(Request $request): JsonResponse
    {
        $orders = PaymentOrder::query()
            ->where('user_id', $request->user()->id)
            // The plan name is on the payable. Without this, a page of 15
            // orders is 16 queries.
            ->with('payable')
            ->latest()
            // id as a tiebreaker: created_at is not unique, and a cursor on a
            // tied column silently drops every row that ties.
            ->orderByDesc('id')
            ->cursorPaginate(15);

        return ApiResponse::ok([
            'items' => $orders->getCollection()->map(fn (PaymentOrder $order) => $this->card($order))->values(),
            'next_cursor' => $orders->nextCursor()?->encode(),
            'totals' => $this->spendTotals($request->user()->id),
        ]);
    }

    /**
     * What this account has actually spent, across every page.
     *
     * **Summed in SQL over a decimal column, never in the client.** Two
     * separate things would go wrong otherwise. The app holds only the pages
     * it has fetched, so a total added up there is the first fifteen orders
     * wearing the word "total"; and the app's own `format.ts` records why the
     * digits are never recomputed — a float round-trip is how a figure stops
     * matching a bank statement. `SUM` on `decimal(10,2)` returns a decimal.
     *
     * **Completed only.** A pending charge is not money that has left an
     * account, and a failed one certainly is not. `payment_orders` is indexed
     * on `['user_id', 'status']`, so this is one indexed aggregate rather
     * than a scan.
     *
     * **Null when the account has paid in more than one currency**, rather
     * than a number. The column is `string('currency', 8)` with no constraint
     * tying an account to one, so mixed currencies are possible — and
     * "UGX 1,500,000" formed by adding shillings to dollars is the worst
     * answer available, because it looks exactly like a right one. The client
     * draws no summary in that case.
     *
     * @return array{spent: string|null, currency: string|null, orders: int}
     */
    private function spendTotals(int $userId): array
    {
        $rows = PaymentOrder::query()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->groupBy('currency')
            ->selectRaw('currency, SUM(amount) as spent, COUNT(*) as orders')
            ->get();

        if ($rows->count() !== 1) {
            return [
                'spent' => null,
                'currency' => null,
                // The count is still honest across currencies: it is a number
                // of orders, not an amount of money.
                'orders' => (int) $rows->sum('orders'),
            ];
        }

        $row = $rows->first();

        return [
            // Cast to string rather than to float, and formatted to the
            // column's own two places so the client is handed the same shape
            // `amount` arrives in on every other row.
            'spent' => number_format((float) $row->spent, 2, '.', ''),
            'currency' => (string) $row->currency,
            'orders' => (int) $row->orders,
        ];
    }

    public function order(Request $request, string $reference): JsonResponse
    {
        $order = PaymentOrder::query()
            ->where('user_id', $request->user()->id)
            ->where('merchant_reference', $reference)
            ->with('payable')
            ->first();

        if (! $order) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That order does not exist.');
        }

        return ApiResponse::ok(['order' => $this->card($order)]);
    }

    /**
     * One order, as the app's billing list and invoice read it.
     *
     * **What was bought comes off `payable`, and `payable` IS the tier** — not
     * a row that has one. The website's own billing and invoice blades read
     * `payable->tier->name` and its controller eager-loaded `payable.tier`,
     * which threw `RelationNotFoundException` for every account that had ever
     * paid. Both were repaired alongside this.
     *
     * `payable` is polymorphic by design — the table is meant to carry
     * rentals and merchandise later — so this asks what it is rather than
     * assuming. An order pointing at something that is not a plan simply has
     * no `plan` block, which is the honest answer and not an error.
     *
     * `payment_method` and `tracking_id` are here because the website's
     * invoice prints them, and an invoice a viewer cannot reconcile against
     * their bank statement is not an invoice.
     *
     * A `description` field used to be returned here. `payment_orders` has no
     * such column and never had one, so it was null on every order in the
     * table's life. Removed rather than left as a field that promises
     * something.
     *
     * @return array<string, mixed>
     */
    private function card(PaymentOrder $order): array
    {
        $tier = $order->payable instanceof SubscriptionTier ? $order->payable : null;

        return [
            'reference' => $order->merchant_reference,
            'status' => $order->status,
            // Amount and currency come off the order row rather than the live
            // tier: an invoice must say what was actually paid, not what the
            // plan costs today.
            'amount' => $order->amount,
            'currency' => $order->currency,
            'plan' => $tier ? [
                'name' => $tier->name,
                'billing_period' => $tier->billing_period,
            ] : null,
            'payment_method' => $order->payment_method,
            'tracking_id' => $order->order_tracking_id,
            'created_at' => optional($order->created_at)->toIso8601String(),
        ];
    }
}
