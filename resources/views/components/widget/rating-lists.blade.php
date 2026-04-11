<tr>
    <td>{{$ratingNo}}</td>
    <td>
        {{$ratingTitle}}
    </td>
    <td>
        <p class="mb-0"> {{$ratingName}}</p>
    </td>
    <td>
        <p class="mb-0">{{$ratingText}}</p>
    </td>
    <td>
        {{$ratingYear}}
    </td>
    <td><i class="ph-fill ph-star text-primary"></i> {{$ratingStar}}</td>
    <td>
        <div class="d-flex align-items-center list-user-action">
            <a class="btn btn-sm btn-icon btn-danger-subtle delete-btn rounded" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" href="#">
                <i class="ph ph-trash-simple"></i>
            </a>
        </div>
    </td>
</tr>
