<?php

namespace Winavin\Permissions\Tests\Enums;

enum TestPermission: string
{
    case CREATE_POST = 'create_post';
    case EDIT_POST = 'edit_post';
    case DELETE_POST = 'delete_post';
}
