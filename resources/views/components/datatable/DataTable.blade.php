<tr>
    <td>
        <input type="checkbox" class="form-check-input" />
    </td>
    @if($isReview ?? false)
    <td>
        {{ $author }}
    </td>
    <td>
        {{ $post_type }}
    </td>
    <td>
        {{ $author_email }}
    </td>
    <td>
        {{ $date }}
    </td>
    <td>
        {{ $rating }}
    </td>
    <td>
        {{ $approved }}
    </td>
    <td>
        {{ $post_id }}
    </td>
    @else
    @if($isTags ?? false)
    <td>
        {{ $term_name }}
    </td>
    <td>
        {{ $term_slug }}
    </td>
    <td>
        {{ $count }}
    </td>
    @else
    @if($isPlaylist ?? false)
    <td>
        {{ $playlist_name }}
    </td>
    <td>
        {{ $playlist_slug }}
    </td>
    @else
    @if($isThumbnail ?? false)
    {{-- Skip td block if isThumbnail --}}
    @else
    <td>
        <div class="d-flex">
            <img src="{{asset('dashboard/images/' .$thumbnail)}}" alt="image" class="rounded-2 avatar avatar-55 img-fluid" />
        </div>
    </td>
    @endif
    <td>
        {{ $title }}
    </td>
    <td>
        {{ $author }}
    </td>
    <td>
        {{ $date }}
    </td>
    <td>
        {{ $views }}
    </td>
    @if($viewsValue ?? false)
    {{-- Nothing here for now --}}
    @else
    <td>
        <div class="form-check form-switch">
            <input class="form-check-input form-switch-input" type="checkbox" {{ $value ?? '' }} />
        </div>
    </td>
    @endif
    @endif
    @endif
    @endif
    <td>
        <div class="d-flex align-items-center list-user-action gap-2">
            <button type="button" class="btn btn-sm btn-icon btn-success-subtle rounded" data-bs-toggle="tooltip"
                data-bs-placement="top" title="Edit">
                <i class="ph ph-pencil-simple fs-6" data-bs-toggle="offcanvas"
                    data-bs-target="#season-offcanvas-edit" aria-controls="season-offcanvas-edit"></i>
            </button>
            <a class="btn btn-sm btn-icon btn-danger-subtle delete-btn rounded" data-bs-toggle="tooltip"
                data-bs-placement="top" title="Delete" href="#">
                <i class="ph ph-trash-simple fs-6"></i>
            </a>
            <a class="btn btn-sm btn-icon btn-info-subtle  rounded" data-bs-toggle="tooltip"
                data-bs-placement="top" title="View" href="#">
                <i class="ph ph-eye fs-6"></i>
            </a>
        </div>
    </td>
</tr>