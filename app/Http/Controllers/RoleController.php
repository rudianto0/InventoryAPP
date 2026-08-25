<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * List roles with search and user count.
     */
    public function index(Request $request): View
    {
        $query = Role::withCount('users');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $roles = $query->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    /**
     * Show the create role form.
     */
    public function create(): View
    {
        $permissions = $this->groupedPermissions();

        return view('roles.create-or-edit', compact('permissions'));
    }

    /**
     * Store a new role.
     */
    public function store(RoleRequest $request): RedirectResponse
    {
        $slug = $request->filled('slug')
            ? Str::slug($request->slug)
            : Str::slug($request->name);

        $role = Role::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
        ]);

        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('roles.index')->with('success', 'Role added successfully.');
    }

    /**
     * Show the edit role form.
     */
    public function edit(Role $role): View
    {
        $permissions = $this->groupedPermissions();

        return view('roles.create-or-edit', compact('role', 'permissions'));
    }

    /**
     * Update a role.
     */
    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $role->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        $role->permissions()->sync($request->input('permissions', []));

        foreach ($role->users as $user) {
            $user->clearPermissionCache();
        }

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Delete a role (blocked if it is the only role of a user).
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->slug === 'super-admin') {
            return redirect()->route('roles.index')->with('error', 'Super Admin role cannot be deleted.');
        }

        $users = $role->users()->get();

        foreach ($users as $user) {
            if ($user->roles()->count() === 1) {
                return redirect()->route('roles.index')->with('error', "Role cannot be deleted because it is still assigned to user \"{$user->name}\".");
            }
        }

        foreach ($users as $user) {
            $user->clearPermissionCache();
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }

    /**
     * Permissions grouped by module.
     */
    private function groupedPermissions()
    {
        return Permission::orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');
    }
}
