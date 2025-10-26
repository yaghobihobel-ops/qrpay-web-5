<?php

namespace App\Traits\Auth;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;

trait HasRolesAndPermissions
{
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles')->withTimestamps();
    }

    public function permissions(): MorphToMany
    {
        return $this->morphToMany(Permission::class, 'model', 'model_has_permissions')->withTimestamps();
    }

    public function assignRole(...$roles): self
    {
        $roles = collect($roles)
            ->flatten()
            ->map(fn ($role) => $this->getStoredRole($role))
            ->filter()
            ->pluck('id')
            ->all();

        if (! empty($roles)) {
            $this->roles()->syncWithoutDetaching($roles);
        }

        return $this;
    }

    public function syncRoles(...$roles): self
    {
        $roles = collect($roles)
            ->flatten()
            ->map(fn ($role) => $this->getStoredRole($role))
            ->filter()
            ->pluck('id')
            ->all();

        $this->roles()->sync($roles);

        return $this;
    }

    public function assignPermission(...$permissions): self
    {
        $permissions = collect($permissions)
            ->flatten()
            ->map(fn ($permission) => $this->getStoredPermission($permission))
            ->filter()
            ->pluck('id')
            ->all();

        if (! empty($permissions)) {
            $this->permissions()->syncWithoutDetaching($permissions);
        }

        return $this;
    }

    public function syncPermissions(...$permissions): self
    {
        $permissions = collect($permissions)
            ->flatten()
            ->map(fn ($permission) => $this->getStoredPermission($permission))
            ->filter()
            ->pluck('id')
            ->all();

        $this->permissions()->sync($permissions);

        return $this;
    }

    public function hasRole($roles): bool
    {
        $roles = Arr::wrap($roles);

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function hasAnyRole($roles): bool
    {
        return $this->hasRole($roles);
    }

    public function hasAllRoles($roles): bool
    {
        $roles = Arr::wrap($roles);

        return collect($roles)->every(fn ($role) => $this->hasRole($role));
    }

    public function hasPermissionTo($permissions): bool
    {
        $permissions = Arr::wrap($permissions);

        if ($this->permissions()->whereIn('name', $permissions)->exists()) {
            return true;
        }

        $rolePermissions = $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('name')
            ->unique();

        return collect($permissions)->every(fn ($permission) => $rolePermissions->contains($permission));
    }

    public function hasAnyPermission($permissions): bool
    {
        $permissions = Arr::wrap($permissions);

        if ($this->permissions()->whereIn('name', $permissions)->exists()) {
            return true;
        }

        $rolePermissions = $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('name')
            ->unique();

        return $rolePermissions->intersect($permissions)->isNotEmpty();
    }

    public function getRoleNames(): array
    {
        return $this->roles->pluck('name')->all();
    }

    public function getPermissionNames(): array
    {
        return $this->permissions->pluck('name')->all();
    }

    protected function getStoredRole($role): ?Role
    {
        if ($role instanceof Role) {
            return $role;
        }

        if (is_numeric($role)) {
            return Role::query()->find($role);
        }

        if (is_string($role)) {
            return Role::findOrCreate($role, $this->getDefaultGuardName());
        }

        return null;
    }

    protected function getStoredPermission($permission): ?Permission
    {
        if ($permission instanceof Permission) {
            return $permission;
        }

        if (is_numeric($permission)) {
            return Permission::query()->find($permission);
        }

        if (is_string($permission)) {
            return Permission::findOrCreate($permission, $this->getDefaultGuardName());
        }

        return null;
    }

    protected function getDefaultGuardName(): string
    {
        return property_exists($this, 'guard_name')
            ? $this->guard_name
            : config('auth.defaults.guard', 'web');
    }
}
