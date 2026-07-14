@extends('layouts.app', ['module_title' => 'Monetization'])

@php($editing = $partner->exists)

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">{{ $editing ? 'Edit partner' : 'Enroll partner' }}</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Only enrolled partners accrue earnings. Being credited as a VJ on content is not enough.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ $errors->first() }}</div>
                @endif

                <div class="card-body">
                    <form method="POST"
                          action="{{ $editing ? route('admin.monetization.partners.update', $partner) : route('admin.monetization.partners.store') }}">
                        @csrf
                        @if ($editing) @method('PUT') @endif

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Display name</label>
                                <input type="text" name="display_name" class="form-control" required maxlength="190"
                                       value="{{ old('display_name', $partner->display_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Partner type</label>
                                <select name="type" class="form-select">
                                    <option value="vj" @selected(old('type', $partner->type) === 'vj')>VJ</option>
                                    <option value="production_company" @selected(old('type', $partner->type) === 'production_company')>Production company</option>
                                    <option value="creator" @selected(old('type', $partner->type) === 'creator')>Content creator</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Linked user account (ID)</label>
                                <input type="number" name="user_id" class="form-control" min="1"
                                       value="{{ old('user_id', $partner->user_id) }}"
                                       placeholder="users.id — grants dashboard login">
                                <small class="text-muted">
                                    The user gains the <code>partner</code> role and access to /partner.
                                    Find the ID on the admin Users page.
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Linked VJ (catalog credit)</label>
                                <select name="vj_id" class="form-select">
                                    <option value="">— none —</option>
                                    @foreach ($vjs as $vj)
                                        <option value="{{ $vj->id }}" @selected((int) old('vj_id', $partner->vj_id) === $vj->id)>{{ $vj->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Multiplier</label>
                                <div class="input-group">
                                    <input type="number" name="multiplier" class="form-control" step="0.001" min="0.1" max="10"
                                           value="{{ old('multiplier', $partner->multiplier) }}">
                                    <span class="input-group-text">×</span>
                                </div>
                                <small class="text-muted">
                                    Boosts/reduces this partner's watch-minute share before the pool is divided (1.0 = neutral).
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="enrolled" @selected(old('status', $partner->status) === 'enrolled')>Enrolled — earning</option>
                                    <option value="suspended" @selected(old('status', $partner->status) === 'suspended')>Suspended — accrues nothing</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label d-block">Content rights on their own titles</label>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" class="form-check-input" id="can_edit_content"
                                           name="can_edit_content" value="1"
                                           @checked(old('can_edit_content', $partner->can_edit_content))>
                                    <label class="form-check-label" for="can_edit_content">Can edit metadata</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" class="form-check-input" id="can_delete_content"
                                           name="can_delete_content" value="1"
                                           @checked(old('can_delete_content', $partner->can_delete_content))>
                                    <label class="form-check-label" for="can_delete_content">Can delete titles</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.monetization.partners.index') }}" class="btn btn-ghost">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ph ph-floppy-disk me-1"></i> {{ $editing ? 'Save changes' : 'Enroll partner' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
