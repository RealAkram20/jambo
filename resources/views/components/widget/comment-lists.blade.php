<tr>
    <td>{{ $commentNo }}</td>
    <td>
        {{ $commentTitle }}
    </td>
    <td>{{ $commentAuth }}</td>
    <td>
        <p class="mb-0">{{ $commentText }}</p>
    </td>
    <td>
        {{ $commentDate }}
    </td>
    <td>
        <div class="d-flex align-items-center list-user-action">
            <a class="btn btn-sm btn-icon btn-success-subtle  rounded" data-bs-toggle="tooltip" data-bs-placement="top"
                title="Edit" href="#">
                <i class="ph-fill ph-pencil-simple fs-6"></i>
            </a>
            <a class="btn btn-sm btn-icon btn-danger-subtle delete-btn rounded" data-bs-toggle="tooltip" data-bs-placement="top"
                title="Delete" href="#">
                <i class="ph-fill ph-trash-simple fs-6"></i>
            </a>
            <a class="btn btn-sm btn-icon btn-info-subtle rounded" data-bs-toggle="tooltip"
                data-bs-placement="top" title="View" href="javascript:void(0);">
                <i class="ph ph-eye fs-6"></i>
            </a>
        </div>
    </td>
</tr>
