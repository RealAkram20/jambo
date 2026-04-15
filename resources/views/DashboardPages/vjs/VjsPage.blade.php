@extends('layouts.app', ['module_title' => 'Vjs', 'isSweetalert' => true, 'isSelect2' => true])

@section('content')
<div class="row streamit-wraper-table2">
    <div class="col-lg-4">
        <div class="card pb-3">
            <div class="card-header">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative">Add New Vj</span>
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
                <form method="POST" action="{{ route('admin.vjs.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="vj-name">Name<span> *</span></label>
                        <input type="text" class="form-control" id="vj-name" name="name" placeholder="e.g. Vj Junior" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="vj-slug">Slug</label>
                        <input type="text" class="form-control" id="vj-slug" name="slug" placeholder="auto-generated from name if empty">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="vj-colour">Colour</label>
                        <input type="color" class="form-control form-control-color" id="vj-colour" name="colour" value="#1A98FF">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control large-text" name="description" rows="3"></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary">Add Vj</button>
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
                        <span class="position-relative">Vjs</span>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="table-view table-space">
                        <table class="table custom-table movie_table">
                            <thead>
                                <tr class="text-uppercase">
                                    <th><input type="checkbox" class="form-check-input" /></th>
                                    <th>Thumbnail</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Movies</th>
                                    <th>Series</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vjs ?? [] as $vj)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input" /></td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center rounded"
                                                style="width:40px;height:40px;background:{{ $vj->colour ?? '#1f2738' }};">
                                                <i class="ph ph-microphone-stage text-white"></i>
                                            </div>
                                        </td>
                                        <td>{{ $vj->name }}</td>
                                        <td>{{ $vj->slug }}</td>
                                        <td>{{ $vj->movies_count ?? 0 }}</td>
                                        <td>{{ $vj->shows_count ?? 0 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center list-user-action gap-2">
                                                <form method="POST" action="{{ route('admin.vjs.destroy', $vj) }}" class="d-inline"
                                                    onsubmit="return confirm('Delete Vj {{ addslashes($vj->name) }}?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-icon btn-danger-subtle rounded"
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                        <i class="ph ph-trash-simple fs-6"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted" style="font-size:14px;">
                                            No Vjs yet. Add one using the form.
                                        </td>
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
@endsection
