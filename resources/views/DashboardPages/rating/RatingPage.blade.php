@extends('layouts.app', ['module_title' => 'Rating Lists','isSelect2'=>true, 'isSweetalert'=>true, 'isFlatpickr'=>true, 'isBanner'=>false])

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="streamit-wraper-table">
            <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative ">
                        {{__('dashboard.Rating_List')}} </span>
                </h2>
            </div>
            <div class="table-view table-space">
                <table id="seasonTable" class="data-tables table custom-table movie_table data-table-one custom-table-height"
                    data-toggle="data-table1">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Category</th>
                                <th>Name</th>
                                <th>{{__('form.description')}}</th>
                                <th>Release Date</th>
                                <th>Rating</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
    
                            @include('components.widget.rating-lists',[
                            'ratingNo' => '1',
                            'ratingTitle' => 'Movie',
                            'ratingName' => 'Unexpected Gem',
                            'ratingText' => 'I started watching with low expectations, but this turned out to be amazing. Great storytelling and character depth.',
                            'ratingYear' => '2017',
                            'ratingStar' => '9.2',
                            ])  

                        @include('components.widget.rating-lists',[
                            'ratingNo' => '2',
                            'ratingTitle' => 'Show',
                            'ratingName' => 'Cursed',
                            'ratingText' => "It's not bad, but not great either. A decent watch for a slow weekend",
                            'ratingYear' => '2012',
                            'ratingStar' => '8.5',
                            ])

                        @include('components.widget.rating-lists',[
                            'ratingNo' => '3',
                            'ratingTitle' => 'Movie',
                            'ratingName' => 'Veronica', 
                            'ratingText' => "This show had me on the edge of my seat. Every episode ended with a cliffhanger!",
                            'ratingYear' => '2018',
                            'ratingStar' => '7.0',
                        ])

                        @include('components.widget.rating-lists',[
                            'ratingNo' => '4',
                            'ratingTitle' => 'Movie',
                            'ratingName' => 'Troll Hunters',
                            'ratingText' => " Everyone was talking about it, but I didn’t find it very engaging.",
                            'ratingYear' => '2020',
                            'ratingStar' => '9.5',
                        ])

                        @include('components.widget.rating-lists',[
                            'ratingNo' => '5',
                            'ratingTitle' => 'Show',
                            'ratingName' => 'Gran Torino',
                            'ratingText' => "The colors, locations, and cinematography are top-notch. A treat for the eyes!",
                            'ratingYear' => '2019',
                            'ratingStar' => '7.5',
                        ])

                        @include('components.widget.rating-lists',[
                            'ratingNo' => '6',
                            'ratingTitle' => 'Movie',   
                            'ratingName' => 'Man of Street',
                            'ratingText' => " Loved the suspense and pacing. Couldn't stop watching after episode 2.",
                            'ratingYear' => '2017',
                            'ratingStar' => '9.2',
                        ])

                        @include('components.widget.rating-lists',[
                            'ratingNo' => '7',
                            'ratingTitle' => 'Show',
                            'ratingName' => 'Cursed',
                            'ratingText' => "Strong start for a new series. Some uneven acting, but promising overall.",
                            'ratingYear' => '2012',
                            'ratingStar' => '8.5',
                        ])

                        @include('components.widget.rating-lists',[
                            'ratingNo' => '8',
                            'ratingTitle' => 'Movie',
                            'ratingName' => 'Veronica',
                            'ratingText' => "The idea was good, but episodes felt too long and repetitive.",
                            'ratingYear' => '2018',
                            'ratingStar' => '7.0',
                        ])

                        @include('components.widget.rating-lists',[
                            'ratingNo' => '9',
                            'ratingTitle' => 'Movie',
                            'ratingName' => 'Troll Hunters',
                            'ratingText' => "The cast delivered excellent performances. Very natural and believable.",
                            'ratingYear' => '2020',
                            'ratingStar' => '9.5',
                        ])

                        @include('components.widget.rating-lists',[
                            'ratingNo' => '10',
                            'ratingTitle' => 'Show',
                            'ratingName' => 'Gran Torino',
                            'ratingText' => "Everything was great until the last episode. That ending was a letdown.",
                            'ratingYear' => '2019',
                            'ratingStar' => '7.5',
                        ])

                        </tbody>
                    </table>
                </div>
        </div>
    </div>
</div>
@endsection