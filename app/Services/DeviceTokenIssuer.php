<?php

namespace App\Services;

use App\Models\User;
use Modules\Streaming\app\Models\Device;

/**
 * Mints the Sanctum token an app install signs in with, and binds it to a
 * device row.
 *
 * Every way into the app ends here — password sign-in, the two-factor second
 * leg, registration, and Google — so the device rules live in one place: one
 * live token per install, named for the install, scoped to `viewer`, and the
 * superseded token deleted.
 *
 * That last rule is not cosmetic. Sanctum's `expiration` is null in this
 * application, so a token that is not deleted works forever; without this an
 * uninstall/reinstall cycle would quietly accumulate working credentials that
 * no device list shows and nobody can revoke.
 */
class DeviceTokenIssuer
{
    /**
     * The validation rules every sign-in path must apply to its `device`
     * block. Without one we would issue a token that no device list can show
     * and no picker can boot.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function deviceRules(): array
    {
        return [
            'device' => ['required', 'array'],
            'device.uuid' => ['required', 'string', 'min:8', 'max:64'],
            'device.platform' => ['required', 'string', 'in:' . implode(',', Device::PLATFORMS)],
            'device.name' => ['nullable', 'string', 'max:120'],
            'device.model' => ['nullable', 'string', 'max:120'],
            'device.app_version' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @param  array<string, mixed>  $device
     * @return array<string, mixed>  The session payload the app stores.
     */
    public function issue(User $user, array $device): array
    {
        // Named for the install so `personal_access_tokens` is readable
        // without a join, and abilities kept to `viewer`: this token is for
        // watching. Anything that moves money or changes rates is unreachable
        // with it even if a route forgets its own policy.
        $newToken = $user->createToken('device:' . $device['uuid'], ['viewer']);

        $row = Device::register(
            user: $user,
            uuid: $device['uuid'],
            platform: $device['platform'],
            name: $device['name'] ?? null,
            model: $device['model'] ?? null,
            appVersion: $device['app_version'] ?? null,
            tokenId: $newToken->accessToken->getKey(),
        );

        return [
            'token' => $newToken->plainTextToken,
            'device' => [
                'uuid' => $row->uuid,
                'platform' => $row->platform,
                'name' => $row->name,
            ],
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ],
        ];
    }
}
