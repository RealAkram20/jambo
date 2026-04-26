<?php

namespace App\Models;

use App\Models\Traits\HasHashedMediaTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;
    use HasPushSubscriptions;
    use HasHashedMediaTrait;

    /**
     * Avatar lives in a single-file media collection so each new
     * upload replaces the previous file rather than piling up. The
     * media-library config persists files under storage/app/public,
     * exposed via the standard /storage symlink.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
    ];

    protected $guarded = [
        'id',
        'updated_at',
        '_token',
        '_method',
        'password_confirmation',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at'       => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'deactivated_at'          => 'datetime',
        'password'                => 'hashed',
    ];

    /**
     * Defaults for new users. Notification toggles are all opt-out by
     * default so a fresh sign-up immediately receives the broadcasts
     * (movies, series, system) without having to flip switches.
     */
    protected $attributes = [
        'in_app_notifications_enabled' => true,
        'email_notifications_enabled'  => true,
        'push_notifications_enabled'   => true,
    ];

    /**
     * Snapshot the user's identity onto their payment_orders before
     * the row is deleted. The FK now sets user_id to NULL on delete
     * (see 2026_04_26_120000_preserve_financial_records_on_user_delete),
     * so without this snapshot we'd retain the order amounts but lose
     * who paid. Refreshing here also picks up any email/name change
     * the user made between order creation and account deletion.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            \Illuminate\Support\Facades\DB::table('payment_orders')
                ->where('user_id', $user->id)
                ->update([
                    'customer_email'    => $user->email,
                    'customer_name'     => $fullName !== '' ? $fullName : null,
                    'customer_username' => $user->username,
                ]);
        });
    }

    /**
     * True when the user has both set up AND confirmed 2FA — the
     * middleware only challenges confirmed users, so a half-finished
     * setup can't lock someone out.
     */
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    public function isDeactivated(): bool
    {
        return !is_null($this->deactivated_at);
    }

    protected $appends = ['full_name', 'profile_image'];

    public function getFullNameAttribute() // notice that the attribute name is in CamelCase.
    {
        return $this->first_name.' '.$this->last_name;
    }


    protected function getProfileImageAttribute()
    {
        $media = $this->getFirstMediaUrl('profile_image');
        if (!empty($media)) {
            return $media;
        }

        // Fallback chain when the user hasn't uploaded a photo:
        //   1. branded_icon() — operator's favicon, then logo
        //   2. final stock avatar from app config
        // Picks up the same brand asset the install prompt and PWA
        // manifest use, so unauthenticated/photo-less users see the
        // site's identity instead of a generic person silhouette.
        if (function_exists('branded_icon')) {
            return branded_icon(config('app.avatar_base_path', '') . 'avatar.png');
        }

        return asset(config('app.avatar_base_path', '') . 'avatar.png');
    }
}
