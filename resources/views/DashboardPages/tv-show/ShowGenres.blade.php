@extends('layouts.app', ['module_title' => 'Genres', 'isSweetalert' => true, 'Activelink' => 'Genres', 'isFlatpickr' => true, 'isQuillEditor' => true, 'isSelect2' => true])

@section('content')
<div class="row streamit-wraper-table2">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative ">
                        {{__('form.add_new_genre')}} </span>
                </h2>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success mb-3">{{ session('success') }}</div>
                @endif
                @if (isset($errors) && $errors->any())
                    <div class="alert alert-danger mb-3">
                        <ul class="mb-0 mt-1">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.genres.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="genre-name">{{__('form.genre-name')}}<span> *</span></label>
                        <input type="text" class="form-control" id="genre-name" name="name"
                            placeholder="{{__('form.enter-title-genre')}}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="genre-slug">{{__('form.genre-slug')}}</label>
                        <input type="text" class="form-control" id="genre-slug" name="slug"
                            placeholder="{{__('form.enter-slug-genre')}}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="genre-colour">Colour</label>
                        <input type="color" class="form-control form-control-color" id="genre-colour" name="colour" value="#1A98FF">
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{__('form.genre-description')}}</label>
                        <textarea class="form-control large-text" name="description" aria-label="With textarea"></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary">{{__('form.add-genre')}}</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="pb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.tvshow-genre')}} </span>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="table-view table-space">
                        <table id="seasonTable" class="data-tables table custom-table movie_table data-table-one"
                            data-toggle="data-table1">
                            <thead>
                                <tr class="text-uppercase">
                                    <th>
                                        <input type="checkbox" class="form-check-input" />
                                    </th>
                                    <th>Thumbnail</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>{{__('form.parent-genre')}}</th>
                                    <th>Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($genres ?? [] as $genre)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input" /></td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center rounded" style="width:40px;height:40px;background:{{ $genre->colour ?? '#1f2738' }};">
                                                <i class="ph ph-faders-horizontal text-white"></i>
                                            </div>
                                        </td>
                                        <td>{{ $genre->name }}</td>
                                        <td>{{ $genre->slug }}</td>
                                        <td>—</td>
                                        <td>{{ $genre->shows_count ?? 0 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center list-user-action gap-2">
                                                <form method="POST" action="{{ route('admin.genres.destroy', $genre) }}" class="d-inline" onsubmit="return confirm('Delete genre {{ addslashes($genre->name) }}?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-icon btn-danger-subtle rounded" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                        <i class="ph ph-trash-simple fs-6"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted" style="font-size:14px;">No genres yet. Add one using the form.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
{{-- edit --}}
<div class="offcanvas offcanvas-end offcanvas-width-80" tabindex="-1" id="season-offcanvas-edit">
    <div class="offcanvas-header">
        <h2 class="episode-playlist-title wp-heading-inline">
            <span class="position-relative ">
                {{__('form.update_genre')}}</span>
        </h2>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form>
            <div class="section-form">
                <div class="form-group">
                    <label class="form-label" for="name">{{__('form.genre-name')}}<span> *</span></label>
                    <input type="text" class="form-control" id="name" value="{{__('form.name')}}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="Slug">{{__('form.genre-slug')}}<span> *</span></label>
                    <input type="text" class="form-control" id="Slug" value="{{__('form.genremovie')}}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="parent-category">{{__('form.parent-genre')}}</label>
                    <select id="parent-category" class="form-control select2-basic-multiple">
                        <option>Add Parent Category</option>
                        <option>Actor</option>
                        <option>Actress</option>
                        <option>Director</option>
                        <option>Producer</option>
                        <option>Singer</option>
                    </select>
                </div>
                @include('components.widget.UploadImageVideo', [
                'upload_image_name' => __('form.thumbnail'),
                'isUploadImageDefault' => true,
                ])

                <div class="form-group">
                    <label class="form-label flex-grow-1" for="Description">
                        {{__('form.excerpt')}}</label>
                    <textarea id="Description" class="form-control" rows="7" placeholder="{{__('streamTag.genre')}}"></textarea>
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
@endsection