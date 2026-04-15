{{--
    Movies breadcrumb.

    Props:
        movie — Movie model or null (null on create)
        leaf  — optional trailing label (e.g. "Add movie")
--}}
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb bg-body-tertiary rounded px-3 py-2 mb-0" style="font-size:13px;">
        <li class="breadcrumb-item">
            <a href="{{ route('admin.movies.index') }}" class="text-decoration-none">
                <i class="ph ph-film-strip me-1"></i>Movies
            </a>
        </li>

        @if (!empty($movie) && $movie->exists)
            <li class="breadcrumb-item @if (empty($leaf ?? null)) active @endif"
                @if (empty($leaf ?? null)) aria-current="page" @endif>
                @if (empty($leaf ?? null))
                    {{ $movie->title }}
                @else
                    <a href="{{ route('admin.movies.edit', $movie) }}" class="text-decoration-none">{{ $movie->title }}</a>
                @endif
            </li>
        @endif

        @if (!empty($leaf ?? null))
            <li class="breadcrumb-item active" aria-current="page">{{ $leaf }}</li>
        @endif
    </ol>
</nav>
