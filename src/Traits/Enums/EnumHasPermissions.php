<?php

namespace Winavin\Permissions\Traits\Enums;

use BackedEnum;

trait EnumHasPermissions
{
    public function hasPermission( string|BackedEnum $permission ) : bool
    {
        return in_array( $permission, $this->permissions() );
    }

    public function hasAnyPermission( array $permissions ) : bool
    {
        $hasPermission = false;
        foreach( $permissions as $permission ) {
            if( $this->hasPermission( $permission ) ) {
                $hasPermission = true;
                break;
            }
        }
        return $hasPermission;
    }

    public function hasAllPermissions( array $permissions ) : bool
    {
        $hasPermission = true;
        foreach( $permissions as $permission ) {
            if( !$this->hasPermission( $permission ) ) {
                $hasPermission = false;
                break;
            }
        }
        return $hasPermission;
    }
}
