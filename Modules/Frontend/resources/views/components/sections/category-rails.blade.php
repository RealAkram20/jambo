{{-- Admin-curated category shelves. One rail per category the admin
     has flagged "visible home" on the category admin page. Data comes
     from SectionDataComposer::buildHomeCategories(): each category
     carries `railItems` — published movies + series merged (newest
     first, max 12), items tagged `_isShow` for per-card routing.
     Rail markup lives in partials.category-rail (shared with the
     random replacement shelves). --}}
@php $homeCategories = $homeCategories ?? collect(); @endphp

@foreach ($homeCategories as $cat)
    @include('frontend::components.partials.category-rail', ['cat' => $cat])
@endforeach
