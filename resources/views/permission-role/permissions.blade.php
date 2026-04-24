@extends('layouts.app', ['module_title' => 'Access control', 'title' => 'Access control'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Access control</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            {{ $roles->count() }} role{{ $roles->count() === 1 ? '' : 's' }}
                            · {{ $permissions->count() }} permission{{ $permissions->count() === 1 ? '' : 's' }}
                            · {{ count($modules) }} module{{ count($modules) === 1 ? '' : 's' }}
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary"
                        data-bs-toggle="modal" data-bs-target="#jambo-create-role-modal">
                        <i class="ph ph-plus me-1"></i> Create role
                    </button>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ session('error') }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- One card per role. Collapsed by default so the page stays
         scannable; click the role header to expand the permissions
         matrix. --}}
    @foreach ($roles as $role)
        @php
            // `title` on the role is a translation key (e.g. accesscontrol.admin).
            // Fall back to the name if the lookup returns the raw key.
            $displayTitle = __($role->title ?? $role->name);
            if ($displayTitle === $role->title) {
                $displayTitle = ucfirst($role->name);
            }
            $rolePerms = $role->permissions->pluck('name')->all();
        @endphp

        <div class="card mt-3 jambo-role-card" id="role-card-{{ $role->id }}">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-ghost p-1" type="button"
                        data-bs-toggle="collapse" data-bs-target="#role-body-{{ $role->id }}"
                        aria-expanded="true" aria-controls="role-body-{{ $role->id }}">
                        <i class="ph ph-caret-down"></i>
                    </button>
                    <h5 class="card-title mb-0">{{ $displayTitle }}</h5>
                    @if ($role->is_fixed)
                        <span class="badge bg-secondary ms-1">Built-in</span>
                    @endif
                    <span class="badge bg-info-subtle text-info-emphasis">
                        {{ count($rolePerms) }} / {{ $permissions->count() }} permissions
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm jambo-reset-role"
                        data-role-id="{{ $role->id }}"
                        data-role-name="{{ $displayTitle }}">
                        <i class="ph ph-arrow-counter-clockwise me-1"></i> Reset
                    </button>
                    @if (!$role->is_fixed)
                        <button type="button" class="btn btn-outline-danger btn-sm jambo-delete-role"
                            data-role-id="{{ $role->id }}"
                            data-role-name="{{ $displayTitle }}">
                            <i class="ph ph-trash me-1"></i> Delete
                        </button>
                    @endif
                </div>
            </div>

            <div class="collapse show" id="role-body-{{ $role->id }}">
                <form method="POST" action="{{ route('backend.permission-role.store', $role->id) }}">
                    @csrf
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table custom-table align-middle mb-0">
                                <thead>
                                    <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                        <th style="min-width:160px;">Module</th>
                                        @foreach (['view', 'add', 'edit', 'delete'] as $action)
                                            <th class="text-center">
                                                {{ ucfirst($action) }}
                                                <button type="button"
                                                    class="btn btn-link btn-sm p-0 ms-1 jambo-toggle-column"
                                                    data-role-id="{{ $role->id }}"
                                                    data-action="{{ $action }}"
                                                    title="Toggle all {{ $action }} across modules"
                                                    tabindex="-1"
                                                    style="font-size:10px;line-height:1;">
                                                    <i class="ph ph-check-square"></i>
                                                </button>
                                            </th>
                                        @endforeach
                                        <th class="text-center" style="width:80px;">All</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modules as $module)
                                        @php
                                            $moduleSlug = strtolower(str_replace(' ', '_', $module['module_name']));
                                            $moduleLabel = __('accesscontrol.' . $moduleSlug);
                                            if ($moduleLabel === 'accesscontrol.' . $moduleSlug) {
                                                $moduleLabel = ucfirst($module['module_name']);
                                            }
                                            $skipStandard = isset($module['is_custom_permission']) && !$module['is_custom_permission'];
                                        @endphp
                                        <tr>
                                            <td>{{ $moduleLabel }}</td>
                                            @foreach (['view', 'add', 'edit', 'delete'] as $action)
                                                <td class="text-center">
                                                    @if ($skipStandard)
                                                        @php $permName = $action . '_' . $moduleSlug; @endphp
                                                        <input type="checkbox"
                                                            class="form-check-input jambo-perm-checkbox"
                                                            name="permission[{{ $permName }}][]"
                                                            value="{{ $role->name }}"
                                                            data-action="{{ $action }}"
                                                            data-module="{{ $moduleSlug }}"
                                                            data-role-id="{{ $role->id }}"
                                                            @checked(in_array($permName, $rolePerms, true))>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-link btn-sm p-0 jambo-toggle-row"
                                                    data-role-id="{{ $role->id }}"
                                                    data-module="{{ $moduleSlug }}"
                                                    title="Toggle all permissions for {{ $moduleLabel }}"
                                                    tabindex="-1"
                                                    style="font-size:12px;line-height:1;">
                                                    <i class="ph ph-check-square"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        @if (isset($module['more_permission']) && is_array($module['more_permission']))
                                            @foreach ($module['more_permission'] as $extra)
                                                @php $extraPermName = $moduleSlug . '_' . strtolower(str_replace(' ', '_', $extra)); @endphp
                                                <tr class="text-muted" style="font-size:12px;">
                                                    <td style="padding-left:2rem;">
                                                        <i class="ph ph-corner-down-right me-1"></i>
                                                        {{ ucwords(str_replace('_', ' ', $extra)) }}
                                                    </td>
                                                    <td colspan="5" class="text-start">
                                                        <input type="checkbox"
                                                            class="form-check-input"
                                                            name="permission[{{ $extraPermName }}][]"
                                                            value="{{ $role->name }}"
                                                            @checked(in_array($extraPermName, $rolePerms, true))>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ph ph-floppy-disk me-1"></i> Save permissions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</div>

