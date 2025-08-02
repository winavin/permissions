<?php

namespace Winavin\Permissions\Traits;

use BackedEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Winavin\Permissions\Contracts\TeamInterface;
use function PHPUnit\Framework\isNull;

trait HasRolesAndPermissions
{
    public function roleRelation(): HasMany
    {
        return $this->hasMany( $this->getRollClass())->with('team');
    }

    public function permissionRelation(): HasMany
    {
        return $this->hasMany( $this->getPermissionClass())->with('team');
    }

    public function roles( ?TeamInterface $team = null) : Collection
    {
        return Cache::rememberForever(
            $this->getCacheKey( $team, 'roles' ),
            fn() => $this->roleRelation()->forTeam($team)->pluck('role'),
        );
    }

    public function directPermissions( ?TeamInterface $team = null ) : Collection
    {
        return Cache::rememberForever(
            $this->getCacheKey( $team, 'direct-permissions' ),
            fn() => $this->permissionRelation()->forTeam($team)->pluck('permission'),
        );
    }

    public function permissionsThroughRoles( ?TeamInterface $team = null ) : Collection
    {
        return $this->roles( $team )
                    ->filter( fn( $role ) => method_exists( $role, 'permissions' ) )
                    ->flatMap( fn( $role ) => $role->permissions() )
                    ->unique()
                    ->values();
    }

    public function permissions( ?TeamInterface $team = null ) : Collection
    {
        return Cache::rememberForever(
            $this->getCacheKey( $team, 'permissions' ),
            fn()
                => collect()
                ->merge( $this->directPermissions( $team ) )
                ->merge( $this->permissionsThroughRoles( $team ) )
                ->unique()
                ->values(),
        );
    }

    public function hasRole( string|BackedEnum $role, ?TeamInterface $team = null ) : bool
    {
        $role = $this->resolveEnumValue($role);

        return Cache::rememberForever(
            $this->getCacheKey( $team, 'hasRole'),
            fn() => $this->roles( $team )->contains( $role ),
        );
    }

    private function checkRoles( array $roles, ?TeamInterface $team = null, bool $requireAll = false ) : bool
    {
        if( empty( $roles ) ) {
            return $this->roles( $team )->isNotEmpty();
        }

        $roles = collect( $roles );

        return $requireAll
            ? $roles->every( fn( $role ) => $this->hasRole( $role, $team ) )
            : $roles->contains( fn( $role ) => $this->hasRole( $role, $team ) );
    }

    public function hasAnyRole( array $roles = [], ?TeamInterface $team = null ) : bool
    {
        return $this->checkRoles( $roles, $team );
    }

    public function hasAllRoles( array $roles, ?TeamInterface $team = null ) : bool
    {
        return $this->checkRoles( $roles, $team, true );
    }

    private function checkPermissions( array $permissions, ?TeamInterface $team = null, bool $requireAll = false ) : bool
    {
        if( empty( $permissions ) ) {
            return $this->permissions( $team )->isNotEmpty();
        }

        $permissions = collect( $permissions );

        return $requireAll
            ? $permissions->every( fn( $permission ) => $this->hasPermission( $permission, $team ) )
            : $permissions->contains( fn( $permission ) => $this->hasPermission( $permission, $team ) );
    }


    public function hasPermission( string|BackedEnum $permission, ?TeamInterface $team = null ) : bool
    {
        $permission = $this->resolveEnumValue($permission);

        return Cache::rememberForever(
            $this->getCacheKey( $team, 'hasPermission', $permission  ),
            fn() => $this->permissions( $team )->contains( $permission ),
        );
    }

    public function hasAnyPermission( array $permissions = [], ?TeamInterface $team = null ) : bool
    {
        return $this->checkPermissions( $permissions, $team );
    }

    public function hasAllPermissions( array $permissions, ?TeamInterface $team = null ) : bool
    {
        return $this->checkPermissions( $permissions, $team, true );
    }

    public function isAbleTo( string|BackedEnum $permission, ?TeamInterface $team = null ) : bool
    {
        return $this->hasPermission( $permission, $team );
    }

    public function assignRole( BackedEnum $role, ?TeamInterface $team = null, ?Carbon $expiresAt = null )
    {
        $this->forgetRolesCacheFor( $team );

        return $this->roleRelation()
                    ->firstOrCreate([
                                        'role'       => $role->value,
                                        'team_type'  => isNull($team) ? get_class( $team ): null,
                                        'team_id'    => $team?->getKey(),
                                        'expires_at' => $expiresAt,
                                    ]);
    }

