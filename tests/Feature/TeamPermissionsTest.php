<?php

use Winavin\Permissions\Tests\User;
use Winavin\Permissions\Tests\Team;
use Winavin\Permissions\Tests\Enums\TestRole;
use Winavin\Permissions\Tests\Enums\TestPermission;

it('can assign and check roles with a team', function () {
    $user = User::create(['email' => 'team1@example.com']);
    $teamA = Team::create(['name' => 'Team A']);
    $teamB = Team::create(['name' => 'Team B']);

    expect($user->hasRole(TestRole::ADMIN, $teamA))->toBeFalse();

    $user->assignRole(TestRole::ADMIN, $teamA);

    expect($user->hasRole(TestRole::ADMIN, $teamA))->toBeTrue();
    expect($user->hasRole(TestRole::ADMIN, $teamB))->toBeFalse();
    expect($user->hasRole(TestRole::ADMIN))->toBeFalse();
});

it('can assign and check permissions with a team', function () {
    $user = User::create(['email' => 'team2@example.com']);
    $teamA = Team::create(['name' => 'Team A']);
    $teamB = Team::create(['name' => 'Team B']);

    expect($user->hasPermission(TestPermission::CREATE_POST, $teamA))->toBeFalse();

    $user->assignPermission(TestPermission::CREATE_POST, $teamA);

    expect($user->hasPermission(TestPermission::CREATE_POST, $teamA))->toBeTrue();
    expect($user->hasPermission(TestPermission::CREATE_POST, $teamB))->toBeFalse();
    expect($user->hasPermission(TestPermission::CREATE_POST))->toBeFalse();
});

it('gets permissions through roles with a team', function () {
    $user = User::create(['email' => 'team3@example.com']);
    $teamA = Team::create(['name' => 'Team A']);

    $user->assignRole(TestRole::ADMIN, $teamA); // Has CREATE_POST and EDIT_POST

    expect($user->hasPermission(TestPermission::CREATE_POST, $teamA))->toBeTrue();
    expect($user->hasPermission(TestPermission::EDIT_POST, $teamA))->toBeTrue();
    expect($user->hasPermission(TestPermission::DELETE_POST, $teamA))->toBeFalse();

    // They shouldn't have it globally or on another team
    $teamB = Team::create(['name' => 'Team B']);
    expect($user->hasPermission(TestPermission::CREATE_POST, $teamB))->toBeFalse();
    expect($user->hasPermission(TestPermission::CREATE_POST))->toBeFalse();
});

it('checks indirect roles globally vs team', function () {
    $user = User::create(['email' => 'team4@example.com']);
    $teamA = Team::create(['name' => 'Team A']);

    // Admin globally
    $user->assignRole(TestRole::ADMIN);

    // Has it globally
    expect($user->hasRole(TestRole::ADMIN))->toBeTrue();
});

it('can get teams the user has roles for', function () {
    $user = User::create(['email' => 'team5@example.com']);
    $teamA = Team::create(['name' => 'Team A']);

    $user->assignRole(TestRole::ADMIN, $teamA);
    $user->assignRole(TestRole::USER, $teamA);

    $user->assignRole(TestRole::MANAGER); // Global role!

    $teams = $user->teams();

    expect($teams)->toHaveCount(1);
});
