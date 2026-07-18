{{--
    Left nav for the profile hub. Driven entirely by $activeTab, which
    each tab controller sets on the view data. Adding a new tab = add
    one entry to $hubNavItems here + one controller method + one route.

    The rail itself renders through the shared <x-ui.sidenav>
    component (embedded variant), so its markup and visuals — solid
    primary active pill, hover tint, icon + item-name row — are
    identical to the admin panel and the partner Creator Studio
    sidebars.

    $user lets us show a tiny identity card at the top (avatar initials
    + display name + email) so users always know whose settings they
    are editing.
--}}
@php
    $avatarInitial = strtoupper(substr($user->first_name ?? $user->username ?? '?', 0, 1));

    // Surface unread count on the Notifications tab so users can see
    // it in the sidebar without opening the bell. The data attribute
    // lets the bell's JS keep the pill in sync after mark-as-read.
    $hubUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;

    // Admins only ever reach the hub for Refer & Earn (every other tab
    // bounces them to /app), so their rail carries just that tab plus a
    // shortcut back to the dashboard.
    $hubIsAdmin = auth()->check() && auth()->user()->hasRole('admin');

    if ($hubIsAdmin) {
        $hubNavItems = [
            ['label' => 'Refer & Earn',    'icon' => 'ph-gift',         'href' => route('profile.refer', ['username' => $user->username]), 'active' => $activeTab === 'refer'],
            ['label' => 'Wallet',          'icon' => 'ph-wallet',       'href' => route('profile.wallet', ['username' => $user->username]), 'active' => $activeTab === 'wallet'],
            ['section' => 'Account'],
            ['label' => 'Admin Dashboard', 'icon' => 'ph-squares-four', 'href' => url('/app')],
            ['label' => 'Sign out',        'icon' => 'ph-sign-out',     'href' => route('logout'), 'post' => true, 'danger' => true],
        ];
    } else {
        $hubNavItems = [
            ['label' => 'Watchlist',     'icon' => 'ph-bookmarks-simple', 'href' => route('profile.watchlist',     ['username' => $user->username]), 'active' => $activeTab === 'watchlist'],
            ['label' => 'Profile',       'icon' => 'ph-user-circle',      'href' => route('profile.show',          ['username' => $user->username]), 'active' => $activeTab === 'profile'],
            ['label' => 'Security',      'icon' => 'ph-shield-check',     'href' => route('profile.security',      ['username' => $user->username]), 'active' => $activeTab === 'security'],
            ['label' => 'Devices',       'icon' => 'ph-devices',          'href' => route('profile.devices',       ['username' => $user->username]), 'active' => $activeTab === 'devices'],
            ['label' => 'Notifications', 'icon' => 'ph-bell',             'href' => route('profile.notifications', ['username' => $user->username]), 'active' => $activeTab === 'notifications', 'badge' => $hubUnreadCount, 'badge-attr' => 'data-hub-unread-badge'],
            ['label' => 'Membership',    'icon' => 'ph-crown',            'href' => route('profile.membership',    ['username' => $user->username]), 'active' => $activeTab === 'membership'],
            // Money page — never gated on the referral program.
            ['label' => 'Wallet',        'icon' => 'ph-wallet',           'href' => route('profile.wallet',        ['username' => $user->username]), 'active' => $activeTab === 'wallet'],
        ];

        if (\Modules\Referrals\app\Services\ReferralSettings::active()) {
            $hubNavItems[] = ['label' => 'Refer & Earn', 'icon' => 'ph-gift', 'href' => route('profile.refer', ['username' => $user->username]), 'active' => $activeTab === 'refer'];
        }

        $hubNavItems = array_merge($hubNavItems, [
            ['section' => 'Account'],
            ['label' => 'Sign out',      'icon' => 'ph-sign-out',         'href' => route('logout'), 'post' => true, 'danger' => true],
        ]);
    }
@endphp

<div class="jambo-hub-sidebar">
    <div class="jambo-hub-sidebar__user">
        <div class="jambo-hub-sidebar__avatar">{{ $avatarInitial }}</div>
        <div class="jambo-hub-sidebar__user-info">
            <strong>{{ $user->full_name ?: $user->username }}</strong>
            <span class="text-muted">{{ '@' . $user->username }}</span>
        </div>
    </div>

    <x-ui.sidenav :items="$hubNavItems" menu-id="hub-sidebar-menu" :embedded="true" />
</div>
