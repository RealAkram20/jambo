{{--
    <x-ui.sidenav> — the unified side-panel menu.

    Emits the EXACT markup the Streamit admin sidebar uses
    (ul.navbar-nav.iq-main-menu > li.nav-item > a.nav-link with
    i.icon + span.item-name, collapsible ul.sub-nav groups and
    static-item section headers), driven by a plain items array so
    every surface (admin-style shells, the user profile hub) renders
    the same structure and active states.

    The admin panel's own menu stays in
    components/partials/vertical-nav.blade.php — this component is for
    the OTHER surfaces that must look like it (see the
    keep-current-admin-panel constraint).

    Item shapes (associative arrays):
      Section header  ['section' => 'Payouts']
      Link            ['label' => 'Wallet', 'icon' => 'ph-wallet',
                       'href' => route(...), 'active' => bool,
                       'badge' => 3,                       // optional pill
                       'badge-attr' => 'data-x',           // optional extra attrs on the pill
                       'danger' => true,                   // optional red treatment (embedded)
                       'post' => true]                     // optional: submit a POST form to href (logout)
      Group           ['label' => 'Movies', 'icon' => 'ph-film-strip',
                       'id' => 'sidenav-movies', 'active' => bool,
                       'children' => [ ...links... ]]
    Items with 'show' => false are skipped, so callers can keep role
    logic inline in the config array.

    Props:
      items    — the menu config array
      menuId   — ul id; Streamit's collapse JS uses it as data-bs-parent
      embedded — true when rendering OUTSIDE a Streamit-CSS page (the
                 frontend profile hub). Ships a scoped stylesheet that
                 mirrors the admin sidebar's visuals (solid-primary
                 active pill, subtle hover, section header type) so the
                 rail looks identical without loading dashboard SCSS.
--}}
@props([
    'items' => [],
    'menuId' => 'sidebar-menu',
    'embedded' => false,
])

@php
    $visible = array_values(array_filter($items, fn ($item) => $item['show'] ?? true));

    // One blade partial renders both top-level links (fs-4 icons) and
    // sub-nav children (fs-5) — the $depth flag picks the size, same
    // convention vertical-nav.blade.php follows.
    $iconSize = fn (int $depth) => $depth === 0 ? 'fs-4' : 'fs-5';
@endphp

