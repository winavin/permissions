<?php

use Winavin\Permissions\Tests\User;
use Winavin\Permissions\Tests\Enums\TestRole;
use Winavin\Permissions\Tests\Enums\TestPermission;

it('can assign and check roles', function () {
    $user = User::create(['email' => 'test@example.com']);

    expect($user->hasRole(TestRole::ADMIN))->toBeFalse();

    $user->assignRole(TestRole::ADMIN);

    // Clear cache? The trait uses Cache::rememberForever.
    // In test environment 'array' driver is usually used, but we might need to clear it manually or ensure keys are unique per test?
    // Pest traits usually handle this if Database migrations reset. But Cache?
    // The trait key logic includes User ID, so safe per user.
    // EXCEPT subsequent calls for SAME user.
    // assignRole calls forgetRolesCacheFor($team).

    expect($user->hasRole(TestRole::ADMIN))->toBeTrue();
    expect($user->hasRole(TestRole::USER))->toBeFalse();
});

it('can assign and check permissions', function () {
    $user = User::create(['email' => 'test2@example.com']);

    expect($user->hasPermission(TestPermission::CREATE_POST))->toBeFalse();

    $user->assignPermission(TestPermission::CREATE_POST);

    expect($user->hasPermission(TestPermission::CREATE_POST))->toBeTrue();
    expect($user->hasPermission(TestPermission::DELETE_POST))->toBeFalse();
});

it('gets permissions through roles', function () {
    $user = User::create(['email' => 'test3@example.com']);

    $user->assignRole(TestRole::ADMIN); // Has CREATE_POST and EDIT_POST

    expect($user->hasPermission(TestPermission::CREATE_POST))->toBeTrue();
    expect($user->hasPermission(TestPermission::EDIT_POST))->toBeTrue();
    expect($user->hasPermission(TestPermission::DELETE_POST))->toBeFalse();
});

it('can sync roles', function () {
    $user = User::create(['email' => 'test4@example.com']);

    $user->assignRole(TestRole::ADMIN);
    expect($user->hasRole(TestRole::ADMIN))->toBeTrue();

    $user->syncRoles([TestRole::USER, TestRole::MANAGER]);

    expect($user->hasRole(TestRole::ADMIN))->toBeFalse();
    expect($user->hasRole(TestRole::USER))->toBeTrue();
    expect($user->hasRole(TestRole::MANAGER))->toBeTrue();
});

it('can sync permissions', function () {
    $user = User::create(['email' => 'test5@example.com']);

    $user->assignPermission(TestPermission::CREATE_POST);

    $user->syncPermissions([TestPermission::DELETE_POST]);

    expect($user->hasPermission(TestPermission::CREATE_POST))->toBeFalse();
    expect($user->hasPermission(TestPermission::DELETE_POST))->toBeTrue();
});

it('can check hasAnyRole', function () {
    $user = User::create(['email' => 'test6@example.com']);
    $user->assignRole(TestRole::USER);

    expect($user->hasAnyRole([TestRole::ADMIN, TestRole::USER]))->toBeTrue();
    expect($user->hasAnyRole([TestRole::ADMIN, TestRole::MANAGER]))->toBeFalse();
});

it('can check hasAllRoles', function () {
    $user = User::create(['email' => 'test7@example.com']);
    $user->assignRole(TestRole::USER);
    $user->assignRole(TestRole::MANAGER);

    expect($user->hasAllRoles([TestRole::USER, TestRole::MANAGER]))->toBeTrue();
    expect($user->hasAllRoles([TestRole::USER, TestRole::ADMIN]))->toBeFalse();
});

it('correctly updates roles on assign and remove', function () {
    $user = User::create(['email' => 'cache1@example.com']);

    expect($user->hasRole(TestRole::ADMIN))->toBeFalse();

    $user->assignRole(TestRole::ADMIN);
    expect($user->hasRole(TestRole::ADMIN))->toBeTrue();

    $user->removeRole(TestRole::ADMIN);
    expect($user->hasRole(TestRole::ADMIN))->toBeFalse();
});

it('correctly updates permissions on assign and remove', function () {
    $user = User::create(['email' => 'cache2@example.com']);

    expect($user->hasPermission(TestPermission::CREATE_POST))->toBeFalse();

    $user->assignPermission(TestPermission::CREATE_POST);
    expect($user->hasPermission(TestPermission::CREATE_POST))->toBeTrue();

    $user->removePermission(TestPermission::CREATE_POST);
    expect($user->hasPermission(TestPermission::CREATE_POST))->toBeFalse();
});

it('validates retrieval of specific permissions sets', function () {
    $user = User::create(['email' => 'cache3@example.com']);

    // No permissions yet
    expect($user->permissions())->toHaveCount(0);
    expect($user->directPermissions())->toHaveCount(0);
    expect($user->permissionsThroughRoles())->toHaveCount(0);

    // Direct permission
    $user->assignPermission(TestPermission::DELETE_POST);

    expect($user->permissions())->toHaveCount(1)
        ->and($user->directPermissions())->toHaveCount(1)
        ->and($user->permissionsThroughRoles())->toHaveCount(0);

    // Permission through role
    $user->assignRole(TestRole::ADMIN); // Gives CREATE_POST, EDIT_POST

    expect($user->permissions())->toHaveCount(3) // delete (direct), create, edit (role)
        ->and($user->directPermissions())->toHaveCount(1)
        ->and($user->permissionsThroughRoles())->toHaveCount(2);
});
