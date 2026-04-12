<?php

namespace Modules\Payments\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Payments\app\Services\PesapalGateway;
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
        $recentOrders = PaymentOrder::latest()->limit(10)->get();

        return view('payments::admin.settings', [
            'values' => [
                'pesapal_enabled' => (bool) setting('payments.pesapal_enabled'),
                'pesapal_environment' => setting('payments.pesapal_environment', 'sandbox'),
                'pesapal_consumer_key' => setting('payments.pesapal_consumer_key', ''),
                'pesapal_consumer_secret_set' => !empty(setting('payments.pesapal_consumer_secret')),
                'pesapal_ipn_id' => setting('payments.pesapal_ipn_id', ''),
                'currency' => setting('payments.currency', config('payments.currency', 'KES')),
            ],
            'recentOrders' => $recentOrders,
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
}
