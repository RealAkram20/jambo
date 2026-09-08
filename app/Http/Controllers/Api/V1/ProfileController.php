<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Rules\ReservedUsername;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * The viewer's own profile.
 *
 * `GET /me` answers "who am I and what can I watch" on launch and stays small
 * for that reason. This is the profile SCREEN: the editable fields, the avatar,
 * and the verification state.
 *
 * The rules are ProfileHubController's, including the one that matters most —
 * changing the account email costs the current password.
 */
class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::ok(['profile' => $this->card($request->user())]);
    }

    /**
     * Edit the profile.
     *
     * Email is the account's recovery anchor: with a hijacked session an
     * attacker could swap it and then "forgot password" their way into a full
     * takeover. Changing it costs the current password, and re-verification.
     * Accounts created through Google have a random password, so those viewers
     * set one via password reset first — same as on the website.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[a-zA-Z0-9_.\-]+$/',
                new ReservedUsername(),
                'unique:users,username,' . $user->id,
            ],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            // Optional. Prefills PesaPal's hosted checkout so mobile-money
            // viewers do not retype it. Any printable characters, because
            // people format numbers with spaces and dashes.
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $emailChanged = strcasecmp($data['email'], $user->email) !== 0;

        if ($emailChanged) {
            $current = (string) $request->input('current_password');

            if ($current === '' || ! Hash::check($current, $user->password)) {
                return ApiResponse::error(
                    ApiErrorCode::ValidationFailed,
                    'Enter your current password to change the account email.',
                    ['current_password' => ['Enter your current password to change the account email.']],
                );
            }
        }

        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'username' => $data['username'],
            'email' => strtolower($data['email']),
            // ?? null, not just a null check: `nullable` means validate()
            // omits the key entirely when the client does not send the
            // field at all. The web form always posts an input, so it never
            // hits this; an API client sending only the fields it changed
            // does on the first request.
            'phone' => isset($data['phone']) ? trim((string) $data['phone']) : null,
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        return ApiResponse::ok(
            ['profile' => $this->card($user->fresh())],
            $emailChanged
                ? 'Profile updated. Check your new address for a verification link.'
                : 'Your profile is up to date.',
        );
    }

    /**
     * Replace the profile photo.
     *
     * Multipart, and the same 2 MB image-only bound the web form applies.
     * Spatie MediaLibrary owns the storage, so the app cannot invent a path.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:2048'],
        ]);

        $user = $request->user();

        $user->addMedia($request->file('avatar'))->toMediaCollection('profile_image');

        return ApiResponse::ok(['profile' => $this->card($user->fresh())], 'Profile photo updated.');
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $request->user()->clearMediaCollection('profile_image');

        return ApiResponse::ok(['profile' => $this->card($request->user()->fresh())], 'Profile photo removed.');
    }

    /** @return array<string, mixed> */
    private function card($user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'email_verified' => $user->hasVerifiedEmail(),
            'avatar_url' => $user->getFirstMediaUrl('profile_image') ?: null,
            'joined_at' => optional($user->created_at)->toIso8601String(),
        ];
    }
}
