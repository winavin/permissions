<?php

namespace Winavin\Permissions\Traits;

use BackedEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Winavin\Permissions\Exceptions\InvalidPermissionException;
use Winavin\Permissions\Exceptions\InvalidRoleException;

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

    private function resolveClass(string $fromNamespace, string $toNamespace, string $suffix, $team = null): string
    {
        $modelClass = get_class($team ?? $this);
    
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
        $method = $type === 'role' ? 'getRoleEnum' : 'getPermissionEnum';
        
        $expectedEnum = method_exists($team, $method) ? $team->{$method}() : null;
    
        if ($expectedEnum && $value::class !== $expectedEnum) {
            $enumClass = $value::class;
            $teamClass = get_class($team);
    
            if ($type === 'role') {
                throw new InvalidRoleException($enumClass, $teamClass);
            } else {
                throw new InvalidPermissionException($enumClass, $teamClass);
            }
        }
    }    

    private function generateCacheKey($team, string $type, string|int|null $value = null): string
    {
        $userClass = str_replace('\\', '.', get_class($this));
        $userId = $this->id;
    
        $teamClass = str_replace('\\', '.', get_class($team));
        $teamId = $team->id;

        $value = is_null($value) ? '' : "-".$value;
    
        return "{$userClass}-{$userId}-{$teamClass}-{$teamId}-{$type}-{$value}";
    }
    
    private function forgetCache($team, string $type): void
    {
        $key = $this->generateCacheKey($team, $type);
        Cache::forget($key);
    }

    private function forgetCacheFor($team, array $types): void
    {
        foreach ($types as $type) {
            $this->forgetCache($team, $type);
        }
    }

    private function foregtRolesCacheFor($team)
    {
        $this->forgetCacheFor($team, ['roles', 'hasRole', 'permissions', 'hasPermission']);
    }

    private function foregtPermissionsCacheFor($team)
    {
        $this->forgetCacheFor($team, ['permissions', 'hasPermission']);
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

            return $values->map(fn($value) => $enumClass::tryFrom($value) ?? null)->filter();
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
            $this->generateCacheKey($team, 'permissions'),
            fn() => collect()
                ->merge($this->directPermissions($team))
                ->merge($this->permissionsThroughRoles($team))
                ->unique()
                ->values()
            );
    }

    public function hasRole(string|BackedEnum $role, $team): bool
    {
        $this->validateEnumForTeam($role, $team, 'role');

        return Cache::rememberForever(
            $this->generateCacheKey($team, 'hasRole', $this->resolveEnumValue($role)),
            fn() => $this->roles($team)->contains($role)
        );
    }
    
    private function checkRoles(array $roles, $team, bool $requireAll = false): bool
    {
        if (empty($roles)) {
            return $this->roles($team)->isNotEmpty();
        }
    
        $roles = collect($roles);
    
        return $requireAll
            ? $roles->every(fn ($role) => $this->hasRole($role, $team))
            : $roles->contains(fn ($role) => $this->hasRole($role, $team));
    }

    public function hasAnyRole(array $roles = [], $team): bool
    {
        return $this->checkRoles($roles, $team, false);
    }
    
    public function hasAllRoles(array $roles, $team): bool
    {
        return $this->checkRoles($roles, $team, true);
    }

    private function checkPermissions(array $permissions, $team, bool $requireAll = false): bool
    {
        if (empty($permissions)) {
            return $this->permissions($team)->isNotEmpty();
        }
    
        $permissions = collect($permissions);
    
        return $requireAll
            ? $permissions->every(fn ($permission) => $this->hasPermission($permission, $team))
            : $permissions->contains(fn ($permission) => $this->hasPermission($permission, $team));
    }
    

    public function hasPermission(string|BackedEnum $permission, $team): bool
    {
        $this->validateEnumForTeam($permission, $team, 'permission');

        return Cache::rememberForever(
            $this->generateCacheKey($team, 'hasPermission', $this->resolveEnumValue($permission)),
            fn() => $this->permissions($team)->contains($permission)
        );
    }

    public function hasAnyPermission(array $permissions = [], $team): bool
    {
        return $this->checkPermissions($permissions, $team, false);
    }
    
    public function hasAllPermissions(array $permissions, $team): bool
    {
        return $this->checkPermissions($permissions, $team, true);
    }

    public function isAbleTo(string|BackedEnum $permission, $team): bool
    {
        return $this->hasPermission($permission, $team);
    }

    public function assignRole(BackedEnum $role, $team, ?Carbon $expiresAt = null)
    {
        $this->validateEnumForTeam($role, $team, 'role');

        $this->foregtRolesCacheFor($team);

        return $this->roleRelations($team)->firstOrCreate([
            'role' => $role->value,
            'team_type' => get_class($team),
            'team_id' => $team->id,
            'expires_at' => $expiresAt,
        ]);
    }

    public function addRole(BackedEnum $role, $team, ?Carbon $expiresAt = null)
    {
        return $this->assignRole($role, $team, $expiresAt);
    }

    public function assignPermission(BackedEnum $permission, $team, ?Carbon $expiresAt = null)
    {
        $this->validateEnumForTeam($permission, $team, 'permission');

        $this->foregtPermissionsCacheFor($team);

        return $this->permissionRelations($team)->firstOrCreate([
            'permission' => $permission->value,
            'team_type' => get_class($team),
            'team_id' => $team->id,
            'expires_at' => $expiresAt,
        ]);
    }

    public function addPermission(BackedEnum $permission, $team, ?Carbon $expiresAt = null)
    {
        return $this->assignPermission($permission, $team, $expiresAt);
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
