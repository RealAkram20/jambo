@extends('layouts.app', ['module_title' => 'review', 'isSelect2' => true, 'isSweetalert' => true, 'isFlatpickr' => true, 'isBanner' => false, 'isQuillEditor' => true])

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="streamit-wraper-table">
                <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.review-list')}} </span>
                    </h2>
                </div>
                <div class="table-view table-space streamit-wraper-table3">
                    <div class="d-flex align-items-center mt-3 gap-2">
                        <div class="form-group mb-0">
                            <select class="form-control select2-basic-multiple">
                                <option>Movie</option>
                                <option>Tv Show</option>
                                <option>Video</option>
                                <option>Episode</option>
                            </select>
                        </div>
                        <button class="btn btn-primary ">{{__('form.filter')}}</button>
                    </div>
                    <table id="seasonTable" class="data-tables table custom-table movie_table custom-table-height"
                        data-toggle="data-table2">
                        <thead>
                            <tr class="text-uppercase">
                                <th>
                                    <input type="checkbox" class="form-check-input" />
                                </th>
                                <th>Author</th>
                                <th>Post Type</th>
                                <th>Author Email</th>
                                <th>Date</th>
                                <th>Rating</th>
                                <th>Approved</th>
                                <th>Post ID</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('components.datatable.DataTable', [
                                'author' => 'admin',
                                'post_type' => 'movie',
                                'author_email' => 'johndeo@gmail.com',
                                'date' => '2025-01-02 12:00:40',
                                'rating' => '5',
                                'approved' => '1',
                                'post_id' => '32',
                                'isReview' => true,
                            ])
                            @include('components.datatable.DataTable', [
                                'author' => 'admin',
                                'post_type' => 'movie',
                                'author_email' => 'johndeo@gmail.com',
                                'date' => '2025-01-02 12:03:33',
                                'rating' => '5',
                                'approved' => '1',
                                'post_id' => '33',
                                'isReview' => true,
                            ])
                            @include('components.datatable.DataTable', [
                                'author' => 'admin',
                                'post_type' => 'video',
                                'author_email' => 'johndeo@gmail.com',
                                'date' => '2025-01-08 10:41:50',
                                'rating' => '4',
                                'approved' => '1',
                                'post_id' => '16',
                                'isReview' => true,
                            ])
                            @include('components.datatable.DataTable', [
                                'author' => 'admin',
                                'post_type' => 'tvshow',
                                'author_email' => 'johndeo@gmail.com',
                                'date' => '2025-01-08 11:43:00',
                                'rating' => '3',
                                'approved' => '1',
                                'post_id' => '1',
                                'isReview' => true,
                            ])
                            @include('components.datatable.DataTable', [
                                'author' => 'admin',
                                'post_type' => 'tvshow',
                                'author_email' => 'johndeo@gmail.com',
                                'date' => '2025-01-09 11:36:35',
                                'rating' => '5',
                                'approved' => '1',
                                'post_id' => '10',
                                'isReview' => true,
                            ])
                            @include('components.datatable.DataTable', [
                                'author' => 'admin',
                                'post_type' => 'video',
                                'author_email' => 'johndeo@gmail.com',
                                'date' => '2025-01-09 11:36:42',
                                'rating' => '4',
                                'approved' => '1',
                                'post_id' => '5',
                                'isReview' => true,
                            ])
                            @include('components.datatable.DataTable', [
                                'author' => 'admin',
                                'post_type' => 'movie',
                                'author_email' => 'johndeo@gmail.com',
                                'date' => '2025-01-10 10:42:46',
                                'rating' => '5',
                                'approved' => '1',
                                'post_id' => '45',
                                'isReview' => true,
                            ])
                            @include('components.datatable.DataTable', [
                                'author' => 'admin',
                                'post_type' => 'video',
                                'author_email' => 'johndeo@gmail.com',
                                'date' => '2025-01-13 07:08:44',
                                'rating' => '4',
                                'approved' => '1',
                                'post_id' => '13',
                                'isReview' => true,
                            ])
                            @include('components.datatable.DataTable', [
                                'author' => 'admin',
                                'post_type' => 'movie',
                                'author_email' => 'johndeo@gmail.com',
                                'date' => '2025-01-15 04:58:43',
                                'rating' => '4',
                                'approved' => '1',
                                'post_id' => '5',
                                'isReview' => true,
                            ])
                            @include('components.datatable.DataTable', [
                                'author' => 'admin',
                                'post_type' => 'movie',
                                'author_email' => 'johndeo@gmail.com',
                                'date' => '2025-01-17 06:51:59',
                                'rating' => '5',
                                'approved' => '1',
                                'post_id' => '6',
                                'isReview' => true,
                            ])
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- edit --}}
        <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas-edit">
            <div class="offcanvas-header">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative ">
                        {{__('form.edit-comment')}}</span>
                </h2>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <form>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label" for="author">{{__('form.author')}}</label>
                                <input type="text" class="form-control" id="author" value="{{__('form.admin')}}">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label" for="author-email">{{__('form.author-email')}}</label>
                                <input type="text" class="form-control" id="author-email" value="johndeo@gmail.com">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label" for="post-type">{{__('form.post-type')}}</label>
                                <input type="text" class="form-control" id="post-type" value="movie">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label" for="rating">{{__('movielist.Rating')}}</label>
                                <input type="text" class="form-control" id="rating" value="5">
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="form-label">{{__('form.comment-content')}}</label>
                                <textarea class="form-control large-text" aria-label="With textarea">
                                TEST DATALorem Ipsum is simply dummy text of the printing and typesetting industry.
                            </textarea>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6 mb-4">
                            <label class="form-label">{{__('form.comment-status')}}</label>
                            <div class="form-check d-block">
                                <input class="form-check-input" type="radio" name="flexRadioDefault"
                                    id="flexRadioDefault1" checked="">
                                <label class="form-check-label" for="flexRadioDefault1">
                                    {{__('form.approve')}}
                                </label>
                            </div>
                            <div class="form-check d-block">
                                <input class="form-check-input" type="radio" name="flexRadioDefault"
                                    id="flexRadioDefault2">
                                <label class="form-check-label" for="flexRadioDefault2">
                                    {{__('form.unapprove')}}
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="offcanvas-footer border-top">
                <div class="d-flex gap-3 p-3">
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                        <i class="ph-fill ph-floppy-disk-back"></i>{{ __('movielist.Save') }}
                    </button>
                    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" data-bs-dismiss="offcanvas"
                        aria-label="Close">
                        <i class="ph ph-caret-double-left"></i>{{ __('movielist.Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
