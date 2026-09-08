<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Email verification, from the app's side.
 *
 * The verification LINK is handled by the website — it is a signed URL that
 * has to open somewhere, and one implementation of that is enough. What the
 * app needs is the two things a browser gets for free: knowing whether the
 * address is verified yet, and being able to ask for the mail again.
 *
 * An unverified account is not blocked from watching, matching the website,
 * which shows a banner rather than a wall.
 */
class EmailVerificationController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        return ApiResponse::ok([
            'email' => $request->user()->email,
            'verified' => $request->user()->hasVerifiedEmail(),
        ]);
    }

    /**
     * Send the verification email again.
     *
     * Throttled on the route: this sends mail, and anything that costs money
     * or reputation is limited below the read endpoints. Already-verified
     * accounts answer success without sending anything, so a confused client
     * retrying cannot generate mail.
     */
    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return ApiResponse::ok(['verified' => true], 'That email is already verified.');
        }

        $request->user()->sendEmailVerificationNotification();

        return ApiResponse::ok(
            ['verified' => false],
            "We've sent a new verification link. If you don't see it in a couple of minutes, check your Spam folder.",
        );
    }
}
