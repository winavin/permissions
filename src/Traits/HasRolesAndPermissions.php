<?php

namespace Winavin\Permissions\Traits;

use BackedEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

trait HasRolesAndPermissions
{
    protected function getRoleEnum($team): string
    {
        return $team->getRoleEnum();
    }

    protected function getPermissionEnum($team): string
    {
        return $team->getPermissionEnum();
    }

    protected function getRoleClass($team): string
    {
        return $this->resolveModelClass('Roles', $team);
    }

    protected function getPermissionClass($team): string
    {
        return $this->resolveModelClass('Permissions', $team);
    }

    protected function getOwnerForeignKey(): string
    {
        return strtolower(class_basename(static::class)) . '_id';
    }

    private function resolveModelClass(string $suffix)
    {
        return $this->resolveClass('App\\Models', 'App\\Models', $suffix);
    }

    private function resolveEnumClass(string $suffix, $team)
    {
        return $this->resolveClass('App\\Models', 'App\\Enums', $suffix, $team);
    }

    private function resolveClass(string $fromNamespace, string $toNamespace, string $suffix, $team): string
    {
        $modelClass = get_class($team);
    
        if (!str_starts_with($modelClass, $fromNamespace)) {
            throw new \RuntimeException("Model [$modelClass] is not within expected namespace [$fromNamespace].");
        }
    
        $relativeClass = substr($modelClass, strlen($fromNamespace));
        $class = rtrim($toNamespace, '\\') . $relativeClass . $suffix;
    
        if (!class_exists($class)) {
            throw new \RuntimeException("Class [$class] not found.");
        }
    
        return $class;
    }

    private function resolveEnumValue(string|BackedEnum $value): string|int
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    private function validateEnumForTeam(BackedEnum|string $value, mixed $team, string $type): void
    {
        $expectedEnum = null;
        $method = $type === 'role' ? 'getRoleEnum' : 'getPermissionEnum';
    
        if (method_exists($team, $method )) {
            $expectedEnum = $team->{$method}();
        } elseif (method_exists($this, $method )) {
            $expectedEnum = $this->{$method}();
        }
    
        if ($expectedEnum && $value::class !== $expectedEnum) {
            throw new InvalidArgumentException("Invalid $type: Enum " . $value::class . " is not valid for the team model " . get_class($team));
        }
    }

    private function getCacheKey($team, string $type): string
    {
        $userClass = str_replace('\\', '.', get_class($this));
        $userId = $this->id;
    
        $teamClass = str_replace('\\', '.', get_class($team));
        $teamId = $team->id;
    
        return "{$userClass}-{$userId}-{$teamClass}-{$teamId}-{$type}";
    }
    
    private function forgetCache($team, string $type): void
    {
        $key = $this->generateCacheKey($team, $type);
        Cache::forget($key);
    }

    private function foregtCacheFor($team, array $types): void
    {
        foreach ($types as $type) {
            $this->forgetCache($team, $type);
        }
    }

    private function foregtRolesCacheFor($team)
    {
        $this->foregtCacheFor($team, ['roles', 'hasRole', 'permissions', 'hasPermission']);
    }

    private function foregtPermissionsCacheFor($team)
    {
        $this->foregtCacheFor($team, ['permissions', 'hasPermission']);
    }

    public function roleRelations($team): HasMany
    {
        return $this->hasMany($this->getRoleClass($team), $this->getOwnerForeignKey());
    }

    public function permissionRelations($team): HasMany
    {
        return $this->hasMany($this->getPermissionClass($team), $this->getOwnerForeignKey());
    }

    public function roles($team): Collection
    {
        return Cache::rememberForever(
            $this->generateCacheKey($team, 'roles'),
            fn() => $this->getEnumCollection(
                relationMethod: 'roleRelations',
                team: $team,
                column: 'role',
                enumResolver: fn($team) => $this->getRoleEnum($team),
            )
        );
    }

    public function directPermissions($team): Collection
    {
        return $this->getEnumCollection(
            relationMethod: 'permissionRelations',
            team: $team,
            column: 'permission',
            enumResolver: fn($team) => $this->getPermissionEnum($team),
        );
    }

