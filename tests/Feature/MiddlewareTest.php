<?php

use Illuminate\Support\Facades\Route;
use Winavin\Permissions\Middlewares\HasAnyRole;
use Winavin\Permissions\Middlewares\HasAllRoles;
use Winavin\Permissions\Middlewares\HasAnyPermission;
use Winavin\Permissions\Middlewares\HasAllPermissions;
use Winavin\Permissions\Tests\User;
use Winavin\Permissions\Tests\Enums\TestRole;
use Winavin\Permissions\Tests\Enums\TestPermission;

beforeEach(function () {
    Route::get('/middleware-test/any-role', function () {
        return 'ok';
    })->middleware(HasAnyRole::class . ':admin|manager');

    Route::get('/middleware-test/all-roles', function () {
        return 'ok';
    })->middleware(HasAllRoles::class . ':admin|manager');

    Route::get('/middleware-test/any-permission', function () {
        return 'ok';
    })->middleware(HasAnyPermission::class . ':create_post|edit_post');

    Route::get('/middleware-test/all-permissions', function () {
        return 'ok';
    })->middleware(HasAllPermissions::class . ':create_post|edit_post');
});

it('allows access if user has any role', function () {
    $user = User::create(['email' => 'm1@example.com']);
    $user->assignRole(TestRole::ADMIN);

    $this->actingAs($user)
        ->get('/middleware-test/any-role')
        ->assertOk();
});

it('denies access if user has no role', function () {
    $user = User::create(['email' => 'm2@example.com']);
    $user->assignRole(TestRole::USER); // Not in admin|manager

    $this->actingAs($user)
        ->get('/middleware-test/any-role')
        ->assertForbidden();
});

it('allows access if user has all roles', function () {
    $user = User::create(['email' => 'm3@example.com']);
    $user->assignRole(TestRole::ADMIN);
    $user->assignRole(TestRole::MANAGER);

    $this->actingAs($user)
        ->get('/middleware-test/all-roles')
        ->assertOk();
});

it('denies access if user misses one role', function () {
    $user = User::create(['email' => 'm4@example.com']);
    $user->assignRole(TestRole::ADMIN);

    $this->actingAs($user)
        ->get('/middleware-test/all-roles')
        ->assertForbidden();
});

it('allows access if user has permission', function () {
    $user = User::create(['email' => 'm5@example.com']);
    $user->assignPermission(TestPermission::CREATE_POST);

    $this->actingAs($user)
        ->get('/middleware-test/any-permission')
        ->assertOk();
});

it('allows access if user has all permissions', function () {
    $user = User::create(['email' => 'm6@example.com']);
    $user->assignPermission(TestPermission::CREATE_POST);
    $user->assignPermission(TestPermission::EDIT_POST);

    $this->actingAs($user)
        ->get('/middleware-test/all-permissions')
        ->assertOk();
});
