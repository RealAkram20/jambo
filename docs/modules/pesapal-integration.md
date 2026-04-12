# PesaPal Payment Integration — Reusable Pattern

A Laravel integration for PesaPal's v3 Payment API: OAuth token caching,
order creation, hosted checkout redirect, browser callback handling, and
server-to-server IPN (Instant Payment Notification) processing.

Based on the implementation at
[github.com/RealAkram20/Forever-Loved-updates](https://github.com/RealAkram20/Forever-Loved-updates)
(`app/Services/PesapalService.php`, `app/Http/Controllers/PaymentController.php`).

---

## What this integrates

PesaPal is a Kenyan payment aggregator that accepts card payments, mobile
money (M-Pesa, Airtel, MTN), and bank transfers. Their v3 API is
OAuth-based: you exchange a consumer key + secret for a short-lived
bearer token, then submit orders with that token.

Flow:

```
User clicks "Subscribe"
        │
        ▼
POST /payment/create-order               (your backend)
        │  ├─ create local PaymentOrder (status=pending)
        │  ├─ call PesapalService::submitOrder()
        │  │     ├─ get/refresh token
        │  │     └─ POST /api/Transactions/SubmitOrderRequest
        │  └─ return { redirect_url, order_tracking_id }
        │
        ▼
Browser redirects to PesaPal hosted page
        │
        ▼
User pays (card / M-Pesa / etc.)
        │
        ├── Browser redirects to /payment/callback  (you handle this)
        │
        └── Server gets POST /payment/ipn            (PesaPal sends this)
              │
              ▼
        Poll GetTransactionStatus → on COMPLETED,
        activate subscription + mark order paid
```

Both callback and IPN must be idempotent — the subscription activation
logic runs whichever arrives first, and becomes a no-op on the second.

---

## The service class

One class (`PesapalService`) owns the HTTP client + token cache + four
public methods. No facade. Inject it with `app(PesapalService::class)` or
via the constructor.

```php
namespace Modules\Payments\Services;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\Log;

class PesapalService
{
    private string $baseUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private bool $verifySsl;

    private ?string $token = null;
    private ?int $tokenExpiresAt = null;

    public function __construct()
    {
        $env = setting('payments.pesapal_environment', 'sandbox');
        $this->baseUrl = $env === 'live'
            ? 'https://pay.pesapal.com/v3'
            : 'https://cybqa.pesapal.com/pesapalv3';

        $this->consumerKey    = setting('payments.pesapal_consumer_key', '');
        $this->consumerSecret = setting('payments.pesapal_consumer_secret', '');
        $this->verifySsl      = (bool) env('PESAPAL_VERIFY_SSL', true);
    }

    // 1. Auth
    public function getToken(): string { /* see below */ }

    // 2. IPN registration (one-time, done from admin settings)
    public function registerIpn(string $url, string $type = 'POST'): array { /* ... */ }

    // 3. Order submission
    public function submitOrder(
        string $merchantReference,
        float $amount,
        string $currency,
        string $description,
        string $callbackUrl,
        array $billingAddress,
        ?string $cancellationUrl = null
    ): array { /* ... */ }

    // 4. Status polling
    public function getTransactionStatus(string $orderTrackingId): array { /* ... */ }
}
```

### Token caching

```php
public function getToken(): string
{
    // Reuse in-memory token until 60s before expiry
    if ($this->token && $this->tokenExpiresAt && time() < $this->tokenExpiresAt - 60) {
        return $this->token;
    }

    $response = Http::withOptions(['verify' => $this->verifySsl])
        ->acceptJson()
        ->asJson()
        ->post("{$this->baseUrl}/api/Auth/RequestToken", [
            'consumer_key'    => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ])->throw()->json();

    if (empty($response['token'])) {
        throw new \RuntimeException('PesaPal returned no token: ' . json_encode($response));
    }

    $this->token = $response['token'];
    // PesaPal tokens are valid ~5 minutes; they return an ISO8601 expiry.
    $this->tokenExpiresAt = strtotime($response['expiryDate'] ?? '+4 minutes');

    return $this->token;
}
```

Tokens live ~5 minutes. A single web request rarely needs more than one,
so in-memory caching in the service instance is enough. Don't persist the
token to `Cache::` unless you have a high-traffic job queue — you'll just
create a race on rotation.

### Submit order

```php
public function submitOrder(/* params */): array
{
    $token = $this->getToken();

    $payload = [
        'id'             => $merchantReference,
        'currency'       => $currency,
        'amount'         => round($amount, 2),
        'description'    => substr($description, 0, 100),
        'callback_url'   => $callbackUrl,
        'cancellation_url' => $cancellationUrl,
        'notification_id' => setting('payments.pesapal_ipn_id'),
        'billing_address' => $billingAddress,
    ];

    $response = Http::withOptions(['verify' => $this->verifySsl])
        ->withToken($token)
        ->acceptJson()
        ->asJson()
        ->post("{$this->baseUrl}/api/Transactions/SubmitOrderRequest", $payload)
        ->throw()
        ->json();

    if (empty($response['order_tracking_id'])) {
        throw new \RuntimeException('PesaPal submitOrder failed: ' . json_encode($response));
    }

    return $response;  // { order_tracking_id, merchant_reference, redirect_url, status }
}
```

### Status polling

```php
public function getTransactionStatus(string $orderTrackingId): array
{
    $token = $this->getToken();

    return Http::withOptions(['verify' => $this->verifySsl])
        ->withToken($token)
        ->acceptJson()
        ->get("{$this->baseUrl}/api/Transactions/GetTransactionStatus", [
            'orderTrackingId' => $orderTrackingId,
        ])->throw()->json();
}

public function isPaymentCompleted(array $status): bool
{
    return strtoupper($status['payment_status_description'] ?? '') === 'COMPLETED'
        && (int) ($status['status_code'] ?? 0) === 1;
}
```

PesaPal sometimes returns `status_code: 1` + `payment_status_description:
"COMPLETED"` on the second or third poll rather than the first. Callback
and IPN handlers both **must** retry with small backoff (see below).

---

## The payment order model

Single table, polymorphic-ish via a `metadata` JSON column.

```php
// database/migrations/xxxx_create_payment_orders_table.php

Schema::create('payment_orders', function (Blueprint $t) {
    $t->id();
    $t->foreignId('user_id')->constrained()->cascadeOnDelete();
    $t->foreignId('subscription_plan_id')->nullable()->constrained();
    $t->nullableMorphs('payable');           // movie/show/subscription target

    $t->string('merchant_reference')->unique();   // your internal id
    $t->string('order_tracking_id')->nullable();  // from PesaPal response
    $t->string('confirmation_code')->nullable();  // from PesaPal callback

    $t->decimal('amount', 10, 2);
    $t->string('currency', 8)->default('KES');

    $t->string('status')->default('pending');    // pending|completed|failed|cancelled
    $t->string('payment_gateway')->default('pesapal'); // pesapal|manual|stripe
    $t->string('payment_method')->nullable();    // card|mpesa|airtel

    $t->json('metadata')->nullable();            // anything you want to remember

    $t->timestamps();

    $t->index(['user_id', 'status']);
});
```

**Merchant reference format:** `SUB-{userId}-{uniqid}` or `SUB-{planId}-{time}`.
Must be unique, must be a valid string for PesaPal's `id` field (no weird
characters). Store it before calling `submitOrder()` so a failed PesaPal
call still leaves a `PaymentOrder` row you can reconcile against.

