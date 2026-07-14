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

    $hubNavItems = [
        ['label' => 'Watchlist',     'icon' => 'ph-bookmarks-simple', 'href' => route('profile.watchlist',     ['username' => $user->username]), 'active' => $activeTab === 'watchlist'],
        ['label' => 'Profile',       'icon' => 'ph-user-circle',      'href' => route('profile.show',          ['username' => $user->username]), 'active' => $activeTab === 'profile'],
        ['label' => 'Security',      'icon' => 'ph-shield-check',     'href' => route('profile.security',      ['username' => $user->username]), 'active' => $activeTab === 'security'],
        ['label' => 'Devices',       'icon' => 'ph-devices',          'href' => route('profile.devices',       ['username' => $user->username]), 'active' => $activeTab === 'devices'],
        ['label' => 'Notifications', 'icon' => 'ph-bell',             'href' => route('profile.notifications', ['username' => $user->username]), 'active' => $activeTab === 'notifications', 'badge' => $hubUnreadCount, 'badge-attr' => 'data-hub-unread-badge'],
        ['label' => 'Membership',    'icon' => 'ph-crown',            'href' => route('profile.membership',    ['username' => $user->username]), 'active' => $activeTab === 'membership'],
        ['section' => 'Account'],
        ['label' => 'Sign out',      'icon' => 'ph-sign-out',         'href' => route('logout'), 'post' => true, 'danger' => true],
    ];
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
