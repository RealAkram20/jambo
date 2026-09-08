<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\DeviceTokenIssuer;
use App\Services\SocialAccountResolver;
use App\Services\TwoFactorChallengeStore;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Google sign-in from the app.
 *
 * The website redirects into Google's OAuth flow and reads the result on a
 * callback. A native app cannot do that usefully — it uses Google's own
 * sign-in SDK and comes back holding an **ID token**, so this endpoint takes
 * that token, proves it, and issues a Jambo device token.
 *
 * Who ends up owning the account, including the account-takeover mitigation
 * for a local row that never verified its email, is SocialAccountResolver's
 * decision — the same service the web callback uses. That logic is not
 * repeated here on purpose.
 *
 * Two-factor still applies. Google proves the mailbox, not possession of the
 * viewer's authenticator, so an account with 2FA enabled gets the same
 * challenge it gets after a password.
 */
class SocialAuthController extends Controller
{
    /**
     * Google's public endpoint for validating an ID token.
     *
     * Used rather than pulling in google/apiclient: this is one request, the
     * response is small, and the checks that matter — signature, expiry, and
     * the audience — are all performed by Google before it answers.
     */
    private const TOKENINFO = 'https://oauth2.googleapis.com/tokeninfo';

    public function google(
        Request $request,
        SocialAccountResolver $resolver,
        DeviceTokenIssuer $issuer,
    ): JsonResponse {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            ...DeviceTokenIssuer::deviceRules(),
        ]);

        $clientId = config('services.google.client_id');

        if (! $clientId) {
            return ApiResponse::error(
                ApiErrorCode::ServerError,
                'Google sign-in is not configured on this server.',
            );
        }

        $profile = $this->verify($data['id_token']);

        if ($profile === null) {
            return ApiResponse::error(
                ApiErrorCode::InvalidCredentials,
                'We could not verify that Google sign-in. Please try again.',
            );
        }

        // The audience check is the whole point. A valid Google ID token
        // issued to SOMEBODY ELSE'S app is still a valid Google token; without
        // this, anyone with any Google client could mint sign-ins here.
        if (($profile['aud'] ?? null) !== $clientId) {
            return ApiResponse::error(
                ApiErrorCode::InvalidCredentials,
                'That sign-in was not issued for this app.',
            );
        }

        // Google sets email_verified false for some workspace edge cases. An
        // unproven address must not adopt an existing Jambo account.
        $verified = filter_var($profile['email_verified'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $email = Str::lower((string) ($profile['email'] ?? ''));

        if (! $email || ! $verified) {
            return ApiResponse::error(
                ApiErrorCode::InvalidCredentials,
                'Google did not share a verified email. Please use email sign-in instead.',
            );
        }

        $user = $resolver->resolve($email, $profile['name'] ?? null);

        if ($user->isDeactivated()) {
            return ApiResponse::error(
                ApiErrorCode::AccountDeactivated,
                'This account has been deactivated. Contact support to reactivate it.',
            );
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return ApiResponse::error(
                ApiErrorCode::TwoFactorRequired,
                'Enter the code from your authenticator app.',
                extra: ['challenge_token' => app(TwoFactorChallengeStore::class)
                    ->issue($user, $data['device'])],
            );
        }

        return ApiResponse::ok($issuer->issue($user, $data['device']), 'Signed in.');
    }

    /**
     * Ask Google whether this token is real, and what is in it.
     *
     * Returns null on anything other than a clean answer — an expired token, a
     * forged one, or Google being unreachable. A network failure deliberately
     * reads as "could not verify" rather than being allowed through: the
     * destructive branch here is granting access, so it only runs on an
     * explicit yes.
     *
     * @return array<string, mixed>|null
     */
    private function verify(string $idToken): ?array
    {
        try {
            $response = Http::timeout(8)->get(self::TOKENINFO, ['id_token' => $idToken]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : null;
    }
}
