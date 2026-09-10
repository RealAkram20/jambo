<?php

/*
 * Payment history for the test account. Local database only.
 *
 * WHY THIS STEP EXISTS. `testuser@jambo.test` has a live subscription and
 * ZERO rows in `payment_orders` — checked, not assumed:
 *
 *   {"success":true,"message":"OK","data":{"items":[],"next_cursor":null}}
 *
 * So the app's Billing screen renders its empty state and nothing else, and
 * the Invoice screen cannot be reached at all. A screen verified only in its
 * empty state is a screen nobody has seen. This step writes a handful of
 * orders against the test account so both can be rendered on a device.
 *
 * WHAT THE ROWS ARE FOR. Four orders, chosen so each one exercises something
 * the screens have to get right rather than to look plausible:
 *
 *   - a completed monthly plan, which is the ordinary row;
 *   - a completed yearly plan at a much larger amount, so the money column is
 *     tested against a figure that does not fit the same width;
 *   - a PENDING order, because the website badges anything that is not
 *     completed as a warning and the app must not colour it as a success;
 *   - an order with a NULL payment_method and a NULL tracking_id, which is
 *     what a real order looks like before the gateway has reported, and is
 *     the case an invoice built on optimism would render as the word "null".
 *
 * One of them also carries `payable_type = null`. That is deliberate: the
 * table is polymorphic and `SubscriptionController::card()` returns no `plan`
 * block for a payable that is not a plan. Without a row like it, that branch
 * is only ever exercised by a unit test.
 *
 * These rows are INERT. `subscription_applied_at` is stamped on every
 * completed one so nothing here can trip
 * `ActivateSubscriptionFromPayment` into granting the test account a free
 * extension if an event is ever replayed, and `payment_gateway` is `manual`,
 * so no gateway is ever polled about them.
 *
 * THE REFERENCES ALL START `DEV-BILL-`, which is how --reset finds them and
 * how a human reading the payments admin can tell them from real money. Three
 * sessions share this database; nothing outside that prefix is touched.
 *
 * Usage:
 *   php artisan tinker --execute="require 'tools/dev-catalogue/10-billing-orders.php';"
 *   JAMBO_BILLING_RESET=1 php artisan tinker --execute="require 'tools/dev-catalogue/10-billing-orders.php';"
 */

use Modules\Payments\app\Models\PaymentOrder;
use Modules\Subscriptions\app\Models\SubscriptionTier;

$email = getenv('JAMBO_TEST_EMAIL') ?: 'testuser@jambo.test';
$reset = (bool) getenv('JAMBO_BILLING_RESET');

$user = App\Models\User::where('email', $email)->first();

if (! $user) {
    echo "No user {$email}. Run 6-test-user.php first.\n";

    return;
}

if ($reset) {
    $gone = PaymentOrder::where('user_id', $user->id)
        ->where('merchant_reference', 'like', 'DEV-BILL-%')
        ->delete();

    echo "Removed {$gone} DEV-BILL- orders from {$email}.\n";

    return;
}

/*
 * Real tiers, looked up rather than invented, so the plan name the screens
 * show is a name that exists in this database.
 */
$monthly = SubscriptionTier::where('billing_period', 'monthly')->where('price', '>', 0)->orderBy('price')->first();
$yearly = SubscriptionTier::where('billing_period', 'yearly')->first() ?? $monthly;

if (! $monthly) {
    echo "No priced monthly tier in this database. Nothing to point an order at.\n";

    return;
}

$rows = [
    [
        'merchant_reference' => 'DEV-BILL-0001',
        'payable' => $monthly,
        'amount' => $monthly->price,
        'status' => PaymentOrder::STATUS_COMPLETED,
        'payment_method' => 'mpesa',
        'order_tracking_id' => 'PSP-8842-113A',
        'created_at' => now()->subMonths(3),
    ],
    [
        'merchant_reference' => 'DEV-BILL-0002',
        'payable' => $yearly,
        'amount' => $yearly->price,
        'status' => PaymentOrder::STATUS_COMPLETED,
        'payment_method' => 'card',
        'order_tracking_id' => 'PSP-9017-77BX',
        'created_at' => now()->subMonths(2),
    ],
    [
        // Before the gateway has said anything back. Both nullable columns
        // really are null, which is the case the invoice must not print.
        'merchant_reference' => 'DEV-BILL-0003',
        'payable' => $monthly,
        'amount' => $monthly->price,
        'status' => PaymentOrder::STATUS_PENDING,
        'payment_method' => null,
        'order_tracking_id' => null,
        'created_at' => now()->subDays(2),
    ],
    [
        // No payable at all: the polymorphic branch that returns no plan.
        'merchant_reference' => 'DEV-BILL-0004',
        'payable' => null,
        'amount' => '2500.00',
        'status' => PaymentOrder::STATUS_COMPLETED,
        'payment_method' => 'airtel',
        'order_tracking_id' => 'PSP-4410-02QQ',
        'created_at' => now()->subDays(9),
    ],
];

$written = 0;

foreach ($rows as $row) {
    $payable = $row['payable'];

    $order = PaymentOrder::updateOrCreate(
        ['merchant_reference' => $row['merchant_reference']],
        [
            'user_id' => $user->id,
            'payable_type' => $payable ? $payable->getMorphClass() : null,
            'payable_id' => $payable?->getKey(),
            'order_tracking_id' => $row['order_tracking_id'],
            'amount' => $row['amount'],
            'currency' => $payable->currency ?? 'UGX',
            'status' => $row['status'],
            'payment_gateway' => 'manual',
            'payment_method' => $row['payment_method'],
            // Stamped so a replayed payment.completed cannot grant this
            // account a free extension off a fixture.
            'subscription_applied_at' => $row['status'] === PaymentOrder::STATUS_COMPLETED ? now() : null,
        ]
    );

    // created_at drives the ordering the screens are checked against, and
    // updateOrCreate would otherwise stamp them all with the same second.
    $order->forceFill(['created_at' => $row['created_at']])->saveQuietly();

    $written++;
}

echo "Wrote {$written} DEV-BILL- orders for {$email} (user {$user->id}).\n";
echo "Reset with: JAMBO_BILLING_RESET=1 php artisan tinker --execute=\"require 'tools/dev-catalogue/10-billing-orders.php';\"\n";
