<?php

namespace Modules\Payments\app\Contracts;

/**
 * A payment gateway Jambo can submit orders to.
 *
 * Each implementation hides the vendor-specific auth + API flow behind
 * these four methods, so the rest of the app never imports a PesaPal /
 * Stripe / Flutterwave class directly.
 */
interface PaymentGateway
{
    /**
     * Machine-readable gateway slug (`pesapal`, `stripe`, ...). Stored
     * on PaymentOrder.payment_gateway.
     */
    public function slug(): string;

    /**
     * True if this gateway has all required credentials and is enabled
     * in the admin settings.
     */
    public function isConfigured(): bool;

    /**
     * Submit a new order and return `['redirect_url' => ..., 'order_tracking_id' => ...]`.
     * The caller is responsible for persisting a local PaymentOrder
     * before calling this — the merchant reference passed here must
     * already exist on disk.
     *
     * @throws \RuntimeException if the submission fails.
     *
     * @return array{redirect_url: string, order_tracking_id: string, raw: array}
     */
    public function submitOrder(
        string $merchantReference,
        float $amount,
        string $currency,
        string $description,
        string $callbackUrl,
        array $billingAddress,
        ?string $cancellationUrl = null
    ): array;

    /**
     * Fetch the latest status for an order. Returns the raw gateway
     * response; callers should feed it into `interpretStatus()` to get
     * a stable Jambo-level status.
     *
     * @return array  raw gateway payload
     */
    public function getTransactionStatus(string $orderTrackingId): array;

    /**
     * Normalise a raw status payload to one of:
     *   'pending' | 'completed' | 'failed' | 'cancelled'
     *
     * Each gateway has its own status codes; this is where we fold them
     * into something the PaymentController can reason about.
     */
    public function interpretStatus(array $rawStatus): string;
}
