<?php

namespace Winavin\Permissions\Tests\Enums;

enum TestRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case MANAGER = 'manager';

    public function permissions(): \Illuminate\Support\Collection
    {
        return match ($this) {
            self::ADMIN => collect([TestPermission::CREATE_POST, TestPermission::EDIT_POST]),
            self::MANAGER => collect([TestPermission::DELETE_POST]),
            default => collect(),
        };
    }
}
