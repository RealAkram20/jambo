<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Rules\ReservedUsername;
use App\Rules\ValidCountry;
use App\Support\Countries;
use App\Support\PhoneNumber;
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
            /*
             * Still 'string' rather than a format rule, because the shape is
             * not the constraint — `0742 078 673` and `+256742078673` are both
             * acceptable input. What is normalised and what is refused is
             * decided below, where the number can be read rather than pattern
             * matched.
             */
            'phone' => ['nullable', 'string', 'max:32'],
            // ISO-3166-1 alpha-2. Validated against the literal list in
            // Countries rather than through `intl`, so a box without the
            // extension cannot start rejecting every country in silence.
            'country' => ['nullable', 'string', 'size:2', new ValidCountry()],
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

        /*
         * Read once, before anything is written.
         *
         * A number that cannot be read is a validation failure rather than a
         * value stored as typed. The alternative — keeping whatever came in —
         * is how the column ended up holding fifteen shapes of one number, and
         * it is what Rio asked us to stop doing on 2026-09-10.
         */
        $phone = null;

        if (($data['phone'] ?? null) !== null && trim((string) $data['phone']) !== '') {
            $phone = PhoneNumber::toE164($data['phone'], $data['country'] ?? null);

            if ($phone === null) {
                return ApiResponse::error(
                    ApiErrorCode::ValidationFailed,
                    'That phone number could not be read.',
                    ['phone' => ['Enter a phone number like 0742 078 673.']],
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
            /*
             * Stored as E.164 whatever was typed, so the column holds one
             * shape rather than the fifteen Rio listed on 2026-09-10. An
             * unreadable value is refused above rather than stored as typed:
             * a phone column that sometimes holds a phone number is worse than
             * one that is empty.
             */
            'phone' => $phone,
            // Normalised on the way in, so "ug" and "UG" are one row in the
            // database rather than two. Same `?? null` reasoning as phone.
            'country' => isset($data['country']) ? Countries::normalise((string) $data['country']) : null,
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        /*
         * Send the link the message below promises.
         *
         * 🔴 **It did not, until 2026-09-10.** This method cleared
         * `email_verified_at` and answered "Check your new address for a
         * verification link", and nothing was ever sent — so the one moment a
         * viewer is actively looking for that email is the moment they were
         * told to wait for one that did not exist. Rio found it from the other
         * end: the profile editor gave him no way to verify an address.
         *
         * `sendEmailVerificationNotification()` is the same call
         * `EmailVerificationController::resend` makes, so there is one
         * mechanism for this rather than two that can drift. After `save()`,
         * because the notification signs a URL against the address that is
         * now on the row.
         */
        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

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

    /**
     * The country list the profile form's picker is built from.
     *
     * An endpoint rather than a list bundled into the app, so there is one
     * source of truth for both the codes and their names. Duplicating 250
     * entries in the client would let the two drift, and the drift would show
     * up as a viewer picking a country the server then rejects.
     *
     * Public and unauthenticated: it is the ISO country list, it is the same
     * for everybody, and requiring a token to read it would only mean the
     * registration form could not use it.
     *
     * @return JsonResponse
     */
    public function countries(): JsonResponse
    {
        return ApiResponse::ok([
            'countries' => Countries::all(),
            // The handful shown above the alphabetical list. Sent rather than
            // hard-coded in the app so the emphasis can change without a new
            // APK — Jambo is Ugandan and this is the East African Community.
            'suggested' => Countries::SUGGESTED,
        ]);
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
            // The code is what is stored; the name is derived every time, so a
            // client never has to carry a 250-row lookup table to render one
            // row of a profile. Both are null when no country is set, and the
            // screen shows an em dash rather than guessing at one.
            'country' => $user->country,
            'country_name' => Countries::name($user->country),
            'email_verified' => $user->hasVerifiedEmail(),
            /*
             * A path, not an absolute URL — the same shape every other image
             * in this API uses (`poster_url` is `/storage/gallery/...`), so a
             * client resolves it against the host it is talking to rather
             * than one the payload names.
             *
             * There is no normalising call here on purpose. The shape comes
             * from the `profiles` disk's own `url`, which is root-relative,
             * and that is the single place it is decided. A helper here would
             * be a second answer to a question the disk config already
             * settles. `ProfileAvatarStorageTest` pins the contract.
             */
            'avatar_url' => $user->getFirstMediaUrl('profile_image') ?: null,
            'joined_at' => optional($user->created_at)->toIso8601String(),
        ];
    }
}