{{-- ============================================================
     Create role modal

     Submits to backend.role.store via fetch. Optional "Import
     permissions from" copies another role's permissions into the
     new one on creation (matches the RoleController::store logic).
     ============================================================ --}}
<div class="modal fade" id="jambo-create-role-modal" tabindex="-1" aria-labelledby="jambo-create-role-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#141923;border:1px solid #1f2738;">
            <form id="jambo-create-role-form" method="POST" action="{{ route('backend.role.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="jambo-create-role-label">Create role</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="jambo-create-role-error"></div>

                    <div class="mb-3">
                        <label class="form-label" for="jambo-new-role-title">Role name <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="jambo-new-role-title" class="form-control"
                            placeholder="e.g. Moderator" required maxlength="60">
                        <div class="form-text" style="font-size:11px;">
                            Stored lowercase with underscores. "Content Editor" becomes <code>content_editor</code>.
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="jambo-new-role-import">Copy permissions from</label>
                        <select name="import_role" id="jambo-new-role-import" class="form-select">
                            <option value="">— Start with no permissions —</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ __($role->title ?? $role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    // ============================================================
    // Create role — POST via fetch, reload on success, show error
    // inline on failure. Avoids a full page refresh bouncing the
    // user away from the list they just came from.
    // ============================================================
    var createForm = document.getElementById('jambo-create-role-form');
    if (createForm) {
        createForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            var errorBox = document.getElementById('jambo-create-role-error');
            errorBox.classList.add('d-none');

            try {
                var res = await fetch(createForm.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: new URLSearchParams(new FormData(createForm)),
                });
                var data = await res.json();
                if (!res.ok || data.status === false) {
                    throw new Error(data.message || 'Could not create role.');
                }
                window.location.reload();
            } catch (err) {
                errorBox.textContent = err.message;
                errorBox.classList.remove('d-none');
            }
        });
    }

    // ============================================================
    // Reset role permissions — DELETE all permissions from a role.
    // Uses the named route so the URL is correct regardless of app
    // prefix (the hardcoded /app/ was the old bug).
    // ============================================================
    document.querySelectorAll('.jambo-reset-role').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var roleId = btn.dataset.roleId;
            var roleName = btn.dataset.roleName;
            if (!confirm('Reset every permission on "' + roleName + '"? This cannot be undone.')) return;

            btn.disabled = true;
            try {
                var url = '{{ route('backend.permission-role.reset', ['role_id' => '__ID__']) }}'.replace('__ID__', roleId);
                var res = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                window.location.reload();
            } catch (err) {
                alert('Could not reset: ' + err.message);
                btn.disabled = false;
            }
        });
    });

    // ============================================================
    // Delete role — hard delete via RoleController::destroy.
    // Built-in roles don't render this button server-side, so the
    // handler never needs to check is_fixed.
    // ============================================================
    document.querySelectorAll('.jambo-delete-role').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var roleId = btn.dataset.roleId;
            var roleName = btn.dataset.roleName;
            if (!confirm('Delete "' + roleName + '" permanently? Users currently assigned to this role will lose it.')) return;

            btn.disabled = true;
            try {
                var url = '{{ route('backend.role.destroy', ['role' => '__ID__']) }}'.replace('__ID__', roleId);
                var res = await fetch(url, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                document.getElementById('role-card-' + roleId)?.remove();
            } catch (err) {
                alert('Could not delete: ' + err.message);
                btn.disabled = false;
            }
        });
    });

    // ============================================================
    // Bulk toggles — "check all X across a module" and "check all
    // modules for a given action". Quality-of-life, same-page, no
    // server roundtrip. Admin still needs to hit Save.
    // ============================================================
    document.querySelectorAll('.jambo-toggle-row').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var roleId = btn.dataset.roleId;
            var module = btn.dataset.module;
            var checkboxes = document.querySelectorAll(
                '.jambo-perm-checkbox[data-role-id="' + roleId + '"][data-module="' + module + '"]'
            );
            var anyUnchecked = Array.prototype.some.call(checkboxes, function (c) { return !c.checked; });
            checkboxes.forEach(function (c) { c.checked = anyUnchecked; });
        });
    });

    document.querySelectorAll('.jambo-toggle-column').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var roleId = btn.dataset.roleId;
            var action = btn.dataset.action;
            var checkboxes = document.querySelectorAll(
                '.jambo-perm-checkbox[data-role-id="' + roleId + '"][data-action="' + action + '"]'
            );
            var anyUnchecked = Array.prototype.some.call(checkboxes, function (c) { return !c.checked; });
            checkboxes.forEach(function (c) { c.checked = anyUnchecked; });
        });
    });
})();
</script>
@endsection
