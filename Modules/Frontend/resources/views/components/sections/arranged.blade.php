{{--
    The home page's sections, in the order an admin put them.

    This is the whole of the website's half of /admin/home-sections. One
    arrangement governs both surfaces: the same rows that order `/api/v1/home`
    order this page, the same switch takes a shelf off both, and the same label
    renames the heading on both.

    Callers pass `sectionData` for anything the route action holds that
    HomeRailsService does not — today that is `upcomingItems` only.

    Nothing about the order is written here. Adding a section is one row in
    HomeSection::DEFAULTS and one in HomeSection::WEB_VIEWS.
--}}
@php
    use Modules\Frontend\app\Models\HomeSection;
    use Modules\Frontend\app\View\Composers\SectionDataComposer;

    $plan = HomeSection::webPlan(array_merge(SectionDataComposer::shared(), $sectionData ?? []));
@endphp

@foreach ($plan as $group)
    @if ($group['bleed'])
        @foreach ($group['steps'] as $step)
            @include($step['view'], $step['with'])
        @endforeach
    @else
        {{-- No overflow-hidden wrapper: card-hover on .iq-card extends
             ~1.25em outside each card and ~5em below via the ::after pseudo,
             so clipping cuts off the Play Now / wishlist reveal on the
             leftmost column and the bottom row. Horizontal page overflow is
             handled at the body level in custom.css. Same pattern as
             /movie, /series, /upcoming, /genres/*. --}}
        <div class="container-fluid">
            @foreach ($group['steps'] as $step)
                @include($step['view'], $step['with'])
            @endforeach
        </div>
    @endif
@endforeach
