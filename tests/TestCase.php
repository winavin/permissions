<?php

namespace Winavin\Permissions\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Winavin\Permissions\PermissionsServiceProvider;
use Illuminate\Foundation\Auth\User as AuthUser;
use Winavin\Permissions\Traits\HasRolesAndPermissions;
use Illuminate\Database\Schema\Blueprint;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../stubs/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            PermissionsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('auth.providers.users.model', User::class);
        config()->set('app.key', 'base64:6Cu/ozj4w03U46sNItistlrIqM5525d97+z8Q8577qE=');

        /*
        $migration = include __DIR__.'/../database/migrations/create_permissions_table.php.stub';
        $migration->up();
        */

        $app['config']->set('permissions.models.users', [User::class]);
    }

    protected function defineDatabaseMigrations()
    {
        // $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->nullableMorphs('team');
            $table->string('role');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->nullableMorphs('team');
            $table->string('permission');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
}

class User extends AuthUser
{
    use HasRolesAndPermissions;

    protected $guarded = [];
}

class UserRoles extends \Winavin\Permissions\Models\Role
{
    protected $table = 'user_roles';
    protected $guarded = [];
    protected $casts = [
        'role' => \Winavin\Permissions\Tests\Enums\TestRole::class
    ];
}

class UserPermissions extends \Winavin\Permissions\Models\Permission
{
    protected $table = 'user_permissions';
    protected $guarded = [];
    protected $casts = [
        'permission' => \Winavin\Permissions\Tests\Enums\TestPermission::class
    ];
}
