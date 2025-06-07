<?php

namespace Winavin\Permissions\Exceptions;

use InvalidArgumentException;

class InvalidRoleException extends InvalidArgumentException
{
    public function __construct( string $enumClass, string $teamClass )
    {
        parent::__construct( "Invalid Role: Enum {$enumClass} is not valid for the team model {$teamClass}." );
    }
}
