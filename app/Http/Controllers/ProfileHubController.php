<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorAuthentication;
use Illuminate\Http\Request;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Payments\app\Models\PaymentOrder;
use Modules\Streaming\app\Models\WatchlistItem;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;

/**
 * Central controller for the /{username} profile hub. Each method
 * renders a single tab of the left-sidebar nav — the sidebar itself
 * is a shared partial that highlights whichever route is active.
 *
 * Access rule (v1): only the signed-in user can view their own hub.
 * Visiting /{someoneElse} redirects you to /{yourUsername}. No public
 * profiles until we explicitly opt in.
 */
class ProfileHubController extends Controller
{
    public function __construct(private TwoFactorAuthentication $twoFactor)
    {
    }

    /**
     * Resolve & guard the username in the URL. Throws a redirect when
     * you're trying to look at someone else's profile.
     *
     * Admins are bounced to /app: the profile hub is the user-facing
     * account surface, and admins manage their own account through the
     * admin panel instead (see feedback_admin_vs_user_separation).
     */
    private function resolveOwn(Request $request, string $username): User
    {
        $authed = $request->user();

        if ($authed->hasRole('admin')) {
            abort(redirect('/app'));
        }

        if (strcasecmp($authed->username, $username) !== 0) {
            abort(redirect()->route('profile.show', ['username' => $authed->username]));
        }
        return $authed;
    }

    /* ---------------------------------------------------------------
     | Tab: Profile (default landing)
     | --------------------------------------------------------------- */
    public function show(Request $request, string $username)
    {
        $user = $this->resolveOwn($request, $username);

        return view('profile-hub.profile', [
            'user' => $user,
            'activeTab' => 'profile',
        ]);
    }

    public function updateProfile(Request $request, string $username)
    {
        $user = $this->resolveOwn($request, $username);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'username'   => [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[a-zA-Z0-9_.\-]+$/',
                new \App\Rules\ReservedUsername(),
                'unique:users,username,' . $user->id,
            ],
            'email' => [
                'required', 'email', 'max:255',
                'unique:users,email,' . $user->id,
            ],
        ]);

        $emailChanged = strcasecmp($data['email'], $user->email) !== 0;

        $user->fill([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'username'   => $data['username'],
            'email'      => strtolower($data['email']),
        ]);

        // Email change → force re-verification.
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()
            ->route('profile.show', ['username' => $user->username])
            ->with('status', 'Your profile is up to date.');
    }

    /* ---------------------------------------------------------------
     | Tab: Security
     | --------------------------------------------------------------- */
    public function security(Request $request, string $username)
    {
        $user = $this->resolveOwn($request, $username);

        $hasPendingSetup = !is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at);
        $is2faEnabled    = $user->hasEnabledTwoFactorAuthentication();

        return view('profile-hub.security', [
            'user'            => $user,
            'activeTab'       => 'security',
            'hasPendingSetup' => $hasPendingSetup,
            'is2faEnabled'    => $is2faEnabled,
            'qrSvg'           => ($hasPendingSetup || $is2faEnabled) ? $this->twoFactor->qrCodeSvg($user) : null,
            'manualSecret'    => ($hasPendingSetup || $is2faEnabled) ? $this->twoFactor->secretForManualEntry($user) : null,
            'recoveryCodes'   => $is2faEnabled ? $this->twoFactor->getRecoveryCodes($user) : [],
            'googleEnabled'   => (bool) config('services.google.client_id'),
        ]);
    }

    /* ---------------------------------------------------------------
     | Tab: Membership (current plan + all tiers)
     | --------------------------------------------------------------- */
    public function membership(Request $request, string $username)
    {
        $user = $this->resolveOwn($request, $username);

        $activeSub = UserSubscription::with('tier')
            ->where('user_id', $user->id)
            ->current()
            ->orderByDesc('ends_at')
            ->first();

        $tiers = SubscriptionTier::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('access_level')
            ->get();

        return view('profile-hub.membership', [
            'user'          => $user,
            'activeTab'     => 'membership',
            'activeSub'     => $activeSub,
            'tiers'         => $tiers,
            'currentTierId' => $activeSub?->subscription_tier_id,
        ]);
    }

    /* ---------------------------------------------------------------
     | Tab: Billing (orders + invoice)
     | --------------------------------------------------------------- */
    public function billing(Request $request, string $username)
    {
        $user = $this->resolveOwn($request, $username);

        $orders = PaymentOrder::where('user_id', $user->id)
            ->with('payable.tier')
            ->latest()
            ->paginate(15);

        return view('profile-hub.billing', [
            'user'      => $user,
            'activeTab' => 'billing',
            'orders'    => $orders,
        ]);
    }

    public function invoice(Request $request, string $username, int $orderId)
    {
        $user = $this->resolveOwn($request, $username);

        $order = PaymentOrder::where('user_id', $user->id)
            ->where('id', $orderId)
            ->with('payable.tier')
            ->firstOrFail();

        return view('profile-hub.invoice', [
            'user'      => $user,
            'activeTab' => 'billing',
            'order'     => $order,
        ]);
    }

    /* ---------------------------------------------------------------
     | Tab: Watchlist
     | --------------------------------------------------------------- */
    public function watchlist(Request $request, string $username)
    {
        $user = $this->resolveOwn($request, $username);

        $movies = WatchlistItem::where('user_id', $user->id)
            ->where('watchable_type', (new Movie)->getMorphClass())
            ->with('watchable.genres')
            ->latest('added_at')
            ->get()
            ->pluck('watchable')
            ->filter();

        // Eager-load seasons_count so cards can surface "N seasons"
        // without an N+1 query per show.
        $shows = WatchlistItem::where('user_id', $user->id)
            ->where('watchable_type', (new Show)->getMorphClass())
            ->with(['watchable' => function ($q) {
                $q->with('genres')->withCount('seasons');
            }])
            ->latest('added_at')
            ->get()
            ->pluck('watchable')
            ->filter();

        return view('profile-hub.watchlist', [
            'user'      => $user,
            'activeTab' => 'watchlist',
            'movies'    => $movies,
            'shows'     => $shows,
        ]);
    }
}
