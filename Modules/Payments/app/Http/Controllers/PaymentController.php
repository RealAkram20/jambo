<?php

namespace Modules\Payments\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Payments\app\Contracts\PaymentGateway;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Payments\app\Services\PesapalGateway;
use Throwable;

/**
 * User-facing payment flow:
 *
 *   POST /payment/create-order   → auth required, creates PaymentOrder + returns redirect URL
 *   GET  /payment/callback       → browser redirect target from gateway (unauth)
 *   GET|POST /payment/ipn        → server-to-server webhook (unauth, CSRF exempt)
 *   GET  /payment/complete       → user-facing result page
 *
 * Both callback and IPN funnel through processPaymentResult(), which
 * is idempotent: whichever arrives first wins, the other becomes a
 * no-op. Activation of the underlying subscription/rental/etc. is
 * delegated to whatever module owns the PaymentOrder.payable — for
 * v1 we just update the order row and fire a `payment.completed`
 * event that the Subscriptions module will hook into later.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentGateway $gateway)
    {
    }

    /* -------------------------------------------------------------------- */
    /* createOrder                                                          */
    /* -------------------------------------------------------------------- */

    public function createOrder(Request $request)
    {
        // Two call styles supported:
        //   1. subscription_tier_id / tier_slug — pricing page flow. We
        //      resolve the tier, copy amount + currency off the tier so
        //      the UI can't lie about the price, and set payable_* to
        //      SubscriptionTier so ActivateSubscriptionFromPayment fires
        //      when the gateway confirms payment.
        //   2. amount / description — legacy/direct call for one-off
        //      payments (rentals, merch). payable_* optional.
        $data = $request->validate([
            'subscription_tier_id' => 'nullable|integer|exists:subscription_tiers,id',
            'tier_slug' => 'nullable|string|max:100|exists:subscription_tiers,slug',
            'amount' => 'nullable|numeric|min:1',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string|max:100',
            'payable_type' => 'nullable|string|max:100',
            'payable_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ]);

        if (!$this->gateway->isConfigured()) {
            return $this->createOrderFailure(
                $request,
                'Payments are not configured. Please contact support.',
                503,
            );
        }

        $user = $request->user();
        $tier = $this->resolveTier($data);

        if ($tier) {
            $amount = (float) $tier->price;
            $currency = $tier->currency ?: config('payments.currency', 'UGX');
            $description = "Jambo — {$tier->name}";
            $payableType = \Modules\Subscriptions\app\Models\SubscriptionTier::class;
            $payableId = $tier->id;
            $metadata = array_merge($data['metadata'] ?? [], [
                'tier_slug' => $tier->slug,
                'tier_name' => $tier->name,
                'billing_period' => $tier->billing_period,
            ]);
        } else {
            if (empty($data['amount']) || empty($data['description'])) {
                return $this->createOrderFailure(
                    $request,
                    'Amount and description are required for non-tier payments.',
                    422,
                );
            }
            $amount = (float) $data['amount'];
            $currency = $data['currency'] ?? config('payments.currency', 'UGX');
            $description = $data['description'];
            $payableType = $data['payable_type'] ?? null;
            $payableId = $data['payable_id'] ?? null;
            $metadata = $data['metadata'] ?? null;
        }

        $merchantRef = $this->buildMerchantReference($user->id);

        try {
            $order = DB::transaction(function () use ($user, $amount, $currency, $merchantRef, $payableType, $payableId, $metadata) {
                return PaymentOrder::create([
                    'user_id' => $user->id,
                    'payable_type' => $payableType,
                    'payable_id' => $payableId,
                    'merchant_reference' => $merchantRef,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => PaymentOrder::STATUS_PENDING,
                    'payment_gateway' => $this->gateway->slug(),
                    'metadata' => $metadata,
                ]);
            });

            event(new \Modules\Notifications\app\Events\OrderPlaced($order));

            $billingAddress = $this->buildBillingAddress($user);
            $callbackUrl = $this->callbackUrl('payment.callback');
            $cancellationUrl = $this->callbackUrl('payment.complete', ['result' => 'cancelled', 'ref' => $merchantRef]);

            $response = $this->gateway->submitOrder(
                merchantReference: $merchantRef,
                amount: $amount,
                currency: $currency,
                description: $description,
                callbackUrl: $callbackUrl,
                billingAddress: $billingAddress,
                cancellationUrl: $cancellationUrl,
            );

            $order->update([
                'order_tracking_id' => $response['order_tracking_id'],
                'raw_response' => $response['raw'] ?? null,
            ]);

            // Form posts (pricing page) redirect straight to the hosted
            // checkout; XHR / JSON callers get the URL to follow themselves.
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => true,
                    'redirect_url' => $response['redirect_url'],
                    'merchant_reference' => $merchantRef,
                ]);
            }

            return redirect()->away($response['redirect_url']);
        } catch (Throwable $e) {
            Log::error('[payments] createOrder failed', [
                'user_id' => $user->id,
                'merchant_reference' => $merchantRef,
                'error' => $e->getMessage(),
            ]);

            if (isset($order)) {
                $order->update(['status' => PaymentOrder::STATUS_FAILED]);
            }

            return $this->createOrderFailure(
                $request,
                'Could not start payment. Please try again.',
                500,
            );
        }
    }

    private function resolveTier(array $data): ?\Modules\Subscriptions\app\Models\SubscriptionTier
    {
        if (!empty($data['subscription_tier_id'])) {
            return \Modules\Subscriptions\app\Models\SubscriptionTier::where('id', $data['subscription_tier_id'])
                ->where('is_active', true)
                ->first();
        }
        if (!empty($data['tier_slug'])) {
            return \Modules\Subscriptions\app\Models\SubscriptionTier::where('slug', $data['tier_slug'])
                ->where('is_active', true)
                ->first();
        }
        return null;
    }

    private function createOrderFailure(Request $request, string $message, int $status)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'error' => $message], $status);
        }

        return redirect()
            ->route('frontend.pricing-page')
            ->with('error', $message);
    }

    /* -------------------------------------------------------------------- */
    /* callback + ipn                                                       */
    /* -------------------------------------------------------------------- */

    public function callback(Request $request): RedirectResponse
    {
        $merchantRef = $request->query('OrderMerchantReference');

        if (!$merchantRef) {
            return redirect()->route('payment.complete', [
                'result' => 'error',
                'message' => 'Missing order reference.',
            ]);
        }

        $order = PaymentOrder::where('merchant_reference', $merchantRef)->first();
        if (!$order) {
            return redirect()->route('payment.complete', [
                'result' => 'error',
                'message' => 'Order not found.',
            ]);
        }

        if ($order->isCompleted()) {
            return redirect()->route('payment.complete', [
                'result' => 'success',
                'ref' => $merchantRef,
            ]);
        }

        $finalStatus = $this->processPaymentResult($order, 'callback');

        return redirect()->route('payment.complete', [
            'result' => match ($finalStatus) {
                PaymentOrder::STATUS_COMPLETED => 'success',
                PaymentOrder::STATUS_FAILED => 'error',
                PaymentOrder::STATUS_CANCELLED => 'cancelled',
                default => 'pending',
            },
            'ref' => $merchantRef,
        ]);
    }

    public function ipn(Request $request): JsonResponse
    {
        $merchantRef = $request->input('OrderMerchantReference', $request->query('OrderMerchantReference'));

        if (!$merchantRef) {
            return response()->json(['ok' => false, 'error' => 'Missing reference'], 200);
        }

        $order = PaymentOrder::where('merchant_reference', $merchantRef)->first();
        if (!$order) {
            Log::warning('[payments] IPN for unknown order', ['merchant_reference' => $merchantRef]);
            return response()->json(['ok' => false, 'error' => 'Order not found'], 200);
        }

        if ($order->isCompleted()) {
            return response()->json(['ok' => true, 'status' => 'already_completed'], 200);
        }

        $finalStatus = $this->processPaymentResult($order, 'ipn');

        return response()->json(['ok' => true, 'status' => $finalStatus], 200);
    }

    public function complete(Request $request): View
    {
        $result = $request->query('result', 'pending');
        $ref = $request->query('ref');
        $order = $ref ? PaymentOrder::where('merchant_reference', $ref)->first() : null;

        return view('payments::complete', [
            'result' => $result,
            'order' => $order,
            'message' => $request->query('message'),
        ]);
    }

    /**
     * JSON status endpoint polled by the iframe modal on the pricing
     * page. Scoped to the signed-in user so one viewer can't snoop on
     * another's payment. Returns:
     *
     *   { status: 'pending' | 'completed' | 'failed' | 'cancelled',
     *     ref: string }
     *
     * The modal uses the status transition to close itself and redirect
     * to the complete page with the right result. We don't leak the raw
     * gateway payload — that's admin-only information.
     */
    public function status(Request $request, string $ref): JsonResponse
    {
        $order = PaymentOrder::where('merchant_reference', $ref)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json(['ok' => false, 'error' => 'Order not found.'], 404);
        }

        return response()->json([
            'ok' => true,
            'ref' => $order->merchant_reference,
            'status' => $order->status,
        ]);
    }

    /* -------------------------------------------------------------------- */
    /* Core: idempotent status reconciliation                               */
    /* -------------------------------------------------------------------- */

    private function processPaymentResult(PaymentOrder $order, string $source): string
    {
        // Re-read inside a lock so concurrent callback + IPN can't
        // double-activate.
        return DB::transaction(function () use ($order, $source) {
            $fresh = PaymentOrder::lockForUpdate()->find($order->id);

            if ($fresh->isCompleted()) {
                return $fresh->status;
            }

            // Always poll using the tracking ID we stored when the order
            // was submitted to the gateway. Trusting the tracking ID
            // from inbound query/body would let an attacker pair their
            // own pending merchant_reference with someone else's
            // completed tracking ID and have the gateway "confirm"
            // their order — paid by another transaction.
            if (!$fresh->order_tracking_id) {
                Log::warning('[payments] processPaymentResult without stored tracking id', [
                    'order_id' => $fresh->id,
                    'source' => $source,
                ]);
                return $fresh->status;
            }

            $status = $this->pollStatus($fresh->order_tracking_id);
            $normalised = $this->gateway->interpretStatus($status);

            $fresh->fill([
                'status' => $normalised,
                'raw_response' => $status,
                'confirmation_code' => $status['confirmation_code'] ?? $fresh->confirmation_code,
                'payment_method' => $status['payment_method'] ?? $fresh->payment_method,
            ])->save();

            if ($normalised === PaymentOrder::STATUS_COMPLETED) {
                $this->dispatchActivation($fresh, $source);
            } elseif ($normalised === PaymentOrder::STATUS_FAILED) {
                event(new \Modules\Notifications\app\Events\PaymentFailed(
                    $fresh,
                    $status['description'] ?? $status['payment_status_description'] ?? null,
                ));
            }

            return $normalised;
        });
    }

    /**
     * Poll the gateway status endpoint with short backoff — some
     * gateways (PesaPal especially) return PENDING on the first poll
     * even when the payment succeeded.
     */
    private function pollStatus(string $trackingId): array
    {
        $retries = config('payments.status_retries', [0, 1, 2, 3]);
        $last = [];

        foreach ($retries as $delay) {
            if ($delay > 0) {
                // Small delay is fine here — we're inside a single HTTP
                // request, not a loop over user input.
                usleep($delay * 500_000);
            }

            try {
                $last = $this->gateway->getTransactionStatus($trackingId);
            } catch (Throwable $e) {
                Log::warning('[payments] status poll failed', ['error' => $e->getMessage()]);
                continue;
            }

            $interpretation = $this->gateway->interpretStatus($last);
            if ($interpretation !== PaymentOrder::STATUS_PENDING) {
                return $last;
            }
        }

        return $last;
    }

    /**
     * V1: log the activation + fire a generic event. The Subscriptions
     * module (when built) will listen on `payment.completed` and flip
     * the relevant subscription / rental to active.
     */
    private function dispatchActivation(PaymentOrder $order, string $source): void
    {
        Log::info('[payments] activation dispatched', [
            'order_id' => $order->id,
            'merchant_reference' => $order->merchant_reference,
            'source' => $source,
            'payable_type' => $order->payable_type,
            'payable_id' => $order->payable_id,
        ]);

        event('payment.completed', [$order, $source]);
    }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function buildMerchantReference(int $userId): string
    {
        return sprintf('JAM-%d-%s', $userId, strtoupper(Str::random(10)));
    }

    private function buildBillingAddress($user): array
    {
        $email = $user->email ?? '';
        $first = $user->first_name ?? '';
        $last = $user->last_name ?? '';

        if ($first === '' && $last === '' && !empty($user->name)) {
            [$first, $last] = array_pad(explode(' ', $user->name, 2), 2, '');
        }

        return [
            'email_address' => $email,
            'first_name' => $first ?: 'Customer',
            'last_name' => $last ?: 'Jambo',
            // Uganda — the platform's home market. PesaPal validates this
            // as ISO 3166-1 alpha-2. If you open up to other markets
            // later, collect it on the user profile and pass it through.
            'country_code' => setting('payments.billing_country_code', 'UG'),
            'phone_number' => $user->phone ?? null,
        ];
    }

    private function callbackUrl(string $routeName, array $params = []): string
    {
        $base = config('payments.callback_base_url');
        if ($base) {
            $path = ltrim(route($routeName, $params, false), '/');
            return rtrim($base, '/') . '/' . $path;
        }

        return route($routeName, $params);
    }
}