    public function addRole( BackedEnum $role, ?TeamInterface $team = null, ?Carbon $expiresAt = null )
    {
        return $this->assignRole( $role, $team, $expiresAt );
    }

    public function assignPermission( BackedEnum $permission, ?TeamInterface $team = null, ?Carbon $expiresAt = null )
    {
        $this->forgetPermissionsCacheFor( $team );

        return $this->permissionRelation()->firstOrCreate( [
                                                                'permission' => $permission->value,
                                                                'team_type'  => isNull($team) ? get_class( $team ): null,
                                                                'team_id'    => $team->getKey(),
                                                                'expires_at' => $expiresAt,
                                                            ] );
    }

    public function addPermission( BackedEnum $permission, ?TeamInterface $team = null, ?Carbon $expiresAt = null )
    {
        return $this->assignPermission( $permission, $team, $expiresAt );
    }

    public function removeRole( BackedEnum $role, ?TeamInterface $team = null )
    {
        $this->forgetRolesCacheFor( $team );

        return $this->roleRelation()
                    ->forTeam( $team )
                    ->where( 'role', $role->value )
                    ->delete();
    }

    public function removePermission( BackedEnum $permission, ?TeamInterface $team = null )
    {
        $this->forgetPermissionsCacheFor( $team );

        return $this->permissionRelation()
                    ->forTeam( $team )
                    ->where( 'permission', $permission->value )
                    ->delete();
    }

    public function syncRoles( array $roles, ?TeamInterface $team = null ) : void
    {
        $this->forgetRolesCacheFor( $team );

        $this->roleRelation()->forTeam( $team )->delete();

        foreach( $roles as $role ) {
            $this->assignRole( $role, $team );
        }
    }

    public function syncPermissions( array $permissions, ?TeamInterface $team = null ) : void
    {
        $this->forgetPermissionsCacheFor( $team );

        $this->permissionRelation()->forTeam( $team )->delete();

        foreach( $permissions as $permission ) {
            $this->assignPermission( $permission, $team );
        }
    }

    public function teams()
    {
        $grouped = $this->roleRelation
                        ->groupBy( function( $role ) {
                            return $role->team_type . ':' . $role->team_id;
                        } );

        return $grouped->map( function( $roles ) {

            $team = $roles->first()->team;

            $team->setAttribute( 'roles', $roles->map( function( $role ) {
                return [
                    'name'       => $role->role,
                    'expires_at' => $role->expires_at,
                ];
            } )->values() );

            return $team;

        } )->values();
    }

    // Can be replaced in parent class
    protected function getRollClass() : string
    {
        return get_class($this). 'Roles';
    }

    // Can be replaced in parent class
    protected function getPermissionClass() : string
    {
        return get_class($this). 'Permissions';
    }

    /*
     * Cache Related Private Methods
     */

    private function getCacheKey( ?TeamInterface $team, string $type, string|int|null $value = null ) : string
    {
        $userClass = str_replace( '\\', '.', get_class( $this ) );
        $userId    = $this->id;

        if($team)
        {
            $teamClass = str_replace( '\\', '.', get_class( $team ) );
            $teamId    = $team->getKey();

        }
        $value = is_null( $value ) ? '' : "-" . $value;

        return (isset($teamClass) ? $teamClass.'-': '').(isset($teamId) ? $teamId.'-': '')."$userClass-$userId-$type$value";
    }

    private function forgetCache( $team, string $type ) : void
    {
        $key = $this->getCacheKey( $team, $type );
        Cache::forget( $key );
    }

    private function forgetCacheFor( $team, array $types ) : void
    {
        foreach( $types as $type ) {
            $this->forgetCache( $team, $type );
        }
    }

    private function forgetRolesCacheFor( $team ) : void
    {
        $this->forgetCacheFor( $team, [ 'roles', 'hasRole', 'permissions', 'hasPermission' ] );
    }

    private function forgetPermissionsCacheFor( $team ) : void
    {
        $this->forgetCacheFor( $team, [ 'permissions', 'hasPermission' ] );
    }

    private function resolveEnumValue(string|BackedEnum $value): string
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
