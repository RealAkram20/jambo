@extends('layouts.app', ['module_title' => 'Comment Lists', 'isSweetalert' => true, 'isFlatpickr' => true])

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="streamit-wraper-table">
                <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{ __('dashboard.Comment_List') }} </span>
                    </h2>
                </div>
                <div class="table-view table-space">
                    <table id="seasonTable"
                        class="data-tables table custom-table movie_table data-table-one custom-table-height"
                        data-toggle="data-table1">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>{{__('form.description')}}</th>
                                <th>Created Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                           @include('components.widget.comment-lists', [
                               'commentNo' => '1',
                               'commentTitle' => 'Hooked',
                               'commentAuth' => 'Nick Allen',
                               'commentText' => 'This show had me from the first episode. The plot twists were unexpected and thrilling. Can\'t wait for the next season!',
                               'commentDate' => '21 Jul, 2020',
                           ])

                          @include('components.widget.comment-lists', [
                               'commentNo' => '2',
                               'commentTitle' => 'Slow Middle',
                               'commentAuth' => 'Hans Olo',
                               'commentText' => 'Great performances, but the middle episodes dragged a bit. It picks up again towards the end.',
                               'commentDate' => '12 Jun, 2020',
                           ])

                          @include('components.widget.comment-lists', [
                               'commentNo' => '3',
                               'commentTitle' => 'Binge Worthy',
                               'commentAuth' => 'Lynn Guini',
                               'commentText' => 'I finished the whole season in one night. It’s addictive and really well-written!',
                               'commentDate' => '01 Jan, 2020',
                           ])

                          @include('components.widget.comment-lists', [
                               'commentNo' => '4',
                               'commentTitle' => 'Misleading Trailer',
                               'commentAuth' => 'Aaronottix',
                               'commentText' => 'The trailer made it look action-packed, but the actual movie was kind of slow. Still decent, but not what I expected.',
                               'commentDate' => '19 Mar, 2020',
                           ])

                          @include('components.widget.comment-lists', [
                               'commentNo' => '5',
                               'commentTitle' => 'Visually Stunning',
                               'commentAuth' => 'Marge Arita',
                               'commentText' => 'Absolutely beautiful cinematography. Every frame looked like a painting. A visual treat!',
                               'commentDate' => '21 Aug, 2020',
                           ])

                          @include('components.widget.comment-lists', [
                               'commentNo' => '6',
                               'commentTitle' => 'Mind Blown',
                               'commentAuth' => 'Bill Dabear',
                               'commentText' => 'Episode 5 completely changed the game. That twist was insane—I did not see it coming!',
                               'commentDate' => '15 May, 2020',
                           ])

                          @include('components.widget.comment-lists', [
                               'commentNo' => '7',
                               'commentTitle' => 'More Please!',
                               'commentAuth' => 'Brock Lee',
                               'commentText' => 'I need Season 2 right now! The finale left me with so many questions.',
                               'commentDate' => '07 Jul, 2020',
                           ])

                          @include('components.widget.comment-lists', [
                               'commentNo' => '8',
                               'commentTitle' => 'Too Long',
                               'commentAuth' => 'Nick Allen',
                               'commentText' => 'Some scenes felt unnecessary and stretched out. Could’ve been tighter with the editing.',
                               'commentDate' => '21 Jul, 2020',
                           ])

                          @include('components.widget.comment-lists', [
                               'commentNo' => '9',
                               'commentTitle' => 'Strong Cast',
                               'commentAuth' => 'Hans Olo',
                               'commentText' => 'The acting was top-notch, especially the lead actress. She brought the character to life.',
                               'commentDate' => '12 Jun, 2020',
                           ])

                          @include('components.widget.comment-lists', [
                               'commentNo' => '10',
                               'commentTitle' => 'Weak Ending',
                               'commentAuth' => 'Lynn Guini',
                               'commentText' => 'Loved the buildup, but the ending didn’t land for me. It felt rushed and unearned.',
                               'commentDate' => '01 Jan, 2020',
                           ])

                          @include('components.widget.comment-lists', [
                               'commentNo' => '11',
                               'commentTitle' => 'Worth It',
                               'commentAuth' => 'Aaronottix',
                               'commentText' => 'Exactly the kind of show I wanted. Gripping, emotional, and well-produced.',
                               'commentDate' => '19 Mar, 2020',
                           ])

                          @include('components.widget.comment-lists', [
                               'commentNo' => '12',
                               'commentTitle' => 'Great Music',
                               'commentAuth' => 'Marge Arita',
                               'commentText' => 'The soundtrack is amazing! I\'ve been replaying the background score ever since I watched it.',
                               'commentDate' => '21 Aug, 2020',
                           ])

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
