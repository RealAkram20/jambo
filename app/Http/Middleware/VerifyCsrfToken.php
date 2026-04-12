<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // PesaPal IPN is a server-to-server webhook. It has no user
        // session and no CSRF token; the gateway identifies itself
        // by source IP and by the merchant reference in the payload.
        'payment/ipn',
    ];
}
