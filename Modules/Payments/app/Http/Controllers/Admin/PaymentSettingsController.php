<?php

namespace Modules\Payments\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Payments\app\Contracts\PaymentGateway;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Payments\app\Services\PesapalGateway;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Throwable;

/**
 * Admin UI for configuring payment gateways.
 *
 * Writes PesaPal credentials into the `settings` table under the
 * `payments.*` namespace. The consumer secret is encrypted with
 * Crypt::encryptString() on save and decrypted inside the gateway
 * service when it builds an auth token. Everything else is plaintext.
 *
 * Gated by `auth + role:admin` in the route file.
 */
class PaymentSettingsController extends Controller
{
    public function index(): View
    {
        // Only the last 5 on the settings page — Orders has its own
        // dedicated view now with pagination + filters.
        $recentOrders = PaymentOrder::latest()->limit(5)->get();

        return view('payments::admin.settings', [
            'values' => [
                'pesapal_enabled' => (bool) setting('payments.pesapal_enabled'),
                'pesapal_environment' => setting('payments.pesapal_environment', 'sandbox'),
                'pesapal_consumer_key' => setting('payments.pesapal_consumer_key', ''),
                'pesapal_consumer_secret_set' => !empty(setting('payments.pesapal_consumer_secret')),
                'pesapal_ipn_id' => setting('payments.pesapal_ipn_id', ''),
                'currency' => setting('payments.currency', config('payments.currency', 'UGX')),
            ],
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Dedicated Orders list. Filters: status, gateway, date range,
     * and a free-text search that matches merchant_reference,
     * order_tracking_id, or confirmation_code. Paginated 20 per page.
     */
    public function orders(Request $request): View
    {
        $query = PaymentOrder::query()
            ->with(['user:id,username,first_name,last_name,email'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($gateway = $request->query('gateway')) {
            $query->where('payment_gateway', $gateway);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('merchant_reference', 'like', "%$search%")
                    ->orWhere('order_tracking_id', 'like', "%$search%")
                    ->orWhere('confirmation_code', 'like', "%$search%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        // Status counts for the filter tabs. Scoped to the current
        // filter's date range so "pending: 12" matches what the table
        // shows, not a different window.
        $baseCountQuery = PaymentOrder::query();
        if ($from) $baseCountQuery->whereDate('created_at', '>=', $from);
        if ($to)   $baseCountQuery->whereDate('created_at', '<=', $to);
        $statusCounts = [
            'all' => (clone $baseCountQuery)->count(),
            'pending' => (clone $baseCountQuery)->where('status', 'pending')->count(),
            'completed' => (clone $baseCountQuery)->where('status', 'completed')->count(),
            'failed' => (clone $baseCountQuery)->where('status', 'failed')->count(),
            'cancelled' => (clone $baseCountQuery)->where('status', 'cancelled')->count(),
        ];

        return view('payments::admin.orders', [
            'orders' => $orders,
            'filters' => [
                'status' => $status ?: '',
                'gateway' => $gateway ?: '',
                'from' => $from ?: '',
                'to' => $to ?: '',
                'q' => $search,
            ],
            'statusCounts' => $statusCounts,
            'gateways' => array_keys(config('payments.gateways', ['pesapal' => null])),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pesapal_enabled' => 'nullable|boolean',
            'pesapal_environment' => 'required|in:sandbox,live',
            'pesapal_consumer_key' => 'nullable|string|max:255',
            'pesapal_consumer_secret' => 'nullable|string|max:255',
            'currency' => 'required|string|size:3',
        ]);

        setting(['payments.pesapal_enabled', $request->boolean('pesapal_enabled') ? '1' : '0']);
        setting(['payments.pesapal_environment', $data['pesapal_environment']]);
        setting(['payments.currency', strtoupper($data['currency'])]);

        if (!empty($data['pesapal_consumer_key'])) {
            setting(['payments.pesapal_consumer_key', $data['pesapal_consumer_key']]);
        }

        // Only overwrite the secret if a new value was provided — an
        // empty field means "leave the existing one alone".
        if (!empty($data['pesapal_consumer_secret'])) {
            setting(['payments.pesapal_consumer_secret', Crypt::encryptString($data['pesapal_consumer_secret'])]);
        }

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Payment settings saved.');
    }

    public function registerIpn(PesapalGateway $gateway): RedirectResponse
    {
        try {
            $url = route('payment.ipn');
            $ipnId = $gateway->registerIpn($url, 'POST');

            return redirect()
                ->route('admin.payments.index')
                ->with('success', "IPN registered with PesaPal. ID: $ipnId");
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', 'Could not register IPN: ' . $e->getMessage());
        }
    }

    /* -------------------------------------------------------------------- */
    /* Per-order admin actions: show, update, destroy, reconcile            */
    /* -------------------------------------------------------------------- */

    /**
     * Form for admin to record a manual / offline payment order —
     * cash, bank transfer, a refund we're booking back, etc. The
     * submitted row goes into payment_orders exactly like a gateway
     * order so reporting (status counts, billing history, etc.) works
     * uniformly across the two sources.
     */
    public function createOrderForm(): View
    {
        return view('payments::admin.order-create', [
            'users' => User::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'username', 'email']),
            'tiers' => SubscriptionTier::active()->ordered()->get(),
            'defaultCurrency' => setting('payments.currency', config('payments.currency', 'UGX')),
        ]);
    }

    /**
     * Store a manually-created order. When the admin marks the order
     * as `completed`, we fire `payment.completed` — same hook
     * callback/IPN/reconcile use — so the subscription activation
     * listener picks it up and creates (or renews) the UserSubscription.
     *
     * Merchant reference prefix is `MAN-` (manual) to distinguish
     * from gateway-issued `JAM-` references at a glance.
     */
    public function storeOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'subscription_tier_id' => 'required|integer|exists:subscription_tiers,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'payment_gateway' => 'required|string|max:50',
            'payment_method' => 'nullable|string|max:50',
            'status' => 'required|in:pending,completed,failed,cancelled',
            'confirmation_code' => 'nullable|string|max:100',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $tier = SubscriptionTier::findOrFail($data['subscription_tier_id']);
        $merchantRef = sprintf('MAN-%d-%s', $data['user_id'], strtoupper(Str::random(10)));

        $metadata = [
            'tier_slug' => $tier->slug,
            'tier_name' => $tier->name,
            'billing_period' => $tier->billing_period,
            'created_manually' => true,
            'created_by' => $request->user()->username,
        ];
        if (!empty($data['admin_notes'])) {
            $metadata['admin_notes'] = trim($data['admin_notes']);
            $metadata['admin_notes_at'] = now()->toIso8601String();
            $metadata['admin_notes_by'] = $request->user()->username;
        }

        $order = DB::transaction(function () use ($data, $tier, $merchantRef, $metadata) {
            return PaymentOrder::create([
                'user_id' => $data['user_id'],
                // Tying to SubscriptionTier so the activation listener
                // knows which tier to provision on completed.
                'payable_type' => SubscriptionTier::class,
                'payable_id' => $tier->id,
                'merchant_reference' => $merchantRef,
                'amount' => $data['amount'],
                'currency' => strtoupper($data['currency']),
                'status' => $data['status'],
                'payment_gateway' => strtolower($data['payment_gateway']),
                'payment_method' => $data['payment_method'] ?: null,
                'confirmation_code' => $data['confirmation_code'] ?: null,
                'metadata' => $metadata,
            ]);
        });

        // Same activation hook the gateway path uses, so a manual
        // order with status=completed spins up the UserSubscription
        // right away — no follow-up admin step required.
        if ($order->status === PaymentOrder::STATUS_COMPLETED) {
            event('payment.completed', [$order, 'admin-manual']);
        }

        Log::info('[payments] manual order created by admin', [
            'merchant_reference' => $merchantRef,
            'user_id' => $data['user_id'],
            'tier' => $tier->slug,
            'status' => $data['status'],
            'by' => $request->user()->username,
        ]);

        return redirect()
            ->route('admin.payments.orders.show', $order)
            ->with('success', "Manual order {$merchantRef} created.");
    }

    /**
     * Full order detail page. Shows everything (user, gateway payload,
     * linked subscription) and hosts the edit form + action buttons.
     */
    public function showOrder(PaymentOrder $order): View
    {
        $order->load(['user:id,username,first_name,last_name,email,phone']);

        // Find any UserSubscription this order activated so the admin
        // can see the downstream effect before deleting.
        $linkedSubscription = UserSubscription::with('tier:id,name,slug')
            ->where('payment_order_id', $order->id)
            ->first();

        return view('payments::admin.order-show', [
            'order' => $order,
            'linkedSubscription' => $linkedSubscription,
        ]);
    }

    /**
     * Admin-editable fields only. We explicitly DO NOT let admins
     * change amount, currency, merchant_reference, order_tracking_id
     * or confirmation_code — those are authoritative financial fields
     * and mutating them would break the audit trail against PesaPal.
     *
     * Status override is allowed but does NOT fire the subscription-
     * activation event (admin handles that side manually via the
     * subscriptions admin). Use "Reconcile with gateway" for the
     * normal catch-up flow where IPN never arrived.
     */
    public function updateOrder(Request $request, PaymentOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:pending,completed,failed,cancelled',
            'payment_method' => 'nullable|string|max:50',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $oldStatus = $order->status;
        $metadata = $order->metadata ?? [];

        if (!empty($data['admin_notes'])) {
            $metadata['admin_notes'] = trim($data['admin_notes']);
            $metadata['admin_notes_at'] = now()->toIso8601String();
            $metadata['admin_notes_by'] = $request->user()->username;
        }

        // Leave a small audit trail whenever status is overridden
        // manually. Stamps who / when / from→to so we can reconstruct
        // what happened if a payment dispute lands later.
        if ($data['status'] !== $oldStatus) {
            $history = $metadata['status_overrides'] ?? [];
            $history[] = [
                'from' => $oldStatus,
                'to' => $data['status'],
                'at' => now()->toIso8601String(),
                'by' => $request->user()->username,
            ];
            $metadata['status_overrides'] = $history;
        }

        $order->update([
            'status' => $data['status'],
            'payment_method' => $data['payment_method'] ?: $order->payment_method,
            'metadata' => $metadata,
        ]);

        return redirect()
            ->route('admin.payments.orders.show', $order)
            ->with('success', 'Order updated.');
    }

    /**
     * Hard-delete with guards:
     *   • Orders that activated a UserSubscription can't be deleted
     *     unless the subscription is also gone — deleting would leave
     *     a dangling subscription with no payment trail.
     *   • Completed orders block delete by default (safer); admin
     *     must manually cancel the subscription first.
     *   • Pending / failed / cancelled orders with no linked sub can
     *     be removed freely.
     */
    public function destroyOrder(PaymentOrder $order): RedirectResponse
    {
        $linkedSub = UserSubscription::where('payment_order_id', $order->id)->first();

        if ($linkedSub) {
            return redirect()
                ->route('admin.payments.orders.show', $order)
                ->with('error', "Can't delete — this order activated subscription #{$linkedSub->id}. Cancel that subscription first.");
        }

        if ($order->status === PaymentOrder::STATUS_COMPLETED) {
            return redirect()
                ->route('admin.payments.orders.show', $order)
                ->with('error', "Can't delete a completed order. Change its status first if this was a reconciliation error, or leave it alone to preserve the audit trail.");
        }

        $ref = $order->merchant_reference;
        $order->delete();

        Log::info('[payments] order deleted by admin', [
            'merchant_reference' => $ref,
        ]);

        return redirect()
            ->route('admin.payments.orders')
            ->with('success', "Order {$ref} deleted.");
    }

    /**
     * Re-poll the gateway and update the local order to match. This
     * is the canonical "catch up" path for orders stuck in pending
     * because IPN never arrived (common on local dev without a
     * tunnel, or when PesaPal's sandbox times out mid-webhook).
     *
     * Transitions from non-completed → completed fire the same
     * `payment.completed` event as callback/IPN, so the subscription
     * activation listener runs.
     */
    public function reconcileOrder(PaymentOrder $order, PaymentGateway $gateway): RedirectResponse
    {
        if (!$order->order_tracking_id) {
            return redirect()
                ->route('admin.payments.orders.show', $order)
                ->with('error', 'No tracking ID on this order — nothing to reconcile. The gateway submitOrder call probably failed before we got one back.');
        }

        try {
            $raw = $gateway->getTransactionStatus($order->order_tracking_id);
            $normalised = $gateway->interpretStatus($raw);
            $oldStatus = $order->status;

            DB::transaction(function () use ($order, $raw, $normalised) {
                $order->fill([
                    'status' => $normalised,
                    'confirmation_code' => $raw['confirmation_code'] ?? $order->confirmation_code,
                    'payment_method' => $raw['payment_method'] ?? $order->payment_method,
                    'raw_response' => $raw,
                ])->save();
            });

            // Same hook as callback/IPN so the subscription activation
            // listener fires on the transition to completed.
            if ($oldStatus !== PaymentOrder::STATUS_COMPLETED && $normalised === PaymentOrder::STATUS_COMPLETED) {
                event('payment.completed', [$order->fresh(), 'admin-reconcile']);
            }

            return redirect()
                ->route('admin.payments.orders.show', $order)
                ->with('success', "Reconciled with gateway. Status: {$oldStatus} → {$normalised}.");
        } catch (Throwable $e) {
            Log::error('[payments] reconcile failed', [
                'merchant_reference' => $order->merchant_reference,
                'error' => $e->getMessage(),
            ]);
            return redirect()
                ->route('admin.payments.orders.show', $order)
                ->with('error', 'Reconcile failed: ' . $e->getMessage());
        }
    }
}
