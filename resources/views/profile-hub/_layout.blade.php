{{--
    Two-column hub layout: left sidebar nav (tabs), right content.
    Child views set $activeTab on their view data so the sidebar
    knows which item to highlight; they also @yield('hub-content')
    for the per-tab body.

    Renders inside the site's frontend master layout so the top bar
    + bell + dropdown + mobile footer stay consistent.
--}}
@extends('frontend::layouts.master', ['isBreadCrumb' => false, 'title' => $pageTitle ?? 'Profile'])

@section('content')
<section class="jambo-profile-hub section-padding">
    <div class="container-fluid" style="max-width: 1180px;">
        <div class="row g-4">
            {{-- Sidebar (sticky on desktop) --}}
            <aside class="col-lg-3">
                @include('profile-hub._sidebar', ['activeTab' => $activeTab ?? 'profile', 'user' => $user])
            </aside>

            {{-- Content --}}
            <div class="col-lg-9">
                @if (session('status'))
                    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
                @endif

                @yield('hub-content')
            </div>
        </div>
    </div>
</section>

@include('frontend::components.widgets.mobile-footer')

<style>
    /* Sidebar: sticky column with Netflix-style tab rail. Matches the
       existing jambo-sidebar pattern so users feel consistency between
       the homepage chrome and the account hub. */
    .jambo-profile-hub {
        min-height: 70vh;
    }

    .jambo-hub-sidebar {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 12px;
        padding: 1rem 0.5rem;
        position: sticky;
        top: 7rem;
    }

    .jambo-hub-sidebar__user {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        margin-bottom: 0.5rem;
    }

    .jambo-hub-sidebar__avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--bs-primary);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    .jambo-hub-sidebar__user-info {
        min-width: 0;
    }

    .jambo-hub-sidebar__user-info strong {
        display: block;
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .jambo-hub-sidebar__user-info .text-muted {
        font-size: 0.8rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }

    .jambo-hub-nav {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .jambo-hub-nav__item {
        margin: 0;
    }

    .jambo-hub-nav__link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.65rem 0.85rem;
        color: #cfd3dc;
        text-decoration: none;
        border-radius: 8px;
        transition: background 0.15s, color 0.15s;
        font-size: 0.95rem;
    }

    .jambo-hub-nav__link:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
    }

    .jambo-hub-nav__link.is-active {
        background: rgba(26, 152, 255, 0.14);
        color: var(--bs-primary);
        font-weight: 600;
    }

    .jambo-hub-nav__link i {
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .jambo-hub-nav__divider {
        margin: 0.5rem 0.75rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    .jambo-hub-nav__link--danger {
        color: #f08a92;
    }

    .jambo-hub-nav__link--danger:hover {
        color: #ffb5bc;
        background: rgba(220, 53, 69, 0.1);
    }

    /* Common card styling used by every tab. */
    .jambo-hub-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .jambo-hub-card h5 {
        font-size: 1.1rem;
        margin-bottom: 0.25rem;
    }

    .jambo-hub-card__subtitle {
        color: #9aa0aa;
        font-size: 0.88rem;
        margin-bottom: 1rem;
    }

    @media (max-width: 991px) {
        .jambo-hub-sidebar {
            position: static;
            margin-bottom: 1rem;
        }
    }
</style>
@endsection
