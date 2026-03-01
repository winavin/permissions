<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    // Clean up directories that might have generated files
    File::deleteDirectory(app_path('Models'));
    File::deleteDirectory(app_path('Enums'));
});

afterEach(function () {
    File::deleteDirectory(app_path('Models'));
    File::deleteDirectory(app_path('Enums'));

    // Clean up migrations
    $migrations = File::files(database_path('migrations'));
    foreach ($migrations as $migration) {
        if (str_contains($migration->getFilename(), 'roles_permissions_tables.php')) {
            File::delete($migration->getPathname());
        }
    }
});

it('can make enums with default structure', function () {
    $this->artisan('permissions:make-enums', ['name' => 'Department'])
        ->assertExitCode(0);

    $this->assertFileExists(app_path('Enums/DepartmentRole.php'));
    $this->assertFileExists(app_path('Enums/DepartmentPermission.php'));

    $content = File::get(app_path('Enums/DepartmentRole.php'));
    expect($content)->toContain('namespace App\Enums;');
    expect($content)->toContain('enum DepartmentRole : string');
});

it('can make enums with subfolder structure', function () {
    $this->artisan('permissions:make-enums', [
        'name' => 'Department',
        '--path' => 'SubDir/Test',
    ])->assertExitCode(0);

    $this->assertFileExists(app_path('Enums/SubDir/Test/DepartmentRole.php'));
    $this->assertFileExists(app_path('Enums/SubDir/Test/DepartmentPermission.php'));

    $content = File::get(app_path('Enums/SubDir/Test/DepartmentRole.php'));
    expect($content)->toContain('namespace App\Enums\SubDir\Test;');
});

it('can make model with default structure', function () {
    $this->artisan('permissions:make-model', ['name' => 'User'])
        ->assertExitCode(0);

    $this->assertFileExists(app_path('Models/UserRoles.php'));
    $this->assertFileExists(app_path('Models/UserPermissions.php'));

    $content = File::get(app_path('Models/UserRoles.php'));
    expect($content)->toContain('namespace App\Models;');
    expect($content)->toContain('class UserRoles extends Role');

    // Check if migration was generated
    $migrations = File::files(database_path('migrations'));
    $hasMigration = false;
    foreach ($migrations as $migration) {
        if (str_contains($migration->getFilename(), '_create_user_roles_permissions_tables.php')) {
            $hasMigration = true;
            break;
        }
    }
    expect($hasMigration)->toBeTrue();
});

it('can make model with subfolder structure', function () {
    $this->artisan('permissions:make-model', [
        'name' => 'User',
        '--path' => 'Auth/Custom',
    ])->assertExitCode(0);

    $this->assertFileExists(app_path('Models/Auth/Custom/UserRoles.php'));
    $this->assertFileExists(app_path('Models/Auth/Custom/UserPermissions.php'));

    $content = File::get(app_path('Models/Auth/Custom/UserRoles.php'));
    expect($content)->toContain('namespace App\Models\Auth\Custom;');
});

it('installs with multiple user models and multiple team models', function () {
    // Create dummy classes so class_exists() passes in InstallCommand
    File::ensureDirectoryExists(app_path('Models/Admin'));

    $dummyClasses = [
        'Models/User.php' => '<?php namespace App\Models; class User {}',
        'Models/Admin/SuperAdmin.php' => '<?php namespace App\Models\Admin; class SuperAdmin {}',
        'Models/Team.php' => '<?php namespace App\Models; class Team {}',
        'Models/Department.php' => '<?php namespace App\Models; class Department {}',
        'Models/Organization.php' => '<?php namespace App\Models; class Organization {}',
        'Models/Branch.php' => '<?php namespace App\Models; class Branch {}',
    ];

    foreach ($dummyClasses as $path => $content) {
        if (!File::exists(app_path($path))) {
            File::put(app_path($path), $content);
            require_once app_path($path);
        }
    }

    Config::set('permissions.teams.is_enabled', true);
    Config::set('permissions.models', [
        'App\Models\User' => [
            'App\Models\Team',
            'App\Models\Department',
        ],
        'App\Models\Admin\SuperAdmin' => [
            'App\Models\Organization',
            'App\Models\Branch',
        ],
    ]);

    $this->artisan('permissions:install', ['--force' => true])
        ->assertExitCode(0);

    // Assert User Models got their migrations and models
    $this->assertFileExists(app_path('Models/UserRoles.php'));
    $this->assertFileExists(app_path('Models/UserPermissions.php'));

    $this->assertFileExists(app_path('Models/Admin/SuperAdminRoles.php'));
    $this->assertFileExists(app_path('Models/Admin/SuperAdminPermissions.php'));

    // Assert check namespace for nested user model
    $content = File::get(app_path('Models/Admin/SuperAdminRoles.php'));
    expect($content)->toContain('namespace App\Models\Admin;');

    // Assert Team Enums got generated
    $this->assertFileExists(app_path('Enums/TeamRole.php'));
    $this->assertFileExists(app_path('Enums/DepartmentRole.php'));
    $this->assertFileExists(app_path('Enums/OrganizationRole.php'));
    $this->assertFileExists(app_path('Enums/BranchRole.php'));
});

it('can uninstall generated models, enums and migrations', function () {
    // Scaffold test models
    File::ensureDirectoryExists(app_path('Models/Admin'));

    $dummyClasses = [
        'Models/User.php' => '<?php namespace App\Models; class User {}',
        'Models/Team.php' => '<?php namespace App\Models; class Team {}',
    ];

    foreach ($dummyClasses as $path => $content) {
        if (!File::exists(app_path($path))) {
            File::put(app_path($path), $content);
            require_once app_path($path);
        }
    }

    Config::set('permissions.teams.is_enabled', true);
    Config::set('permissions.models', [
        'App\Models\User' => [
            'App\Models\Team',
        ],
    ]);

    $this->artisan('permissions:install', ['--force' => true])
        ->assertExitCode(0);

    // Verify they exist
    $this->assertFileExists(app_path('Models/UserRoles.php'));
    $this->assertFileExists(app_path('Enums/TeamRole.php'));

    // Check migrations
    $migrations = File::files(database_path('migrations'));
    $migrationExists = collect($migrations)->contains(function ($file) {
        return str_contains($file->getFilename(), '_create_user_roles_permissions_tables.php');
    });
    expect($migrationExists)->toBeTrue();

    // Now run uninstall
    $this->artisan('permissions:uninstall')
        ->assertExitCode(0);

    // Assert files are deleted
    $this->assertFileDoesNotExist(app_path('Models/UserRoles.php'));
    $this->assertFileDoesNotExist(app_path('Enums/TeamRole.php'));

    $migrations = File::files(database_path('migrations'));
    $migrationExists = collect($migrations)->contains(function ($file) {
        return str_contains($file->getFilename(), '_create_user_roles_permissions_tables.php');
    });
    expect($migrationExists)->toBeFalse();
});
