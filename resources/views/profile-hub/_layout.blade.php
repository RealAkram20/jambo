{{--
    Two-column hub layout: left sidebar nav (tabs), right content.
    Child views set $activeTab on their view data so the sidebar
    knows which item to highlight; they also @yield('hub-content')
    for the per-tab body.

    Shell is role-aware so partners/VJs get ONE dashboard:
      - partner role  → renders inside the Creator Studio shell
        (monetization::layouts.partner). The studio sidebar already
        carries the Account links, so the hub's own rail is skipped
        and content goes full-width.
      - everyone else → the site's frontend master layout with the
        hub rail, so the top bar + bell + mobile footer stay
        consistent with the rest of the site.
--}}
@php
    $studioShell = auth()->check() && auth()->user()->hasRole('partner');
@endphp

@extends($studioShell ? 'monetization::layouts.partner' : 'frontend::layouts.master', ['isBreadCrumb' => false, 'title' => $pageTitle ?? 'Profile'])

@section('content')
<section class="jambo-profile-hub {{ $studioShell ? '' : 'section-padding' }}">
    <div class="container-fluid" style="max-width: 1180px; {{ $studioShell ? 'margin-left: 0;' : '' }}">
        <div class="row g-4">
            {{-- Sidebar (sticky on desktop) — skipped in the studio
                 shell, whose own sidebar carries these links. --}}
            @unless ($studioShell)
                <aside class="col-lg-3">
                    @include('profile-hub._sidebar', ['activeTab' => $activeTab ?? 'profile', 'user' => $user])
                </aside>
            @endunless

            {{-- Content --}}
            <div class="{{ $studioShell ? 'col-12' : 'col-lg-9' }}">
                @if (session('status'))
                    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
                @endif

                @yield('hub-content')
            </div>
        </div>
    </div>
</section>

@unless ($studioShell)
    @include('frontend::components.widgets.mobile-footer')
@endunless

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

    /* Nav rail styling now ships with the shared x-ui.sidenav
       component (embedded variant) so the hub, admin, and partner
       sidebars stay visually identical from one source. */

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
