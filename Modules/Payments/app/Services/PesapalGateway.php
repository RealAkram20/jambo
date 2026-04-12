<?php

namespace Modules\Payments\app\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Payments\app\Contracts\PaymentGateway;
use RuntimeException;
use Throwable;

/**
 * PesaPal v3 payment gateway.
 *
 * Credentials live in the existing `settings` table under the
 * `payments.pesapal_*` namespace, edited from the admin Payments page.
 * The consumer secret is stored encrypted with Laravel's Crypt facade;
 * everything else is plaintext because it needs to be visible in the
 * admin form for debugging.
 *
 * Base URL switches between sandbox and live based on
 * `payments.pesapal_environment`:
 *
 *   sandbox → https://cybqa.pesapal.com/pesapalv3
 *   live    → https://pay.pesapal.com/v3
 *
 * OAuth tokens are cached in memory on the service instance (PesaPal
 * tokens last ~5 minutes; a single web request rarely needs more than
 * one).
 */
class PesapalGateway implements PaymentGateway
{
    private ?string $token = null;
    private ?int $tokenExpiresAt = null;

    public function slug(): string
    {
        return 'pesapal';
    }

    public function isConfigured(): bool
    {
        return (bool) setting('payments.pesapal_enabled')
            && setting('payments.pesapal_consumer_key')
            && setting('payments.pesapal_consumer_secret');
    }

    public function submitOrder(
        string $merchantReference,
        float $amount,
        string $currency,
        string $description,
        string $callbackUrl,
        array $billingAddress,
        ?string $cancellationUrl = null
    ): array {
        if (!$this->isConfigured()) {
            throw new RuntimeException('PesaPal is not configured.');
        }

        $payload = [
            'id' => $merchantReference,
            'currency' => $currency,
            'amount' => round($amount, 2),
            'description' => mb_substr($description, 0, 100),
            'callback_url' => $callbackUrl,
            'notification_id' => setting('payments.pesapal_ipn_id'),
            'billing_address' => $billingAddress,
        ];

        if ($cancellationUrl) {
            $payload['cancellation_url'] = $cancellationUrl;
        }

        $response = $this->client()
            ->withToken($this->getToken())
            ->post($this->baseUrl() . '/api/Transactions/SubmitOrderRequest', $payload);

        if (!$response->ok()) {
            throw new RuntimeException('PesaPal submitOrder HTTP ' . $response->status() . ': ' . $response->body());
        }

        $json = $response->json();
        if (empty($json['order_tracking_id']) || empty($json['redirect_url'])) {
            throw new RuntimeException('PesaPal submitOrder returned an unexpected payload: ' . json_encode($json));
        }

        return [
            'redirect_url' => $json['redirect_url'],
            'order_tracking_id' => $json['order_tracking_id'],
            'raw' => $json,
        ];
    }

    public function getTransactionStatus(string $orderTrackingId): array
    {
        $response = $this->client()
            ->withToken($this->getToken())
            ->get($this->baseUrl() . '/api/Transactions/GetTransactionStatus', [
                'orderTrackingId' => $orderTrackingId,
            ]);

        if (!$response->ok()) {
            throw new RuntimeException('PesaPal getTransactionStatus HTTP ' . $response->status());
        }

        return $response->json() ?? [];
    }

    public function interpretStatus(array $rawStatus): string
    {
        $desc = strtoupper((string) ($rawStatus['payment_status_description'] ?? ''));
        $code = (int) ($rawStatus['status_code'] ?? 0);

        if ($desc === 'COMPLETED' && $code === 1) {
            return 'completed';
        }

        if (in_array($desc, ['FAILED', 'INVALID'], true)) {
            return 'failed';
        }

        if ($desc === 'REVERSED') {
            return 'cancelled';
        }

        return 'pending';
    }

    /**
     * Register (or re-register) the IPN callback URL with PesaPal and
     * persist the returned notification ID to settings. Only called
     * from the admin settings page after saving credentials.
     */
    public function registerIpn(string $url, string $type = 'POST'): string
    {
        $response = $this->client()
            ->withToken($this->getToken())
            ->post($this->baseUrl() . '/api/URLSetup/RegisterIPN', [
                'url' => $url,
                'ipn_notification_type' => $type,
            ]);

        if (!$response->ok()) {
            throw new RuntimeException('PesaPal registerIpn HTTP ' . $response->status() . ': ' . $response->body());
        }

        $json = $response->json();
        $ipnId = $json['ipn_id'] ?? null;
        if (!$ipnId) {
            throw new RuntimeException('PesaPal registerIpn returned no ipn_id: ' . json_encode($json));
        }

        setting(['payments.pesapal_ipn_id', $ipnId]);
        return $ipnId;
    }

    /* -------------------------------------------------------------------- */
    /* Internals                                                            */
    /* -------------------------------------------------------------------- */

    private function getToken(): string
    {
        if ($this->token && $this->tokenExpiresAt && time() < $this->tokenExpiresAt - 60) {
            return $this->token;
        }

        $response = $this->client()
            ->post($this->baseUrl() . '/api/Auth/RequestToken', [
                'consumer_key' => setting('payments.pesapal_consumer_key'),
                'consumer_secret' => $this->decryptSecret(),
            ]);

        if (!$response->ok()) {
            throw new RuntimeException('PesaPal RequestToken HTTP ' . $response->status() . ': ' . $response->body());
        }

        $json = $response->json();
        if (empty($json['token'])) {
            throw new RuntimeException('PesaPal returned no token: ' . json_encode($json));
        }

        $this->token = $json['token'];
        $this->tokenExpiresAt = isset($json['expiryDate'])
            ? strtotime($json['expiryDate'])
            : (time() + 4 * 60);

        return $this->token;
    }

    private function decryptSecret(): string
    {
        $raw = setting('payments.pesapal_consumer_secret', '');
        if (!$raw) {
            return '';
        }

        try {
            return Crypt::decryptString($raw);
        } catch (Throwable) {
            // Not yet encrypted (migration from an older admin page, or
            // a hand-inserted value). Return as-is.
            return $raw;
        }
    }

    private function baseUrl(): string
    {
        $env = setting('payments.pesapal_environment', 'sandbox');
        return $env === 'live'
            ? 'https://pay.pesapal.com/v3'
            : 'https://cybqa.pesapal.com/pesapalv3';
    }

    private function client()
    {
        $verify = filter_var(env('PESAPAL_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN);
        return Http::withOptions(['verify' => $verify])
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('payments.http_timeout', 30));
    }
}
