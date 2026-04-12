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

    public function createOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'nullable|string|size:3',
            'description' => 'required|string|max:100',
            'payable_type' => 'nullable|string|max:100',
            'payable_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ]);

        if (!$this->gateway->isConfigured()) {
            return response()->json([
                'ok' => false,
                'error' => 'Payments are not configured. Please contact support.',
            ], 503);
        }

        $user = $request->user();
        $currency = $data['currency'] ?? config('payments.currency', 'KES');
        $merchantRef = $this->buildMerchantReference($user->id);

        try {
            $order = DB::transaction(function () use ($user, $data, $currency, $merchantRef) {
                return PaymentOrder::create([
                    'user_id' => $user->id,
                    'payable_type' => $data['payable_type'] ?? null,
                    'payable_id' => $data['payable_id'] ?? null,
                    'merchant_reference' => $merchantRef,
                    'amount' => $data['amount'],
                    'currency' => $currency,
                    'status' => PaymentOrder::STATUS_PENDING,
                    'payment_gateway' => $this->gateway->slug(),
                    'metadata' => $data['metadata'] ?? null,
                ]);
            });

            $billingAddress = $this->buildBillingAddress($user);
            $callbackUrl = $this->callbackUrl('payment.callback');
            $cancellationUrl = $this->callbackUrl('payment.complete', ['result' => 'cancelled', 'ref' => $merchantRef]);

            $response = $this->gateway->submitOrder(
                merchantReference: $merchantRef,
                amount: (float) $data['amount'],
                currency: $currency,
                description: $data['description'],
                callbackUrl: $callbackUrl,
                billingAddress: $billingAddress,
                cancellationUrl: $cancellationUrl,
            );

            $order->update([
                'order_tracking_id' => $response['order_tracking_id'],
                'raw_response' => $response['raw'] ?? null,
            ]);

            return response()->json([
                'ok' => true,
                'redirect_url' => $response['redirect_url'],
                'merchant_reference' => $merchantRef,
            ]);
        } catch (Throwable $e) {
            Log::error('[payments] createOrder failed', [
                'user_id' => $user->id,
                'merchant_reference' => $merchantRef,
                'error' => $e->getMessage(),
            ]);

            if (isset($order)) {
                $order->update(['status' => PaymentOrder::STATUS_FAILED]);
            }

            return response()->json([
                'ok' => false,
                'error' => 'Could not start payment. Please try again.',
            ], 500);
        }
    }

    /* -------------------------------------------------------------------- */
    /* callback + ipn                                                       */
    /* -------------------------------------------------------------------- */

    public function callback(Request $request): RedirectResponse
    {
        $merchantRef = $request->query('OrderMerchantReference');
        $trackingId = $request->query('OrderTrackingId');

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

        $finalStatus = $this->processPaymentResult($order, $trackingId, 'callback');

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
        $trackingId = $request->input('OrderTrackingId', $request->query('OrderTrackingId'));

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

        $finalStatus = $this->processPaymentResult($order, $trackingId, 'ipn');

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

    /* -------------------------------------------------------------------- */
    /* Core: idempotent status reconciliation                               */
    /* -------------------------------------------------------------------- */

    private function processPaymentResult(PaymentOrder $order, ?string $trackingId, string $source): string
    {
        // Re-read inside a lock so concurrent callback + IPN can't
        // double-activate.
        return DB::transaction(function () use ($order, $trackingId, $source) {
            $fresh = PaymentOrder::lockForUpdate()->find($order->id);

            if ($fresh->isCompleted()) {
                return $fresh->status;
            }

            $effectiveTracking = $trackingId ?: $fresh->order_tracking_id;
            if (!$effectiveTracking) {
                Log::warning('[payments] processPaymentResult without tracking id', [
                    'order_id' => $fresh->id,
                    'source' => $source,
                ]);
                return $fresh->status;
            }

            $status = $this->pollStatus($effectiveTracking);
            $normalised = $this->gateway->interpretStatus($status);

            $fresh->fill([
                'status' => $normalised,
                'raw_response' => $status,
                'confirmation_code' => $status['confirmation_code'] ?? $fresh->confirmation_code,
                'payment_method' => $status['payment_method'] ?? $fresh->payment_method,
            ])->save();

            if ($normalised === PaymentOrder::STATUS_COMPLETED) {
                $this->dispatchActivation($fresh, $source);
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
            'country_code' => 'KE',
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