<ul {{ $attributes->merge(['class' => 'navbar-nav iq-main-menu' . ($embedded ? ' sidenav-embedded' : '')]) }} id="{{ $menuId }}">
    @foreach ($visible as $item)
        @if (isset($item['section']))
            <li class="nav-item static-item">
                @unless ($loop->first)
                    <hr class="sidenav-divider">
                @endunless
                <a class="nav-link static-item disabled" tabindex="-1">
                    <span class="default-icon">{{ $item['section'] }}</span>
                    <span class="mini-icon">-</span>
                </a>
            </li>
        @elseif (isset($item['children']))
            @php
                $children = array_values(array_filter($item['children'], fn ($c) => $c['show'] ?? true));
                $groupActive = $item['active'] ?? collect($children)->contains(fn ($c) => $c['active'] ?? false);
                $groupId = $item['id'] ?? 'sidenav-' . \Illuminate\Support\Str::slug($item['label']);
            @endphp
            <li class="nav-item">
                <a class="nav-link {{ $groupActive ? 'active' : '' }}" data-bs-toggle="collapse"
                    href="#{{ $groupId }}" role="button" aria-expanded="{{ $groupActive ? 'true' : 'false' }}"
                    aria-controls="{{ $groupId }}">
                    <i class="icon" data-bs-toggle="tooltip" title="{{ $item['label'] }}" data-bs-placement="right"
                        aria-label="{{ $item['label'] }}" data-bs-original-title="{{ $item['label'] }}">
                        <i class="ph {{ $item['icon'] }} {{ $iconSize(0) }}"></i>
                    </i>
                    <span class="item-name">{{ $item['label'] }}</span>
                    <i class="right-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </i>
                </a>
                <ul class="sub-nav collapse {{ $groupActive ? 'show' : '' }}" id="{{ $groupId }}"
                    data-bs-parent="#{{ $menuId }}">
                    @foreach ($children as $child)
                        <li class="nav-item">
                            <a class="nav-link {{ ($child['active'] ?? false) ? 'active' : '' }}"
                                href="{{ $child['href'] }}">
                                <i class="icon" data-bs-toggle="tooltip" title="{{ $child['label'] }}"
                                    data-bs-placement="right" aria-label="{{ $child['label'] }}"
                                    data-bs-original-title="{{ $child['label'] }}">
                                    <i class="ph {{ $child['icon'] }} {{ $iconSize(1) }}"></i>
                                </i>
                                <span class="item-name">{{ $child['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>
        @else
            @php $postFormId = ($item['post'] ?? false) ? 'sidenav-post-' . $loop->index . '-' . $menuId : null; @endphp
            <li class="nav-item">
                <a class="nav-link {{ ($item['active'] ?? false) ? 'active' : '' }} {{ ($item['danger'] ?? false) ? 'sidenav-link-danger' : '' }}"
                    href="{{ $item['href'] }}"
                    @if ($postFormId) onclick="event.preventDefault(); document.getElementById('{{ $postFormId }}').submit();" @endif>
                    <i class="icon" data-bs-toggle="tooltip" title="{{ $item['label'] }}" data-bs-placement="right"
                        aria-label="{{ $item['label'] }}" data-bs-original-title="{{ $item['label'] }}">
                        <i class="ph {{ $item['icon'] }} {{ $iconSize(0) }}"></i>
                    </i>
                    <span class="item-name">{{ $item['label'] }}</span>
                    @if (isset($item['badge']))
                        <span class="badge bg-primary rounded-pill ms-auto" {!! $item['badge-attr'] ?? '' !!}
                            style="font-size:10px; {{ (int) $item['badge'] > 0 ? '' : 'display:none;' }}">
                            {{ (int) $item['badge'] > 99 ? '99+' : $item['badge'] }}
                        </span>
                    @endif
                </a>
                @if ($postFormId)
                    <form id="{{ $postFormId }}" action="{{ $item['href'] }}" method="POST" class="d-none">
                        @csrf
                    </form>
                @endif
            </li>
        @endif
    @endforeach
</ul>

@if ($embedded)
    @once
        {{-- Mirrors the Streamit admin sidebar visuals (see
             _default_sidebar.scss + _default_style.scss +
             _active_style_nav_rounded_all.scss) for pages that do NOT
             load the dashboard SCSS bundle — same paddings, the
             solid-primary active pill, subtle hover, uppercase section
             headers. Scoped under .sidenav-embedded so it can't leak
             into the host page. --}}
        <style>
            .sidenav-embedded {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .sidenav-embedded .nav-item {
                margin-top: 8px;
            }

            .sidenav-embedded .nav-item.static-item {
                margin-top: 12px;
            }

            .sidenav-embedded .nav-link {
                display: flex;
                align-items: center;
                padding: .5rem 1rem;
                border-radius: .5rem;
                line-height: 1.5;
                color: #9aa0aa;
                text-decoration: none;
                text-transform: capitalize;
                cursor: pointer;
                transition: background-color .3s ease-in-out, color .3s ease-in-out;
            }

            .sidenav-embedded .nav-link .icon {
                display: flex;
                font-size: 1.15rem;
            }

            .sidenav-embedded .nav-link .item-name {
                flex: 1;
                margin-left: 1rem;
                font-size: .95rem;
            }

            .sidenav-embedded .nav-link:hover:not(.active) {
                background: rgba(var(--bs-primary-rgb, 26, 152, 255), .1);
                color: var(--bs-primary, #1A98FF);
            }

            .sidenav-embedded .nav-link.active {
                background: var(--bs-primary, #1A98FF);
                color: #fff;
                font-weight: 600;
                box-shadow: 0 10px 20px -10px rgba(var(--bs-primary-rgb, 26, 152, 255), .38);
            }

            .sidenav-embedded .nav-link.active .badge {
                background: rgba(255, 255, 255, .25) !important;
            }

            .sidenav-embedded .static-item .nav-link,
            .sidenav-embedded .nav-link.static-item {
                cursor: default;
                padding-bottom: 0;
            }

            .sidenav-embedded .static-item .default-icon {
                text-transform: uppercase;
                font-size: calc(1rem - 4px);
                letter-spacing: .18rem;
                color: #6c7280;
            }

            .sidenav-embedded .static-item .mini-icon {
                display: none;
            }

            .sidenav-embedded .sub-nav {
                list-style: none;
                padding-left: 1.25rem;
                margin: 0;
            }

            .sidenav-embedded .nav-link.sidenav-link-danger {
                color: #f08a92;
            }

            .sidenav-embedded .nav-link.sidenav-link-danger:hover {
                background: rgba(220, 53, 69, .1);
                color: #ffb5bc;
            }
        </style>
    @endonce
@endif
