<?php

namespace Winavin\Permissions\Exceptions;

use InvalidArgumentException;

class InvalidPermissionException extends InvalidArgumentException
{
    public function __construct( string $enumClass, string $teamClass )
    {
        parent::__construct( "Invalid Permission: Enum {$enumClass} is not valid for the team model {$teamClass}." );
    }
}
