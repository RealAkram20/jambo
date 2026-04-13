@extends('layouts.app', ['module_title' => 'movie tags', 'isSweetalert' => true, 'isSelect2' => false, 'isFlatpickr' => true])

@section('content')
    <div class="row streamit-wraper-table2">
        <div class="col-lg-4">
            <div class="card pb-3">
                <div class="card-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.add_new_tag')}} </span>
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
                <form method="POST" action="{{ route('admin.tags.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="Name">
                                        {{__('form.name')}} <span> *</span>
                                    </label>
                                    <input id="Name" type="text" name="name" class="form-control" placeholder="{{__('form.enter-name-tag')}}"
                                        required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="form-label flex-grow-1" for="Slug">
                                        {{__('form.slug')}}
                                    </label>
                                    <input id="Slug" type="text" name="slug" class="form-control" placeholder="{{__('form.enter-slug-tag')}}">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">{{__('form.add-tag')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card pb-3">
                    <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center">
                        <h2 class="episode-playlist-title wp-heading-inline">
                            <span class="position-relative ">
                                {{__('form.tags')}} </span>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="table-view table-space">
                            <table id="seasonTable" class="data-tables table custom-table movie_table data-table-one custom-table-height"
                            data-toggle="data-table1">
                            <thead>
                                <tr class="text-uppercase">
                                    <th class="">
                                        <input type="checkbox" class="form-check-input" />
                                    </th>
                                    <th>Term Name</th>
                                    <th>Term Slug</th>
                                    <th>Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tags ?? [] as $tag)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input" /></td>
                                        <td>{{ $tag->name }}</td>
                                        <td>{{ $tag->slug }}</td>
                                        <td>{{ $tag->movies_count ?? 0 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center list-user-action gap-2">
                                                <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" class="d-inline" onsubmit="return confirm('Delete tag {{ addslashes($tag->name) }}?');">
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
                                        <td colspan="5" class="text-center py-5 text-muted" style="font-size:14px;">No tags yet. Add one using the form.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
                {{__('form.update-tag')}}</span>
        </h2>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form>
            <div class="section-form">
                <div class="form-group">
                    <label class="form-label" for="name">{{__('form.name')}}<span> *</span></label>
                    <input type="text" class="form-control" id="name" value="{{__('form.name')}}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="Slug1">{{__('form.slug')}}<span> *</span></label>
                    <input type="text" class="form-control" id="Slug1" value="{{__('form.genremovie')}}">
                </div>
                <div class="form-group">
                    <label class="form-label flex-grow-1" for="Description1">
                        {{__('form.excerpt')}}
                    </label>
                    <textarea id="Description1" class="form-control" rows="7" placeholder="{{__('streamTag.genre')}}"></textarea>
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