---

## Routes

```php
Route::middleware('auth')->group(function () {
    Route::post('/payment/create-order', [PaymentController::class, 'createOrder'])
        ->name('payment.create-order');
});

// Called by PesaPal after the user pays (browser redirect)
Route::get('/payment/callback', [PaymentController::class, 'callback'])
    ->name('payment.callback');

// Called by PesaPal's servers (machine-to-machine)
Route::match(['get', 'post'], '/payment/ipn', [PaymentController::class, 'ipn'])
    ->name('payment.ipn')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Shown to the user after callback processing
Route::get('/payment/complete', [PaymentController::class, 'complete'])
    ->name('payment.complete');
```

**Exempt `/payment/ipn` from CSRF.** PesaPal won't send a token.

---

## Config and settings

Two layers. Static config (never changes per deploy) lives in
`config/services.php`; dynamic config (admin-edited) lives in a
`system_settings` key-value table under the `payments.*` namespace.

### `.env`

```
PESAPAL_VERIFY_SSL=true
PESAPAL_CALLBACK_BASE_URL=https://yourdomain.co   # optional
```

Local XAMPP dev needs `PESAPAL_VERIFY_SSL=false` — XAMPP's bundled curl
cacert is often stale.

`PESAPAL_CALLBACK_BASE_URL` lets you override the callback domain for
cases where PesaPal can't hit your local Laravel instance (e.g. using a
tunnel for testing against sandbox). If blank, the app uses `APP_URL`.

