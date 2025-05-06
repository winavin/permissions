<?php

namespace Winavin\Permissions\Traits;

use BackedEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait HasRolesAndPermissions
{
    protected function getRoleEnum($team = null): string
    {
        return $this->resolveEnumClass('Role', $team);
    }
    
    protected function getPermissionEnum($team = null): string
    {
        return $this->resolveEnumClass('Permission', $team);
    }
    
    protected function getRoleClass($team = null): string
    {
        return $this->resolveModelClass('Roles', $team);
    }
    
    protected function getPermissionClass($team = null): string
    {
        return $this->resolveModelClass('Permissions', $team);
    }

    protected function resolveModelClass(string $suffix, $team = null)
    {
        return $this->resolveClass('App\\Models', 'App\\Models', $suffix, $team);
    }

    protected function resolveEnumClass(string $suffix, $team = null)
    {
        return $this->resolveClass('App\\Models', 'App\\Enums', $suffix, $team);
    }

    protected function resolveClass(string $fromNamespace, string $toNamespace, string $suffix, $team = null): string
    {
        $modelClass = $team ? get_class($team) : static::class;
    
        if (!str_starts_with($modelClass, $fromNamespace)) {
            throw new \RuntimeException("Model [$modelClass] is not within expected namespace [$fromNamespace].");
        }
    
        // Replace the base namespace and append suffix
        $relativeClass = substr($modelClass, strlen($fromNamespace));
        $class = rtrim($toNamespace, '\\') . $relativeClass . $suffix;
    
        if (!class_exists($class)) {
            throw new \RuntimeException("Class [$class] not found.");
        }
    
        return $class;
    }

    protected function getOwnerForeignKey(): string
    {
        return strtolower(class_basename(static::class)) . '_id';
    }

    protected function applyTeamScope(Builder|HasMany $query, $team = null): Builder|HasMany
    {
        return $query->when($team, fn($q) => $q->where('team_type', get_class($team))
                                              ->where('team_id', $team->id));
    }

    protected function resolveEnumValue(string|BackedEnum $value): string|int
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    protected function cacheKey(string $type, $team = null, $identifier = null): string
    {
        $teamKey = $team ? "{$team->id}-" : '';
        $identifierKey = $identifier ? "-{$identifier}" : '';
        return "{$type}:{$this->getKey()}:{$teamKey}{$identifierKey}";
    }

    protected function clearRoleCache($team = null): void
    {
        // Clear all related cache keys
        Cache::forget($this->cacheKey('roles', $team));
        Cache::forget($this->cacheKey('has_role', $team));
    }

    protected function clearPermissionCache($team = null): void
    {
        // Clear all related cache keys
        Cache::forget($this->cacheKey('direct_permissions', $team));
        Cache::forget($this->cacheKey('has_permission', $team));
    }

    public function roleRelations($team = null): HasMany
    {
        return $this->hasMany($this->getRoleClass($team), $this->getOwnerForeignKey());
    }

    public function permissionRelations($team = null): HasMany
    {
        return $this->hasMany($this->getPermissionClass($team), $this->getOwnerForeignKey());
    }

    public function roles($team = null): Collection
    {
        // Cache the result for faster access
        return Cache::rememberForever($this->cacheKey('roles', $team), function () use ($team) {
            return $this->applyTeamScope($this->roleRelations($team), $team)
                        ->pluck('role');
        });
    }

    public function directPermissions($team = null): Collection
    {
        // Cache the result for faster access
        return Cache::rememberForever($this->cacheKey('direct_permissions', $team), function () use ($team) {
            return $this->applyTeamScope($this->permissionRelations($team), $team)
                        ->pluck('permission');
        });
    }

    public function permissionsThroughRoles($team = null): Collection
    {
        $roleEnumClass = $this->getRoleEnum($team);
        $permissions = [];

        foreach ($this->roles($team) as $role) {
            $enum = $roleEnumClass::tryFrom($role);
            if ($enum && method_exists($enum, 'permissions')) {
                $permissions = array_merge($permissions, $enum->permissions());
            }
        }

        return collect(array_unique($permissions));
    }

    public function permissions($team = null): Collection
    {
        return $this->directPermissions($team)
                    ->merge($this->permissionsThroughRoles($team))
                    ->unique()
                    ->values();
    }

    public function hasRole(string|BackedEnum $role, $team = null): bool
    {
        $cacheKey = $this->cacheKey('has_role', $team, $role);
        return Cache::rememberForever($cacheKey, function () use ($role, $team) {
            return $this->roles($team)->contains($this->resolveEnumValue($role));
        });
    }

    public function hasAnyRole(array $roles = [], $team = null): bool
    {
        // If no roles are provided, check if the user has any roles
        if (empty($roles)) {
            return ! empty($this->roles($team));
        }

        // Check if the user has any of the provided roles
        $hasAnyRole = false;
        foreach ($roles as $role) {
            if ($this->hasRole($role, $team)) {
                $hasAnyRole = true;
                break;
            }
        }
        return $hasAnyRole;
    }

    public function hasAllRoles(array $roles, $team = null): bool
    {
        $hasAllRoles = true;
        foreach ($roles as $role) {
            if ($this->hasRole($role, $team)) {
                $hasAllRoles = false;
                break;
            }
        }
        return $hasAllRoles;
    }

    public function hasPermission(string|BackedEnum $permission, $team = null): bool
    {
        $cacheKey = $this->cacheKey('has_permission', $team, $permission);
        return Cache::rememberForever($cacheKey, function () use ($permission, $team) {
            return $this->permissions($team)->contains($this->resolveEnumValue($permission));
        });
    }
    

    public function hasAnyPermission(array $permissions = [], $team = null): bool
    {
        // If no permissions are provided, check if the user has any permission
        if (empty($permissions)) {
            return ! empty($this->permissions($team, $team));
        }

        // Check if the user has any of the provided permission
        $hasAnyPermission = false;
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission, $team)) {
                $hasAnyPermission = true;
                break;
            }
        }
        return $hasAnyPermission;
    }
    

    public function hasAllPermissions(array $permissions, $team = null): bool
    {
        $hasAllPermissions = true;
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission, $team)) {
                $hasAllPermissions = false;
                break;
            }
        }
        return $hasAllPermissions;
    }

    public function isAbleTo(string|BackedEnum $permission, $team = null): bool
    {
        return $this->hasPermission($permission, $team);
    }

    public function assignRole(BackedEnum $role, $team = null)
    {
        // Clear cache related to roles and permissions
        $this->clearRoleCache($team);
        
        return $this->roleRelations($team)->create([
            'role' => $role->value,
            'team_type' => $team ? get_class($team) : null,
            'team_id' => $team?->id,
        ]);
    }

    public function addRole(BackedEnum $role, $team = null)
    {
        return $this->assignRole($role, $team);
    }

    public function assignPermission(BackedEnum $permission, $team = null)
    {
        // Clear cache related to permissions
        $this->clearPermissionCache($team);
        
        return $this->permissionRelations($team)->create([
            'permission' => $permission->value,
            'team_type' => $team ? get_class($team) : null,
            'team_id' => $team?->id,
        ]);
    }

    public function addPermission(BackedEnum $permission, $team = null)
    {
        return $this->assignPermission($permission, $team);
    }

    public function removeRole(BackedEnum $role, $team = null)
    {
        // Clear cache related to roles
        $this->clearRoleCache($team);

        return $this->applyTeamScope($this->roleRelations($team), $team)
                    ->where('role', $role->value)
                    ->delete();
    }

    public function removePermission(BackedEnum $permission, $team = null)
    {
        // Clear cache related to permissions
        $this->clearPermissionCache($team);

        return $this->applyTeamScope($this->permissionRelations($team), $team)
                    ->where('permission', $permission->value)
                    ->delete();
    }

    public function syncRoles(array $roles, $team = null)
    {
        // Clear cache related to roles before syncing
        $this->clearRoleCache($team);

        $this->applyTeamScope($this->roleRelations($team), $team)->delete();

        foreach ($roles as $role) {
            $this->assignRole($role, $team);
        }
    }

    public function syncPermissions(array $permissions, $team = null)
    {
        // Clear cache related to roles before syncing
        $this->clearPermissionCache($team);

        $this->applyTeamScope($this->permissionRelations($team), $team)->delete();

        foreach ($permissions as $permission) {
            $this->assignPermission($permission, $team);
        }
    }
}
