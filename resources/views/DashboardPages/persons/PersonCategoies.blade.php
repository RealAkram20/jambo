@extends('layouts.app', ['module_title' => 'Person-category', 'isSweetalert' => true, 'Activelink' => 'Person-category', 'isFlatpickr' => true, 'isQuillEditor' => true, 'isSelect2' => true])

@section('content')
    <div class="row streamit-wraper-table2">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative ">
                            {{__('form.add-new-category')}}</span>
                    </h2>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success mb-3">{{ session('success') }}</div>
                    @endif
                    <form method="POST" action="{{ route('admin.categories.store') }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="category-name">{{__('form.category-name')}} <span> *</span></label>
                            <input type="text" class="form-control" id="category-name" name="name"
                                placeholder="{{__('form.enter-title-genre')}}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="category-slug1">{{__('form.category-slug')}}</label>
                            <input type="text" class="form-control" id="category-slug1" name="slug"
                                placeholder="{{__('form.enter-slug-genre')}}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{__('form.category-description')}}</label>
                            <textarea class="form-control large-text" name="description" aria-label="With textarea"></textarea>
                        </div>
                        <div class="d-flex align-items-center justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">{{__('form.add-category')}}</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="pb-3">
                    <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                        <h2 class="episode-playlist-title wp-heading-inline">
                            <span class="position-relative ">
                                {{__('form.person-categories')}} </span>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="table-view table-space">
                            <table id="seasonTable"
                                class="data-tables table custom-table movie_table data-table-one custom-table-height"
                                data-toggle="data-table1">
                                <thead>
                                    <tr class="text-uppercase">
                                    <tr class="text-uppercase">
                                        <th class>
                                            <input type="checkbox" class="form-check-input" />
                                        </th>
                                        <th class>Name</th>
                                        <th class>Slug</th>
                                        <th class>Parent Genre</th>
                                        <th class>Count</th>
                                        <th class>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($categories ?? [] as $category)
                                        <tr>
                                            <td><input type="checkbox" class="form-check-input" /></td>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $category->slug }}</td>
                                            <td>—</td>
                                            <td>{{ ($category->movies_count ?? 0) + ($category->shows_count ?? 0) }}</td>
                                            <td>
                                                <div class="d-flex align-items-center list-user-action gap-2">
                                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Delete category {{ addslashes($category->name) }}?');">
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
                                            <td colspan="6" class="text-center py-5 text-muted" style="font-size:14px;">No categories yet. Add one using the form.</td>
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
                    {{__('form.update-category')}}</span>
            </h2>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form>
                <div class="form-group">
                    <label class="form-label" for="category-name1">{{__('form.category-name')}} <span> *</span></label>
                    <input type="text" class="form-control" id="category-name1" value="{{__('form.singer')}}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="category-slug">{{__('form.category-slug')}} <span> *</span></label>
                    <input type="text" class="form-control" id="category-slug" value="{{__('form.singer')}}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="parent-category">{{__('form.parent-category')}}</label>
                    <select id="parent-category1" class="form-control select2-basic-multiple">
                        <option>Add Parent Category</option>
                        <option selected="">Actor</option>
                        <option>Actress</option>
                        <option>Director</option>
                        <option>Producer</option>
                        <option>Singer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{__('form.category-description')}}</label>
                    <textarea class="form-control large-text" aria-label="With textarea"></textarea>
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
