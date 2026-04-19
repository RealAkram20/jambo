<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\ReservedUsername;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'username'   => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_.\-]+$/', new ReservedUsername(), 'unique:'.User::class],
            'email'      => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password'   => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'username'   => $data['username'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
        ]);

        // Default role so RBAC checks work without an admin having to
        // touch every new signup. The `admin` role stays hand-assigned.
        if (method_exists($user, 'assignRole') && \Spatie\Permission\Models\Role::where('name', 'user')->exists()) {
            $user->assignRole('user');
        }

        event(new Registered($user));

        Auth::login($user);

        // New signups are always regular users (the 'admin' role is
        // hand-assigned only), so we send them to the public frontend,
        // never to the admin dashboard.
        return redirect('/')
            ->with('status', 'Welcome to ' . config('app.name') . '!');
    }
}
