<?php

namespace Modules\Streaming\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * One app install, signed into one account.
 *
 * The browser equivalent is a row in Laravel's `sessions` table, which the
 * device picker already lists and boots. An app has no session, so this is
 * what the picker will list for phones and TV boxes, and revoking one here
 * kills the Sanctum token rather than just flagging a stream.
 *
 * The `uuid` doubles as the client session key for active_streams, so an app
 * device counts against max_concurrent_streams through exactly the same code
 * path a browser tab does.
 */
class Device extends Model
{
    use HasFactory;

    protected $table = 'devices';

    public const PLATFORM_ANDROID = 'android';
    public const PLATFORM_ANDROID_TV = 'android_tv';

    /** Platforms a client may claim. Anything else is a validation failure. */
    public const PLATFORMS = [
        self::PLATFORM_ANDROID,
        self::PLATFORM_ANDROID_TV,
    ];

    protected $fillable = [
        'uuid',
        'user_id',
        'platform',
        'name',
        'model',
        'app_version',
        'last_seen_at',
        'revoked_at',
        'personal_access_token_id',
        'fcm_token',
        'push_enabled',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
        'push_enabled' => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNull('revoked_at');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Find the device that owns a Sanctum token, so an authenticated request
     * can be checked against a boot without the client telling us who it is.
     */
    public static function forToken(?PersonalAccessToken $token): ?self
    {
        if (! $token) {
            return null;
        }

        return static::query()
            ->where('personal_access_token_id', $token->getKey())
            ->first();
    }

    /**
     * Record this install and bind it to the token it just received.
     *
     * Re-signing in on the same handset updates the row rather than adding
     * one, and clears a previous revocation: booting a device stops the
     * session it had, it does not blacklist the hardware. The account owner
     * signing in again is the strongest possible statement that they want it
     * back.
     */
    public static function register(
        User $user,
        string $uuid,
        string $platform,
        ?string $name,
        ?string $model,
        ?string $appVersion,
        int $tokenId,
    ): self {
        $device = static::firstOrNew([
            'user_id' => $user->id,
            'uuid' => $uuid,
        ]);

        // One live token per install. Without this, every re-login leaves the
        // previous token valid forever (Sanctum's expiration is null here by
        // config), so an uninstall/reinstall cycle would quietly accumulate
        // working credentials that no device list shows and nobody can revoke.
        if ($device->personal_access_token_id && $device->personal_access_token_id !== $tokenId) {
            PersonalAccessToken::whereKey($device->personal_access_token_id)->delete();
        }

        $device->fill([
            'platform' => $platform,
            'name' => $name ?: $device->name,
            'model' => $model ?: $device->model,
            'app_version' => $appVersion ?: $device->app_version,
            'last_seen_at' => now(),
            'revoked_at' => null,
            'personal_access_token_id' => $tokenId,
        ])->save();

        return $device;
    }

    /**
     * Record the FCM token this install currently holds.
     *
     * Idempotent on the token, and it also steals the token from any other
     * row that claims it: Android reissues a token to a *reinstall*, and two
     * rows holding the same token means one push delivered twice and a
     * receipt that cannot be matched back to an install.
     */
    public function registerPushToken(string $token): void
    {
        static::query()
            ->where('fcm_token', $token)
            ->whereKeyNot($this->getKey())
            ->update(['fcm_token' => null]);

        $this->forceFill(['fcm_token' => $token])->save();
    }

    public function forgetPushToken(): void
    {
        $this->forceFill(['fcm_token' => null])->save();
    }

    /** Installs that can actually be pushed to for this account. */
    public function scopePushable($q)
    {
        return $q->whereNull('revoked_at')
            ->where('push_enabled', true)
            ->whereNotNull('fcm_token');
    }

    /**
     * Boot this device: delete its token and stamp it revoked.
     *
     * Order matters. The token goes first, so a request already in flight
     * cannot squeeze past between the two writes with a token that is about
     * to stop existing.
     */
    public function revoke(): void
    {
        if ($this->personal_access_token_id) {
            PersonalAccessToken::whereKey($this->personal_access_token_id)->delete();
        }

        // The push token goes with the session. A shared handset that keeps
        // the previous account's token delivers their notifications, with
        // whatever is in them, to whoever is holding the phone. This is the
        // first rule in the push standard and the reason the token lives on
        // this row: every path that ends a session already comes through here.
        $this->forceFill([
            'revoked_at' => now(),
            'personal_access_token_id' => null,
            'fcm_token' => null,
        ])->save();

        // Whatever it was playing stops counting against the cap, and the
        // player's next heartbeat sees the flag and stands down - the same
        // mechanism the browser picker uses.
        ActiveStream::terminateSession($this->user_id, $this->uuid);
    }

    /** Cheap "still here" stamp, written on authenticated requests. */
    public function touchSeen(?string $appVersion = null): void
    {
        $this->forceFill([
            'last_seen_at' => now(),
            'app_version' => $appVersion ?: $this->app_version,
        ])->saveQuietly();
    }
}
