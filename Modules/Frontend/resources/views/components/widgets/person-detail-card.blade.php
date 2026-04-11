<div class="d-flex align-items-center gap-3 bg-gray-900 movie-history overflow-hidden">
    <div class="image flex-shrink-0">
        <a href="{{$productLink}}">
            <img src="{{ asset('frontend/images/media/'.$productImg) }}"
                alt="The First Of Us" class="img-fluid object-fit-cover person-history-thumbnail">
        </a>
    </div>
    <div class="content">
        <h5 class="mb-1 line-count-2">
            <a href="{{$productLink}}" class="text-capitalize">
                {{$productTitle}} </a>
        </h5>
        <span>{{$productYear}}</span>
    </div>
</div>
