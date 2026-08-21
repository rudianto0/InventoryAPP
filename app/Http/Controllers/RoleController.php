<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * List roles with user count.
     */
    public function index(): View
    {
        $roles = Role::withCount('users')->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    /**
     * Show the create role form.
     */
    public function create(): View
    {
        $permissions = $this->groupedPermissions();

        return view('roles.create', compact('permissions'));
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

        return redirect()->route('roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    /**
     * Show the edit role form.
     */
    public function edit(Role $role): View
    {
        $permissions = $this->groupedPermissions();

        return view('roles.edit', compact('role', 'permissions'));
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

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    /**
     * Delete a role (blocked if it is the only role of a user).
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->slug === 'super-admin') {
            return redirect()->route('roles.index')->with('error', 'Role Super Admin tidak dapat dihapus.');
        }

        $users = $role->users()->get();

        foreach ($users as $user) {
            if ($user->roles()->count() === 1) {
                return redirect()->route('roles.index')->with('error', "Role tidak dapat dihapus karena masih dipakai oleh user \"{$user->name}\".");
            }
        }

        foreach ($users as $user) {
            $user->clearPermissionCache();
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
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
