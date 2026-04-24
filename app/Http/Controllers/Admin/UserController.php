<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\ReservedUsername;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Admin CRUD for users.
 *
 * Routes: /user-list/*  (kept under the `dashboard.user-list.*` name
 * so the sidebar link doesn't need rewiring).
 * Middleware: auth + role:admin (applied in routes/web.php).
 *
 * Every method here treats the signed-in admin as the actor. Guards
 * prevent self-deletion and prevent deleting the last remaining
 * admin — either would lock the platform out of its own dashboard.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with('roles:id,name');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%$search%")
                    ->orWhere('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if ($role = $request->query('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if (($status = $request->query('status')) === 'active') {
            $query->whereNull('deactivated_at');
        } elseif ($status === 'deactivated') {
            $query->whereNotNull('deactivated_at');
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('DashboardPages.user.ListPage', [
            'title' => __('dashboard.user_list'),
            'users' => $users,
            'filters' => [
                'q' => $search,
                'role' => $role ?: '',
                'status' => $status ?: '',
            ],
            'totalCount' => User::count(),
            'adminCount' => User::role('admin')->count(),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function create(): View
    {
        return view('DashboardPages.user.FormPage', [
            'title' => 'Create user',
            'user' => new User(),
            'roles' => Role::orderBy('name')->pluck('name'),
            'assignedRoles' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'username'   => $data['username'],
            'email'      => strtolower($data['email']),
            'phone'      => $data['phone'] ?: null,
            'password'   => Hash::make($data['password']),
        ]);

        // Admin-created users skip the email-verification flow unless
        // the admin unticks the "mark verified" box.
        if ($request->boolean('email_verified', true)) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->syncRoles($user, $data['roles'] ?? ['user']);

        return redirect()
            ->route('dashboard.user-list')
            ->with('success', "User \"{$user->username}\" created.");
    }

    public function edit(User $user): View
    {
        return view('DashboardPages.user.FormPage', [
            'title' => "Edit {$user->username}",
            'user' => $user,
            'roles' => Role::orderBy('name')->pluck('name'),
            'assignedRoles' => $user->roles->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user->id);

        $user->fill([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'username'   => $data['username'],
            'email'      => strtolower($data['email']),
            'phone'      => $data['phone'] ?: null,
        ]);

        // Password is optional on edit — empty field means "leave it".
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        // Toggle verified state. Unticking after the user has been
        // verified wipes the stamp and forces them through the
        // verification flow again on next login.
        $user->email_verified_at = $request->boolean('email_verified')
            ? ($user->email_verified_at ?: now())
            : null;

        $user->save();

        $this->syncRoles($user, $data['roles'] ?? []);

        return redirect()
            ->route('dashboard.user-list')
            ->with('success', "User \"{$user->username}\" updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', "You can't delete your own account from here. Use account deactivation in your profile.");
        }

        // Prevent locking the admin group out of the dashboard by
        // deleting the last remaining admin.
        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            return back()->with('error', "Can't delete the last remaining admin. Promote someone else first.");
        }

        $username = $user->username;
        $user->delete();

        return redirect()
            ->route('dashboard.user-list')
            ->with('success', "User \"{$username}\" deleted.");
    }

    /**
     * Shared validation for store + update. Password is required on
     * create, optional on edit (empty means "leave unchanged"). Email
     * + username uniqueness ignore the row being edited.
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $isCreate = $ignoreId === null;

        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'username'   => [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[a-zA-Z0-9_.\-]+$/',
                new ReservedUsername(),
                Rule::unique('users', 'username')->ignore($ignoreId),
            ],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($ignoreId),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => $isCreate
                ? ['required', 'confirmed', Password::defaults()]
                : ['nullable', 'confirmed', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);
    }

    /**
     * Sync roles with a safety net: if the admin hasn't chosen any
     * role, default to `user` (the RBAC checks assume everyone has
     * at least one role). The last-admin guard in destroy() prevents
     * removing the last admin through role changes here too — we
     * check after sync and revert if needed.
     */
    private function syncRoles(User $user, array $roles): void
    {
        $roles = array_values(array_filter(array_unique($roles)));
        if (empty($roles)) {
            $roles = ['user'];
        }

        $wasAdmin = $user->hasRole('admin');
        $user->syncRoles($roles);

        // Post-condition: if this demotion would leave zero admins,
        // roll back. Applies on edit, not create (new users aren't
        // the "last admin" by definition).
        if ($wasAdmin && !in_array('admin', $roles, true) && User::role('admin')->count() === 0) {
            $user->assignRole('admin');
        }
    }
}
