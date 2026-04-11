@extends('layouts.app', ['module_title' => 'FontAwesome Icons'])

@section('title')
    {{ __($module_action) }} {{ __($module_title) }}
@endsection

@section('content')
    <div class="row">
        <div class="card">
            @foreach ($roles as $role)
                {{ Form::open(['route' => ['backend.permission-role.store', $role->id], 'method' => 'post']) }}
                <div class="permission-collapse border rounded p-3 mb-3 mt-3" id="permission_{{ $role->id }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6>{{ __($role->title) }}</h6>
                        <div class="toggle-btn-groups">
                            @if ($role->is_fixed == 0)
                                <button class="btn btn-danger" type="button" onclick="delete_role({{ $role->id }})">
                                    {{ __('accesscontrol.delete') }}
                                </button>
                            @endif
                            <button class="btn btn-primary ms-2" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseBox1_{{ $role->id }}" aria-expanded="false"
                                aria-controls="collapseBox1_{{ $role->id }}">
                                {{ __('accesscontrol.permission') }}
                            </button>
                        </div>
                    </div>
                    <div class="collapse pt-3 show" id="collapseBox1_{{ $role->id }}">
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped mb-0">
                                <thead class="sticky-top">
                                    <tr>
                                        <th>{{ __('accesscontrol.modules') }}</th>
                                        <th>{{ __('accesscontrol.view') }}</th>
                                        <th>{{ __('accesscontrol.add') }}</th>
                                        <th>{{ __('accesscontrol.edit') }}</th>
                                        <th>{{ __('accesscontrol.delete') }}</th>
                                        <th class="text-end">
                                            {{ Form::submit(__('accesscontrol.save'), ['class' => 'btn btn-md btn-secondary']) }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modules as $mKey => $module)
                                        <tr>
                                            <td>{{ __('accesscontrol.' . strtolower($module['module_name'])) }}</td>

                                            @foreach (['view', 'add', 'edit', 'delete'] as $action)
                                                <td>
                                                    @if (isset($module['is_custom_permission']) && !$module['is_custom_permission'])
                                                        <span>
                                                            <input type="checkbox"
                                                                id="role-{{ $role->name }}-permission-{{ $action }}_{{ strtolower(str_replace(' ', '_', $module['module_name'])) }}"
                                                                name="permission[{{ $action }}_{{ strtolower(str_replace(' ', '_', $module['module_name'])) }}][]"
                                                                value="{{ $role->name }}" class="form-check-input"
                                                                {{ AuthHelper::checkRolePermission($role, $action . '_' . strtolower(str_replace(' ', '_', $module['module_name']))) ? 'checked' : '' }}>
                                                        </span>
                                                    @endif
                                                </td>
                                            @endforeach

                                            @if (isset($module['more_permission']) && is_array($module['more_permission']))
                                                <td class="text-end">
                                                    <a data-bs-toggle="collapse" data-bs-target="#demo_{{ $mKey }}"
                                                        class="accordion-toggle btn btn-primary btn-xs">
                                                        <i class="fa-solid fa-chevron-down me-2"></i>More
                                                    </a>
                                                </td>
                                            @else
                                                <td></td>
                                            @endif
                                        </tr>

                                        @if (isset($module['more_permission']) && is_array($module['more_permission']))
                                            <tr>
                                                <td colspan="12" class="hiddenRow">
                                                    <div class="accordian-body collapse" id="demo_{{ $mKey }}">
                                                        <table class="table table-striped mb-0">
                                                            <tbody>
                                                                @foreach ($module['more_permission'] as $permission_data)
                                                                    <tr>
                                                                        <td class="text-center">
                                                                            {{ ucwords($module['module_name']) }}
                                                                            {{ ucwords(str_replace('_', ' ', $permission_data)) }}
                                                                            <span class="ms-5">
                                                                                <input type="checkbox"
                                                                                    id="role-{{ $role->name }}-permission-{{ strtolower(str_replace(' ', '_', $module['module_name'])) }}_{{ strtolower(str_replace(' ', '_', $permission_data)) }}"
                                                                                    name="permission[{{ strtolower(str_replace(' ', '_', $module['module_name'])) }}_{{ strtolower(str_replace(' ', '_', $permission_data)) }}][]"
                                                                                    value="{{ $role->name }}"
                                                                                    class="form-check-input"
                                                                                    {{ AuthHelper::checkRolePermission($role, strtolower(str_replace(' ', '_', $module['module_name']) . '_' . str_replace(' ', '_', $permission_data))) ? 'checked' : '' }}>
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {{ Form::close() }}
            @endforeach
        </div>
    </div>

    <div data-render="app">
        <manage-role-form create-title="{{ __('messages.create') }} {{ __('page.lbl_role') }}"></manage-role-form>
    </div>

    <script>
        function reset_permission(role_id) {
            var url = "/app/permission-role/reset/" + role_id;

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    successSnackbar(response.message);
                    window.location.reload();
                },
                error: function(response) {
                    alert('error');
                }
            });
        }

        function delete_role(role_id) {
            var url = "{{ route('backend.role.destroy', ['role' => ':role_id']) }}";
            url = url.replace(':role_id', role_id);

            $.ajax({
                url: url,
                type: 'DELETE',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#permission_' + role_id).hide();
                    successSnackbar(response.message);
                },
                error: function(response) {
                    alert('error');
                }
            });
        }
    </script>
@endsection
