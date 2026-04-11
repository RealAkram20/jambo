@extends('layouts.app', ['module_title' => 'Seasons', 'isSweetalert' => true, 'Activelink' => 'Tvshow', 'isSelect2' => true])
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="streamit-wraper-table">
                <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('sidebar.season')}} </span>
                    </h2>
                    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#season-offcanvas"
                        aria-controls="season-offcanvas">
                        <i class="fa-solid fa-plus me-2"></i>{{ __('dashboard.Add_Seasaon') }}
                    </button>
                </div>
                <div class="table-view table-space">
                    <table id="seasonTable" class="data-tables table custom-table movie_table data-table-one"
                        data-toggle="data-table1">
                        <thead>
                            <tr class="text-uppercase">
                                <th class="border-bottom border-primary border-4">
                                    <input type="checkbox" class="form-check-input" />
                                </th>
                                <th class="border-bottom border-primary border-4">Movie</th>
                                <th class="border-bottom border-primary border-4">Title</th>
                                <th class="border-bottom border-primary border-4">Author</th>
                                <th class="border-bottom border-primary border-4">Date and Time</th>
                                <th class="border-bottom border-primary border-4">Views</th>
                                <th class="border-bottom border-primary border-4">Status</th>
                                <th class="border-bottom border-primary border-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('components.datatable.DataTable', [
                                'title' => 'arrival 1999',
                                'thumbnail' => 'movie-thumb/gameofheros.webp',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'date' => '2010',
                                'views' => '14',
                                'value' => 'checked',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'day of darkness',
                                'thumbnail' => 'movie-thumb/04.jpg',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'views' => '14',
                                'date' => '2010',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'don jon',
                                'thumbnail' => 'movie-thumb/05.jpg',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'date' => '2010',
                                'views' => '14',
                                'value' => 'checked',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'mega fun',
                                'thumbnail' => 'movie-thumb/06.jpg',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'views' => '14',
                                'date' => '2010',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'my true friends',
                                'thumbnail' => 'movie-thumb/rabbit.webp',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'views' => '14',
                                'date' => '2010',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'night mare',
                                'thumbnail' => 'movie-thumb/08.jpg',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'views' => '14',
                                'date' => '2010',
                                'value' => 'checked',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'portable',
                                'thumbnail' => 'movie-thumb/09.jpg',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'views' => '14',
                                'date' => '2010',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'suffered',
                                'thumbnail' => 'movie-thumb/10.jpg',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'views' => '14',
                                'date' => '2010',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'the witcher',
                                'thumbnail' => 'movie-thumb/02.jpg',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'views' => '14',
                                'date' => '2010',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'troll hunter',
                                'thumbnail' => 'movie-thumb/joker.webp',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'views' => '14',
                                'date' => '2010',
                                'value' => 'checked',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'troll hunter',
                                'thumbnail' => 'movie-thumb/joker.webp',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'views' => '14',
                                'date' => '2010',
                            ])

                            @include('components.datatable.DataTable', [
                                'title' => 'troll hunter',
                                'thumbnail' => 'movie-thumb/joker.webp',
                                'author' => 'admin',
                                'genres' => ['action', 'fight', 'thriller', 'etc'],
                                'views' => '14',
                                'date' => '2010',
                            ])
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- add --}}
            <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas">
                <div class="offcanvas-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('movielist.add_new_season')}} </span>
                    </h2>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <form class="section-form">
                        <div class="form-group">
                            <div class="form-group px-3 ">
                                <label class="form-label flex-grow-1" for="Seasons">
                                    <strong>{{__('streamEpisode.season')}}</strong> :
                                </label>
                                <input id="Seasons" type="number" class="form-control " placeholder="1" min="1" />
                            </div>
                            <div class="form-group px-3 ">
                                <label class="form-label flex-grow-1" for="Description">
                                    <strong>{{__('form.description')}}</strong> :
                                </label>
                                <textarea id="Description" class="form-control" placeholder="{{__('form.description')}}"></textarea>
                            </div>
                            <div class="form-group px-3">
                                <label class="form-label flex-grow-1" for="shows"><strong>{{__('movielist.shows')}}:</strong></label>
                                <select id="shows" class="form-control select2-basic-multiple">
                                    <option>abc</option>
                                    <option>xyz</option>
                                    <option>mno</option>
                                    <option>stu</option>
                                </select>
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

            {{-- edit --}}
            <div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas-edit">
                <div class="offcanvas-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('movielist.edit_season')}} </span>
                    </h2>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <form class="section-form">
                        <div class="form-group">
                            <div class="form-group px-3 ">
                                <label class="form-label flex-grow-1" for="Seasons1">
                                    <strong>{{__('streamEpisode.season')}}</strong> :
                                </label>
                                <input id="Seasons1" type="number" class="form-control " placeholder="1" min="1" />
                            </div>
                            <div class="form-group px-3 ">
                                <label class="form-label flex-grow-1" for="Description1">
                                    <strong>{{__('form.description')}}</strong> :
                                </label>
                                <textarea id="Description1" class="form-control" placeholder="{{__('form.description')}}"></textarea>
                            </div>
                            <div class="form-group px-3">
                                <label class="form-label flex-grow-1"><strong>{{__('movielist.shows')}}:</strong></label>
                                <select id="show1" class="form-control select2-basic-multiple">
                                    <option>abc</option>
                                    <option selected>xyz</option>
                                    <option>mno</option>
                                    <option>stu</option>
                                </select>
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
    </div>
@endsection
