<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermission extends Controller
{
    // Declared to silence PHP 8.2 dynamic-property deprecation.
    protected string $module_title = 'Access control';
    protected string $module_name = 'permission';

    public function index()
    {
        return view('permission-role.permissions', [
            'module_title' => $this->module_title,
            'module_action' => 'List',
            'roles' => Role::orderBy('is_fixed', 'desc')->orderBy('name')->get(),
            'modules' => config('constant.MODULES'),
            'permissions' => Permission::get(),
        ]);
    }

    public function store(Request $request, Role $role_id)
    {
        // if (env('IS_DEMO')) {
        //     return redirect()->back()->with('error', __('messages.permission_denied'));
        // }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = Permission::get()->pluck('name')->toArray();
        $role_id->revokePermissionTo($permissions);
        if (isset($request->permission) && is_array($request->permission)) {
            foreach ($request->permission as $permission => $roles) {
                $pr = Permission::findOrCreate($permission);
                $role_id->permissions()->syncWithoutDetaching([$pr->id]);
            }
        }

        \Artisan::call('cache:clear');

        return redirect()->route('backend.permission-role')->withSuccess(__('permission-role.save_form'));
    }

    public function reset_permission($role_id)
    {
        $message = __('messages.reset_form', ['form' => __('page.lbl_role')]);
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            $role = Role::find($role_id);

            $permissions = Permission::get()->pluck('name')->toArray();

            if ($role) {
                $role->permissions()->detach();
            }

            \Artisan::call('cache:clear');
        } catch (\Exception $th) {
        }

        return response()->json(['status' => true, 'message' => $message]);
    }
}
?>
