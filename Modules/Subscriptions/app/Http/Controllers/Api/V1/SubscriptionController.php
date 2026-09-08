<?php

namespace Modules\Subscriptions\app\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Streaming\app\Services\PlaybackAuthorizer;
use Modules\Subscriptions\app\Models\SubscriptionTier;

/**
 * Plans, the viewer's current plan, and their payment history.
 *
 * **Read-only, and that is a decision.** ADR-0004 splits the app in two: the
 * Google Play build is consumption-only — no purchase, no checkout, no link to
 * a payment page, because Play's Payments policy requires Play Billing for
 * subscription content in Uganda and a consumption-only app is the documented
 * way to stay compliant. Only the direct APK carries in-app PesaPal.
 *
 * So these endpoints serve both builds: the Play build shows the plan and the
 * renewal date, and the direct build will add checkout on top.
 *
 * **In-app checkout is deliberately not here.** `PaymentController::createOrder`
 * holds real money logic — the price is copied off the tier server-side so a
 * client cannot name its own, the amount is frozen in a snapshot so an admin
 * editing a price mid-checkout cannot void a genuine payment, and a referral
 * discount is computed and recorded server-side. It also explicitly refuses a
 * client that pairs an arbitrary amount with a SubscriptionTier payable, which
 * is the attack that would otherwise buy Premium for one shilling. Reusing
 * that safely means extracting it into a service both callers share, and
 * money code earns its own careful slice rather than being tacked onto the end
 * of another one. See docs/api/coverage.md.
 */
class SubscriptionController extends Controller
{
    /**
     * The plans on offer.
     *
     * Public: the website's pricing page is, and the Play build needs to show
     * what a plan costs even though it cannot sell one.
     */
    public function plans(): JsonResponse
    {
        $tiers = SubscriptionTier::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('access_level')
            ->get()
            ->map(fn (SubscriptionTier $tier) => [
                'slug' => $tier->slug,
                'name' => $tier->name,
                'description' => $tier->description,
                'price' => $tier->price,
                'currency' => $tier->currency,
                'billing_period' => $tier->billing_period,
                'access_level' => $tier->access_level,
                // null means the plan sets no cap. Render "unlimited", never
                // "0" and never "none".
                'max_concurrent_streams' => $tier->max_concurrent_streams,
            ]);

        return ApiResponse::ok(['plans' => $tiers]);
    }

    /**
     * What this viewer is on, and until when.
     *
     * `GET /me` carries a summary for the launch screen; this is the
     * membership screen, with the plan list beside it so an upgrade prompt can
     * be drawn from one request.
     */
    public function show(Request $request, PlaybackAuthorizer $authorizer): JsonResponse
    {
        $subscription = $authorizer->activeSubscription($request->user());
        $tier = $subscription?->tier;

        return ApiResponse::ok([
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'starts_at' => optional($subscription->starts_at)->toIso8601String(),
                'ends_at' => optional($subscription->ends_at)->toIso8601String(),
                'auto_renew' => (bool) $subscription->auto_renew,
                'tier' => [
                    'slug' => $tier?->slug,
                    'name' => $tier?->name,
                    'access_level' => $tier?->access_level,
                    'max_concurrent_streams' => $tier?->max_concurrent_streams,
                ],
            ] : null,
            // So the app can say "you are on Basic, Premium gives you X"
            // without a second request.
            'plans' => $this->plans()->getData(true)['data']['plans'],
        ]);
    }

    /**
     * Payment history.
     *
     * Amount and currency come off the order row rather than the live tier: an
     * invoice must say what was actually paid, not what the plan costs today.
     */
    public function orders(Request $request): JsonResponse
    {
        $orders = PaymentOrder::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->cursorPaginate(15);

        return ApiResponse::ok([
            'items' => $orders->getCollection()->map(fn (PaymentOrder $order) => $this->card($order))->values(),
            'next_cursor' => $orders->nextCursor()?->encode(),
        ]);
    }

    public function order(Request $request, string $reference): JsonResponse
    {
        $order = PaymentOrder::query()
            ->where('user_id', $request->user()->id)
            ->where('merchant_reference', $reference)
            ->first();

        if (! $order) {
            return ApiResponse::error(ApiErrorCode::NotFound, 'That order does not exist.');
        }

        return ApiResponse::ok(['order' => $this->card($order)]);
    }

    /** @return array<string, mixed> */
    private function card(PaymentOrder $order): array
    {
        return [
            'reference' => $order->merchant_reference,
            'status' => $order->status,
            'amount' => $order->amount,
            'currency' => $order->currency,
            'description' => $order->description,
            'created_at' => optional($order->created_at)->toIso8601String(),
        ];
    }
}
