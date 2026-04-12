<?php

return [
    'name' => 'Payments',

    /*
    |--------------------------------------------------------------------------
    | Default gateway
    |--------------------------------------------------------------------------
    |
    | Slug of the gateway chosen when createOrder() is called without an
    | explicit gateway. Must match a key in the `gateways` array below.
    |
    */
    'default_gateway' => env('JAMBO_DEFAULT_GATEWAY', 'pesapal'),

    /*
    |--------------------------------------------------------------------------
    | Registered gateways
    |--------------------------------------------------------------------------
    |
    | Map of slug => fully-qualified class name. Each class must
    | implement Modules\Payments\app\Contracts\PaymentGateway. The
    | service provider reads this list at register() time to bind each
    | implementation into the container, so resolving
    | `app(PaymentGateway::class)` returns the default, and
    | `app("payments.gateway.stripe")` returns a specific one.
    |
    */
    'gateways' => [
        'pesapal' => \Modules\Payments\app\Services\PesapalGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('JAMBO_CURRENCY', 'KES'),

    /*
    |--------------------------------------------------------------------------
    | HTTP client timeout for gateway calls (seconds)
    |--------------------------------------------------------------------------
    */
    'http_timeout' => 30,

    /*
    |--------------------------------------------------------------------------
    | Status retry backoff (seconds between each attempt)
    |--------------------------------------------------------------------------
    |
    | Gateway status endpoints are eventually-consistent. We poll a few
    | times with backoff before giving up and leaving the order pending.
    |
    */
    'status_retries' => [0, 1, 2, 3],

    /*
    |--------------------------------------------------------------------------
    | Callback URL base override
    |--------------------------------------------------------------------------
    |
    | Optional override for the domain used in payment callback URLs.
    | Useful when PesaPal (or any other gateway) can't reach a local
    | development URL and you're using a tunnel. Falls back to APP_URL.
    |
    */
    'callback_base_url' => env('PESAPAL_CALLBACK_BASE_URL'),
];
