<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\ReservedUsername;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            // Hide super-admin from the role picker — it's console-only by design.
'roles' => Role::where('name', '!=', 'super-admin')->orderBy('name')->pluck('name'),
        ]);
    }

    public function create(): View
    {
        return view('DashboardPages.user.FormPage', [
            'title' => 'Create user',
            'user' => new User(),
            // Hide super-admin from the role picker — it's console-only by design.
'roles' => Role::where('name', '!=', 'super-admin')->orderBy('name')->pluck('name'),
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
            // Hide super-admin from the role picker — it's console-only by design.
'roles' => Role::where('name', '!=', 'super-admin')->orderBy('name')->pluck('name'),
            'assignedRoles' => $user->roles->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user->id);

        // Super-admins are immutable from the admin UI. The view
        // already disables most fields, but a hand-crafted POST could
        // still slip through — refuse the whole update from the
        // controller as a second line of defence.
        if ($user->hasRole('super-admin') && $user->id !== $request->user()->id) {
            return back()->with('error',
                "\"{$user->username}\" is a super-admin and can only be edited from the console or by themselves.");
        }

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

        // Super-admins are immutable from the admin UI. Removing one is
        // a deliberate two-key action — drop into tinker if you really
        // mean it. This guard runs even when the actor is themselves a
        // super-admin: keeps the top tier symmetric so a single rogue
        // super-admin can't lock the rest out of the platform.
        if ($user->hasRole('super-admin')) {
            return back()->with('error', "\"{$user->username}\" is a super-admin and can't be deleted from the admin UI.");
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
     * Bulk mark-verified for the checkbox selection on the list page.
     * Exists because the Google-signup bug (email_verified_at dropped
     * by mass assignment) left a backlog of real, mailbox-verified
     * users stuck on "Pending" — this is the shovel for that backlog,
     * and generally useful after any import.
     */
    public function bulkVerify(Request $request): RedirectResponse
    {
        $count = User::whereIn('id', $this->bulkIds($request))
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        return back()->with('success', $count === 0
            ? 'All selected users were already verified.'
            : "Marked {$count} user" . ($count === 1 ? '' : 's') . ' as verified.');
    }

    /**
     * Bulk delete. Every row passes the same guards as destroy() —
     * self, super-admin, last-admin — skipped rows are reported by
     * name so the admin knows exactly what didn't happen and why.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $users = User::with('roles')->whereIn('id', $this->bulkIds($request))->get();

        $deleted = 0;
        $skipped = [];

        foreach ($users as $user) {
            if ($user->id === $request->user()->id) {
                $skipped[] = "{$user->username} (yourself)";
            } elseif ($user->hasRole('super-admin')) {
                $skipped[] = "{$user->username} (super-admin)";
            } elseif ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
                $skipped[] = "{$user->username} (last admin)";
            } else {
                $user->delete();
                $deleted++;
            }
        }

        $message = "Deleted {$deleted} user" . ($deleted === 1 ? '' : 's') . '.';
        if ($skipped) {
            $message .= ' Skipped: ' . implode(', ', $skipped) . '.';
        }

        return back()->with($deleted > 0 ? 'success' : 'error', $message);
    }

    /**
     * Validate and return the checkbox selection for the bulk verbs.
     */
    private function bulkIds(Request $request): array
    {
        return $request->validate([
            'ids'   => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
        ])['ids'];
    }

    /**
     * Confirmation page for granting/revoking super-admin. The crown
     * control on the user edit form links here; password.confirm on
     * the route means the password gate has already been passed by
     * the time this renders, so the confirm button submits cleanly.
     */
    public function confirmSuperAdmin(Request $request, User $user): View
    {
        return view('DashboardPages.user.SuperAdminConfirmPage', [
            'title'        => 'Super admin',
            'user'         => $user,
            'isSuperAdmin' => $user->hasRole('super-admin'),
            'isSelf'       => $user->id === $request->user()->id,
        ]);
    }

    /**
     * Grant the super-admin (owner) tier. Route-gated to
     * role:super-admin + password.confirm — only an existing
     * super-admin who has just re-entered their password can reach
     * this. The general role picker still never offers super-admin;
     * this dedicated endpoint is the one UI path, mirroring what the
     * users:make-super-admin console command does.
     */
    public function grantSuperAdmin(Request $request, User $user): RedirectResponse
    {
        if ($user->hasRole('super-admin')) {
            return redirect()->route('dashboard.user-list')
                ->with('error', "\"{$user->username}\" is already a super-admin.");
        }

        // Super-admins always also hold `admin` so role:admin-gated
        // surfaces (/app, /admin/*) keep recognising them.
        $user->assignRole('super-admin');
        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
        }

        Log::info('[rbac] super-admin granted', [
            'target_id' => $user->id,
            'target'    => $user->username,
            'by_id'     => $request->user()->id,
            'by'        => $request->user()->username,
        ]);

        return redirect()->route('dashboard.user-list')->with('success',
            "\"{$user->username}\" is now a super-admin with full platform-owner access.");
    }

    /**
     * Revoke the super-admin tier (the user keeps admin and any other
     * roles). Self-revocation is refused, which also guarantees at
     * least one super-admin always remains: the actor performing the
     * revoke is a super-admin and can only demote OTHERS.
     */
    public function revokeSuperAdmin(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('dashboard.user-list')->with('error',
                "You can't remove your own super-admin role — ask another super-admin to do it.");
        }

        if (!$user->hasRole('super-admin')) {
            return redirect()->route('dashboard.user-list')
                ->with('error', "\"{$user->username}\" is not a super-admin.");
        }

        $user->removeRole('super-admin');

        Log::info('[rbac] super-admin revoked', [
            'target_id' => $user->id,
            'target'    => $user->username,
            'by_id'     => $request->user()->id,
            'by'        => $request->user()->username,
        ]);

        return redirect()->route('dashboard.user-list')->with('success',
            "\"{$user->username}\" is no longer a super-admin. Their admin role is unchanged.");
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
     * Sync roles with two safety nets and one filter:
     *
     *   - Filter: super-admin can never be ASSIGNED via the role
     *     picker. Any 'super-admin' value in the incoming list is
     *     dropped. Granting goes through grantSuperAdmin() (super-
     *     admin-only + password.confirm) or the console command —
     *     never this form, which regular admins can also submit.
     *
     *   - Preserve: if the user is currently a super-admin, that role
     *     stays on no matter what the form submitted. Stops one admin
     *     stripping another's owner status by submitting a roles list
     *     that omits super-admin.
     *
     *   - Default: if no roles selected at all, fall back to `user`
     *     (the RBAC checks assume everyone has at least one role).
     *
     *   - Last-admin guard: removing the admin role from the last
     *     remaining admin would lock the dashboard out. Re-adds admin
     *     after the sync if that would have happened.
     */
    private function syncRoles(User $user, array $roles): void
    {
        // Strip super-admin from the incoming list — UI can never grant it.
        $roles = array_values(array_filter(array_unique($roles), fn ($r) => $r !== 'super-admin'));

        if (empty($roles)) {
            $roles = ['user'];
        }

        $wasAdmin = $user->hasRole('admin');
        $wasSuperAdmin = $user->hasRole('super-admin');

        $user->syncRoles($roles);

        // Preserve super-admin if the user already had it. syncRoles
        // would have dropped it; re-attach so the owner tier survives
        // a regular admin saving the form.
        if ($wasSuperAdmin) {
            $user->assignRole('super-admin');
        }

        // Post-condition: if this demotion would leave zero admins,
        // roll back. Applies on edit, not create (new users aren't
        // the "last admin" by definition).
        if ($wasAdmin && !in_array('admin', $roles, true) && User::role('admin')->count() === 0) {
            $user->assignRole('admin');
        }
    }
}
