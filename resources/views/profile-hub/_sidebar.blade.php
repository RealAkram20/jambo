{{--
    Left nav for the profile hub. Driven entirely by $activeTab, which
    each tab controller sets on the view data. Adding a new tab = add
    one <li> here + one controller method + one route.

    $user lets us show a tiny identity card at the top (avatar initials
    + display name + email) so users always know whose settings they
    are editing.
--}}
@php
    $avatarInitial = strtoupper(substr($user->first_name ?? $user->username ?? '?', 0, 1));
    $tabs = [
        ['key' => 'profile',       'label' => 'Profile',       'icon' => 'ph-user-circle',      'route' => route('profile.show',          ['username' => $user->username])],
        ['key' => 'security',      'label' => 'Security',      'icon' => 'ph-shield-check',     'route' => route('profile.security',      ['username' => $user->username])],
        ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'ph-bell',             'route' => route('profile.notifications', ['username' => $user->username])],
        ['key' => 'membership',    'label' => 'Membership',    'icon' => 'ph-crown',            'route' => route('profile.membership',    ['username' => $user->username])],
        ['key' => 'billing',       'label' => 'Billing',       'icon' => 'ph-receipt',          'route' => route('profile.billing',       ['username' => $user->username])],
        ['key' => 'watchlist',     'label' => 'Watchlist',     'icon' => 'ph-bookmarks-simple', 'route' => route('profile.watchlist',     ['username' => $user->username])],
    ];

    // Surface unread count on the Notifications tab so users can see
    // it in the sidebar without opening the bell.
    $hubUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
@endphp

<div class="jambo-hub-sidebar">
    <div class="jambo-hub-sidebar__user">
        <div class="jambo-hub-sidebar__avatar">{{ $avatarInitial }}</div>
        <div class="jambo-hub-sidebar__user-info">
            <strong>{{ $user->full_name ?: $user->username }}</strong>
            <span class="text-muted">{{ '@' . $user->username }}</span>
        </div>
    </div>

    <ul class="jambo-hub-nav">
        @foreach ($tabs as $tab)
            <li class="jambo-hub-nav__item">
                <a href="{{ $tab['route'] }}"
                   class="jambo-hub-nav__link {{ $activeTab === $tab['key'] ? 'is-active' : '' }}">
                    <i class="ph {{ $tab['icon'] }}"></i>
                    <span>{{ $tab['label'] }}</span>
                    @if ($tab['key'] === 'notifications')
                        <span class="badge bg-primary rounded-pill ms-auto" data-hub-unread-badge
                              style="font-size:10px; {{ $hubUnreadCount > 0 ? '' : 'display:none;' }}">
                            {{ $hubUnreadCount > 99 ? '99+' : $hubUnreadCount }}
                        </span>
                    @endif
                </a>
            </li>
        @endforeach

        <li class="jambo-hub-nav__divider"></li>

        <li class="jambo-hub-nav__item">
            <a href="{{ route('logout') }}"
               class="jambo-hub-nav__link jambo-hub-nav__link--danger"
               onclick="event.preventDefault(); document.getElementById('jambo-hub-logout-form').submit();">
                <i class="ph ph-sign-out"></i>
                <span>Sign out</span>
            </a>
            <form id="jambo-hub-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </li>
    </ul>
</div>