    private function getEnumCollection(
        string $relationMethod,
        $team,
        string $column,
        callable $enumResolver,
    ): Collection {

            $query = $this->{$relationMethod}($team);
            $values = $query->forTeam($team)->pluck($column);
            $enumClass = $enumResolver($team);

            return $values->map(fn($value) => $enumClass::from($value) ?? null)->filter();
    }

    public function permissionsThroughRoles($team): Collection
    {
        return $this->roles($team)
            ->filter(fn($role) => method_exists($role, 'permissions'))
            ->flatMap(fn($role) => $role->permissions())
            ->unique()
            ->values();
    }

    public function permissions($team): Collection
    {
        return Cache::rememberForever(
            $this->generateCacheKey($team, 'roles'),
            fn() => collect()
                ->merge($this->directPermissions($team))
                ->merge($this->permissionsThroughRoles($team))
                ->unique()
                ->values()
            );
    }

    public function hasRole(string|BackedEnum $role, $team): bool
    {
        return Cache::rememberForever(
            $this->generateCacheKey($team, 'hasRole'),
            fn() => $this->roles($team)->contains($this->resolveEnumValue($role))
        );
    }

    public function hasAnyRole(array $roles = [], $team): bool
    {
        if (empty($roles)) {
            return $this->roles($team)->isNotEmpty();
        }

        return collect($roles)->contains(fn ($role) => $this->hasRole($role, $team));
    }

    public function hasAllRoles(array $roles, $team): bool
    {
        return collect($roles)->every(fn ($role) => $this->hasRole($role, $team));
    }

    public function hasPermission(string|BackedEnum $permission, $team): bool
    {
        return Cache::rememberForever(
            $this->generateCacheKey($team, 'hasPermission'),
            fn() => $this->permissions($team)->contains($this->resolveEnumValue($permission))
        );
    }

    public function hasAnyPermission(array $permissions = [], $team): bool
    {
        if (empty($permissions)) {
            return $this->permissions($team)->isNotEmpty();
        }

        return collect($permissions)->contains(fn ($permission) => $this->hasPermission($permission, $team));
    }

    public function hasAllPermissions(array $permissions, $team): bool
    {
        return collect($permissions)->every(fn ($permission) => $this->hasPermission($permission, $team));
    }

    public function isAbleTo(string|BackedEnum $permission, $team): bool
    {
        return $this->hasPermission($permission, $team);
    }

    public function assignRole(BackedEnum $role, $team)
    {
        $this->validateEnumForTeam($role, $team, 'role');

        $this->foregtRolesCacheFor($team);

        return $this->roleRelations($team)->firstOrCreate([
            'role' => $role->value,
            'team_type' => get_class($team),
            'team_id' => $team->id,
        ]);
    }

    public function addRole(BackedEnum $role, $team)
    {
        return $this->assignRole($role, $team);
    }

    public function assignPermission(BackedEnum $permission, $team)
    {
        $this->validateEnumForTeam($permission, $team, 'permission');

        $this->foregtPermissionsCacheFor($team);

        return $this->permissionRelations($team)->firstOrCreate([
            'permission' => $permission->value,
            'team_type' => get_class($team),
            'team_id' => $team->id,
        ]);
    }

    public function addPermission(BackedEnum $permission, $team)
    {
        return $this->assignPermission($permission, $team);
    }

    public function removeRole(BackedEnum $role, $team)
    {
        $this->foregtRolesCacheFor($team);

        return $this->roleRelations($team)
                    ->forTeam($team)
                    ->where('role', $role->value)
                    ->delete();
    }

    public function removePermission(BackedEnum $permission, $team)
    {
        $this->foregtPermissionsCacheFor($team);

        return $this->permissionRelations($team)
                    ->forTeam($team)
                    ->where('permission', $permission->value)
                    ->delete();
    }

    public function syncRoles(array $roles, $team)
    {
        $this->foregtRolesCacheFor($team);

        $this->roleRelations($team)->forTeam($team)->delete();

        foreach ($roles as $role) {
            $this->assignRole($role, $team);
        }
    }

    public function syncPermissions(array $permissions, $team)
    {
        $this->foregtPermissionsCacheFor($team);

        $this->permissionRelations($team)->forTeam($team)->delete();

        foreach ($permissions as $permission) {
            $this->assignPermission($permission, $team);
        }
    }
}