### Admin-editable settings

Stored in the `settings` table by the payments admin page:

| Key | Type | Notes |
|---|---|---|
| `payments.enabled` | bool | master switch |
| `payments.currency` | string | `KES`, `USD`, ... |
| `payments.pesapal_enabled` | bool | per-gateway toggle |
| `payments.pesapal_consumer_key` | string | from PesaPal dashboard |
| `payments.pesapal_consumer_secret` | string | **encrypt before storing** |
| `payments.pesapal_environment` | enum | `sandbox` \| `live` |
| `payments.pesapal_ipn_id` | string | returned by `registerIpn()` — one-time setup |

Encrypt the secret with Laravel's `Crypt::encryptString()` on write and
`decryptString()` on read. Don't rely on "DB encryption at rest" for
credentials.

### One-time IPN registration

From the admin settings page, after saving credentials, call
`PesapalService::registerIpn($url, 'POST')` and save the returned
`ipn_id` into `payments.pesapal_ipn_id`. This tells PesaPal where to send
server-to-server notifications. You only do it once per environment.

---

## PaymentController responsibilities

Five methods.

### `createOrder(Request $request): JsonResponse`

1. Validate: authenticated user, plan id, currency matches gateway.
2. Build `merchant_reference` (`SUB-{user}-{uniqid}`).
3. Create `PaymentOrder` with `status=pending`, `payment_gateway=pesapal`,
   and a `metadata` blob containing whatever return context you need
   (`from_signup`, `target_id`, etc.).
4. Build billing address from user profile (name split, email, country
   code, phone if the user selected mobile money).
5. Build callback URL: `$pesapal->getCallbackUrl('payment.callback')`.
   Helper reads `PESAPAL_CALLBACK_BASE_URL` and falls back to `route()`.
6. Build cancellation URL: `payment.complete?result=cancelled`.
7. Call `$pesapal->submitOrder(...)`.
8. On success: store `order_tracking_id` on the `PaymentOrder`, return
   `{redirect_url, order_tracking_id}`.
9. On failure: update the order to `status=failed`, return a user-facing
   error JSON.

**Wrap the whole thing in a DB transaction.** Partial rows after a PesaPal
timeout are a reconciliation nightmare.

### `callback(Request $request): Response`

1. Read `OrderTrackingId` and `OrderMerchantReference` from the query.
2. Find the `PaymentOrder` by `merchant_reference`.
3. If already `completed`, redirect straight to the success page.
4. Otherwise, call `processPaymentResult($order, 'callback')` — see below.

### `ipn(Request $request): Response`

Same as callback but:

- Reads the same two params from `GET` *or* `POST`.
- Always returns `200 OK` once processing finishes, so PesaPal doesn't
  retry forever.
- Has no auth (exempted from CSRF).
- Source-tags the processing call: `processPaymentResult($order, 'ipn')`.

### `processPaymentResult(PaymentOrder $order, string $source): void`

The idempotent core. Both callback and IPN funnel through this.

1. If `$order->status === 'completed'`, return. (Idempotent.)
2. Poll `getTransactionStatus($order->order_tracking_id)` with retries:
   4 attempts, delays `0.5s, 1s, 1.5s, 2s`. PesaPal's sandbox is flaky
   and sometimes needs 2–3 polls before returning `COMPLETED`.
3. If `isPaymentCompleted($status)`:
   - Update order: `status=completed`, `confirmation_code = $status['confirmation_code']`, `payment_method = $status['payment_method']`.
   - Call `activateSubscription($order)`.
   - If `$source === 'callback'`, redirect to `payment.complete?result=success`.
4. If `isPaymentFailed($status)`:
   - Update order: `status=failed`.
   - If callback, redirect to `payment.complete?result=error`.
