<div class="iq-breadcrumb">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <nav aria-label="breadcrumb" class="text-center">
                    <ol class="breadcrumb justify-content-center text-white mt-0">
                        <li class="breadcrumb-item"><a href="{{ route('frontend.ott') }}">{{__('frontendheader.home')}}</a></li>
                        <li class="breadcrumb-item active">{{ $title ?? 'Page' }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>
