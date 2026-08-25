<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * List users with search and pagination.
     */
    public function index(Request $request): View
    {
        $query = User::query()->with('roles');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        return view('users.index', compact('users'));
    }

    /**
     * Show the create user form.
     */
    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('users.create-or-edit', compact('roles'));
    }

    /**
     * Store a new user.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->roles()->sync($request->input('roles', []));

        return redirect()->route('users.index')->with('success', 'User added successfully.');
    }

    /**
     * Show the edit user form.
     */
    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();

        return view('users.create-or-edit', compact('user', 'roles'));
    }

    /**
     * Update a user.
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->roles()->sync($request->input('roles', []));
        $user->clearPermissionCache();

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Soft-deactivate a user.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->isSuperAdmin()) {
            return redirect()->route('users.index')->with('error', 'Super Admin cannot be deactivated.');
        }

        $user->update(['is_active' => false]);
        $user->clearPermissionCache();

        return redirect()->route('users.index')->with('success', 'User deactivated successfully.');
    }

    /**
     * Toggle user active status.
     */
    public function toggle(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot change your own account status.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $user->clearPermissionCache();

        return redirect()->route('users.index')->with('success', 'User status updated successfully.');
    }
}
