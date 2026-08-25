<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Role::class)
            ->using(\App\Models\RoleUser::class)
            ->withTimestamps();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->getAllPermissions()->contains('slug', $slug);
    }

    public function getAllPermissions(): Collection
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Permission::all();
        }

        return cache()->remember("user_{$this->id}_permissions", 3600, function () {
            return $this->roles()
                ->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->unique('id');
        });
    }

    public function clearPermissionCache(): void
    {
        cache()->forget("user_{$this->id}_permissions");
    }
}