5. If still pending after all retries, leave the order pending — IPN will
   catch it later.

### `activateSubscription(PaymentOrder $order): void`

1. Lock on the target (memorial / user / whatever) with a SELECT FOR UPDATE
   or `Cache::lock()` to prevent double activation from concurrent IPN +
   callback.
2. Check if an active subscription already exists for this target + plan.
   If yes, bail out.
3. Expire any previous subscriptions on this target.
4. Compute `starts_at = now()`, `ends_at = now->addMonth()` (or
   `->addYear()` depending on the plan's `interval` column).
5. Create the `UserSubscription` row with `status=active`.
6. Flip the target row (`Memorial`, `User`, whatever) to `plan=paid`.

---

## Gotchas and clever bits

- **Both callback and IPN must complete the activation.** Either can
  arrive first. Use a lock to serialize, and make activation idempotent.
- **The callback handler runs in the user's browser.** It's therefore
  blocked by auth middleware if you put it behind `auth` — PesaPal
  doesn't send your user's session cookie. Leave the callback route
  unauthenticated and use the merchant reference (a non-guessable UUID)
  as the authorization token.
- **Status polling is slow the first time.** PesaPal's sandbox frequently
  returns `PENDING` on the first poll and `COMPLETED` on the second.
  Retry at least 4 times with backoff before giving up.
- **Never trust `payment_status_description` alone.** Also check
  `status_code === 1`. Some sandbox responses are inconsistent.
- **Store raw PesaPal payloads** on the `PaymentOrder` (`metadata` or a
  separate `raw_response` column). When something goes wrong in
  production, you'll want the exact JSON.
- **One-time tokens for the completion page.** If the completion page
  redirects away from the PesaPal iframe, the user's session may not be
  available (third-party cookie restrictions). Cache the result payload
  under a random token, pass the token as a query string, read + delete
  on the completion page.
- **Sandbox vs live uses different base URLs.** `cybqa.pesapal.com/pesapalv3`
  vs `pay.pesapal.com/v3`. Make the switch config-driven; don't hardcode.
- **Add a ledger view for admins.** A filterable list of PaymentOrders
  with their statuses and raw payloads. When a payment disappears into
  the void, this is the first thing you'll reach for.
- **Pesapal IP whitelist instead of HMAC.** There's no signature on the
  IPN — authenticity is based on PesaPal's published IP ranges. If you
  want real security, check `$request->ip()` against the official list.

---

## How to rebuild in Jambo

Drop this into [Modules/Payments/](../../Modules/Payments/):

1. `Modules/Payments/app/Contracts/PaymentGateway.php` — interface with
   `submitOrder()`, `getTransactionStatus()`, `isCompleted()`,
   `isFailed()`. Prepare for a second gateway (Stripe, Flutterwave) from
   day one.
2. `Modules/Payments/app/Services/PesapalGateway.php` — the
   implementation. Injects `Http` factory for testability.
3. `Modules/Payments/app/Http/Controllers/PaymentController.php` —
   `createOrder`, `callback`, `ipn`, `complete`, `processPaymentResult`,
   `activateSubscription`. Call `activateSubscription` from the
   `Modules/Subscriptions` module via a service, not directly — keeps
   the dependency one-way.
4. `Modules/Payments/app/Models/PaymentOrder.php` — Eloquent model, with
   `metadata` cast to array.
5. `Modules/Payments/database/migrations/...create_payment_orders_table.php`
6. `Modules/Payments/routes/web.php` — the four routes above.
7. `Modules/Payments/config/config.php` — sandbox URL, live URL, default
   currency, allowed gateways array.
8. `Modules/Payments/resources/views/admin/settings.blade.php` — admin
   config form (key, secret, environment, enabled toggle).
9. `Modules/Payments/resources/views/complete.blade.php` — success /
   error / cancelled / pending states.
10. Wire the subscribe button in `Modules/Subscriptions` to POST to
    `route('payment.create-order')`.

When both `Modules/Payments` and `Modules/Subscriptions` are built, the
subscribe-and-pay flow is one hop: user clicks Subscribe → payment
module creates order → PesaPal → callback → payment module activates
subscription via the Subscriptions service.
