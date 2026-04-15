{{--
    Series → Season → Episode breadcrumb.

    Props:
        show    — Show model (required)
        season  — Season model or null
        episode — Episode model or null
        leaf    — optional extra trailing label (e.g. "Add season")
--}}
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb bg-body-tertiary rounded px-3 py-2 mb-0" style="font-size:13px;">
        <li class="breadcrumb-item">
            <a href="{{ route('admin.series.index') }}" class="text-decoration-none">
                <i class="ph ph-television-simple me-1"></i>Series
            </a>
        </li>

        <li class="breadcrumb-item @if (!$season && !($leaf ?? null)) active @endif"
            @if (!$season && !($leaf ?? null)) aria-current="page" @endif>
            @if (!$season && !($leaf ?? null))
                {{ $show->title }}
            @else
                <a href="{{ route('admin.series.edit', $show) }}" class="text-decoration-none">{{ $show->title }}</a>
            @endif
        </li>

        @if ($season)
            <li class="breadcrumb-item @if (!$episode && !($leaf ?? null)) active @endif"
                @if (!$episode && !($leaf ?? null)) aria-current="page" @endif>
                @if (!$episode && !($leaf ?? null))
                    Season {{ $season->number }}@if ($season->title): {{ $season->title }}@endif
                @else
                    <a href="{{ route('admin.series.seasons.edit', [$show, $season]) }}" class="text-decoration-none">
                        Season {{ $season->number }}@if ($season->title): {{ $season->title }}@endif
                    </a>
                @endif
            </li>
        @endif

        @if ($episode)
            <li class="breadcrumb-item active" aria-current="page">
                Episode {{ $episode->number }}: {{ $episode->title }}
            </li>
        @endif

        @if (!empty($leaf ?? null))
            <li class="breadcrumb-item active" aria-current="page">{{ $leaf }}</li>
        @endif
    </ol>
</nav>
